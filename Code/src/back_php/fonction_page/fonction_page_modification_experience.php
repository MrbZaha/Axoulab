<?php
require_once __DIR__ . '/../fonctions_site_web.php';


$bdd = connectBDD();
verification_connexion($bdd);

/**
 * Récupère le projet lié à l'experience donnée
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_experience ID de l'experience
 * 
 * @return int|null Id du projet lié
*/

function get_projet_from_experience(PDO $bdd, int $id_experience) :?int{

    //Prépare la requête et l'execute 
    $sql = "
    SELECT pe.ID_projet
    FROM projet_experience pe
    WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);

    //Retourne l'id du projet
    return $stmt->fetchColumn();
}


/**
 * Récupère les détails d'une expérience pour modification.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_experience ID de l'expérience à récupérer
 * 
 * @return array|null Tableau associatif contenant les champs 
 *                    ID_experience, Nom, Description, Date_reservation, Heure_debut, Heure_fin
 *                    ou null si aucune expérience ne correspond
 */
function get_experience_pour_modification(PDO $bdd, int $id_experience): ?array {
    $sql = "SELECT ID_experience, Nom, Description, Date_reservation, Heure_debut, Heure_fin 
            FROM experience WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Récupère les ID des matériels liés à une expérience donnée.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_experience ID de l'expérience
 * 
 * @return int[] Tableau contenant les ID des matériels liés à l'expérience
 *               (vide si aucun matériel n'est lié)
 */
function get_materiels_experience(PDO $bdd, int $id_experience): array {
    $sql = "SELECT ID_materiel FROM materiel_experience WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


/**
 * Récupère le nom de la salle associée à une expérience donnée.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_experience ID de l'expérience
 * 
 * @return string|null Nom de la salle ou null si aucune salle n'est associée
 */
function get_salle_from_experience(PDO $bdd, int $id_experience): ?string {
    $sql = "
        SELECT DISTINCT sm.Nom_salle
        FROM materiel_experience me
        INNER JOIN salle_materiel sm ON sm.ID_materiel = me.ID_materiel
        WHERE me.ID_experience = :id_experience
        LIMIT 1
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetchColumn() ?: null;
}
?>