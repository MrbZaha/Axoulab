<?php
// Inclusion du fichier contenant des fonctions générales du site.
// __DIR__ représente le dossier actuel. 
require_once __DIR__ . '/../fonctions_site_web.php';


/**
 * Vérifie si un compte existe et si celui-ci est encore en cours de validation.
 *
 * @param PDO $bdd Connexion à la base de données
 * @param str $email Email de l'utilisateur
 * @return bool False si compte validé, True si il est non vérifié ou désactivé
 */
function en_cours_validation(PDO $bdd, string $email) :bool{

    // Préparation de la requête : récupérer l’état du compte pour cet email
    $stmt = $bdd->prepare("SELECT validation, etat FROM compte WHERE email = ?");
    
    // Exécution de la requête avec $email passé en paramètre
    $stmt->execute([$email]);

    // rowCount() > 0 : si un compte avec cet email existe
    if ($stmt->rowCount() > 0) {
        
        // Récupération du résultat sous forme de tableau associatif
        $user = $stmt->fetch();

        // Conditions d’un compte en cours de validation :
        // - validation = 0 → email non validé
        // - etat = 0 → compte désactivé temporairement
        return ($user["validation"] == 0 || $user["etat"] == 0);
    }

    // Aucun utilisateur trouvé, donc certainement pas en validation
    return false;
}


/**
 * Vérifie que le mot de passe entré correspond à celui stocké en base.
 *
 * @param PDO $bdd Connexion à la base de données
 * @param str $email Email de l'utilisateur
 * @param str $mdp Mot de passe de l'utilisateur
 * @return bool True si mot de passe valide, False si il est erroné
 */

function mot_de_passe_correct(PDO $bdd, string $email, string $mdp) :bool{

    // On sélectionne le mot de passe hashé du compte correspondant à l'email
    $stmt = $bdd->prepare("SELECT Mdp FROM compte WHERE email = ?");
    $stmt->execute([$email]);

    // Si un utilisateur existe avec cet email
    if ($stmt->rowCount() > 0) {

        // Récupérer le mot de passe hashé stocké
        $user = $stmt->fetch();

        // Vérification sécurisée du mot de passe :
        // compare le mdp tapé à son équivalent hashé
        return password_verify($mdp, $user["Mdp"]);
    }

    // Aucune correspondance email → mot de passe incorrect
    return false;
}
?>
