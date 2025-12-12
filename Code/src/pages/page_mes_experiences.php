<?php
session_start();
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// Récupération et filtrage des expériences
$id_compte = $_SESSION['ID_compte'];
$experiences = get_mes_experiences_complets($bdd, $id_compte);


// Réindexation des tableaux
$experiences = array_values($experiences);

// Configuration pagination
$items_par_page = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$total_pages = create_page($experiences, $items_par_page);

// Vérification que les pages demandées existent
if ($page > $total_pages) $page = $total_pages;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes expériences</title>
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_mes_experiences.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class=main-content>
        <?php afficher_Bandeau_Haut($bdd, $id_compte)?>
        <h1>Mes expériences</h1>

        <div class="experiences">
            <section class="section-experiences">
                <h2>Expériences (<?= count($experiences) ?>)</h2>
                <?php afficher_experiences_pagines($bdd, $experiences, $page, $items_par_page, false); ?>
                <?php afficher_pagination($page, $total_pages); ?>
            </section>

        </div>

        <?php afficher_Bandeau_Bas() ?>
    </div>
</body>
</html>
