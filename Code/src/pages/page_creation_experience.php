<?php
session_start();
include_once "../back_php/fonctions_site_web.php";
$bdd = connectBDD();

// ======================= VERIFIER TAILLES DES CHAMPS =======================
function verifier_champs_experience($nom_experience, $description) {
    $erreurs = [];

    if (strlen($nom_experience) < 3 || strlen($nom_experience) > 100) {
        $erreurs[] = "Le nom de l'experience doit contenir entre 3 et 100 caractères."; 
    }

    if (strlen($description) < 10 || strlen($description) > 2000) {
        $erreurs[] = "La description doit contenir entre 10 et 2000 caractères.";
    }

    return $erreurs;
}

// ======================= INSERER UNE NOUVELLE EXPERIENCE =======================
function creer_experience($bdd, $nom_experience, $validation, $description, $date_reservation, $heure_debut, $heure_fin, $statut_experiecnce = 0) {
    $sql = $bdd->prepare("
        INSERT INTO experience (Nom, Validation, Description, Date_de_reservation, Heure_debut, Heure_fin, Statut_experience)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    return $sql->execute([$nom_experience, $validation, $description, $date_reservation, $heure_debut, $heure_fin, $statut_experiecnce = 0]);
}

// ======================= AJOUTER PARTICIPANTS =======================
function ajouter_participants($bdd, $id_experience, $id_experimentateurs) {
    $sql = $bdd->prepare("
        INSERT INTO experience_experimentataire (Id_experience, Id_compte)
        VALUES (?, ?)
    ");

    foreach ($id_experimentateurs as $id_compte) {
        $sql->execute([$id_projet, $id_compte]);
    }
}

$message = "";

if (isset($_POST["nom_experience"], $_POST["validation"], 
          $_POST["description"], $_POST["date_reservation"], 
          $_POST["heure_debut"], $_POST["heure_fin"], $_POST["statut_experience"])) {
    $nom_experience = trim($_POST["nom_experience"]);
    $validation = $_POST["validation"];
    $description = trim($_POST["description"]);
    $date_reservation = $_POST["date_reservation"];
    $heure_debut = $_POST["heure_debut"];
    $heure_fin = $_POST["heure_fin"];
    $statut_experiecnce = $_POST["statut_experience"];
    
    // Gestion des tableaux pour participants
    $gestionnaires = isset($_POST["gestionnaires"]) ? (array)$_POST["gestionnaires"] : [];
    $collaborateurs = isset($_POST["collaborateurs"]) ? (array)$_POST["collaborateurs"] : [];

    // Vérifier tailles des champs
    $erreurs = verifier_champs_projet($nom_projet, $description);
    if (!empty($erreurs)) {
        $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
    } else {
        // Enregistrer le projet
        if (creer_projet($bdd, $nom_projet, $description, $confidentialite)) {
            $id_projet = $bdd->lastInsertId();
            
            // Enregistrer les participants
            ajouter_participants($bdd, $id_projet, $gestionnaires, $collaborateurs);
            
            $message = "<p style='color:green;'>Projet créé avec succès!</p>";
        } else {
            $message = "<p style='color:red;'>Erreur lors de la création du projet.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page de création d'expérience</title>
    <link rel="stylesheet" href="../css/page_creation_experience.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
</head>
<?php afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);?>
<body>
    <div class="project-box">
        <h2>Créer une expérience</h2>

        <?php
        // Affiche le message si présent
        if (!empty($message)) echo $message;
        ?>

        <form action="" method="post" autocomplete="off">
            <label for="nom_experience">Nom de l'expérience :</label>
            <input type="text" id="nom_experience" name="nom_experience" value="<?= htmlspecialchars($_POST['nom_experience'] ?? '') ?>">

            <label for="description">Description :</label>
            <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

            <label for="experimentateur">Expérimentateur :</label>
            <input type="text" id="experimentateur" name="experimentateur" value="<?= htmlspecialchars($_POST['experimentateur']?? '') ?>">

            <input type="submit" value="Créer l'expérience">
        </form>
    </div>
</body>
</html>