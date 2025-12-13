<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';


require_once __DIR__ . '/../back_php/fonction_page/fonction_page_rechercher.php';

$bdd = connectBDD();

$id_compte = $_SESSION['ID_compte'];
// On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

$page_actuelle = $_GET['page'] ?? 1;
$projet_exp   = $_GET['type'] ?? [];          // 'projet' et/ou 'experience'
$tri          = $_GET['tri'] ?? 'A-Z';        // 'A-Z', 'date_modif', 'date_creation'
$ordre        = $_GET['ordre'] ?? 'asc';      // 'asc' ou 'desc'
$texte        = $_GET['texte'] ?? null;

// Confidentialité projets : true = afficher confidentiels, null = ne pas filtrer
$confid = isset($_GET['afficher_confidentiels']) ? 1 : null;

// Statut projet : true = afficher projets finis, null = ne pas filtrer
$statut_proj = isset($_GET['afficher_projets_finis']) ? 1 : null;

// Statut expérience : tableau de valeurs 'fini', 'encours', 'pascommence'
$statut_exp = [];
if (isset($_GET['statut_exp_fini'])) {
    $statut_exp[] = '2';
}
if (isset($_GET['statut_exp_encours'])) {
    $statut_exp[] = '1';
}
if (isset($_GET['statut_exp_pascommence'])) {
    $statut_exp[] = '0';
}

$items_par_page=10;

$liste_mixte=filtrer_trier_pro_exp($bdd, $id_compte, $projet_exp, $tri, $ordre, $texte, $confid, $statut_proj, $statut_exp);
$total_pages=create_page($liste_mixte,$items_par_page);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rechercher</title>
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_mes_experiences.css">
    <link rel="stylesheet" href="../css/page_mes_projets.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="../css/page_rechercher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte, $recherche=false) ?>

<h1>Que recherchez-vous?</h1>

<form method="GET" action="page_rechercher.php" style="text-align:center;">

    <!-- Barre de recherche principale -->
    <div class="searchbar">
        <input type="text" name="texte" value="<?= htmlspecialchars($_GET['texte'] ?? '') ?>" placeholder="Tapez votre recherche...">
    </div>

    <!-- Bouton Rechercher principal -->
    <button type="submit" class="search-btn">Rechercher</button>

    <!-- Container pour toggle + menu avancé -->
    <div class="advanced-container">

        <!-- Checkbox invisible pour contrôler le menu -->
        <input type="checkbox" id="toggle-adv" class="adv-toggle">
        <label for="toggle-adv" class="adv-btn">Recherche avancée</label>

        <!-- Menu avancé -->
        <div class="adv-menu">

            <!-- Type -->
            <div class="adv-row">
                <span class="adv-label">Type :</span>
                <input type="checkbox" id="type-projet" name="type[]" value="projet" <?= in_array('projet', $_GET['type'] ?? []) ? 'checked' : '' ?>>
                <label for="type-projet">Projet</label>

                <input type="checkbox" id="type-exp" name="type[]" value="experience" <?= in_array('experience', $_GET['type'] ?? []) ? 'checked' : '' ?>>
                <label for="type-exp">Expérience</label>
            </div>

            <!-- Options Projet -->
            <div class="adv-row adv-options projet-options">
                <span class="adv-label">Projet :</span>
                
                <!-- Case pour afficher les projets confidentiels -->
                <label>
                    <input type="checkbox" name="afficher_confidentiels" <?= isset($_GET['afficher_confidentiels']) ? 'checked' : '' ?>>
                    Afficher confidentiels
                </label>

                <!-- Case pour afficher les projets finis -->
                <label>
                    <input type="checkbox" name="afficher_projets_finis" <?= isset($_GET['afficher_projets_finis']) ? 'checked' : '' ?>>
                    Afficher projets finis
                </label>
            </div>

            <!-- Options Experience -->
            <div class="adv-row adv-options projet-options">
                <span class="adv-label">Experience :</span>
                
                <!-- Case pour afficher les experiences finies -->
                <label>
                    <input type="checkbox" name="statut_exp_fini" <?= isset($_GET['statut_exp_fini']) ? 'checked' : '' ?>>
                    Afficher experiences finies
                </label>

                <!-- Case pour afficher les experience en cours -->
                <label>
                    <input type="checkbox" name="statut_exp_encours" <?= isset($_GET['statut_exp_encours']) ? 'checked' : '' ?>>
                    Afficher experiences en cours
                </label>

                <!-- Case pour afficher les experience pas commencées -->
                <label>
                    <input type="checkbox" name="statut_exp_pascommence" <?= isset($_GET['statut_exp_pascommence']) ? 'checked' : '' ?>>
                    Afficher experiences à venir
                </label>
            </div>




            <!-- --------------------------- -->
            <!-- Options de tri / ordre      -->
            <!-- --------------------------- -->
            <div class="adv-row">
                <span class="adv-label">Trier par :</span>
                <select name="tri">
                    <option value="A-Z" <?= ($_GET['tri'] ?? '')=='A-Z' ? 'selected' : '' ?>>A-Z</option>
                    <option value="date_modif" <?= ($_GET['tri'] ?? '')=='date_modif' ? 'selected' : '' ?>>Date de modification</option>
                    <option value="date_creation" <?= ($_GET['tri'] ?? '')=='date_creation' ? 'selected' : '' ?>>Date de création</option>
                </select>
            </div>

            <div class="adv-row">
                <span class="adv-label">Ordre :</span>
                <label><input type="radio" name="ordre" value="asc" <?= ($_GET['ordre'] ?? '')=='asc' ? 'checked' : '' ?>> Croissant</label>
                <label><input type="radio" name="ordre" value="desc" <?= ($_GET['ordre'] ?? '')=='desc' ? 'checked' : '' ?>> Décroissant</label>
            </div>

        </div>
    </div>
</form>


<div class="projets">
    <section class="section-projets">
            <?php 
        // Affiche les projets et expériences filtrés/tris
        afficher_projets_experiences_pagines($liste_mixte, $page_actuelle, $items_par_page);

        // Pagination
        afficher_pagination_mixte($page_actuelle, $total_pages, 'page');
        ?>
    </section>
</div>

<?php afficher_Bandeau_Bas() ?>
</body>
</html>
