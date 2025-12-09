<?php
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

//fonction utilisée dans cette page
// ======================= VÉRIFICATION FORCE DU MOT DE PASSE =======================
/* Vérifie que le mot de passe respecte les règles de sécurité :
   - Au moins 8 caractères
   - Au moins une majuscule
   - Au moins une minuscule
   - Au moins un chiffre
   - Au moins un caractère spécial
   Retourne un tableau d'erreurs (vide si mot de passe valide) */
function verifier_mdp($mdp) {
    $erreurs = [];
    if (strlen($mdp) < 8) $erreurs[] = "au moins 8 caractères";
    if (!preg_match('/[A-Z]/', $mdp)) $erreurs[] = "au moins une majuscule";
    if (!preg_match('/[a-z]/', $mdp)) $erreurs[] = "au moins une minuscule";
    if (!preg_match('/[0-9]/', $mdp)) $erreurs[] = "au moins un chiffre";
    if (!preg_match('/[\W_]/', $mdp)) $erreurs[] = "au moins un caractère spécial (!@#$%^&*...)";
    return $erreurs;
}

// ======================= COMPARAISON DE MOT DE PASSE =======================
/* Vérifie si deux mots de passe sont identiques (mot de passe et "réécrire mot de passe") */
function mot_de_passe_identique($mdp1, $mdp2) {
    return $mdp1 === $mdp2;
}
?>