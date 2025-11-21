<?php
session_start();
try {
    $bdd = new PDO("mysql:host=localhost;dbname=projet_site_web;charset=utf8", "caca", "juliette74");
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Si le formulaire est soumis pour changer le mot de passe
if (isset($_POST['token'], $_POST['new_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];

    // Vérifie que le token est valide
    $stmt = $bdd->prepare("SELECT email FROM password_reset WHERE token = ? AND expiration > NOW()");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        $email = $stmt->fetch()['email'];

        // Met à jour le mot de passe 
        $update = $bdd->prepare("UPDATE compte SET mdp = ? WHERE email = ?");
        $update->execute([$new_password, $email]);

        // Supprime le token
        $delete = $bdd->prepare("DELETE FROM password_reset WHERE token = ?");
        $delete->execute([$token]);

        $message = "Mot de passe réinitialisé avec succès !";
    } else {
        $message = "Lien invalide ou expiré.";
    }
}

// Si on arrive avec un token dans l'URL
elseif (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $bdd->prepare("SELECT * FROM password_reset WHERE token = ? AND expiration > NOW()");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Réinitialiser le mot de passe</title>
            <link rel="stylesheet" href="page_reset_mdp.css">
        </head>
        <body>
            <h2>Réinitialisation du mot de passe</h2>
            <form method="POST">
                <div classe ="container">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <label for="new_password">Nouveau mot de passe :</label>
                <input type="password" name="new_password" required>
                <button type="submit">Réinitialiser</button>
                </div>
            </form>
        </body>
        </html>
        <?php
        exit();
    } else {
        $message = "Lien invalide ou expiré.";
    }
} else {
    $message = "Aucun token fourni.";
}

// Affiche un message si besoin
if (isset($message)) {
    echo "<p>$message</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Création de la page de connexion-->
    <meta charset="UTF-8">
    <title>réinitialisation du mot de passe </title>
    <div class ="liens">
        <a href="page_connexion.php">Connecte-toi?</a> <!--permet de renvoyer vers la page de connexion-->
</div>
</html> 