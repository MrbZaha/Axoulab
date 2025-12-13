<?php
// ======================= FONCTIONS =======================
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

// Fonction pour modifier le mot de passe avec vérification
/**
 * Modifie le mot de passe d'un utilisateur.
 *
 * Cette fonction vérifie d'abord que le mot de passe respecte les critères de sécurité via `verifier_mdp`.
 * Si le mot de passe est valide, il est hashé puis stocké en base de données.
 *
 * @param PDO $bdd Connexion à la base de données
 * @param string $mdp Nouveau mot de passe
 * @param int $user_ID ID de l'utilisateur
 * @return array ['success' => bool, 'erreurs' => array] Indique si l'opération a réussi et liste les erreurs éventuelles
 */
function modifier_mdp($bdd, $mdp, $user_ID) {
    // Vérifier que le mot de passe respecte les critères de sécurité
    $erreurs = verifier_mdp($mdp);
    
    // Si des erreurs sont détectées, on ne modifie pas le mot de passe
    if (!empty($erreurs)) {
        // Retourner false et les erreurs
        return [
            'success' => false,
            'erreurs' => $erreurs
        ];
    }
    
    // Si tout est OK, on procède au hashage et à la mise à jour
    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    $update = $bdd->prepare("UPDATE compte SET Mdp = ? WHERE ID_compte = ?");
    $resultat = $update->execute([$hash, $user_ID]);
    
    // Retourner le résultat
    return [
        'success' => $resultat,
        'erreurs' => []
    ];
}

/**
 * Modifie la photo de profil d'un utilisateur.
 *
 * Cette fonction vérifie d'abord qu'un fichier a été uploadé correctement et qu'il s'agit d'une image au format JPEG ou PNG.
 * L'image est ensuite convertie en PNG et sauvegardée dans le dossier des photos de profil.
 *
 * @param int $user_ID ID de l'utilisateur
 * @return bool|null True si la modification a réussi, false si le format est invalide ou si la sauvegarde a échoué, null si aucun fichier uploadé
 */
function modifier_photo_de_profil($user_ID) {
    // Vérification qu'un fichier a été uploadé
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return null; // Aucun fichier ou erreur d'upload
    }
    $tmp = $_FILES['photo']['tmp_name'];
    $type = exif_imagetype($tmp);

    // Vérification du format
    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
        return false; // Format non accepté
    }

    // Charger l'image selon son type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($tmp);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($tmp);
            break;
        default:
            return false;
    }

    // Vérification que l'image a bien été créée
    if (!$image) {
        return false;
    }

    // Destination en PNG
    $destination = "../assets/profile_pictures/" . $user_ID . ".png";
    
    // Sauvegarde de l'image
    $resultat = imagepng($image, $destination);
    imagedestroy($image);
    
    // Retourne true si la sauvegarde a réussi, false sinon
    return $resultat ? true : false;
}

?>