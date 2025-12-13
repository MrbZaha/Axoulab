<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}
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
//On récupère l'ensemble des expériences
$experiences = get_mes_experiences_complets($bdd); 

// Réindexation des tableaux
$experiences = array_values($experiences);

// Configuration pagination
$items_par_page = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$total_pages = create_page($experiences, $items_par_page);

// Vérification que les pages demandées existent
if ($page > $total_pages) $page = $total_pages;

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à supprimer une expérience
// Si une action GET est reçue
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    if (isset($_GET['id'])) {
        $id_experience = intval($_GET['id']);
        supprimer_experience($bdd, $id_experience);

        // On recharge la page proprement (cela empêche de supprimer deux fois)
        header("Location: page_admin_experiences.php?suppression=ok");
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset= "utf-8"/>
            <link rel="stylesheet" href="../css/page_mes_experiences.css"> <!-- Utilisé pour l'affichage des exp-->
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/boutons.css">

        <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title> Expériences </title>

    </head>
    <body>

    <!-- import du header de la page -->
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    ?>
    <!-- Afficher le titre de la page en bandeau-->
    <div class=bandeau>
        <p> <?php echo "Expériences"; ?></p>
    </div> 

    <!-- Crée un grand div qui aura des bords arrondis et sera un peu gris-->
    <div class="back_square">
    <!-- Affichage des expériences une à une-->
        <section class="section-experiences">
            <h2>Expériences (<?= count($experiences) ?>)</h2>  <!--Titre affichant le nombre d'expérience-->
            <?php afficher_experiences_pagines($bdd, $experiences, $page, $items_par_page, $page_admin); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>
        <!-- À l'intérieur, avec aspect spécifique et boutons -->
    </div>

    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>

</html>