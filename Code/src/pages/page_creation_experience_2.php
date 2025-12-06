<?php
session_start();
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
verification_connexion($bdd);

// ======================= FONCTIONS PLANNING =======================
function recup_salles($bdd) {
    $sql = "SELECT DISTINCT Nom_salle FROM salle_materiel ORDER BY Nom_salle";
    $stmt = $bdd->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function recuperer_materiels_salle($bdd, $nom_salle) {
    $sql = "
        SELECT ID_materiel, Materiel
        FROM salle_materiel
        WHERE Nom_salle = :nom_salle
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['nom_salle' => $nom_salle]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function recuperer_id_materiel_par_nom($bdd, $nom_materiel, $nom_salle) {
    $sql = "
        SELECT ID_materiel
        FROM salle_materiel
        WHERE Materiel = :materiel AND Nom_salle = :nom_salle
        LIMIT 1
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['materiel' => $nom_materiel, 'nom_salle' => $nom_salle]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['ID_materiel'] : null;
}

function recuperer_reservations_semaine($bdd, $nom_salle, $date_debut, $date_fin) {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Nom AS nom_experience,
            DATE(e.Date_reservation) AS Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Description,
            e.Statut_experience,
            p.Nom_projet,
            GROUP_CONCAT(DISTINCT sm.Materiel SEPARATOR ', ') AS materiels_utilises,
            GROUP_CONCAT(DISTINCT CONCAT(c.Prenom, ' ', c.Nom) SEPARATOR ', ') AS experimentateurs
        FROM experience e
        INNER JOIN materiel_experience em ON em.ID_experience = e.ID_experience
        INNER JOIN salle_materiel sm ON sm.ID_materiel = em.ID_materiel
        LEFT JOIN projet_experience pe ON pe.ID_experience = e.ID_experience
        LEFT JOIN projet p ON p.ID_projet = pe.ID_projet
        LEFT JOIN experience_experimentateur ee ON ee.ID_experience = e.ID_experience
        LEFT JOIN compte c ON c.ID_compte = ee.ID_compte
        WHERE sm.Nom_salle = :nom_salle
        AND DATE(e.Date_reservation) BETWEEN :date_debut AND :date_fin
        GROUP BY e.ID_experience, e.Nom, e.Date_reservation, e.Heure_debut, e.Heure_fin, e.Description, e.Statut_experience, p.Nom_projet
        ORDER BY e.Date_reservation, e.Heure_debut
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'nom_salle' => $nom_salle,
        'date_debut' => $date_debut,
        'date_fin' => $date_fin
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_dates_semaine($date_reference = null) {
    if ($date_reference === null) { $date_reference = date('Y-m-d'); }
    $date = new DateTime($date_reference);
    $jour_semaine = (int)$date->format('N');
    $date->modify('-' . ($jour_semaine - 1) . ' days');
    $jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
    $dates_semaine = [];
    for ($i=0;$i<7;$i++){
        $dates_semaine[] = [
            'date' => $date->format('Y-m-d'),
            'jour' => $jours[$i],
            'numero_jour' => $i+1,
            'date_formatee' => $date->format('d/m/Y')
        ];
        $date->modify('+1 day');
    }
    return $dates_semaine;
}

function creneau_est_occupe($reservations, $jour, $heure) {
    $creneaux = [];
    foreach ($reservations as $reservation) {
        $res_date = $reservation['Date_reservation'];
        if ($res_date === $jour['date']) {
            $hd = (int)date('G', strtotime($reservation['Heure_debut']));
            $hf = (int)date('G', strtotime($reservation['Heure_fin']));
            if ($heure >= $hd && $heure < $hf) {
                $creneaux[] = $reservation;
            }
        }
    }
    return $creneaux;
}

function organiser_reservations_par_creneau($reservations, $dates_semaine, $heures) {
    $planning = [];
    
    // Initialiser le planning
    foreach ($dates_semaine as $jour) {
        $planning[$jour['date']] = [];
        foreach ($heures as $heure) {
            $planning[$jour['date']][$heure] = [];
        }
    }
    
    // Remplir le planning
    foreach ($reservations as $res) {
        $date = $res['Date_reservation'];
        $heure_debut = (int)date('G', strtotime($res['Heure_debut']));
        $heure_fin = (int)date('G', strtotime($res['Heure_fin']));
        
        // Ajouter la réservation à toutes les heures concernées
        for ($h = $heure_debut; $h < $heure_fin; $h++) {
            if (isset($planning[$date][$h])) {
                $planning[$date][$h][] = $res;
            }
        }
    }
    
    return $planning;
}

function est_debut_reservation($reservations, $heure) {
    foreach ($reservations as $res) {
        $heure_debut = (int)date('G', strtotime($res['Heure_debut']));
        if ($heure_debut === $heure) {
            return true;
        }
    }
    return false;
}

// ======================= FONCTIONS CRÉATION EXPÉRIENCE =======================
function creer_experience($bdd, $nom_experience, $validation, $description, $date_reservation, $heure_debut, $heure_fin, $statut_experience = 'En attente') {
    $sql = $bdd->prepare("
        INSERT INTO experience (Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Statut_experience)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if ($sql->execute([$nom_experience, $validation, $description, $date_reservation, $heure_debut, $heure_fin, $statut_experience])) {
        return $bdd->lastInsertId();
    }
    return false;
}


function associer_experience_projet($bdd, $id_projet, $id_experience) {
    $sql = $bdd->prepare("
        INSERT INTO projet_experience (ID_projet, ID_experience)
        VALUES (?, ?)
    ");
    return $sql->execute([$id_projet, $id_experience]);
}

function ajouter_experimentateurs($bdd, $id_experience, $experimentateurs) {
    $sql = $bdd->prepare("
        INSERT INTO experience_experimentateur (ID_experience, ID_compte)
        VALUES (?, ?)
    ");

    foreach ($experimentateurs as $id_compte) {
        $sql->execute([$id_experience, $id_compte]);
    }
}

function associer_materiel_experience($bdd, $id_experience, $materiels_ids) {
    $sql_insert = $bdd->prepare("
        INSERT INTO materiel_experience (ID_experience, ID_materiel)
        VALUES (?, ?)
    ");
    
    foreach ($materiels_ids as $id_materiel) {
        $sql_insert->execute([$id_experience, $id_materiel]);
    }
}

function get_nom_projet($bdd, $id_projet){
    $sql = "
        SELECT 
            p.Nom_projet
        FROM projet p
        WHERE p.ID_projet = :id_projet
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'id_projet' => $id_projet,
    ]);
    $nom_projet = $stmt->fetchColumn();
    return $nom_projet;
}

// ======================= TRAITEMENT DES DONNÉES =======================
$message = "";
$nom_experience = "";
$description = "";
$experimentateurs_ids = [];
$materiels_selectionnes = [];
$id_projet = null;
$creneau_selectionne = null;

// Récupération des données de la page 1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer id_projet
    if (isset($_POST['id_projet']) && !empty($_POST['id_projet'])) {
        $id_projet = intval($_POST['id_projet']);
    }
    
    if (isset($_POST['nom_experience'])) {
        $nom_experience = $_POST['nom_experience'];
    }
    
    if (isset($_POST['description'])) {
        $description = $_POST['description'];
    }
    
    if (isset($_POST['experimentateurs_ids'])) {
        $experimentateurs_ids = array_filter(array_map('intval', explode(',', $_POST['experimentateurs_ids'])));
    }
    
    // Récupérer la liste des matériels sélectionnés
    if (isset($_POST['materiels_selectionnes'])) {
        $materiels_selectionnes = array_filter(array_map('intval', explode(',', $_POST['materiels_selectionnes'])));
    }
    
    // Récupérer le créneau sélectionné
    if (isset($_POST['date_reservation']) && isset($_POST['heure_debut']) && isset($_POST['heure_fin'])) {
        $creneau_selectionne = [
            'date' => $_POST['date_reservation'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin']
        ];
    }
    
    // Gérer les actions d'ajout/retrait de matériel
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_materiel':
                if (!empty($_POST['Materiel']) && isset($_POST['nom_salle'])) {
                    $nom_materiel = trim($_POST['Materiel']);
                    $nom_salle = $_POST['nom_salle'];
                    
                    // Récupérer l'ID du matériel par son nom
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
                    $materiels_selectionnes = array_values($materiels_selectionnes); // Réindexer le tableau
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
                }
                break;
        }
    }
    
    // Traitement de la création finale
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

        // Déterminer si l'utilisateur est gestionnaire
        $stmt = $bdd->prepare("SELECT 1 FROM projet_collaborateur_gestionnaire WHERE ID_projet = ? AND ID_compte = ? AND Statut = 1");
        $stmt->execute([$id_projet, $_SESSION['ID_compte']]);
        $is_gestionnaire = $stmt->fetch() ? true : false;

        $validation = $is_gestionnaire ? 1 : 0;

        // Créer l'expérience
        $id_experience = creer_experience($bdd, $nom_experience, $validation, $description, $date_reservation, $heure_debut, $heure_fin);
        if ($id_experience) {
            // Associer au projet
            associer_experience_projet($bdd, $id_projet, $id_experience);

            // Ajouter les expérimentateurs
            if (!empty($experimentateurs_ids)) {
                ajouter_experimentateurs($bdd, $id_experience, $experimentateurs_ids);
            }

            // Associer les matériels sélectionnés
            if (!empty($materiels_selectionnes)) {
                associer_materiel_experience($bdd, $id_experience, $materiels_selectionnes);
            }

            // Envoyer la notification uniquement si l'utilisateur n'est pas gestionnaire
            if (!$is_gestionnaire) {
                $idEnvoyeur = $_SESSION['ID_compte'];

                // Récupérer les gestionnaires du projet
                $stmt = $bdd->prepare("SELECT ID_compte FROM projet_collaborateur_gestionnaire WHERE ID_projet = ? AND Statut = 1");
                $stmt->execute([$id_projet]);
                $gestionnaires = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Retirer l’envoyeur s’il est gestionnaire
                $destinataires = array_diff($gestionnaires, [$idEnvoyeur]);

                if (!empty($destinataires)) {
                    envoyerNotification($bdd, 1, $idEnvoyeur, ['ID_experience' => $id_experience], $destinataires);
                }
            }

            // Redirection automatique
            header("Location: page_experience.php?id_projet=$id_projet&id_experience=$id_experience");
            exit();
        } else {
            $message = "<p style='color:red;'>Erreur lors de la création de l'expérience.</p>";
        }
    }
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
        <form method="get" action="" class="form-row">
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
                <a href="?nom_salle=<?= urlencode($nom_salle_selectionnee) ?>&date_ref=<?= htmlspecialchars($semaine_precedente) ?>" class="btn-nav">
                    ← Semaine précédente
                </a>
                
                <span class="semaine-info">
                    Semaine du <?= htmlspecialchars($dates_semaine[0]['date_formatee']) ?> au <?= htmlspecialchars($dates_semaine[6]['date_formatee']) ?>
                </span>
                
                <a href="?nom_salle=<?= urlencode($nom_salle_selectionnee) ?>&date_ref=<?= htmlspecialchars($semaine_suivante) ?>" class="btn-nav">
                    Semaine suivante →
                </a>
            </div>

            <?php if ($date_ref !== date('Y-m-d')): ?>
                <div style="text-align:center;margin:10px 0;">
                    <a href="?nom_salle=<?= urlencode($nom_salle_selectionnee) ?>" class="btn-nav" style="display:inline-block;">
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

<style>
/* Overlay visible au hover uniquement avec CSS */
.reservation-continue {
    position: relative;
}

.reservation-overlay {
    display: none;
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border: 2px solid #333;
    border-radius: 8px;
    padding: 12px;
    min-width: 280px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 1000;
    margin-top: 5px;
    pointer-events: none;
}

.reservation-continue:hover .reservation-overlay {
    display: block;
}

.reservation-continue:hover {
    transform: scale(1.02);
    z-index: 10;
}
</style>

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
                                                <?= htmlspecialchars($res['experimentateurs'] ?: 'Non assigné') ?>
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
                                                    <?= htmlspecialchars($res['experimentateurs'] ?: 'Non assigné') ?>
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
                               required 
                               readonly>
                    </div>

                    <div class="form-group">
                        <label for="heure_debut">Heure de début :</label>
                        <input type="time" 
                               id="heure_debut" 
                               name="heure_debut" 
                               value="<?= $creneau_selectionne ? htmlspecialchars($creneau_selectionne['heure_debut']) : '' ?>"
                               required 
                               readonly>
                    </div>

                    <div class="form-group">
                        <label for="heure_fin">Heure de fin :</label>
                        <input type="time" 
                               id="heure_fin" 
                               name="heure_fin" 
                               value="<?= $creneau_selectionne ? htmlspecialchars($creneau_selectionne['heure_fin']) : '' ?>"
                               required 
                               readonly>
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