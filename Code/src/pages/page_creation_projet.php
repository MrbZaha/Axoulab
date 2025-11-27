<?php
session_start();
require "../back_php/fonctions_site_web.php";
$bdd = connectBDD();
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    
    // Vérifier le type de compte
    $stmt = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id_compte]);
    $etat = $stmt->fetchColumn();
    // Si c'est un étudiant => projet non validé
    $valide = ($etat == 1) ? 0 : 1;

    $sql = $bdd->prepare("
        INSERT INTO projet (Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $sql->execute([$nom_projet, $description, $confidentialite, $valide, $date_creation, $date_creation]);
    return $bdd->lastInsertId();
}

// ======================= AJOUTER PARTICIPANTS =======================
function ajouter_participants($bdd, $id_projet, $gestionnaires, $collaborateurs) {
    $sql = $bdd->prepare("
        INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut)
        VALUES (?, ?, ?)
    ");

    foreach ($gestionnaires as $id_compte) {
        $sql->execute([$id_projet, $id_compte, 1]); // 1 = gestionnaire
    }

    foreach ($collaborateurs as $id_compte) {
        $sql->execute([$id_projet, $id_compte, 0]); // 0 = collaborateur
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

// ======================= GESTION DES FORMULAIRES =======================
$message = "";
$gestionnaires_selectionnes = [];
$collaborateurs_selectionnes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $gestionnaires_selectionnes = isset($_POST["gestionnaires_ids"]) ? array_filter(array_map('intval', explode(',', $_POST["gestionnaires_ids"]))) : [];
    $collaborateurs_selectionnes = isset($_POST["collaborateurs_ids"]) ? array_filter(array_map('intval', explode(',', $_POST["collaborateurs_ids"]))) : [];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_gestionnaire':
                if (!empty($_POST['nom_gestionnaire'])) {
                    $id = trouver_id_par_nom_complet($bdd, $_POST['nom_gestionnaire']);
                    if ($id && !in_array($id, $gestionnaires_selectionnes) && !in_array($id, $collaborateurs_selectionnes)) {
                        $stmt = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
                        $stmt->execute([$id]);
                        $etat = $stmt->fetchColumn();
                        if ($etat > 1) {
                            $gestionnaires_selectionnes[] = $id;
                        } else {
                            $message = "<p style='color:orange;'>Un étudiant ne peut pas être gestionnaire.</p>";
                        }
                    }
                }
                break;
                
            case 'retirer_gestionnaire':
                if (!empty($_POST['id_retirer'])) {
                    $gestionnaires_selectionnes = array_diff($gestionnaires_selectionnes, [intval($_POST['id_retirer'])]);
                }
                break;
                
            case 'ajouter_collaborateur':
                if (!empty($_POST['nom_collaborateur'])) {
                    $id = trouver_id_par_nom_complet($bdd, $_POST['nom_collaborateur']);
                    if ($id && !in_array($id, $collaborateurs_selectionnes) && !in_array($id, $gestionnaires_selectionnes)) {
                        $collaborateurs_selectionnes[] = $id;
                    }
                }
                break;
                
            case 'retirer_collaborateur':
                if (!empty($_POST['id_retirer'])) {
                    $collaborateurs_selectionnes = array_diff($collaborateurs_selectionnes, [intval($_POST['id_retirer'])]);
                }
                break;
        }
    }

    if (isset($_POST["creer_projet"])) {
        $nom_projet = trim($_POST["nom_projet"]);
        $description = trim($_POST["description"]);
        $confidentialite = $_POST["confidentialite"] === 'oui' ? 1 : 0;

        $erreurs = verifier_champs_projet($nom_projet, $description);

        if (!empty($erreurs)) {
            $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
        } else {
            try {
                // Créer le projet
                $id_projet = creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"]);

                // Ajouter participants
                ajouter_participants($bdd, $id_projet, $gestionnaires_selectionnes, $collaborateurs_selectionnes);

                // ======================= ENVOYER NOTIFICATIONS AUX AUTRES GESTIONNAIRES =======================
                // Ici on n’envoie PAS de notification au créateur lors de la création
                $gestionnaires_dest = array_values(array_diff($gestionnaires_selectionnes, [$_SESSION["ID_compte"]]));
                if (!empty($gestionnaires_dest)) {
                    $donnees = ['Nom_projet' => $nom_projet, 'ID_projet' => $id_projet];
                    // type 11 = proposition de création de projet (comme défini dans ton système)
                    // envoyerNotification gère l'insertion dans notification_projet (fonction dans fonctions_site_web.php)
                    $ok = envoyerNotification($bdd, 11, $_SESSION["ID_compte"], $donnees, $gestionnaires_dest);

                    // debug - log des destinataires
                    error_log("Envoi notif type 11 projet $id_projet de " . $_SESSION["ID_compte"] . " vers : " . implode(',', $gestionnaires_dest));
                }

                // Réinitialiser les sélections
                $gestionnaires_selectionnes = [];
                $collaborateurs_selectionnes = [];

                // Redirection vers la page du projet
                header("Location: page_projet.php?id_projet=" . $id_projet);
                exit();
            } catch (Exception $e) {
                $message = "<p style='color:red;'>Erreur lors de la création du projet : " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
}

// ======================= Récupération des listes =======================
$tous_ids_selectionnes = array_merge($gestionnaires_selectionnes, $collaborateurs_selectionnes);
$personnes_gestionnaires = get_personnes_disponibles($bdd, $tous_ids_selectionnes, true);
$personnes_collaborateurs = get_personnes_disponibles($bdd, $tous_ids_selectionnes, false);

$gestionnaires_info = [];
$collaborateurs_info = [];

if (!empty($gestionnaires_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($gestionnaires_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE ID_compte IN ($placeholders)");
    $stmt->execute($gestionnaires_selectionnes);
    $gestionnaires_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!empty($collaborateurs_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($collaborateurs_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE ID_compte IN ($placeholders)");
    $stmt->execute($collaborateurs_selectionnes);
    $collaborateurs_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page de création de projet</title>
    <link rel="stylesheet" href="../css/page_creation_projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"]); ?>
<div class="project-box">
    <h2>Créer un projet</h2>

    <?php if (!empty($message)) echo $message; ?>

    <form action="" method="post" id="form-projet">
        <input type="hidden" name="gestionnaires_ids" value="<?= implode(',', $gestionnaires_selectionnes) ?>">
        <input type="hidden" name="collaborateurs_ids" value="<?= implode(',', $collaborateurs_selectionnes) ?>">
        
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

        <!-- GESTIONNAIRES -->
        <div class="participants-section">
            <label>Gestionnaires :</label>
            <p class="info-text">Seuls les professeurs/chercheurs et administrateurs peuvent être gestionnaires</p>
            <div class="selection-container">
                <input type="text" name="nom_gestionnaire" list="liste-gestionnaires-disponibles" placeholder="Rechercher un gestionnaire..." autocomplete="off">
                <button type="submit" name="action" value="ajouter_gestionnaire" class="btn-ajouter">Ajouter</button>
            </div>
            <datalist id="liste-gestionnaires-disponibles">
                <?php foreach ($personnes_gestionnaires as $personne): ?>
                    <option value="<?= htmlspecialchars($personne['Prenom'] . ' ' . $personne['Nom']) ?>">
                        <?= $personne['Etat'] == 3 ? 'ADMIN' : 'Chercheur' ?>
                    </option>
                <?php endforeach; ?>
            </datalist>
            <div class="liste-selectionnes">
                <?php if (empty($gestionnaires_info)): ?>
                    <div class="liste-vide">Aucun gestionnaire ajouté</div>
                <?php else: ?>
                    <?php foreach ($gestionnaires_info as $gest): ?>
                        <span class="tag-personne <?= $gest['Etat'] == 3 ? 'tag-admin' : 'tag-chercheur' ?>">
                            <?= htmlspecialchars($gest['Prenom'] . ' ' . $gest['Nom']) ?>
                            <button type="submit" name="action" value="retirer_gestionnaire" class="btn-croix"
                                    onclick="this.form.id_retirer.value=<?= $gest['ID_compte'] ?>; return true;">×</button>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLLABORATEURS -->
        <div class="participants-section">
            <label>Collaborateurs :</label>
            <p class="info-text">Tous les utilisateurs validés peuvent être collaborateurs</p>
            <div class="selection-container">
                <input type="text" name="nom_collaborateur" list="liste-collaborateurs-disponibles" placeholder="Rechercher un collaborateur..." autocomplete="off">
                <button type="submit" name="action" value="ajouter_collaborateur" class="btn-ajouter">Ajouter</button>
            </div>
            <datalist id="liste-collaborateurs-disponibles">
                <?php foreach ($personnes_collaborateurs as $personne): ?>
                    <option value="<?= htmlspecialchars($personne['Prenom'] . ' ' . $personne['Nom']) ?>">
                        <?php if ($personne['Etat'] == 1) echo 'Étudiant'; elseif ($personne['Etat'] == 2) echo 'Chercheur'; else echo 'ADMIN'; ?>
                    </option>
                <?php endforeach; ?>
            </datalist>
            <div class="liste-selectionnes">
                <?php if (empty($collaborateurs_info)): ?>
                    <div class="liste-vide">Aucun collaborateur ajouté</div>
                <?php else: ?>
                    <?php foreach ($collaborateurs_info as $collab): ?>
                        <span class="tag-personne <?= $collab['Etat'] == 1 ? 'tag-etudiant' : ($collab['Etat']==2 ? 'tag-chercheur' : 'tag-admin') ?>">
                            <?= htmlspecialchars($collab['Prenom'] . ' ' . $collab['Nom']) ?>
                            <button type="submit" name="action" value="retirer_collaborateur" class="btn-croix"
                                    onclick="this.form.id_retirer.value=<?= $collab['ID_compte'] ?>; return true;">×</button>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <input type="hidden" id="id_retirer" name="id_retirer" value="">
        <input type="submit" name="creer_projet" value="Créer le projet">
    </form>
</div>
<?php afficher_Bandeau_Bas(); ?>
</body>
</html>
