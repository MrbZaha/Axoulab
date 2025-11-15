<?php
// Démarre la session si besoin
session_start();

// Inclure le fichier de fonctions
include '../back_php/fonctions_site_web.php';

// Connexion à la base
$bdd = connectBDD();

// Variable pour afficher les messages
$message = "";

// ======================= TRAITEMENT DU FORMULAIRE =======================
if (isset($_POST["Nom"], $_POST["Prenom"], $_POST["date_de_naissance"], $_POST["etat"], $_POST["email"], $_POST["mdp"])) {

    // Récupération des données et nettoyage
    $nom = trim($_POST["Nom"]);
    $prenom = trim($_POST["Prenom"]);
    $datedenaissance = trim($_POST["date_de_naissance"]);
    $utilisateur = $_POST["etat"];
    $email = trim($_POST["email"]);
    $mdp = $_POST["mdp"];

    // ======================= VALIDATION EMAIL =======================
    if (!verifier_email_axoulab($email)) {
        $message = "<p style='color:red;'>L'adresse email doit être au format prenom.nom@axoulab.fr.</p>";
    } 
    else {

        // ======================= VALIDATION MOT DE PASSE =======================
        $erreurs_mdp = verifier_mdp($mdp);
        if (!empty($erreurs_mdp)) {
            $message = "<p style='color:red;'>Le mot de passe doit contenir : " . implode(", ", $erreurs_mdp) . ".</p>";
        } 
        else {

            // ======================= VERIFICATION EMAIL EXISTANT =======================
            if (email_existe($bdd, $email)) {
                $message = "<p style='color:red;'>Cet email a déjà un compte.</p>";
            } 
            else {

                // ======================= HACHAGE DU MOT DE PASSE =======================
                $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);

                // ======================= INSERTION DANS LA BASE =======================
                if (inserer_utilisateur($bdd, $nom, $prenom, $datedenaissance, $utilisateur, $email, $mdp_hash)) {

                    // ======================= NOTIFICATION ADMIN =======================
                    envoyer_notification_admin($email, $nom, $prenom);

                    $message = "<p style='color:green;'>Compte créé avec succès !</p>";
                } 
                else {
                    $message = "<p style='color:red;'>Erreur lors de l'inscription. Veuillez réessayer.</p>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page d'inscription</title>
    <link rel="stylesheet" href="../css/page_d'inscription_style.css">
</head>
<body>
   <form action="" method="post">
       <p> 
        <?php 
        // Affiche le message si présent
        if (!empty($message)) echo $message;
        ?>
        <div class="signup-box"> <!-- Bloc contenant tout le formulaire -->
            <h2 class="titre">Créer ton compte</h2>
            <div class="colonne1">
                Nom <input type="text" name="Nom" required /> <br/>
                Prénom <input type="text" name="Prenom" required /> <br/>
                Date de naissance <input type="date" name="date_de_naissance" required/> <br/>
                Email <input type="text" name="email" required /> <br/>
            </div>
            <div class="colonne2">
                Numéro de téléphone (facultatif) <input type="text" name="telephone"/> <br/>
                <div class="user-type">
                    Je suis un(e) : <br/>
                    <input type="radio" name="etat" value="chercheur" id="chercheur" required/>
                    <label for="chercheur"> Chercheur(se)</label> <br/>
                    <input type="radio" name="etat" value="etudiant" id="etudiant"/>
                    <label for="etudiant"> Etudiant(e)</label> <br/>
                </div>
                Mot de passe <input type="password" name="mdp" required /> <br/>
                <input type="submit" value="Valider" />
            </div>
        </div>
       </p>
   </form>
</body>
</html>
