<?php

require_once __DIR__ . '/../back_php/fonctions_site_web.php';
require_once __DIR__ . '/../back_php/fonction_page/fonction_page_modification_resultats.php';

$bdd = connectBDD();
verification_connexion($bdd);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

$id_compte = $_SESSION['ID_compte'];
$id_experience = isset($_GET['id_experience']) ? (int)$_GET['id_experience'] : 0;

// ----- Contrôle d'accès (utilise ta fonction existante) -----
if (verifier_acces_experience($bdd, $id_compte, $id_experience) === 'none') {
    http_response_code(403);
    echo "Accès refusé.";
    exit;
}

// --- POST handling: suppression, upload, save ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_only'])) {
    // --- Upload images UNIQUEMENT (sans enregistrer le texte) ---
    $uploadedFiles = [];
    if (!empty($_FILES['images']['name'][0])) {
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $tmpName = $_FILES['images']['tmp_name'][$i];
            $origName = basename($_FILES['images']['name'][$i]);
            $size = $_FILES['images']['size'][$i];
            $error = $_FILES['images']['error'][$i];

            if ($error !== UPLOAD_ERR_OK) {
                if ($error !== UPLOAD_ERR_NO_FILE) $errors[] = "$origName : erreur upload ($error)";
                continue;
            }

            if ($size > 5*1024*1024) { $errors[] = "$origName : fichier trop volumineux (>5MB)"; continue; }

            $imgInfo = @getimagesize($tmpName);
            if (!$imgInfo) { $errors[] = "$origName : fichier non image"; continue; }

            $ext = image_type_to_extension($imgInfo[2], false);
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $newName = $safeName . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

            if (!move_uploaded_file($tmpName, $uploadDir . $newName)) { $errors[] = "$origName : impossible de déplacer"; continue; }

            @chmod($uploadDir . $newName, 0644);
            $uploadedFiles[] = $newName;
            $messages[] = "Fichier uploadé : $newName";
        }
    }
    $existingFiles = list_images_for_experience($uploadDir);
    
    // Récupérer le texte pour le garder dans le textarea
    $text = $_POST['content'] ?? '';
    $initial_textarea_value = $text;
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_files'])) {
    // --- Upload fichiers (non-images) ---
    if (!empty($_FILES['other_files']['name'][0])) {

        // Extensions autorisées

        $authorized = [
            // Documents
            'pdf', 'doc', 'docx', 'txt', 'md', 'csv', 'tsv', 'xlsx', 'xls', 'ppt', 'pptx','odt',

            // Code / scripts
            'py', 'ipynb', 'js', 'html', 'css', 'json', 'xml', 'c', 'cpp', 'java', 'r', 'm',

            // Images / Graphiques
            'png', 'jpg', 'jpeg', 'gif', 'tif', 'tiff', 'svg', 'bmp', 'eps',

            // Vidéo
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv',

            // Audio
            'mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac',

            // Archives / données brutes
            'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz',

            // Fichiers scientifiques / labo
            'fasta', 'fastq', 'gb', 'sdf', 'mol', 'pdb', 'mat', 'rds'
        ];
        
        for ($i = 0; $i < count($_FILES['other_files']['name']); $i++) {
            $tmpName = $_FILES['other_files']['tmp_name'][$i];
            $origName = basename($_FILES['other_files']['name'][$i]);
            $size = $_FILES['other_files']['size'][$i];
            $error = $_FILES['other_files']['error'][$i];

            if ($error !== UPLOAD_ERR_OK) {
                if ($error !== UPLOAD_ERR_NO_FILE) $errors[] = "$origName : erreur upload ($error)";
                continue;
            }

            // Vérifier l'extension
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $authorized)) {
                $errors[] = "$origName : type de fichier non autorisé";
                continue;
            }

            if ($size > 50*1024*1024) { // 50MB max
                $errors[] = "$origName : fichier trop volumineux (>50MB)";
                continue;
            }

            $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origName);
            $newName = pathinfo($safeName, PATHINFO_FILENAME) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

            if (!move_uploaded_file($tmpName, $uploadDir . $newName)) {
                $errors[] = "$origName : impossible de déplacer";
                continue;
            }

            @chmod($uploadDir . $newName, 0644);
            $messages[] = "Fichier uploadé : $newName";
        }
    }
    $existingOtherFiles = list_files_for_experience($uploadDir);
    
    // Récupérer le texte pour le garder dans le textarea
    $text = $_POST['content'] ?? '';
    $initial_textarea_value = $text;
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])) {
    // --- Générer UNIQUEMENT la prévisualisation (sans enregistrer en BDD) ---
    $text = $_POST['content'] ?? '';
    $initial_textarea_value = $text;

    $successHtml=afficher_resultats($text,$id_experience);
}


else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // --- Enregistrer en BDD UNIQUEMENT ---
    $text = $_POST['content'] ?? '';
    
    if (empty($errors)) {
        if (update_resultats_experience($bdd, $id_experience, $text)) {
            $messages[] = "Les résultats ont été enregistrés en base de données.";
        } else {
            $errors[] = "Erreur lors de l'enregistrement en base.";
        }
    }
    $initial_textarea_value = $text;
    
    // Générer aussi la preview après enregistrement
    $successHtml = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $successHtml = nl2br($successHtml);

    if (preg_match_all('/\[\[file:([^\]]+)\]\]/', $text, $matches)) {
        foreach ($matches[1] as $filename) {
            $filename = basename($filename);
            $path = $webUploadDir . $filename;
            if (is_file($uploadDir . $filename)) {
                $imgTag = '<img class="inserted-image" src="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '">';
                $successHtml = str_replace('[[' . 'file:' . $filename . ']]', $imgTag, $successHtml);
            }
        }
    }

    // Remplacer [[link:xxx]] par des liens de téléchargement
    if (preg_match_all('/\[\[link:([^\]]+)\]\]/', $text, $matches)) {
        foreach ($matches[1] as $filename) {
            $filename = basename($filename);
            $path = $webUploadDir . $filename;
            if (is_file($uploadDir . $filename)) {
                $icon = get_file_icon($filename);
                $linkTag = '<a href="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '" download class="file-download-link"><i class="fa ' . $icon . '"></i> ' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</a>';
                $successHtml = str_replace('[[' . 'link:' . $filename . ']]', $linkTag, $successHtml);
            }
        }
    }
} 

else {
    // GET : récupérer texte brut
    $text = get_resultats_experience($bdd, $id_experience) ?? '';
    $initial_textarea_value = $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suppress'])) {
    // --- Suppression de fichiers ---
    if (!empty($_POST['delete_files']) && is_array($_POST['delete_files'])) {
        foreach ($_POST['delete_files'] as $toDelete) {
            $file = basename($toDelete);
            $path = $uploadDir . $file;
            if (is_file($path)) {
                if (unlink($path)) $messages[] = "Fichier supprimé : $file";
                else $errors[] = "Impossible de supprimer $file";
            }
        }
        $existingFiles = list_images_for_experience($uploadDir);
    }
    
    // Récupérer le texte pour le garder
    $text = $_POST['content'] ?? '';
    $initial_textarea_value = $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suppress'])) {
    // --- Suppression de fichiers (images) ---
    if (!empty($_POST['delete_files']) && is_array($_POST['delete_files'])) {
        foreach ($_POST['delete_files'] as $toDelete) {
            $file = basename($toDelete);
            $path = $uploadDir . $file;
            if (is_file($path)) {
                if (unlink($path)) $messages[] = "Fichier supprimé : $file";
                else $errors[] = "Impossible de supprimer $file";
            }
        }
        $existingFiles = list_images_for_experience($uploadDir);
    }
    
    // Récupérer le texte pour le garder
    $text = $_POST['content'] ?? '';
    $initial_textarea_value = $text;
}

// Nouveau bloc pour supprimer les autres fichiers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suppress_other'])) {
    // --- Suppression de fichiers (non-images) ---
    if (!empty($_POST['delete_other_files']) && is_array($_POST['delete_other_files'])) {
        foreach ($_POST['delete_other_files'] as $toDelete) {
            $file = basename($toDelete);
            $path = $uploadDir . $file;
            if (is_file($path)) {
                if (unlink($path)) $messages[] = "Fichier supprimé : $file";
                else $errors[] = "Impossible de supprimer $file";
            }
        }
        $existingOtherFiles = list_files_for_experience($uploadDir);
    }
    
    // Récupérer le texte pour le garder
    $text = $_POST['content'] ?? '';
    $initial_textarea_value = $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_docx'])) {
    export_resultats_docx($text, $uploadDir, "Experience_" . $id_experience . ".docx");
}

$page_title="Modification experience ".$id_experience
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <!--permet d'uniformiser le style sur tous les navigateurs-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="../css/page_modification_resultats.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte); ?>

<div style="max-width:1400px; margin:20px auto 20px auto;">
    <!-- Bouton en haut à gauche -->
    <div style="margin-bottom: 15px;">
        <a href="page_experience.php?id_experience=<?= $id_experience ?>" class="button" style="padding:8px 14px; font-size:14px; display:inline-block;">
            ← Retour
        </a>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1 style="margin:0; font-size:24px;">Résultats — expérience <?= htmlspecialchars($id_experience) ?></h1>
        <a href="#help" class="help-button">
            <i class="fa fa-question-circle"></i> Aide
        </a>
    </div>
</div>



<div class="container">
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

    <div class="content-area">
        <!-- Zone de texte principale -->
        <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <label class="form-label" for="content">Texte (tu peux inclure des balises HTML autorisées - images via placeholders)</label>
            <textarea id="content" name="content" placeholder="Écris ton texte ici..."><?= isset($initial_textarea_value) ? htmlspecialchars($initial_textarea_value, ENT_QUOTES, 'UTF-8') : (isset($_POST['content']) ? htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8') : '') ?></textarea>

            <!-- Section images existantes avec scroll horizontal -->
            <div class="images-section">
                <h3 style="margin-top:0; margin-bottom:10px;">Images existantes (<?= count($existingFiles) ?>)</h3>
                <?php if (count($existingFiles) === 0): ?>
                    <p class="small">Aucune image trouvée pour cette expérience.</p>
                <?php else: ?>
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
                        <button class="button" type="submit" name="suppress">Appliquer suppression</button>
                        <span class="small" style="margin-left:10px;">Cochez une ou plusieurs cases puis cliquez sur "Appliquer suppression"</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section téléversement -->
            <div class="upload-save-section">
                <div class="upload-area">
                    <label class="form-label" for="images">Téléverser des images (png, jpg, gif, webp) — max 5MB chacune</label>
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <input id="images" type="file" name="images[]" accept="image/*" multiple style="flex: 1;">
                        <button type="submit" name="upload_only" class="button">Ajouter les fichiers</button>
                    </div>
                </div>
            </div>

            <!-- Bouton prévisualiser centré en dessous -->
            <div class="preview-button-wrapper">
                <button type="submit" name="preview" class="button" style="font-size: 16px; padding: 12px 24px;">Prévisualiser</button>
            </div>

            <!-- Prévisualisation en pleine largeur -->
            <?php if ($successHtml): ?>
                <div class="preview">
                    <strong>Aperçu du rendu :</strong>
                    <div style="margin-top:8px;">
                        <?= $successHtml /* déjà safe : contient <img> autorisées et <br> */ ?>
                    </div>
                </div>
            <?php endif; ?>
<!-- Section fichiers supplémentaires -->
<div class="files-section" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
    <h3 style="margin-top:0; margin-bottom:15px;">📎 Fichiers supplémentaires (vidéos, documents, code, audio...)</h3>
    
    <!-- Upload de fichiers -->
    <div style="background:#fafafa; border:1px solid #eee; padding:12px; border-radius:8px; margin-bottom:15px;">
        <label class="form-label" for="other_files">Ajouter des fichiers (PDF, vidéo, audio, code, documents...)</label>
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <input id="other_files" type="file" name="other_files[]" multiple style="flex: 1;">
            <button type="submit" name="upload_files" class="button">Ajouter les fichiers</button>
        </div>
        <p class="small" style="margin-top:8px;">Taille max : 50 MB par fichier. Pour insérer un lien : <span class="placeholder-sample">[[link:nom_fichier.ext]]</span></p>
        <p class="small" style="color:var(--danger);">⚠️ Fichiers interdits : .php, .sql, .sh, .exe, .bat, .vbs</p>
    </div>
    
    <!-- Liste des fichiers existants -->
    <?php if (count($existingOtherFiles) > 0): ?>
        <div style="background:#fafafa; border:1px solid #eee; padding:12px; border-radius:8px;">
            <h4 style="margin-top:0; margin-bottom:10px;">Fichiers disponibles (<?= count($existingOtherFiles) ?>)</h4>
            <div class="other-files-list">
                <?php foreach ($existingOtherFiles as $fname): 
                    $filePath = $uploadDir . $fname;
                    $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                    $icon = get_file_icon($fname);
                ?>

            <div class="other-file-item">
                <div style="display:flex; align-items:center; gap:10px; flex:1;">
                    <i class="fa <?= $icon ?>" style="font-size:24px; color:var(--accent);"></i>
                    <div style="flex:1;">
                        <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small"><?= format_file_size($fileSize) ?></div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="<?= htmlspecialchars($webUploadDir . $fname, ENT_QUOTES, 'UTF-8') ?>" download class="button" style="font-size:12px; padding:6px 10px;">
                        <i class="fa fa-download"></i> Télécharger
                    </a>
                    <label style="display:flex; align-items:center; cursor:pointer;">
                        <input class="checkbox" type="checkbox" name="delete_other_files[]" value="<?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="small">Supprimer</span>
                    </label>
                </div>
                <div style="margin-top:8px; width:100%;">
                    <div class="placeholder-sample" style="font-size:12px;">[[link:<?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?>]]</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="margin-top:12px;">
        <button class="button" type="submit" name="suppress_other">Appliquer suppression</button>
    </div>
</div>
<?php else: ?>
<p class="small" style="text-align:center; color:var(--muted);">Aucun fichier supplémentaire pour cette expérience.</p>
<?php endif; ?>
</div>

            <!-- Bouton enregistrer en base à la fin -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <button type="submit" name="save" class="button" style="font-size: 16px; padding: 12px 24px;">
                    💾 Enregistrer
                </button>
                <button type="submit" name="export_docx" class="button" style="font-size: 16px; padding: 12px 24px;">
                    Exporter en Word (.docx)
                </button>
                <button type="submit" name="export_odt" class="button" style="font-size: 16px; padding: 12px 24px;">
                    Exporter en odt (.odt)
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Overlay d'aide -->
<div id="help" class="help-overlay">
    <div class="help-content">
        <a href="#" class="help-close">&times;</a>
        
        <h2>💡 Guide d'utilisation</h2>
        
        <h3>📝 Rédiger du texte</h3>
        <p>Écrivez votre texte dans la zone prévue. Vous pouvez utiliser des retours à la ligne qui seront conservés dans le rendu final.</p>
        
        <h3>🖼️ Insérer des images</h3>
        <p>Il existe deux façons d'ajouter des images :</p>
        
        <h4>1. Images déjà uploadées</h4>
        <ul>
            <li>Consultez la colonne de droite "Images existantes"</li>
            <li>Copiez le placeholder indiqué sous l'image (ex: <span class="placeholder-sample">[[file:image.png]]</span>)</li>
            <li>Collez-le à l'endroit voulu dans votre texte</li>
        </ul>
        
        <h4>2. Nouvelles images</h4>
        <ul>
            <li>Sélectionnez vos images avec le bouton "Téléverser des images"</li>
            <li>Dans votre texte, utilisez <span class="placeholder-sample">[[img1]]</span> pour la première image, <span class="placeholder-sample">[[img2]]</span> pour la seconde, etc.</li>
            <li>L'ordre correspond à l'ordre de sélection des fichiers</li>
        </ul>
        
        <h3>🗑️ Supprimer des images</h3>
        <ol>
            <li>Cochez la case "Supprimer" sous l'image concernée</li>
            <li>Cliquez sur "Appliquer suppression"</li>
            <li>Pensez à retirer le placeholder correspondant de votre texte</li>
        </ol>
        
        <h3>💾 Enregistrer</h3>
        <p>Cliquez sur "Enregistrer" pour sauvegarder vos modifications. Un aperçu du rendu s'affichera automatiquement.</p>
        
        <h3>⚠️ Limites</h3>
        <ul>
            <li>Formats acceptés : PNG, JPG, GIF, WEBP</li>
            <li>Taille maximale : 5 MB par image</li>
        </ul>
    </div>
</div>


</body>
</html>