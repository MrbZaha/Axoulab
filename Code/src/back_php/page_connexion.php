<?php
// ======================= PAGE DE CONNEXION =======================

// Démarre la session pour garder les informations de l'utilisateur
session_start();

// Inclure le fichier de fonctions réutilisables
include 'fonctions_site_web.php';

// ======================= CONNEXION À LA BASE =======================
// Utilise la fonction connectBDD() définie dans fonctions_site_web.php
$bdd = connectBDD();

// ======================= INITIALISATION =======================
$erreur = "";                // Message d'erreur pour l'affichage
$message_succes = "";        // Message de succès pour l'affichage
$tentatives_max = 5;         // Nombre maximum de tentatives
$delai_blocage = 60;         // Durée du blocage en secondes (1 minute pour test)

// Initialiser le compteur de tentatives si nécessaire
if (!isset($_SESSION['tentatives_connexion'])) {
    $_SESSION['tentatives_connexion'] = 0;
    $_SESSION['dernier_essai'] = time();
}

// ======================= VÉRIFICATION BLOQUAGE =======================
$compte_bloque = false;
$temps_restant_minutes = 0;

if ($_SESSION['tentatives_connexion'] >= $tentatives_max) {
    $temps_ecoule = time() - $_SESSION['dernier_essai'];
    if ($temps_ecoule < $delai_blocage) {
        // Compte encore bloqué
        $compte_bloque = true;
        $temps_restant_secondes = $delai_blocage - $temps_ecoule;
        $temps_restant_minutes = ceil($temps_restant_secondes / 60);
        $erreur = "Trop de tentatives échouées. Votre compte est temporairement bloqué.<br>";
        $erreur .= "Veuillez réessayer dans <strong>$temps_restant_minutes minute(s)</strong>.";
    } else {
        // Le délai est écoulé, réinitialiser le compteur
        $_SESSION['tentatives_connexion'] = 0;
        $_SESSION['dernier_essai'] = time();
        $compte_bloque = false;
    }
}

// ======================= TRAITEMENT DU FORMULAIRE =======================
if (isset($_POST["email"], $_POST["mdp"]) && !$compte_bloque) {
    $email = trim($_POST["email"]);
    $mdp = $_POST["mdp"];

    // Vérification : email existe et mot de passe correct
    if (connexion_valide($bdd, $email, $mdp)) {
        // Connexion réussie
        $_SESSION["email"] = $email;             // Stocke l'email dans la session
        $_SESSION['tentatives_connexion'] = 0;   // Réinitialise le compteur de tentatives

        // Récupération de l'ID du compte
        $_SESSION['id_compte'] = recuperer_id_compte($bdd, $email);

        // Message succès pour l'affichage dans HTML
        $message_succes = "Tu es identifié !"; // Pour test, remplacer par redirection plus tard

    } else {
        // Si connexion échoue : email inconnu ou mot de passe incorrect
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
} elseif (!$compte_bloque && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si le formulaire est soumis mais que les champs sont vides
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
    <link rel="stylesheet" href="page_connexion_style.css">
</head>
<body>

    <!-- Affichage du message si le compte est bloqué -->
    <?php if ($compte_bloque) : ?>
        <div class='compte-bloque'>
            Votre compte est temporairement verrouillé.<br>
            Temps restant : <strong><?php echo $temps_restant_minutes; ?> minute(s)</strong>
        </div>
    <?php endif; ?>

    <!-- Affichage du message de succès -->
    <?php if (!empty($message_succes)) : ?>
        <div class='message-succes'>
            <?php echo $message_succes; ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire de connexion -->
    <form action="" method="post">
        <div class="login-box">
            Email <input type="text" name="email" /> <br/>
            Mot de passe <input type="password" name="mdp" /> <br/>
            <input type="submit" value="Se connecter" />

            <!-- Affichage du message d'erreur -->
            <?php if (!empty($erreur) && !$compte_bloque): ?>
                <div class='message-erreur'><?php echo $erreur; ?></div>
            <?php endif; ?>

            <div class="liens">
                <a href="page_mdp_oublie.php">Mot de passe oublié?</a>
                <a href="page_d'inscription.php">Créer un compte</a>
            </div>
        </div>
    </form>

    <!-- Script pour rafraîchir la page si le blocage expire -->
    <?php if ($compte_bloque): ?>
        <script>
            setTimeout(function() {
                location.reload();
            }, <?php echo ($temps_restant_minutes * 60 * 1000); ?>);
        </script>
    <?php endif; ?>
</body>
</html>
