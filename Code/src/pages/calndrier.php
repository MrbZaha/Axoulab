<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
$id_compte = $_SESSION['ID_compte'];
$id_salle = isset($_GET['id_salle']) ? (int)$_GET['id_salle'] : 0;

function recup_salles($bdd) {
    $sql = "
        SELECT 
            ID_salle,
            Salle
        FROM salle_materiel
        ORDER BY Salle
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute();
    $salles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $salles;
}

function recuperer_info_salle($bdd, $id_salle) {
    $sql = "
        SELECT 
            sm.ID_salle,
            sm.Salle,
            sm.Materiel,
            p.ID_projet AS ID_experience,
            p.Date_reservation,
            p.Heure_debut,
            p.Heure_fin,
            p.Nom AS nom_experience
        FROM salle_materiel sm
        LEFT JOIN salle_experience se ON se.ID_salle = sm.ID_salle
        LEFT JOIN projet p ON p.ID_projet = se.ID_experience
        WHERE sm.ID_salle = :id_salle
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_salle' => $id_salle]);
    $info_salle = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $info_salle ?: [];
}

function recuperer_reservations_semaine($bdd, $id_salle, $date_debut, $date_fin) {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Nom AS nom_experience,
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Description,
            e.Statut_experience
        FROM experience e
        INNER JOIN salle_experience se ON se.ID_experience = e.ID_experience
        WHERE se.ID_salle = :id_salle
        AND e.Date_reservation BETWEEN :date_debut AND :date_fin
        ORDER BY e.Date_reservation, e.Heure_debut
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'id_salle' => $id_salle,
        'date_debut' => $date_debut,
        'date_fin' => $date_fin
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_dates_semaine($date_reference = null) {
    if ($date_reference === null) {
        $date_reference = date('Y-m-d');
    }
    
    $date = new DateTime($date_reference);
    
    $jour_semaine = $date->format('N'); 
    $date->modify('-' . ($jour_semaine - 1) . ' days');
    
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    
    $dates_semaine = [];
    for ($i = 0; $i < 7; $i++) {
        $dates_semaine[] = [
            'date' => $date->format('Y-m-d'),
            'jour' => $jours[$i],
            'numero_jour' => $i + 1 
        ];
        $date->modify('+1 day');
    }
    
    return $dates_semaine;

}

function creneau_est_occupe($reservations, $jour, $heure) {}

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <title>Calendrier</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../css/parcours.css">
    </head>
    <body>
        <h1>Planning des salles</h1>
        <table> 
            <thead class="table">
            <tr> 
                <th></th>
                <th>Lundi</th>
                <th>Mardi</th> 
                <th>Mercredi</th> 
                <th>Jeudi</th>
                <th>Vendredi</th>
                <th>Samedi</th>
                <th>Dimanche</th>
            </tr>
            </thead>
            <tbody>
        <tr>
        <th>8H</th>
        <?php for ($jour = 0; $jour < 7; $jour++): ?>
        <td>
            <?php 
            $reservation = creneau_est_occupe($reservations, $dates_semaine[$jour], 8);
            if ($reservation) {
                echo '<div class="reservation">' . htmlspecialchars($reservation['nom_experience']) . '</div>';
            }
            ?>
        </td>
        <?php endfor; ?>
        </tr>
            </tr>
            <tr>
                <th>9H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>10H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>11H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>12H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>13H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr> 
            <tr>
                <th>14H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>15H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>16H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>17H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>18H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            <tr>
                <th>19H</th>
                <td></td>
                <td></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td> 
                <td></td>
            </tr>
            </tbody>
        </table>
    </body>
</html>