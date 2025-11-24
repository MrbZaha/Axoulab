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
    $etat = $stmt->fetchColumn();
    // Si c'est un étudiant => projet non validé
    $valide = ($etat == 1) ? 0 : 1; // Correction: == au lieu de =

    $sql = $bdd->prepare("
        INSERT INTO projet (ID_projet, Nom_projet, Description, Confidentiel, Date_de_creation, Date_de_modification)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    return $sql->execute([$nom_projet, $description, $confidentialite, $date_creation, $id_compte, $valide]);
}

// ======================= TROUVER ID COMPTE PAR NOM =======================
function trouver_id_par_nom($bdd, $nom_complet) {
    $parts = explode(' ', $nom_complet, 2);
    if (count($parts) < 2) return null;
    
    $prenom = trim($parts[0]);
    $nom = trim($parts[1]);
    
    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE Prenom = ? AND Nom = ?");
    $stmt->execute([$prenom, $nom]);
    return $stmt->fetchColumn();
}

// ======================= AJOUTER PARTICIPANTS =======================
function ajouter_participants($bdd, $id_projet, $gestionnaires, $collaborateurs) {
    $sql = $bdd->prepare("
        INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut)
        VALUES (?, ?, ?)
    ");

    foreach ($gestionnaires as $nom_complet) {
        $id_compte = trouver_id_par_nom($bdd, $nom_complet);
        if ($id_compte) {
            $sql->execute([$id_projet, $id_compte, 'gestionnaire']);
        }
    }

    foreach ($collaborateurs as $nom_complet) {
        $id_compte = trouver_id_par_nom($bdd, $nom_complet);
        if ($id_compte) {
            $sql->execute([$id_projet, $id_compte, 'collaborateur']);
        }
    }
}

// ======================= Récupérer tous les comptes valides =======================
$stmt = $bdd->query("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE Etat > 1 ORDER BY Nom, Prenom");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$message = "";

if (isset($_POST["nom_projet"], $_POST["description"], $_POST["confidentialite"])) {
    $nom_projet = trim($_POST["nom_projet"]);
    $description = trim($_POST["description"]);
    $confidentialite = $_POST["confidentialite"];
    
    // Gestion des tableaux pour participants (maintenant avec noms complets)
    $gestionnaires = isset($_POST["gestionnaires"]) ? array_filter(array_map('trim', (array)$_POST["gestionnaires"])) : [];
    $collaborateurs = isset($_POST["collaborateurs"]) ? array_filter(array_map('trim', (array)$_POST["collaborateurs"])) : [];

    // Vérifier tailles des champs
    $erreurs = verifier_champs_projet($nom_projet, $description);
    
    // Vérifier que les noms des participants existent
    foreach ($gestionnaires as $nom) {
        if (!trouver_id_par_nom($bdd, $nom)) {
            $erreurs[] = "Le gestionnaire '$nom' n'existe pas dans la base de données.";
        }
    }
    
    foreach ($collaborateurs as $nom) {
        if (!trouver_id_par_nom($bdd, $nom)) {
            $erreurs[] = "Le collaborateur '$nom' n'existe pas dans la base de données.";
        }
    }

    if (!empty($erreurs)) {
        $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
    } else {
        // Enregistrer le projet
        if (creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"])) {
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
    <title>Page de création de projet</title>
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
            <input type="text" id="nom_projet" name="nom_projet" value="<?= htmlspecialchars($_POST['nom_projet'] ?? '') ?>" required>

            <label for="description">Description :</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

            <label>Confidentiel :</label>
            <div class="user-type">
                <input type="radio" name="confidentialite" value="oui" id="oui" required <?= (($_POST['confidentialite'] ?? '') === 'oui') ? 'checked' : '' ?>>
                <label for="oui">Oui</label>

                <input type="radio" name="confidentialite" value="non" id="non" required <?= (($_POST['confidentialite'] ?? '') === 'non') ? 'checked' : '' ?>>
                <label for="non">Non</label>
            </div>

            <label for="gestionnaires">Gestionnaires :</label>
            <input type="text" id="gestionnaires" name="gestionnaires[]" list="liste_comptes" 
                   placeholder="Tapez le nom d'un gestionnaire..." 
                   value="<?= htmlspecialchars($_POST['gestionnaires'][0] ?? '') ?>">

            <label for="collaborateurs">Collaborateurs :</label>
            <input type="text" id="collaborateurs" name="collaborateurs[]" list="liste_comptes" 
                   placeholder="Tapez le nom d'un collaborateur..." 
                   value="<?= htmlspecialchars($_POST['collaborateurs'][0] ?? '') ?>">

            <!-- Datalist avec tous les utilisateurs -->
            <datalist id="liste_comptes">
            <?php foreach ($utilisateurs as $user): ?>
                <option value="<?= htmlspecialchars($user['Prenom'] . ' ' . $user['Nom']) ?>">
            <?php endforeach; ?>
            </datalist>
            <input type="submit" value="Créer le projet">
        </form>
    </div>
   <?php
    afficher_Bandeau_Bas();
    ?>
</body>
</html>