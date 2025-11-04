<?php
require_once 'init_DB.php';

$_SESSION['ID_compte'] = 1; // TEMPORAIRE pour test

/*
if (!isset($_SESSION['ID_compte'])) {
    header('Location: login.php');
    exit;
}
*/

$id_compte = $_SESSION['ID_compte'];

$sql = "
    SELECT 
        p.ID_projet, 
        p.Nom_projet, 
        p.Description, 
        p.Confidentiel, 
        p.Validation, 
        pcg.Statut,
        p.Date_de_creation
    FROM table_projet p
    INNER JOIN table_projet_collaborateur_gestionnaire pcg
        ON p.ID_projet = pcg.ID_projet
    WHERE pcg.ID_compte = :id_compte
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_compte' => $id_compte]);
$projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// On récupère tous les gestionnaires pour tous les projets trouvés
$ids_projets = array_column($projets, 'ID_projet');
$gestionnaires = [];

if (!empty($ids_projets)) {
    $in = str_repeat('?,', count($ids_projets) - 1) . '?';
    $sql_gestionnaires = "
        SELECT 
            pcg.ID_projet, 
            c.Nom, 
            c.Prenom
        FROM table_projet_collaborateur_gestionnaire pcg
        INNER JOIN table_compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.Statut = 1 AND pcg.ID_projet IN ($in)
    ";
    $stmt2 = $pdo->prepare($sql_gestionnaires);
    $stmt2->execute($ids_projets);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // On range les gestionnaires par projet
    foreach ($rows as $row) {
        $gestionnaires[$row['ID_projet']][] = $row['Prenom'] . ' ' . $row['Nom'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes projets</title>
    <link rel="stylesheet" href="mes_projets.css">
</head>
<body>

<h1>Mes projets</h1>

<div class="projets">
    <h2>Projets en cours</h2>
    <div class="liste">
        <?php foreach ($projets as $p): ?>
            <?php if ($p['Validation'] == 0): ?>
                <a class="projet-card" href="projet.php?id=<?= $p['ID_projet'] ?>">
                    <h3><?= htmlspecialchars($p['Nom_projet']) ?></h3>
                    <p><?= htmlspecialchars($p['Description']) ?></p>
                    <p><strong>Date de création :</strong> <?= htmlspecialchars($p['Date_de_creation']) ?></p>
                    <p><strong>Rôle :</strong> <?= $p['Statut'] ? "Gestionnaire" : "Collaborateur" ?></p>
                    <?php if (!empty($gestionnaires[$p['ID_projet']])): ?>
                        <p><strong>Gestionnaires :</strong> <?= htmlspecialchars(implode(', ', $gestionnaires[$p['ID_projet']])) ?></p>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <h2>Projets terminés</h2>
    <div class="liste">
        <?php foreach ($projets as $p): ?>
            <?php if ($p['Validation'] == 1): ?>
                <a class="projet-card" href="projet.php?id=<?= $p['ID_projet'] ?>">
                    <h3><?= htmlspecialchars($p['Nom_projet']) ?></h3>
                    <p><?= htmlspecialchars($p['Description']) ?></p>
                    <p><strong>Date de création :</strong> <?= htmlspecialchars($p['Date_de_creation']) ?></p>
                    <p><strong>Rôle :</strong> <?= $p['Statut'] ? "Gestionnaire" : "Collaborateur" ?></p>
                    <?php if (!empty($gestionnaires[$p['ID_projet']])): ?>
                        <p><strong>Gestionnaires :</strong> <?= htmlspecialchars(implode(', ', $gestionnaires[$p['ID_projet']])) ?></p>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
