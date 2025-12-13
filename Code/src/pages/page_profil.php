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

// ======================= GESTION DES MESSAGES DE CONFIRMATION =======================

$message = "";

// Messages de confirmation basés sur les paramètres GET
if (isset($_GET['photo']) && $_GET['photo'] === 'ok') {
    $message = afficher_popup("Photo mise à jour", "Votre photo de profil a été changée avec succès !", "success", "page_profil");
}
if (isset($_GET['photo']) && $_GET['photo'] === 'erreur') {
    $message = afficher_popup("Fichier non accepté", "Seuls les formats JPEG et PNG sont autorisés.", "error", "page_profil");
}

// ======================= GESTION DU CHANGEMENT DE MOT DE PASSE =======================

$showForm = false;
$messageType = ""; // success ou error

if (isset($_POST['changer_mdp'])) {
    $showForm = true;
}

if (isset($_POST['valider_mdp'])) {
    $ancien_mdp = $_POST['ancien_mdp'];
    $nouveau_mdp = $_POST['nouveau_mdp'];
    $confirmer_mdp = $_POST['confirmer_mdp'];

    // Vérification de l'ancien mot de passe
    if (!password_verify($ancien_mdp, $user['Mdp'])) {
        $message = afficher_popup("Erreur", "L'ancien mot de passe est incorrect.", "error", "page_profil");
        $showForm = true; // Garder le formulaire affiché
    }
    // Vérification que les nouveaux mots de passe sont identiques
    else if (!mot_de_passe_identique($nouveau_mdp, $confirmer_mdp)) {
        $message = afficher_popup("Erreur", "Les nouveaux mots de passe ne correspondent pas.", "error", "page_profil");
        $showForm = true; // Garder le formulaire affiché
    }
    // Si tout est OK jusqu'ici, on tente la modification
    else {
        $resultat = modifier_mdp($bdd, $nouveau_mdp, $user_ID);
        
        if ($resultat['success']) {
            header("Location: page_profil.php?mdp=ok");
            exit;
        } else {
            // Afficher les erreurs de validation du mot de passe
            $erreurs_text = implode(", ", $resultat['erreurs']);
            $message = afficher_popup("Erreur", "Le mot de passe doit contenir : " . $erreurs_text, "error", "page_profil");
            $showForm = true; // Garder le formulaire affiché
        }
    }
}

if (isset($_GET['mdp']) && $_GET['mdp'] === 'ok') {
    $message = afficher_popup("Succès", "Mot de passe changé avec succès !", "success", "page_profil");
}

// ======================= GESTION DE LA PHOTO DE PROFIL =======================

// Traitement de l'upload de photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $resultat = modifier_photo_de_profil($user_ID);
    
    if ($resultat === true) {
        header("Location: page_profil.php?photo=ok");
    } else if ($resultat === false) {
        header("Location: page_profil.php?photo=erreur");
    }
    exit;
}

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
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_profil.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="../css/popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
  <?php 
  // Affiche la popup si elle existe
  echo $message;
  ?>

  <?php afficher_Bandeau_Haut($bdd, $_SESSION["ID_compte"])?>
  
  <div class="main-content">
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
    </div>
  </div>

  <?php afficher_Bandeau_Bas() ?>
</body>
</html>