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
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset= "utf-8"/>
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title> utilisateur </title>

    </head>
    <body>

    <!-- import du header de la page -->
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    #bandeau_page("Dashboard", true)
    ?>
    <!-- Afficher le titre de la page en bandeau-->
    <div class=bandeau>
        <p> <?php echo "Utilisateurs"; ?></p>
    </div> 


<!-- Crée un grand div qui aura des bords arrondis et sera un peu gris-->
    <div class="back_square">
        <!-- À l'intérieur, affichage des différentes expériences une par une, avec aspect spécifique et boutons -->
    </div>


    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>

</html>