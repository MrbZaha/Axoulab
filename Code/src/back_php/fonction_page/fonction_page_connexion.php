<?php
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

// =======================  VÉRIFIER SI COMPTE EN COURS DE VALIDATION =======================
/* Vérifie si le compte est en cours de validation
   Retourne true si validation ,  false sinon */
function en_cours_validation($bdd, $email) {
    $stmt = $bdd->prepare("SELECT validation, etat FROM compte WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();

        // Compte en cours de validation si :
        // - validation = false (0)
        // - ou etat = 0
        return ($user["validation"] == 0 || $user["etat"] == 0);
    }

    return false; // si aucun compte trouvé
}

function mot_de_passe_correct($bdd, $email, $mdp) {
    $stmt = $bdd->prepare("SELECT Mdp FROM compte WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return password_verify($mdp, $user["Mdp"]); // IMPORTANT
    }
    return false;
}
?>