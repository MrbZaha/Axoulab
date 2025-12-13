<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_projet.php';

$bdd = connectBDD();
// On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

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
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
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
            <?php
            if (est_gestionnaire($bdd,$_SESSION['ID_compte'],$id_projet) || est_admin_par_id($bdd,$_SESSION['ID_compte'])){ ?>
            <form action="page_supprimer_projet.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce projet ?');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id_projet" value="<?= $id_projet ?>">
                <input type="submit" value="Supprimer le projet" />
            </form>
            <?php }?>
            <form action="page_creation_experience_1.php?id_projet=<?= $id_projet ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="submit" value="Ajouter une expérience" />
            </form>
            <form action="page_modification_projet.php?id_projet=<?= $id_projet ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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