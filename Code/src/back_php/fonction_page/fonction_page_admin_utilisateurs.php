<?php
include_once "../back_php/fonctions_site_web.php";
 
/**
 * Traitement des informations modifiées pour l'utilisateur
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $id Identifiant de l'utilisateur
 * @return bool ou string Return vrai si l'étape s'est effectuée correctement, sinon un message d'erreur
 */
function modifier_utilisateur(PDO $bdd, int $id){
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

/**
 * Fonction pour récupérer l'ensemble des utilisateurs
 *
 * @param PDO $bdd Connexion à la base de données
 * @return array Retourne un tableau contenant la liste de l'ensemble des utilisateurs
 */
function get_utilisateurs(PDO $bdd) :array{
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

/**
 * Fonction pour afficher l'ensemble des utilisateurs
 *
 * @param array $materiel Liste de l'ensemble des utilisateurs
 * @param int $page_actuelle Précise le numéro de la page qui doit être affiché
 * @param int $items_par_page Donne le nombre d'élément à afficher sur chaque page
 * @param PDO $bdd Connexion à la base de données
 * @return void C'est une procédure qui ne retourne rien
 */
function afficher_utilisateurs_pagines(array $utilisateurs, int $page_actuelle, int $items_par_page, PDO $bdd) :void{
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

        $id         = htmlspecialchars($user['ID_compte'], ENT_QUOTES, 'UTF-8');
        $nom        = htmlspecialchars($user['Nom'], ENT_QUOTES, 'UTF-8');
        $prenom     = htmlspecialchars($user['Prenom'], ENT_QUOTES, 'UTF-8');
        $dateN      = htmlspecialchars($user['Date_de_naissance'], ENT_QUOTES, 'UTF-8');
        $email      = htmlspecialchars($user['Email'], ENT_QUOTES, 'UTF-8');
        $etat       = (int)$user['Etat'];
        $validation = (int)$user['validation'];
    ?>

    <tr>

        <form action="page_admin_utilisateurs.php" method="POST">

            <td>
                <input type="text" name="nom_<?= $id ?>" value="<?= $nom ?>" class="input-user" required>
            </td>

            <td>
                <input type="text" name="prenom_<?= $id ?>" value="<?= $prenom ?>" class="input-user" required>
            </td>

            <td>
                <input type="date" name="date_<?= $id ?>" value="<?= $dateN ?>" class="input-user" required>
            </td>

            <td>
                <input type="email" name="email_<?= $id ?>" value="<?= $email ?>" class="input-user" required>
            </td>

            <td>
                <select name="etat_<?= $id ?>" class="select-user">
                    <option value="1" <?= $etat === 1 ? "selected" : "" ?>>Étudiant</option>
                    <option value="2" <?= $etat === 2 ? "selected" : "" ?>>Chercheur</option>
                    <option value="3" <?= $etat === 3 ? "selected" : "" ?>>Administrateur</option>
                </select>
            </td>

            <!-- VALIDATION : POST + CSRF (PAS DE GET) -->
            <td>
                <?php if ($validation !== 1): ?>
                    <form action="page_admin_utilisateurs.php" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="accepter">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button class="btn btnVert" type="submit">Valider</button>
                    </form>
                <?php else: ?>
                    <span class="badge-valide">Validé(e)</span>
                <?php endif; ?>
            </td>

            <td>
                <div class="actions-cell">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="btn btnViolet" type="submit">Modifier</button>
                </div>
            </td>

        </form>

        <!-- FORMULAIRE DE SUPPRESSION (POST + CSRF) -->
        <td>
            <?php if ($_SESSION['ID_compte'] != $id): ?>
                <form action="page_admin_utilisateurs.php" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="btn btnRouge" type="submit">
                        Supprimer
                    </button>
                </form>
            <?php else: ?>
                <span class="btn btnGris">Supprimer</span>
            <?php endif; ?>
        </td>

    </tr>

    <?php endforeach; ?>

        </tbody>
    </table>

    <?php endif; ?>

    <?php
}

?>