<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
include_once '../back_php/fonction_page/fonction_page_admin_utilisateurs.php';

$bdd = connectBDD();

$message = "";  // Variable globale pour les messages

////////////////////////////////////////////////////////////////////////////////
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// On vérifie si l'utilisateur a les droits pour accéder à cette page
if (!est_admin_par_id($bdd, $_SESSION["ID_compte"])) {
    layout_erreur();
    exit;
}

///////////////////////////////////////////////////////////////////////////////
// On récupère la liste des utilisateurs
$utilisateurs = array_values(get_utilisateurs($bdd));

// On set la page que l'on observe
$items_par_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_pages = create_page($utilisateurs, $items_par_page);

// Vérification que la page demandée existe
if ($page > $total_pages) $page = $total_pages;

///////////////////////////////////////////////////////////////////////////////
// TRAITEMENT DES ACTIONS (POST UNIQUEMENT + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    if (isset($_POST['action'], $_POST['id'])) {

        $id_utilisateur = (int)$_POST['id'];

        switch ($_POST['action']) {

            case 'modifier':
                $resultat = modifier_utilisateur($bdd, $id_utilisateur);
                if ($resultat === true) {
                    header("Location: page_admin_utilisateurs.php?modification=ok");
                    $_SESSION['popup_message'] = afficher_popup("Modification réussie", "Les informations de l'utilisateur ont été mises à jour.","page_admin_utilisateurs", "success");

                } else {
                    header("Location: page_admin_utilisateurs.php?erreur=" . urlencode($resultat));
                    $_SESSION['popup_message'] = afficher_popup("Erreur", "Une erreur est survenue : " . htmlspecialchars($_GET['erreur']),"page_admin_utilisateurs", "error");

                }
                exit;

            case 'supprimer':
                if ($_SESSION['ID_compte'] !== $id_utilisateur) {
                    supprimer_utilisateur($bdd, $id_utilisateur);
                    $_SESSION['popup_message'] = afficher_popup("Suppression réussie", "L'utilisateur a été supprimé avec succès.","page_admin_utilisateurs", "success");
                    header("Location: page_admin_utilisateurs.php?suppression=ok");
                }
                exit;

            case 'accepter':
                accepter_utilisateur($bdd, $id_utilisateur);
                $_SESSION['popup_message'] = afficher_popup("Validation réussie", "Le compte utilisateur a été validé.","page_admin_utilisateurs", "success");
                header("Location: page_admin_utilisateurs.php?accept=ok");
                exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8"/>
        <!--permet d'uniformiser le style sur tous les navigateurs-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
        <link rel="stylesheet" href="../css/page_mes_experiences.css">
        <link rel="stylesheet" href="../css/page_admin_utilisateurs_materiel.css">
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/boutons.css">
        <link rel="stylesheet" href="../css/popup.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title>Utilisateurs</title>
    </head>
    <body>
    
    <?php 
    if (isset($_SESSION['popup_message'])) {
        echo $_SESSION['popup_message'];
        unset($_SESSION['popup_message']); // Pour ne pas l’afficher à chaque reload
    }
    ?>


    <?php afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]); ?>

    <div class=bandeau>
        <p><?php echo "Utilisateurs"; ?></p>
    </div> 

    <div class="back_square">
        <section class="section-experiences">
            <h2>Utilisateurs (<?= count($utilisateurs) ?>)</h2>
            <?php afficher_utilisateurs_pagines($utilisateurs, $page, $items_par_page, $bdd); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>
    </div>

    <?php afficher_Bandeau_Bas(); ?>
    </body>
</html>