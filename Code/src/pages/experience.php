<?php
require_once __DIR__ . '/../back_php/init_DB.php';
require __DIR__ . '/../back_php/fonctions_site_web.php';

$_SESSION['ID_compte'] = 3; // TEMPORAIRE pour test
$bdd = connectBDD();
$id_compte = $_SESSION['ID_compte'];
$id_experience = isset($_GET['id_experience']) ? (int)$_GET['id_experience'] : 0;

// Variable pour stocker les erreurs
$erreur = null;

// ========== FONCTIONS DE VÉRIFICATION ==========

/**
 * Vérifie si l'utilisateur a accès à une expérience
 * Une expérience est accessible si :
 * - L'utilisateur est expérimentateur de cette expérience
 * - OU le projet lié est non confidentiel
 * - OU l'utilisateur est gestionnaire du projet confidentiel lié
 */
function verifier_acces_experience(PDO $bdd, int $id_compte, int $id_experience): bool {
    // Vérifier si l'utilisateur est expérimentateur
    $sql_experimentateur = "
        SELECT 1 
        FROM experience_experimentateur 
        WHERE ID_experience = :id_experience 
        AND ID_compte = :id_compte
    ";
    $stmt = $bdd->prepare($sql_experimentateur);
    $stmt->execute([
        'id_experience' => $id_experience,
        'id_compte' => $id_compte
    ]);
    
    if ($stmt->fetch()) {
        return true; // L'utilisateur est expérimentateur
    }
    
    // Sinon, vérifier via le projet lié
    $sql_projet = "
        SELECT 
            p.Confidentiel,
            pcg.Statut
        FROM experience e
        LEFT JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        LEFT JOIN projet p ON pe.ID_projet = p.ID_projet
        LEFT JOIN projet_collaborateur_gestionnaire pcg 
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE e.ID_experience = :id_experience
    ";
    
    $stmt2 = $bdd->prepare($sql_projet);
    $stmt2->execute([
        'id_experience' => $id_experience,
        'id_compte' => $id_compte
    ]);
    
    $result = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return false; // Pas de projet lié ou projet inexistant
    }
    
    // Si projet non confidentiel → accessible
    if ((int)$result['Confidentiel'] === 0) {
        return true;
    }
    
    // Si projet confidentiel → accessible uniquement aux gestionnaires
    return isset($result['Statut']) && (int)$result['Statut'] === 1;
}

// ========== FONCTIONS DE RÉCUPÉRATION ==========

function get_info_experience(PDO $bdd, int $id_experience): ?array {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Nom,
            e.Description,
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Validation,
            e.Resultat,
            e.Statut_experience,
            p.ID_projet,
            p.Nom_projet
        FROM experience e
        LEFT JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        LEFT JOIN projet p ON pe.ID_projet = p.ID_projet
        WHERE e.ID_experience = :id_experience
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    $experience = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $experience ?: null;
}

function get_experimentateurs(PDO $bdd, int $id_experience): array {
    $sql = "
        SELECT c.Prenom, c.Nom
        FROM experience_experimentateur ee
        JOIN compte c ON ee.ID_compte = c.ID_compte
        WHERE ee.ID_experience = :id_experience
        ORDER BY c.Nom, c.Prenom
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    $experimentateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $experimentateurs[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $experimentateurs;
}

function get_salles_et_materiel(PDO $bdd, int $id_experience): array {
    $sql = "
        SELECT 
            sm.Salle,
            sm.Materiel,
            sm.Nombre
        FROM salle_experience se
        JOIN salle_materiel sm ON se.ID_salle = sm.ID_salle
        WHERE se.ID_experience = :id_experience
        ORDER BY sm.Salle, sm.Materiel
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ========== FONCTIONS D'AFFICHAGE ==========

function afficher_erreur(string $erreur): void {
    ?>
    <div class="project-container">
        <div class="error-message">
            <?= htmlspecialchars($erreur) ?>
        </div>
    </div>
    <?php
}

function afficher_experience(array $experience, array $experimentateurs, array $salles_materiel): void {
    // Regrouper les salles et le matériel
    $salles = [];
    $materiels = [];
    
    foreach ($salles_materiel as $item) {
        // Ajouter la salle (éviter les doublons)
        if (!in_array($item['Salle'], $salles)) {
            $salles[] = $item['Salle'];
        }
        
        // Ajouter le matériel avec son nombre
        if (!empty($item['Materiel'])) {
            $materiels[] = [
                'nom' => $item['Materiel'],
                'nombre' => $item['Nombre'],
                'salle' => $item['Salle']
            ];
        }
    }
    
    ?>
    <div class="projets">
        <section class="section-projets">
            <h2><?= htmlspecialchars($experience['Nom']) ?></h2>
            
            <div class="project-container">
                <div class="project-main">
                    
                    <!-- Description -->
                    <div class="project-description">
                        <h3>Description</h3>
                        <p><?= nl2br(htmlspecialchars($experience['Description'])) ?></p>
                        
                        <?php if (!empty($experience['Resultat'])): ?>
                            <h3 style="margin-top: 25px;">Résultats</h3>
                            <p><?= nl2br(htmlspecialchars($experience['Resultat'])) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Informations -->
                    <div class="project-info">
                        <h3>Informations</h3>
                        
                        <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($experience['Date_reservation'])) ?></p>
                        <p><strong>Horaires :</strong> <?= substr($experience['Heure_debut'], 0, 5) ?> - <?= substr($experience['Heure_fin'], 0, 5) ?></p>
                        
                        <p><strong>Statut :</strong> 
                            <span class="badge <?= $experience['Statut_experience'] ? 'badge-termine' : 'badge-en-cours' ?>">
                                <?= $experience['Statut_experience'] ? 'Terminée' : 'En cours' ?>
                            </span>
                        </p>
                        
                        <p><strong>Validation :</strong> 
                            <?= $experience['Validation'] ? '✅ Validée' : '⏳ En attente' ?>
                        </p>
                        
                        <?php if (!empty($experience['Nom_projet'])): ?>
                            <h4>Projet lié</h4>
                            <p>
                                <a href="projet.php?id_projet=<?= $experience['ID_projet'] ?>" class="link-projet">
                                    <?= htmlspecialchars($experience['Nom_projet']) ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <h4>Expérimentateur(s)</h4>
                        <p><?= !empty($experimentateurs) ? htmlspecialchars(implode(', ', $experimentateurs)) : "Aucun" ?></p>
                        
                        <?php if (!empty($salles)): ?>
                            <h4>Salle(s) utilisée(s)</h4>
                            <p><?= htmlspecialchars(implode(', ', $salles)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Matériel utilisé -->
                <?php if (!empty($materiels)): ?>
                    <div class="section-projets" style="margin-top: 30px;">
                        <h3>Matériel utilisé (<?= count($materiels) ?>)</h3>
                        <div class="materiel-list">
                            <?php foreach ($materiels as $mat): ?>
                                <div class="materiel-card">
                                    <p><strong><?= htmlspecialchars($mat['nom']) ?></strong></p>
                                    <p>Salle : <?= htmlspecialchars($mat['salle']) ?></p>
                                    <p>Nombre : <?= htmlspecialchars($mat['nombre']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php
}
function maj_bdd_experience(PDO $bdd) {
    $now = new DateTime();
    $now_datetime = new DateTime($now->format('Y-m-d H:i'));

    // Sélection uniquement des expériences non terminées
    $sql = "
        SELECT ID_experience, 
        Date_reservation, 
        Heure_fin, 
        Statut_experience
        FROM experience
        WHERE Statut_experience = 0
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute();
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($experiences as $exp) {
        // Création d'un DateTime complet pour la fin de l'expérience
        $exp_datetime_fin = new DateTime($exp['Date_reservation'] . ' ' . $exp['Heure_fin']);

        // Mise à jour uniquement si l'expérience est passée
        if ($exp_datetime_fin < $now_datetime) {
            modifie_value_exp($bdd, $exp["ID_experience"], 1);
        }
    }
}

function modifie_value_exp(PDO $bdd, int $id, int $value) {
    $sql_maj_bdd = "
        UPDATE experience
        SET Statut_experience = :Statut_experience
        WHERE ID_experience = :id
    ";

    $stmt = $bdd->prepare($sql_maj_bdd);
    $stmt->execute([
        ':Statut_experience' => $value,
        ':id' => $id
    ]);
}
// METTRE CES FONCTIONS DANS TOUTES LES PAGES RELATIVES AUX EXP2RIENCE
// ========== CHARGEMENT DES DONNÉES ==========

function charger_donnees_experience(PDO $bdd, int $id_compte, int $id_experience): array {
    // Vérifier si l'expérience existe
    $sql_check = "SELECT ID_experience FROM experience WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql_check);
    $stmt->execute(['id_experience' => $id_experience]);
    
    if (!$stmt->fetch()) {
        return [
            'erreur' => "❌ Désolé, cette expérience n'existe pas.",
            'experience' => null,
            'experimentateurs' => [],
            'salles_materiel' => []
        ];
    }
    
    // Vérifier l'accès
    if (!verifier_acces_experience($bdd, $id_compte, $id_experience)) {
        return [
            'erreur' => "⛔ Vous n'avez pas accès à cette expérience.",
            'experience' => null,
            'experimentateurs' => [],
            'salles_materiel' => []
        ];
    }
    
    // Charger toutes les données
    return [
        'erreur' => null,
        'experience' => get_info_experience($bdd, $id_experience),
        'experimentateurs' => get_experimentateurs($bdd, $id_experience),
        'salles_materiel' => get_salles_et_materiel($bdd, $id_experience)
    ];
}

// ========== EXÉCUTION ==========

if ($id_experience === 0) {
    $erreur = "❌ ID d'expérience manquant.";
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
    <link rel="stylesheet" href="../css/experience.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<?php if ($erreur): ?>
    <?php afficher_erreur($erreur); ?>
<?php else: ?>
    <?php afficher_experience($experience, $experimentateurs, $salles_materiel); ?>
<?php endif; ?>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>