<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

$ajouter = true;            // Définition d'un variable permettant de dire si on cherche à ajouter un élément ou non

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
// Dans le cas où l'on cherche à supprimer un outil
// Si une action GET est reçue
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    if (isset($_GET['id'])) {
        $id_materiel = intval($_GET['id']);
        supprimer_materiel($bdd, $id_materiel);

        // On recharge la page proprement (cela empêche de supprimer deux fois)
        header("Location: page_admin_materiel_salle.php?suppression=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à modifier les informations d'un outil
// Si une action POST est reçue
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    if (isset($_POST['id'])) {
        modifier_materiel($bdd, intval($_POST['id']));

        // On recharge la page proprement (cela empêche de supprimer deux fois)
        header("Location: page_admin_materiel_salle.php?modification=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à ajouter un outil
// Si une action POST est reçue
if (isset($_GET['action']) && $_GET['action'] === 'ajouter') {
    $ajouter = true;   // On définit la variable qui permettra de déclencher, ou non, une fonction
    echo $ajouter;
}
else {
    $ajouter = false;
}

///////////////////////////////////////////////////////////////////////////////
// Après confirmation de l'ajout d'un outil
// Si une action POST est reçue
if (isset($_POST['action']) && $_POST['action'] === 'valider') {
    if (!empty($_POST['salle_new']) && !empty($_POST['materiel_new'])) {
        ajouter_materiel($bdd, $_POST['salle_new'], $_POST['materiel_new']);
        header("Location: page_admin_materiel_salle.php?ajout=ok");
        exit;
    }
}

// =======================  INSÉRER DU NOUVEAU MATÉRIEL =======================
/* Insère du nouveau matériel dans la base de donnée */
function ajouter_materiel($bdd, $Nom_Salle, $Materiel) {
    $sql = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?)");
    return $sql->execute([$Nom_Salle, $Materiel]);
}

// =======================  Traitement des informations modifiées  =======================
function modifier_materiel($bdd, $id){
    if (isset($_POST["salle"], $_POST["materiel"])) {
        // Récupération des données et nettoyage
        $salle = trim($_POST["salle"]);
        $mat = trim($_POST["materiel"]);

        // ======================= MODIFICATION DANS LA BASE DE DONNÉES =======================
        try {
            $stmt = $bdd->prepare("
                UPDATE salle_materiel
                SET Nom_Salle = ?,
                    Materiel = ?
                WHERE ID_materiel = ?
            ");

            $stmt->execute([
                $salle,
                $mat,
                $id
            ]);
            $message = "<p style='color:green;'>La modification du matériel a été effectué correctement !</p>";
        }
        catch (Exception $e) {
            $message = "<p style='color:red;'>Une erreur est survenue : " . $e->getMessage() . "</p>";
            echo $message;

        }
    } else {
    $message = "<p style='color:red;'>Une erreur est survenue. Veuillez réessayer ultérieurement</p>";
    }
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

    $materiel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $materiel;   
}

// =======================  Fonction pour afficher l'ensemble du matériel =======================
function afficher_materiel_pagines($materiel, $page_actuelle, $items_par_page, $bdd) {
    // On récupère l'indice de la première expérience qui sera affichée
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
                <!-- On affiche le nom de la salle -->
                <td>
                    <input type="text" name="salle" value="<?=$salle?>">
                </td>
                <!-- On affiche le matériel -->
                <td>
                    <input type="text" name="materiel" value="<?=$mat?>">
                </td>
                <!-- Affichage du bouton de modification des données -->
                <td>
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" value="<?=$id?>">
                    <button class="btn btnBlanc" type="submit">
                    Modifier</button>
                </td>
                <!-- Affichage du bouton de suppression des données -->
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
<html lang="en">
    <head>
        <meta charset= "utf-8"/>
        <link rel="stylesheet" href="../css/page_mes_experiences.css"> <!-- Utilisé pour l'affichage des titres -->
        <link rel="stylesheet" href="../css/page_admin_utilisateurs_materiel.css"> <!-- Utilisé pour l'affichage du matériel-->

        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/boutons.css">

        <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title> Matériel </title>

    </head>
    <body>
    <?php 
    // Affiche un message s'il existe
    if (!empty($message)) echo $message;
    ?>

    <!-- import du header de la page -->
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    ?>

    <!-- Afficher le titre de la page en bandeau-->
    <div class=bandeau>
        <p> <?php echo "Matériel"; ?></p>
    </div> 

    <!-- Crée un grand div qui aura des bords arrondis et sera un peu gris-->
    <div class="back_square">
    <!-- Affichage des expériences une à une-->
        <section class="section-experiences">
            <h2>Matériel (<?= count($materiel) ?>)</h2>  <!--Titre affichant le nombre de matériel disponible-->
            <?php afficher_materiel_pagines($materiel, $page, $items_par_page, $bdd); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>

        <?php if ($ajouter) {
            ?>

            <div class="creer_materiel_form">
                <form action="page_admin_materiel_salle.php" method="POST">
                    <h3>Ajouter du matériel</h3>

                    <label>Salle :</label>
                    <input type="text" name="salle_new" required>

                    <label>Matériel :</label>
                    <input type="text" name="materiel_new" required>

                    <input type="hidden" name="action" value="valider">
                    <button class="btn btnBlanc" type="submit">Ajouter</button>
                    <a class="btn btnRouge" href="page_admin_materiel_salle.php">Annuler</a>
                </form>
            </div>
        <?php
        } else { ?>
            <div class=creer_materiel>
            <a href="page_admin_materiel_salle.php?action=ajouter"
                class="btn btnBlanc">
                Ajouter du matériel</a>
            </div>
        <?php } ?>
    </div>

    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>

</html>