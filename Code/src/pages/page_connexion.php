<?php
// Démarre la session pour garder les infos de l'utilisateur
session_start();

include_once '../back_php/fonctions_site_web.php';


// Connexion à la base de données
try {
    $bdd = new PDO("mysql:host=localhost;dbname=projet_site_web;charset=utf8", "caca", "juliette74");
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}


$erreur = "";

// PARAMÈTRES DE SÉCURITÉ : LIMITE DE TENTATIVES 
$tentatives_max = 5;       // Nombre maximum de tentatives autorisées
$delai_blocage = 60;   // Durée du blocage en secondes (1 minutes) mettre plus quand plus de test

// Initialiser le compteur de tentatives si nécessaire
if (!isset($_SESSION['tentatives_connexion'])) {
    $_SESSION['tentatives_connexion'] = 0;
    $_SESSION['dernier_essai'] = time();
}

//  VÉRIFICATION : Le compte est-il bloqué ? 
$compte_bloque = false;
$temps_restant_minutes = 0;

if ($_SESSION['tentatives_connexion'] >= $tentatives_max) {
    $temps_ecoule = time() - $_SESSION['dernier_essai']; //temps ecoule depuis la derniere tentative
    
    if ($temps_ecoule < $delai_blocage) {
        // Compte encore bloqué
        $compte_bloque = true;
        $temps_restant_secondes = $delai_blocage - $temps_ecoule;
        $temps_restant_minutes = ceil($temps_restant_secondes / 60);
        
        $erreur = "Trop de tentatives échouées. Votre compte est temporairement bloqué.<br>";
        $erreur .= "Veuillez réessayer dans <strong>$temps_restant_minutes minute(s)</strong>.";
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

    // Prépare la requête pour vérifier l'utilisateur
    $verification = $bdd->prepare("SELECT * FROM table_compte WHERE email = ?");
    $verification->execute([$email]);

// Variable pour savoir si la connexion a réussi
    $connexion_reussie = false;

    // Si l'utilisateur existe
    if ($verification->rowCount() > 0) {
        $user = $verification->fetch();

        // Vérifie le mot de passe
        if (password_verify($mdp, $user["mdp"])) { //compare le mdp haché stocké avec celui saisi
            $_SESSION["email"] = $email;
            $_SESSION["ID_compte"]=recuperer_id_compte($bdd, $email);
            $_SESSION['tentatives_connexion'] = 0;

            // Redirection vers la page après connexion
            header("Location: Main_page_connected.php"); 
            exit; // Arrêter le script après la redirection
        } 
    }
        if (!$connexion_reussie) {
               // echo "<p>Mot de passe incorrect</p>";
                $_SESSION['tentatives_connexion']++;
                $_SESSION['dernier_essai'] = time();

                $tentatives_restantes = $tentatives_max - $_SESSION['tentatives_connexion'];

                if ($tentatives_restantes > 0) {
                    $erreur = "Email ou mot de passe incorrect.<br>";
                    $erreur .= "<small>Il vous reste <strong>$tentatives_restantes tentative(s)</strong>.</small>";
                } else {
                    $erreur = "<small>Trop de tentatives échouées. Votre compte est bloqué pour <strong>15 minutes</strong></small>.";
                }
        }
    } /*else {
        echo "<p>Identifiant inconnu</p>";
    }*/else if (!$compte_bloque && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['tentatives_connexion']++;
        $_SESSION['dernier_essai'] = time();
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Création de la page de connexion-->
    <meta charset="UTF-8">
    <title>Page de connexion</title>
    <link rel="stylesheet" href="../css/page_connexion_style.css">

</head>
<body>
    <!--affichage du message si le compte est bloqué-->
    <?php if ($compte_bloque) : ?>
        <div class='compte-bloque'>;
        Votre compte est temporairement verrouillé.<br>;
        Temps restant : <strong><?php echo $temps_restant_minutes; ?> minute(s)</strong>
        </div>
        <?php endif; ?>

    <form action ="" method="post"> <!-- Envoie vers la page (qui est juste au-dessus) qui permet de récuperer les informations du l'utilisateur-->

    <div class ="login-box"> <!-- permet d'avoir le petit cadrant blanc autours-->

    Email <input type = "text" name="email" /> <br/>  <!-- Emplacement pour écrire le mail-->
    Mot de passe<input type = "password" name="mdp" /> <br/> <!-- Emplacement pour écrire le mot de passe -->
    <input type = "submit" value="Se connecter" /> <!-- permet d'envoyer le formulaire afin d'enregistrer les informatiosn -->
    
    <!-- affihcage du message d'erreur : email ou mdp incorrect et le nombre de tentatives restante-->
    <?php if (!empty($erreur) && !$compte_bloque): ?>
        <div class='message-erreur'><?php echo $erreur; ?></div>
    <?php endif; ?>

    <div class ="liens">
        <a href="page_mdp_oublie.php">Mot de passe oublié?</a> <!--permet de renvoyer vers la page du mot de passe oublié-->
        <a href="page_d'inscription.php">Créer un compte</a> <!--permet de renvoyer vers la page d'inscription'-->
    </div>

</div>
</form>

 <?php if ($compte_bloque): ?>
    <script>
        // Rafraîchir automatiquement la page quand le blocage expire
        setTimeout(function() {
            location.reload();
        }, <?php echo ($temps_restant_minutes * 60 * 1000); ?>);
    </script>
    <?php endif; ?>
</body>
</html> 

