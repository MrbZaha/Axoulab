<?php
session_start();
require_once __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
verification_connexion($bdd);

$message = "";
$experimentateurs_selectionnes = [];
$id_projet = null;
$id_compte = $_SESSION['ID_compte'];

// Récupérer l'ID du projet
if (isset($_POST['id_projet'])) {
    $id_projet = intval($_POST['id_projet']);
} elseif (isset($_GET['id_projet'])) {
    $id_projet = intval($_GET['id_projet']);
}

// Gestion des actions (ajout/retrait)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer la liste actuelle
    $experimentateurs_selectionnes = isset($_POST["experimentateurs_ids"]) && $_POST["experimentateurs_ids"] !== ''
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["experimentateurs_ids"]))))
        : [];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_experimentateur':
                if (!empty($_POST['nom_experimentateur'])) {
                    // On retire "(Rôle)" pour retrouver le nom dans la base
                    $nom_nettoye = trim(preg_replace('/\s*\(.*?\)$/', '', $_POST['nom_experimentateur']));
                    $id = trouver_id_par_nom_complet($bdd, $nom_nettoye);
                    if ($id && !in_array($id, $experimentateurs_selectionnes)) {
                        $experimentateurs_selectionnes[] = $id;
                    }
                }
                break;
                
            case 'retirer_experimentateur':
                if (isset($_POST['id_retirer']) && !empty($_POST['id_retirer'])) {
                    $id_a_retirer = intval($_POST['id_retirer']);
                    $experimentateurs_selectionnes = array_values(array_diff($experimentateurs_selectionnes, [$id_a_retirer]));
                }
                break;
        }
    }

    // Charger les infos de TOUS les expérimentateurs sélectionnés
    if (!empty($experimentateurs_selectionnes)) {
        $placeholders = implode(',', array_fill(0, count($experimentateurs_selectionnes), '?'));
        $stmt = $bdd->prepare("
            SELECT ID_compte, Nom, Prenom, Etat
            FROM compte
            WHERE ID_compte IN ($placeholders)
            ORDER BY Nom, Prenom
        ");
        $stmt->execute($experimentateurs_selectionnes);
        $experimentateurs_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $experimentateurs_info = [];
    }

    // Validation et passage à la page 2
    if (isset($_POST["continuer_reservation"])) {
        $nom_experience = trim($_POST["nom_experience"]);
        $description = trim($_POST["description"]);
        $experimentateurs_selectionnes = isset($_POST["experimentateurs_ids"]) && $_POST["experimentateurs_ids"] !== ''
            ? array_values(array_filter(array_map('intval', explode(',', $_POST["experimentateurs_ids"]))))
            : [];

        // Vérification des champs
        $erreurs = [];
        if (strlen($nom_experience) < 3 || strlen($nom_experience) > 100) {
            $erreurs[] = "Le nom de l'expérience doit contenir entre 3 et 100 caractères."; 
        }
        if (strlen($description) < 10 || strlen($description) > 2000) {
            $erreurs[] = "La description doit contenir entre 10 et 2000 caractères.";
        }

        if (!empty($erreurs)) {
            $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
        } else {
            // Stocker en session
            $_SESSION['creation_experience'] = [
                'nom_experience' => $nom_experience,
                'description' => $description,
                'experimentateurs_ids' => $experimentateurs_selectionnes
            ];
            
            // Redirection vers page 2 avec les données en POST
            echo '<form id="form-redirect" method="post" action="page_creation_experience_2.php">';
            echo '<input type="hidden" name="id_projet" value="' . $id_projet . '">';
            echo '<input type="hidden" name="nom_experience" value="' . htmlspecialchars($nom_experience, ENT_QUOTES) . '">';
            echo '<input type="hidden" name="description" value="' . htmlspecialchars($description, ENT_QUOTES) . '">';
            echo '<input type="hidden" name="experimentateurs_ids" value="' . implode(',', $experimentateurs_selectionnes) . '">';
            echo '</form>';
            echo '<script>document.getElementById("form-redirect").submit();</script>';
            exit();
        }
    }
}

// S'assurer que le créateur est toujours dans la sélection
$creator_id = $_SESSION['ID_compte'] ?? null;
if ($creator_id && !in_array($creator_id, $experimentateurs_selectionnes)) {
    $experimentateurs_selectionnes[] = $creator_id;
}

// Récupérer les personnes disponibles (exclut les IDs déjà sélectionnés)
// Exclure aussi le créateur des suggestions
$tous_ids_a_exclure = array_merge($experimentateurs_selectionnes, [$_SESSION['ID_compte']]);
$experimentateurs_disponibles = get_personnes_disponibles($bdd, $tous_ids_a_exclure);



// s'assurer que le créateur est dans la sélection (et recharger les infos)
$creator_id = $_SESSION['ID_compte'] ?? null;
if ($creator_id && !in_array($creator_id, $experimentateurs_selectionnes)) {
    $experimentateurs_selectionnes[] = $creator_id;
}

if (!empty($experimentateurs_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($experimentateurs_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE ID_compte IN ($placeholders)");
    $stmt->execute($experimentateurs_selectionnes);
    $experimentateurs_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $experimentateurs_info = [];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création d'expérience - Étape 1</title>
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_creation_experience_1.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
</head>
<body>
    <?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"]); ?>
    
    <div class="project-box">
        <h2>Créer une expérience - Étape 1/2</h2>
        <p style="text-align:center;color:#666;margin-bottom:20px;">Renseignez les informations de base</p>

        <?php if (!empty($message)) echo $message; ?>
        
        <?php if ($id_projet === null): ?>
            <p style="color:red;">Erreur : Vous devez accéder à cette page depuis un projet.</p>
            <a href="page_mes_projets.php">Retour à la liste de vos projets</a>
        <?php else: ?>

        <form method="post" id="form-experience">
            <input type="hidden" name="id_projet" value="<?= $id_projet ?>">
            <input type="hidden" name="experimentateurs_ids" value="<?= implode(',', $experimentateurs_selectionnes) ?>">
            <input type="hidden" id="id_retirer" name="id_retirer" value="">
            
            <label for="nom_experience">Nom de l'expérience :</label>
            <input type="text" id="nom_experience" name="nom_experience" value="<?= htmlspecialchars($_POST['nom_experience'] ?? '') ?>" required>

            <label for="description">Description :</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

            <!-- EXPERIMENTATEURS -->
            <div class="participants-section">
                <label>Expérimentateurs :</label>
                <p class="info-text">Tous les utilisateurs validés peuvent être expérimentateurs</p>  
                <div class="selection-container">
                    <input type="text" 
                           name="nom_experimentateur" 
                           list="liste-experimentateurs-disponibles" 
                           placeholder="Rechercher un expérimentateur..."
                           autocomplete="off">
                    <button type="submit" name="action" value="ajouter_experimentateur" class="btn-ajouter">Ajouter</button>
                </div>
                <datalist id="liste-experimentateurs-disponibles">
                    <?php foreach ($experimentateurs_disponibles as $personne): ?>
                        <?php
                            $nom = htmlspecialchars($personne['Prenom'] . ' ' . $personne['Nom']);
                            $role = $personne['Etat'] == 1 ? 'Étudiant' : ($personne['Etat'] == 2 ? 'Chercheur' : 'Administrateur');
                            $affichage = $nom . ' (' . $role . ')';
                        ?>
                        <option value="<?= $affichage ?>"><?= $affichage ?></option>
                    <?php endforeach; ?>
                </datalist>
                
                <div class="liste-selectionnes">
                    <?php if (empty($experimentateurs_info)): ?>
                        <div class="liste-vide">Aucun expérimentateur ajouté</div>
                    <?php else: ?>
                        <?php foreach ($experimentateurs_info as $exp): ?>
                            <?php
                                $tag_class = $exp['Etat'] == 1 ? 'tag-etudiant' : ($exp['Etat'] == 2 ? 'tag-chercheur' : 'tag-admin');
                            ?>
                            <span class="tag-personne <?= $tag_class ?>">
                                <?= htmlspecialchars($exp['Prenom'] . ' ' . $exp['Nom']) ?>
                                <button type="submit" name="action" value="retirer_experimentateur" class="btn-croix"
                                        onclick="this.form.id_retirer.value=<?= $exp['ID_compte'] ?>; return true;">
                                    ×
                                </button>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" name="continuer_reservation" class="btn-continuer">
                Continuer → Réserver une salle
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php afficher_Bandeau_Bas(); ?>
</body>
</html>
