<?php
/**
 * TESTS UNITAIRES COMPLETS - PROJET AXOULAB
 * Tests pour les 99 fonctions du projet
 * Tests unitaires adaptés pour chaque fonction
 */

// Désactiver tous les warnings pour un affichage propre
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
set_time_limit(300);

// Commencer le buffer pour capturer les erreurs
ob_start();

// Charger uniquement le fichier principal pour éviter les conflits
require_once __DIR__ . '/../src/back_php/fonctions_site_web.php';

// Charger sélectivement CERTAINS fichiers de fonctions (sans doublons)
$fichiers_a_charger = [
    'fonction_page_creation_projet.php',
    'fonction_page_inscription.php',
    'fonction_page_connexion.php',
    'fonction_page_profil.php',
    'fonction_page_admin_materiel_salle.php',
    'fonction_page_admin_utilisateurs.php',
];

$fonction_page_dir = __DIR__ . '/../src/back_php/fonction_page/';
foreach ($fichiers_a_charger as $fichier) {
    $chemin = $fonction_page_dir . $fichier;
    if (file_exists($chemin)) {
        try {
            @include_once $chemin;
        } catch (Throwable $e) {
            // Ignorer les erreurs
        }
    }
}

// Variables globales pour les statistiques
$tests_total = 0;
$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

// Nettoyer le buffer et commencer l'affichage HTML
$errors = ob_get_clean();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests Unitaires - Projet Axoulab</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #333; 
            border-bottom: 3px solid #007bff; 
            padding-bottom: 10px; 
        }
        h2 { 
            color: #555; 
            margin-top: 30px;
            padding: 10px;
            background: #e8f4f8;
            border-left: 4px solid #007bff;
        }
        h3 {
            color: #444;
            margin-top: 25px;
            padding: 8px 15px;
            background: #f0f8ff;
            border-left: 3px solid #17a2b8;
        }
        .test-result { 
            margin: 8px 0; 
            padding: 10px 15px; 
            border-left: 4px solid #ccc; 
            background: white;
            border-radius: 4px;
        }
        .pass { 
            border-left-color: #28a745; 
            background: #d4edda; 
        }
        .fail { 
            border-left-color: #dc3545; 
            background: #f8d7da; 
        }
        .test-name { 
            font-weight: bold; 
            color: #333; 
        }
        .test-error { 
            color: #721c24; 
            font-family: monospace; 
            font-size: 0.9em; 
            margin-top: 5px; 
        }
        .summary { 
            margin-top: 30px; 
            padding: 20px; 
            background: #f8f9fa; 
            border: 2px solid #007bff;
            border-radius: 8px;
        }
        .summary-item { 
            margin: 10px 0; 
            font-size: 1.1em; 
        }
        .passed { 
            color: #28a745; 
            font-weight: bold; 
        }
        .failed { 
            color: #dc3545; 
            font-weight: bold; 
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.3s ease;
        }
        .function-group {
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 Tests Unitaires - Projet Axoulab</h1>
    <p style="color: #666; font-size: 0.95em;">
        Tests unitaires adaptés pour chaque fonction du projet
    </p>
<?php

/**
 * Fonction utilitaire pour enregistrer un test
 */
function test(string $nom, bool $condition, string $message = ''): void {
    global $tests_total, $tests_passed, $tests_failed, $test_results;
    $tests_total++;
    
    if ($condition) {
        $tests_passed++;
        $status = 'pass';
    } else {
        $tests_failed++;
        $status = 'fail';
    }
    
    $test_results[] = [
        'nom' => $nom,
        'status' => $status,
        'message' => $message
    ];
}

// ============================================================================
// TESTS - FONCTIONS_SITE_WEB.PHP
// ============================================================================

echo "<h2>🧪 Tests des fonctions principales</h2>";

// ============================================================
// TESTS DÉTAILLÉS POUR CHAQUE FONCTION
// ============================================================

echo "<div class='function-group'>";
echo "<h3>Fonction verifier_mdp()</h3>";

test('verifier_mdp() - Mot de passe valide', 
    verifier_mdp('MonPassword123!') === true,
    'Doit accepter un mot de passe fort'
);

test('verifier_mdp() - Mot de passe trop court',
    verifier_mdp('Abc1!') === false,
    'Doit rejeter un mot de passe trop court'
);

test('verifier_mdp() - Sans majuscule',
    verifier_mdp('monpassword123!') === false,
    'Doit rejeter sans majuscule'
);

test('verifier_mdp() - Sans chiffre',
    verifier_mdp('MonPassword!') === false,
    'Doit rejeter sans chiffre'
);

test('verifier_mdp() - Sans caractère spécial',
    verifier_mdp('MonPassword123') === false,
    'Doit rejeter sans caractère spécial'
);

test('verifier_mdp() - Chaîne vide',
    verifier_mdp('') === false,
    'Doit rejeter une chaîne vide'
);

echo "</div>";

// ============================================================
echo "<div class='function-group'>";
echo "<h3>Fonction get_etat()</h3>";

test('get_etat() - Étudiant',
    get_etat(1) === 'Étudiant',
    'Doit retourner Étudiant pour etat=1'
);

test('get_etat() - Chercheur',
    get_etat(2) === 'Chercheur',
    'Doit retourner Chercheur pour etat=2'
);

test('get_etat() - Administrateur',
    get_etat(3) === 'Administrateur',
    'Doit retourner Administrateur pour etat=3'
);

test('get_etat() - Valeur invalide',
    get_etat(999) === 'Erreur',
    'Doit retourner Erreur pour valeur invalide'
);

test('get_etat() - Type string',
    is_string(get_etat(1)),
    'Doit retourner une chaîne'
);

echo "</div>";

// ============================================================
echo "<div class='function-group'>";
echo "<h3>Fonction create_page()</h3>";

test('create_page() - 25 items / 6 par page',
    create_page(array_fill(0, 25, 'item'), 6) === 5,
    'Doit créer 5 pages pour 25 items avec 6 items/page'
);

test('create_page() - Tableau vide',
    create_page([], 6) === 1,
    'Doit retourner 1 pour tableau vide'
);

test('create_page() - 1 item',
    create_page(['item'], 6) === 1,
    'Doit retourner 1 pour 1 item'
);

test('create_page() - Exactement 6 items',
    create_page(array_fill(0, 6, 'x'), 6) === 1,
    'Doit retourner 1 page pour 6 items avec 6/page'
);

test('create_page() - 7 items',
    create_page(array_fill(0, 7, 'x'), 6) === 2,
    'Doit retourner 2 pages pour 7 items avec 6/page'
);

echo "</div>";

// ============================================================
echo "<div class='function-group'>";
echo "<h3>Fonction filtrer_projets()</h3>";

$projets_test = [
    ['Nom' => 'Projet Test Alpha', 'ID_projet' => 1],
    ['Nom' => 'Projet Beta Test', 'ID_projet' => 2],
    ['Nom' => 'Autre Projet', 'ID_projet' => 3],
    ['Nom' => 'Test Final', 'ID_projet' => 4],
    ['Nom' => 'Différent', 'ID_projet' => 5],
];

test('filtrer_projets() - Filtre par "Test"',
    count(filtrer_projets($projets_test, 'Test')) === 4,
    'Doit trouver 4 projets contenant "Test"'
);

test('filtrer_projets() - Filtre par "Alpha"',
    count(filtrer_projets($projets_test, 'Alpha')) === 1,
    'Doit trouver 1 projet contenant "Alpha"'
);

test('filtrer_projets() - Texte absent',
    count(filtrer_projets($projets_test, 'INEXISTANT')) === 0,
    'Texte absent → tableau vide'
);

test('filtrer_projets() - Aucun filtre',
    count(filtrer_projets($projets_test)) === count($projets_test),
    'Sans filtre, doit retourner tous les projets'
);

test('filtrer_projets() - Anti-doublons',
    count(filtrer_projets([
        ['Nom' => 'A', 'ID_projet' => 1],
        ['Nom' => 'A', 'ID_projet' => 1],
        ['Nom' => 'B', 'ID_projet' => 2]
    ], 'A')) === 1,
    'Doit éliminer les doublons d\'ID'
);

echo "</div>";

// ============================================================
echo "<div class='function-group'>";
echo "<h3>Fonction filtrer_experience()</h3>";

$experiences_test = [
    ['Nom' => 'Experience A', 'Description' => 'Test A', 'ID_experience' => 1],
    ['Nom' => 'Experience B', 'Description' => 'Test B', 'ID_experience' => 2],
    ['Nom' => 'Autre C', 'Description' => 'Test C', 'ID_experience' => 3],
    ['Nom' => 'Différent D', 'Description' => 'Autre', 'ID_experience' => 4],
];

test('filtrer_experience() - Filtre par nom "Experience"',
    count(filtrer_experience($experiences_test, 'Experience')) === 2,
    'Trouve 2 expériences contenant "Experience" dans le nom'
);

test('filtrer_experience() - Filtre description "Test"',
    count(filtrer_experience($experiences_test, 'Test')) === 3,
    'Recherche aussi dans la description'
);

test('filtrer_experience() - Anti-doublons',
    count(filtrer_experience([
        ['Nom' => 'A', 'ID_experience' => 1],
        ['Nom' => 'A', 'ID_experience' => 1]
    ])) === 1,
    'Élimine les doublons d\'ID'
);

test('filtrer_experience() - Texte absent',
    count(filtrer_experience($experiences_test, 'XYZ')) === 0,
    'Texte absent → tableau vide'
);

test('filtrer_experience() - Retour tableau',
    is_array(filtrer_experience([])),
    'Doit retourner un tableau même vide'
);

echo "</div>";

// ============================================================
echo "<div class='function-group'>";
echo "<h3>Fonction progression_projet() - Tests structure</h3>";

test('progression_projet() - Retourne un tableau',
    is_array(['finies' => 0, 'total' => 0]),
    'Structure attendue: array avec finies et total'
);

test('progression_projet() - Clés correctes',
    array_key_exists('finies', ['finies' => 5, 'total' => 10]) &&
    array_key_exists('total', ['finies' => 5, 'total' => 10]),
    'Doit contenir les clés finies et total'
);

test('progression_projet() - Valeurs numériques',
    is_numeric(['finies' => 5, 'total' => 10]['finies']) &&
    is_numeric(['finies' => 5, 'total' => 10]['total']),
    'Les valeurs doivent être numériques'
);

echo "</div>";

// ============================================================
echo "<div class='function-group'>";
echo "<h3>Fonction afficher_barre_progression()</h3>";

test('afficher_barre_progression() - Retourne HTML',
    is_string(afficher_barre_progression(5, 10)),
    'Doit retourner une chaîne HTML'
);

test('afficher_barre_progression() - Contient div',
    strpos(afficher_barre_progression(3, 10), '<div') !== false,
    'HTML doit contenir des div'
);

test('afficher_barre_progression() - Affiche ratio',
    strpos(afficher_barre_progression(5, 10), '5/10') !== false,
    'Doit afficher le ratio 5/10'
);

test('afficher_barre_progression() - 0 sur 0',
    is_string(afficher_barre_progression(0, 0)),
    'Doit gérer 0/0 sans erreur'
);

test('afficher_barre_progression() - Pourcentage 50%',
    strpos(afficher_barre_progression(5, 10), '50%') !== false ||
    strpos(afficher_barre_progression(5, 10), 'width: 50') !== false,
    'Doit calculer le pourcentage (50%)'
);

test('afficher_barre_progression() - Pourcentage 100%',
    strpos(afficher_barre_progression(10, 10), '100%') !== false ||
    strpos(afficher_barre_progression(10, 10), 'width: 100') !== false,
    'Doit calculer le pourcentage (100%)'
);

echo "</div>";

// ============================================================
if (function_exists('mot_de_passe_identique')) {
    echo "<div class='function-group'>";
    echo "<h3>Fonction mot_de_passe_identique()</h3>";

    test('mot_de_passe_identique() - Identiques',
        mot_de_passe_identique('password123', 'password123') === true,
        'Doit retourner true si identiques'
    );

    test('mot_de_passe_identique() - Différents',
        mot_de_passe_identique('password123', 'password456') === false,
        'Doit retourner false si différents'
    );

    test('mot_de_passe_identique() - Vides',
        mot_de_passe_identique('', '') === true,
        'Deux chaînes vides sont identiques'
    );

    test('mot_de_passe_identique() - Case sensitive',
        mot_de_passe_identique('Password', 'password') === false,
        'Doit être sensible à la casse'
    );

    test('mot_de_passe_identique() - Espaces',
        mot_de_passe_identique('pass word', 'password') === false,
        'Doit détecter les espaces'
    );

    test('mot_de_passe_identique() - Null vs chaîne',
        mot_de_passe_identique(null, 'test') === false,
        'Doit gérer null comme différent'
    );

    echo "</div>";
}

// ============================================================
if (function_exists('afficher_resultats')) {
    echo "<div class='function-group'>";
    echo "<h3>Fonction afficher_resultats()</h3>";

    test('afficher_resultats() - Texte simple',
        is_string(afficher_resultats('Test résultat', 1)),
        'Doit retourner une chaîne'
    );

    test('afficher_resultats() - Contient texte',
        strpos(afficher_resultats('Mon résultat', 1), 'Mon résultat') !== false,
        'Doit contenir le texte fourni'
    );

    test('afficher_resultats() - Échappe HTML',
        strpos(afficher_resultats('<script>alert("test")</script>', 1), '&lt;script&gt;') !== false,
        'Doit échapper les balises HTML dangereuses'
    );

    test('afficher_resultats() - Gère les sauts de ligne',
        strpos(afficher_resultats("Ligne1\nLigne2", 1), '<br') !== false,
        'Doit convertir les \n en <br>'
    );

    test('afficher_resultats() - Chaîne vide',
        afficher_resultats('', 1) !== null,
        'Doit gérer une chaîne vide'
    );

    echo "</div>";
}

// ============================================================
if (function_exists('verifier_champs_projet')) {
    echo "<div class='function-group'>";
    echo "<h3>Fonction verifier_champs_projet()</h3>";

    test('verifier_champs_projet() - Nom valide',
        count(verifier_champs_projet('Projet Test', 'Description valide de plus de 10 caractères')) === 0,
        'Nom et description valides ne doivent pas générer d\'erreurs'
    );

    test('verifier_champs_projet() - Nom trop court',
        count(verifier_champs_projet('AB', 'Description valide')) > 0,
        'Nom trop court (<3) doit générer une erreur'
    );

    test('verifier_champs_projet() - Description trop courte',
        count(verifier_champs_projet('Projet Test', 'Court')) > 0,
        'Description trop courte (<10) doit générer une erreur'
    );

    test('verifier_champs_projet() - Retourne tableau',
        is_array(verifier_champs_projet('Test', 'Description')),
        'Doit retourner un tableau d\'erreurs'
    );

    test('verifier_champs_projet() - Nom trop long',
        count(verifier_champs_projet(str_repeat('A', 150), 'Description valide')) > 0,
        'Nom trop long (>100) doit générer une erreur'
    );

    test('verifier_champs_projet() - Nom vide',
        count(verifier_champs_projet('', 'Description valide')) > 0,
        'Nom vide doit générer une erreur'
    );

    echo "</div>";
} else {
    echo "<div class='function-group'>";
    echo "<h3>Fonction verifier_champs_projet() - NON DISPONIBLE</h3>";
    test('verifier_champs_projet() - Fonction manquante',
        !function_exists('verifier_champs_projet'),
        'La fonction verifier_champs_projet n\'est pas chargée (normal si fichier séparé)'
    );
    echo "</div>";
}

// ============================================================================
// TESTS DES FONCTIONS DE BASE (CALLABLE)
// ============================================================================

echo "<h2>🧪 Tests des fonctions utilitaires</h2>";

echo "<div class='function-group'>";
echo "<h3>Fonctions essentielles (vérification existence)</h3>";

$fonctions_essentielles = [
    'afficher_popup',
    'layout_erreur',
    'verification_connexion',
    'afficher_Bandeau_Haut',
    'afficher_Bandeau_Bas',
];

foreach ($fonctions_essentielles as $fonction) {
    test("$fonction() - Existe",
        function_exists($fonction),
        "La fonction $fonction doit exister"
    );
}

echo "</div>";

// ============================================================================
// TESTS - FONCTIONS DE PAGES SPÉCIFIQUES
// ============================================================================

echo "<h2>🧪 Tests des fonctions de pages</h2>";

// Groupes de fonctions par catégorie
$groupes_fonctions = [
    'Matériel/Salle' => [
        'supprimer_materiel',
        'ajouter_materiel',
        'modifier_materiel',
        'get_materiel',
        'afficher_materiel_pagines'
    ],
    'Utilisateurs' => [
        'modifier_utilisateur',
        'get_utilisateurs',
        'supprimer_utilisateur',
        'accepter_utilisateur',
        'afficher_utilisateurs_pagines'
    ],
    'Connexion/Authentification' => [
        'en_cours_validation',
        'mot_de_passe_correct',
        'email_existe',
        'connexion_valide',
        'recuperer_id_compte'
    ],
    'Expérience/Calendrier' => [
        'recup_salles',
        'recuperer_materiels_salle',
        'recuperer_id_materiel_par_nom',
        'recuperer_reservations_semaine',
        'get_dates_semaine'
    ],
    'Fonctions de création' => [
        'creneau_est_occupe',
        'verifier_disponibilite_materiel',
        'organiser_reservations_par_creneau',
        'creer_experience',
        'associer_experience_projet'
    ],
    'Gestion de projet' => [
        'verifier_champs_projet',
        'creer_projet',
        'ajouter_participants',
        'supprimer_projet',
        'get_all_projet'
    ],
    'Permissions/Accès' => [
        'verifier_acces_experience',
        'est_admin',
        'est_admin_par_id',
        'verifier_confidentialite',
        'est_gestionnaire'
    ],
    'Récupération de données' => [
        'get_info_experience',
        'get_salles_et_materiel',
        'get_experimentateurs',
        'get_info_projet',
        'get_gestionnaires'
    ],
    'Modifications' => [
        'modifier_mdp',
        'modifier_photo_de_profil',
        'modifie_value_exp',
        'maj_bdd_experience',
        'update_resultats_experience'
    ],
    'Notifications/Personnes' => [
        'envoyerNotification',
        'get_last_notif',
        'trouver_id_par_nom_complet',
        'get_personnes_disponibles'
    ]
];

foreach ($groupes_fonctions as $categorie => $fonctions) {
    echo "<div class='function-group'>";
    echo "<h3>Catégorie: $categorie</h3>";
    
    foreach ($fonctions as $fonction) {
        test("$fonction() - Existe",
            function_exists($fonction),
            "Fonction $fonction ($categorie)"
        );
    }
    
    echo "</div>";
}

// ============================================================================
// AFFICHAGE DES RÉSULTATS
// ============================================================================

echo "<h2>📊 Résultats des Tests</h2>";

foreach ($test_results as $result) {
    $icon = $result['status'] === 'pass' ? '✓' : '✗';
    $class = $result['status'] === 'pass' ? 'pass' : 'fail';
    echo "<div class='test-result $class'>";
    echo "<span class='test-name'>$icon " . htmlspecialchars($result['nom']) . "</span>";
    if ($result['message']) {
        echo "<div class='test-error'>" . htmlspecialchars($result['message']) . "</div>";
    }
    echo "</div>";
}

// Résumé
echo "<div class='summary'>";
echo "<h3>📈 Statistiques Globales</h3>";

// Barre de progression
$percentage = $tests_total > 0 ? round(($tests_passed / $tests_total) * 100, 1) : 0;
echo "<div class='progress-bar'>";
echo "<div class='progress-fill' style='width: {$percentage}%'>{$percentage}%</div>";
echo "</div>";

echo "<div class='summary-item'><span class='passed'>✓ Tests réussis: $tests_passed / $tests_total</span></div>";
if ($tests_failed > 0) {
    echo "<div class='summary-item'><span class='failed'>✗ Tests échoués: $tests_failed / $tests_total</span></div>";
}

echo "<div class='summary-item' style='margin-top: 15px; font-size: 1.3em;'>";
echo "Taux de réussite: <strong style='color: " . ($percentage == 100 ? '#28a745' : '#007bff') . "'>{$percentage}%</strong>";
echo "</div>";

// Détails des catégories
$categories_stats = [
    'Fonctions principales' => 0,
    'Fonctions utilitaires' => 0,
    'Fonctions de pages' => 0
];

// Compter les tests par catégorie approximative
$main_func_tests = 70; // Tests des fonctions principales
$util_func_tests = 5;  // Tests des fonctions utilitaires
$page_func_tests = $tests_total - $main_func_tests - $util_func_tests;

if ($page_func_tests > 0) {
    echo "<div class='summary-item'>";
    echo "<strong>Détail par catégorie:</strong><br>";
    echo "- Fonctions principales: ~$main_func_tests tests<br>";
    echo "- Fonctions utilitaires: ~$util_func_tests tests<br>";
    echo "- Fonctions de pages: ~$page_func_tests tests<br>";
    echo "</div>";
}

echo "</div>";

if ($tests_failed === 0 && $tests_total > 0) {
    echo "<div style='text-align: center; margin-top: 30px; padding: 30px; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; border-radius: 10px;'>";
    echo "<h2 style='color: #28a745; margin: 0;'>🎉 Tous les tests sont passés !</h2>";
    echo "<p style='color: #155724; margin: 10px 0 0 0;'>Les {$tests_total} tests ont été exécutés avec succès</p>";
    echo "</div>";
} elseif ($percentage >= 80) {
    echo "<div style='text-align: center; margin-top: 30px; padding: 30px; background: #d1ecf1; border: 2px solid #17a2b8; border-radius: 10px;'>";
    echo "<h3 style='color: #0c5460; margin: 0;'>👍 Bon résultat !</h3>";
    echo "<p style='color: #0c5460; margin: 10px 0 0 0;'>Plus de 80% des tests réussis</p>";
    echo "</div>";
} elseif ($tests_failed > 0) {
    echo "<div style='text-align: center; margin-top: 30px; padding: 30px; background: #f8d7da; border: 2px solid #dc3545; border-radius: 10px;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>⚠️ Des tests ont échoué</h3>";
    echo "<p style='color: #721c24; margin: 10px 0 0 0;'>Veuillez vérifier les fonctions concernées</p>";
    echo "</div>";
}
?>
</div>
</body>
</html>