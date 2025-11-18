<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$_SESSION['ID_compte'] = 3; // TEMPORAIRE pour test

/*
if (!isset($_SESSION['ID_compte'])) {
    header('Location: login.php');
    exit;
}
*/

function get_mes_experiences_complets(PDO $pdo, int $id_compte): array {
    // Récupération des expériences du compte
$sql_experiences = "
    SELECT 
        e.ID_experience, 
        e.Nom, 
        e.Validation, 
        e.Description, 
        e.Date_reservation,
        e.Heure_debut,
        e.Heure_fin,
        e.Resultat,
        e.Fin_experience,
        s.Salle,
        p.Nom_projet,
        p.ID_projet
    FROM experience e
    LEFT JOIN projet_experience pe
        ON pe.ID_experience = e.ID_experience
    LEFT JOIN projet p
        ON p.ID_projet = pe.ID_projet
    INNER JOIN experience_experimentateur ee
        ON e.ID_experience = ee.ID_experience
    LEFT JOIN salle_experience se
        ON e.ID_experience = se.ID_experience
    LEFT JOIN salle_materiel s
        ON se.ID_salle = s.ID_salle
    WHERE ee.ID_compte = :id_compte
";

    $stmt = $pdo->prepare($sql_experiences);
    $stmt->execute(['id_compte' => $id_compte]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($experiences)) {
        return [];
    }
    return $experiences;    
}

    // // Récupération des gestionnaires pour tous les projets trouvés
    // $ids_projets = array_column($projets, 'ID_projet');
    // $in = str_repeat('?,', count($ids_projets) - 1) . '?';

    // $sql_gestionnaires = "
    //     SELECT 
    //         pcg.ID_projet, 
    //         c.Nom, 
    //         c.Prenom
    //     FROM projet_collaborateur_gestionnaire pcg
    //     INNER JOIN compte c ON pcg.ID_compte = c.ID_compte
    //     WHERE pcg.Statut = 1 AND pcg.ID_projet IN ($in)
    // ";
    // $stmt2 = $pdo->prepare($sql_gestionnaires);
    // $stmt2->execute($ids_projets);
    // $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

//     // Organisation des gestionnaires par projet
//     $gestionnaires = [];
//     foreach ($rows as $row) {
//         $gestionnaires[$row['ID_projet']][] = $row['Prenom'] . ' ' . $row['Nom'];
//     }

//     // Ajout des gestionnaires directement dans le tableau des projets
//     foreach ($projets as &$p) {
//         $p['Gestionnaires'] = $gestionnaires[$p['ID_projet']] ?? [];
//     }

//     return $projets;
// }

function afficher_experience(array $experience): void {
    $id_experience = htmlspecialchars($experience['ID_experience']);
    $nom = htmlspecialchars($experience['Nom']);
    $description = $experience['Description'];
    $desc = strlen($description) > 200 
    ? htmlspecialchars(substr($description, 0, 200)) . '…'
    : htmlspecialchars($description);    
    $date_reservation = htmlspecialchars($experience['Date_reservation']);
    $heure_debut = htmlspecialchars($experience['Heure_debut']);
    $heure_fin = htmlspecialchars($experience['Heure_fin']);
    $resultat = htmlspecialchars($experience['Resultat']);
    $fin_experience = htmlspecialchars($experience['Fin_experience']);
    $salle = htmlspecialchars($experience['Salle']);
    $nom_projet = htmlspecialchars($experience['Nom_projet']);
    $id_projet = htmlspecialchars($experience['ID_projet']);


    echo "<a class='projet-card' href='experience.php?id_projet=$id_projet?id_experience=$id_experience'>";
    echo "<h2>$nom_projet</h2>";
    echo "<h3>$nom</h3>";
    echo "<p>$desc</p>";
    echo "<p><strong>Date de création :</strong>$date_reservation</p>";
    echo "</a>";
}

$id_compte = $_SESSION['ID_compte'];
$projets = get_mes_experiences_complets($pdo, $id_compte);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes projets</title>
    <link rel="stylesheet" href="../css/mes_projets.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php afficher_Bandeau_Haut($pdo, $id_compte)?>
</head>
<body>

<h1>Mes projets</h1>

<div class="projets">

    <h2>Expérience à venir</h2>
    <div class="liste">
        <?php foreach ($projets as $p): ?>
            <?php if ($p['Fin_experience'] == 0): ?>
                <?php afficher_experience($p); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <h2>Expériences terminés</h2>
    <div class="liste">
        <?php foreach ($projets as $p): ?>
            <?php if ($p['Fin_experience'] == 1): ?>
                <?php afficher_experience($p); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

</div>

</body>
</html>