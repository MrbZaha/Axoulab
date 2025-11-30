<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd)

// On vérifie si l'utilisateur a les droits pour accéder à cette page
if (est_admin($bdd, $_SESSION["email"])){
    // Le code peut poursuivre
}
else {
    // On change le layout de la page et on invite l'utilisateur à revenir sur la page précédente
    layout_erreur();
}


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset= "utf-8"/>
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title> Dashboard </title>

    </head>
    <body>
    
<!-- import du header de la page -->
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    ?>
<!-- Afficher le titre de la page en bandeau-->
    <div class=bandeau>
        <p> <?php echo "Dashboard"; ?></p>
    </div> 


<!-- Crée un grand div qui aura des bords arrondis et sera un peu gris-->
    <div class="back_square">
        <div class=inside_square>
            <a href="page_admin_utilisateurs.php"> Utilisateurs</a>
        </div>
        <div class=inside_square>
            <a href="page_admin_projets.php"> Projets</a>
        </div>
        <div class=inside_square>
            <a href="page_admin_experiences.php"> Expériences</a>
        </div>
        <div class=inside_square>
            <a href="page_admin_materiel_salles.php"> Matériel et salles</a>
        </div>
    </div>


    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>
</html>