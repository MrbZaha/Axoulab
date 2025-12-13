<?php
// --- En-tête : includes et connexion BDD ---
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();

$id_compte = $_SESSION['ID_compte'];
$id_projet = isset($_GET['id_projet']) ? (int)$_GET['id_projet'] : 0;

/**
 * Récupère tout les projets d'un utilisateur ou l'ensemble des fonctions
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte utilisateur
 * @return array Tableau associatif contenant :
 *               - 'ID_projet' (int) L'identifiant du projet
 *               - 'Nom' (string) : Le nom du projet
 *               - 'Description' (string) : La descritpion du projet
 *               - 'Confidentiel' (Boolean) : La confidentialité du projet (0=non confidentiel, 1=confidentiel)
 *               - 'Validation' (Boolean) : La validation du projet (0=non validé, 1=validé)
 *               - 'Statut' (Boolean) : Le statut de l'utilisateur vis-à-vis de ce projet (0=collaborateur, 1=gestionnaire) 
 *               - 'Date_de_creation' (Date) : La date de céation du projet
 *               - 'Date_de_modification' (Date) : La dernière date de modification du projet
*/
function get_mes_projets_complets(PDO $bdd, int $id_compte): array {
    $sql_projets = "
        SELECT 
            p.ID_projet, 
            p.Nom_projet AS Nom, 
            p.Description, 
            p.Confidentiel, 
            p.Validation, 
            pcg.Statut,
            p.Date_de_creation,
            p.Date_de_modification
        FROM projet p
        INNER JOIN projet_collaborateur_gestionnaire pcg
            ON p.ID_projet = pcg.ID_projet   
        WHERE pcg.ID_compte = :id_compte";
    
    $stmt = $bdd->prepare($sql_projets);
    $stmt->execute(['id_compte' => $id_compte]);
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projets)) {
        return [];
    }
    
    // Utiliser & pour modifier par référence
    foreach ($projets as &$projet) {
        if ($projet['Statut'] == 0) {
            $projet['Statut'] = 'Collaborateur';
        }
        else {
            $projet['Statut'] = 'Gestionnaire';
        }
    }
    unset($projet); // Détruire la référence après la boucle
    return $projets;
}

?>