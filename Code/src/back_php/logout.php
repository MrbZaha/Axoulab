<?php
require_once __DIR__ . '/fonctions_site_web.php';

$bdd = connectBDD();
verification_connexion($bdd);
check_csrf();

$_SESSION = [];
session_destroy();

header("Location: ../pages/page_connexion.php");
exit;
