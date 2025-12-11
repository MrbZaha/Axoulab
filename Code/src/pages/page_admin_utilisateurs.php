<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

$bdd = connectBDD();

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
// Dans le cas où l'on cherche à supprimer un utilisateur
// Si une action GET est reçue
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        supprimer_utilisateur($bdd, $id_utilisateur);

        // On recharge la page proprement (cela empêche de supprimer deux fois)
        header("Location: page_admin_utilisateurs.php?suppression=ok");
        exit;
    }
}

///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à modifier les informations d'un utilisateur
// Si une action GET est reçue
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    modifier_utilisateur($bdd, intval($_POST['id']));
}


///////////////////////////////////////////////////////////////////////////////
// Dans le cas où l'on cherche à accepter la création d'un compte
// Si une action GET est reçue
if (isset($_GET['action']) && $_GET['action'] === 'accepter') {
    if (isset($_GET['id'])) {
        $id_utilisateur = intval($_GET['id']);
        accepter_utilisateur($bdd, $id_utilisateur);

        // On recharge la page proprement (cela empêche d'accepter deux fois)
        header("Location: page_admin_utilisateurs.php?accept=ok");
        exit;
    }
}

// =======================  Traitement des informations modifiées  =======================
function modifier_utilisateur($bdd, $id){
    if (isset($_POST["nom_$id"], $_POST["prenom_$id"], $_POST["date_$id"], $_POST["etat_$id"], $_POST["email_$id"])) {
        // Récupération des données et nettoyage
        $nom = trim($_POST["nom_$id"]);
        $prenom = trim($_POST["prenom_$id"]);
        $datedenaissance = trim($_POST["date_$id"]);
        $etat = intval($_POST["etat_$id"]); // permet de savoir si c'est un étudiant, prof ou admin
        $email = trim($_POST["email_$id"]);

        // ======================= MODIFICATION DANS LA BASE DE DONNÉES =======================
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

            $stmt->execute([
                $nom,
                $prenom,
                $datedenaissance,
                $email,
                $etat,
                $id
            ]);
            $message = "<p style='color:green;'>La modification du compte a été effectué correctement !</p>";
        }
        catch (Exception $e) {
            $message = "<p style='color:red;'>Une erreur est survenue : " . $e->getMessage() . "</p>";
        }
    } else {
    $message = "<p style='color:red;'>Une erreur est survenue. Veuillez réessayer ultérieurement</p>";

    }
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

    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $utilisateurs;   
}

// =======================  Fonction pour afficher l'ensemble des utilisateurs =======================
function afficher_utilisateurs_pagines($utilisateurs, $page_actuelle, $items_par_page, $bdd) {
    // On récupère l'indice de la première expérience qui sera affichée
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
                <th>Modifier</th>
                <th>Supprimer</th>
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
                <!-- On affiche le nom -->
                <td>
                    <input type="text" name="nom_<?= $id ?>" value="<?= $nom ?>">
                </td>
                <!-- On affiche le prénom -->
                <td>
                    <input type="text" name="prenom_<?= $id ?>" value="<?= $prenom ?>">
                </td>
                <!-- On affiche la date de naissance -->
                <td>
                    <input type="date" name="date_<?= $id ?>" value="<?= $dateN ?>">
                </td>
                <!-- On affiche l'email -->
                <td>
                    <input type="email" name="email_<?= $id ?>" value="<?= $email ?>">
                </td>
                <!-- On affiche le statut de l'utilisateur -->
                <td>
                    <select name="etat_<?= $id ?>">
                        <option value="1" <?= $etat == 1 ? "selected" : "" ?>>Étudiant</option>
                        <option value="2" <?= $etat == 2 ? "selected" : "" ?>>Chercheur</option>
                        <option value="3" <?= $etat == 3 ? "selected" : "" ?>>Administrateur</option>
                    </select>
                </td>
                <!-- Affichage du bouton de validation si nécessaire -->
                <td>
                    <?php if ($validation != 1): ?>
                        <a class="btn btnBlanc"
                            href="page_admin_utilisateurs.php?action=accepter&id=<?= $id ?>">
                            Valider
                        </a>
                    <?php else: ?>
                        Validé(e)
                    <?php endif; ?>
                </td>
                <!-- Affichage du bouton de modification -->
                <td>
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="btn btnBlanc" type="submit">Modifier</button>
                </td>
                <!-- Affichage du bouton pour supprimer un compte -->
                <td>
                    <?php if ($_SESSION['ID_compte'] != $id): ?>
                        <a class="btn btnRouge"
                            href="page_admin_utilisateurs.php?action=supprimer&id=<?= $id ?>">
                            Supprimer
                        </a>
                    <?php else: ?>
                        <span class="btn btnGris">Supprimer</span>
                    <?php endif; ?>
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
        <link rel="stylesheet" href="../css/page_mes_experiences.css"> <!-- Utilisé pour l'affichage des utilisateurs -->
        <link rel="stylesheet" href="../css/page_admin_utilisateurs_materiel.css"> <!-- Utilisé pour l'affichage des exp-->

        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/boutons.css">

        <!-- Permet d'afficher la loupe pour le bandeau de recherche -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <title> Utilisateurs </title>

    </head>
    <body>
    <?php 
    // Affiche le message si présent
    if (!empty($message)) echo $message;
    ?>

    <!-- import du header de la page -->
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    ?>
    <!-- Afficher le titre de la page en bandeau-->
    <div class=bandeau>
        <p> <?php echo "Utilisateurs"; ?></p>
    </div> 

    <!-- Crée un grand div qui aura des bords arrondis et sera un peu gris-->
    <div class="back_square">
    <!-- Affichage des expériences une à une-->
        <section class="section-experiences">
            <h2>Utilisateurs (<?= count($utilisateurs) ?>)</h2>  <!--Titre affichant le nombre d'utilisateurs-->
            <?php afficher_utilisateurs_pagines($utilisateurs, $page, $items_par_page, $bdd); ?>
            <?php afficher_pagination($page, $total_pages); ?>
        </section>
    </div>

    <!-- Permet d'afficher le footer de la page -->
    <?php afficher_Bandeau_Bas(); ?>
    </body>

</html>