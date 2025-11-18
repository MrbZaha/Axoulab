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

function get_mes_projets_complets(PDO $pdo, int $id_compte): array {
    // Récupération des projets du compte
    $sql_projets = "
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
        WHERE pcg.ID_compte = :id_compte
    ";
    $stmt = $pdo->prepare($sql_projets);
    $stmt->execute(['id_compte' => $id_compte]);
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($projets)) {
        return [];
    }

    // Récupération des gestionnaires pour tous les projets trouvés
    $ids_projets = array_column($projets, 'ID_projet');
    $in = str_repeat('?,', count($ids_projets) - 1) . '?';

    $sql_gestionnaires = "
        SELECT 
            pcg.ID_projet, 
            c.Nom, 
            c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        INNER JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.Statut = 1 AND pcg.ID_projet IN ($in)
    ";
    $stmt2 = $pdo->prepare($sql_gestionnaires);
    $stmt2->execute($ids_projets);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Organisation des gestionnaires par projet
    $gestionnaires = [];
    foreach ($rows as $row) {
        $gestionnaires[$row['ID_projet']][] = $row['Prenom'] . ' ' . $row['Nom'];
    }

    // Ajout des gestionnaires directement dans le tableau des projets
    foreach ($projets as &$p) {
        $p['Gestionnaires'] = $gestionnaires[$p['ID_projet']] ?? [];
    }

    return $projets;
}

function afficher_projet(array $projet): void {
    $id = htmlspecialchars($projet['ID_projet']);
    $nom = htmlspecialchars($projet['Nom_projet']);
    $description = $projet['Description'];
    $desc = strlen($description) > 200 
    ? htmlspecialchars(substr($description, 0, 200)) . '…'
    : htmlspecialchars($description);    $date = htmlspecialchars($projet['Date_de_creation']);
    $role = $projet['Statut'] ? "Gestionnaire" : "Collaborateur";

    echo "<a class='projet-card' href='projet.php?id=$id'>";
    echo "<h3>$nom</h3>";
    echo "<p>$desc</p>";
    echo "<p><strong>Date de création :</strong> $date</p>";
    echo "<p><strong>Rôle :</strong> $role</p>";
    echo "</a>";
}

$id_compte = $_SESSION['ID_compte'];
$projets = get_mes_projets_complets($pdo, $id_compte);
$ids_projets = array_column($projets, 'ID_projet');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes projets</title>
    <link rel="stylesheet" href="../css/mes_projets.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($pdo, $id_compte)?>
<h1>Mes projets</h1>

<div class="projets">

    <h2>Projets en cours</h2>
    <div class="liste">
        <?php foreach ($projets as $p): ?>
            <?php if ($p['Validation'] == 0): ?>
                <?php afficher_projet($p); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <h2>Projets terminés</h2>
    <div class="liste">
        <?php foreach ($projets as $p): ?>
            <?php if ($p['Validation'] == 1): ?>
                <?php afficher_projet($p); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php afficher_Bandeau_Bas() ?>
</body>
</html>