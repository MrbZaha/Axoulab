<?php

session_start();
    // Connexion à la base de données
try {
    $bdd = new PDO("mysql:host=localhost;dbname=projet_site_web;charset=utf8", "caca", "juliette74");
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Vérifie si le formulaire a été soumis
if (isset($_POST["email"])) {
    $email = trim($_POST["email"]);

    
    // Prépare la requête pour vérifier l'utilisateur
    $verification = $bdd->prepare("SELECT * FROM table_compte WHERE email = ?");
    $verification->execute([$email]);

    // Si l'utilisateur existe
    if ($verification->rowCount() > 0) {
        $token = bin2hex(random_bytes(32)); // permet de créer le token unique sans exposer le mail ou le mdp 
        //random_bytes(32) → crée 32 octets aléatoires.
        //bin2hex(...) → convertit ces octets en une chaîne hexadécimale de 64 caractères.
        
        // Définir une date d'expiration (1 heure à partir de maintenant)
        $expiration = date("Y-m-d H:i:s", strtotime('+1 hour'));
        
        // Enregistrer le token dans la base de données
        
        $requete_token = $bdd->prepare("INSERT INTO password_reset (email, token, expiration) VALUES (?, ?, ?)");
        if ($requete_token->execute([$email, $token, $expiration])) {
   
        

            // Créer le lien de réinitialisation
            // IMPORTANT : Remplacez par le bon chemin de votre projet
            $lien_reset = "http://localhost/projet_site_web/reset_mdp.php?token=" . $token;
            
            // Message de succès
            $message = "Lien de réinitialisation généré avec succès ! Copiez le lien ci-dessous :";
            $type_message = "success";
            
        } else {
            $message = "Erreur lors de la création du lien de réinitialisation.";
            $type_message = "error";
        }
        
    } else {
        // Email non trouvé
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
    <link rel="stylesheet" href="page_mdp_oublie_style.css">

</head>

<body>
    <div class="container">
        <h1>Mot de passe oublié ?</h1>
        <p class="subtitle">Entrez votre adresse email pour générer un lien de réinitialisation</p>
        
        <?php
        // Afficher le message s'il existe
        if (!empty($message)) {
            echo "<div class='message $type_message'>$message</div>";
        }
        
        // Afficher le lien de réinitialisation s'il existe
        if (!empty($lien_reset)) {
            echo "
            <div class='reset-link-box'>
                <h3> Votre lien de réinitialisation</h3>
                <div class='link-display' id='resetLink'>$lien_reset</div>
                <p class='info-text'> Ce lien expire dans 1 heure</p>
                <p class='info-text'> Copiez-le dans votre navigateur</p>
            </div>
            ";
        }
        ?>
        
        <!-- Formulaire de demande de réinitialisation -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Adresse email :</label>
                <input type="email" id="email" name="email" required placeholder="votre.email@exemple.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <button type="submit" class="btn-submit">
                Générer le lien de réinitialisation
            </button>
        </form>
        
        <!-- Liens de navigation -->
        <div class="links">
            <a href="connexion.php"> Retour à la connexion</a>
        </div>
    </div>
</body>
</html> 

