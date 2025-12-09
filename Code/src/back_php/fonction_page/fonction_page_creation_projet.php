<?php
session_start();
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !
$bdd = connectBDD();
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier connexion
verification_connexion($bdd);

/**
 * Vérifie la validité des champs du formulaire de création/modification de projet
 *
 * @param string $nom_projet Nom du projet à vérifier
 * @param string $description Description du projet à vérifier
 * @return array Tableau des messages d'erreur (vide si aucune erreur)
 */
function verifier_champs_projet($nom_projet, $description) {
    $erreurs = [];

    if (strlen($nom_projet) < 3 || strlen($nom_projet) > 100) {
        $erreurs[] = "Le nom du projet doit contenir entre 3 et 100 caractères.";
    }

    if (strlen($description) < 10 || strlen($description) > 2000) {
        $erreurs[] = "La description doit contenir entre 10 et 2000 caractères.";
    }

    return $erreurs;
}

/**
 * Récupère toutes les personnes disponibles pour être collaborateur ou gestionnaire
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param array $ids_exclus Tableau des identifiants (ID_compte) à exclure des résultats (par défaut vide)
 * @param bool $seulement_non_etudiants Si true, exclut les étudiants (Etat = 0) des résultats (par défaut false)
 * @return array Tableau de tableaux associatifs, chaque élément contenant :
 *               - 'ID_compte' (int) : L'identifiant du compte
 *               - 'Nom' (string) : Le nom de la personne
 *               - 'Prenom' (string) : Le prénom de la personne
 *               - 'Etat' (int) : Le statut du compte (0=étudiant, 1=gestionnaire salle, 2=admin)
 */
function get_personnes_disponibles($bdd, $ids_exclus = [], $seulement_non_etudiants = false) {
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
 * Crée un nouveau projet dans la base de données
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param string $nom_projet Nom du projet
 * @param string $description Description détaillée du projet
 * @param int $confidentialite Statut de confidentialité (0 = public, 1 = confidentiel)
 * @param int $id_compte ID du compte créateur du projet
 * @param int $valide Statut de validation (0 = en attente, 1 = validé)
 * @return int|false ID du projet créé (lastInsertId) ou false en cas d'échec
 */
function creer_projet($bdd, $nom_projet, $description, $confidentialite, $id_compte, $valide) {
    $date_creation = date('Y-m-d');

    $sql = $bdd->prepare("
        INSERT INTO projet (Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $sql->execute([$nom_projet, $description, $confidentialite, $valide, $date_creation, $date_creation]);
    return $bdd->lastInsertId();
}

/**
 * Ajoute les gestionnaires et collaborateurs à un projet
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_projet ID du projet auquel ajouter les participants
 * @param array $gestionnaires Tableau des ID_compte des gestionnaires à ajouter
 * @param array $collaborateurs Tableau des ID_compte des collaborateurs à ajouter
 * @return void
 */
function ajouter_participants($bdd, $id_projet, $gestionnaires, $collaborateurs) {
    $sql = $bdd->prepare("
        INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut)
        VALUES (?, ?, ?)
    ");

    foreach ($gestionnaires as $id_compte) {
        $sql->execute([$id_projet, $id_compte, 1]); // 1 = gestionnaire
    }

    foreach ($collaborateurs as $id_compte) {
        $sql->execute([$id_projet, $id_compte, 0]); // 0 = collaborateur
    }
}

/**
 * Recherche l'ID d'un compte à partir d'un nom complet "Prénom Nom"
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param string $nom_complet Nom complet au format "Prénom Nom" (ex: "Jean Dupont")
 * @return int|null ID du compte trouvé ou null si non trouvé ou format invalide
 */
function trouver_id_par_nom_complet($bdd, $nom_complet) {
    $parts = explode(' ', trim($nom_complet), 2);
    if (count($parts) < 2) return null;

    $prenom = trim($parts[0]);
    $nom = trim($parts[1]);

    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE Prenom = ? AND Nom = ? AND validation = 1");
    $stmt->execute([$prenom, $nom]);
    return $stmt->fetchColumn();
}

?>