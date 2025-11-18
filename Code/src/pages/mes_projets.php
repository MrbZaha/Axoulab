<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$_SESSION['ID_compte'] = 3; // TEMPORAIRE pour test

function get_mes_projets_complets(PDO $pdo, int $id_compte): array {
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

    $gestionnaires = [];
    foreach ($rows as $row) {
        $gestionnaires[$row['ID_projet']][] = $row['Prenom'] . ' ' . $row['Nom'];
    }

    foreach ($projets as &$p) {
        $p['Gestionnaires'] = $gestionnaires[$p['ID_projet']] ?? [];
    }

    return $projets;
}

function create_page(array $items, int $items_par_page = 6): int {
    $total_items = count($items);
    if ($total_items == 0) {
        return 1;
    }
    return (int)ceil($total_items / $items_par_page);
}

function afficher_projets_pagines(array $projets, int $page_actuelle = 1, int $items_par_page = 6): void {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $projets_page = array_slice($projets, $debut, $items_par_page);
    
    ?>
    <div class="liste">
        <?php if (empty($projets_page)): ?>
            <p class="no-projects">Aucun projet en cours</p>
        <?php else: ?>
            <?php foreach ($projets_page as $p): ?>
                <?php 
                $id = htmlspecialchars($p['ID_projet']);
                $nom = htmlspecialchars($p['Nom_projet']);
                $description = $p['Description'];
                $desc = strlen($description) > 200 
                    ? htmlspecialchars(substr($description, 0, 200)) . '…'
                    : htmlspecialchars($description);
                $date = htmlspecialchars($p['Date_de_creation']);
                $role = $p['Statut'] ? "Gestionnaire" : "Collaborateur";
                ?>
                
                <a class='projet-card' href='projet.php?id=<?= $id ?>'>
                    <h3><?= $nom ?></h3>
                    <p><?= $desc ?></p>
                    <p><strong>Date de création :</strong> <?= $date ?></p>
                    <p><strong>Rôle :</strong> <?= $role ?></p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function afficher_pagination(int $page_actuelle, int $total_pages, string $type = 'en_cours'): void {
    if ($total_pages <= 1) return;
    
    // Préserver l'autre paramètre de page dans l'URL
    $autre_type = ($type === 'en_cours') ? 'termines' : 'en_cours';
    $autre_page = isset($_GET["page_$autre_type"]) ? (int)$_GET["page_$autre_type"] : 1;
    
    ?>
    <div class="pagination">
        <?php if ($page_actuelle > 1): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle - 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">« Précédent</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page_actuelle): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page_<?= $type ?>=<?= $i ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page_actuelle < $total_pages): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle + 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">Suivant »</a>
        <?php endif; ?>
    </div>
    <?php
}

// Récupération et filtrage des projets
$id_compte = $_SESSION['ID_compte'];
$projets = get_mes_projets_complets($pdo, $id_compte);

// Séparation en deux listes
$projets_en_cours = array_filter($projets, fn($p) => $p['Validation'] == 0);
$projets_termines = array_filter($projets, fn($p) => $p['Validation'] == 1);

// Réindexation des tableaux
$projets_en_cours = array_values($projets_en_cours);
$projets_termines = array_values($projets_termines);

// Configuration pagination
$items_par_page = 6;
$page_en_cours = isset($_GET['page_en_cours']) ? max(1, (int)$_GET['page_en_cours']) : 1;
$page_termines = isset($_GET['page_termines']) ? max(1, (int)$_GET['page_termines']) : 1;

$total_pages_en_cours = create_page($projets_en_cours, $items_par_page);
$total_pages_termines = create_page($projets_termines, $items_par_page);

// Vérification que les pages demandées existent
if ($page_en_cours > $total_pages_en_cours) $page_en_cours = $total_pages_en_cours;
if ($page_termines > $total_pages_termines) $page_termines = $total_pages_termines;
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
    <section class="section-projets">
        <h2>Projets en cours (<?= count($projets_en_cours) ?>)</h2>
        <?php afficher_projets_pagines($projets_en_cours, $page_en_cours, $items_par_page); ?>
        <?php afficher_pagination($page_en_cours, $total_pages_en_cours, 'en_cours'); ?>
    </section>

    <section class="section-projets">
        <h2>Projets terminés (<?= count($projets_termines) ?>)</h2>
        <?php afficher_projets_pagines($projets_termines, $page_termines, $items_par_page); ?>
        <?php afficher_pagination($page_termines, $total_pages_termines, 'termines'); ?>
    </section>
</div>

<?php afficher_Bandeau_Bas() ?>
</body>
</html>
