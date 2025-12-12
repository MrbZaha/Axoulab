<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_modification_projet.php';

// Variables pour les sélections
$gestionnaires_selectionnes = [];
$collaborateurs_selectionnes = [];
$erreur = null;
$success = null;

// Vérifications initiales
if ($id_projet === 0) {
    $erreur = "ID de projet manquant.";
} elseif (!est_gestionnaire($bdd, $id_compte, $id_projet)) {
    $erreur = "Vous n'avez pas les droits pour modifier ce projet.";
} else {
    // Charger les participants actuels (TOUJOURS, même en POST initial)
    $gestionnaires_selectionnes = get_gestionnaires_ids($bdd, $id_projet);
    $collaborateurs_selectionnes = get_collaborateurs_ids($bdd, $id_projet);
}

// Traitement du formulaire — n'exécuter que pour les POST provenant du formulaire interne
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erreur && (isset($_POST['modifier_projet']) || isset($_POST['action']) || isset($_POST['gestionnaires_ids']) || isset($_POST['collaborateurs_ids']))) {
    
    // Récupérer les listes depuis les champs cachés
    $gestionnaires_selectionnes = isset($_POST["gestionnaires_ids"]) && $_POST["gestionnaires_ids"] !== '' 
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["gestionnaires_ids"])))) 
        : [];
    $collaborateurs_selectionnes = isset($_POST["collaborateurs_ids"]) && $_POST["collaborateurs_ids"] !== ''
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["collaborateurs_ids"]))))
        : [];

    // Gestion des actions d'ajout/retrait
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
                            $erreur = "Un étudiant ne peut pas être gestionnaire.";
                        }
                    }
                }
                break;

            case 'retirer_gestionnaire':
                if (!empty($_POST['id_retirer'])) {
                    $gestionnaires_selectionnes = array_values(array_diff($gestionnaires_selectionnes, [intval($_POST['id_retirer'])]));
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
                    $collaborateurs_selectionnes = array_values(array_diff($collaborateurs_selectionnes, [intval($_POST['id_retirer'])]));
                }
                break;
        }
    }

    // Traitement de la modification du projet
    if (isset($_POST['modifier_projet'])) {
        
        $nom_projet = trim($_POST['nom_projet'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $confidentiel = ($_POST['confidentialite'] ?? '') === 'oui' ? 1 : 0;

        // Validation
        if (empty($nom_projet)) {
            $erreur = "Le nom du projet est obligatoire.";
        } elseif (strlen($nom_projet) < 3 || strlen($nom_projet) > 100) {
            $erreur = "Le nom du projet doit contenir entre 3 et 100 caractères.";
        } elseif (strlen($description) < 10 || strlen($description) > 2000) {
            $erreur = "La description doit contenir entre 10 et 2000 caractères.";
        } elseif (empty($gestionnaires_selectionnes)) {
            $erreur = "Le projet doit avoir au moins un gestionnaire.";
        } else {
            try {
                $bdd->beginTransaction();

                // Mise à jour des informations du projet
                $sql = "UPDATE projet 
                        SET Nom_projet = :nom_projet, 
                            Description = :description, 
                            Confidentiel = :confidentiel,
                            Date_de_modification = :date_modif
                        WHERE ID_projet = :id_projet";
                $stmt = $bdd->prepare($sql);
                $stmt->execute([
                    'nom_projet' => $nom_projet,
                    'description' => $description,
                    'confidentiel' => $confidentiel,
                    'date_modif' => date('Y-m-d'),
                    'id_projet' => $id_projet
                ]);

                // Supprimer toutes les anciennes associations
                $sql = "DELETE FROM projet_collaborateur_gestionnaire WHERE ID_projet = :id_projet";
                $stmt = $bdd->prepare($sql);
                $stmt->execute(['id_projet' => $id_projet]);

                // Ajouter les nouveaux gestionnaires
                $sql = "INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) 
                        VALUES (:id_projet, :id_compte, 1)";
                $stmt = $bdd->prepare($sql);
                foreach ($gestionnaires_selectionnes as $id_gestionnaire) {
                    $stmt->execute([
                        'id_projet' => $id_projet,
                        'id_compte' => (int)$id_gestionnaire
                    ]);
                }

                // Ajouter les nouveaux collaborateurs (sauf ceux déjà gestionnaires)
                // Utiliser Statut = 0 pour les collaborateurs (cohérent avec la création)
                $sql = "INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) 
                    VALUES (:id_projet, :id_compte, 0)";
                $stmt = $bdd->prepare($sql);
                foreach ($collaborateurs_selectionnes as $id_collaborateur) {
                    if (!in_array($id_collaborateur, $gestionnaires_selectionnes)) {
                        $stmt->execute([
                            'id_projet' => $id_projet,
                            'id_compte' => (int)$id_collaborateur
                        ]);
                    }
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

// Récupérer les infos complètes des personnes sélectionnées
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

// Charger les données du projet
$projet = $id_projet > 0 && !$erreur ? get_projet_pour_modification($bdd, $id_projet) : null;

// Récupération des listes pour le datalist
$tous_ids_selectionnes = array_merge($gestionnaires_selectionnes, $collaborateurs_selectionnes);
$personnes_gestionnaires = get_personnes_disponibles($bdd, $tous_ids_selectionnes, true);
$personnes_collaborateurs = get_personnes_disponibles($bdd, $tous_ids_selectionnes, false);

$page_title = $projet ? "Modifier " . htmlspecialchars($projet['Nom_projet']) : "Modification de projet";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_modification_projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<div class="project-box">
    <h2>Modifier le projet</h2>

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
            <a href="page_projet.php?id_projet=<?= $id_projet ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour au projet
            </a>
        </div>
    <?php elseif ($projet && !$success): ?>
        <form action="" method="post" id="form-projet">
            <input type="hidden" name="id_projet" value="<?= htmlspecialchars($id_projet) ?>">
            <input type="hidden" name="gestionnaires_ids" value="<?= implode(',', $gestionnaires_selectionnes) ?>">
            <input type="hidden" name="collaborateurs_ids" value="<?= implode(',', $collaborateurs_selectionnes) ?>">

            <label for="nom_projet">Nom du projet :</label>
            <input type="text" 
                   id="nom_projet" 
                   name="nom_projet" 
                   value="<?= htmlspecialchars($projet['Nom_projet']) ?>" 
                   required>

            <label for="description">Description :</label>
            <textarea id="description" 
                      name="description" 
                      required><?= htmlspecialchars($projet['Description']) ?></textarea>

            <label>Confidentiel :</label>
            <div class="user-type">
                <input type="radio" 
                       name="confidentialite" 
                       value="oui" 
                       id="oui" 
                       <?= $projet['Confidentiel'] ? 'checked' : '' ?>>
                <label for="oui">Oui</label>

                <input type="radio" 
                       name="confidentialite" 
                       value="non" 
                       id="non" 
                       <?= !$projet['Confidentiel'] ? 'checked' : '' ?>>
                <label for="non">Non</label>
            </div>

            <!-- GESTIONNAIRES -->
            <div class="participants-section">
                <label>Gestionnaires (<?= count($gestionnaires_info) ?>) :</label>
                <p class="info-text">Seuls les professeurs/chercheurs et administrateurs peuvent être gestionnaires</p>
                <div class="selection-container">
                    <input type="text" 
                           name="nom_gestionnaire" 
                           list="liste-gestionnaires-disponibles" 
                           placeholder="Rechercher un gestionnaire..." 
                           autocomplete="off">
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
                <label>Collaborateurs (<?= count($collaborateurs_info) ?>) :</label>
                <p class="info-text">Tous les utilisateurs validés peuvent être collaborateurs</p>
                <div class="selection-container">
                    <input type="text" 
                           name="nom_collaborateur" 
                           list="liste-collaborateurs-disponibles" 
                           placeholder="Rechercher un collaborateur..." 
                           autocomplete="off">
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

            <div class="form-actions">
                <a href="page_projet.php?id_projet=<?= $id_projet ?>" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" name="modifier_projet" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>