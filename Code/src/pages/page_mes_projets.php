<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);


// Récupération et filtrage des projets
$id_compte = $_SESSION['ID_compte'];
$projets = get_mes_projets_complets($bdd, $id_compte);

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
    <link rel="stylesheet" href="../css/page_mes_projets.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte)?>
<h1>Mes projets</h1>
<div class="create-projet">
<form action= "page_creation_projet.php" method= "post">
    <input type= "submit" value= "Créer un projet" />
</div>
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
