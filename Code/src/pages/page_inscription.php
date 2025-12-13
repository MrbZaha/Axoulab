<?php
// Démarre la session si besoin
session_start();

// Inclure les fichiers de fonctions
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_inscription.php';

// Connexion à la base
$bdd = connectBDD();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

// Variable pour afficher les messages
$message = "";
$messages = [];  // Tableau pour accumuler les messages d'erreur

// ======================= TRAITEMENT DU FORMULAIRE =======================
if (isset($_POST["Nom"], $_POST["Prenom"], $_POST["date_de_naissance"], $_POST["utilisateur"], $_POST["email"], $_POST["mdp1"], $_POST["mdp2"])) {

    // Récupération des données et nettoyage
    $nom = trim($_POST["Nom"]);
    $prenom = trim($_POST["Prenom"]);
    $datedenaissance = trim($_POST["date_de_naissance"]);
    $utilisateur = $_POST["utilisateur"];  // Utilisateur permet de savoir si c'est un étudiant ou professeur
    $email = trim($_POST["email"]);
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $mdp1 = $_POST["mdp1"];
    $mdp2 = $_POST["mdp2"];

    // ======================= VALIDATION DATE DE NAISSANCE =======================
    $date_naissance = DateTime::createFromFormat('Y-m-d', $datedenaissance);
    $today = new DateTime();

    // Si la date est invalide
    if (!$date_naissance) {
        $messages[] = "La date de naissance est invalide.";
    }
    // Si la date est dans le futur
    elseif ($date_naissance > $today) {
        $messages[] = "La date de naissance ne peut pas être dans le futur.";
    }
    // Si la personne a plus de 120 ans
    elseif ($date_naissance->diff($today)->y > 120) {
        $messages[] = "L'âge ne peut pas dépasser 120 ans.";
    }
    // Si la personne est trop jeune (moins de 18 ans)
    elseif ($date_naissance > $today->modify('-18 years')) {
        $messages[] = "L'âge minimum est de 18 ans.";
    }
    // Si la date est trop ancienne (avant 1900-01-01)
    elseif ($date_naissance < new DateTime('1900-01-01')) {
        $messages[] = "La date de naissance est trop ancienne.";
    }

    // ======================= VALIDATION EMAIL =======================
    if (!verifier_email_axoulab($email, $prenom, $nom)) {
        $messages[] = "L'adresse email doit être au format prenom.nom@axoulab.fr.";
    }

    // ======================= VALIDATION MOT DE PASSE =======================
    $erreurs_mdp = verifier_mdp($mdp1);
    if (!empty($erreurs_mdp)) {
        $messages[] = "Le mot de passe doit contenir : " . implode(", ", $erreurs_mdp) . ".";
    } elseif (!mot_de_passe_identique($mdp1, $mdp2)) {
        $messages[] = "Les mots de passe ne sont pas identiques.";
    }

    // ======================= VERIFICATION EMAIL EXISTANT =======================
    if (email_existe($bdd, $email)) {
        $messages[] = "Cet email a déjà un compte.";
    }

    // ======================= IF NO ERRORS, INSERT INTO DATABASE =======================
    if (empty($messages)) {
        // DÉTERMINATION ÉTAT UTILISATEUR
        $etat = ($utilisateur === "etudiant") ? 1 : 2;  // 1 = etudiant, 2 = chercheur

        // HACHAGE DU MOT DE PASSE
        $mdp_hash = password_hash($mdp1, PASSWORD_DEFAULT);

        // INSERTION DANS LA BASE
        if (inserer_utilisateur($bdd, $nom, $prenom, $datedenaissance, $etat, $email, $mdp_hash)) {
            // Message de succès + redirection
            $message = "<p style='color:green;'>Compte créé avec succès ! Vous allez être redirigé vers la page d'accueil </p>";
            // Redirection après 2 secondes
            echo '<meta http-equiv="refresh" content="2;url=Main_page.php">';
            $_POST = array(); // Vider $_POST pour ne pas réafficher les valeurs
        } else {
            $messages[] = "Erreur lors de l'inscription. Veuillez réessayer.";
        }
    } else {
        // Si des erreurs existent, afficher les messages d'erreur
        $message = "<p style='color:red;'>" . implode("<br>", $messages) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page d'inscription</title>
    <!-- Permet d'uniformiser le style sur tous les navigateurs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_d'inscription.css">
</head>
<body>
   <form action="" method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
