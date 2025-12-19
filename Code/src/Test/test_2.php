<?php
/**
 * TESTS UNITAIRES SIMPLES
 * Lance ce fichier dans ton navigateur : http://localhost/tests_simples.php
 */

// Charger vos fonctions
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '1');
ob_start();

require_once __DIR__ . '/../back_php/fonctions_site_web.php';

$fichiers_a_charger = [
    'fonction_page_creation_projet.php',
    'fonction_page_inscription.php',
    'fonction_page_experience.php',
    'fonction_page_connexion.php',
    'fonction_page_profil.php',
    'fonction_page_admin_materiel_salle.php',
    'fonction_page_admin_utilisateurs.php',
    'fonction_page_creation_experience_2.php',
];

$fonction_page_dir = __DIR__ . '/../back_php/fonction_page/';
foreach ($fichiers_a_charger as $fichier) {
    $chemin = $fonction_page_dir . $fichier;
    if (file_exists($chemin)) {
        try {
            @include_once $chemin;
        } catch (Throwable $e) {}
    }
}

// Connexion à la BDD de test
try {
    $bdd = new PDO(
        "mysql:host=localhost;dbname=projet_site_web;charset=utf8",
        "root",
        ""
    );
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erreur de connexion à la BDD de test: " . $e->getMessage());
}

// Variables pour les statistiques
$tests_total = 0;
$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

// Fonction helper pour exécuter un test
function test($nom, $callback) {
    global $tests_total, $tests_passed, $tests_failed, $test_results, $bdd;
    $tests_total++;
    
    try {
        // Démarrer une transaction pour isoler le test
        $bdd->beginTransaction();
        
        $result = $callback($bdd);
        
        // Rollback pour nettoyer
        $bdd->rollBack();
        
        if ($result === true) {
            $tests_passed++;
            $test_results[] = [
                'nom' => $nom,
                'status' => 'pass',
                'message' => ''
            ];
        } else {
            $tests_failed++;
            $test_results[] = [
                'nom' => $nom,
                'status' => 'fail',
                'message' => is_string($result) ? $result : 'Le test a retourné false'
            ];
        }
    } catch (Exception $e) {
        $tests_failed++;
        $bdd->rollBack();
        $test_results[] = [
            'nom' => $nom,
            'status' => 'fail',
            'message' => $e->getMessage()
        ];
    }
}

// Fonction helper pour créer des données de test
function creer_utilisateur_test($bdd, $email = 'test@example.com') {
    $stmt = $bdd->prepare("
        INSERT INTO compte (Nom, Prenom, Email, Mdp, Date_de_naissance, Etat, validation)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'TestNom',
        'TestPrenom',
        $email,
        password_hash('password123', PASSWORD_DEFAULT),
        '1990-01-01',
        2,
        1
    ]);
    return $bdd->lastInsertId();
}

function creer_projet_test($bdd, $nom = 'Projet Test') {
    $stmt = $bdd->prepare("
        INSERT INTO projet (Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $nom,
        'Description test',
        0,
        1,
        date('Y-m-d'),
        date('Y-m-d')
    ]);
    return $bdd->lastInsertId();
}

// ==================== DÉBUT DES TESTS ====================

// Tests pour email_existe()
test("email_existe() retourne true pour un email existant", function($bdd) {
    $id = creer_utilisateur_test($bdd, 'existe@test.com');
    $resultat = email_existe($bdd, 'existe@test.com');
    return $resultat === true;
});

test("email_existe() retourne false pour un email inexistant", function($bdd) {
    $resultat = email_existe($bdd, 'nexistepas@test.com');
    return $resultat === false;
});

// Tests pour recuperer_id_compte()
test("recuperer_id_compte() retourne l'ID pour un email existant", function($bdd) {
    $id = creer_utilisateur_test($bdd, 'lol.recup@axoulab.fr');
    $resultat = recuperer_id_compte($bdd, 'lol.recup@axoulab.fr');
    return $resultat === $id;
});

test("recuperer_id_compte() retourne null pour un email inexistant", function($bdd) {
    $resultat = recuperer_id_compte($bdd, 'nexistepas@axoulab.fr');
    return $resultat === null;
});

// Tests pour est_admin()
test("est_admin() retourne true pour un admin (état = 3)", function($bdd) {
    $stmt = $bdd->prepare("
        INSERT INTO compte (Nom, Prenom, Email, Mdp, Date_de_naissance, Etat, validation)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['Admin', 'Test', 'admin@test.com', 'pass', '1990-01-01', 3, 1]);
    
    $resultat = est_admin($bdd, 'admin@test.com');
    return $resultat === true;
});

test("est_admin() retourne false pour un non-admin", function($bdd) {
    $id = creer_utilisateur_test($bdd, 'user@test.com');
    $resultat = est_admin($bdd, 'user@test.com');
    return $resultat === false;
});

test("est_admin() retourne false pour un email inexistant", function($bdd) {
    $resultat = est_admin($bdd, 'nexistepas@test.com');
    return $resultat === false;
});

// Tests pour est_admin_par_id()
test("est_admin_par_id() retourne true pour un admin", function($bdd) {
    $stmt = $bdd->prepare("
        INSERT INTO compte (Nom, Prenom, Email, Mdp, Date_de_naissance, Etat, validation)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['Admin', 'Test', 'admin2@test.com', 'pass', '1990-01-01', 3, 1]);
    $id = $bdd->lastInsertId();
    
    $resultat = est_admin_par_id($bdd, $id);
    return $resultat === true;
});

test("est_admin_par_id() retourne false pour un non-admin", function($bdd) {
    $id = creer_utilisateur_test($bdd, 'user2@test.com');
    $resultat = est_admin_par_id($bdd, $id);
    return $resultat === false;
});

test("est_admin_par_id() retourne false pour un ID inexistant", function($bdd) {
    $resultat = est_admin_par_id($bdd, 99999);
    return $resultat === false;
});

// Tests pour get_utilisateurs()
test("get_utilisateurs() retourne un tableau", function($bdd) {
    creer_utilisateur_test($bdd, 'liste1@test.com');
    creer_utilisateur_test($bdd, 'liste2@test.com');
    
    $resultat = get_utilisateurs($bdd);
    return is_array($resultat) && count($resultat) >= 2;
});

// Tests pour get_materiel()
test("get_materiel() retourne un tableau", function($bdd) {
    $stmt = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?), (?, ?)");
    $stmt->execute(['Salle A', 'Projecteur', 'Salle B', 'Ordinateur']);
    
    $resultat = get_materiel($bdd);
    return is_array($resultat) && count($resultat) >= 2;
});

// Tests pour recup_salles()
test("recup_salles() retourne la liste des salles", function($bdd) {
    $stmt = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?), (?, ?)");
    $stmt->execute(['Salle Alpha', 'Mat1', 'Salle Beta', 'Mat2']);
    
    $resultat = recup_salles($bdd);
    return is_array($resultat) && count($resultat) >= 2;
});

// Tests pour recuperer_materiels_salle()
test("recuperer_materiels_salle() retourne les matériels d'une salle", function($bdd) {
    $stmt = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?), (?, ?)");
    $stmt->execute(['Salle Gamma', 'Projecteur', 'Salle Gamma', 'Tableau']);
    
    $resultat = recuperer_materiels_salle($bdd, 'Salle Gamma');
    return is_array($resultat) && count($resultat) === 2;
});

// Tests pour recuperer_id_materiel_par_nom()
test("recuperer_id_materiel_par_nom() retourne l'ID du matériel", function($bdd) {
    $stmt = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?)");
    $stmt->execute(['Salle Delta', 'Microscope']);
    $id_attendu = $bdd->lastInsertId();
    
    $resultat = recuperer_id_materiel_par_nom($bdd, 'Microscope', 'Salle Delta');
    return $resultat === $id_attendu;
});

test("recuperer_id_materiel_par_nom() retourne null si non trouvé", function($bdd) {
    $resultat = recuperer_id_materiel_par_nom($bdd, 'Inexistant', 'Salle Delta');
    return $resultat === null;
});

// Tests pour creer_projet()
test("creer_projet() crée un projet et retourne son ID", function($bdd) {
    $id = creer_projet($bdd, 'Nouveau Projet', 'Description', 0, 1, 1);
    
    // Vérifier que le projet existe
    $stmt = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
    $stmt->execute([$id]);
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $projet && $projet['Nom_projet'] === 'Nouveau Projet';
});

// Tests pour creer_experience()
test("creer_experience() crée une expérience et retourne son ID", function($bdd) {
    $id = creer_experience($bdd, 1, 'Exp Test', 'Desc', '2024-12-20', date('Y-m-d'), '10:00', '12:00', 'Salle');
    
    // Vérifier que l'expérience existe
    $stmt = $bdd->prepare("SELECT Nom FROM experience WHERE ID_experience = ?");
    $stmt->execute([$id]);
    $exp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $exp && $exp['Nom'] === 'Exp Test';
});

test("creer_experience() ajuste l'heure de début minimum à 08:00", function($bdd) {
    $id = creer_experience($bdd, 1, 'Exp', 'Desc', '2024-12-20', date('Y-m-d'), '07:00', '10:00', 'Salle');
    
    $stmt = $bdd->prepare("SELECT Heure_debut FROM experience WHERE ID_experience = ?");
    $stmt->execute([$id]);
    $exp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $exp && $exp['Heure_debut'] === '08:00:00';
});

test("creer_experience() ajuste l'heure de fin maximum à 19:00", function($bdd) {
    $id = creer_experience($bdd, 1, 'Exp', 'Desc', '2024-12-20', date('Y-m-d'), '15:00', '20:00', 'Salle');
    
    $stmt = $bdd->prepare("SELECT Heure_fin FROM experience WHERE ID_experience = ?");
    $stmt->execute([$id]);
    $exp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $exp && $exp['Heure_fin'] === '19:00:00';
});

test("creer_experience() échoue si heure_debut >= heure_fin", function($bdd) {
    $resultat = creer_experience($bdd, 1, 'Exp', 'Desc', '2024-12-20', date('Y-m-d'), '15:00', '10:00', 'Salle');
    return $resultat === false;
});

// Tests pour associer_experience_projet()
test("associer_experience_projet() associe une expérience à un projet", function($bdd) {
    $id_projet = creer_projet_test($bdd);
    $id_exp = creer_experience($bdd, 1, 'Exp Assoc', 'Desc', '2024-12-20', date('Y-m-d'), '14:00', '16:00', 'Salle');
    
    $resultat = associer_experience_projet($bdd, $id_projet, $id_exp);
    
    // Vérifier l'association
    $stmt = $bdd->prepare("SELECT COUNT(*) as count FROM projet_experience WHERE ID_projet = ? AND ID_experience = ?");
    $stmt->execute([$id_projet, $id_exp]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultat === true && $row['count'] == 1;
});

// Tests pour get_info_experience()
test("get_info_experience() retourne les infos d'une expérience", function($bdd) {
    $id_exp = creer_experience($bdd, 1, 'Exp Info', 'Description', '2024-12-20', date('Y-m-d'), '10:00', '12:00', 'Salle');
    
    $resultat = get_info_experience($bdd, $id_exp);
    
    return is_array($resultat) && $resultat['Nom'] === 'Exp Info';
});

test("get_info_experience() retourne null si expérience inexistante", function($bdd) {
    $resultat = get_info_experience($bdd, 99999);
    return $resultat === null;
});

// Tests pour modifier_materiel()
test("modifier_materiel() modifie correctement un matériel", function($bdd) {
    $stmt = $bdd->prepare("INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES (?, ?)");
    $stmt->execute(['Ancienne Salle', 'Ancien Mat']);
    $id = $bdd->lastInsertId();
    
    $_POST['salle'] = 'Nouvelle Salle';
    $_POST['materiel'] = 'Nouveau Mat';
    
    $resultat = modifier_materiel($bdd, $id);
    
    // Vérifier la modification
    $stmt = $bdd->prepare("SELECT Nom_Salle, Materiel FROM salle_materiel WHERE ID_materiel = ?");
    $stmt->execute([$id]);
    $mat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    unset($_POST['salle'], $_POST['materiel']);
    
    return $resultat === true && $mat['Nom_Salle'] === 'Nouvelle Salle' && $mat['Materiel'] === 'Nouveau Mat';
});

test("modifier_materiel() retourne une erreur si données manquantes", function($bdd) {
    $resultat = modifier_materiel($bdd, 1);
    return $resultat === 'Données manquantes';
});

// Tests pour modifier_utilisateur()
test("modifier_utilisateur() modifie correctement un utilisateur", function($bdd) {
    $id = creer_utilisateur_test($bdd, 'avant@test.com');
    
    $_POST["nom_$id"] = 'NouveauNom';
    $_POST["prenom_$id"] = 'NouveauPrenom';
    $_POST["date_$id"] = '1995-05-15';
    $_POST["etat_$id"] = '3';
    $_POST["email_$id"] = 'apres@test.com';
    
    $resultat = modifier_utilisateur($bdd, $id);
    
    // Vérifier la modification
    $stmt = $bdd->prepare("SELECT Nom, Prenom, Email FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    unset($_POST["nom_$id"], $_POST["prenom_$id"], $_POST["date_$id"], $_POST["etat_$id"], $_POST["email_$id"]);
    
    return $resultat === true && $user['Nom'] === 'NouveauNom' && $user['Email'] === 'apres@test.com';
});

test("modifier_utilisateur() retourne une erreur si données manquantes", function($bdd) {
    $resultat = modifier_utilisateur($bdd, 1);
    return $resultat === 'Données manquantes';
});

// Tests pour get_all_projet()
test("get_all_projet() retourne la liste des projets avec statuts", function($bdd) {
    $id_user = creer_utilisateur_test($bdd, 'projets@test.com');
    $id_projet = creer_projet_test($bdd, 'Mon Projet');
    
    // Associer l'utilisateur au projet comme gestionnaire
    $stmt = $bdd->prepare("INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES (?, ?, ?)");
    $stmt->execute([$id_projet, $id_user, 1]);
    
    $resultat = get_all_projet($bdd, $id_user);
    
    $trouve = false;
    foreach ($resultat as $p) {
        if ($p['ID_projet'] == $id_projet && $p['Statut'] === 'Gestionnaire') {
            $trouve = true;
            break;
        }
    }
    
    return is_array($resultat) && $trouve;
});

// ==================== FIN DES TESTS ====================

$errors = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats des Tests</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
            text-align: center;
        }
        h2 {
            color: #666;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .test-result:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .test-result.pass {
            background: #d4edda;
            border-color: #28a745;
        }
        .test-result.fail {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .test-name {
            font-weight: 600;
            font-size: 1.1em;
            display: block;
            margin-bottom: 5px;
        }
        .test-error {
            color: #721c24;
            background: #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .summary {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .summary-item {
            font-size: 1.2em;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .passed { color: #28a745; font-weight: bold; }
        .failed { color: #dc3545; font-weight: bold; }
        .progress-bar {
            width: 100%;
            height: 40px;
            background: #e0e0e0;
            border-radius: 20px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1em;
            transition: width 1s ease;
        }
        .header-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .db-info {
            color: #666;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-info">
            <h1>Tests Unitaires</h1>
            <p class="db-info">Base de données: <strong>projet_site_web</strong></p>
            <p class="db-info">Date: <strong><?= date('d/m/Y H:i:s') ?></strong></p>
        </div>

        <h2>Résultats des Tests</h2>
        <?php
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
        ?>

        <div class="summary">
            <h3>Statistiques Globales</h3>

            <?php
            $percentage = $tests_total > 0 ? round(($tests_passed / $tests_total) * 100, 1) : 0;
            ?>

            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $percentage ?>%"><?= $percentage ?>%</div>
            </div>

            <div class="summary-item">
                <span class="passed">✓ Tests réussis: <?= $tests_passed ?> / <?= $tests_total ?></span>
            </div>
            
            <?php if ($tests_failed > 0): ?>
                <div class="summary-item">
                    <span class="failed">✗ Tests échoués: <?= $tests_failed ?> / <?= $tests_total ?></span>
                </div>
            <?php endif; ?>

            <div class="summary-item" style="margin-top: 15px; font-size: 1.3em;">
                Taux de réussite: <strong style="color: <?= $percentage == 100 ? '#28a745' : '#007bff' ?>"><?= $percentage ?>%</strong>
            </div>
        </div>

        <?php if ($tests_failed === 0 && $tests_total > 0): ?>
            <div style="text-align: center; margin-top: 30px; padding: 30px; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; border-radius: 10px;">
                <h2 style="color: #28a745; margin: 0;">Parfait ! Tous les tests passent !</h2>
                <p style="color: #155724; margin: 10px 0 0 0;"><?= $tests_total ?> tests fonctionnels validés avec succès</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>