<?php
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();
verification_connexion($bdd);

/**
 * Permet de supprimer du matériel de la base de données
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $id_materiel Identifiant du matériel associé
 * @return void c'est une procédure qui ne retourne rien
 */
function supprimer_materiel(PDO $bdd, int $id_materiel) :void{
    $stmt = $bdd->prepare("DELETE FROM salle_materiel WHERE ID_materiel = ?");
    $stmt->execute([$id_materiel]);
}

/**
 * Permet d'ajouter du nouveau matériel dans la base de données
 *
 * @param PDO $bdd Connexion à la base de données
 * @param string $Nom_Salle Nom de la salle possédant l'outil
 * @param string $Matériel Nom du matériel que l'on ajoute
 * @return bool ou string Return vrai si l'étape s'est effectuée correctement, sinon un message d'erreur
 */
function ajouter_materiel(PDO $bdd, string $Nom_Salle, string $Materiel) {
    try {
        $sql = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?)");
        $sql->execute([$Nom_Salle, $Materiel]);
        return true;                            // True si tout se passe bien
    } catch (Exception $e) {
        return $e->getMessage();                // Message d'erreur s'il y a un problème
    }
}

/**
 * Permet de modifier les informations d'un ensemble de matériel + salle
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $id Nom de la salle possédant l'outil
 * @return bool ou string Return vrai si l'étape s'est effectuée correctement, sinon un message d'erreur
 */
function modifier_materiel(PDO $bdd, int $id) {
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

/**
 * Fonction pour récupérer l'ensemble du matériel
 *
 * @param PDO $bdd Connexion à la base de données
 * @return array Rend la liste de l'ensemble du matériel
 */
function get_materiel(PDO $bdd) :array{
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

/**
 * Fonction pour afficher l'ensemble du matériel
 *
 * @param array $materiel Liste de l'ensemble du matériel
 * @param int $page_actuelle Précise le numéro de la page et la liste du matériel qui doit être affiché en conséquence
 * @param int $items_par_page Donne le nombre d'élément à afficher sur chaque page
 * @param PDO $bdd Connexion à la base de données
 * @return void C'est une procédure qui ne retourne rien
 */
function afficher_materiel_pagines(array $materiel, int $page_actuelle, int $items_par_page, PDO $bdd) :void{
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