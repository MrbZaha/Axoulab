<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

$page_admin = true;  // On déclare qu'on est sur une page admin pour les fonctions qui le nécessite

///////////////////////////////////////////////////////////////////////////////
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// On vérifie si l'utilisateur a les droits pour accéder à cette page
if (est_admin_par_id($bdd, $_SESSION["ID_compte"])){
    // Le code peut poursuivre
} else {
    // On change le layout de la page et on invite l'utilisateur à revenir sur la page précédente
    layout_erreur();
}

///////////////////////////////////////////////////////////////////////////////
//On récupère l'ensemble des projets
$projets = get_all_projet($bdd, $_SESSION["ID_compte"]); 

// Réindexation des tableaux
$projets = array_values($projets);

// Configuration pagination
$items_par_page = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$total_pages = create_page($projets, $items_par_page);

// Vérification que les pages demandées existent
if ($page > $total_pages) $page = $total_pages;

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à supprimer un projet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    // Vérification CSRF
    check_csrf();

    if (isset($_POST['id'])) {
        $id_projet = intval($_POST['id']);
        supprimer_projet($bdd, $id_projet);
        $_SESSION['popup_message'] = afficher_popup("Suppression réussie", "Le projet a été supprimé avec succès.", "success","page_admin_projets");
        // On recharge la page proprement (pour empêcher de supprimer deux fois)
        header("Location: page_admin_projets.php?suppression=ok");
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset= "utf-8"/>
        <!--permet d'uniformiser le style sur tous les navigateurs-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
        <link rel="stylesheet" href="../css/page_mes_projets.css"> <!-- Utilisé pour l'affichage des projets-->
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/boutons.css">
        <link rel="stylesheet" href="../css/popup.css">
        <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title> Projets </title>

    </head>
    <body>

    <?php 
    if (isset($_SESSION['popup_message'])) {
        echo $_SESSION['popup_message'];
        unset($_SESSION['popup_message']); // Pour ne pas l’afficher à chaque reload
    }
    ?>
    <!-- import du header de la page -->
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    ?>
    <!-- Afficher le titre de la page en bandeau-->
    <div class=bandeau>
        <p> <?php echo "Projets"; ?></p>
    </div> 

    <!-- Crée un grand div qui aura des bords arrondis et sera un peu gris-->
    <div class="back_square">
    <!-- Affichage des projets un à un-->
        <section class="section-projets">
            <!-- Debug -->
            <h2>Projet(s) (<?= count($projets) ?>)</h2>  <!--Titre affichant le nombre de projets-->
            <?php afficher_projets_pagines($bdd, $projets, $page, $items_par_page, $page_admin); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>
        <!-- À l'intérieur, avec aspect spécifique et boutons -->
    </div>


    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>

</html>