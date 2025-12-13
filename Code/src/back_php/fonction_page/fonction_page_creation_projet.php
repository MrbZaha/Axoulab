<?php
session_start();
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

// En mode test, ne pas se connecter à la BDD ni vérifier la connexion
if (!defined('TEST_MODE')) {
    $bdd = connectBDD();
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    verification_connexion($bdd);
}

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

?>