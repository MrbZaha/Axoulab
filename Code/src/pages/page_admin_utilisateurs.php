<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

$message = "";  // Variable globale pour les messages

////////////////////////////////////////////////////////////////////////////////
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// On vérifie si l'utilisateur a les droits pour accéder à cette page
if (est_admin($bdd, $_SESSION["email"])){
    // Le code peut poursuivre
}
else {
    // On change le layout de la page et on invite l'utilisateur à revenir sur la page précédente
    layout_erreur();
}

///////////////////////////////////////////////////////////////////////////////
// On récupère la liste des utilisateurs
$utilisateurs = array_values(get_utilisateurs($bdd));

// On set la page que l'on observe
$items_par_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_pages = create_page($utilisateurs, $items_par_page);

// Vérification que la page demandée existe
if ($page > $total_pages) $page = $total_pages;

///////////////////////////////////////////////////////////////////////////////
// Affichage des messages de confirmation selon les paramètres GET
if (isset($_GET['suppression']) && $_GET['suppression'] === 'ok') {
    $message = afficher_popup("Suppression réussie", "L'utilisateur a été supprimé avec succès.", "success");
}
if (isset($_GET['modification']) && $_GET['modification'] === 'ok') {
    $message = afficher_popup("Modification réussie", "Les informations de l'utilisateur ont été mises à jour.", "success");
}
if (isset($_GET['accept']) && $_GET['accept'] === 'ok') {
    $message = afficher_popup("Validation réussie", "Le compte utilisateur a été validé.", "success");
}
if (isset($_GET['erreur'])) {
    $message = afficher_popup("Erreur", "Une erreur est survenue : " . htmlspecialchars($_GET['erreur']), "error");
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à supprimer un utilisateur
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        supprimer_utilisateur($bdd, $id_utilisateur);
        header("Location: page_admin_utilisateurs.php?suppression=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à modifier les informations d'un utilisateur
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $resultat = modifier_utilisateur($bdd, intval($_POST['id']));
    if ($resultat === true) {
        header("Location: page_admin_utilisateurs.php?modification=ok");
    } else {
        header("Location: page_admin_utilisateurs.php?erreur=" . urlencode($resultat));
    }
    exit;
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à accepter la création d'un compte
if (isset($_GET['action']) && $_GET['action'] === 'accepter') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        accepter_utilisateur($bdd, $id_utilisateur);
        header("Location: page_admin_utilisateurs.php?accept=ok");
        exit;
    }
}

// =======================  FONCTION POPUP =======================
function afficher_popup($titre, $texte, $type = "success") {
    $classe = ($type === "error") ? "popup-error" : "popup-success";
    return '
    <div class="popup-overlay" id="popup">
        <div class="popup-box ' . $classe . '">
            <h3>' . htmlspecialchars($titre) . '</h3>
            <p>' . htmlspecialchars($texte) . '</p>
            <a href="page_admin_utilisateurs.php" class="popup-close">Fermer</a>
        </div>
    </div>';
}

// =======================  Traitement des informations modifiées  =======================
function modifier_utilisateur($bdd, $id){
    if (isset($_POST["nom_$id"], $_POST["prenom_$id"], $_POST["date_$id"], $_POST["etat_$id"], $_POST["email_$id"])) {
        $nom = trim($_POST["nom_$id"]);
        $prenom = trim($_POST["prenom_$id"]);
        $datedenaissance = trim($_POST["date_$id"]);
        $etat = intval($_POST["etat_$id"]);
        $email = trim($_POST["email_$id"]);

        try {
            $stmt = $bdd->prepare("
                UPDATE compte
                SET Nom = ?,
                    Prenom = ?,
                    Date_de_naissance = ?,
                    Email = ?,
                    Etat = ?
                WHERE ID_compte = ?
            ");
            $stmt->execute([$nom, $prenom, $datedenaissance, $email, $etat, $id]);
            return true;
        }
        catch (Exception $e) {
            return $e->getMessage();
        }
    }
    return "Données manquantes";
}

// =======================  Fonction pour récupérer l'ensemble des utilisateurs =======================
function get_utilisateurs($bdd) {
    $sql_utilisateurs = "
        SELECT ID_compte,
        Nom,
        Prenom,
        Date_de_naissance,
        Email,
        Etat,
        validation
        FROM compte
    ";  
    $stmt = $bdd->prepare($sql_utilisateurs);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =======================  Fonction pour afficher l'ensemble des utilisateurs =======================
function afficher_utilisateurs_pagines($utilisateurs, $page_actuelle, $items_par_page, $bdd) {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $utilisateur_page = array_slice($utilisateurs, $debut, $items_par_page);
    ?>
        <?php if (empty($utilisateur_page)): ?>
            <p class="no-experiences">Aucun utilisateur à afficher</p>
        <?php else: ?>

    <table class="whole_table">
        <thead class="tablehead">
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Date de naissance</th>
                <th>Email</th>
                <th>Statut</th>
                <th>Validation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    <?php
        foreach ($utilisateur_page as $user):
            $id  = htmlspecialchars($user['ID_compte']);
            $nom = htmlspecialchars($user['Nom']);
            $prenom = htmlspecialchars($user['Prenom']);
            $dateN = htmlspecialchars($user['Date_de_naissance']);
            $email = htmlspecialchars($user['Email']);
            $etat = htmlspecialchars($user['Etat']);
            $validation = htmlspecialchars($user['validation']);
    ?>
        <tr>
            <form action="page_admin_utilisateurs.php" method="POST">
                <td>
                    <input type="text" name="nom_<?= $id ?>" value="<?= $nom ?>" class="input-user">
                </td>
                <td>
                    <input type="text" name="prenom_<?= $id ?>" value="<?= $prenom ?>" class="input-user">
                </td>
                <td>
                    <input type="date" name="date_<?= $id ?>" value="<?= $dateN ?>" class="input-user">
                </td>
                <td>
                    <input type="email" name="email_<?= $id ?>" value="<?= $email ?>" class="input-user">
                </td>
                <td>
                    <select name="etat_<?= $id ?>" class="select-user">
                        <option value="1" <?= $etat == 1 ? "selected" : "" ?>>Étudiant</option>
                        <option value="2" <?= $etat == 2 ? "selected" : "" ?>>Chercheur</option>
                        <option value="3" <?= $etat == 3 ? "selected" : "" ?>>Administrateur</option>
                    </select>
                </td>
                <td>
                    <?php if ($validation != 1): ?>
                        <a class="btn btnVert"
                            href="page_admin_utilisateurs.php?action=accepter&id=<?= $id ?>">
                            Valider
                        </a>
                    <?php else: ?>
                        <span class="badge-valide">Validé(e)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions-cell">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button class="btn btnViolet" type="submit">Modifier</button>
                        <?php if ($_SESSION['ID_compte'] != $id): ?>
                            <a class="btn btnRouge"
                                href="page_admin_utilisateurs.php?action=supprimer&id=<?= $id ?>">
                                Supprimer
                            </a>
                        <?php else: ?>
                            <span class="btn btnGris">Supprimer</span>
                        <?php endif; ?>
                    </div>
                </td>
            </form>
        </tr>
    <?php endforeach; ?>
        </tbody>
    </table>
        <?php endif; ?>
    <?php
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8"/>
        <!--permet d'uniformiser le style sur tous les navigateurs-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
        <link rel="stylesheet" href="../css/page_mes_experiences.css">
        <link rel="stylesheet" href="../css/page_admin_utilisateurs_materiel.css">
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/boutons.css">
        <link rel="stylesheet" href="../css/popup.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title>Utilisateurs</title>
    </head>
    <body>
    
    <?php 
    // Affiche la popup si elle existe
    echo $message;
    ?>

    <?php afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]); ?>

    <div class=bandeau>
        <p><?php echo "Utilisateurs"; ?></p>
    </div> 

    <div class="back_square">
        <section class="section-experiences">
            <h2>Utilisateurs (<?= count($utilisateurs) ?>)</h2>
            <?php afficher_utilisateurs_pagines($utilisateurs, $page, $items_par_page, $bdd); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>
    </div>

    <?php afficher_Bandeau_Bas(); ?>
    </body>
</html>