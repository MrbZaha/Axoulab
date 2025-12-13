<?php
// Inclusion des fonctions générales du site.
require_once __DIR__ . '/../fonctions_site_web.php';



function mot_de_passe_identique($mdp1, $mdp2) {
    // ============================================================================
    //  FONCTION : mot_de_passe_identique()
    //  Compare le mot de passe saisi et sa confirmation.
    //  Retourne true si identiques, false sinon.
    // ============================================================================
    return $mdp1 === $mdp2;
}

function verifier_email_axoulab($email,$prenom,$nom) {
    // Nettoyer les données (en minuscules, sans espaces)
    $email = strtolower(trim($email));
    $prenom = strtolower(trim($prenom));
    $nom = strtolower(trim($nom));
    
    // Construire l'email attendu
    $email_attendu = $prenom . '.' . $nom . '@axoulab.fr';
    
    // Vérifier que l'email saisi correspond exactement à l'email attendu
    if ($email === $email_attendu) {
        return true;
    } else {
        return false;
    }
}

// =======================  INSÉRER UN UTILISATEUR =======================
/* Insère un nouvel utilisateur dans la base de données
   Retourne true si insertion réussie, false sinon */
function inserer_utilisateur(PDO $bdd, string $nom, string $prenom, string $date,int $etat, string $email, string $mdp_hash) {
    $sql = $bdd->prepare("INSERT INTO compte (Nom, Prenom, date_de_naissance, etat, email, Mdp) VALUES (?, ?, ?, ?, ?, ?)");
    return $sql->execute([$nom, $prenom, $date, $etat, $email, $mdp_hash]);
}
?>
