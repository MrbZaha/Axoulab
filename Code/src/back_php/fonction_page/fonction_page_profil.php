<?php
// ======================= FONCTIONS =======================
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

// Fonction pour modifier le mot de passe
function modifier_mdp($bdd, $mdp, $user_ID) {
    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    $update = $bdd->prepare("UPDATE compte SET Mdp = ? WHERE ID_compte = ?");
    return $update->execute([$hash, $user_ID]);
}

// Fonction pour vérifier que deux mots de passe sont identiques
function mot_de_passe_identique($mdp1, $mdp2) {
    return $mdp1 === $mdp2;
}

// Fonction pour modifier la photo de profil
function modifier_photo_de_profil($user_ID) {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return;
    }

    $tmp = $_FILES['photo']['tmp_name'];
    $type = exif_imagetype($tmp);

    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
        // Popup d'erreur
        echo '
        <div class="popup-overlay">
            <div class="popup-box">
                <h3>❌ Fichier non accepté</h3>
                <p>Seuls les formats <strong>JPEG</strong> et <strong>PNG</strong> sont autorisés.</p>
                <a href="#" class="popup-close">Fermer</a>
            </div>
        </div>';
        return;
    }

    // Charger l'image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($tmp);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($tmp);
            break;
        default:
            return;
    }

    if (!$image) return;

    // Destination en PNG
    $destination = "../assets/profile_pictures/" . $user_ID . ".png";
    imagepng($image, $destination);
    imagedestroy($image);
}
?>