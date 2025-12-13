<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_modification_projet.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_creation_experience_2.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_projet.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_modification_experience.php';

$bdd = connectBDD();
verification_connexion($bdd);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

$id_compte = $_SESSION['ID_compte'];
$id_experience = isset($_GET['id_experience']) ? (int)$_GET['id_experience'] : 0;

$erreur = null;
$success = null;

$id_projet = get_projet_from_experience($bdd, $id_experience);
$experimentateurs_selectionnes = [];
$materiels_selectionnes = [];
$nom_salle_selectionnee = '';

if ($id_experience === 0) {
    $erreur = "ID d'expérience manquant.";
} else {
    $experimentateurs_selectionnes = get_experimentateurs_ids($bdd, $id_experience);
    $materiels_selectionnes = get_materiels_experience($bdd, $id_experience);
    $nom_salle_selectionnee = get_salle_from_experience($bdd, $id_experience);
    }

    $experimentateurs_selectionnes = get_experimentateurs_ids($bdd, $id_experience);
    $materiels_selectionnes = get_materiels_experience($bdd, $id_experience);
    $nom_salle_selectionnee = get_salle_from_experience($bdd, $id_experience);


$experimentateurs_info = [];
if (!empty($experimentateurs_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($experimentateurs_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE ID_compte IN ($placeholders)");
    $stmt->execute($experimentateurs_selectionnes);
    $experimentateurs_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer les salles et matériels
$salles = recup_salles($bdd);
$materiels = [];
if ($nom_salle_selectionnee) {
    $materiels = recuperer_materiels_salle($bdd, $nom_salle_selectionnee);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erreur) {
    
    // Récupérer les listes depuis les champs cachés
    $experimentateurs_selectionnes = isset($_POST["experimentateurs_ids"]) && $_POST["experimentateurs_ids"] !== '' 
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["experimentateurs_ids"])))) 
        : [];
    
    $materiels_selectionnes = isset($_POST["materiels_selectionnes"]) && $_POST["materiels_selectionnes"] !== ''
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["materiels_selectionnes"]))))
        : [];

    // Gestion des actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_experimentateur':
                if (!empty($_POST['nom_experimentateur'])) {
                    $id = trouver_id_par_nom_complet($bdd, $_POST['nom_experimentateur']);
                    if ($id && !in_array($id, $experimentateurs_selectionnes)) {
                        $experimentateurs_selectionnes[] = $id;
                    }
                }
                break;

            case 'retirer_experimentateur':
                if (!empty($_POST['id_retirer'])) {
                    $experimentateurs_selectionnes = array_values(array_diff($experimentateurs_selectionnes, [intval($_POST['id_retirer'])]));
                }
                break;

            case 'ajouter_materiel':
                if (!empty($_POST['Materiel']) && isset($_POST['nom_salle'])) {
                    $nom_materiel = trim($_POST['Materiel']);
                    $nom_salle = $_POST['nom_salle'];
                    $id_materiel = recuperer_id_materiel_par_nom($bdd, $nom_materiel, $nom_salle);
                    if ($id_materiel && !in_array($id_materiel, $materiels_selectionnes)) {
                        $materiels_selectionnes[] = $id_materiel;
                    }
                }
                break;

            case 'retirer_materiel':
                if (isset($_POST['id_retirer']) && !empty($_POST['id_retirer'])) {
                    $id_a_retirer = intval($_POST['id_retirer']);
                    $materiels_selectionnes = array_diff($materiels_selectionnes, [$id_a_retirer]);
                    $materiels_selectionnes = array_values($materiels_selectionnes);
                }
                break;
        }
    }

    // Traitement de la modification de l'expérience
    if (isset($_POST['modifier_experience'])) {
        $nom_experience = trim($_POST['nom_experience'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date_reservation = trim($_POST['date_reservation'] ?? '');
        $heure_debut = trim($_POST['heure_debut'] ?? '');
        $heure_fin = trim($_POST['heure_fin'] ?? '');
        $nom_salle = trim($_POST['nom_salle'] ?? '');

        // Validation
        if (empty($nom_experience)) {
            $erreur = "Le nom de l'expérience est obligatoire.";
        } elseif (strlen($nom_experience) < 3 || strlen($nom_experience) > 100) {
            $erreur = "Le nom de l'expérience doit contenir entre 3 et 100 caractères.";
        } elseif (strlen($description) < 10 || strlen($description) > 2000) {
            $erreur = "La description doit contenir entre 10 et 2000 caractères.";
        } elseif (empty($experimentateurs_selectionnes)) {
            $erreur = "L'expérience doit avoir au moins un expérimentateur.";
        } elseif (empty($date_reservation) || empty($heure_debut) || empty($heure_fin)) {
            $erreur = "La date et les horaires sont obligatoires.";
        } elseif (empty($materiels_selectionnes)) {
            $erreur = "L'expérience doit avoir au moins un matériel.";
        } else {
            // Vérifier la disponibilité des matériels (sauf pour l'expérience actuelle)
            $materiels_indisponibles = [];
            foreach ($materiels_selectionnes as $id_materiel) {
                $sql_check = "
                    SELECT e.Nom AS nom_experience
                    FROM experience e
                    INNER JOIN materiel_experience me ON me.ID_experience = e.ID_experience
                    WHERE me.ID_materiel = :id_materiel
                    AND e.ID_experience != :id_experience
                    AND DATE(e.Date_reservation) = :date
                    AND (
                        (e.Heure_debut < :heure_fin AND e.Heure_fin > :heure_debut)
                    )
                ";
                $stmt_check = $bdd->prepare($sql_check);
                $stmt_check->execute([
                    'id_materiel' => $id_materiel,
                    'id_experience' => $id_experience,
                    'date' => $date_reservation,
                    'heure_debut' => $heure_debut,
                    'heure_fin' => $heure_fin
                ]);
                
                if ($conflit = $stmt_check->fetch(PDO::FETCH_ASSOC)) {
                    $sql_nom = "SELECT Materiel FROM salle_materiel WHERE ID_materiel = :id";
                    $stmt_nom = $bdd->prepare($sql_nom);
                    $stmt_nom->execute(['id' => $id_materiel]);
                    $nom_materiel = $stmt_nom->fetchColumn();
                    $materiels_indisponibles[] = [
                        'nom' => $nom_materiel,
                        'conflit' => $conflit['nom_experience']
                    ];
                }
            }

            if (!empty($materiels_indisponibles)) {
                $erreur = "Conflit de matériel : ";
                foreach ($materiels_indisponibles as $mat) {
                    $erreur .= "Le matériel " . $mat['nom'] . " est déjà utilisé pour l'expérience « " . $mat['conflit'] . " ». ";
                }
            } else {
                try {
                    $bdd->beginTransaction();

                    // Mise à jour des informations de l'expérience
                    $sql = "UPDATE experience 
                            SET Nom = :nom, 
                                Description = :description,
                                Date_reservation = :date_reservation,
                                Heure_debut = :heure_debut,
                                Heure_fin = :heure_fin,
                                Date_de_modification = :date_modif
                            WHERE ID_experience = :id_experience";
                    $stmt = $bdd->prepare($sql);
                    $stmt->execute([
                        'nom' => $nom_experience,
                        'description' => $description,
                        'date_reservation' => $date_reservation,
                        'heure_debut' => $heure_debut,
                        'heure_fin' => $heure_fin,
                        'date_modif' => date('Y-m-d'),
                        'id_experience' => $id_experience
                    ]);

                    // Supprimer les anciennes associations expérimentateurs
                    $sql = "DELETE FROM experience_experimentateur WHERE ID_experience = :id_experience";
                    $stmt = $bdd->prepare($sql);
                    $stmt->execute(['id_experience' => $id_experience]);

                    // Ajouter les nouveaux expérimentateurs
                    $sql = "INSERT INTO experience_experimentateur (ID_experience, ID_compte) 
                            VALUES (:id_experience, :id_compte)";
                    $stmt = $bdd->prepare($sql);
                    foreach ($experimentateurs_selectionnes as $id_experimentateur) {
                        $stmt->execute([
                            'id_experience' => $id_experience,
                            'id_compte' => (int)$id_experimentateur
                        ]);
                    }

                    // Supprimer les anciennes associations matériels
                    $sql = "DELETE FROM materiel_experience WHERE ID_experience = :id_experience";
                    $stmt = $bdd->prepare($sql);
                    $stmt->execute(['id_experience' => $id_experience]);

                    // Ajouter les nouveaux matériels
                    $sql = "INSERT INTO materiel_experience (ID_experience, ID_materiel) 
                            VALUES (:id_experience, :id_materiel)";
                    $stmt = $bdd->prepare($sql);
                    foreach ($materiels_selectionnes as $id_materiel) {
                        $stmt->execute([
                            'id_experience' => $id_experience,
                            'id_materiel' => (int)$id_materiel
                        ]);
                    }

                    $bdd->commit();
                    $success = "L'expérience a été modifiée avec succès !";
                    
                    // Rediriger après 2 secondes
                    header("refresh:2;url=page_experience.php?id_projet=" . $id_projet . "&id_experience=" . $id_experience);
                    
                } catch (Exception $e) {
                    $bdd->rollBack();
                    $erreur = "Erreur lors de la modification : " . $e->getMessage();
                }
            }
        }
    }
}

$experience = $id_experience > 0 && !$erreur ? get_experience_pour_modification($bdd, $id_experience) : null;

// Si, pour une raison quelconque, les listes sélectionnées sont vides, recharger depuis la base
if (empty($experimentateurs_selectionnes) && $id_experience > 0) {
    $experimentateurs_selectionnes = get_experimentateurs_ids($bdd, $id_experience);
}
// Ajouter l'utilisateur courant comme fallback si aucun expérimentateur trouvé
if (empty($experimentateurs_selectionnes) && isset($_SESSION['ID_compte'])) {
    $experimentateurs_selectionnes = [ (int) $_SESSION['ID_compte'] ];
}

// Requêter les infos des expérimentateurs sélectionnés
$experimentateurs_info = [];
if (!empty($experimentateurs_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($experimentateurs_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE ID_compte IN ($placeholders)");
    $stmt->execute($experimentateurs_selectionnes);
    $experimentateurs_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$personnes_experimentateur = get_personnes_disponibles($bdd, $experimentateurs_selectionnes, false);

// Recharger matériels sélectionnés si vide
if (empty($materiels_selectionnes) && $id_experience > 0) {
    $materiels_selectionnes = get_materiels_experience($bdd, $id_experience);
}
$page_title = $experience ? "Modifier " . htmlspecialchars($experience['Nom']) : "Modification de l'expérience";

// === Calcul du planning (vue non-interactive) ===
$salles = recup_salles($bdd);
$nom_salle_selectionnee = $_GET['nom_salle'] ?? ($_POST['nom_salle'] ?? $nom_salle_selectionnee);
$date_ref = $_GET['date_ref'] ?? date('Y-m-d');
$dates_semaine = get_dates_semaine($date_ref);
$date_debut = $dates_semaine[0]['date'];
$date_fin = $dates_semaine[6]['date'];
$date_obj = new DateTime($date_debut);
$date_obj->modify('-7 days');
$semaine_precedente = $date_obj->format('Y-m-d');
$date_obj = new DateTime($date_debut);
$date_obj->modify('+7 days');
$semaine_suivante = $date_obj->format('Y-m-d');
$materiels = [];
$reservations = [];
if ($nom_salle_selectionnee !== '') {
    $materiels = recuperer_materiels_salle($bdd, $nom_salle_selectionnee);
    $reservations = recuperer_reservations_semaine($bdd, $nom_salle_selectionnee, $date_debut, $date_fin);
}
$nom_projet = $id_projet ? get_nom_projet($bdd, $id_projet) : '';
$heures = range(8, 19);
$planning = organiser_reservations_par_creneau($reservations, $dates_semaine, $heures);

// Récupérer les noms des matériels déjà sélectionnés pour cette expérience
$selected_materiels_names = [];
if (!empty($materiels_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($materiels_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT Materiel FROM salle_materiel WHERE ID_materiel IN ($placeholders)");
    $stmt->execute($materiels_selectionnes);
    $selected_materiels_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_modification_projet.css">
    <link rel="stylesheet" href="../css/page_creation_experience_2.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<div class="project-box">
    <h2>Modifier l'expérience</h2>

    <?php if ($success): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <p style="margin-top: 10px; font-size: 0.9rem;">Redirection en cours...</p>
        </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erreur) ?>
        </div>
        <div class="back-button-container">
            <a href="page_experience.php?id_projet=<?= $id_projet ?>&id_experience=<?= $id_experience ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour à l'expérience
            </a>
        </div>
    <?php elseif ($experience && !$success): ?>
        <form action="" method="post" id="form-experience">
            <input type="hidden" name="experimentateurs_ids" value="<?= implode(',', $experimentateurs_selectionnes) ?>">
            <input type="hidden" name="materiels_selectionnes" value="<?= implode(',', $materiels_selectionnes) ?>">
            <input type="hidden" name="nom_salle" value="<?= htmlspecialchars($nom_salle_selectionnee) ?>">
            <input type="hidden" id="id_retirer" name="id_retirer" value="">

            <label for="nom_experience">Nom de l'expérience :</label>
            <input type="text" 
                   id="nom_experience" 
                   name="nom_experience" 
                   value="<?= htmlspecialchars($experience['Nom']) ?>" 
                   required>

            <label for="description">Description :</label>
            <textarea id="description" 
                      name="description" 
                      required><?= htmlspecialchars($experience['Description']) ?></textarea>

            <!-- Date et horaires -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label for="date_reservation">Date de réservation :</label>
                    <input type="date" 
                           id="date_reservation" 
                           name="date_reservation" 
                           value="<?= htmlspecialchars($experience['Date_reservation']) ?>" 
                           required>
                </div>
                <div>
                    <label for="heure_debut">Heure de début :</label>
                    <input type="time" 
                           id="heure_debut" 
                           name="heure_debut" 
                           value="<?= htmlspecialchars(substr($experience['Heure_debut'], 0, 5)) ?>" 
                           required>
                </div>
                <div>
                    <label for="heure_fin">Heure de fin :</label>
                    <input type="time" 
                           id="heure_fin" 
                           name="heure_fin" 
                           value="<?= htmlspecialchars(substr($experience['Heure_fin'], 0, 5)) ?>" 
                           required>
                </div>
            </div>

            <!-- Vue planning (lecture seule) -->
            <div class="planning-readonly" style="margin-bottom:20px;">
                <h3>Planning semaine (Salle : <?= htmlspecialchars($nom_salle_selectionnee) ?>)</h3>
                <div class="navigation-semaine" style="margin-bottom:8px;">
                    <a href="?id_experience=<?= $id_experience ?>&id_projet=<?= $id_projet ?>&nom_salle=<?= urlencode($nom_salle_selectionnee) ?>&date_ref=<?= htmlspecialchars($semaine_precedente) ?>" class="btn-nav">← Semaine précédente</a>
                    <span class="semaine-info" style="margin:0 12px;">Semaine du <?= htmlspecialchars($dates_semaine[0]['date_formatee']) ?> au <?= htmlspecialchars($dates_semaine[6]['date_formatee']) ?></span>
                    <a href="?id_experience=<?= $id_experience ?>&id_projet=<?= $id_projet ?>&nom_salle=<?= urlencode($nom_salle_selectionnee) ?>&date_ref=<?= htmlspecialchars($semaine_suivante) ?>" class="btn-nav">Semaine suivante →</a>
                </div>

                <?php if (!empty($selected_materiels_names)): ?>
                    <div style="margin-bottom:8px;">
                        <strong>Matériels sélectionnés :</strong>
                        <?php foreach ($selected_materiels_names as $m): ?>
                            <span class="tag-materiel" style="margin-left:8px; display:inline-block; padding:4px 8px; border-radius:6px; background:#f093fb;"><?= htmlspecialchars($m) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($reservations) || !empty($materiels)): ?>
                    <?php $planning_for_view = $planning; ?>
                    <table id="planning-table" style="width:100%;border-collapse:collapse;margin-bottom:12px;">
                        <thead>
                            <tr>
                                <th class="col-heure">Heure</th>
                                <?php foreach ($dates_semaine as $jour): ?>
                                    <th class="col-jour">
                                        <div style="font-weight:700;"><?= htmlspecialchars($jour['jour']) ?></div>
                                        <div style="font-size:0.9em;opacity:0.85;"><?= htmlspecialchars($jour['date_formatee']) ?></div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($heures as $heure): ?>
                                <tr style="position:relative;">
                                    <th class="col-heure" style="padding:8px;"><?= sprintf('%02dH', $heure) ?></th>
                                    <?php foreach ($dates_semaine as $jour): ?>
                                        <td class="col-jour" style="border:1px solid #eee;vertical-align:top;padding:6px;position:relative;height:48px;">
                                            <?php
                                            $reservations_creneau = $planning_for_view[$jour['date']][$heure] ?? [];
                                            if (!empty($reservations_creneau)):
                                                foreach ($reservations_creneau as $i => $res):
                                                    $heure_debut_res = (int)date('G', strtotime($res['Heure_debut']));
                                                    $heure_fin_res = (int)date('G', strtotime($res['Heure_fin']));
                                                    $duree = $heure_fin_res - $heure_debut_res;
                                                    if ($heure_debut_res === $heure):
                                                        $cls = 'couleur-' . (($i % 4) + 1);
                                            ?>
                                                        <div class="reservation-continue <?= $cls ?>" style="height:calc(<?= $duree ?> * 100%);position:relative;padding:6px;border-radius:4px;">
                                                            <div style="font-weight:700;font-size:0.9em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($res['nom_experience'] ?: ($res['Nom_projet'] ?? '')) ?></div>
                                                            <div style="font-size:0.85em;opacity:0.85;"><?= htmlspecialchars($res['experimentateurs_ids'] ?? ($res['experimentateurs'] ?? '')) ?></div>
                                                        </div>
                                            <?php
                                                    endif;
                                                endforeach;
                                            else:
                                            ?>
                                                <div style="color:#999;text-align:center;">—</div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#666;">Aucune réservation sur cette semaine.</p>
                <?php endif; ?>
            </div>

            <!-- Expérimentateurs -->
            <div class="participants-section">
                <label>Expérimentateurs :</label>
                <p class="info-text">Tous les utilisateurs validés peuvent être expérimentateurs</p>
                <div class="selection-container">
                    <input type="text" 
                           name="nom_experimentateur" 
                           list="liste-experimentateur-disponibles" 
                           placeholder="Rechercher un expérimentateur..." 
                           autocomplete="off">
                    <button type="submit" name="action" value="ajouter_experimentateur" class="btn-ajouter">Ajouter</button>
                </div>
                <datalist id="liste-experimentateur-disponibles">
                    <?php foreach ($personnes_experimentateur as $personne): ?>
                        <?php
                            if ($personne['Etat'] == 3) {
                                $etat = 'ADMIN';
                            } elseif ($personne['Etat'] == 2) {
                                $etat = 'Chercheur';
                            } else {
                                $etat = 'Etudiant';
                            }
                        ?>
                        <option value="<?= htmlspecialchars($personne['Prenom'] . ' ' . $personne['Nom']); ?>">
                            <?= $etat ?>
                        </option>
                    <?php endforeach; ?>
                </datalist>

                <div class="liste-selectionnes">
                    <?php if (empty($experimentateurs_info)): ?>
                        <div class="liste-vide">Aucun expérimentateur ajouté</div>
                    <?php else: ?>
                        <?php foreach ($experimentateurs_info as $expe): ?>
                            <span class="tag-personne <?= $expe['Etat'] == 1 ? 'tag-etudiant' : ($expe['Etat']==2 ? 'tag-chercheur' : 'tag-admin') ?>">
                                <?= htmlspecialchars($expe['Prenom'] . ' ' . $expe['Nom']) ?>
                                <button type="submit" name="action" value="retirer_experimentateur" class="btn-croix"
                                        onclick="this.form.id_retirer.value=<?= $expe['ID_compte'] ?>; return true;">×</button>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Matériels -->
            <div class="participants-section">
                <label>Matériels (Salle: <?= htmlspecialchars($nom_salle_selectionnee) ?>) :</label>
                <div class="selection-container">
                    <input type="text"
                           name="Materiel"
                           list="liste-materiel"
                           placeholder="Rechercher un matériel"
                           autocomplete="off">
                    <button type="submit" name="action" value="ajouter_materiel" class="btn-ajouter">Ajouter</button>
                </div>
                <datalist id="liste-materiel">
                    <?php foreach ($materiels as $materiel): 
                        if (!in_array($materiel['ID_materiel'], $materiels_selectionnes)):
                    ?>
                        <option value="<?= htmlspecialchars($materiel['Materiel']) ?>"></option>
                    <?php 
                        endif;
                    endforeach; ?>
                </datalist>

                <div class="liste-selectionnes">
                    <?php if (empty($materiels_selectionnes)): ?>
                        <div class="liste-vide">Aucun matériel ajouté</div>
                    <?php else: ?>
                        <?php 
                        foreach ($materiels as $materiel): 
                            if (in_array($materiel['ID_materiel'], $materiels_selectionnes)):
                        ?>
                            <span class="tag-materiel">
                                <?= htmlspecialchars($materiel['Materiel']) ?>
                                <button type="submit" 
                                        name="action" 
                                        value="retirer_materiel" 
                                        class="btn-croix"
                                        onclick="this.form.id_retirer.value='<?= $materiel['ID_materiel'] ?>'; return true;">
                                    ×
                                </button>
                            </span>
                        <?php 
                            endif;
                        endforeach;
                        ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <a href="page_experience.php?id_projet=<?= $id_projet ?>&id_experience=<?= $id_experience ?>" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" name="modifier_experience" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>