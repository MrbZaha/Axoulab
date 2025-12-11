<?php
// Inclusion du fichier contenant des fonctions générales du site.
// __DIR__ représente le dossier actuel. 
require_once __DIR__ . '/../fonctions_site_web.php';

function en_cours_validation($bdd, $email) {

    // ============================================================================
    //  FONCTION : en_cours_validation()
    //  Vérifie si un compte existe et si celui-ci est encore en cours de validation.
    //  Un compte est considéré "en cours de validation" si :
    //   - la colonne "validation" = 0  (email non vérifié)
    //   - OU la colonne "etat" = 0     (compte désactivé / inactif)
    // ============================================================================

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

function mot_de_passe_correct($bdd, $email, $mdp) {
    
    // ============================================================================
    //  FONCTION : mot_de_passe_correct()
    //  Vérifie que le mot de passe entré correspond à celui stocké en base.
    //  ATTENTION : le mot de passe stocké en BDD est un hash, pas le mot de passe brut.
    //  On utilise password_verify() pour comparer proprement.
    // ============================================================================

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
