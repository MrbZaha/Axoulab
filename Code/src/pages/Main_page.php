<?php

require_once '../back_php/fonctions_site_web.php';

session_start();

if  ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset= "utf-8"/>
        <!--permet d'uniformiser le style sur tous les navigateurs-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
        <link rel="stylesheet" href="../css/Main_page.css">
        <title>AxouLab</title>
</head>
<body>
    <!-- On crée un conteneur pour le titre ainsi que les boutons de connexion ou d'inscription -->
    <div class="container">
        <div class="left-section">
            <h1 class="title">Axoulab</h1>
            <p class="subtitle">Le partage nous fait avancer</p>
        </div>
        
        <!-- Cette boîte contiendra les boutons de connexion et d'inscription -->
        <div class="right-section">
            <div class="box">
                <button class="btn btn-login"  onclick="location.href='page_connexion.php'">Connexion</button>
                <button class="btn btn-register"  onclick="location.href='page_inscription.php'">Inscription</button>
            </div>
        </div>
    </div>
</body>
</html>
