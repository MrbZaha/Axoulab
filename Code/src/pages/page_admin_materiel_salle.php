<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

$ajouter = false;
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
// On récupère la liste du matériel
$materiel = array_values(get_materiel($bdd));

// On set la page que l'on observe
$items_par_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_pages = create_page($materiel, $items_par_page);

// Vérification que la page demandée existe
if ($page > $total_pages) $page = $total_pages;

///////////////////////////////////////////////////////////////////////////////
// Affichage des messages de confirmation selon les paramètres GET
if (isset($_GET['suppression']) && $_GET['suppression'] === 'ok') {
    $message = afficher_popup("Suppression réussie", "Le matériel a été supprimé avec succès.", "success");
}
if (isset($_GET['modification']) && $_GET['modification'] === 'ok') {
    $message = afficher_popup("Modification réussie", "Les informations ont été mises à jour.", "success");
}
if (isset($_GET['ajout']) && $_GET['ajout'] === 'ok') {
    $message = afficher_popup("Ajout réussi", "Le nouveau matériel a été ajouté.", "success");
}
if (isset($_GET['erreur'])) {
    $message = afficher_popup("Erreur", "Une erreur est survenue : " . htmlspecialchars($_GET['erreur']), "error");
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à supprimer un outil
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    if (isset($_GET['id'])) {
        $id_materiel = intval($_GET['id']);
        supprimer_materiel($bdd, $id_materiel);
        header("Location: page_admin_materiel_salle.php?suppression=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à modifier les informations d'un outil
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    if (isset($_POST['id'])) {
        $resultat = modifier_materiel($bdd, intval($_POST['id']));
        if ($resultat === true) {
            header("Location: page_admin_materiel_salle.php?modification=ok");
        } else {
            header("Location: page_admin_materiel_salle.php?erreur=" . urlencode($resultat));
        }
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à ajouter un outil
if (isset($_GET['action']) && $_GET['action'] === 'ajouter') {
    $ajouter = true;
}

///////////////////////////////////////////////////////////////////////////////
// Après confirmation de l'ajout d'un outil
if (isset($_POST['action']) && $_POST['action'] === 'valider') {
    if (!empty($_POST['salle_new']) && !empty($_POST['materiel_new'])) {
        $resultat = ajouter_materiel($bdd, $_POST['salle_new'], $_POST['materiel_new']);
        if ($resultat === true) {
            header("Location: page_admin_materiel_salle.php?ajout=ok");
        } else {
            header("Location: page_admin_materiel_salle.php?erreur=" . urlencode($resultat));
        }
        exit;
    }
}

// =======================  Suppression d'un utilisateur à partir de son identifiant =======================
function supprimer_materiel($bdd, $id_materiel) {
    $stmt = $bdd->prepare("DELETE FROM salle_materiel WHERE ID_materiel = ?");
    $stmt->execute([$id_materiel]);
}

// =======================  FONCTION POPUP =======================
function afficher_popup($titre, $texte, $type = "success") {
    $classe = ($type === "error") ? "popup-error" : "popup-success";
    return '
    <div class="popup-overlay" id="popup">
        <div class="popup-box ' . $classe . '">
            <h3>' . htmlspecialchars($titre) . '</h3>
            <p>' . htmlspecialchars($texte) . '</p>
            <a href="page_admin_materiel_salle.php" class="popup-close">Fermer</a>
        </div>
    </div>';
}

// =======================  INSÉRER DU NOUVEAU MATÉRIEL =======================
function ajouter_materiel($bdd, $Nom_Salle, $Materiel) {
    try {
        $sql = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?)");
        $sql->execute([$Nom_Salle, $Materiel]);
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

// =======================  Traitement des informations modifiées  =======================
function modifier_materiel($bdd, $id){
    if (isset($_POST["salle"], $_POST["materiel"])) {
        $salle = trim($_POST["salle"]);
        $mat = trim($_POST["materiel"]);

        try {
            $stmt = $bdd->prepare("
                UPDATE salle_materiel
                SET Nom_Salle = ?,
                    Materiel = ?
                WHERE ID_materiel = ?
            ");
            $stmt->execute([$salle, $mat, $id]);
            return true;
        }
        catch (Exception $e) {
            return $e->getMessage();
        }
    }
    return "Données manquantes";
}

// =======================  Fonction pour récupérer l'ensemble du matériel =======================
function get_materiel($bdd) {
    $sql_materiel = "
        SELECT ID_materiel,
        Nom_salle,
        Materiel
        FROM salle_materiel
    ";  
    $stmt = $bdd->prepare($sql_materiel);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =======================  Fonction pour afficher l'ensemble du matériel =======================
function afficher_materiel_pagines($materiel, $page_actuelle, $items_par_page, $bdd) {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $materiel_page = array_slice($materiel, $debut, $items_par_page);
    ?>
        <?php if (empty($materiel_page)): ?>
            <p class="no-experiences">Il n'y a pas de matériel</p>
        <?php else: ?>

    <table class="whole_table">
        <thead class="tablehead">
            <tr>
                <th>Salle</th>
                <th>Matériel</th>
                <th>Modifier</th>
                <th>Supprimer</th>
            </tr>
        </thead>
        <tbody>
    <?php
        foreach ($materiel_page as $user):
            $id  = htmlspecialchars($user['ID_materiel']);
            $salle = htmlspecialchars($user['Nom_salle']);
            $mat = htmlspecialchars($user['Materiel']);
    ?>
        <tr>
            <form action="page_admin_materiel_salle.php" method="POST">
                <td>
                    <input type="text" name="salle" value="<?=$salle?>">
                </td>
                <td>
                    <input type="text" name="materiel" value="<?=$mat?>">
                </td>
                <td>
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" value="<?=$id?>">
                    <button class="btn btnViolet" type="submit">Modifier</button>
                </td>
                <td>
                    <a class="btn btnRouge"
                        href="page_admin_materiel_salle.php?action=supprimer&id=<?= $id ?>">
                        Supprimer</a>
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
        <link rel="stylesheet" href="../css/page_mes_experiences.css">   <!-- Utilisé pour les titres -->
        <link rel="stylesheet" href="../css/page_admin_utilisateurs_materiel.css">
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/boutons.css">
        <link rel="stylesheet" href="../css/popup.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        
        <title>Matériel</title>
    </head>
    <body>
    
    <?php 
    // Affiche la popup si elle existe
    echo $message;
    ?>

    <?php afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]); ?>

    <div class=bandeau>
        <p><?php echo "Matériel"; ?></p>
    </div> 

    <div class="back_square">
        <section class="section-experiences">
            <h2>Matériel (<?= count($materiel) ?>)</h2>
            <?php afficher_materiel_pagines($materiel, $page, $items_par_page, $bdd); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>

        <?php if ($ajouter) { ?>
            <div class="creer_materiel_form">
                <form action="page_admin_materiel_salle.php" method="POST">
                    <h3>Ajouter du matériel</h3>
                    <label>Salle :</label>
                    <input type="text" name="salle_new" required>
                    <label>Matériel :</label>
                    <input type="text" name="materiel_new" required>
                    <input type="hidden" name="action" value="valider">
                    <button class="btn btnViolet" type="submit">Ajouter</button>
                    <a class="btn btnRouge" href="page_admin_materiel_salle.php">Annuler</a>
                </form>
            </div>
        <?php } else { ?>
            <div class=creer_materiel>
                <a href="page_admin_materiel_salle.php?action=ajouter" class="btn btnViolet">
                    Ajouter du matériel</a>
            </div>
        <?php } ?>
    </div>

    <?php afficher_Bandeau_Bas(); ?>
    </body>
</html>