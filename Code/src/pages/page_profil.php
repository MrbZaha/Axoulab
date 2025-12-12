<?php
// Démarrage de la session
session_start();

// Inclusion des fonctions pour la base de données
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_profil.php';

$user_ID = $_SESSION["ID_compte"];

// Connexion à la base de données
$bdd = connectBDD();
// On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// ======================= RÉCUPÉRATION DES INFOS UTILISATEUR =======================

// Préparation et exécution de la requête
$requete = $bdd->prepare("SELECT * FROM compte WHERE ID_compte = ?");
$requete->execute([$user_ID]);
$user = $requete->fetch(PDO::FETCH_ASSOC); // Retourne un tableau associatif
$etat = htmlspecialchars($user['Etat']);   // Récupère le statut de l'utilisateur

// Vérification que l'utilisateur existe
if (!$user) {
    die("Utilisateur non trouvé.");
}

// ======================= GESTION DU CHANGEMENT DE MOT DE PASSE =======================

$showForm = false;
$message = "";
$messageType = ""; // success ou error

if (isset($_POST['changer_mdp'])) {
    $showForm = true;
}

if (isset($_POST['valider_mdp'])) {
    $ancien_mdp = $_POST['ancien_mdp'];
    $nouveau_mdp = $_POST['nouveau_mdp'];
    $confirmer_mdp = $_POST['confirmer_mdp'];

    // Vérification de l'ancien mot de passe
    if (password_verify($ancien_mdp, $user['Mdp'])) {
        // Vérification que les nouveaux mots de passe sont identiques
        if (mot_de_passe_identique($nouveau_mdp, $confirmer_mdp)) {
            if (modifier_mdp($bdd, $nouveau_mdp, $user_ID)) {
                $message = "Mot de passe changé avec succès !";
                $messageType = "success";
                $showForm = false;
            } else {
                $message = "Erreur lors du changement de mot de passe.";
                $messageType = "error";
            }
        } else {
            $message = "Les nouveaux mots de passe ne correspondent pas.";
            $messageType = "error";
        }
    } else {
        $message = "L'ancien mot de passe est incorrect.";
        $messageType = "error";
    }
}

// ======================= GESTION DE LA PHOTO DE PROFIL =======================


// Définition du chemin de la photo de profil
$path = "../assets/profile_pictures/" . $user_ID . ".png";
if (!file_exists($path)) {
    $path = "../assets/profile_pictures/model.jpg";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil utilisateur</title>
    <link rel="stylesheet" href="../css/page_profil.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"])?>
<body>
  
  <div class="profil-box">
    <!-- Section photo de profil -->
    <div class="avatar-section">
      <form method="post" enctype="multipart/form-data">
        <label for="photo">
          <img src="<?= $path . '?t=' . time() ?>" alt="Photo de profil" class="avatar" />
        </label>
        <input type="file" name="photo" id="photo" onchange="this.form.submit()" hidden>
      </form>

      <span class="role"> <?= get_etat($etat) ?> </span>
       <form action="../back_php/logout.php" method="post">
      <input type="submit" value="Déconnexion" class="btn-deconnect">
      </form>
    </div>

    <!-- Infos personnelles -->
    <div class="infos">
      <p><strong>Nom :</strong> <?= htmlspecialchars($user["Nom"]) ?></p>
      <p><strong>Prénom :</strong> <?= htmlspecialchars($user["Prenom"]) ?></p>
      <p><strong>Date de naissance :</strong> <?= htmlspecialchars($user["Date_de_naissance"]) ?></p>
      <p><strong>Email :</strong> <?= htmlspecialchars($user["Email"]) ?></p>
    </div>

    <!-- Message de confirmation ou d'erreur -->
    <?php if ($message): ?>
      <p class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Formulaire de changement de mot de passe -->
    <?php if (!$showForm): ?>
      <form action="" method="post">
        <input type="submit" name="changer_mdp" value="Changer de mot de passe" class="btn-mdp">
      </form>
    <?php else: ?>
      <form action="" method="post" class="mdp-form">
        <input type="password" name="ancien_mdp" placeholder="Ancien mot de passe" required>
        <input type="password" name="nouveau_mdp" placeholder="Nouveau mot de passe" required>
        <input type="password" name="confirmer_mdp" placeholder="Confirmer le nouveau mot de passe" required>
        <input type="submit" name="valider_mdp" value="Valider" class="btn-mdp">
      </form>
    <?php endif; ?>


    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        modifier_photo_de_profil($user_ID);
    }?>

    

  </div>
<?php afficher_Bandeau_Bas() ?>

<!-- <div id="popup-photo" class="popup-overlay">
    <div class="popup-box">
        <h3>❌ Fichier non accepté</h3>
        <p>Seuls les formats <strong>JPEG</strong> et <strong>PNG</strong> sont autorisés.</p>
        <a href="#" class="popup-close">Fermer</a>
    </div>
</div> -->

</body>
</html>
