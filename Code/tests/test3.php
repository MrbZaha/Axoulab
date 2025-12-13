<?php
/**
 * Script d'extraction de toutes les fonctions du projet
 * Génère une liste complète des fonctions avec leurs signatures
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300); // 5 minutes

// Définir le chemin de base du projet
define('PROJECT_BASE', __DIR__ . '/../src/back_php/');

/**
 * Extrait les informations d'une fonction depuis le code source
 */
function extraire_info_fonction($content, $nom_fonction) {
    // Trouver la déclaration complète de la fonction
    $pattern = '/function\s+' . preg_quote($nom_fonction) . '\s*\((.*?)\)/s';
    
    if (preg_match($pattern, $content, $match)) {
        $params = trim($match[1]);
        
        // Extraire le commentaire PHPDoc si présent
        $doc_pattern = '/\/\*\*.*?\*\/\s*function\s+' . preg_quote($nom_fonction) . '/s';
        $description = '';
        
        if (preg_match($doc_pattern, $content, $doc_match)) {
            // Nettoyer le commentaire
            $description = trim(preg_replace('/^\s*\*\s?/m', '', $doc_match[0]));
            $description = trim(str_replace(['/**', '*/', 'function ' . $nom_fonction], '', $description));
        }
        
        return [
            'nom' => $nom_fonction,
            'parametres' => $params,
            'description' => $description
        ];
    }
    
    return null;
}

/**
 * Scan récursif de tous les fichiers PHP
 */
function scanner_fonctions($base_dir) {
    $fonctions = [];
    $fichiers_scannes = 0;
    
    if (!is_dir($base_dir)) {
        echo "❌ Erreur: Le répertoire $base_dir n'existe pas\n";
        return $fonctions;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $fichier) {
        if ($fichier->isFile() && $fichier->getExtension() === 'php') {
            $fichiers_scannes++;
            $chemin_relatif = str_replace($base_dir, '', $fichier->getRealPath());
            
            try {
                $contenu = file_get_contents($fichier->getRealPath());
                
                // Extraire tous les noms de fonctions
                if (preg_match_all('/^\s*function\s+(\w+)\s*\(/m', $contenu, $matches)) {
                    foreach ($matches[1] as $nom_fonction) {
                        $info = extraire_info_fonction($contenu, $nom_fonction);
                        
                        if ($info) {
                            $fonctions[] = [
                                'fichier' => $chemin_relatif,
                                'nom' => $info['nom'],
                                'parametres' => $info['parametres'],
                                'description' => $info['description'],
                                'signature' => $info['nom'] . '(' . $info['parametres'] . ')'
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                echo "⚠️  Erreur lors de la lecture de $chemin_relatif: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Scan terminé: $fichiers_scannes fichiers analysés\n";
    echo "📊 Total: " . count($fonctions) . " fonctions trouvées\n\n";
    
    return $fonctions;
}

/**
 * Génère un fichier JSON avec toutes les fonctions
 */
function generer_json($fonctions, $fichier_sortie = 'fonctions_projet.json') {
    $json = json_encode($fonctions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($fichier_sortie, $json);
    echo "💾 Fichier JSON généré: $fichier_sortie\n";
}

/**
 * Génère un fichier texte lisible
 */
function generer_texte($fonctions, $fichier_sortie = 'fonctions_projet.txt') {
    $contenu = "LISTE DES FONCTIONS DU PROJET AXOULAB\n";
    $contenu .= "====================================\n";
    $contenu .= "Total: " . count($fonctions) . " fonctions\n";
    $contenu .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Grouper par fichier
    $par_fichier = [];
    foreach ($fonctions as $func) {
        $par_fichier[$func['fichier']][] = $func;
    }
    
    foreach ($par_fichier as $fichier => $funcs) {
        $contenu .= "\n" . str_repeat("=", 80) . "\n";
        $contenu .= "📁 FICHIER: $fichier\n";
        $contenu .= str_repeat("=", 80) . "\n\n";
        
        foreach ($funcs as $func) {
            $contenu .= "Fonction: " . $func['signature'] . "\n";
            if (!empty($func['description'])) {
                $contenu .= "Description: " . trim($func['description']) . "\n";
            }
            $contenu .= "\n";
        }
    }
    
    file_put_contents($fichier_sortie, $contenu);
    echo "📄 Fichier texte généré: $fichier_sortie\n";
}

/**
 * Génère un affichage HTML
 */
function generer_html($fonctions, $fichier_sortie = 'fonctions_projet.html') {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Fonctions du Projet Axoulab</title>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                padding: 20px; 
                background: #f8f9fa; 
                max-width: 1200px;
                margin: 0 auto;
            }
            h1 { 
                color: #2c3e50; 
                border-bottom: 3px solid #3498db; 
                padding-bottom: 10px; 
            }
            .stats {
                background: white;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .fichier {
                background: white;
                margin: 20px 0;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .fichier-titre {
                color: #2c3e50;
                font-size: 1.2em;
                font-weight: bold;
                margin-bottom: 15px;
                padding: 10px;
                background: #ecf0f1;
                border-left: 4px solid #3498db;
            }
            .fonction {
                margin: 15px 0;
                padding: 15px;
                background: #f8f9fa;
                border-left: 3px solid #27ae60;
                border-radius: 4px;
            }
            .fonction-nom {
                font-family: 'Courier New', monospace;
                font-size: 1.1em;
                color: #e74c3c;
                font-weight: bold;
            }
            .fonction-params {
                color: #8e44ad;
                font-family: 'Courier New', monospace;
            }
            .fonction-desc {
                margin-top: 8px;
                color: #555;
                font-style: italic;
            }
            .search-box {
                width: 100%;
                padding: 12px;
                font-size: 16px;
                border: 2px solid #3498db;
                border-radius: 8px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <h1>🔧 Fonctions du Projet Axoulab</h1>
        
        <div class="stats">
            <strong>📊 Statistiques:</strong> 
            <?php echo count($fonctions); ?> fonctions trouvées | 
            <?php 
            $fichiers_uniques = array_unique(array_column($fonctions, 'fichier'));
            echo count($fichiers_uniques); 
            ?> fichiers | 
            Généré le <?php echo date('Y-m-d à H:i:s'); ?>
        </div>
        
        <input type="text" class="search-box" id="searchBox" placeholder="🔍 Rechercher une fonction...">
        
        <div id="functions-container">
        <?php
        // Grouper par fichier
        $par_fichier = [];
        foreach ($fonctions as $func) {
            $par_fichier[$func['fichier']][] = $func;
        }
        
        foreach ($par_fichier as $fichier => $funcs):
        ?>
            <div class="fichier">
                <div class="fichier-titre">📁 <?php echo htmlspecialchars($fichier); ?></div>
                
                <?php foreach ($funcs as $func): ?>
                    <div class="fonction" data-search="<?php echo htmlspecialchars(strtolower($func['nom'] . ' ' . $func['parametres'])); ?>">
                        <div class="fonction-nom"><?php echo htmlspecialchars($func['nom']); ?></div>
                        <div class="fonction-params">Paramètres: (<?php echo htmlspecialchars($func['parametres']); ?>)</div>
                        <?php if (!empty($func['description'])): ?>
                            <div class="fonction-desc"><?php echo htmlspecialchars($func['description']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
        
        <script>
            // Fonction de recherche
            document.getElementById('searchBox').addEventListener('input', function(e) {
                const search = e.target.value.toLowerCase();
                const functions = document.querySelectorAll('.fonction');
                
                functions.forEach(func => {
                    const searchText = func.getAttribute('data-search');
                    if (searchText.includes(search)) {
                        func.style.display = 'block';
                    } else {
                        func.style.display = 'none';
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    file_put_contents($fichier_sortie, $html);
    echo "🌐 Fichier HTML généré: $fichier_sortie\n";
}

// ==================== EXÉCUTION ====================

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  EXTRACTEUR DE FONCTIONS - PROJET AXOULAB             ║\n";
echo "╚════════════════════════════════════════════════════════╝\n";
echo "\n";

// Scanner les fonctions
echo "🔍 Scan du répertoire: " . PROJECT_BASE . "\n\n";
$fonctions = scanner_fonctions(PROJECT_BASE);

if (empty($fonctions)) {
    echo "❌ Aucune fonction trouvée. Vérifiez le chemin du projet.\n";
    exit(1);
}

// Générer les fichiers de sortie
echo "\n📦 Génération des fichiers de sortie...\n\n";
generer_json($fonctions);
generer_texte($fonctions);
generer_html($fonctions);

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  ✅ EXTRACTION TERMINÉE AVEC SUCCÈS                   ║\n";
echo "╚════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📋 Prochaines étapes:\n";
echo "   1. Ouvrir 'fonctions_projet.html' dans un navigateur\n";
echo "   2. Ou consulter 'fonctions_projet.txt' pour la liste complète\n";
echo "   3. Ou utiliser 'fonctions_projet.json' pour le traitement automatique\n";
echo "\n";
echo "💡 Une fois que vous aurez la liste, partagez-la moi et je créerai\n";
echo "   les tests unitaires pour chaque fonction !\n";
echo "\n";

?>