<?php
require_once __DIR__ . '/../back_php/init_DB.php';

$_SESSION['ID_compte'] = 1; // TEMPORAIRE pour test

/*
if (!isset($_SESSION['ID_compte'])) {
    header('Location: login.php');
    exit;
}
*/

$id_compte = $_SESSION['ID_compte'];
$id_projet = $_GET['id'];
function get_info_projet(PDO $pdo,int $id_compte,int $id_projet){
    $sql_projet = "
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
    WHERE pcg.ID_compte = :id_compte AND p.ID_projet = :id_projet
    ";
    $stmt = $pdo->prepare($sql_projet);
    $stmt->execute(['id_compte' => $id_compte, 'id_projet' => $id_projet]);
    $projet = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($projet)) {
        echo("Désolé ce projet n'existe pas");
    }
        if(verifier_confidentialite($pdo, $id_compte, $id_projet)){

        }
}
/* Verifier comment tourner la fonction verifier_confidentialite, je ne suis pas sur que ca fait
exacteemnt ce que je veux pour l'utiliser dans get_info_projet*/
function verifier_confidentialite(PDO $pdo, int $id_compte, int $id_projet): bool {
    $sql = "
        SELECT p.Confidentiel
        FROM projet p
        LEFT JOIN projet_collaborateur_gestionnaire pcg 
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE p.ID_projet = :id_projet
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_compte' => $id_compte,
        'id_projet' => $id_projet
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return false;
    }

    if ((int)$result['Confidentiel'] === 0) {
        return true;
    }
    return $result !== false && $result['Confidentiel'] == 1 && $result['Confidentiel'] !== null && $stmt->rowCount() > 0;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes projets</title>
    <link rel="stylesheet" href="../css/mes_projets.css">
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
