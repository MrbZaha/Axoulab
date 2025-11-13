<?php 
//Connexion à la base de données
try {
$bdd= new PDO("mysql:host=localhost; dbname=projet_site_web; charset=utf8","caca","juliette74");
}
catch (Exception $e) {
    die("Erreur : ". $e->getMessage());
}

//echo $_POST["Nom"], $_POST["email"], $_POST["mdp"], $_POST["date_de_naissance"], $_POST["Prenom"], $_POST["telephone"], $_POST["etat"];
$message = "";
// Vérifie si le formulaire a bien été soumis et récupère chaque données pour les mettres dans une variables 
if (isset($_POST["Nom"], $_POST["email"], $_POST["mdp"], $_POST["date_de_naissance"], $_POST["Prenom"], $_POST["etat"])){  //je pensais que etat dans la base de données etait chercher ou etudiant mais je ne suis pas sure
    $nom=trim($_POST["Nom"]);
    $prenom=trim($_POST["Prenom"]);
    $datedenaissance=trim($_POST["date_de_naissance"]);
    $utilisateur=$_POST["etat"];
    $email=trim($_POST["email"]);
    $mdp=($_POST["mdp"]);

// Validation de l'email au format prenom.nom@axoulab.fr
if (!preg_match('/^[a-zA-Z]+\.[a-zA-Z]+@axoulab\.fr$/', $email)) {
    $message = "<p style='color:red;'>L'adresse email doit être au format prenom.nom@axoulab.fr.</p>";
}

// Validation du mot de passe 
   else { $erreurs_mdp = [];
    
    if (strlen($mdp) < 8) {
        $erreurs_mdp[] = "au moins 8 caractères";
    }
    if (!preg_match('/[A-Z]/', $mdp)) {
        $erreurs_mdp[] = "au moins une majuscule";
    }
    if (!preg_match('/[a-z]/', $mdp)) {
        $erreurs_mdp[] = "au moins une minuscule";
    }
    if (!preg_match('/[0-9]/', $mdp)) {
        $erreurs_mdp[] = "au moins un chiffre";
    }
    if (!preg_match('/[\W_]/', $mdp)) {
        $erreurs_mdp[] = "au moins un caractère spécial (!@#$%^&*...)";
    }
    
    if (!empty($erreurs_mdp)) {
        $message = "<p style='color:red;'>Le mot de passe doit contenir : " . implode(", ", $erreurs_mdp) . ".</p>";
    } else {
//Sécurise le mdp dans la base de donées, chiffre le mdp avant de l'enregistré dans la base
    $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT); 

// Vérifie si l'email existe déjà car si déja existant le compte est déja créer
    $verif = $bdd->prepare("SELECT * FROM table_compte WHERE email = ?");
    $verif->execute([$email]);

//Vérifie si l'email existe déjà
    if ($verif->rowCount() > 0) {
        echo "Cet email à déjà un compte.";
    } else {
        // Insertion dans la base
        $sql = $bdd->prepare("INSERT INTO table_compte (Nom, Prenom, date_de_naissance, etat, email, mdp) VALUES (?, ?,?, ?, ?, ?)"); //peut etre chnager ce qu'est l'état
        $sql->execute([$nom,$prenom,$datedenaissance,$utilisateur, $email, $mdp_hash]); 
        echo "Compte créé avec succès !";
    }
    }
}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page d'inscritpion</title>
    <link rel="stylesheet" href="../css/page_d'inscription_style.css">
</head>
<body>
   <form action ="" method="post"> <!-- création du formulaire-->
<p> 
    <?php 
        //  Affiche le message s'il existe
        if (!empty($message)) {
            echo $message;
        }
        ?>
    <div class ="signup-box"> <!--permet d'avoir le bloc  avec tout le formulaire d'inscription-->
        <h2 class="titre">Créer ton compte</h2>
        <div class="colonne1"> <!-- je crée deux colonne pour afficher les données cote à cote pour que ça soit jolie-->
             Nom <input type = "text" name="Nom" required /> <br/> <!-- encandrant pour saisir le nom-->
            Prénom <input type="text" name="Prenom" required /> <br/> <!-- encandrant pour saisir le prenom-->
            Date de naissance <input type="date" name="date_de_naissance" required/> <br/> <!-- encandrant pour saisir la date de naissance-->
            Email <input type = "text" name="email" required /> <br/> <!-- encandrant pour saisir le mail-->
        </div>
        <div class="colonne2">  <!-- je crée deux colonne pour afficher les données cote à cote pour que ça soit jolie-->
            Numéro de téléphone (facultatif) <input type="text" name="telephone"/> <br/> <!-- encandrant pour saisir le numero de télphone (facultatif)-->
        <div class ="user-type">
            Je suis un(e) : </br>
            <input type="radio" name="etat" value="chercheur" id="chercheur" required/> <!-- bouton permet de sélectionner quel type d'utilisateur je suis-->
            <label for="chercheur"> Chercheur(se)</label> <br/>
            <input type="radio" name="etat" value="etudiant" id="etudiant"/> <!-- bouton permet de sélectionner quel type d'utilisateur je suis-->
            <label for="chercheur"> Etudiant(e)</label> <br/>
        </div>
            Mot de passe <input type = "password" name="mdp" required /> <br/> <!-- encandrant pour saisir le mot de passe-->
            <input type = "submit" value="Valider" /> <!-- envoie le formulaire pour enregistrer les infos dans la base de données-->
        </div>
</div>
</p>
</form>
</body>
</html> 
