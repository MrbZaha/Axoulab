<?php
// --- En-tête : includes et connexion BDD ---
// require/init, session, connexion à la BDD
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
// On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

// --- Vérification et récupération des paramètres ---
// Récupère l'ID du compte en session et l'id_projet passé en GET.
// Définit la variable $erreur si l'id_projet est absent.
$id_compte = $_SESSION['ID_compte'];
$id_projet = isset($_GET['id_projet']) ? (int)$_GET['id_projet'] : 0;

// Variable pour stocker les erreurs
$erreur = null;

if ($id_projet === 0) {
    $erreur = "ID de projet manquant.";
}


/**
 * Vérifie si l'utilisateur a le droit d'accéder au projet.
 *
 * Un projet est accessible si :
 *  - il n'est PAS confidentiel
 *  - OU si l'utilisateur est gestionnaire (Statut = 1)
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte utilisateur
 * @param int $id_projet ID du projet à vérifier
 * @return bool true si accès autorisé, false sinon
 */
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


/**
 * Récupère toutes les informations d’un projet SI l’accès est autorisé.
 *
 * Renvoie un tableau associatif contenant les infos du projet.
 * Si l'utilisateur n'a pas accès ou si le projet n'existe pas → return null.
 *
 * @param PDO $bdd Connexion à la BDD
 * @param int $id_compte ID de l’utilisateur
 * @param int $id_projet ID du projet
 * @return array|null Informations du projet ou null
 */
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

/**
 * Renvoie la liste des gestionnaires du projet.
 *
 * Les gestionnaires sont ceux avec Statut = 1.
 * Le format retourné : ["Prénom Nom", "Prénom Nom", ...]
 *
 * @param PDO $bdd
 * @param int $id_projet
 * @return array Liste des gestionnaires
 */
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

/**
 * Renvoie la liste des collaborateurs du projet.
 *
 * Les collaborateurs sont ceux avec Statut = 2.
 * Le format retourné : ["Prénom Nom", "Prénom Nom", ...]
 *
 * @param PDO $bdd
 * @param int $id_projet
 * @return array Liste des collaborateurs
 */
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

/**
 * Récupère toutes les expériences liées à un projet.
 *
 * Chaque expérience contient :
 *  - Nom
 *  - Description
 *  - Date et heures
 *  - Salle utilisée
 *  - Statut, validation, résultat
 *
 * Les expériences sont triées par date et heure décroissantes.
 *
 * @param PDO $bdd
 * @param int $id_projet
 * @return array Liste d'expériences (tableau associatif)
 */
function get_experiences(PDO $bdd, int $id_projet): array {
    $sql = "
        SELECT DISTINCT
            e.ID_experience,
            e.Description,
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Validation,
            e.Resultat,
            e.Nom,
            e.Statut_experience,
            sm.Nom_salle
        FROM experience e
        INNER JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        INNER JOIN materiel_experience me ON e.ID_experience = me.ID
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

/**
 * Charge toutes les données nécessaires à l'affichage de la page projet.
 *
 * Effectue les étapes suivantes :
 *  1. Vérifie si le projet existe
 *  2. Vérifie si l'utilisateur a accès (confidentialité)
 *  3. Charge :
 *      - Informations du projet
 *      - Gestionnaires
 *      - Collaborateurs
 *      - Expériences
 *
 * Retourne un tableau contenant :
 *   [
 *      'erreur' => null|string,
 *      'projet' => array|null,
 *      'gestionnaires' => array,
 *      'collaborateurs' => array,
 *      'experiences' => array
 *   ]
 *
 * @param PDO $bdd
 * @param int $id_compte
 * @param int $id_projet
 * @return array
 */
function charger_donnees_projet(PDO $bdd, int $id_compte, int $id_projet): array {
    // Vérifier si le projet existe
    $sql_check = "SELECT ID_projet FROM projet WHERE ID_projet = :id_projet";
    $stmt = $bdd->prepare($sql_check);
    $stmt->execute(['id_projet' => $id_projet]);
    
    if (!$stmt->fetch()) {
        return [
            'erreur' => "Désolé, ce projet n'existe pas.",
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
            'erreur' => "Il s'agit d'un projet confidentiel auquel vous n'avez pas accès.",
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

// Titre de la page
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
<h1>Projet</h1>
<div class="actions-projet">
<div class="create-experience">
<form action= "page_creation_experience_1.php?id_projet=<?= $id_projet ?>" method= "post">
    <input type= "submit" value= "Ajouter une expérience" />
</div>
</form>
<div class="modifier-projet">
<form action= "page_modification_projet.php?id_projet=<?= $id_projet ?>" method= "post">
    <input type= "submit" value= "Modifier le projet" />
</div>
</form>
</div>
<?php if ($erreur): ?>
    <?php afficher_erreur($erreur); ?>
<?php else: ?>
    <?php afficher_projet($projet, $gestionnaires, $collaborateurs, $experiences); ?>
<?php endif; ?>

<?php afficher_Bandeau_Bas(); ?>
</body>
</html>