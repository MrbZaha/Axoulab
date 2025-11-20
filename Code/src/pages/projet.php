<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$_SESSION['ID_compte'] = 3; // TEMPORAIRE pour test
$bdd = connectBDD();
$id_compte = $_SESSION['ID_compte'];
$id_projet = isset($_GET['id_projet']) ? (int)$_GET['id_projet'] : 0;

// Variable pour stocker les erreurs
$erreur = null;

if ($id_projet === 0) {
    $erreur = "❌ ID de projet manquant.";
}



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
    $id = htmlspecialchars($exp['ID_experience']);
    $nom = htmlspecialchars($exp['Nom']);
    $description = $exp['Description'];
    $desc = strlen($description) > 200 
        ? htmlspecialchars(substr($description, 0, 200)) . '…'
        : htmlspecialchars($description);
    $date = htmlspecialchars($exp['Date_reservation']);

    ?>
    <a class="experience-card" href="experience.php?id_experience=<?= $id ?>">
        <h3><?= $nom ?></h3>
        <p><?= $desc ?></p>
        <p><strong>Date :</strong> <?= $date ?></p>
    </a>
    <?php
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

function afficher_projet(array $projet, array $gestionnaires, array $collaborateurs, array $experiences): void {
    ?>
    <div class="projets">

        <!-- Section unique pour le projet -->
        <section class="section-projets">
            <h2><?= htmlspecialchars($projet['Nom_projet']) ?></h2>

            <div class="project-container">

                <div class="project-main">
                    <!-- Description -->
                    <?php afficher_description_projet($projet); ?>

                    <!-- Informations générales -->
                    <?php afficher_informations_projet($projet, $gestionnaires, $collaborateurs); ?>
                </div>

                <!-- Expériences reliées -->
                <?php if (!empty($experiences)): ?>
                    <div class="section-projets">
                        <h3>Expériences liées (<?= count($experiences) ?>)</h3>
                        <?php afficher_experiences($experiences); ?>
                    </div>
                <?php else: ?>
                    <p>Aucune expérience liée à ce projet.</p>
                <?php endif; ?>

            </div>
        </section>

    </div>
    <?php
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
    <?php afficher_projet($projet, $gestionnaires, $collaborateurs, $experiences); ?>
<?php endif; ?>


<?php afficher_Bandeau_Bas(); ?>
</body>
</html>