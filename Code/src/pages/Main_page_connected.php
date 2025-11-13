<?php

session_start();

try {
    // Connexion à la base de données MySQL avec PDO
    // Remplacez 'Axel' et 'zaza123' par vos identifiants MySQL personnels
    $bdd = new PDO("mysql:host=localhost;dbname=projet_site_web;charset=utf8", "caca", "juliette74");
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}


function find_last_projects (){
    $reponse = $bdd->query("SELECT * FROM Projet ORDER BY Date_de_creation DESC LIMIT 0,9");

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
    <nav class="site_nav">
        <div id="site_nav_main">
            <a class="lab_logo">
                <img src="logo_labo.jpg" alt="Logo_labo">
                </a>
            <form action="/search" method="GET">
            <input type="text" name="q" placeholder="Rechercher..." />
            <button type="submit">🔍</button>
                </form>                
            </div>
        <div id="site_nav_links">
            <ul class="liste_links">
            <li class="main_links" >
                <a href="/contacts" class="Links">
                    Contacts
                </a>
            </li>
            <li class="main_links" >
                <a href="/explorer" class="Links">
                    Explorer
                </a>
            </li>
            <li class="main_links" >
                <a href="/mes_experiences" class="Links">
                    Mes expériences
                </a>
            </li>
            <li class="main_links" >
                <a href="/mes_projets" class="Links">
                    Mes projets
                </a>
            </li>
            <li id="Notif">
                <a class="notif_logo">
                <img src="Notification_logo.png" alt="Logo_notif">
                    </a>
            </li>
            <li id="User">
                <a class="user_logo">
                <img src="/Assets/Balblalba.png" alt="User_notif">
                    </a>
            </li>
        </ul>
        </div>
        </nav>
        

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
