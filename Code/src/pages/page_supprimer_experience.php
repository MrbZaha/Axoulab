<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();

if (!isset($_POST['id_experience'])) {
    die("ID manquant.");
}

$id = intval($_POST['id_experience']);

supprimer_experience($bdd, $id);

// redirection après suppression
header("Location: page_mes_experiences.php?delete=success");
exit;
