<?php
session_start();
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

function recup_salles($bdd) {
    $sql = "SELECT ID_salle, Nom_salle FROM salle ORDER BY Nom_salle";
    $stmt = $bdd->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function recuperer_materiels_salle($bdd, $id_salle) {
    $sql = "
        SELECT ID_materiel_salle, Type_materiel, Nombre
        FROM materiel_salle
        WHERE ID_salle = :id_salle
        ORDER BY Type_materiel
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_salle' => $id_salle]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function recuperer_reservations_semaine($bdd, $id_salle, $date_debut, $date_fin) {
    $sql = "
        SELECT
            e.ID_experience,
            e.Nom AS nom_experience,
            DATE(e.Date_reservation) AS Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Description,
            e.Statut_experience,
            ms.Type_materiel,
            ms.ID_materiel_salle,
            em.Quantite_utilisee
        FROM experience e
        INNER JOIN experience_materiel em ON em.ID_experience = e.ID_experience
        INNER JOIN materiel_salle ms ON ms.ID_materiel_salle = em.ID_materiel_salle
        WHERE ms.ID_salle = :id_salle
        AND DATE(e.Date_reservation) BETWEEN :date_debut AND :date_fin
        ORDER BY e.Date_reservation, e.Heure_debut
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'id_salle' => $id_salle,
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
            'ID_materiel_salle' => $r['ID_materiel_salle'],
            'Type_materiel' => $r['Type_materiel'],
            'Quantite_utilisee' => $r['Quantite_utilisee']
        ];
    }

    return array_values($grouped);
}

function recuperer_reservations_semaine_sans_salle($bdd, $date_debut, $date_fin) {
    $sql = "
        SELECT
            e.ID_experience,
            e.Nom AS nom_experience,
            DATE(e.Date_reservation) AS Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Description,
            e.Statut_experience,
            ms.Type_materiel,
            ms.ID_materiel_salle,
            em.Quantite_utilisee,
            ms.ID_salle
        FROM experience e
        INNER JOIN experience_materiel em ON em.ID_experience = e.ID_experience
        INNER JOIN materiel_salle ms ON ms.ID_materiel_salle = em.ID_materiel_salle
        WHERE DATE(e.Date_reservation) BETWEEN :date_debut AND :date_fin
        ORDER BY e.Date_reservation, e.Heure_debut
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([
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
            'date_formatee' => $date->format('d/m')
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

$salles = recup_salles($bdd);

$id_salle = isset($_GET['id_salle']) ? (int)$_GET['id_salle'] : ($salles[0]['ID_salle'] ?? 0);

// Gestion de la date de référence pour la semaine
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

$nom_salle = '';
$materiels = [];
$reservations = [];

if ($id_salle > 0) {
    foreach ($salles as $s) {
        if ((int)$s['ID_salle'] === $id_salle) {
            $nom_salle = $s['Nom_salle'];
            break;
        }
    }
    $materiels = recuperer_materiels_salle($bdd, $id_salle);
    $reservations = recuperer_reservations_semaine($bdd, $id_salle, $date_debut, $date_fin);
}

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug) {
    echo "<h2>DEBUG MODE</h2>";
    echo "<p><strong>ID_salle sélectionnée :</strong> " . htmlspecialchars((string)$id_salle) . "</p>";
    echo "<p><strong>Nom salle trouvée :</strong> " . htmlspecialchars($nom_salle) . "</p>";
    echo "<p><strong>Semaine :</strong> {$date_debut} → {$date_fin}</p>";

    echo "<h3>Materiels (pour la salle)</h3><pre>";
    var_dump($materiels);
    echo "</pre>";

    echo "<h3>Réservations (après regroupement par ID_experience)</h3><pre>";
    var_dump($reservations);
    echo "</pre>";

    echo "<h3>Toutes les réservations (sans filtre de salle) — pour comparaison</h3><pre>";
    $all = recuperer_reservations_semaine_sans_salle($bdd, $date_debut, $date_fin);
    var_dump($all);
    echo "</pre>";
}

$heures = range(8, 19);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Planning salles - debug</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/page_creation_experience_2.css">
<style>

</style>
</head>
<body>
<div class="container">
    <h1>📅 Planning des salles</h1>

    <!-- form - salle select -->
    <form method="get" action="" class="form-row">
        <label for="id_salle" style="font-weight:700;margin-right:6px">Choisir une salle :</label>
        <select name="id_salle" id="id_salle" onchange="this.form.submit()" style="padding:8px 10px;border-radius:6px;border:1px solid #d0d0d0;">
            <?php foreach ($salles as $s): ?>
                <option value="<?= (int)$s['ID_salle'] ?>" <?= ((int)$s['ID_salle'] === $id_salle) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['Nom_salle']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($debug)): ?>
            <input type="hidden" name="debug" value="1">
        <?php endif; ?>
        <?php if (isset($_GET['date_ref'])): ?>
            <input type="hidden" name="date_ref" value="<?= htmlspecialchars($_GET['date_ref']) ?>">
        <?php endif; ?>
    </form>

    <?php if ($id_salle > 0 && $nom_salle !== ''): ?>
        <h2>Salle : <?= htmlspecialchars($nom_salle) ?></h2>

        <div class="navigation-semaine">
            <a href="?id_salle=<?= (int)$id_salle ?>&date_ref=<?= htmlspecialchars($semaine_precedente) ?><?= $debug ? '&debug=1' : '' ?>" class="btn-nav">
                ← Semaine précédente
            </a>
            
            <span class="semaine-info">
                Semaine du <?= htmlspecialchars($dates_semaine[0]['date_formatee']) ?> au <?= htmlspecialchars($dates_semaine[6]['date_formatee']) ?>
            </span>
            
            <a href="?id_salle=<?= (int)$id_salle ?>&date_ref=<?= htmlspecialchars($semaine_suivante) ?><?= $debug ? '&debug=1' : '' ?>" class="btn-nav">
                Semaine suivante →
            </a>
        </div>

        <?php if ($date_ref !== date('Y-m-d')): ?>
            <div style="text-align:center;margin:10px 0;">
                <a href="?id_salle=<?= (int)$id_salle ?><?= $debug ? '&debug=1' : '' ?>" class="btn-nav" style="display:inline-block;">
                    Revenir à la semaine actuelle
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($materiels)): ?>
            <div class="materiel-info">
                <strong>Matériel disponible :</strong>
                <ul style="margin:8px 0 0 14px;padding:0;">
                    <?php foreach ($materiels as $m): ?>
                        <li style="list-style:none;margin:6px 0;"><?= htmlspecialchars($m['Type_materiel']) ?> — <?= (int)$m['Nombre'] ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($debug)): ?>
            <div style="margin-top:10px;padding:8px;background:#fff9c4;border:1px solid #f0e68c;border-radius:6px;">
                <strong>DEBUG:</strong>
                ID salle = <?= (int)$id_salle ?> |
                total réservations récupérées = <?= (int)count($reservations) ?> |
                semaine = <?= htmlspecialchars($dates_semaine[0]['date']) ?> → <?= htmlspecialchars($dates_semaine[6]['date']) ?>
            </div>
        <?php endif; ?>

        <table aria-describedby="planning">
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
                                <div class="cell-content">
                                    <?php
                                    $occ = creneau_est_occupe($reservations, $jour, $heure);
                                    if (!empty($occ)):
                                        foreach ($occ as $i => $res):
                                            $cls = 'couleur-' . (($i % 4) + 1);
                                    ?>
                                            <div class="reservation <?= $cls ?>" title="<?= htmlspecialchars($res['Description'] ?? '') ?>">
                                                <div style="font-weight:700;"><?= htmlspecialchars($res['nom_experience']) ?></div>
                                                <div style="font-size:0.85em;margin-top:6px;color:#f7f7f7;">
                                                    <?= htmlspecialchars(substr($res['Heure_debut'],0,5)) ?> — <?= htmlspecialchars(substr($res['Heure_fin'],0,5)) ?>
                                                </div>
                                                <?php if (!empty($res['materiels'])): ?>
                                                    <div class="materiel-liste">
                                                        <?php
                                                        $parts = [];
                                                        foreach ($res['materiels'] as $mm) {
                                                            $parts[] = htmlspecialchars($mm['Type_materiel']) . ' (×' . (int)$mm['Quantite_utilisee'] . ')';
                                                        }
                                                        echo implode(' • ', $parts);
                                                        ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach;
                                    else: ?>
                                        <div class="empty-slot">—</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <p style="text-align:center;margin-top:20px;">Aucune salle trouvée.</p>
    <?php endif; ?>
</div>
</body>
</html>