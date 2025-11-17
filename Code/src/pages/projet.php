<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$_SESSION['ID_compte'] = 3; // TEMPORAIRE pour test

/*
if (!isset($_SESSION['ID_compte'])) {
    header('Location: login.php');
    exit;
}
*/

$id_compte = $_SESSION['ID_compte'];
$id_projet = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_projet === 0) {
    afficher_Bandeau_Haut($pdo,$_SESSION["ID_compte"]);
    echo "❌ ID de projet manquant.";
    exit;
}

function verifier_confidentialite(PDO $pdo, int $id_compte, int $id_projet): bool {
    $sql = "
        SELECT 
            p.Confidentiel,
            pcg.Statut
        FROM projet p
        LEFT JOIN projet_collaborateur_gestionnaire pcg
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE p.ID_projet = :id_projet
    ";

    $stmt = $pdo->prepare($sql);
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

function get_info_projet(PDO $pdo, int $id_compte, int $id_projet) {
    // Vérification d'accès avant tout
    if (!verifier_confidentialite($pdo, $id_compte, $id_projet)) {
        echo "⛔ Il s'agit d'un projet confidentiel auquel vous n'avez pas accès.";
        exit;
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

    $stmt = $pdo->prepare($sql_projet);
    $stmt->execute([
        'id_compte' => $id_compte,
        'id_projet' => $id_projet
    ]);

    $projet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$projet) {
        afficher_Bandeau_Haut($pdo,$_SESSION["ID_compte"]);
        echo "❌ Désolé, ce projet n'existe pas.";
        exit;
    }

    return $projet;
}

function get_gestionnaires(PDO $pdo, int $id_projet): array {
    $sql = "
        SELECT c.Nom, c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.ID_projet = :id_projet AND pcg.Statut = 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    $gestionnaires = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gestionnaires[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $gestionnaires;
}

function get_collaborateurs(PDO $pdo, int $id_projet): array {
    $sql = "
        SELECT c.Nom, c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.ID_projet = :id_projet AND pcg.Statut = 2
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    $collaborateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $collaborateurs[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $collaborateurs;
}

function get_experiences(PDO $pdo, int $id_projet): array {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Description,
            e.Salle,
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
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_experimentateurs(PDO $pdo, int $id_experience): array {
    $sql = "
        SELECT c.Prenom, c.Nom
        FROM experience_experimentateur ee
        JOIN compte c ON ee.ID_compte = c.ID_compte
        WHERE ee.ID_experience = :id_experience
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    $experimentateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $experimentateurs[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $experimentateurs;
}

// Récupération des données
$projet = get_info_projet($pdo, $id_compte, $id_projet);
$gestionnaires = get_gestionnaires($pdo, $id_projet);
$collaborateurs = get_collaborateurs($pdo, $id_projet);
$experiences = get_experiences($pdo, $id_projet);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($projet['Nom_projet']) ?></title>
    <link rel="stylesheet" href="../css/projet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
</head>
<body>
    <?php
    afficher_Bandeau_Haut($pdo,$_SESSION["ID_compte"]);
    ?>
<div class="project-container">
    <!-- Titre du projet -->
    <div class="project-title"><?= htmlspecialchars($projet['Nom_projet']) ?></div>
    
    <!-- Bloc description et info -->
    <div class="project-main">
        <div class="project-description">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($projet['Description'])) ?></p>
        </div>
        
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
    </div>
    
    <!-- Expériences -->
    <?php if (!empty($experiences)): ?>
    <div class="experiences">
        <h3>Expériences liées au projet</h3>
        <?php foreach ($experiences as $exp): ?>
            <div class="experience-card">
                <div class="experience-header">
                    <h4><?= htmlspecialchars($exp['Nom']) ?></h4>
                    <span class="status <?= $exp['Fin_experience'] ? 'status-termine' : 'status-en_cours' ?>">
                        <?= $exp['Fin_experience'] ? "Terminée" : "En cours" ?>
                    </span>
                </div>
                <p class="experience-lieu">📍 <?= htmlspecialchars($exp['Salle']) ?></p>
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
                <?php if (!empty($exp['Résultats'])): ?>
                    <details class="experience-resultats">
                        <summary>Voir les résultats</summary>
                        <p><?= nl2br(htmlspecialchars($exp['Résultats'])) ?></p>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>