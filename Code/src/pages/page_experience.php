<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_experience.php';

if ($id_experience === 0) {
    $erreur = "ID d'expérience manquant.";
    $experience = null;
    $experimentateurs = [];
    $salles_materiel = [];
} else {
    $data = charger_donnees_experience($bdd, $id_compte, $id_experience);
    $erreur = $data['erreur'];
    $experience = $data['experience'];
    $experimentateurs = $data['experimentateurs'];
    $salles_materiel = $data['salles_materiel'];
}

$page_title = $experience ? htmlspecialchars($experience['Nom']) : "Expérience";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
        <link rel="stylesheet" href="../css/Bandeau_haut.css">
        <link rel="stylesheet" href="../css/Bandeau_bas.css">
        <link rel="stylesheet" href="../css/page_experience.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<div class="experience">
    <h1>Experience</h1>
    
<?php if (!$erreur): ?>
    <div class="actions-experience">
        <form action="page_modification_experience.php?id_experience=<?= $id_experience ?>" method="post">
            <input type="submit" value="Modifier l'experience" />
        </form>
    </div>
<?php endif; ?>

<?php if ($erreur): ?>
    <?php afficher_erreur($erreur); ?>
<?php else: ?>
    <?php afficher_experience($experience, $experimentateurs, $salles_materiel); ?>
<?php endif; ?>

</div>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>