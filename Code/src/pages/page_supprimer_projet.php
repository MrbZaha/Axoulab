<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
// On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

if (!isset($_POST['id_experience'])) {
    die("ID manquant.");
}

$id = intval($_POST['id_experience']);

supprimer_experience($bdd, $id);

// redirection après suppression
header("Location: page_mes_experiences.php?delete=success");
exit;
