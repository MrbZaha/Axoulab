<?php
session_start();

// Supprime toutes les variables de session
$_SESSION = [];

// Détruit la session
session_destroy();

// Redirige vers la page de connexion
header("Location: ../pages/page_connexion.php");
exit();
