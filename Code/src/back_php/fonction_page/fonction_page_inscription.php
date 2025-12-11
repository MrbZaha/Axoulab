<?php
// Inclusion des fonctions générales du site.
require_once __DIR__ . '/../fonctions_site_web.php';

function verifier_mdp($mdp) {

    // ============================================================================
    //  FONCTION : verifier_mdp()
    //  Vérifie que le mot de passe respecte plusieurs critères de sécurité :
    //      ✔ au moins 8 caractères
    //      ✔ au moins une MAJUSCULE
    //      ✔ au moins une minuscule
    //      ✔ au moins un chiffre
    //      ✔ au moins un caractère spécial
    //
    //  Retourne :
    //      - un tableau vide si TOUT est correct
    //      - un tableau contenant les messages d'erreurs sinon
    // ============================================================================
    
    // Tableau où seront ajoutées les erreurs éventuelles
    $erreurs = [];

    // Longueur minimale
    if (strlen($mdp) < 8) 
        $erreurs[] = "au moins 8 caractères";

    // Présence d’une lettre majuscule
    if (!preg_match('/[A-Z]/', $mdp)) 
        $erreurs[] = "au moins une majuscule";

    // Présence d’une lettre minuscule
    if (!preg_match('/[a-z]/', $mdp)) 
        $erreurs[] = "au moins une minuscule";

    // Présence d'un chiffre
    if (!preg_match('/[0-9]/', $mdp)) 
        $erreurs[] = "au moins un chiffre";

    // Présence d'un caractère spécial
    // \W = tout ce qui n’est pas alphanumérique | _ = inclus aussi le souligné
    if (!preg_match('/[\W_]/', $mdp)) 
        $erreurs[] = "au moins un caractère spécial (!@#$%^&*...)";

    // Retourne le tableau : vide si OK, rempli si erreurs
    return $erreurs;
}

function mot_de_passe_identique($mdp1, $mdp2) {
    // ============================================================================
    //  FONCTION : mot_de_passe_identique()
    //  Compare le mot de passe saisi et sa confirmation.
    //  Retourne true si identiques, false sinon.
    // ============================================================================
    return $mdp1 === $mdp2;
}

<<<<<<< HEAD
function verifier_email_axoulab($email) {
    // Nettoyer l'email (en minuscules)
    $email = strtolower(trim($email));

    // Vérifie que l'email se termine par @axoulab.fr
    if (!str_ends_with($email, '@axoulab.fr')) {
        return false;
    }

    // Vérifie le format prenom.nom@axoulab.fr
    $pattern = '/^[a-z]+(\.[a-z]+)?@axoulab\.fr$/'; 
    // ^[a-z]+       -> au moins une lettre pour le prénom
    // (\.[a-z]+)?   -> un point suivi d'au moins une lettre pour le nom
    // @axoulab\.fr$ -> domaine exact

    if (preg_match($pattern, $email)) {
        return true;
    } else {
        return false;
    }
}

=======
// =======================  INSÉRER UN UTILISATEUR =======================
/* Insère un nouvel utilisateur dans la base de données
   Retourne true si insertion réussie, false sinon */
function inserer_utilisateur($bdd, $nom, $prenom, $date, $etat, $email, $mdp_hash) {
    $sql = $bdd->prepare("INSERT INTO compte (Nom, Prenom, date_de_naissance, etat, email, Mdp) VALUES (?, ?, ?, ?, ?, ?)");
    return $sql->execute([$nom, $prenom, $date, $etat, $email, $mdp_hash]);
}
>>>>>>> 91f1f7f94cc32f2c0d97f05f55f14fe61734f9ee
?>
