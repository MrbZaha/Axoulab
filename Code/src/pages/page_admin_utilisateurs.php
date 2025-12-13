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
if (est_admin_par_id($bdd, $_SESSION["ID_compte"])){
    // Le code peut poursuivre
}
else {
    // On change le layout de la page et on invite l'utilisateur à revenir sur la page précédente
    layout_erreur();
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
// Affichage des messages de confirmation selon les paramètres GET
if (isset($_GET['suppression']) && $_GET['suppression'] === 'ok') {
    $message = afficher_popup("Suppression réussie", "L'utilisateur a été supprimé avec succès.", "success", "page_admin_utilisateurs");
}
if (isset($_GET['modification']) && $_GET['modification'] === 'ok') {
    $message = afficher_popup("Modification réussie", "Les informations de l'utilisateur ont été mises à jour.", "success", "page_admin_utilisateurs");
}
if (isset($_GET['accept']) && $_GET['accept'] === 'ok') {
    $message = afficher_popup("Validation réussie", "Le compte utilisateur a été validé.", "success", "page_admin_utilisateurs");
}
if (isset($_GET['erreur'])) {
    $message = afficher_popup("Erreur", "Une erreur est survenue : " . htmlspecialchars($_GET['erreur']), "error", "page_admin_utilisateurs");
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à supprimer un utilisateur
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        supprimer_utilisateur($bdd, $id_utilisateur);
        header("Location: page_admin_utilisateurs.php?suppression=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à modifier les informations d'un utilisateur
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $resultat = modifier_utilisateur($bdd, intval($_POST['id']));
    if ($resultat === true) {
        header("Location: page_admin_utilisateurs.php?modification=ok");
    } else {
        header("Location: page_admin_utilisateurs.php?erreur=" . urlencode($resultat));
    }
    exit;
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à accepter la création d'un compte
if (isset($_GET['action']) && $_GET['action'] === 'accepter') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        accepter_utilisateur($bdd, $id_utilisateur);
        header("Location: page_admin_utilisateurs.php?accept=ok");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8"/>
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
    // Affiche la popup si elle existe
    echo $message;
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