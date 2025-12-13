<?php
// Inclusion du fichier de fonctions
include_once '../back_php/fonctions_site_web.php';

// Connexion à la base
$bdd = connectBDD();


$message = "";
$type_message = "";

// Vérifie si le formulaire a été soumis
if (!empty($_POST["email"])) {

    $email = trim($_POST["email"]);

    // Vérifie si l'email existe en base via la fonction
    if (email_existe($bdd, $email)) {

        $message = "Un email vous a été envoyé avec un lien de réinitialisation";
        $type_message = "success";

    } else {

        $message = "Aucun compte n'est associé à cet email.";
        $type_message = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Création de la page de connexion-->
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe </title>
        <link rel="stylesheet" href="../css/page_mdp_oublie.css">

</head>

<body>
    <div class="container">
        <h1>Mot de passe oublié ?</h1>
        <p class="subtitle">Entrez votre adresse email pour envoyer un mail de  réinitialisation</p>
        <?php
        // Afficher le message s'il existe
        if (!empty($message)) {
            echo "<div class='message $type_message'>$message</div>";
        }
        ?>
       
        <!-- Formulaire de demande de réinitialisation -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Adresse email :</label>
                <input type="email" id="email" name="email" required placeholder="votre.email@exemple.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <button type="submit" class="btn-submit">
                Envoyer le mail de réinitialisation
            </button>
        </form>
        
        <!-- Liens de navigation -->
        <div class="links">
            <a href="Main_page.php"> Retour à la connexion</a>
        </div>
    </div>
</body>
</html> 

