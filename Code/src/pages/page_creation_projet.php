<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_creation_projet.php';

$bdd = connectBDD();
// On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

$message = "";
$gestionnaires_selectionnes = [];
$collaborateurs_selectionnes = [];

/* ============================================================
   TRAITEMENT DU FORMULAIRE
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --- Récupération des IDs déjà sélectionnés --- */
    $gestionnaires_selectionnes = !empty($_POST["gestionnaires_ids"])
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["gestionnaires_ids"]))))
        : [];

    $collaborateurs_selectionnes = !empty($_POST["collaborateurs_ids"])
        ? array_values(array_filter(array_map('intval', explode(',', $_POST["collaborateurs_ids"]))))
        : [];

    /* --- Actions du formulaire --- */
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

            /* --- Ajouter gestionnaire --- */
case 'ajouter_gestionnaire':
    if (!empty($_POST['nom_gestionnaire'])) {

        // On passe DIRECTEMENT la valeur du datalist (avec nom + rôle + email)
        $id = trouver_id_par_email($bdd, $_POST['nom_gestionnaire']);

        if (
            $id &&
            !in_array($id, $gestionnaires_selectionnes, true) &&
            !in_array($id, $collaborateurs_selectionnes, true)
        ) {
            $stmt = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
            $stmt->execute([$id]);
            $etat = (int) $stmt->fetchColumn();

            if ($etat > 1) {
                $gestionnaires_selectionnes[] = $id;
            } else {
                $message = "<p style='color:orange;'>Un étudiant ne peut pas être gestionnaire.</p>";
            }
        }
    }
    break;


            /* --- Retirer gestionnaire --- */
            case 'retirer_gestionnaire':
                if (!empty($_POST['id_retirer'])) {
                    $gestionnaires_selectionnes = array_values(array_diff($gestionnaires_selectionnes, [intval($_POST['id_retirer'])]));
                }
                break;

            /* --- Ajouter collaborateur --- */
case 'ajouter_collaborateur':
    if (!empty($_POST['nom_collaborateur'])) {

        // On passe directement la valeur du datalist (nom + rôle + email)
        $id = trouver_id_par_email($bdd, $_POST['nom_collaborateur']);

        if (
            $id &&
            !in_array($id, $collaborateurs_selectionnes, true) &&
            !in_array($id, $gestionnaires_selectionnes, true)
        ) {
            $collaborateurs_selectionnes[] = $id;
        }
    }
    break;

            /* --- Retirer collaborateur --- */
            case 'retirer_collaborateur':
                if (!empty($_POST['id_retirer'])) {
                    $collaborateurs_selectionnes = array_values(array_diff($collaborateurs_selectionnes, [intval($_POST['id_retirer'])]));
                }
                break;
        }
    }

    /* ============================================================
       CRÉATION DU PROJET
       ============================================================ */
    if (isset($_POST["creer_projet"])) {

        $nom_projet = trim($_POST["nom_projet"] ?? '');
        $description = trim($_POST["description"] ?? '');
        $confidentialite = ($_POST["confidentialite"] ?? '') === 'oui' ? 1 : 0;

        $erreurs = verifier_champs_projet($nom_projet, $description);

        /* --- Vérification du créateur --- */
        $stmtEtat = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
        $stmtEtat->execute([$_SESSION["ID_compte"]]);
        $etatCreateur = $stmtEtat->fetchColumn();

        /* --- Étudiant : doit avoir un gestionnaire valide --- */
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

        /* --- Si erreurs --- */
        if (!empty($erreurs)) {
            $message = "<p style='color:red;'>" . implode("<br>", $erreurs) . "</p>";
        }

        /* --- Sinon création du projet --- */
        else {
            try {

                /* --- Étudiant --- */
                if ($etatCreateur == 1) {

                    $id_projet = creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"], 0);

                    $tous_collaborateurs = array_unique(array_merge($collaborateurs_selectionnes, [$_SESSION["ID_compte"]]));
                    
                    ajouter_participants($bdd, $id_projet, $gestionnaires_selectionnes, $tous_collaborateurs);

                    $donnees = ['ID_projet' => $id_projet, 'Nom_projet' => $nom_projet];

                    $dest_gest = array_values(array_diff($gestionnaires_selectionnes, [$_SESSION["ID_compte"]]));
                    if (!empty($dest_gest)) envoyerNotification($bdd, 11, $_SESSION["ID_compte"], $donnees, $dest_gest);

                    $dest_collab = array_values(array_diff($tous_collaborateurs, [$_SESSION["ID_compte"]]));
                    if (!empty($dest_collab)) envoyerNotification($bdd, 16, $_SESSION["ID_compte"], $donnees, $dest_collab);

                    header("Location: page_projet.php?id_projet=" . $id_projet);
                    exit();
                }

                /* --- Chercheur / Admin --- */
                else {

                    $id_projet = creer_projet($bdd, $nom_projet, $description, $confidentialite, $_SESSION["ID_compte"], 1);

                    $tous_gestionnaires = array_unique(array_merge($gestionnaires_selectionnes, [$_SESSION["ID_compte"]]));

                    ajouter_participants($bdd, $id_projet, $tous_gestionnaires, $collaborateurs_selectionnes);

                    $donnees = ['ID_projet' => $id_projet, 'Nom_projet' => $nom_projet];

                    $dest_gest = array_values(array_diff($gestionnaires_selectionnes, [$_SESSION["ID_compte"]]));
                    if (!empty($dest_gest)) envoyerNotification($bdd, 11, $_SESSION["ID_compte"], $donnees, $dest_gest);

                    $dest_collab = array_values(array_diff($collaborateurs_selectionnes, [$_SESSION["ID_compte"]]));
                    if (!empty($dest_collab)) envoyerNotification($bdd, 16, $_SESSION["ID_compte"], $donnees, $dest_collab);

                    header("Location: page_projet.php?id_projet=" . $id_projet);
                    exit();
                }

            } catch (Exception $e) {
                $message = "<p style='color:red;'>Erreur lors de la création du projet : " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
}

/* ============================================================
   RÉCUPÉRATION DES PERSONNES DISPONIBLES
   ============================================================ */
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
    <title>Créer un projet</title>
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_creation_projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"]); ?>

<div class="project-box">
    <h2>Créer un projet</h2>

    <?= $message ?>

    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <input type="hidden" name="gestionnaires_ids" value="<?= implode(',', $gestionnaires_selectionnes) ?>">
        <input type="hidden" name="collaborateurs_ids" value="<?= implode(',', $collaborateurs_selectionnes) ?>">

        <!-- NOM -->
        <label>Nom du projet :</label>
        <input type="text" name="nom_projet" required value="<?= htmlspecialchars($_POST['nom_projet'] ?? '') ?>">

        <!-- DESCRIPTION -->
        <label>Description :</label>
        <textarea name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

        <!-- CONFIDENTIALITÉ -->
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
            <p class="info-text">Seuls les chercheurs et administrateurs peuvent être gestionnaires</p>

            <div class="selection-container">
                <input type="text" name="nom_gestionnaire" list="liste-gestionnaires" placeholder="Rechercher..." autocomplete="off">
                <button type="submit" name="action" value="ajouter_gestionnaire" class="btn-ajouter">Ajouter</button>
            </div>

<datalist id="liste-gestionnaires">
    <?php foreach ($personnes_gestionnaires as $personne): ?>
        <?php
            $nom = htmlspecialchars($personne['Prenom'] . ' ' . $personne['Nom']);
            $email = htmlspecialchars($personne['Email']);

            $role = ($personne['Etat'] == 3) ? 'ADMIN' : 'Chercheur';

            // Valeur envoyée en POST
            $valeur = "$nom ($role) — $email";
        ?>
        <option value="<?= $valeur ?>"></option>
    <?php endforeach; ?>
</datalist>


            <div class="liste-selectionnes">
                <?php if (empty($gestionnaires_info)): ?>
                    <div class="liste-vide">Aucun gestionnaire ajouté</div>
                <?php else: ?>
                    <?php foreach ($gestionnaires_info as $g): ?>
                        <?php
                            $role = $g['Etat'] == 3 ? 'Admin' : 'Chercheur';
                            $badge = $g['Etat'] == 3 ? 'badge-admin' : 'badge-chercheur';
                            $tag = $g['Etat'] == 3 ? 'tag-admin' : 'tag-chercheur';
                        ?>
                        <span class="tag-personne <?= $tag ?>">
                            <?= htmlspecialchars($g['Prenom'] . ' ' . $g['Nom']) ?>
                            <span class="badge <?= $badge ?>"><?= $role ?></span>
                            <button type="submit" name="action" value="retirer_gestionnaire" class="btn-croix"
                                onclick="this.form.id_retirer.value=<?= $g['ID_compte'] ?>;">×</button>
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
                <input type="text" name="nom_collaborateur" list="liste-collaborateurs" placeholder="Rechercher..." autocomplete="off">
                <button type="submit" name="action" value="ajouter_collaborateur" class="btn-ajouter">Ajouter</button>
            </div>

<datalist id="liste-collaborateurs">
    <?php foreach ($personnes_collaborateurs as $personne): ?>
        <?php
            $nom = htmlspecialchars($personne['Prenom'] . ' ' . $personne['Nom']);
            $email = htmlspecialchars($personne['Email']);

            $role = match ($personne['Etat']) {
                1 => 'Étudiant',
                2 => 'Chercheur',
                default => 'ADMIN',
            };

            // Ce que l'utilisateur voit ET ce qui est envoyé en POST
            $valeur = "$nom ($role) — $email";
        ?>
        <option value="<?= $valeur ?>"></option>
    <?php endforeach; ?>
</datalist>


            <div class="liste-selectionnes">
                <?php if (empty($collaborateurs_info)): ?>
                    <div class="liste-vide">Aucun collaborateur ajouté</div>
                <?php else: ?>
                    <?php foreach ($collaborateurs_info as $c): ?>
                        <?php
                            $role = $c['Etat'] == 1 ? 'Étudiant' : ($c['Etat'] == 2 ? 'Chercheur' : 'Admin');
                            $badge = $c['Etat'] == 1 ? 'badge-etudiant' : ($c['Etat'] == 2 ? 'badge-chercheur' : 'badge-admin');
                            $tag = $c['Etat'] == 1 ? 'tag-etudiant' : ($c['Etat'] == 2 ? 'tag-chercheur' : 'tag-admin');
                        ?>
                        <span class="tag-personne <?= $tag ?>">
                            <?= htmlspecialchars($c['Prenom'] . ' ' . $c['Nom']) ?>
                            <span class="badge <?= $badge ?>"><?= $role ?></span>
                            <button type="submit" name="action" value="retirer_collaborateur" class="btn-croix"
                                onclick="this.form.id_retirer.value=<?= $c['ID_compte'] ?>;">×</button>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <input type="hidden" id="id_retirer" name="id_retirer">

        <input type="submit" name="creer_projet" value="Créer le projet">

    </form>
</div>

<?php afficher_Bandeau_Bas(); ?>

</body>
</html>
