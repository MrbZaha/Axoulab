<?php
// Démarre la session pour garder les infos de l'utilisateur
session_start();

// Inclure le fichier de fonctions
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_connexion.php';

// Connexion à la base de données

$bdd = connectBDD();

$erreur = "";

// PARAMÈTRES DE SÉCURITÉ : LIMITE DE TENTATIVES 
$tentatives_max = 5;       // Nombre maximum de tentatives autorisées
$delai_blocage = 300;        // Durée du blocage en secondes

// Initialiser le compteur de tentatives si nécessaire
if (!isset($_SESSION['tentatives_connexion'])) {
    $_SESSION['tentatives_connexion'] = 0;
    $_SESSION['dernier_essai'] = time();
}

// VÉRIFICATION : Le compte est-il bloqué ? 
$compte_bloque = false;
$temps_restant_minutes = 0;

// Si trop d'essai erroné ont lieu
if ($_SESSION['tentatives_connexion'] >= $tentatives_max) {
    $temps_ecoule = time() - $_SESSION['dernier_essai'];
    if ($temps_ecoule < $delai_blocage) {
        // Compte encore bloqué
        $compte_bloque = true;
        $temps_restant_secondes = $delai_blocage - $temps_ecoule;
        $temps_restant_minutes = ceil($temps_restant_secondes / 60);

        $erreur = afficher_popup("Trop de tentatives échouées.", "Veuillez réessayer dans $temps_restant_minutes minute(s).", "error", "page_connexion");
    } else {
        // Le délai est écoulé, réinitialiser
        $_SESSION['tentatives_connexion'] = 0;
        $_SESSION['dernier_essai'] = time();
        $compte_bloque = false;
    }
}

// Vérifie si le formulaire a été soumis
if (isset($_POST["email"], $_POST["mdp"]) && !$compte_bloque) {
    $email = trim($_POST["email"]);
    $mdp = trim($_POST["mdp"]);

    $verification = $bdd->prepare("SELECT * FROM compte WHERE email = ?");
    $verification->execute([$email]);

    if ($verification->rowCount() > 0) {
        $user = $verification->fetch();

        if (connexion_valide($bdd, $email, $mdp)) {
            $_SESSION["email"] = $email;
            $_SESSION["ID_compte"] = recuperer_id_compte($bdd, $email);
            $_SESSION['tentatives_connexion'] = 0;

            if (en_cours_validation($bdd, $email)) {
                header("Location: page_validation_compte.php");
                exit;
            }

            if (est_admin($bdd, $email)) {
                header("Location: page_admin.php");
            } else {
                header("Location: Main_page_connected.php");
            }
            exit;
        } else {
            // Mot de passe incorrect
            $_SESSION['tentatives_connexion']++;
            $_SESSION['dernier_essai'] = time();
            $tentatives_restantes = $tentatives_max - $_SESSION['tentatives_connexion'];
            $erreur = afficher_popup("Email ou mot de passe incorrect", "Il vous reste $tentatives_restantes tentative(s).", "error", "page_connexion");
         }
    } else {
        // Email inexistant
        $_SESSION['tentatives_connexion']++;
        $_SESSION['dernier_essai'] = time();
        $tentatives_restantes = $tentatives_max - $_SESSION['tentatives_connexion'];
        $erreur = afficher_popup("Email ou mot de passe incorrect", "Il vous reste $tentatives_restantes tentative(s).", "error", "page_connexion");
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Création de la page de connexion-->
    <meta charset="UTF-8">
    <title>Page de connexion</title>
        <link rel="stylesheet" href="../css/page_connexion.css">
    <link rel="stylesheet" href="../css/popup.css">

</head>
<body>

    <form action ="" method="post"> <!-- Envoie vers la page (qui est juste au-dessus) qui permet de récuperer les informations du l'utilisateur-->

    <div class ="login-box"> <!-- permet d'avoir le petit cadrant blanc autours-->

    Email <input type = "text" name="email" /> <br/>  <!-- Emplacement pour écrire le mail-->
    Mot de passe<input type = "password" name="mdp" /> <br/> <!-- Emplacement pour écrire le mot de passe -->
    <input type = "submit" value="Se connecter" /> <!-- permet d'envoyer le formulaire afin d'enregistrer les informatiosn -->
    
    <!-- affichage du message d'erreur : email ou mdp incorrect et le nombre de tentatives restantes-->
    <?php if (!empty($erreur) && !$compte_bloque): ?>
        <?php 
        // Affiche la popup si elle existe
        echo $erreur;
        ?>
    <?php endif; ?>

    <div class ="liens">
        <a href="page_mdp_oublie.php">Mot de passe oublié?</a> <!--permet de renvoyer vers la page du mot de passe oublié-->
        <a href="page_inscription.php">Créer un compte</a> <!--permet de renvoyer vers la page d'inscription'-->
    </div>

</div>
</form>

 <?php if ($compte_bloque): ?>
        <?php 
        // Affiche la popup si elle existe
        echo $erreur;
        ?>
    <?php endif; ?>
</body>
</html> 

