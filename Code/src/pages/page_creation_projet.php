<?php
session_start();
include_once "../back_php/fonctions_site_web.php";
$bdd = connectBDD();

$message = "";

if (isset($_POST["nom_projet"],$_POST["description"], $_POST["confidentialite"],$_POST["gestionnaires[]"], $_POST["collaborateurs"])){
    $nom_projet=trim($_POST["nom_projet"]);
    $description=trim($_POST["description"]);
    $confidentialite=$_POST["confidentialite"];
    $gestionnaires=trim($_POST["gestionnaires"]);
    $collaborateurs=trim($_POST["collaborateur"]);

     //  Vérifier tailles des champs
    $erreurs = verifier_champs_projet($nom, $description);
    if (!empty($erreurs)) {
        $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
    } else {
         // Enregistrer le projet
        if (creer_projet($bdd, $nom, $description, $confidentialite, $id_createur)) {

            $id_projet = $bdd->lastInsertId(); #permet de connaitre l'id du projet qu'on vient d'ajouter dans la bdd
            //enregistrer les participants
            ajouter_participants($bdd, $id_projet, $gestionnaires, $collaborateurs);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page de création de projet</title>
    <link rel="stylesheet" href="../css/page_creation_projet_style.css">
</head>
<body>
    <div class="project-box">
        <h2>Créer un projet</h2>

        <?php
        // Affiche le message si présent
        if (!empty($message)) echo $message;
        ?>

        <form action="" method="post" autocomplete="off">
            <label for="nom_projet">Nom du projet :</label>
            <input type="text" id="nom_projet" name="nom_projet" value="<?= htmlspecialchars($_POST['nom_projet'] ?? '') ?>">

            <label for="description">Description :</label>
            <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

            <label>Confidentiel :</label>
            <div class="user-type">
                <input type="radio" name="confidentialite" value="oui" id="oui" required <?= (($_POST['confidentialite'] ?? '') === 'oui') ? 'checked' : '' ?>>
                <label for="oui">Oui</label>

                <input type="radio" name="confidentialite" value="non" id="non" required <?= (($_POST['confidentialite'] ?? '') === 'non') ? 'checked' : '' ?>>
                <label for="non">Non</label>
            </div>

            <label for="gestionnaires">Gestionnaires :</label>
            <input type="text" id="gestionnaires" name="gestionnaires[]" value="<?= htmlspecialchars($_POST['gestionnaires'][0] ?? '') ?>">

            <label for="collaborateurs">Collaborateurs :</label>
            <input type="text" id="collaborateurs" name="collaborateurs[]" value="<?= htmlspecialchars($_POST['collaborateurs'][0] ?? '') ?>">

            <input type="submit" value="Créer le projet">
        </form>
    </div>
</body>
</html>