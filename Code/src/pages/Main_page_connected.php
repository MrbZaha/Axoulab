<?php
// Inclure le fichier de fonctions
include_once '../back_php/fonctions_site_web.php';
session_start();

try {
    // Connexion à la base de données MySQL avec PDO
    $bdd = connectBDD();
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

function find_last_projects (){
    $reponse = $bdd->query("SELECT * FROM projet JOIN projet_collaborateur_gestionnaire ORDER BY Date_de_creation DESC LIMIT 0,9");

    while ($donnees = $reponse->fetch()) {
      echo $donnees['Nom_projet'];
      echo $donnees['Description'];
      echo $donnees['Validation'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset= "utf-8"/>
        <link rel="stylesheet" href="../css/Main_page_connected.css">
        <title>AxouLab</title>
</head>
<body>
    <?php
    afficher_Bandeau_Haut($bdd,$_SESSION["ID_compte"]);
    ?>
<div class="slider">
  <!-- Radios -->
  <input type="radio" name="slider" id="slide1" checked>
  <input type="radio" name="slider" id="slide2">
  <input type="radio" name="slider" id="slide3">

  <!-- Slides -->
  <div class="slides">
    <div class="slide s1">
      <img src="exemple1.jpg" alt="Image 1">
      <label for="slide3" class="prev">◀</label>
      <label for="slide2" class="next">▶</label>
    </div>

    <div class="slide s2">
      <img src="exemple2.jpg" alt="Image 2">
      <label for="slide1" class="prev">◀</label>
      <label for="slide3" class="next">▶</label>
    </div>

    <div class="slide s3">
      <img src="exemple3.jpg" alt="Image 3">
      <label for="slide2" class="prev">◀</label>
      <label for="slide1" class="next">▶</label>
    </div>
  </div>
</div>

<h1 style="text-align:center; color:#003366;">Derniers Projets</h1>

<div class="grille-projets">
<?php while ($donnees = $reponse->fetch()) { ?>
    <div class="projet">
        <h2><?= htmlspecialchars($donnees['Nom_du_projet']) ?></h2>
        <p><?= htmlspecialchars($donnees['Description']) ?></p>
        <p><em>Créé le <?= htmlspecialchars($donnees['Date_de_creation']) ?></em></p>
    </div>
<?php } ?>
</div>

<?php $reponse->closeCursor(); ?>

</body>
</html>
