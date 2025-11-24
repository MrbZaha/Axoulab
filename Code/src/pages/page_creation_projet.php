<?php
session_start();
include_once "../back_php/fonctions_site_web.php";
$bdd = connectBDD();
$_SESSION["ID_compte"] = 3;

// ======================= VERIFIER TAILLES DES CHAMPS =======================
function verifier_champs_projet($nom_projet, $description) {
    $erreurs = [];

    if (strlen($nom_projet) < 3 || strlen($nom_projet) > 100) {
        $erreurs[] = "Le nom du projet doit contenir entre 3 et 100 caractères."; 
    }

    if (strlen($description) < 10 || strlen($description) > 2000) {
        $erreurs[] = "La description doit contenir entre 10 et 2000 caractères.";
    }

    return $erreurs;
}

// ======================= INSERER UN NOUVEAU PROJET =======================
function creer_projet($bdd, $nom_projet, $description, $confidentialite, $id_compte) {
    $date_creation = date('Y-m-d H:i:s');
    
    // Vérifier le type de compte
    $stmt = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id_compte]);
    $etat= $stmt->fetchColumn();
    // Si c'est un étudiant => projet non validé
    $valide = ($etat = 1) ? 0 : 1;

    $sql = $bdd->prepare("
        INSERT INTO projets (Nom_projet, Description, Confidentiel, Date_de_creation)
        VALUES (?, ?, ?, ?)
    ");

    return $sql->execute([$nom_projet, $description, $confidentialite, $date_creation]);
}

// ======================= AJOUTER PARTICIPANTS =======================
function ajouter_participants($bdd, $id_projet, $gestionnaires, $collaborateurs) {
    $sql = $bdd->prepare("
        INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut)
        VALUES (?, ?, ?)
    ");

    foreach ($gestionnaires as $id_compte) {
        $sql->execute([$id_projet, $id_compte, 'gestionnaire']);
    }

    foreach ($collaborateurs as $id_compte) {
        $sql->execute([$id_projet, $id_compte, 'collaborateur']);
    }
}

// ======================= Récupérer tous les comptes valides =======================
$stmt = $bdd->query("SELECT ID_compte, Nom, Prenom FROM compte ORDER BY Nom, Prenom");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$message = "";

if (isset($_POST["nom_projet"], $_POST["description"], $_POST["confidentialite"])) {
    $nom_projet = trim($_POST["nom_projet"]);
    $description = trim($_POST["description"]);
    $confidentialite = $_POST["confidentialite"];
    
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
    <title> Page de création de projet</title>
    <link rel="stylesheet" href="../css/page_creation_projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
</head>
<body>
    <?php afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);?>
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
            <select name="gestionnaires[]" id="gestionnaires" multiple>
            <?php foreach ($utilisateurs as $user): ?>
             <option value="<?= $user['ID_compte'] ?>"
                <?= in_array($user['ID_compte'], $_POST['gestionnaires'] ?? []) ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['Prenom'] . " " . $user['Nom']) ?>
            </option>
            <?php endforeach; ?>
            </select>

            <label for="collaborateurs">Collaborateurs :</label>
            <select name="collaborateurs[]" id="collaborateurs" multiple>
            <?php foreach ($utilisateurs as $user): ?>
             <option value="<?= $user['ID_compte'] ?>"
                <?= in_array($user['ID_compte'], $_POST['collaborateurs'] ?? []) ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['Prenom'] . " " . $user['Nom']) ?>
            </option>
            <?php endforeach; ?>
            </select>


            <input type="submit" value="Créer le projet">
        </form>
    </div>
   <?php
    afficher_Bandeau_Bas();
    ?>
</body>
</html>