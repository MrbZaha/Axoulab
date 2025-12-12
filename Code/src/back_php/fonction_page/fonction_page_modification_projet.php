<?php
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();
verification_connexion($bdd);

$id_compte = $_SESSION['ID_compte'];
// Support POST and GET so that form submissions without querystring keep the id
$id_projet = 0;
if (isset($_REQUEST['id_projet'])) {
    $id_projet = (int)$_REQUEST['id_projet'];
} elseif (isset($_GET['id_projet'])) {
    $id_projet = (int)$_GET['id_projet'];
}

$erreur = null;
$success = null;

/**
 * Vérifie si un compte est gestionnaire d’un projet.
 *
 * Effectue la vérification suivante :
 *  - Recherche dans la table projet_collaborateur_gestionnaire si l'utilisateur
 *    possède le statut 1 (gestionnaire) pour le projet donné.
 *
 * Retourne :
 *   - true  : si l'utilisateur est gestionnaire
 *   - false : sinon
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte à vérifier
 * @param int $id_projet ID du projet concerné
 * @return bool
 */

function est_gestionnaire(PDO $bdd, int $id_compte, int $id_projet): bool {
    $sql = "SELECT Statut FROM projet_collaborateur_gestionnaire 
            WHERE ID_projet = :id_projet AND ID_compte = :id_compte AND Statut = 1";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet, 'id_compte' => $id_compte]);
    return $stmt->fetch() !== false;
}

/**
 * Récupère les informations essentielles d’un projet pour une modification.
 *
 * Données récupérées :
 *   - ID_projet
 *   - Nom_projet
 *   - Description
 *   - Confidentiel
 *
 * Retourne :
 *   - Un tableau associatif contenant les données du projet
 *   - null si le projet n’existe pas
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet ID du projet à récupérer
 * @return array|null
 */
function get_projet_pour_modification(PDO $bdd, int $id_projet): ?array {
    $sql = "SELECT ID_projet, Nom_projet, Description, Confidentiel 
            FROM projet WHERE ID_projet = :id_projet";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Récupère la liste des personnes disponibles pour être ajoutées à un projet.
 *
 * Fonctionnement :
 *  - Sélectionne tous les comptes validés
 *  - Peut exclure certains IDs (gestionnaires ou collaborateurs déjà présents)
 *  - Peut filtrer pour n’inclure que les non-étudiants (Etat > 1)
 *  - Trie par nom puis prénom
 *
 * Retourne :
 *   - Un tableau de comptes disponibles sous forme associative
 *
 * @param PDO   $bdd Connexion PDO
 * @param array $ids_exclus Liste d’IDs à exclure du résultat
 * @param bool  $seulement_non_etudiants Si true → filtre Etat > 1
 * @return array
 */
function get_personnes_disponibles(PDO $bdd, array $ids_exclus = [], bool $seulement_non_etudiants = false): array {
    $sql = "SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE validation = 1";

    if ($seulement_non_etudiants) {
        $sql .= " AND Etat > 1";
    }

    if (!empty($ids_exclus)) {
        $placeholders = implode(',', array_fill(0, count($ids_exclus), '?'));
        $sql .= " AND ID_compte NOT IN ($placeholders)";
    }

    $sql .= " ORDER BY Nom, Prenom";

    $stmt = $bdd->prepare($sql);
    $stmt->execute($ids_exclus);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Trouve l'ID d’un compte à partir d’un nom complet.
 *
 * Fonctionnement :
 *   - Décompose la chaîne "Prénom Nom"
 *   - Vérifie que les deux parties existent
 *   - Recherche un compte validé correspondant
 *
 * Retourne :
 *   - ID_compte si trouvé
 *   - null sinon
 *
 * @param PDO    $bdd Connexion PDO
 * @param string $nom_complet Chaîne "Prénom Nom"
 * @return int|null
 */
function trouver_id_par_nom_complet(PDO $bdd, string $nom_complet): ?int {
    $parts = explode(' ', trim($nom_complet), 2);
    if (count($parts) < 2) return null;

    $prenom = trim($parts[0]);
    $nom = trim($parts[1]);

    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE Prenom = ? AND Nom = ? AND validation = 1");
    $stmt->execute([$prenom, $nom]);
    return $stmt->fetchColumn() ?: null;
}

/**
 * Récupère la liste des IDs des gestionnaires d’un projet.
 *
 * Sélectionne dans la table projet_collaborateur_gestionnaire :
 *   - Tous les comptes avec Statut = 1 (gestionnaires)
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet ID du projet
 * @return array Liste des IDs des gestionnaires
 */
function get_gestionnaires_ids(PDO $bdd, int $id_projet): array {
    $sql = "SELECT ID_compte FROM projet_collaborateur_gestionnaire 
            WHERE ID_projet = :id_projet AND Statut = 1";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Récupère la liste des IDs des collaborateurs d’un projet.
 *
 * Sélectionne dans projet_collaborateur_gestionnaire :
 *   - Tous les comptes avec Statut = 0 (collaborateurs)
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet ID du projet
 * @return array Liste des IDs collaborateurs
 */
function get_collaborateurs_ids(PDO $bdd, int $id_projet): array {
    // Accepter les valeurs historiques de Statut pour les collaborateurs (0 ou 2)
    $sql = "SELECT ID_compte FROM projet_collaborateur_gestionnaire 
            WHERE ID_projet = :id_projet AND Statut IN (0,2)";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>