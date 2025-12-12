<?php
// Démarre la session si besoin
session_start();

// Inclure le fichier de fonctions
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_inscription.php';


// Connexion à la base
$bdd = connectBDD();

// Variable pour afficher les messages
$message = "";

// ======================= TRAITEMENT DU FORMULAIRE =======================
// Note : on vérifie mdp1 et mdp2 (champs obligatoires)
if (isset($_POST["Nom"], $_POST["Prenom"], $_POST["date_de_naissance"], $_POST["utilisateur"], $_POST["email"], $_POST["mdp1"], $_POST["mdp2"])) {

    // Récupération des données et nettoyage
    $nom = trim($_POST["Nom"]);
    $prenom = trim($_POST["Prenom"]);
    $datedenaissance = trim($_POST["date_de_naissance"]);
    $utilisateur = $_POST["utilisateur"]; # utilisateur permet de savoir si c'est un étudiant ou professeur
    $email = trim($_POST["email"]);
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $mdp1 = $_POST["mdp1"];
    $mdp2 = $_POST["mdp2"];


    if ($utilisateur === "etudiant") {
        $etat = 1;   // etudiant
    } elseif ($utilisateur === "chercheur") {
        $etat = 2;   // chercheur
    }

    // ======================= VALIDATION EMAIL =======================
    if (!verifier_email_axoulab($email,$prenom,$nom)) {
        $message = "<p style='color:red;'>L'adresse email doit être au format prenom.nom@axoulab.fr.</p>";
    } else {

        // ======================= VALIDATION MOT DE PASSE =======================
        $erreurs_mdp = verifier_mdp($mdp1);
        if (!empty($erreurs_mdp)) {
            $message = "<p style='color:red;'>Le mot de passe doit contenir : " . implode(", ", $erreurs_mdp) . ".</p>";
        } else {

            // Vérifier que les deux mots de passe sont identiques
            if (!mot_de_passe_identique($mdp1, $mdp2)) {
                $message = "<p style='color:red;'>Les mots de passe ne sont pas identiques.</p>";
            } else {

                // ======================= VERIFICATION EMAIL EXISTANT =======================
                if (email_existe($bdd, $email)) {
                    $message = "<p style='color:red;'>Cet email a déjà un compte.</p>";
                } else {

                    // ======================= HACHAGE DU MOT DE PASSE =======================
                    # Permet de garantir plus de sécurité au niveau de la base de données
                    $mdp_hash = password_hash($mdp1, PASSWORD_DEFAULT);

                    // ======================= INSERTION DANS LA BASE =======================
                    if (inserer_utilisateur($bdd, $nom, $prenom, $datedenaissance, $etat, $email, $mdp_hash)) {

                        // ======================= NOTIFICATION ADMIN =======================
                        //envoyer_notification_admin($email, $nom, $prenom);

                        // Message de succès + redirection
                        $message = "<p style='color:green;'>Compte créé avec succès ! Vous allez être redirigé vers la page d'accueil </p>";
                        // permet de rediriger vers la page d'accueil au bout d'un certain temps (ici 2s)
                        echo '<meta http-equiv="refresh" content="2;url=Main_page.php">';

                        // Vider $_POST pour ne pas réafficher les valeurs
                        $_POST = array();

                    } else {
                        $message = "<p style='color:red;'>Erreur lors de l'inscription. Veuillez réessayer.</p>";
                    }
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
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_d'inscription.css">
</head>
<body>
   <form action="" method="post" autocomplete="off">
       <p> 
        <?php 
        // Affiche le message si présent
        if (!empty($message)) echo $message;
        ?>
        <div class="signup-box"> <!-- Bloc contenant tout le formulaire -->
            <h2 class="titre">Créer ton compte</h2>
            <div class="colonne1">
                Nom 
                <input type="text" name="Nom" required
                       value="<?= htmlspecialchars($_POST['Nom'] ?? '') ?>" /> <br/>

                Prénom 
                <input type="text" name="Prenom" required
                       value="<?= htmlspecialchars($_POST['Prenom'] ?? '') ?>" /> <br/>

                Date de naissance 
                <input type="date" name="date_de_naissance" required
                       value="<?= htmlspecialchars($_POST['date_de_naissance'] ?? '') ?>" /> <br/>

                Email 
                <input type="text" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" /> <br/>
            </div>
            <div class="colonne2">
                <div class="user-type">
                    Je suis un(e) : <br/>
                    <input type="radio" name="utilisateur" value="chercheur" id="chercheur" required
                        <?= (($_POST['utilisateur'] ?? '') === 'chercheur') ? 'checked' : '' ?>/>
                    <label for="chercheur"> Chercheur(se)</label> <br/>

                    <input type="radio" name="utilisateur" value="etudiant" id="etudiant"
                        <?= (($_POST['utilisateur'] ?? '') === 'etudiant') ? 'checked' : '' ?>/>
                    <label for="etudiant"> Etudiant(e)</label> <br/>
                </div>

                Mot de passe <input type="password" name="mdp1" required /> <br/>
                Confirmer le mot de passe <input type="password" name="mdp2" required /> <br/>
                <input type="submit" value="Valider" />
            </div>
        </div>
       </p>
   </form>
</body>
</html>
