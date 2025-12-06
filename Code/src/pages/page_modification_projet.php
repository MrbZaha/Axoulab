<?php
include_once "../back_php/fonctions_site_web.php";

$bdd = connectBDD();
verification_connexion($bdd);

$id_compte = $_SESSION['ID_compte'];
$id_projet = isset($_GET['id_projet']) ? (int)$_GET['id_projet'] : 0;

$erreur = null;
$success = null;

// Vérifier que l'utilisateur est gestionnaire du projet
/**
 * Vérifie si un compte est gestionnaire d’un projet.
 *
 * Effectue la vérification suivante :
 *  - Recherche dans la table projet_collaborateur_gestionnaire si l'utilisateur
 *    possède le statut 1 (gestionnaire) pour le projet donné.
 *
 * Retourne :
 *   - true  : si l'utilisateur est gestionnaire
 *   - false : sinon
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte à vérifier
 * @param int $id_projet ID du projet concerné
 * @return bool
 */
function est_gestionnaire(PDO $bdd, int $id_compte, int $id_projet): bool {
    $sql = "SELECT Statut FROM projet_collaborateur_gestionnaire 
            WHERE ID_projet = :id_projet AND ID_compte = :id_compte AND Statut = 1";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet, 'id_compte' => $id_compte]);
    return $stmt->fetch() !== false;
}

/**
 * Récupère les informations essentielles d’un projet pour une modification.
 *
 * Données récupérées :
 *   - ID_projet
 *   - Nom_projet
 *   - Description
 *   - Confidentiel
 *
 * Retourne :
 *   - Un tableau associatif contenant les données du projet
 *   - null si le projet n’existe pas
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet ID du projet à récupérer
 * @return array|null
 */
function get_projet_pour_modification(PDO $bdd, int $id_projet): ?array {
    $sql = "SELECT ID_projet, Nom_projet, Description, Confidentiel 
            FROM projet WHERE ID_projet = :id_projet";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Récupère la liste des personnes disponibles pour être ajoutées à un projet.
 *
 * Fonctionnement :
 *  - Sélectionne tous les comptes validés
 *  - Peut exclure certains IDs (gestionnaires ou collaborateurs déjà présents)
 *  - Peut filtrer pour n’inclure que les non-étudiants (Etat > 1)
 *  - Trie par nom puis prénom
 *
 * Retourne :
 *   - Un tableau de comptes disponibles sous forme associative
 *
 * @param PDO   $bdd Connexion PDO
 * @param array $ids_exclus Liste d’IDs à exclure du résultat
 * @param bool  $seulement_non_etudiants Si true → filtre Etat > 1
 * @return array
 */
function get_personnes_disponibles(PDO $bdd, array $ids_exclus = [], bool $seulement_non_etudiants = false): array {
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

/**
 * Trouve l'ID d’un compte à partir d’un nom complet.
 *
 * Fonctionnement :
 *   - Décompose la chaîne "Prénom Nom"
 *   - Vérifie que les deux parties existent
 *   - Recherche un compte validé correspondant
 *
 * Retourne :
 *   - ID_compte si trouvé
 *   - null sinon
 *
 * @param PDO    $bdd Connexion PDO
 * @param string $nom_complet Chaîne "Prénom Nom"
 * @return int|null
 */
function trouver_id_par_nom_complet(PDO $bdd, string $nom_complet): ?int {
    $parts = explode(' ', trim($nom_complet), 2);
    if (count($parts) < 2) return null;

    $prenom = trim($parts[0]);
    $nom = trim($parts[1]);

    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE Prenom = ? AND Nom = ? AND validation = 1");
    $stmt->execute([$prenom, $nom]);
    return $stmt->fetchColumn() ?: null;
}

/**
 * Récupère la liste des IDs des gestionnaires d’un projet.
 *
 * Sélectionne dans la table projet_collaborateur_gestionnaire :
 *   - Tous les comptes avec Statut = 1 (gestionnaires)
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet ID du projet
 * @return array Liste des IDs des gestionnaires
 */
function get_gestionnaires_ids(PDO $bdd, int $id_projet): array {
    $sql = "SELECT ID_compte FROM projet_collaborateur_gestionnaire 
            WHERE ID_projet = :id_projet AND Statut = 1";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Récupère la liste des IDs des collaborateurs d’un projet.
 *
 * Sélectionne dans projet_collaborateur_gestionnaire :
 *   - Tous les comptes avec Statut = 0 (collaborateurs)
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet ID du projet
 * @return array Liste des IDs collaborateurs
 */
function get_collaborateurs_ids(PDO $bdd, int $id_projet): array {
    $sql = "SELECT ID_compte FROM projet_collaborateur_gestionnaire 
            WHERE ID_projet = :id_projet AND Statut = 0";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


// Variables pour les sélections
$gestionnaires_selectionnes = [];
$collaborateurs_selectionnes = [];

// Vérifications initiales
if ($id_projet === 0) {
    $erreur = "ID de projet manquant.";
} elseif (!est_gestionnaire($bdd, $id_compte, $id_projet)) {
    $erreur = "Vous n'avez pas les droits pour modifier ce projet.";
} else {
    // Charger les participants actuels
    $gestionnaires_selectionnes = get_gestionnaires_ids($bdd, $id_projet);
    $collaborateurs_selectionnes = get_collaborateurs_ids($bdd, $id_projet);
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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erreur) {
    
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
                $sql = "INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) 
                        VALUES (:id_projet, :id_compte, 2)";
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

// Charger les données
$projet = $id_projet > 0 && !$erreur ? get_projet_pour_modification($bdd, $id_projet) : null;

// Récupération des listes pour le datalist
$tous_ids_selectionnes = array_merge($gestionnaires_selectionnes, $collaborateurs_selectionnes);
$personnes_gestionnaires = get_personnes_disponibles($bdd, $tous_ids_selectionnes, true);
$personnes_collaborateurs = get_personnes_disponibles($bdd, $tous_ids_selectionnes, false);

// Récupérer les infos complètes des personnes sélectionnées

$page_title = $projet ? "Modifier " . htmlspecialchars($projet['Nom_projet']) : "Modification de projet";
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
                <label>Gestionnaires :</label>
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
                <label>Collaborateurs :</label>
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