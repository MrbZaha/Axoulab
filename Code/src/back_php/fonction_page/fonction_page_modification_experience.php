<?php
require_once __DIR__ . '/../fonctions_site_web.php';


$bdd = connectBDD();
verification_connexion($bdd);

function get_projet_from_experience(PDO $bdd, int $id_experience) {
    $sql = "
    SELECT pe.ID_projet
    FROM projet_experience pe
    WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetchColumn();
}

function get_experience_pour_modification(PDO $bdd, int $id_experience): ?array {
    $sql = "SELECT ID_experience, Nom, Description, Date_reservation, Heure_debut, Heure_fin 
            FROM experience WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function get_materiels_experience(PDO $bdd, int $id_experience): array {
    $sql = "SELECT ID_materiel FROM materiel_experience WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

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