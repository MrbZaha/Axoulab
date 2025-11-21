<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$_SESSION['ID_compte'] = 3; // TEMPORAIRE pour test

$bdd = connectBDD();

function get_mes_experiences_complets(PDO $bdd, int $id_compte): array {
    $sql_experiences = "
        SELECT 
            e.ID_experience, 
            e.Nom, 
            e.Validation, 
            e.Description, 
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Resultat,
            e.Statut_experience,
            s.Salle,
            p.Nom_projet,
            p.ID_projet
        FROM experience e
        LEFT JOIN projet_experience pe
            ON pe.ID_experience = e.ID_experience
        LEFT JOIN projet p
            ON p.ID_projet = pe.ID_projet
        INNER JOIN experience_experimentateur ee
            ON e.ID_experience = ee.ID_experience
        LEFT JOIN salle_experience se
            ON e.ID_experience = se.ID_experience
        LEFT JOIN salle_materiel s
            ON se.ID_salle = s.ID_salle
        WHERE ee.ID_compte = :id_compte
    ";

    $stmt = $bdd->prepare($sql_experiences);
    $stmt->execute(['id_compte' => $id_compte]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($experiences)) {
        return [];
    }
    return $experiences;    
}

function create_page(array $items, int $items_par_page = 6): int {
    $total_items = count($items);
    if ($total_items == 0) {
        return 1;
    }
    return (int)ceil($total_items / $items_par_page);
}

function afficher_experiences_pagines(array $experiences, int $page_actuelle = 1, int $items_par_page = 6): void {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $experiences_page = array_slice($experiences, $debut, $items_par_page);
    
    ?>
    <div class="liste">
        <?php if (empty($experiences_page)): ?>
            <p class="no-experiences">Aucune expérience à afficher</p>
        <?php else: ?>
            <?php foreach ($experiences_page as $exp): ?>
                <?php 
                $id_experience = htmlspecialchars($exp['ID_experience']);
                $nom = htmlspecialchars($exp['Nom']);
                $description = $exp['Description'];
                $desc = strlen($description) > 200 
                    ? htmlspecialchars(substr($description, 0, 200)) . '…'
                    : htmlspecialchars($description);    
                $date_reservation = htmlspecialchars($exp['Date_reservation']);
                $heure_debut = htmlspecialchars($exp['Heure_debut']);
                $heure_fin = htmlspecialchars($exp['Heure_fin']);
                $salle = htmlspecialchars($exp['Salle'] ?? 'Non définie');
                $nom_projet = htmlspecialchars($exp['Nom_projet'] ?? 'Sans projet');
                $id_projet = htmlspecialchars($exp['ID_projet']);
                ?>
                
                <a class='experience-card' href='page_experience.php?id_projet=<?= $id_projet ?>&id_experience=<?= $id_experience ?>'>
                    <div class="experience-header">
                        <h3><?= $nom ?></h3>
                        <span class="projet-badge"><?= $nom_projet ?></span>
                    </div>
                    <p class="description"><?= $desc ?></p>
                    <div class="experience-details">
                        <p><strong>Date :</strong> <?= $date_reservation ?></p>
                        <p><strong>Horaires :</strong> <?= $heure_debut ?> - <?= $heure_fin ?></p>
                        <p><strong>Salle :</strong> <?= $salle ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function afficher_pagination(int $page_actuelle, int $total_pages, string $type = 'a_venir'): void {
    if ($total_pages <= 1) return;
    
    // Préserver l'autre paramètre de page dans l'URL
    $autre_type = ($type === 'a_venir') ? 'terminees' : 'a_venir';
    $autre_page = isset($_GET["page_$autre_type"]) ? (int)$_GET["page_$autre_type"] : 1;
    
    ?>
    <div class="pagination">
        <?php if ($page_actuelle > 1): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle - 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">« Précédent</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page_actuelle): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page_<?= $type ?>=<?= $i ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page_actuelle < $total_pages): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle + 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">Suivant »</a>
        <?php endif; ?>
    </div>
    <?php
}

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
