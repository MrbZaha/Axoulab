<?php
// ======================= FICHIER DE FONCTIONS =======================
// Ce fichier contient toutes les fonctions réutilisables pour les pages
// inscription et connexion du projet de cahier de laboratoire.
// L'objectif est de centraliser le code pour qu'il soit plus propre,
// facile à maintenir et réutilisable sur plusieurs pages.

// ======================= 1. CONNEXION À LA BASE DE DONNÉES =======================
/* Fonction pour connecter la base de données avec PDO
   Utilise try/catch pour gérer les erreurs de connexion
   Retourne l'objet PDO si connexion réussie */
function connectBDD() {
    try {
        $bdd = new PDO("mysql:host=localhost;dbname=projet_site_web;charset=utf8","caca","juliette74");
        return $bdd;
    } catch (Exception $e) {
        // Si erreur de connexion, on arrête le script et on affiche le message
        die("Erreur : " . $e->getMessage());
    }
}

// ======================= 2. VÉRIFICATION FORCE DU MOT DE PASSE =======================
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

// ======================= 3. VÉRIFICATION EMAIL EXISTANT =======================
/* Vérifie si une adresse email existe déjà dans la base de données
   Retourne true si l'email existe, false sinon */
function email_existe($bdd, $email) {
    $stmt = $bdd->prepare("SELECT * FROM table_compte WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// ======================= 4. COMPARAISON DE MOT DE PASSE =======================
/* Vérifie si deux mots de passe sont identiques (mot de passe et "réécrire mot de passe") */
function mot_de_passe_identique($mdp1, $mdp2) {
    return $mdp1 === $mdp2;
}

// ======================= 5. VALIDATION EMAIL AXOULAB =======================
/* Vérifie que l'email est au format prenom.nom@axoulab.fr
   Retourne true si le format est correct, false sinon */
function verifier_email_axoulab($email) {
    return preg_match('/^[a-zA-Z]+\.[a-zA-Z]+@axoulab\.fr$/', $email);
}

// ======================= 6. INSÉRER UN UTILISATEUR =======================
/* Insère un nouvel utilisateur dans la base de données
   Retourne true si insertion réussie, false sinon */
function inserer_utilisateur($bdd, $nom, $prenom, $date, $etat, $email, $mdp_hash) {
    $sql = $bdd->prepare("INSERT INTO table_compte (Nom, Prenom, date_de_naissance, etat, email, mdp) VALUES (?, ?, ?, ?, ?, ?)");
    return $sql->execute([$nom, $prenom, $date, $etat, $email, $mdp_hash]);
}

// ======================= 7. NOTIFICATION ADMIN =======================
/* Fonction pour envoyer une notification à l'administrateur lors
   d'une nouvelle inscription. Ici c'est un exemple, tu peux utiliser mail() ou autre */
function envoyer_notification_admin($email, $nom, $prenom) {
    // Exemple : on pourrait utiliser mail() pour notifier l'admin
    // mail("admin@axoulab.fr", "Nouvel utilisateur", "Nouvel utilisateur inscrit : $prenom $nom ($email)");
}

// ======================= 8. MOT DE PASSE CORRECT =======================
/* Vérifie si le mot de passe saisi correspond au hash stocké en base
   Retourne true si mot de passe correct, false sinon */
function mot_de_passe_correct($bdd, $email, $mdp) {
    $stmt = $bdd->prepare("SELECT mdp FROM table_compte WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return $user["mdp"] === $mdp;
    }
    return false; // Email inexistant
}

// ======================= 9. CONNEXION VALIDE =======================
/* Vérifie si un utilisateur peut se connecter (email + mot de passe corrects)
   Retourne true si connexion possible, false sinon */
function connexion_valide($bdd, $email, $mdp) {
    return email_existe($bdd, $email) && mot_de_passe_correct($bdd, $email, $mdp);
}

// ======================= 10. RÉCUPÉRER ID COMPTE =======================
/* Récupère l'ID du compte à partir de l'email
   Retourne ID du compte si trouvé, null sinon */
function recuperer_id_compte($bdd, $email) {
    $stmt = $bdd->prepare("SELECT ID_compte FROM table_compte WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return $user["ID_compte"];
    }
    return null;
}

// ======================= 11. VÉRIFIER SI ADMIN =======================
/* Vérifie si un compte est administrateur
   Retourne true si l'utilisateur est admin, false sinon */
/*function est_admin($bdd, $email) {
    $stmt = $bdd->prepare("SELECT admin FROM table_compte WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return $user["admin"] == 1;
    }
    return false;
}*/

// ======================= 12. VÉRIFIER SI COMPTE EN COURS DE VALIDATION =======================
/* Vérifie si le compte est en cours de validation
   Retourne true si validation en cours, false sinon */
/*function en_cours_validation($bdd, $email) {
    $stmt = $bdd->prepare("SELECT validation FROM table_compte WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return $user["validation"] == 1; // 1 = en cours de validation
    }
    return false;
}*/
?>
