<?php

include_once "../back_php/fonctions_site_web.php";
$bdd = connectBDD();
$_SESSION["ID_compte"] = 4;
$user_ID = $_SESSION["ID_compte"];

// ======================= FONCTION MODIFIER MDP =======================
/* Met à jour le mot de passe de l'utilisateur après vérifications */
function modifier_mdp($bdd, $mdp, $user_ID) {
    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    $update = $bdd->prepare("UPDATE compte SET Mdp = ? WHERE ID_compte = ?");
    return $update->execute([$hash, $user_ID]);
}

// ======================= FONCTION COMPARAISON MDP =======================
/* Vérifie si deux mots de passe sont identiques */
function mot_de_passe_identique($mdp1, $mdp2) {
    return $mdp1 === $mdp2;
}

// Récupère les informations de l'utilisateur
$requete = $bdd->prepare("SELECT * FROM compte WHERE ID_compte = ?");
$requete->execute([$user_ID]);
$user = $requete->fetch();

// Variables pour le formulaire et les messages
$showForm = false;
$message = "";
$messageType = ""; // 'success' ou 'error'

// Si on clique sur "Changer de mot de passe"
if (isset($_POST['changer_mdp'])) {
    $showForm = true;
}

// Si on valide le nouveau mot de passe
if (isset($_POST['valider_mdp'])) {
    $ancien_mdp = $_POST['ancien_mdp'];
    $nouveau_mdp = $_POST['nouveau_mdp'];
    $confirmer_mdp = $_POST['confirmer_mdp'];
    
    // Vérification 1 : L'ancien mot de passe est correct
    if (password_verify($ancien_mdp, $user['Mdp'])) {
        
        // Vérification 2 : Les nouveaux mots de passe sont identiques
        if (mot_de_passe_identique($nouveau_mdp, $confirmer_mdp)) {
            
            // Tout est OK, on modifie le mot de passe
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Profil utilisateur</title>
  <link rel="stylesheet" href="../css/page_profil_style.css"> <!-- Fichier CSS externe -->
</head>
<body>
  <div class="profil-box">
    <!-- Section photo de profil -->
    <div class="avatar-section">
      <form action="changer_photo.php" method="post" enctype="multipart/form-data">
        <label for="photo">
          <img src="<?php echo $user['photo'] ?? 'default.png'; ?>" alt="Photo de profil" class="avatar" />
        </label>
        <input type="file" name="photo" id="photo" onchange="this.form.submit()" hidden>
      </form>
      <span class="role">Étudiant(e)</span>
    </div>
    
    <!-- Infos personnelles -->
    <div class="infos">
      <p><strong>Nom :</strong> <?php echo htmlspecialchars($user["Nom"]); ?></p>
      <p><strong>Prénom :</strong> <?php echo htmlspecialchars($user["Prenom"]); ?></p>
      <p><strong>Date de naissance :</strong> <?php echo htmlspecialchars($user["Date_de_naissance"]); ?></p>
      <p><strong>Email :</strong> <?php echo htmlspecialchars($user["Email"]); ?></p>
    </div>
    
    <!-- Message de confirmation ou d'erreur -->
    <?php if ($message): ?>
      <p class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></p>
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
  </div>
</body>
</html>