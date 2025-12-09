<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_projet.php';
// Récupération des données
if ($id_projet === 0) {
    $erreur = "ID de projet manquant.";
    $projet = null;
    $gestionnaires = [];
    $collaborateurs = [];
    $experiences = [];
} else {
    $data = charger_donnees_projet($bdd, $id_compte, $id_projet);
    $erreur = $data['erreur'];
    $projet = $data['projet'];
    $gestionnaires = $data['gestionnaires'];
    $collaborateurs = $data['collaborateurs'];
    $experiences = $data['experiences'];
}

$page_title = $projet ? htmlspecialchars($projet['Nom_projet']) : "Projet";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="../css/page_projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<div class="projets">
    <h1>Projet</h1>
    
    <?php if (!$erreur): ?>
        <div class="actions-projet">
            <form action="page_creation_experience_1.php?id_projet=<?= $id_projet ?>" method="post">
                <input type="submit" value="Ajouter une expérience" />
            </form>
            <form action="page_modification_projet.php?id_projet=<?= $id_projet ?>" method="post">
                <input type="submit" value="Modifier le projet" />
            </form>
        </div>
    <?php endif; ?>
    
    <?php if ($erreur): ?>
        <div class="error-message">
            <p><?= htmlspecialchars($erreur) ?></p>
        </div>
    <?php else: ?>
        <?php afficher_projet($projet, $gestionnaires, $collaborateurs, $experiences); ?>
    <?php endif; ?>
</div>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>