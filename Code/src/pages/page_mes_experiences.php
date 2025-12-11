<?php
session_start();
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// Récupération et filtrage des expériences
$id_compte = $_SESSION['ID_compte'];
$experiences = get_mes_experiences_complets($bdd, $id_compte);

// Séparation en deux listes
$experiences_a_venir = array_filter($experiences, fn($e) => $e['Statut_experience'] == 0);
$experiences_terminees = array_filter($experiences, fn($e) => $e['Statut_experience'] == 1);

// Réindexation des tableaux
$experiences_a_venir = array_values($experiences_a_venir);
$experiences_terminees = array_values($experiences_terminees);

// Configuration pagination
$items_par_page = 6;
$page_a_venir = isset($_GET['page_a_venir']) ? max(1, (int)$_GET['page_a_venir']) : 1;
$page_terminees = isset($_GET['page_terminees']) ? max(1, (int)$_GET['page_terminees']) : 1;

$total_pages_a_venir = create_page($experiences_a_venir, $items_par_page);
$total_pages_terminees = create_page($experiences_terminees, $items_par_page);

// Vérification que les pages demandées existent
if ($page_a_venir > $total_pages_a_venir) $page_a_venir = $total_pages_a_venir;
if ($page_terminees > $total_pages_terminees) $page_terminees = $total_pages_terminees;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes expériences</title>
    <link rel="stylesheet" href="../css/page_mes_experiences.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte)?>
<h1>Mes expériences</h1>

<div class="experiences">
    <section class="section-experiences">
        <h2>Expériences à venir (<?= count($experiences_a_venir) ?>)</h2>
        <?php afficher_experiences_pagines($experiences_a_venir, $page_a_venir, $items_par_page); ?>
        <?php afficher_pagination($page_a_venir, $total_pages_a_venir, 'a_venir'); ?>
    </section>

    <section class="section-experiences">
        <h2>Expériences terminées (<?= count($experiences_terminees) ?>)</h2>
        <?php afficher_experiences_pagines($experiences_terminees, $page_terminees, $items_par_page); ?>
        <?php afficher_pagination($page_terminees, $total_pages_terminees, 'terminees'); ?>
    </section>
</div>

<?php afficher_Bandeau_Bas() ?>
</body>
</html>
