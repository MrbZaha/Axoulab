<?php
require_once __DIR__ . '/../back_php/fonctions_site_web.php';
// require_once __DIR__ . '/../back_php/fonctions_page_modification_resultats.php';

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

    return $row['Resultat'];
}

/**
 * Met à jour le champ Resulat de l'expérience.
 */
function update_resultats_experience(PDO $bdd, int $id_experience, string $html): bool {
    $sql = "UPDATE experience SET Resultat = :res WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql);
    return $stmt->execute([
        'res' => $html,
        'id_experience' => $id_experience
    ]);
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
    // Retourner les noms de fichiers (sans chemin serveur)
    $names = [];
    foreach ($files as $f) {
        $names[] = basename($f);
    }
    // trier alphabétiquement (optionnel)
    sort($names);
    return $names;
}

$existingFiles = list_images_for_experience($uploadDir);

// --- POST handling: suppression, upload, save ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Suppression de fichiers cochés
    if (!empty($_POST['delete_files']) && is_array($_POST['delete_files'])) {
        foreach ($_POST['delete_files'] as $toDelete) {
            // nettoyer la valeur (ne garder que le nom de fichier)
            $name = basename($toDelete);
            $path = $uploadDir . $name;
            if (is_file($path)) {
                if (unlink($path)) {
                    $messages[] = "Fichier supprimé : $name";
                } else {
                    $errors[] = "Impossible de supprimer $name";
                }
            } else {
                $errors[] = "Fichier introuvable pour suppression : $name";
            }
        }
        // mettre à jour la liste existante
        $existingFiles = list_images_for_experience($uploadDir);
    }

    // 2) Traitement des uploads (similaire à ton code)
    $uploadedFiles = []; // noms sauvegardés
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $error = $_FILES['images']['error'][$i];
            if ($error !== UPLOAD_ERR_OK) {
                if ($error === UPLOAD_ERR_NO_FILE) continue;
                $errors[] = "Erreur upload fichier #".($i+1)." (code $error).";
                continue;
            }

            $tmpName = $_FILES['images']['tmp_name'][$i];
            $origName = basename($_FILES['images']['name'][$i]);
            $size = $_FILES['images']['size'][$i];

            if ($size > 5 * 1024 * 1024) {
                $errors[] = "$origName : fichier trop volumineux (>5MB).";
                continue;
            }

            $imgInfo = @getimagesize($tmpName);
            if ($imgInfo === false) {
                $errors[] = "$origName : ce n'est pas une image valide.";
                continue;
            }

            $mime = $imgInfo['mime'];
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!in_array($mime, $allowed, true)) {
                $errors[] = "$origName : type non autorisé ($mime).";
                continue;
            }

            $ext = image_type_to_extension($imgInfo[2], false);
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            // éviter collisions : si le nom existe, ajouter suffixe
            $newBase = $safeName . '_' . time() . '_' . bin2hex(random_bytes(4));
            $newName = $newBase . '.' . $ext;
            $dest = $uploadDir . $newName;

            if (!move_uploaded_file($tmpName, $dest)) {
                $errors[] = "Impossible de déplacer $origName.";
                continue;
            }

            // chmod optionnel
            @chmod($dest, 0644);
            $uploadedFiles[] = $newName;
            $messages[] = "Fichier uploadé : $newName";
        }
        // mettre à jour la liste existante après upload
        $existingFiles = list_images_for_experience($uploadDir);
    }

    // 3) Récupérer le texte envoyé (brut)
    $text = $_POST['content'] ?? '';

    // 4) Remplacements :
    // - Remplacer [[file:nom.ext]] par <img src="...nom.ext">
    // - Remplacer placeholders pour fichiers uploadés [[img1]], [[img2]]... dans l'ordre
    // On commence par échapper le texte pour éviter XSS, puis remplacer les placeholders autorisés
    $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); // échappe tout
    $escapedText = nl2br($escapedText); // conserver retours à la ligne

    // a) remplacement des balises [[file:filename.ext]]
    if (preg_match_all('/\[\[file:([^\]\n]+)\]\]/i', $text, $matches)) {
        // matches[1] contient les noms utilisés
        foreach ($matches[1] as $filenameRaw) {
            $filename = basename($filenameRaw);
            $serverPath = $uploadDir . $filename;
            $webPath = $webUploadDir . $filename;
            if (is_file($serverPath)) {
                // Construire balise img (autorisé)
                $imgTag = '<img class="inserted-image" src="' . htmlspecialchars($webPath, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '">';
                // Remplacer la version non échappée ET la version échappée (dans escapedText)
                $escapedText = str_replace(htmlspecialchars('[[' . 'file:' . $filename . ']]', ENT_QUOTES, 'UTF-8'), $imgTag, $escapedText);
                $escapedText = str_replace('[[' . 'file:' . $filename . ']]', $imgTag, $escapedText);
            } else {
                // si le fichier n'existe pas, on laisse le placeholder tel quel (ou on peut afficher message)
                $errors[] = "Image référencée introuvable : $filename";
            }
        }
    }

    // b) remplacement pour les nouveaux fichiers uploadés : [[img1]], [[img2]], ...
    foreach ($uploadedFiles as $index => $filename) {
        $placeholder = '[[' . 'img' . ($index + 1) . ']]';
        $imgTag = '<img class="inserted-image" src="' . htmlspecialchars($webUploadDir . $filename, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '">';
        $escapedText = str_replace(htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'), $imgTag, $escapedText);
        $escapedText = str_replace($placeholder, $imgTag, $escapedText);
    }

    // c) Optionnel : si l'utilisateur avait déjà des placeholders [[img1]] mais sans upload dans ce post,
    // on ne touche pas à ces placeholders (pour éviter remplacements incorrects).

    // 5) Si pas d'erreurs bloquantes, sauvegarder en BDD
    if (empty($errors)) {
        $saved = update_resultats_experience($bdd, $id_experience, $escapedText);
        if ($saved) {
            $messages[] = "Les résultats ont été enregistrés.";
            $successHtml = $escapedText;
        } else {
            $errors[] = "Erreur lors de l'enregistrement en base.";
        }
    }
} else {
    // GET : charger le contenu existant en BDD pour pré-remplir la textarea
    $stored = get_resultats_experience($bdd, $id_experience);
    // si null, initialiser vide
    $stored = $stored ?? '';
    // la textarea doit afficher la version "brute" stockée (donc on n'ajoute pas nl2br ici)
    $initial_textarea_value = $stored;
}
?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="../css/page_modification_resultats.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<div class="container">
    <h1>Modifier les résultats — expérience <?= htmlspecialchars($id_experience) ?></h1>

    <?php if (!empty($messages)): ?>
        <div class="notice success">
            <?php foreach ($messages as $m) echo htmlspecialchars($m) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="notice error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col">
            <form method="post" enctype="multipart/form-data">
                <label class="form-label" for="content">Texte (tu peux inclure des balises HTML autorisées - images via placeholders)</label>
                <textarea id="content" name="content" placeholder="Écris ton texte ici..."><?= isset($initial_textarea_value) ? htmlspecialchars($initial_textarea_value, ENT_QUOTES, 'UTF-8') : (isset($_POST['content']) ? htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8') : '') ?></textarea>

                <p class="small">Pour insérer une image existante : copiez le placeholder indiqué sous l'image (ex. <span class="placeholder-sample">[[file:image.png]]</span>) et collez-le à l'endroit voulu dans le texte.</p>
                <p class="small">Pour insérer une image que vous venez d'uploader : utilisez <span class="placeholder-sample">[[img1]]</span>, <span class="placeholder-sample">[[img2]]</span> ... selon l'ordre des fichiers uploadés dans ce formulaire.</p>

                <label class="form-label" for="images">Téléverser des images (png, jpg, gif, webp) — max 5MB chacune</label>
                <input id="images" type="file" name="images[]" accept="image/*" multiple>

                <div class="form-actions">
                    <button class="button" type="submit">Enregistrer</button>
                    <span class="small">Après enregistrement, la page sera ré-affichée avec le rendu.</span>
                </div>

            </form>

            <?php if ($successHtml): ?>
                <div class="preview panel">
                    <strong>Aperçu rendu (ce qui est enregistré en BDD):</strong>
                    <div style="margin-top:8px;">
                        <?= $successHtml /* déjà safe : contient <img> autorisées et <br> */ ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col" style="max-width:420px;">
            <div class="panel">
                <h3 style="margin-top:0">Images existantes (<?= count($existingFiles) ?>)</h3>
                <?php if (count($existingFiles) === 0): ?>
                    <p class="small">Aucune image trouvée pour cette expérience.</p>
                <?php else: ?>
                    <form method="post" id="filesForm" enctype="multipart/form-data">
                        <div class="files-grid">
                            <?php foreach ($existingFiles as $fname): ?>
                                <div class="file-card">
                                    <img src="<?= htmlspecialchars($webUploadDir . $fname, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="file-meta">
                                        <label style="display:flex;align-items:center;">
                                            <input class="checkbox" type="checkbox" name="delete_files[]" value="<?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?>">
                                            Supprimer
                                        </label>
                                    </div>
                                    <div style="margin-top:6px;">
                                        <div class="small">Placeholder:</div>
                                        <div class="placeholder-sample"><?= '[[' . 'file:' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . ']]' ?></div>
                                        <div class="small" style="margin-top:6px">Nom: <?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top:10px;">
                            <button class="button" type="submit">Appliquer suppression</button>
                            <div class="small" style="margin-top:6px">Cochez une ou plusieurs cases puis cliquez sur "Appliquer suppression"</div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div style="height:12px;"></div>
        </div>
    </div>
</div>
</body>
</html>