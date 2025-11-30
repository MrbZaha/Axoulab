<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();
# Doit checker si est administrateur
// $email = $_SESSION("email");
// echo $email;

// if (est_admin($bdd,$email)) {
// }
// else {
//     exit;
// }

# Récupération des différentes expériences 
// Récupération et filtrage des expériences
$id_compte = $_SESSION['ID_compte'];
$experiences = get_mes_experiences_complets($bdd, $id_compte);

// Réindexation des tableaux
$experiences = array_values($experiences);

// Configuration pagination
$items_par_page = 6;
$page = isset($_GET['pages']) ? max(1, (int)$_GET['page']) : 1;

$total_pages = create_page($experiences, $items_par_page);

// Vérification que les pages demandées existent
if ($page > $total_pages) $page = $total_pages;


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset= "utf-8"/>
        <link rel="stylesheet" href="../css/page_mes_experiences.css">
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title> Expériences </title>

    </head>
    <body>

    <!-- import du header de la page -->
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    #bandeau_page("Dashboard", true)
    ?>
    <!-- Afficher le titre de la page en bandeau-->
    <div class=bandeau>
        <p> <?php echo "Expériences"; ?></p>
    </div> 


<!-- Crée un grand div qui aura des bords arrondis et sera un peu gris-->
    <div class="back_square">
        <section class="section-experiences">
            <h2>Expériences (<?= count($experiences) ?>)</h2>
            <?php afficher_experiences_pagines($experiences, $page, $items_par_page); ?>
            <?php afficher_pagination($page, $total_pages, 'a_venir'); ?>
        </section>
        <!-- À l'intérieur, affichage des différentes expériences une par une, avec aspect spécifique et boutons -->
    </div>


    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>

</html>