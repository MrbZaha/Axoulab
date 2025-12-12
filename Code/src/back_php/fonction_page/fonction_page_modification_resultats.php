<?php
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();
verification_connexion($bdd);

$id_compte = $_SESSION['ID_compte'];
$id_experience = isset($_GET['id_experience']) ? (int)$_GET['id_experience'] : 0;

// ----- Contrôle d'accès (utilise ta fonction existante) -----
if (verifier_acces_experience($bdd, $id_compte, $id_experience) === 'none') {
    http_response_code(403);
    echo "Accès refusé.";
    exit;
}


/**
 * Vérifie si l'utilisateur a le droit d'accéder à une expérience donnée.
 *
 * Cette fonction implémente une logique de contrôle d'accès à trois niveaux :
 * 1. L'utilisateur est directement expérimentateur de cette expérience → accès autorisé
 * 2. L'expérience est liée à un projet non confidentiel → accès autorisé à tous
 * 3. L'expérience est liée à un projet confidentiel → accès uniquement aux gestionnaires du projet
 *
 * Elle vérifie d'abord si l'utilisateur est expérimentateur, puis, si ce n'est pas
 * le cas, remonte au projet parent pour appliquer les règles de confidentialité.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte utilisateur dont on vérifie les droits
 * @param int $id_experience ID de l'expérience à laquelle on souhaite accéder
 *
 * @return str 'modification' si la personne est experimentateur de l'experience ou gestionnaire du projet lié
 *             'acces' si elle est collaborateur du projet ou que le projet n'est pas confidentiel
 *             'none' dans les cas restants
 */
function verifier_acces_experience(PDO $bdd, int $id_compte, int $id_experience): string {
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
        return 'modification'; // L'utilisateur est expérimentateur
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
        return 'none'; // Pas de projet lié ou projet inexistant
    }

    // Si personne gestionnaire -> droit de modification
    else if (isset($result['Statut']) && (int)$result['Statut'] === 1) {
        return 'modification';
    }

    // Si personne collaborateur -> droit d'accès
    else if (isset($result['Statut']) && (int)$result['Statut'] === 0) {
        return 'acces';
    }
    
    // Si projet non confidentiel → accessible
    else if ((int)$result['Confidentiel'] === 0) {
        return 'acces';
    }
    
    else {
        return 'none';
    }
}   


function get_resultats_experience(PDO $bdd, int $id_experience): ?string {
    $sql = "
        SELECT e.Resultat
        FROM experience e
        WHERE e.ID_experience = :id_experience
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row ? $row['Resultat'] : null;
}


/**
 * Met à jour le champ Resulat de l'expérience.
 */
function update_resultats_experience(PDO $bdd, int $id_experience, string $html): bool {
    $sql = "UPDATE experience SET Resultat = :res, Date_de_modification = :date_modif WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    return $stmt->execute([
        'res' => $html,
        'date_modif' => date('Y-m-d'),
        'id_experience' => $id_experience
    ]);
}

// Liste des fichiers non-images (documents, vidéos, audio, code, etc.)
function list_files_for_experience(string $dir): array {
    if (!is_dir($dir)) return [];
    
    // Extensions autorisées (JAMAIS .php, .sql, .sh, .exe, etc. pour la sécurité)
    $extensions = [
        // Documents
        'pdf', 'doc', 'docx', 'txt', 'md', 'csv', 'xlsx', 'xls', 'odt', 'rtf',
        // Code
        'py', 'js', 'html', 'css', 'json', 'xml', 'yaml', 'yml', 'c', 'cpp', 'java', 'r',
        // Vidéo
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv',
        // Audio
        'mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac',
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz'
    ];
    
    $pattern = $dir . "*.{" . implode(',', $extensions) . "}";
    $files = glob($pattern, GLOB_BRACE);
    
    // Aussi chercher les majuscules
    $patternUpper = $dir . "*.{" . implode(',', array_map('strtoupper', $extensions)) . "}";
    $filesUpper = glob($patternUpper, GLOB_BRACE);
    
    $allFiles = array_merge($files ?: [], $filesUpper ?: []);
    sort($allFiles);
    
    return array_map('basename', $allFiles);
}

// Fonction pour obtenir l'icône selon le type de fichier
function get_file_icon(string $filename): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
$icons = [
    // Documents
    'pdf' => 'fa-file-pdf',
    'doc' => 'fa-file-word', 'docx' => 'fa-file-word', 'odt' => 'fa-file-word',
    'txt' => 'fa-file-text', 'md' => 'fa-file-text',
    'csv' => 'fa-file-csv', 'tsv' => 'fa-file-csv', // ajout de tsv
    'xlsx' => 'fa-file-excel', 'xls' => 'fa-file-excel',
    'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint',

    // Code / scripts
    'py' => 'fa-file-code', 'ipynb' => 'fa-file-code', // notebooks Jupyter
    'js' => 'fa-file-code', 'html' => 'fa-file-code',
    'css' => 'fa-file-code', 'json' => 'fa-file-code', 'xml' => 'fa-file-code',
    'c' => 'fa-file-code', 'cpp' => 'fa-file-code', 'java' => 'fa-file-code', 'r' => 'fa-file-code',
    'm' => 'fa-file-code', // Matlab

    // Images / Graphiques
    'png' => 'fa-file-image', 'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image',
    'gif' => 'fa-file-image', 'tif' => 'fa-file-image', 'tiff' => 'fa-file-image',
    'svg' => 'fa-file-image', 'bmp' => 'fa-file-image', 'eps' => 'fa-file-image',

    // Vidéo
    'mp4' => 'fa-file-video', 'avi' => 'fa-file-video', 'mov' => 'fa-file-video',
    'wmv' => 'fa-file-video', 'flv' => 'fa-file-video', 'webm' => 'fa-file-video', 'mkv' => 'fa-file-video',

    // Audio
    'mp3' => 'fa-file-audio', 'wav' => 'fa-file-audio', 'ogg' => 'fa-file-audio',
    'flac' => 'fa-file-audio', 'm4a' => 'fa-file-audio', 'aac' => 'fa-file-audio',

    // Archives / données brutes
    'zip' => 'fa-file-archive', 'rar' => 'fa-file-archive', '7z' => 'fa-file-archive',
    'tar' => 'fa-file-archive', 'gz' => 'fa-file-archive',
    'bz2' => 'fa-file-archive', 'xz' => 'fa-file-archive',

    // Fichiers scientifiques / labo
    'fasta' => 'fa-file-alt', 'fastq' => 'fa-file-alt', // séquences
    'gb' => 'fa-file-alt', // GenBank
    'sdf' => 'fa-file-alt', // chimie
    'mol' => 'fa-file-alt', // chimie
    'pdb' => 'fa-file-alt', // protéines
    'csv' => 'fa-file-csv', // données expérimentales tabulaires
    'tsv' => 'fa-file-csv',
    'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel',
    'mat' => 'fa-file-code', // Matlab
    'rds' => 'fa-file-code', // R
];
    
    return $icons[$ext] ?? 'fa-file';
}

// Fonction pour formater la taille des fichiers
function format_file_size(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}


// add_result.php
// Simple page pour ajouter un texte et des images sans JS.
// NOTE: en production, adapte la validation et ajoute protections CSRF si nécessaire.

// Répertoire de stockage
$uploadDir = "../assets/resultats/" . $id_experience . "/";
$webUploadDir = "../assets/resultats/" . $id_experience . "/"; // chemin relatif pour <img src=>

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


$errors = [];
$messages = [];
$successHtml = null;

// Liste des fichiers existants (images)
function list_images_for_experience(string $dir): array {
    if (!is_dir($dir)) return [];
    $files = glob($dir . "*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}", GLOB_BRACE);
    sort($files);
    return array_map('basename', $files);
}

$existingFiles = list_images_for_experience($uploadDir);
$existingOtherFiles = list_files_for_experience($uploadDir); // ← Ajoute ça
?>