<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_creation_projet.php';

$message = "";
$gestionnaires_selectionnes = [];
$collaborateurs_selectionnes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $gestionnaires_selectionnes = isset($_POST["gestionnaires_ids"]) && $_POST["gestionnaires_ids"] !== '' 
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["gestionnaires_ids"])))) 
        : [];
    $collaborateurs_selectionnes = isset($_POST["collaborateurs_ids"]) && $_POST["collaborateurs_ids"] !== ''
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["collaborateurs_ids"]))))
        : [];

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


// ======================= CRÉATION DU PROJET =======================
    if (isset($_POST["creer_projet"])) {
        $nom_projet = trim($_POST["nom_projet"] ?? '');
        $description = trim($_POST["description"] ?? '');
        $confidentialite = ($_POST["confidentialite"] ?? '') === 'oui' ? 1 : 0;

        $erreurs = verifier_champs_projet($nom_projet, $description);

        // Vérifier l'état du créateur
        $stmtEtat = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
        $stmtEtat->execute([$_SESSION["ID_compte"]]);
        $etatCreateur = $stmtEtat->fetchColumn();

        // Étudiant : doit avoir au moins un gestionnaire valide
        if ($etatCreateur == 1) {
            if (empty($gestionnaires_selectionnes)) {
                $erreurs[] = "Un chercheur/gestionnaire doit être renseigné pour valider la création.";
            } else {
                $placeholders = implode(',', array_fill(0, count($gestionnaires_selectionnes), '?'));
                $stmt = $bdd->prepare("SELECT COUNT(*) FROM compte WHERE ID_compte IN ($placeholders) AND Etat > 1");
                $stmt->execute($gestionnaires_selectionnes);
            if ((int)$stmt->fetchColumn() == 0) {
                $erreurs[] = "Au moins un gestionnaire doit être chercheur ou administrateur.";
            }
        }
    }

        if (!empty($erreurs)) {
            $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
        } else {
            try {
                if ($etatCreateur == 1) {
                // ---------------------- ÉTUDIANT ----------------------
                // Créer le projet avec Validation = 0 (en attente)
                    $id_projet = creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"], 0);
                
                // L'étudiant créateur devient COLLABORATEUR sur son propre projet
                    $tous_collaborateurs = array_unique(array_merge($collaborateurs_selectionnes, [$_SESSION["ID_compte"]]));
                
                // Ajouter les participants
                    ajouter_participants($bdd, $id_projet, $gestionnaires_selectionnes, $tous_collaborateurs);
                
                // Envoyer notification type 11 UNIQUEMENT aux gestionnaires (pas aux collaborateurs)
                    $donnees = [
                        'ID_projet' => $id_projet,
                        'Nom_projet' => $nom_projet
                    ];
                
                // On retire l'étudiant créateur de la liste des destinataires (si jamais il s'est mis)
                    $destinataires_gestionnaires = array_values(array_diff($gestionnaires_selectionnes, [$_SESSION["ID_compte"]]));
                
                    if (!empty($destinataires_gestionnaires)) {
                        envoyerNotification($bdd, 11, $_SESSION["ID_compte"], $donnees, $destinataires_gestionnaires);
                    }  
                    
                       // Notification aux collaborateurs ajoutés (type 16)
                    $dest_collab = array_values(array_diff($tous_collaborateurs, [$_SESSION["ID_compte"]]));
                    if (!empty($dest_collab)) {
                        envoyerNotification($bdd, 16, $_SESSION["ID_compte"], $donnees, $dest_collab);
                    }
                    
                    $message = "<p style='color:green;'>Le projet a été créé et proposé aux gestionnaires pour validation.</p>";
                
                    header("Location: page_projet.php?id_projet=" . $id_projet);
                    exit();

                } else {
                // ---------------------- CHERCHEUR/ADMIN ----------------------
                // Création directe avec Validation = 1 (validé d'office)
                $id_projet = creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"], 1);
                
                // Le créateur devient GESTIONNAIRE sur son propre projet
                $tous_gestionnaires = array_unique(array_merge($gestionnaires_selectionnes, [$_SESSION["ID_compte"]]));
                
                ajouter_participants($bdd, $id_projet, $tous_gestionnaires, $collaborateurs_selectionnes);

                $donnees = [
                    'ID_projet' => $id_projet,
                    'Nom_projet' => $nom_projet
                ];

                // Notifier les autres gestionnaires (type 11 : demande de validation de participation)
                $gestionnaires_dest = array_values(array_diff($gestionnaires_selectionnes, [$_SESSION["ID_compte"]]));
                if (!empty($gestionnaires_dest)) {
                    envoyerNotification($bdd, 11, $_SESSION["ID_compte"], $donnees, $gestionnaires_dest);
                }

                // Notifier les collaborateurs (type 16 : ajout collaborateur - notification simple)
                $collaborateurs_dest = array_values(array_diff($collaborateurs_selectionnes, [$_SESSION["ID_compte"]]));
                if (!empty($collaborateurs_dest)) {
                    envoyerNotification($bdd, 16, $_SESSION["ID_compte"], $donnees, $collaborateurs_dest);
                }

                header("Location: page_projet.php?id_projet=" . $id_projet);
                exit();
            }

            // Réinitialiser
            $gestionnaires_selectionnes = [];
            $collaborateurs_selectionnes = [];

        }catch (Exception $e) {
            $message = "<p style='color:red;'>Erreur lors de la création du projet : " . htmlspecialchars($e->getMessage()) . "</p>";
            error_log("Erreur création projet: " . $e->getMessage());
        }
    }
}
}

// ======================= Récupération des listes pour le datalist =======================
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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