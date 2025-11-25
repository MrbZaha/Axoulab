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

// ======================= RÉCUPÉRER LISTE PERSONNES DISPONIBLES =======================
function get_personnes_disponibles($bdd, $ids_exclus = [], $seulement_non_etudiants = false) {
    $sql = "SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE validation = 1";
    
    if ($seulement_non_etudiants) {
        $sql .= " AND Etat > 1";
    }
    
    if (!empty($ids_exclus)) {
        $placeholders = implode(',', array_fill(0, count($ids_exclus), '?'));
        $sql .= " AND ID_compte NOT IN ($placeholders)";
    }
    
    $sql .= " ORDER BY Nom, Prenom";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute($ids_exclus);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ======================= INSERER UN NOUVEAU PROJET =======================
function creer_projet($bdd, $nom_projet, $description, $confidentialite, $id_compte) {
    $date_creation = date('Y-m-d');
    
    $stmt = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id_compte]);
    $etat = $stmt->fetchColumn();
    $valide = ($etat == 1) ? 0 : 1;

    $sql = $bdd->prepare("
        INSERT INTO projet (Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    return $sql->execute([$nom_projet, $description, $confidentialite, $date_creation, $id_compte, $valide]);
}

// ======================= TROUVER ID COMPTE PAR NOM =======================
function trouver_id_par_nom($bdd, $nom_complet) {
    $parts = explode(' ', trim($nom_complet), 2);
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

    foreach ($gestionnaires as $id_compte) {
        if ($id_compte) {
            $sql->execute([$id_projet, $id_compte, 'gestionnaire']);
        }
    }

    foreach ($collaborateurs as $id_compte) {
        if ($id_compte) {
            $sql->execute([$id_projet, $id_compte, 'collaborateur']);
        }
    }
}

// ======================= TROUVER ID PAR NOM COMPLET =======================
function trouver_id_par_nom_complet($bdd, $nom_complet) {
    $parts = explode(' ', trim($nom_complet), 2);
    if (count($parts) < 2) return null;
    
    $prenom = trim($parts[0]);
    $nom = trim($parts[1]);
    
    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE Prenom = ? AND Nom = ? AND validation = 1");
    $stmt->execute([$prenom, $nom]);
    return $stmt->fetchColumn();
}

$message = "";
$gestionnaires_selectionnes = [];
$collaborateurs_selectionnes = [];

// Gérer la sélection des participants
$gestionnaires_selectionnes = [];
$collaborateurs_selectionnes = [];

// Ajouter un gestionnaire
if (isset($_POST['ajouter_gestionnaire']) && !empty($_POST['nouveau_gestionnaire'])) {
    $gestionnaires_selectionnes = isset($_POST['gestionnaires_ids']) ? $_POST['gestionnaires_ids'] : [];
    if (!in_array($_POST['nouveau_gestionnaire'], $gestionnaires_selectionnes)) {
        $gestionnaires_selectionnes[] = $_POST['nouveau_gestionnaire'];
    }
}

// Retirer un gestionnaire
if (isset($_POST['retirer_gestionnaire'])) {
    $gestionnaires_selectionnes = isset($_POST['gestionnaires_ids']) ? $_POST['gestionnaires_ids'] : [];
    $gestionnaires_selectionnes = array_diff($gestionnaires_selectionnes, [$_POST['retirer_gestionnaire']]);
}

// Ajouter un collaborateur
if (isset($_POST['ajouter_collaborateur']) && !empty($_POST['nouveau_collaborateur'])) {
    $collaborateurs_selectionnes = isset($_POST['collaborateurs_ids']) ? $_POST['collaborateurs_ids'] : [];
    if (!in_array($_POST['nouveau_collaborateur'], $collaborateurs_selectionnes)) {
        $collaborateurs_selectionnes[] = $_POST['nouveau_collaborateur'];
    }
}

// Retirer un collaborateur
if (isset($_POST['retirer_collaborateur'])) {
    $collaborateurs_selectionnes = isset($_POST['collaborateurs_ids']) ? $_POST['collaborateurs_ids'] : [];
    $collaborateurs_selectionnes = array_diff($collaborateurs_selectionnes, [$_POST['retirer_collaborateur']]);
}

// Récupérer les IDs depuis les champs cachés si présents
if (isset($_POST['gestionnaires_ids'])) {
    $gestionnaires_selectionnes = $_POST['gestionnaires_ids'];
}
if (isset($_POST['collaborateurs_ids'])) {
    $collaborateurs_selectionnes = $_POST['collaborateurs_ids'];
}

// Créer le projet
if (isset($_POST["creer_projet"])) {
    $nom_projet = trim($_POST["nom_projet"]);
    $description = trim($_POST["description"]);
    $confidentialite = $_POST["confidentialite"];
    
    $gestionnaires_ids = isset($_POST['gestionnaires_ids']) ? $_POST['gestionnaires_ids'] : [];
    $collaborateurs_ids = isset($_POST['collaborateurs_ids']) ? $_POST['collaborateurs_ids'] : [];

    $erreurs = verifier_champs_projet($nom_projet, $description);

    if (!empty($erreurs)) {
        $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
    } else {
        if (creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"])) {
            $id_projet = $bdd->lastInsertId();
            ajouter_participants($bdd, $id_projet, $gestionnaires_ids, $collaborateurs_ids);
            $message = "<p style='color:green;'>Projet créé avec succès!</p>";
            
            // Réinitialiser les sélections
            $gestionnaires_selectionnes = [];
            $collaborateurs_selectionnes = [];
        } else {
            // Enregistrer le projet
            if (creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"])) {
                $id_projet = $bdd->lastInsertId();
                
                // Enregistrer les participants
                ajouter_participants($bdd, $id_projet, $gestionnaires_selectionnes, $collaborateurs_selectionnes);
                
                // Redirection vers la page du projet créé
                header("Location: page_projet.php?id_projet=" . $id_projet);
                exit();
            } else {
                $message = "<p style='color:red;'>Erreur lors de la création du projet.</p>";
            }
        }
    }
}

// Fonction pour obtenir le nom complet d'un utilisateur
function obtenir_nom_utilisateur($bdd, $id_compte) {
    $stmt = $bdd->prepare("SELECT Prenom, Nom FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id_compte]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user['Prenom'] . ' ' . $user['Nom'] : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page de création de projet</title>
    <link rel="stylesheet" href="../css/page_creation_projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <style>
        .participant-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .participant-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .participant-selector select {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-add {
            padding: 8px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-add:hover {
            background: #0056b3;
        }
        
        .selected-list {
            margin-top: 10px;
        }
        
        .participant-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-remove {
            padding: 5px 15px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .empty-message {
            color: #666;
            font-style: italic;
            padding: 10px;
        }
        
        .btn-create {
            padding: 12px 30px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .btn-create:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"]); ?>
    <div class="project-box">
        <h2>Créer un projet</h2>

        <?php if (!empty($message)) echo $message; ?>

        <form action="" method="post">
            <label for="nom_projet">Nom du projet :</label>
            <input type="text" id="nom_projet" name="nom_projet" 
                   value="<?= htmlspecialchars($_POST['nom_projet'] ?? '') ?>" required>

            <label for="description">Description :</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

            <label>Confidentiel :</label>
            <div class="user-type">
                <input type="radio" name="confidentialite" value="oui" id="oui" required 
                       <?= (($_POST['confidentialite'] ?? '') === 'oui') ? 'checked' : '' ?>>
                <label for="oui">Oui</label>

                <input type="radio" name="confidentialite" value="non" id="non" required 
                       <?= (($_POST['confidentialite'] ?? '') === 'non') ? 'checked' : '' ?>>
                <label for="non">Non</label>
            </div>

            <!-- SECTION GESTIONNAIRES -->
            <div class="participant-section">
                <h3>Gestionnaires</h3>
                <div class="participant-selector">
                    <select name="nouveau_gestionnaire" id="nouveau_gestionnaire">
                        <option value="">-- Sélectionner un gestionnaire --</option>
                        <?php foreach ($utilisateurs as $user): 
                            if (!in_array($user['ID_compte'], $gestionnaires_selectionnes)): ?>
                                <option value="<?= $user['ID_compte'] ?>">
                                    <?= htmlspecialchars($user['Prenom'] . ' ' . $user['Nom']) ?>
                                </option>
                        <?php endif; endforeach; ?>
                    </select>
                    <button type="submit" name="ajouter_gestionnaire" class="btn-add">Ajouter</button>
                </div>

                <div class="selected-list">
                    <strong>Gestionnaires sélectionnés :</strong>
                    <?php if (empty($gestionnaires_selectionnes)): ?>
                        <div class="empty-message">Aucun gestionnaire sélectionné</div>
                    <?php else: ?>
                        <?php foreach ($gestionnaires_selectionnes as $id): ?>
                            <div class="participant-item">
                                <span><?= htmlspecialchars(obtenir_nom_utilisateur($bdd, $id)) ?></span>
                                <button type="submit" name="retirer_gestionnaire" 
                                        value="<?= $id ?>" class="btn-remove">Retirer</button>
                            </div>
                            <input type="hidden" name="gestionnaires_ids[]" value="<?= $id ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION COLLABORATEURS -->
            <div class="participant-section">
                <h3>Collaborateurs</h3>
                <div class="participant-selector">
                    <select name="nouveau_collaborateur" id="nouveau_collaborateur">
                        <option value="">-- Sélectionner un collaborateur --</option>
                        <?php foreach ($utilisateurs as $user): 
                            if (!in_array($user['ID_compte'], $collaborateurs_selectionnes)): ?>
                                <option value="<?= $user['ID_compte'] ?>">
                                    <?= htmlspecialchars($user['Prenom'] . ' ' . $user['Nom']) ?>
                                </option>
                        <?php endif; endforeach; ?>
                    </select>
                    <button type="submit" name="ajouter_collaborateur" class="btn-add">Ajouter</button>
                </div>

                <div class="selected-list">
                    <strong>Collaborateurs sélectionnés :</strong>
                    <?php if (empty($collaborateurs_selectionnes)): ?>
                        <div class="empty-message">Aucun collaborateur sélectionné</div>
                    <?php else: ?>
                        <?php foreach ($collaborateurs_selectionnes as $id): ?>
                            <div class="participant-item">
                                <span><?= htmlspecialchars(obtenir_nom_utilisateur($bdd, $id)) ?></span>
                                <button type="submit" name="retirer_collaborateur" 
                                        value="<?= $id ?>" class="btn-remove">Retirer</button>
                            </div>
                            <input type="hidden" name="collaborateurs_ids[]" value="<?= $id ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Conserver les valeurs du formulaire -->
            <?php foreach ($gestionnaires_selectionnes as $id): ?>
                <input type="hidden" name="gestionnaires_ids[]" value="<?= $id ?>">
            <?php endforeach; ?>
            
            <?php foreach ($collaborateurs_selectionnes as $id): ?>
                <input type="hidden" name="collaborateurs_ids[]" value="<?= $id ?>">
            <?php endforeach; ?>

            <button type="submit" name="creer_projet" class="btn-create">Créer le projet</button>
        </form>
    </div>
    <?php afficher_Bandeau_Bas(); ?>
</body>
<?php afficher_Bandeau_Bas(); ?>
</html>