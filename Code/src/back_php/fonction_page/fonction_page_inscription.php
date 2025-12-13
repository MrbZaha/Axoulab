<?php
// Inclusion des fonctions générales du site.
require_once __DIR__ . '/../fonctions_site_web.php';


/**
 * Vérifie que l'email entré correspond bien à l'email attendu
 *
 * @param string $email Email de l'utilisateur
 * @param string $prenom Prénom de l'utilisateur
 * @param string $nom Nom de l'utilisateur
 * @return bool True si email valide, False si il est non valide
 */
function verifier_email_axoulab(string $email, string $prenom, string $nom): bool {
    // Nettoyer les données (en minuscules, sans espaces)
    $email = strtolower(trim($email));
    $prenom = strtolower(trim($prenom));
    $nom = strtolower(trim($nom));
    
    // Construire l'email attendu de base
    $email_attendu_base = $prenom . '.' . $nom . '@axoulab.fr';
    
    // Vérifier si l'email correspond exactement (sans chiffre)
    if ($email === $email_attendu_base) {
        return true;
    }
    
    // Vérifier si l'email correspond avec un chiffre après le nom
    // Pattern : prenom.nom[chiffre]@axoulab.fr
    $pattern = '/^' . preg_quote($prenom, '/') . '\.' . preg_quote($nom, '/') . '\d+@axoulab\.fr$/';
    
    if (preg_match($pattern, $email)) {
        return true;
    }
    
    return false;
}

/**
 * Insère un utilisateur dans la BDD
 *
 * @param PDO $bdd Connexion à la base de données
 * @param str $date Date
 * @param str $prenom Prenom de l'utilisateur
 * @param str $nom Nom de l'utilisateur
 * @param str $etat Etat de l'utilisateur
 * @param str $email Email de l'utilisateur
 * @param str $mdp_hash Mot de passe haché
 * @return bool True si insertion a réussie, False si il est échouée
 */

function inserer_utilisateur(PDO $bdd, string $nom, string $prenom, string $date,int $etat, string $email, string $mdp_hash) :bool{
    $sql = $bdd->prepare("INSERT INTO compte (Nom, Prenom, date_de_naissance, etat, email, Mdp) VALUES (?, ?, ?, ?, ?, ?)");
    return $sql->execute([$nom, $prenom, $date, $etat, $email, $mdp_hash]);
}
?>
