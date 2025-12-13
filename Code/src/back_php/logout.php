<?php
session_start();
require_once __DIR__ . '/fonctions_site_web.php';

check_csrf();

session_destroy();
header("Location: ../front_php/Main_page.php");
exit;

exit();
