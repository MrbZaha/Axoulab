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
            sm.ID_materiel
        FROM experience e
        INNER JOIN materiel_experience em ON em.ID_experience = e.ID_experience
        INNER JOIN salle_materiel sm ON sm.ID_materiel = em.ID_materiel
        WHERE sm.Nom_salle = :nom_salle
        AND DATE(e.Date_reservation) BETWEEN :date_debut AND :date_fin
        ORDER BY e.Date_reservation, e.Heure_debut
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'nom_salle' => $nom_salle,
        'date_debut' => $date_debut,
        'date_fin' => $date_fin
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($rows as $r) {
        $id = $r['ID_experience'];
        if (!isset($grouped[$id])) {
            $grouped[$id] = [
                'ID_experience' => $r['ID_experience'],
                'nom_experience' => $r['nom_experience'],
                'Date_reservation' => $r['Date_reservation'],
                'Heure_debut' => $r['Heure_debut'],
                'Heure_fin' => $r['Heure_fin'],
                'Description' => $r['Description'],
                'Statut_experience' => $r['Statut_experience'],
                'materiels' => []
            ];
        }
        $grouped[$id]['materiels'][] = [
            'ID_materiel' => $r['ID_materiel']
        ];
    }

    return array_values($grouped);
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

// ======================= FONCTIONS CRÉATION EXPÉRIENCE =======================
function creer_experience($bdd, $nom_experience, $description, $date_reservation, $heure_debut, $heure_fin, $nom_salle) {
    $sql = $bdd->prepare("
        INSERT INTO experience (Nom, Description, Date_reservation, Heure_debut, Heure_fin, Statut_experience, Validation)
        VALUES (?, ?, ?, ?, ?, 'En attente', 0)
    ");

    if ($sql->execute([$nom_experience, $description, $date_reservation, $heure_debut, $heure_fin])) {
        return $bdd->lastInsertId();
    }
    return false;
}

function associer_experience_a_projet($bdd, $id_projet, $id_experience) {
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

function associer_materiel_experience($bdd, $id_experience, $nom_salle) {
    // Récupérer tous les matériels de la salle
    $sql = "SELECT ID_materiel FROM salle_materiel WHERE Nom_salle = ?";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([$nom_salle]);
    $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Associer chaque matériel à l'expérience
    $sql_insert = $bdd->prepare("
        INSERT INTO materiel_experience (ID_experience, ID_materiel)
        VALUES (?, ?)
    ");
    
    foreach ($materiels as $mat) {
        $sql_insert->execute([$id_experience, $mat['ID_materiel']]);
    }
}

// ======================= TRAITEMENT DES DONNÉES =======================
$message = "";
$id_projet = null;
$nom_experience = "";
$description = "";
$experimentateurs_ids = [];

// Récupération des données de la page 1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_projet'])) {
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
    
    // Traitement de la création finale
    if (isset($_POST['creer_experience'])) {
        $date_reservation = $_POST['date_reservation'];
        $heure_debut = $_POST['heure_debut'];
        $heure_fin = $_POST['heure_fin'];
        $nom_salle = $_POST['nom_salle'];
        
        // Créer l'expérience
        $id_experience = creer_experience($bdd, $nom_experience, $description, $date_reservation, $heure_debut, $heure_fin, $nom_salle);
        
        if ($id_experience) {
            // Associer au projet
            associer_experience_a_projet($bdd, $id_projet, $id_experience);
            
            // Ajouter les expérimentateurs
            if (!empty($experimentateurs_ids)) {
                ajouter_experimentateurs($bdd, $id_experience, $experimentateurs_ids);
            }
            
            // Associer les matériels de la salle
            associer_materiel_experience($bdd, $id_experience, $nom_salle);
            
            // Redirection
            header("Location: page_experience.php?id_projet=" . $id_projet . "&id_experience=" . $id_experience);
            exit();
        } else {
            $message = "<p style='color:red;'>Erreur lors de la création de l'expérience.</p>";
        }
    }
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

</style>
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"]); ?>

<div class="container">
    <h1>Réservation de salle - Étape 2/2</h1>

    <?php if (!empty($message)) echo $message; ?>

    <!-- Récapitulatif de l'expérience -->
    <div class="info-experience">
        <h3>📋 Récapitulatif de l'expérience</h3>
        <p><strong>Nom :</strong> <?= htmlspecialchars($nom_experience) ?></p>
        <p><strong>Description :</strong> <?= htmlspecialchars($description) ?></p>
        <p><strong>Projet ID :</strong> <?= $id_projet ?></p>
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
            Cliquez sur un créneau disponible pour réserver
        </p>

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
                                $occ = creneau_est_occupe($reservations, $jour, $heure);
                                if (!empty($occ)):
                                    foreach ($occ as $i => $res):
                                        $cls = 'couleur-' . (($i % 4) + 1);
                                ?>
                                        <div class="cell-content">
                                            <div class="reservation <?= $cls ?>" title="<?= htmlspecialchars($res['Description'] ?? '') ?>">
                                                <div style="font-weight:700;"><?= htmlspecialchars($res['nom_experience']) ?></div>
                                                <div style="font-size:0.85em;margin-top:6px;color:#f7f7f7;">
                                                    <?= htmlspecialchars(substr($res['Heure_debut'],0,5)) ?> — <?= htmlspecialchars(substr($res['Heure_fin'],0,5)) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach;
                                else: ?>
                                    <div class="cell-content selectable" 
                                         data-date="<?= $jour['date'] ?>" 
                                         data-heure="<?= $heure ?>"
                                         onclick="selectionnerCreneau(this)">
                                        <div class="empty-slot">—</div>
                                    </div>
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

                <div class="form-group">
                    <label for="date_reservation">Date :</label>
                    <input type="date" id="date_reservation" name="date_reservation" required>
                </div>

                <div class="form-group">
                    <label for="heure_debut">Heure de début :</label>
                    <input type="time" id="heure_debut" name="heure_debut" required>
                </div>

                <div class="form-group">
                    <label for="heure_fin">Heure de fin :</label>
                    <input type="time" id="heure_fin" name="heure_fin" required>
                </div>

                <button type="submit" name="creer_experience" class="btn-creer">
                    Créer l'expérience et réserver
                </button>
            </form>
        </div>

    <?php else: ?>
        <p style="text-align:center;margin-top:20px;">Aucune salle trouvée.</p>
    <?php endif; ?>
</div>

<script>
function selectionnerCreneau(element) {
    // Désélectionner tous les créneaux
    document.querySelectorAll('.cell-content.selected').forEach(el => {
        el.classList.remove('selected');
    });
    
    // Sélectionner le créneau cliqué
    element.classList.add('selected');
    
    // Remplir le formulaire
    const date = element.getAttribute('data-date');
    const heure = parseInt(element.getAttribute('data-heure'));
    
    document.getElementById('date_reservation').value = date;
    document.getElementById('heure_debut').value = String(heure).padStart(2, '0') + ':00';
    document.getElementById('heure_fin').value = String(heure + 1).padStart(2, '0') + ':00';
    
    // Scroll vers le formulaire
    document.getElementById('form-reservation').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>