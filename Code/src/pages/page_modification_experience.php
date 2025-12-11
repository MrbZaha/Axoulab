<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_modification_projet.php';

function get_info_experience(PDO $bdd, int $id_experience): ?array {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Nom,
            e.Description,
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Validation,
            e.Resultat,
            e.Statut_experience,
            p.ID_projet,
            p.Nom_projet
        FROM experience e
        LEFT JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        LEFT JOIN projet p ON pe.ID_projet = p.ID_projet
        WHERE e.ID_experience = :id_experience
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    $experience = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $experience ?: null;
}


/**
 * Vérifie si l'utilisateur a le droit d'accéder à une expérience donnée.
 *
 * Cette fonction implémente une logique de contrôle d'accès à trois niveaux :
 * 1. L'utilisateur est directement expérimentateur de cette expérience → accès autorisé
 * 2. L'expérience est liée à un projet non confidentiel → accès autorisé à tous
 * 3. L'expérience est liée à un projet confidentiel → accès uniquement aux gestionnaires du projet
 *
 * Elle vérifie d'abord si l'utilisateur est expérimentateur, puis, si ce n'est pas
 * le cas, remonte au projet parent pour appliquer les règles de confidentialité.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte utilisateur dont on vérifie les droits
 * @param int $id_experience ID de l'expérience à laquelle on souhaite accéder
 *
 * @return str 'modification' si la personne est experimentateur de l'experience ou gestionnaire du projet lié
 *             'acces' si elle est collaborateur du projet ou que le projet n'est pas confidentiel
 *             'none' dans les cas restants
 */
function verifier_acces_experience(PDO $bdd, int $id_compte, int $id_experience): string {
    // Vérifier si l'utilisateur est expérimentateur
    $sql_experimentateur = "
        SELECT 1 
        FROM experience_experimentateur 
        WHERE ID_experience = :id_experience 
        AND ID_compte = :id_compte
    ";
    $stmt = $bdd->prepare($sql_experimentateur);
    $stmt->execute([
        'id_experience' => $id_experience,
        'id_compte' => $id_compte
    ]);
    
    if ($stmt->fetch()) {
        return 'modification'; // L'utilisateur est expérimentateur
    }
    
    // Sinon, vérifier via le projet lié
    $sql_projet = "
        SELECT 
            p.Confidentiel,
            pcg.Statut
        FROM experience e
        LEFT JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        LEFT JOIN projet p ON pe.ID_projet = p.ID_projet
        LEFT JOIN projet_collaborateur_gestionnaire pcg 
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE e.ID_experience = :id_experience
    ";
    
    $stmt2 = $bdd->prepare($sql_projet);
    $stmt2->execute([
        'id_experience' => $id_experience,
        'id_compte' => $id_compte
    ]);
    
    $result = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return 'none'; // Pas de projet lié ou projet inexistant
    }

    // Si personne gestionnaire -> droit de modification
    else if (isset($result['Statut']) && (int)$result['Statut'] === 1) {
        return 'modification';
    }

    // Si personne collaborateur -> droit d'accès
    else if (isset($result['Statut']) && (int)$result['Statut'] === 0) {
        return 'acces';
    }
    
    // Si projet non confidentiel → accessible
    else if ((int)$result['Confidentiel'] === 0) {
        return 'acces';
    }
    
    else {
        return 'none';
    }
}   


/**
 * Récupère la liste des expérimentateurs assignés à une expérience.
 *
 * Cette fonction recherche tous les comptes liés à l'expérience via la table
 * de liaison experience_experimentateur, puis formate les résultats sous forme
 * de chaînes "Prénom Nom". La liste est triée alphabétiquement par nom puis prénom.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_experience ID de l'expérience dont on souhaite les expérimentateurs
 *
 * @return array Tableau de chaînes de caractères au format "Prénom Nom".
 *               Exemple : ["Jean Dupont", "Marie Martin"]
 *               Retourne un tableau vide si aucun expérimentateur n'est assigné
 */
function get_experimentateurs(PDO $bdd, int $id_experience): array {
    $sql = "
        SELECT c.Prenom, c.Nom
        FROM experience_experimentateur ee
        JOIN compte c ON ee.ID_compte = c.ID_compte
        WHERE ee.ID_experience = :id_experience
        ORDER BY c.Nom, c.Prenom
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    $experimentateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $experimentateurs[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $experimentateurs;
}


// Vérifications initiales
if ($id_experience === 0) {
    $erreur = "ID d'experience manquant.";
} elseif (!est_gestionnaire($bdd, $id_compte, $id_projet) || !est_experimentateur($bdd, $id_compte, $id_experience)) {
    $erreur = "Vous n'avez pas les droits pour modifier ce projet.";
} else {
    // Charger les experiemntataeur actuels
    $experimentateurs_selectionnes = get_experimentateurs($bdd, $id_experimentateur);
}

// Récupérer les infos complètes des personnes sélectionnées
$experimentateurs_info = [];

if (!empty($experimentateurs_selectionnes)) {
    $placeholders = implode(',', array_fill(0, count($experimentateurs_selectionnes), '?'));
    $stmt = $bdd->prepare("SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE ID_compte IN ($placeholders)");
    $stmt->execute($experimentateurs_selectionnes);
    $experimentateurs_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erreur) {
    
    // Récupérer les listes depuis les champs cachés
    $experimentateurs_selectionnes = isset($_POST["experimentateurs_ids"]) && $_POST["experimentateurs_ids"] !== '' 
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["experimentateurs_ids"])))) 
        : [];

    // Gestion des actions d'ajout/retrait
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_experimentateurs':
                if (!empty($_POST['nom_experimentateurs'])) {
                    $id = trouver_id_par_nom_complet($bdd, $_POST['nom_experimentateurs']);
                    if ($id && !in_array($id, $experimentateurs_selectionnes)) {
                        $stmt = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
                        $stmt->execute([$id]);
                        $etat = $stmt->fetchColumn();
                        if ($etat > 1) {
                            $experimentateurs_selectionnes[] = $id;
                        } else {
                            $erreur = "Un étudiant ne peut pas être gestionnaire.";
                        }
                    }
                }
                break;

            case 'retirer_experimentateurs':
                if (!empty($_POST['id_retirer'])) {
                    $gestionnaires_selectionnes = array_values(array_diff($gestionnaires_selectionnes, [intval($_POST['id_retirer'])]));
                }
                break;
        }
    }

    // Traitement de la modification du projet
    if (isset($_POST['modifier_experience'])) {
        $nom_experience = trim($_POST['nom_experience'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date_reservation = 

        // Validation
        if (empty($nom_experience)) {
            $erreur = "Le nom du projet est obligatoire.";
        } elseif (strlen($nom_experience) < 3 || strlen($nom_experience) > 100) {
            $erreur = "Le nom du projet doit contenir entre 3 et 100 caractères.";
        } elseif (strlen($description) < 10 || strlen($description) > 2000) {
            $erreur = "La description doit contenir entre 10 et 2000 caractères.";
        } elseif (empty($experimentateurs_selectionnes)) {
            $erreur = "Le projet doit avoir au moins un gestionnaire.";
        } else {
            try {
                $bdd->beginTransaction();

                // Mise à jour des informations du projet
                $sql = "UPDATE experience 
                        SET Nom = :nom_projet, 
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