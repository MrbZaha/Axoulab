<?php
session_start();
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();
verification_connexion($bdd);

/**
 * Récupère toutes les personnes disponibles pour être collaborateur ou gestionnaire
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param array $ids_exclus Tableau des identifiants (ID_compte) à exclure des résultats (par défaut vide)
 * @return array Tableau de tableaux associatifs, chaque élément contenant :
 *               - 'ID_compte' (int) : L'identifiant du compte
 *               - 'Nom' (string) : Le nom de la personne
 *               - 'Prenom' (string) : Le prénom de la personne
 *               - 'Etat' (int) : Le statut du compte (0=utilisateur, 1=gestionnaire salle, 2=admin)
 */
function get_personnes_disponibles(PDO $bdd, array $ids_exclus = []) {
    $sql = "SELECT ID_compte, Nom, Prenom, Etat FROM compte WHERE validation = 1";
    
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
 * Permet de récupérer l'identifiant d'une personne à partir de son prénom
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param string $nom_complet, le nom de la personne
 * @return int : l'identifiant de la personne. Par ailleur cette personne doit nécésserement être une personne dont le compte à été validé.
 */
function trouver_id_par_nom_complet(PDO $bdd, string $nom_complet) {
    $parts = explode(' ', trim($nom_complet), 2);
    if (count($parts) < 2) return null;
    
    $prenom = trim($parts[0]);
    $nom = trim($parts[1]);
    
    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE Prenom = ? AND Nom = ? AND validation = 1");
    $stmt->execute([$prenom, $nom]);
    return $stmt->fetchColumn();
}
?>