<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

////////////////////////////////////////////////////////////////////////////////
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// On vérifie si l'utilisateur a les droits pour accéder à cette page
if (est_admin($bdd, $_SESSION["email"])){
    // Le code peut poursuivre
}
else {
    // On change le layout de la page et on invite l'utilisateur à revenir sur la page précédente
    layout_erreur();
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à supprimer un utilisateur
// Si une action GET est reçue
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        supprimer_utilisateur($bdd, $id_utilisateur);

        // On recharge la page proprement (cela empêche de supprimer deux fois)
        header("Location: page_admin_utilisateurs.php?suppression=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à modifier les informations d'un utilisateur
// Si une action GET est reçue
if (isset($_GET['action']) && $_GET['action'] === 'modifier') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        // supprimer_experience($bdd, $id_experience);

        // On recharge la page proprement (cela empêche de supprimer deux fois)
        header("Location: page_admin_utilisateurs.php?modification=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à accepter la création d'un compte
// Si une action GET est reçue
if (isset($_GET['action']) && $_GET['action'] === 'accepter') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        accepter_utilisateur($bdd, $id_utilisateur);

        // On recharge la page proprement (cela empêche de supprimer deux fois)
        header("Location: page_admin_utilisateurs.php?accept=ok");
        exit;
    }
}



// =======================  Fonction pour récupérer l'ensemble des utilisateurs =======================
function get_utilisateurs($bdd) {
    $sql_utilisateurs = "
        SELECT ID_compte,
        Nom,
        Prenom,
        Date_de_naissance,
        Email,
        Mdp,
        Etat,
        validation
        FROM compte
    ";  
        $stmt = $bdd->prepare($sql_utilisateurs);
        $stmt->execute();

    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $utilisateurs;   
}

// =======================  Fonction pour afficher l'ensemble des utilisateurs =======================
function afficher_utilisateurs_pagines($utilisateurs, $page_actuelle, $items_par_page, $bdd) {
    // On récupère l'indice de la première expérience qui sera affichée
    $debut = ($page_actuelle - 1) * $items_par_page;
    $utilisateur_page = array_slice($utilisateurs, $debut, $items_par_page);
    
    ?>
    <div class="liste">
        <?php if (empty($utilisateur_page)): ?>
            <p class="no-experiences">Aucun utilisateur à afficher</p>
        <?php else:
            // On récupère l'ensemble des critères pour chacun des utilisateurs
            foreach ($utilisateur_page as $user):
                $id_utilisateur = htmlspecialchars($user['ID_compte']);
                $nom = htmlspecialchars($user['Nom']);
                $prenom = htmlspecialchars($user['Prenom']);  
                $date_naissance = htmlspecialchars($user['Date_de_naissance']);
                $email = htmlspecialchars($user['Email']);
                $mdp = htmlspecialchars($user['Mdp']);
                $etat = htmlspecialchars($user['Etat']);
                $validation = htmlspecialchars($user['validation']);
                ?>
                
                <div class="experience-header">
                    <h3><?= $nom ?> <?= $prenom?></h3>
                </div>

                <div class="experience-details">
                    <p><strong>Date :</strong> <?= $date_naissance ?></p>
                    <p><strong>Horaires :</strong> <?= $email ?>
                    <p><strong>mot de passe :</strong> <?= $mdp ?></p>
                    <p><strong>Statut :</strong> <?= get_etat($etat) ?></p>
                    <p><strong>État de validation :</strong> <?= $validation ?></p>
                    <!-- lance une fonction qui ajoute 3 boutons : acceptation, modification et suppression -->

                    <div class="right-section">
                        <div class="box">
                        <?php if (!$validation == 1) {
                            // Ajoute un bouton pour accepter l'utilisateur si nécessaire 
                            ?>
                            <a href="page_admin_utilisateurs.php?action=accepter&id=<?php echo $id_utilisateur; ?>"
                                class="btn btnBlanc"
                                onclick="event.stopPropagation();">
                                Accepter</a>
                            <?php } ?>
                            <a href="page_admin_utilisateurs.php?action=modifier&id=<?php echo $id_utilisateur; ?>"
                                class="btn btnBlanc"
                                onclick="event.stopPropagation();">
                                Modifier</a>
                            <a href="page_admin_utilisateurs.php?action=supprimer&id=<?php echo $id_utilisateur; ?>"
                                class="btn btnRouge"
                                onclick="event.stopPropagation();">
                                Supprimer</a>
                        </div>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>
    <?php



    $sql_utilisateurs = "
        SELECT ID_compte,
        Nom,
        Prenom,
        Date_de_naissance,
        Email,
        Mdp,
        Etat,
        validation
        FROM compte
    ";  
        $stmt = $bdd->prepare($sql_utilisateurs);
        $stmt->execute();

    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $utilisateurs;   
}


// On récupère la liste des utilisateurs
$utilisateurs = array_values(get_utilisateurs($bdd));

// On set la page que l'on observe
$items_par_page = 20;
$page = isset($_GET['pages']) ? max(1, (int)$_GET['page']) : 1;
$total_pages = create_page($utilisateurs, $items_par_page);

// Vérification que la page demandée existe
if ($page > $total_pages) $page = $total_pages;


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
        <title> Utilisateurs </title>

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
    <!-- Affichage des expériences une à une-->
        <section class="section-experiences">
            <h2>Utilisateurs (<?= count($utilisateurs) ?>)</h2>  <!--Titre affichant le nombre d'expérience-->
            <?php afficher_utilisateurs_pagines($utilisateurs, $page, $items_par_page, $bdd); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>
        <!-- À l'intérieur, avec aspect spécifique et boutons -->
    </div>


    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>

</html>