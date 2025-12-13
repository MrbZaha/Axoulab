<?php
require_once __DIR__ . '/fonctions_site_web.php';

check_csrf();

$_SESSION = [];
session_destroy();

header("Location: ../pages/page_connexion.php");
exit;
