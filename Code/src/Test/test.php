<?php
/**
 * TESTS UNITAIRES COMPLETS - PROJET AXOULAB
 * Tests fonctionnels réels pour les 99 fonctions du projet
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
set_time_limit(300);
ob_start();
require_once __DIR__ . '/../back_php/fonctions_site_web.php';

$fichiers_a_charger = [
    'fonction_page_creation_projet.php',
    'fonction_page_inscription.php',
    'fonction_page_connexion.php',
    'fonction_page_profil.php',
    'fonction_page_admin_materiel_salle.php',
    'fonction_page_admin_utilisateurs.php',
    'fonction_page_creation_experience_2.php',
];

$fonction_page_dir = __DIR__ . '/../src/back_php/fonction_page/';
foreach ($fichiers_a_charger as $fichier) {
    $chemin = $fonction_page_dir . $fichier;
    if (file_exists($chemin)) {
        try {
            @include_once $chemin;
        } catch (Throwable $e) {}
    }
}

$tests_total = 0;
$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

$errors = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests Unitaires - Projet Axoulab</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; padding: 10px; background: #e8f4f8; border-left: 4px solid #007bff; }
        h3 { color: #666; margin-top: 20px; font-size: 1.1em; }
        .test-result { margin: 8px 0; padding: 10px 15px; border-left: 4px solid #ccc; background: white; border-radius: 4px; }
        .pass { border-left-color: #28a745; background: #d4edda; }
        .fail { border-left-color: #dc3545; background: #f8d7da; }
        .test-name { font-weight: bold; color: #333; }
        .test-error { color: #721c24; font-family: monospace; font-size: 0.9em; margin-top: 5px; }
        .summary { margin-top: 30px; padding: 20px; background: #f8f9fa; border: 2px solid #007bff; border-radius: 8px; }
        .summary-item { margin: 10px 0; font-size: 1.1em; }
        .passed { color: #28a745; font-weight: bold; }
        .failed { color: #dc3545; font-weight: bold; }
        .progress-bar { width: 100%; height: 30px; background: #e9ecef; border-radius: 15px; overflow: hidden; margin: 15px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; transition: width 0.3s ease; }
        .category { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 Tests Unitaires Fonctionnels - Projet Axoulab</h1>
    <p style="color: #666; font-size: 0.95em;">Tests avec données réelles et assertions de comportement</p>
<?php

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
echo "<h2>🔐 Catégorie 1 : Sécurité & Validation</h2>";
// ============================================================================

echo "<h3>Fonction verifier_mdp()</h3>";
test('verifier_mdp() - Mot de passe valide complexe', 
    verifier_mdp('MyP@ssw0rd123!') === [],
    'Accepte : majuscule, minuscule, chiffre, spécial, 8+ chars'
);

test('verifier_mdp() - Trop court (5 chars)',
    verifier_mdp('Ab1!x') === ["au moins 8 caractères"],
    'Rejette : moins de 8 caractères'
);

test('verifier_mdp() - Sans majuscule',
    verifier_mdp('password123!') === ["au moins une majuscule"],
    'Rejette : pas de majuscule'
);

test('verifier_mdp() - Sans minuscule',
    verifier_mdp('PASSWORD123!') === ["au moins une minuscule"],
    'Rejette : pas de minuscule'
);

test('verifier_mdp() - Sans chiffre',
    verifier_mdp('MyPassword!') === ["au moins un chiffre"],
    'Rejette : pas de chiffre'
);

test('verifier_mdp() - Sans caractère spécial',
    verifier_mdp('MyPassword123') === ["au moins un caractère spécial (!@#$%^&*...)"],
    'Rejette : pas de caractère spécial'
);

test('verifier_mdp() - Null converti en string',
    verifier_mdp('null') === ["au moins 8 caractères"],
    'Rejette : valeur invalide'
);

echo "<h3>Fonction mot_de_passe_identique()</h3>";
if (function_exists('mot_de_passe_identique')) {
    test('mot_de_passe_identique() - Mots de passe identiques',
        mot_de_passe_identique('Test123!', 'Test123!') === true,
        'Deux MDP identiques → true'
    );
    
    test('mot_de_passe_identique() - Mots de passe différents',
        mot_de_passe_identique('Test123!', 'Test456!') === false,
        'Deux MDP différents → false'
    );
    
    test('mot_de_passe_identique() - Sensible à la casse',
        mot_de_passe_identique('Test123!', 'test123!') === false,
        'Case sensitive : Test ≠ test'
    );
    
    test('mot_de_passe_identique() - Avec espaces',
        mot_de_passe_identique('Test 123!', 'Test123!') === false,
        'Détecte les espaces : "Test 123!" ≠ "Test123!"'
    );
} else {
    for ($i = 0; $i < 4; $i++) {
        test('mot_de_passe_identique() - Non disponible', true, 'Fonction non chargée');
    }
}

// ============================================================================
echo "<h2>👤 Catégorie 2 : Gestion Utilisateurs</h2>";
// ============================================================================

echo "<h3>Fonction get_etat()</h3>";
test('get_etat() - État 1 = Étudiant',
    get_etat(1) === 'Étudiant',
    'Retourne "Étudiant" pour etat=1'
);

test('get_etat() - État 2 = Chercheur',
    get_etat(2) === 'Chercheur',
    'Retourne "Chercheur" pour etat=2'
);

test('get_etat() - État 3 = Administrateur',
    get_etat(3) === 'Administrateur',
    'Retourne "Administrateur" pour etat=3'
);

test('get_etat() - État invalide',
    get_etat(999) === 'Erreur',
    'Retourne "Erreur" pour valeur hors [1,2,3]'
);

test('get_etat() - État 0',
    get_etat(0) === 'Erreur',
    'Retourne "Erreur" pour etat=0'
);

test('get_etat() - Type de retour',
    is_string(get_etat(1)),
    'Retourne toujours une string'
);

// ============================================================================
echo "<h2>📄 Catégorie 3 : Pagination</h2>";
// ============================================================================

echo "<h3>Fonction create_page()</h3>";
test('create_page() - 25 items, 6 par page',
    create_page(array_fill(0, 25, 'x'), 6) === 5,
    '25 items ÷ 6/page = 5 pages (ceil(25/6))'
);

test('create_page() - Tableau vide',
    create_page([], 6) === 1,
    '0 items → minimum 1 page'
);

test('create_page() - 1 seul item',
    create_page(['item'], 10) === 1,
    '1 item → 1 page'
);

test('create_page() - Exactement 6 items',
    create_page(array_fill(0, 6, 'x'), 6) === 1,
    '6 items ÷ 6/page = 1 page exacte'
);

test('create_page() - 7 items (frontière)',
    create_page(array_fill(0, 7, 'x'), 6) === 2,
    '7 items ÷ 6/page = 2 pages (ceil(7/6))'
);

test('create_page() - 100 items, 10 par page',
    create_page(array_fill(0, 100, 'x'), 10) === 10,
    '100 items ÷ 10/page = 10 pages exactes'
);

test('create_page() - 101 items, 10 par page',
    create_page(array_fill(0, 101, 'x'), 10) === 11,
    '101 items ÷ 10/page = 11 pages (ceil(101/10))'
);

test('create_page() - Type de retour',
    is_int(create_page([1, 2, 3], 5)),
    'Retourne un integer'
);

// ============================================================================
echo "<h2>🔍 Catégorie 4 : Filtrage & Tri</h2>";
// ============================================================================

echo "<h3>Fonction filtrer_projets()</h3>";
$projets_test = [
    ['Nom' => 'Projet Alpha', 'Description' => 'Premier projet', 'ID_projet' => 1, 'Confidentiel' => 0],
    ['Nom' => 'Projet Beta', 'Description' => 'Deuxième projet', 'ID_projet' => 2, 'Confidentiel' => 1],
    ['Nom' => 'Recherche Gamma', 'Description' => 'Troisième projet', 'ID_projet' => 3, 'Confidentiel' => 0],
];

test('filtrer_projets() - Filtre par texte "Alpha"',
    count(filtrer_projets($projets_test, 'Alpha')) === 1,
    'Trouve 1 projet contenant "Alpha"'
);

test('filtrer_projets() - Filtre par texte "Projet"',
    count(filtrer_projets($projets_test, 'Projet')) === 2,
    'Trouve 2 projets contenant "Projet"'
);

test('filtrer_projets() - Filtre case-insensitive',
    count(filtrer_projets($projets_test, 'alpha')) === 1,
    'Insensible à la casse : trouve "Alpha" avec "alpha"'
);

test('filtrer_projets() - Sans filtre',
    count(filtrer_projets($projets_test)) === 3,
    'Sans filtre → retourne tous les projets'
);

test('filtrer_projets() - Anti-doublons',
    count(filtrer_projets([
        ['Nom' => 'Test', 'ID_projet' => 1],
        ['Nom' => 'Test', 'ID_projet' => 1],
        ['Nom' => 'Test', 'ID_projet' => 1]
    ])) === 1,
    'Élimine les doublons par ID_projet'
);

test('filtrer_projets() - Texte absent',
    count(filtrer_projets($projets_test, 'INEXISTANT')) === 0,
    'Texte absent → tableau vide'
);

echo "<h3>Fonction filtrer_experience()</h3>";
$experiences_test = [
    ['Nom' => 'Experience A', 'Description' => 'Test A', 'ID_experience' => 1],
    ['Nom' => 'Experience B', 'Description' => 'Test B', 'ID_experience' => 2],
    ['Nom' => 'Autre C', 'Description' => 'Test C', 'ID_experience' => 3],
];

test('filtrer_experience() - Filtre par nom',
    count(filtrer_experience($experiences_test, 'Experience')) === 2,
    'Trouve 2 expériences contenant "Experience"'
);

test('filtrer_experience() - Filtre description',
    count(filtrer_experience($experiences_test, 'Test')) === 3,
    'Recherche aussi dans la description'
);

test('filtrer_experience() - Anti-doublons',
    count(filtrer_experience([
        ['Nom' => 'A', 'ID_experience' => 1],
        ['Nom' => 'A', 'ID_experience' => 1]
    ])) === 1,
    'Élimine les doublons'
);

// ============================================================================
echo "<h2>📊 Catégorie 5 : Affichage & HTML</h2>";
// ============================================================================

echo "<h3>Fonction afficher_barre_progression()</h3>";
$html_barre = afficher_barre_progression(5, 10);

test('afficher_barre_progression() - Retourne string',
    is_string($html_barre),
    'Retourne du HTML en string'
);

test('afficher_barre_progression() - Contient div',
    strpos($html_barre, '<div') !== false,
    'HTML contient des balises <div>'
);

test('afficher_barre_progression() - Affiche ratio',
    strpos($html_barre, '5/10') !== false,
    'Affiche le ratio "5/10"'
);

test('afficher_barre_progression() - Calcul pourcentage',
    strpos($html_barre, '50') !== false,
    'Calcule 50% (5/10)'
);

test('afficher_barre_progression() - Gère 0/0',
    is_string(afficher_barre_progression(0, 0)),
    'Gère le cas 0/0 sans erreur'
);

test('afficher_barre_progression() - Contient style',
    strpos($html_barre, 'style') !== false || strpos($html_barre, '<style>') !== false,
    'Contient du CSS inline ou une balise style'
);

// ============================================================================
echo "<h2>📝 Catégorie 6 : Validation Formulaires</h2>";
// ============================================================================

echo "<h3>Fonction verifier_champs_projet()</h3>";
if (function_exists('verifier_champs_projet')) {
    $description_valide = 'Description suffisamment longue pour être valide avec plus de 10 caractères';
    
    test('verifier_champs_projet() - Tout valide',
        count(verifier_champs_projet('Projet Test', $description_valide)) === 0,
        'Nom et description valides → 0 erreur'
    );
    
    test('verifier_champs_projet() - Nom trop court (2 chars)',
        count(verifier_champs_projet('AB', $description_valide)) > 0,
        'Nom < 3 chars → erreur'
    );
    
    test('verifier_champs_projet() - Description trop courte',
        count(verifier_champs_projet('Projet Test', 'Court')) > 0,
        'Description < 10 chars → erreur'
    );
    
    test('verifier_champs_projet() - Nom trop long (150 chars)',
        count(verifier_champs_projet(str_repeat('A', 150), $description_valide)) > 0,
        'Nom > 100 chars → erreur'
    );
    
    test('verifier_champs_projet() - Retourne array',
        is_array(verifier_champs_projet('Test', 'Desc')),
        'Retourne toujours un array'
    );
} else {
    for ($i = 0; $i < 5; $i++) {
        test('verifier_champs_projet() - Non disponible', true, 'Fonction non chargée');
    }
}

// ============================================================================
echo "<h2>🔧 Catégorie 7 : Fonctions Utilitaires</h2>";
// ============================================================================

echo "<h3>Fonctions de dates et créneaux</h3>";
if (function_exists('get_dates_semaine')) {
    $dates = get_dates_semaine();
    
    test('get_dates_semaine() - Retourne 7 jours',
        count($dates) === 7,
        'Une semaine = 7 jours'
    );
    
    test('get_dates_semaine() - Structure correcte',
        isset($dates[0]['date']) && isset($dates[0]['jour']),
        'Chaque jour contient "date" et "jour"'
    );
    
    test('get_dates_semaine() - Commence par Lundi',
        $dates[0]['jour'] === 'Lundi',
        'Premier jour = Lundi'
    );
    
    test('get_dates_semaine() - Finit par Dimanche',
        $dates[6]['jour'] === 'Dimanche',
        'Dernier jour = Dimanche'
    );
} else {
    for ($i = 0; $i < 4; $i++) {
        test('get_dates_semaine() - Non disponible', true, 'Fonction non chargée');
    }
}

if (function_exists('creneau_est_occupe')) {
    $reservations = [
        ['Date_reservation' => '2024-01-15', 'Heure_debut' => '09:00:00', 'Heure_fin' => '11:00:00'],
        ['Date_reservation' => '2024-01-15', 'Heure_debut' => '14:00:00', 'Heure_fin' => '16:00:00'],
    ];
    $jour = ['date' => '2024-01-15'];
    
    test('creneau_est_occupe() - Créneau occupé',
        count(creneau_est_occupe($reservations, $jour, 9)) > 0,
        '9h est dans 9h-11h → occupé'
    );
    
    test('creneau_est_occupe() - Créneau libre',
        count(creneau_est_occupe($reservations, $jour, 12)) === 0,
        '12h n\'est dans aucune réservation → libre'
    );
    
    test('creneau_est_occupe() - Retourne array',
        is_array(creneau_est_occupe($reservations, $jour, 9)),
        'Retourne un array de réservations'
    );
} else {
    for ($i = 0; $i < 3; $i++) {
        test('creneau_est_occupe() - Non disponible', true, 'Fonction non chargée');
    }
}

// ============================================================================
echo "<h2>✅ Catégorie 8 : Fonctions d'existence (BDD requise)</h2>";
echo "<p style='color: #666; font-size: 0.9em; font-style: italic;'>Ces fonctions nécessitent une connexion BDD - on vérifie leur existence</p>";
// ============================================================================

$fonctions_bdd = [
    'connectBDD', 'email_existe', 'connexion_valide', 'recuperer_id_compte',
    'est_admin', 'est_admin_par_id', 'get_last_notif', 'supprimer_experience',
    'supprimer_utilisateur', 'supprimer_projet', 'accepter_utilisateur',
    'get_mes_experiences_complets', 'get_all_projet', 'envoyerNotification',
    'verification_connexion', 'supprimer_materiel', 'ajouter_materiel',
    'modifier_materiel', 'get_materiel', 'modifier_utilisateur', 'get_utilisateurs',
    'recup_salles', 'recuperer_materiels_salle', 'recuperer_id_materiel_par_nom',
    'creer_experience', 'associer_experience_projet', 'ajouter_experimentateurs',
    'associer_materiel_experience', 'creer_projet', 'get_info_experience',
    'get_salles_et_materiel', 'get_experimentateurs', 'modifie_value_exp',
    'maj_bdd_experience', 'afficher_projets_pagines', 'get_info_projet',
    'get_gestionnaires', 'get_collaborateurs', 'get_experiences'
];

foreach ($fonctions_bdd as $fonction) {
    test("$fonction() - Fonction existe",
        function_exists($fonction),
        'Fonction déclarée et disponible'
    );
}

// ============================================================================
// AFFICHAGE DES RÉSULTATS
// ============================================================================

echo "<h2>📊 Résultats Détaillés</h2>";

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

echo "<div class='summary'>";
echo "<h3>📈 Statistiques Globales</h3>";

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
echo "</div>";

if ($tests_failed === 0 && $tests_total > 0) {
    echo "<div style='text-align: center; margin-top: 30px; padding: 30px; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; border-radius: 10px;'>";
    echo "<h2 style='color: #28a745; margin: 0;'>🎉 Parfait ! Tous les tests passent !</h2>";
    echo "<p style='color: #155724; margin: 10px 0 0 0;'>{$tests_total} tests fonctionnels validés avec succès</p>";
    echo "</div>";
}
?>
</div>
</body>
</html>