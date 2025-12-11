<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_modification_projet.php';

$bdd = connectBDD();
verification_connexion($bdd);

$id_compte = $_SESSION['ID_compte'];
$id_experience = isset($_GET['id_experience']) ? (int)$_GET['id_experience'] : 0;

$erreur = null;
$success = null;

function get_projet_from_experience(PDO $bdd, int $id_experience) {
    $sql = "
    SELECT
        pe.ID_projet
    FROM projet_experience pe
    WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    return $stmt->fetchColumn();
}

function get_experimentateurs_ids(PDO $bdd, int $id_experience): array {
    $sql = "SELECT ID_compte FROM experience_experimentateur
            WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function get_experience_pour_modification(PDO $bdd, int $id_experience): ?array {
    $sql = "SELECT ID_experience, Nom, Description 
            FROM experience WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$id_projet = get_projet_from_experience($bdd, $id_experience);
$experimentateurs_selectionnes = [];
// Y mettre est_experimentateur, est_admin
if ($id_experience ===0 ) {
    $erreur = "ID d'experience manquant.";
} elseif (!est_gestionnaire($bdd, $id_compte, $id_projet)) {
    $erreur = "Vous n'avez pas les droits pour modifier cette experience";
} else {
    // Charger les participants actuels
    $experimentateurs_selectionnes = get_experimentateurs_ids($bdd, $id_experience);
}

$experimentateurs_info = [];

if (!empty($experimentateurs_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($experimentateurs_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE ID_compte IN ($placeholders)");
    $stmt->execute($experimentateurs_selectionnes);
    $experimentateurs_selectionnes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erreur) {
    
    // Récupérer les listes depuis les champs cachés
    $experimentateurs_selectionnes = isset($_POST["experimentateurs_ids"]) && $_POST["experimentateurs_ids"] !== '' 
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["experimentateurs_ids"])))) 
        : [];

    // expeion des actions d'ajout/retrait
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_experimentateur':
                if (!empty($_POST['nom_experimentateur'])) {
                    $id = trouver_id_par_nom_complet($bdd, $_POST['nom_experimentateur']);
                }
                break;

            case 'retirer_experimentateur':
                if (!empty($_POST['id_retirer'])) {
                    $experimentateurs_selectionnes = array_values(array_diff($experimentateurs_selectionnes, [intval($_POST['id_retirer'])]));
                }
                break;
        }
    }

    // Traitement de la modification de l'experience
    if (isset($_POST['modifier_experience'])) {
        $nom_experience = trim($_POST['nom_experience'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation
        if (empty($nom_experience)) {
            $erreur = "Le nom de l'experience est obligatoire.";
        } elseif (strlen($nom_experience) < 3 || strlen($nom_experience) > 100) {
            $erreur = "Le nom de l'experience doit contenir entre 3 et 100 caractères.";
        } elseif (strlen($description) < 10 || strlen($description) > 2000) {
            $erreur = "La description doit contenir entre 10 et 2000 caractères.";
        } elseif (empty($experimentateurs_selectionnes)) {
            $erreur = "L'experience doit avoir au moins un experimentateur.";
        } else {
            try {
                $bdd->beginTransaction();

                // Mise à jour des informations de l'experience
                $sql = "UPDATE experience 
                        SET Nom = :nom_experience, 
                            Description = :description, 
                            Date_de_modification = :date_modif
                        WHERE ID_experience = :id_experience";
                $stmt = $bdd->prepare($sql);
                $stmt->execute([
                    'nom' => $nom_experience,
                    'description' => $description,
                    'date_modif' => date('Y-m-d'),
                ]);

                // Supprimer toutes les anciennes associations
                $sql = "DELETE FROM experience_experimentateur WHERE ID_compte = :id_compte";
                $stmt = $bdd->prepare($sql);
                $stmt->execute(['id_compte' => $id_compte]);

                 // Supprimer toutes les anciennes experimentateurs
                $sql = "DELETE FROM experience_experimentateur WHERE ID_compte = :id_compte
                        VALUES (:id_experience, :id_compte)";
                $stmt = $bdd->prepare($sql);
                foreach ($experimentateurs_selectionnes as $id_experimentateur) {
                    $stmt->execute([
                        'id_experience' => $id_experience,
                        'id_compte' => (int)$id_experimentateur
                    ]);
                }

                // Ajouter les nouveaux expeionnaires
                $sql = "INSERT INTO experience_experimentateur (ID_experience, ID_compte) 
                        VALUES (:id_experience, :id_compte)";
                $stmt = $bdd->prepare($sql);
                foreach ($experimentateurs_selectionnes as $id_experimentateur) {
                    $stmt->execute([
                        'id_experience' => $id_experience,
                        'id_compte' => (int)$id_experimentateur
                    ]);
                }

                $bdd->commit();
                $success = "Le projet a été modifié avec succès !";
                
                // Rediriger après 2 secondes
                header("refresh:2;url=page_projet.php?id_projet=" . $id_projet);
                
            } catch (Exception $e) {
                $bdd->rollBack();
                $erreur = "Erreur lors de la modification : " . $e->getMessage();
            }
        }
    }
}
$experience = $id_experience > 0 && !$erreur ? get_experience_pour_modification($bdd, $id_experience) : null;

$personnes_experimentateur = get_personnes_disponibles($bdd, $experimentateurs_selectionnes, false);

$page_title = $experience ? "Modifier " . htmlspecialchars($experience['Nom']) : "Modification de l'experience";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="../css/page_modification_projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<div class="project-box">
    <h2>Modifier l'experience</h2>

    <?php if ($success): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <p style="margin-top: 10px; font-size: 0.9rem;">Redirection en cours...</p>
        </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erreur) ?>
        </div>
        <div class="back-button-container">
            <a href="page_experience.php?id_experience=<?= $id_experience ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour à l'experience
            </a>
        </div>
    <?php elseif ($experience && !$success): ?>
        <form action="" method="post" id="form-projet">
            <input type="hidden" name="experimentateurs_ids" value="<?= implode(',', $experimentateurs_selectionnes) ?>">

            <label for="nom_experience">Nom de l'experience :</label>
            <input type="text" 
                   id="nom_experience" 
                   name="nom_experience" 
                   value="<?= htmlspecialchars($experience['Nom']) ?>" 
                   required>

            <label for="description">Description :</label>
            <textarea id="description" 
                      name="description" 
                      required><?= htmlspecialchars($experience['Description']) ?></textarea>

            <!-- Experimentateur -->
            <div class="participants-section">
                <label>Experimentateur :</label>
                <p class="info-text">Tous les utilisateurs validés peuvent être experimentateur</p>
                <div class="selection-container">
                    <input type="text" 
                           name="nom_experimentateur" 
                           list="liste-experimentateur-disponibles" 
                           placeholder="Rechercher un experimentateur..." 
                           autocomplete="off">
                    <button type="submit" name="action" value="ajouter_experimentateur" class="btn-ajouter">Ajouter</button>
                </div>
<datalist id="liste-experimentateur-disponibles">
    <?php foreach ($personnes_experimentateur as $personne): ?>
        <?php
            if ($personne['Etat'] == 3) {
                $etat = 'ADMIN';
            } elseif ($personne['Etat'] == 2) {
                $etat = 'Chercheur';
            } else {
                $etat = 'Etudiant';
            }
        ?>
        <option value="<?= htmlspecialchars($personne['Prenom'] . ' ' . $personne['Nom']); ?>">
            <?= $etat ?>
        </option>
    <?php endforeach; ?>
</datalist>

                <div class="liste-selectionnes">
                    <?php if (empty($experimentateur_info)): ?>
                        <div class="liste-vide">Aucun experimentateur ajouté</div>
                    <?php else: ?>
                        <?php foreach ($experimentateur_info as $expe): ?>
                            <span class="tag-personne <?= $collab['Etat'] == 1 ? 'tag-etudiant' : ($collab['Etat']==2 ? 'tag-chercheur' : 'tag-admin') ?>">
                                <?= htmlspecialchars($expe['Prenom'] . ' ' . $expe['Nom']) ?>
                                <button type="submit" name="action" value="retirer_experimentateur" class="btn-croix"
                                        onclick="this.form.id_retirer.value=<?= $expe['ID_compte'] ?>; return true;">×</button>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <input type="hidden" id="id_retirer" name="id_retirer" value="">

            <div class="form-actions">
                <a href="page_experience.php?id_projet=<?= $id_experience ?>" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" name="modifier_experience" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>