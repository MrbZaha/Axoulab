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

function get_experimentateurs(PDO $bdd, int $id_experience): array {
    $sql = "
        SELECT c.Prenom, c.Nom
        FROM experience_experimentateur ee
        JOIN compte c ON ee.ID_compte = c.ID_compte
        WHERE ee.ID_experience = :id_experience
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    $experimentateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $experimentateurs[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $experimentateurs;
}

// Récupération des données seulement si pas d'erreur
$projet = null;
$gestionnaires = [];
$collaborateurs = [];
$experiences = [];

if (!$erreur && $id_projet > 0) {
    $projet = get_info_projet($bdd, $id_compte, $id_projet);
    
    if ($projet === null) {
        $erreur = "⛔ Il s'agit d'un projet confidentiel auquel vous n'avez pas accès, ou le projet n'existe pas.";
    } else {
        $gestionnaires = get_gestionnaires($bdd, $id_projet);
        $collaborateurs = get_collaborateurs($bdd, $id_projet);
        $experiences = get_experiences($bdd, $id_projet);
    }
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
    <!-- Affichage de l'erreur -->
    <div class="project-container">
        <div class="error-message" style="padding: 20px; background-color: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00; text-align: center; margin: 20px auto; max-width: 600px;">
            <?= htmlspecialchars($erreur) ?>
        </div>
    </div>
<?php else: ?>
    <!-- Affichage du projet -->
    <div class="project-container">
        <div class="project-title"><?= htmlspecialchars($projet['Nom_projet']) ?></div>
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
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>