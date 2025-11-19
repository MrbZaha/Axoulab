<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$_SESSION['ID_compte'] = 3; // TEMPORAIRE pour test
$bdd = connectBDD();

/*
if (!isset($_SESSION['ID_compte'])) {
    header('Location: login.php');
    exit;
}
*/

$id_compte = $_SESSION['ID_compte'];
$id_projet = isset($_GET['id_projet']) ? (int)$_GET['id_projet'] : 0;

// Variable pour stocker les erreurs
$erreur = null;

if ($id_projet === 0) {
    $erreur = "❌ ID de projet manquant.";
}

function verifier_confidentialite(PDO $bdd, int $id_compte, int $id_projet): bool {
    $sql = "
        SELECT 
            p.Confidentiel,
            pcg.Statut
        FROM projet p
        LEFT JOIN projet_collaborateur_gestionnaire pcg
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE p.ID_projet = :id_projet
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'id_compte' => $id_compte,
        'id_projet' => $id_projet
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return false;
    }

    // Projet NON confidentiel → accessible à tout le monde
    if ((int)$result['Confidentiel'] === 0) {
        return true;
    }

    // Projet confidentiel → accessible UNIQUEMENT aux gestionnaires
    return isset($result['Statut']) && (int)$result['Statut'] === 1;
}

function afficher_page_projet(){

}



function get_info_projet(PDO $bdd, int $id_compte, int $id_projet) {
    // Vérification d'accès avant tout
    if (!verifier_confidentialite($bdd, $id_compte, $id_projet)) {
        return null; // Retourne null au lieu de sortir
    }

    // Récupération des informations du projet
    $sql_projet = "
        SELECT 
            p.ID_projet, 
            p.Nom_projet, 
            p.Description, 
            p.Confidentiel, 
            p.Validation, 
            pcg.Statut,
            p.Date_de_creation
        FROM projet p
        LEFT JOIN projet_collaborateur_gestionnaire pcg
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE p.ID_projet = :id_projet
    ";

    $stmt = $bdd->prepare($sql_projet);
    $stmt->execute([
        'id_compte' => $id_compte,
        'id_projet' => $id_projet
    ]);

    $projet = $stmt->fetch(PDO::FETCH_ASSOC);

    return $projet ?: null;
}

function get_gestionnaires(PDO $bdd, int $id_projet): array {
    $sql = "
        SELECT c.Nom, c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.ID_projet = :id_projet AND pcg.Statut = 1
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    $gestionnaires = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gestionnaires[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $gestionnaires;
}

function get_collaborateurs(PDO $bdd, int $id_projet): array {
    $sql = "
        SELECT c.Nom, c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.ID_projet = :id_projet AND pcg.Statut = 2
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    $collaborateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $collaborateurs[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $collaborateurs;
}

function get_experiences(PDO $bdd, int $id_projet): array {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Description,
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Validation,
            e.Resultat,
            e.Nom,
            e.Fin_experience
        FROM experience e
        INNER JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        WHERE pe.ID_projet = :id_projet
        ORDER BY e.Date_reservation DESC, e.Heure_debut DESC
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function charger_donnees_projet(PDO $bdd, int $id_compte, int $id_projet): array {
    // Vérifier si le projet existe
    $sql_check = "SELECT ID_projet FROM projet WHERE ID_projet = :id_projet";
    $stmt = $bdd->prepare($sql_check);
    $stmt->execute(['id_projet' => $id_projet]);
    
    if (!$stmt->fetch()) {
        return [
            'erreur' => "❌ Désolé, ce projet n'existe pas.",
            'projet' => null,
            'gestionnaires' => [],
            'collaborateurs' => [],
            'experiences' => []
        ];
    }
    
    // Le projet existe, vérifier l'accès
    $projet = get_info_projet($bdd, $id_compte, $id_projet);
    
    if ($projet === null) {
        return [
            'erreur' => "⛔ Il s'agit d'un projet confidentiel auquel vous n'avez pas accès.",
            'projet' => null,
            'gestionnaires' => [],
            'collaborateurs' => [],
            'experiences' => []
        ];
    }
    
    // Tout est OK, charger toutes les données
    return [
        'erreur' => null,
        'projet' => $projet,
        'gestionnaires' => get_gestionnaires($bdd, $id_projet),
        'collaborateurs' => get_collaborateurs($bdd, $id_projet),
        'experiences' => get_experiences($bdd, $id_projet)
    ];
}

// Récupération des données
if ($id_projet === 0) {
    $erreur = "❌ ID de projet manquant.";
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

function afficher_projets_pagines(array $projets, int $page_actuelle = 1, int $items_par_page = 6): void {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $projets_page = array_slice($projets, $debut, $items_par_page);
    
    ?>
    <div class="liste">
        <?php if (empty($projets_page)): ?>
            <p class="no-projects">Aucun projet en cours</p>
        <?php else: ?>
            <?php foreach ($projets_page as $p): ?>
                <?php 
                $id = htmlspecialchars($p['ID_projet']);
                $nom = htmlspecialchars($p['Nom_projet']);
                $description = $p['Description'];
                $desc = strlen($description) > 200 
                    ? htmlspecialchars(substr($description, 0, 200)) . '…'
                    : htmlspecialchars($description);
                $date = htmlspecialchars($p['Date_de_creation']);
                $role = $p['Statut'] ? "Gestionnaire" : "Collaborateur";
                ?>
                
                <a class='projet-card' href='projet.php?id_projet=<?= $id ?>'>
                    <h3><?= $nom ?></h3>
                    <p><?= $desc ?></p>
                    <p><strong>Date de création :</strong> <?= $date ?></p>
                    <p><strong>Rôle :</strong> <?= $role ?></p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function create_page(array $items, int $items_par_page = 6): int {
    $total_items = count($items);
    if ($total_items == 0) {
        return 1;
    }
    return (int)ceil($total_items / $items_par_page);
}

function afficher_pagination(int $page_actuelle, int $total_pages, string $type = 'en_cours'): void {
    if ($total_pages <= 1) return;
    
    // Préserver l'autre paramètre de page dans l'URL
    $autre_type = ($type === 'en_cours') ? 'termines' : 'en_cours';
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
// Titre de la page
$page_title = $projet ? htmlspecialchars($projet['Nom_projet']) : "Projet";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="../css/projet.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<?php if ($erreur): ?>
    <?php afficher_erreur($erreur); ?>
<?php else: ?>
    <div class="project-container">
        <!-- En-tête du projet -->
        <?php afficher_en_tete_projet($projet); ?>
        
        <div class="project-main">
            <!-- Description du projet -->
            <?php afficher_description_projet($projet); ?>
            
            <!-- Informations du projet -->
            <?php afficher_informations_projet($projet, $gestionnaires, $collaborateurs); ?>
        </div>
        
        <!-- Expériences -->
        <?php if (!empty($experiences)): ?>
            <?php afficher_experiences($experiences); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>

<?php
// Fonctions d'affichage
function afficher_erreur(string $erreur): void {
    ?>
    <div class="project-container">
        <div class="error-message">
            <?= htmlspecialchars($erreur) ?>
        </div>
    </div>
    <?php
}

function afficher_en_tete_projet(array $projet): void {
    ?>
    <div class="project-title"><?= htmlspecialchars($projet['Nom_projet']) ?></div>
    <?php
}

function afficher_description_projet(array $projet): void {
    ?>
    <div class="project-description">
        <h3>Description</h3>
        <p><?= nl2br(htmlspecialchars($projet['Description'])) ?></p>
    </div>
    <?php
}

function afficher_informations_projet(array $projet, array $gestionnaires, array $collaborateurs): void {
    ?>
    <div class="project-info">
        <h3>Informations</h3>
        <p><strong>Confidentiel :</strong> <?= $projet['Confidentiel'] ? "Oui" : "Non" ?></p>
        <p><strong>Validation :</strong> <?= $projet['Validation'] ? "Validé" : "En attente" ?></p>
        <p><strong>Votre rôle :</strong> <?= $projet['Statut'] == 1 ? "Gestionnaire" : ($projet['Statut'] == 2 ? "Collaborateur" : "Aucun") ?></p>
        <p><strong>Date de création :</strong> <?= date('d/m/Y', strtotime($projet['Date_de_creation'])) ?></p>
        
        <h4>Gestionnaire(s)</h4>
        <p><?= !empty($gestionnaires) ? htmlspecialchars(implode(', ', $gestionnaires)) : "Aucun" ?></p>
        
        <h4>Collaborateur(s)</h4>
        <p><?= !empty($collaborateurs) ? htmlspecialchars(implode(', ', $collaborateurs)) : "Aucun" ?></p>
    </div>
    <?php
}

function afficher_experiences(array $experiences): void {
    ?>
    <div class="experiences">
        <h3>Expériences liées au projet</h3>
        <?php foreach ($experiences as $exp): ?>
            <?php afficher_carte_experience($exp); ?>
        <?php endforeach; ?>
    </div>
    <?php
}

function afficher_carte_experience(array $exp): void {
    $id_experience = $exp['ID_experience'];
    ?>
    <div class="experience-card" onclick="window.location.href='experience.php?id_experience=<?= $id_experience ?>';" style="cursor: pointer;">
        <div class="experience-header">
            <h4><?= htmlspecialchars($exp['Nom']) ?></h4>
            <span class="status <?= $exp['Fin_experience'] ? 'status-termine' : 'status-en_cours' ?>">
                <?= $exp['Fin_experience'] ? "Terminée" : "En cours" ?>
            </span>
        </div>
        <p class="experience-date">
            📅 <?= date('d/m/Y', strtotime($exp['Date_reservation'])) ?> 
            | ⏰ <?= substr($exp['Heure_debut'], 0, 5) ?> - <?= substr($exp['Heure_fin'], 0, 5) ?>
        </p>
        <?php if (!empty($exp['Description'])): ?>
            <p class="experience-description"><?= nl2br(htmlspecialchars($exp['Description'])) ?></p>
        <?php endif; ?>
        <?php if ($exp['Validation']): ?>
            <span class="validation-badge">✓ Validée</span>
        <?php endif; ?>
    </div>
    <?php
}