<?php
session_start();
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_mes_projets.php';

// On récupère l'ensemble des projets
$projets = get_mes_projets_complets($bdd, $id_compte);

// Réindexation des tableaux
$projets = array_values($projets);

// Configuration pagination
$items_par_page = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$total_pages = create_page($projets, $items_par_page);

// Vérification que les pages demandées existent
if ($page > $total_pages) $page = $total_pages;
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
            <h2>Expériences (<?= count($projets) ?>)</h2>
            <?php afficher_projets_pagines($bdd, $projets, $page, $items_par_page, false); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>
    </div>

    <?php afficher_Bandeau_Bas() ?>
</body>
</html>
