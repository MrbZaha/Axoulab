<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_creation_experience_2.php';

$message = "";
$nom_experience = "";
$description = "";
$experimentateurs_ids = [];
$materiels_selectionnes = [];
$id_projet = null;
$creneau_selectionne = null;

// Récupération de l'ID du projet depuis POST OU GET
if (isset($_POST['id_projet']) && !empty($_POST['id_projet'])) {
    $id_projet = intval($_POST['id_projet']);
} elseif (isset($_GET['id_projet']) && !empty($_GET['id_projet'])) {
    $id_projet = intval($_GET['id_projet']);
}

// Récupération des données de la page 1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si on vient de la page 1 ou qu'on envoie de nouvelles données
    if (isset($_POST['nom_experience'])) {
        $nom_experience = $_POST['nom_experience'];
        $_SESSION['creation_experience']['nom_experience'] = $nom_experience;
    }
    
    if (isset($_POST['description'])) {
        $description = $_POST['description'];
        $_SESSION['creation_experience']['description'] = $description;
    }
    
    if (isset($_POST['experimentateurs_ids'])) {
        $experimentateurs_ids = array_filter(array_map('intval', explode(',', $_POST['experimentateurs_ids'])));
        $_SESSION['creation_experience']['experimentateurs_ids'] = $experimentateurs_ids;
    }
    
    if (isset($_POST['materiels_selectionnes'])) {
        $materiels_selectionnes = array_filter(array_map('intval', explode(',', $_POST['materiels_selectionnes'])));
        $_SESSION['creation_experience']['materiels_selectionnes'] = $materiels_selectionnes;
    } elseif (isset($_SESSION['creation_experience']['materiels_selectionnes'])) {
        $materiels_selectionnes = $_SESSION['creation_experience']['materiels_selectionnes'];
    }
    
    if (isset($_POST['date_reservation']) && isset($_POST['heure_debut']) && isset($_POST['heure_fin'])) {
        $creneau_selectionne = [
            'date' => $_POST['date_reservation'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin']
        ];
        $_SESSION['creation_experience']['creneau_selectionne'] = $creneau_selectionne;
    } elseif (isset($_SESSION['creation_experience']['creneau_selectionne'])) {
        $creneau_selectionne = $_SESSION['creation_experience']['creneau_selectionne'];
    }
    
    // Gérer les actions d'ajout/retrait de matériel et sélection créneau
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_materiel':
                if (!empty($_POST['Materiel']) && isset($_POST['nom_salle'])) {
                    $nom_materiel = trim($_POST['Materiel']);
                    $nom_salle = $_POST['nom_salle'];
                    $id_materiel = recuperer_id_materiel_par_nom($bdd, $nom_materiel, $nom_salle);
                    if ($id_materiel && !in_array($id_materiel, $materiels_selectionnes)) {
                        $materiels_selectionnes[] = $id_materiel;
                        $_SESSION['creation_experience']['materiels_selectionnes'] = $materiels_selectionnes;
                    }
                }
                break;
                
            case 'retirer_materiel':
                if (isset($_POST['id_retirer']) && !empty($_POST['id_retirer'])) {
                    $id_a_retirer = intval($_POST['id_retirer']);
                    $materiels_selectionnes = array_diff($materiels_selectionnes, [$id_a_retirer]);
                    $materiels_selectionnes = array_values($materiels_selectionnes);
                    $_SESSION['creation_experience']['materiels_selectionnes'] = $materiels_selectionnes;
                }
                break;
                
            case 'selectionner_creneau':
                if (isset($_POST['creneau_date']) && isset($_POST['creneau_heure'])) {
                    $date = $_POST['creneau_date'];
                    $heure = intval($_POST['creneau_heure']);
                    $creneau_selectionne = [
                        'date' => $date,
                        'heure_debut' => sprintf('%02d:00', $heure),
                        'heure_fin' => sprintf('%02d:00', $heure + 1)
                    ];
                    $_SESSION['creation_experience']['creneau_selectionne'] = $creneau_selectionne;
                }
                break;
        }
    }

    // ======================= Création de l'expérience =======================
    if (isset($_POST['creer_experience'])) {
        if (!$id_projet || $id_projet <= 0) {
            $message = "<p style='color:red;'>Erreur : ID du projet invalide.</p>";
        } elseif (empty($_POST['date_reservation']) || empty($_POST['heure_debut']) || empty($_POST['heure_fin'])) {
            $message = "<p style='color:red;'>Erreur : Veuillez sélectionner un créneau horaire.</p>";
        } else {
            $date_reservation = $_POST['date_reservation'];
            $heure_debut = $_POST['heure_debut'];
            $heure_fin = $_POST['heure_fin'];
            $nom_salle = $_POST['nom_salle'];

            // Vérifier la disponibilité de tous les matériels
            $materiels_indisponibles = [];
            foreach ($materiels_selectionnes as $id_materiel) {
                $verif = verifier_disponibilite_materiel($bdd, $id_materiel, $date_reservation, $heure_debut, $heure_fin);
                if (!$verif['disponible']) {
                    $sql_nom = "SELECT Materiel FROM salle_materiel WHERE ID_materiel = :id";
                    $stmt_nom = $bdd->prepare($sql_nom);
                    $stmt_nom->execute(['id' => $id_materiel]);
                    $nom_materiel = $stmt_nom->fetchColumn();
                    $materiels_indisponibles[] = [
                        'nom' => $nom_materiel,
                        'conflit' => $verif['conflit']
                    ];
                }
            }

            if (!empty($materiels_indisponibles)) {
                $message = "<div style='background:#fdeaea;border:1px solid #f5bcbc;color:#b30000;border-radius:8px;padding:15px;margin:20px 0;'>";
                $message .= "<strong>Impossible de créer l'expérience :</strong><br><br>";
                foreach ($materiels_indisponibles as $mat) {
                    $message .= "Le matériel <strong>" . htmlspecialchars($mat['nom']) . "</strong> est déjà utilisé pour l'expérience « " . htmlspecialchars($mat['conflit']) . " » sur ce créneau.<br>";
                }
                $message .= "<br>Veuillez retirer ce(s) matériel(s) ou choisir un autre créneau.";
                $message .= "</div>";
            } else {
                try {
                    $date_creation = (new DateTime())->format('Y-m-d');
                    
                    // Créer l'expérience
                    $id_experience = creer_experience($bdd, $validation, $nom_experience, $description, $date_reservation, $date_creation, $heure_debut, $heure_fin, $nom_salle);

                    if ($id_experience) {
                        // Associer au projet
                        associer_experience_projet($bdd, $id_projet, $id_experience);

                        // Ajouter les expérimentateurs
                        if (!empty($experimentateurs_ids)) {
                            ajouter_experimentateurs($bdd, $id_experience, $experimentateurs_ids);
                        }

                        // Associer les matériels
                        if (!empty($materiels_selectionnes)) {
                            associer_materiel_experience($bdd, $id_experience, $materiels_selectionnes);
                        }

                        // ======================= Notifications =======================
                        // Récupérer les gestionnaires du projet
                        $stmt_gest = $bdd->prepare("
                            SELECT ID_compte FROM projet_collaborateur_gestionnaire 
                            WHERE ID_projet = ? AND Statut = 1
                        ");
                        $stmt_gest->execute([$id_projet]);
                        $gestionnaires = $stmt_gest->fetchAll(PDO::FETCH_COLUMN);

                        // Retirer le créateur si présent
                        $destinataires = array_values(array_diff($gestionnaires, [$_SESSION['ID_compte']]));

                        // Envoyer notification type 1 aux gestionnaires
                        if (!empty($destinataires)) {
                            $donnees = [
                                'ID_experience' => $id_experience,
                                'Nom_experience' => $nom_experience
                            ];
                            envoyerNotification($bdd, 1, $_SESSION['ID_compte'], $donnees, $destinataires);
                        }

                        // Nettoyer la session
                        unset($_SESSION['creation_experience']);

                        // Redirection
                        header("Location: page_experience.php?id_projet=" . $id_projet . "&id_experience=" . $id_experience);
                        exit();
                    } else {
                        $message = "<p style='color:red;'>Erreur lors de la création de l'expérience.</p>";
                    }
                } catch (Exception $e) {
                    $message = "<p style='color:red;'>Erreur lors de la création de l'expérience : " . htmlspecialchars($e->getMessage()) . "</p>";
                    error_log("Erreur création expérience: " . $e->getMessage());
                }
            }
        }
    }
} else {
    // Si on arrive via GET (navigation), récupérer depuis la session
    if (isset($_SESSION['creation_experience'])) {
        $nom_experience = $_SESSION['creation_experience']['nom_experience'] ?? '';
        $description = $_SESSION['creation_experience']['description'] ?? '';
        $experimentateurs_ids = $_SESSION['creation_experience']['experimentateurs_ids'] ?? [];
        $materiels_selectionnes = $_SESSION['creation_experience']['materiels_selectionnes'] ?? [];
        $creneau_selectionne = $_SESSION['creation_experience']['creneau_selectionne'] ?? null;
    }
}

// Vérifier si on a un ID_projet valide
if (!$id_projet || $id_projet <= 0) {
    $message = "<p style='color:red;'>Erreur : Aucun projet sélectionné. Veuillez retourner à l'étape 1.</p>";
}

// ======================= PLANNING =======================
$salles = recup_salles($bdd);
$nom_salle_selectionnee = isset($_GET['nom_salle']) ? $_GET['nom_salle'] : ($salles[0]['Nom_salle'] ?? '');
$date_ref = isset($_GET['date_ref']) ? $_GET['date_ref'] : date('Y-m-d');

$dates_semaine = get_dates_semaine($date_ref);
$date_debut = $dates_semaine[0]['date'];
$date_fin = $dates_semaine[6]['date'];

// Calculer les dates pour navigation
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
$nom_projet = get_nom_projet($bdd, $id_projet);
$heures = range(8, 19);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Réservation de salle - Étape 2</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/page_creation_experience_2.css">
<link rel="stylesheet" href="../css/Bandeau_haut.css">
<link rel="stylesheet" href="../css/Bandeau_bas.css">
<style>
.cell-content.selected {
    background-color: #e3f2fd;
    border: 2px solid #2196F3;
}
</style>
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"]); ?>

<div class="container">
    <h1>Réservation de salle - Étape 2/2</h1>

    <?php if (!empty($message)) echo $message; ?>

    <?php if ($id_projet && $id_projet > 0): ?>
        <!-- Récapitulatif de l'expérience -->
        <div class="info-experience">
            <h3>Récapitulatif de l'expérience</h3>
            <p><strong>Expérience :</strong> <?= htmlspecialchars($nom_experience) ?></p>
            <p><strong>Description :</strong> <?= htmlspecialchars($description) ?></p>
            <p><strong>Projet :</strong> <?= htmlspecialchars($nom_projet) ?></p>
        </div>

        <!-- Sélection de salle -->
        <!-- Sélection de salle -->
<form method="get" action="" class="form-row">
    <input type="hidden" name="id_projet" value="<?= $id_projet ?>">
    <label for="nom_salle" style="font-weight:700;margin-right:6px">Choisir une salle :</label>
    <select name="nom_salle" id="nom_salle" onchange="this.form.submit()" style="padding:8px 10px;border-radius:6px;border:1px solid #d0d0d0;">
        <?php foreach ($salles as $s): ?>
            <option value="<?= htmlspecialchars($s['Nom_salle']) ?>" <?= ($s['Nom_salle'] === $nom_salle_selectionnee) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['Nom_salle']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($_GET['date_ref'])): ?>
        <input type="hidden" name="date_ref" value="<?= htmlspecialchars($_GET['date_ref']) ?>">
    <?php endif; ?>
</form>

        <?php if ($nom_salle_selectionnee !== ''): ?>
            <h2>Salle : <?= htmlspecialchars($nom_salle_selectionnee) ?></h2>
    <div class="materiels-salle">
        <h3>Matériels disponibles dans cette salle :</h3>
        <div class="liste-materiels-salle">
            <?php foreach ($materiels as $mat): ?>
                <span class="tag-materiel-info"><?= htmlspecialchars($mat['Materiel']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
            <div class="navigation-semaine">
    <a href="?id_projet=<?= $id_projet ?>&nom_salle=<?= urlencode($nom_salle_selectionnee) ?>&date_ref=<?= htmlspecialchars($semaine_precedente) ?>" class="btn-nav">
        ← Semaine précédente
    </a>
    
    <span class="semaine-info">
        Semaine du <?= htmlspecialchars($dates_semaine[0]['date_formatee']) ?> au <?= htmlspecialchars($dates_semaine[6]['date_formatee']) ?>
    </span>
    
    <a href="?id_projet=<?= $id_projet ?>&nom_salle=<?= urlencode($nom_salle_selectionnee) ?>&date_ref=<?= htmlspecialchars($semaine_suivante) ?>" class="btn-nav">
        Semaine suivante →
    </a>
</div>

<?php if ($date_ref !== date('Y-m-d')): ?>
    <div style="text-align:center;margin:10px 0;">
        <a href="?id_projet=<?= $id_projet ?>&nom_salle=<?= urlencode($nom_salle_selectionnee) ?>" class="btn-nav" style="display:inline-block;">
            Revenir à la semaine actuelle
        </a>
    </div>
<?php endif; ?>

            <p style="text-align:center;color:#666;margin:15px 0;">
                Cliquez sur un créneau disponible pour le sélectionner
            </p>

<?php
// Organiser les réservations
$planning = organiser_reservations_par_creneau($reservations, $dates_semaine, $heures);
?>

<table aria-describedby="planning" id="planning-table">
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
            <tr>
                <th class="col-heure"><?= sprintf('%02dH', $heure) ?></th>

                <?php foreach ($dates_semaine as $jour): ?>
                    <td class="col-jour">
                        <?php
                        $reservations_creneau = $planning[$jour['date']][$heure];
                        $est_selectionne = ($creneau_selectionne && 
                                           $creneau_selectionne['date'] === $jour['date'] && 
                                           (int)substr($creneau_selectionne['heure_debut'], 0, 2) === $heure);
                        
                        if (!empty($reservations_creneau)):
                            foreach ($reservations_creneau as $i => $res):
                                $heure_debut_res = (int)date('G', strtotime($res['Heure_debut']));
                                $heure_fin_res = (int)date('G', strtotime($res['Heure_fin']));
                                $duree = $heure_fin_res - $heure_debut_res;
                                
                                // N'afficher le bloc que s'il s'agit de la première heure de la réservation
                                if ($heure_debut_res === $heure):
                                    $cls = 'couleur-' . (($i % 4) + 1);
                                    ?>
                                    <div class="reservation-continue <?= $cls ?>" 
                                         style="height: calc(<?= $duree ?> * 100%); position: absolute; top: 0; left: 0; right: 0; z-index: <?= 2 + $i ?>; border-radius: 4px; padding: 8px; cursor: pointer; transition: transform 0.2s;">
                                        <div class="reservation-content" style="font-size: 0.85em;">
                                            <div class="experimentateur" style="font-weight: 700; margin-bottom: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($res['experimentateurs_selectionnes'] ?: 'Non assigné') ?>
                                            </div>
                                            <div class="projet" style="font-size: 0.9em; opacity: 0.9; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($res['Nom_projet'] ?: 'Sans projet') ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Overlay avec détails -->
                                        <div class="reservation-overlay">
                                            <div class="overlay-content" style="font-size: 0.9em; color: #333;">
                                                <div class="overlay-row" style="margin-bottom: 8px; line-height: 1.4;">
                                                    <strong style="color: #000; display: inline-block; min-width: 130px;">Expérience :</strong> 
                                                    <?= htmlspecialchars($res['nom_experience']) ?>
                                                </div>
                                                <div class="overlay-row" style="margin-bottom: 8px; line-height: 1.4;">
                                                    <strong style="color: #000; display: inline-block; min-width: 130px;">Projet :</strong> 
                                                    <?= htmlspecialchars($res['Nom_projet'] ?: 'Non défini') ?>
                                                </div>
                                                <div class="overlay-row" style="margin-bottom: 8px; line-height: 1.4;">
                                                    <strong style="color: #000; display: inline-block; min-width: 130px;">Expérimentateur(s) :</strong> 
                                                    <?= htmlspecialchars($res['experimentateurs_selectionnes'] ?: 'Non assigné') ?>
                                                </div>
                                                <div class="overlay-row" style="margin-bottom: 8px; line-height: 1.4;">
                                                    <strong style="color: #000; display: inline-block; min-width: 130px;">Horaire :</strong> 
                                                    <?= htmlspecialchars(substr($res['Heure_debut'],0,5)) ?> — <?= htmlspecialchars(substr($res['Heure_fin'],0,5)) ?>
                                                </div>
                                                <div class="overlay-row" style="margin-bottom: 0; line-height: 1.4;">
                                                    <strong style="color: #000; display: inline-block; min-width: 130px;">Matériel :</strong> 
                                                    <?= htmlspecialchars($res['materiels_utilises']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        <?php 
                                endif;
                            endforeach;
                        else: 
                        ?>
                            <form method="post" style="margin:0;height:100%;">
                                <input type="hidden" name="id_projet" value="<?= $id_projet ?>">
                                <input type="hidden" name="nom_experience" value="<?= htmlspecialchars($nom_experience) ?>">
                                <input type="hidden" name="description" value="<?= htmlspecialchars($description) ?>">
                                <input type="hidden" name="experimentateurs_ids" value="<?= implode(',', $experimentateurs_ids) ?>">
                                <input type="hidden" name="materiels_selectionnes" value="<?= implode(',', $materiels_selectionnes) ?>">
                                <input type="hidden" name="nom_salle" value="<?= htmlspecialchars($nom_salle_selectionnee) ?>">
                                <input type="hidden" name="creneau_date" value="<?= $jour['date'] ?>">
                                <input type="hidden" name="creneau_heure" value="<?= $heure ?>">
                                <input type="hidden" name="action" value="selectionner_creneau">
                                <?php if ($creneau_selectionne): ?>
                                    <input type="hidden" name="date_reservation" value="<?= htmlspecialchars($creneau_selectionne['date']) ?>">
                                    <input type="hidden" name="heure_debut" value="<?= htmlspecialchars($creneau_selectionne['heure_debut']) ?>">
                                    <input type="hidden" name="heure_fin" value="<?= htmlspecialchars($creneau_selectionne['heure_fin']) ?>">
                                <?php endif; ?>
                                
                                <button type="submit" class="cell-content selectable <?= $est_selectionne ? 'selected' : '' ?>" style="width:100%;height:100%;border:none;background:none;cursor:pointer;padding:0;">
                                    <div class="empty-slot">—</div>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            <!-- Formulaire de réservation -->
            <div class="reservation-form">
                <h3>Créer la réservation</h3>
                <form method="post" id="form-reservation">
                    <input type="hidden" name="id_projet" value="<?= $id_projet ?>">
                    <input type="hidden" name="nom_experience" value="<?= htmlspecialchars($nom_experience) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($description) ?>">
                    <input type="hidden" name="experimentateurs_ids" value="<?= implode(',', $experimentateurs_ids) ?>">
                    <input type="hidden" name="nom_salle" value="<?= htmlspecialchars($nom_salle_selectionnee) ?>">
                    <input type="hidden" name="materiels_selectionnes" id="materiels_selectionnes" value="<?= implode(',', $materiels_selectionnes) ?>">
                    <input type="hidden" name="id_retirer" id="id_retirer" value="">

                    <div class="form-group">
                        <label for="date_reservation">Date :</label>
                        <input type="date" 
                               id="date_reservation" 
                               name="date_reservation" 
                               value="<?= $creneau_selectionne ? htmlspecialchars($creneau_selectionne['date']) : '' ?>"
                               required >
                    </div>

                    <div class="form-group">
                        <label for="heure_debut">Heure de début :</label>
                        <input type="time" 
                               id="heure_debut" 
                               name="heure_debut" 
                               value="<?= $creneau_selectionne ? htmlspecialchars($creneau_selectionne['heure_debut']) : '' ?>"
                               required >
                    </div>

                    <div class="form-group">
                        <label for="heure_fin">Heure de fin :</label>
                        <input type="time" 
                               id="heure_fin" 
                               name="heure_fin" 
                               value="<?= $creneau_selectionne ? htmlspecialchars($creneau_selectionne['heure_fin']) : '' ?>"
                               required >
                    </div>

                    <!-- Section Matériels -->
                    <div class="form-group">
                        <label>Matériels :</label>                
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
                                // N'afficher que les matériels NON sélectionnés
                                if (!in_array($materiel['ID_materiel'], $materiels_selectionnes)):
                            ?>
                                <option value="<?= htmlspecialchars($materiel['Materiel']) ?>">
                                </option>
                            <?php 
                                endif;
                            endforeach; ?>
                        </datalist>

                        <div class="liste-selectionnes">
                            <?php if (empty($materiels_selectionnes)): ?>
                                <div class="liste-vide">Aucun matériel ajouté</div>
                            <?php else: ?>
                                <?php 
                                // Afficher UNIQUEMENT les matériels sélectionnés
                                foreach ($materiels as $materiel): 
                                    if (in_array($materiel['ID_materiel'], $materiels_selectionnes)):
                                ?>
                                    <span class="tag-materiel">
                                        <?= htmlspecialchars($materiel['Materiel']) ?>
                                        <button type="submit" 
                                                name="action" 
                                                value="retirer_materiel" 
                                                class="btn-croix"
                                                onclick="document.getElementById('id_retirer').value='<?= $materiel['ID_materiel'] ?>'; return true;">
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

                    <button type="submit" name="creer_experience" class="btn-creer">
                        Créer l'expérience et réserver
                    </button>
                </form>
            </div>

        <?php else: ?>
            <p style="text-align:center;margin-top:20px;">Aucune salle trouvée.</p>
        <?php endif; ?>
    <?php else: ?>
        <p style="text-align:center;margin-top:20px;">
            <a href="page_creation_experience_1.php" class="btn-nav">← Retour à l'étape 1</a>
        </p>
    <?php endif; ?>
</div>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>