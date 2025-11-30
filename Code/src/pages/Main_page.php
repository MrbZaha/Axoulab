<?php

session_start();

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset= "utf-8"/>
        <link rel="stylesheet" href="../css/Main_page.css">
        <title>AxouLab</title>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h1 class="title">Axoulab</h1>
            <p class="subtitle">Le partage nous fait avancer</p>
        </div>

        <div class="right-section">
            <div class="box">
                <button class="btn btn-login"  onclick="location.href='page_connexion.php'">Connexion</button>
                <button class="btn btn-register"  onclick="location.href='page_inscription.php'">Inscription</button>
            </div>
        </div>
    </div>
</body>
</html>
