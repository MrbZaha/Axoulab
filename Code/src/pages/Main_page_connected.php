<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();
$id_compte = $_SESSION['ID_compte'];
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);
maj_bdd_experience($bdd);
$derniers_projets=filtrer_trier_pro_exp($bdd, $id_compte, $types=['projet'],$tri='Date_modif',$ordre='desc');
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset= "utf-8"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
        <link rel="stylesheet" href="../css/Main_page_connected.css">
        <link rel="stylesheet" href="../css/page_mes_projets.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">

        <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title>AxouLab</title>
    </head>
<body>
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    ?>
      <div class="slider">
        <!-- Radios -->
        <input type="radio" name="slider" id="slide1" checked>
        <input type="radio" name="slider" id="slide2">
        <input type="radio" name="slider" id="slide3">

        <!-- Images -->
        <div class="slides">
            <img src="../assets/exemple1.png" alt="Image 1">
            <img src="../assets/exemple2.png" alt="Image 2">
            <img src="../assets/exemple3.png" alt="Image 3">
        </div>

        <!-- Points -->
        <div class="dots">
            <label for="slide1"></label>
            <label for="slide2"></label>
            <label for="slide3"></label>
        </div>
    </div>

    <h1 style="text-align:center; color:#003366;">Derniers Projets</h1>

    <div class="projets">
      <section class="section-projets">
        <?php afficher_projets_pagines($bdd, $derniers_projets, $page_en_cours=1, $items_par_page=3); ?>
      </section>    
    </div>
    

    <?php
    afficher_Bandeau_Bas(); ?>

</body>
</html>
