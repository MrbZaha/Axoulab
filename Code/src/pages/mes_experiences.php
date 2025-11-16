<?php
require_once '../back_php/init_DB.php';

$_SESSION['ID_compte'] = 1; // TEMPORAIRE pour test

$id_compte = $_SESSION['ID_compte'];

// Récupérer toutes les expériences liées au projet
$sql = "
    SELECT 
        e.ID_experience, 
        e.Validation, 
        e.Description, 
        e.Nom,    
        e.Salle, 
        e.Date_reservation,
        e.Heure_debut,
        e.Heure_fin,
        e.Resultats,
        e.ID_piece_jointe
    FROM experience e
    INNER JOIN projet_experience pe
        ON e.ID_experience = pe.ID_experience
    WHERE pe.ID_projet = :id_projet
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id_projet' => $_GET['id']]);
$experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les infos du projet
$sql2 = "
    SELECT 
        p.ID_projet, 
        p.Nom_projet, 
        p.Description, 
        p.Confidentiel, 
        p.Validation, 
        pcg.Statut,
        p.Date_de_creation
    FROM projet p
    INNER JOIN projet_collaborateur_gestionnaire pcg
        ON p.ID_projet = pcg.ID_projet
    WHERE p.ID_projet = :id_projet
";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute(['id_projet' => $_GET['id']]);
$projet = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Catégoriser les expériences
$date_actuelle = date('Y-m-d');
foreach ($experiences as &$exp) { // Par référence pour modifier directement
    $date_exp = $exp['Date_reservation'];
    if ($date_exp > $date_actuelle) {
        $exp['categorie'] = "à venir";
    } elseif ($date_exp == $date_actuelle) {
        $exp['categorie'] = "en cours";
    } else {
        $exp['categorie'] = "passée";
    }
}
unset($exp); // Bonne pratique après modification par référence
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes expériences</title>
    <link rel="stylesheet" href="mes_experiences.css">
</head>
<body>

<h1>Expériences du projet</h1>

<div class="projet">
    <h2>Projet</h2>
    <div class="liste">
        <a class="projet-card" href="projet.php?id=<?= $projet[0]['ID_projet'] ?>">
            <h3><?= htmlspecialchars($projet[0]['Nom_projet']) ?></h3>
            <p><?= htmlspecialchars($projet[0]['Description']) ?></p>
            <p><strong>Date de création :</strong> <?= htmlspecialchars($projet[0]['Date_de_creation']) ?></p>
            <p><strong>Rôle :</strong> <?= $projet[0]['Statut'] ? "Gestionnaire" : "Collaborateur" ?></p>
        </a>
    </div>

    <h2>Expériences</h2>

    <?php
    $categories = ['en cours' => 'Expérience en cours', 'à venir' => 'Expérience(s) future(s)', 'passée' => 'Expérience(s) passée(s)'];
    foreach ($categories as $key => $titre):
    ?>
        <h3><?= $titre ?></h3>
        <div class="liste">
            <?php foreach ($experiences as $exp): ?>
                <?php if ($exp['categorie'] == $key): ?>
                    <a class="experience-card" href="experience.php?id=<?= $exp['ID_experience'] ?>">
                        <h3><?= htmlspecialchars($exp['Nom']) ?></h3>
                        <p><strong>Date :</strong> <?= htmlspecialchars($exp['Date_reservation']) ?></p>
                        <p><strong>Heure de début :</strong> <?= htmlspecialchars($exp['Heure_debut']) ?></p>
                        <p><strong>Heure de fin :</strong> <?= htmlspecialchars($exp['Heure_fin']) ?></p>
                        <p><strong>Salle :</strong> <?= htmlspecialchars($exp['Salle']) ?></p>
                        <p><strong>Description :</strong> <?= htmlspecialchars($exp['Description']) ?></p>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

</div>

</body>
</html>
