<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
// On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

if (!isset($_POST['id_projet'])) {
    die("ID manquant.");
}

$id = intval($_POST['id_projet']);

supprimer_projet($bdd, $id);

// redirection après suppression
header("Location: page_mes_projets.php?delete=success");
exit;
