<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Runner - Fonctions du Projet</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        pre { background:#f9f9f9; padding:8px; border:1px solid #eee; max-height: 300px; overflow: auto; }
        .ok { color: #155724; }
        .err { color: #721c24; }
        .warn { color: #856404; background: #fff3cd; padding: 8px; border: 1px solid #ffc107; margin-bottom: 16px; }
        .file-list { margin-bottom:16px; }
        .small { font-size:0.9em; color:#666 }
        .modifies-db { background: #ffe6e6; }
        button { padding: 5px 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
<h1>Test Runner — Fonctions du Projet</h1>
<p class="small">Ce runner exécute les fonctions définies dans les fichiers <code>fonction*.php</code> du projet. Saisissez les arguments en JSON pour tester chaque fonction.</p>

<div class="warn">
    <strong>⚠️ Attention :</strong> Les fonctions marquées en rouge modifient la base de données. Testez avec prudence sur une copie de sauvegarde.
</div>

<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Whitelist des fonctions du projet (sûres et intéressantes à tester)
$projectFunctions = [
    // Fonctions d'authentification et connexion
    'connectBDD',
    'email_existe',
    'connexion_valide',
    'recuperer_id_compte',
    'verifier_mdp',
    'est_admin',
    'est_admin_par_id',
    'verification_connexion',
    
    // Fonctions d'affichage
    'afficher_Bandeau_Haut',
    'afficher_Bandeau_Bas',
    'afficher_experiences_pagines',
    'afficher_pagination',
    'layout_erreur',
    'get_etat',
    
    // Fonctions de récupération de données
    'get_last_notif',
    'get_mes_experiences_complets',
    'get_all_projet',
    'create_page',
    
    // Fonctions de gestion (à utiliser avec prudence)
    'envoyerNotification',
    'supprimer_experience',
    'supprimer_utilisateur'
];

// Fonctions qui modifient la base de données
$dbModifyingFunctions = [
    'envoyerNotification',
    'supprimer_experience',
    'supprimer_utilisateur'
];

// trouver et inclure les fichiers fonction*.php
$base = realpath(__DIR__ . '/../src/back_php');
$files = [];
if ($base && is_dir($base)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $name = $f->getFilename();
            if (preg_match('/^fonction.*\.php$/i', $name)) {
                $files[] = $f->getRealPath();
            }
        }
    }
}

echo "<div class=\"file-list\"><strong>Fichiers inclus (" . count($files) . ") :</strong><ul>";
foreach ($files as $fp) echo "<li>" . htmlspecialchars(str_replace(__DIR__ . '/..', '', $fp)) . "</li>";
echo "</ul></div>";

// snapshot des fonctions avant inclusion
$before = get_defined_functions();
$beforeUser = $before['user'];

// inclure les fichiers
foreach ($files as $f) {
    try { require_once $f; } catch (Throwable $e) { echo "<div class=err>Erreur include: " . htmlspecialchars($f) . " — " . htmlspecialchars($e->getMessage()) . "</div>"; }
}

// récupérer les nouvelles fonctions du projet
$after = get_defined_functions();
$afterUser = $after['user'];
$newFuncs = array_diff($afterUser, $beforeUser);
$projectFuncs = array_intersect($newFuncs, $projectFunctions);

if (empty($projectFuncs)) {
    echo "<p>Aucune fonction du projet trouvée.</p>";
} else {
    echo "<h2>Fonctions disponibles (" . count($projectFuncs) . ")</h2>";
    echo "<table><thead><tr><th>Fonction</th><th>Fichier</th><th>Paramètres</th><th>Action</th></tr></thead><tbody>";
    foreach ($projectFuncs as $fn) {
        try {
            $ref = new ReflectionFunction($fn);
            $params = [];
            foreach ($ref->getParameters() as $p) {
                $params[] = ($p->isOptional() ? '[opt] ' : '[req] ') . '$' . $p->getName();
            }
            $declFile = $ref->getFileName();
        } catch (ReflectionException $e) {
            $params = ['?'];
            $declFile = '';
        }
        
        $isModifying = in_array($fn, $dbModifyingFunctions);
        $rowClass = $isModifying ? 'modifies-db' : '';
        $paramStr = htmlspecialchars(implode(', ', $params));
        $fileShort = htmlspecialchars(str_replace(__DIR__ . '/..', '', $declFile));
        
        echo "<tr class=\"$rowClass\">";
        echo "<td><code>" . htmlspecialchars($fn) . "</code>" . ($isModifying ? " <span style='color:red;'>⚠️</span>" : "") . "</td>";
        echo "<td>" . $fileShort . "</td>";
        echo "<td><small>" . $paramStr . "</small></td>";
        echo "<td>";
        echo "<form method=post style='display:inline-block'>";
        echo "<input type=hidden name=fn value='" . htmlspecialchars($fn, ENT_QUOTES) . "'>";
        echo "<input type=text name=args placeholder='JSON args' size='30'> ";
        echo "<button type=submit>Exécuter</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}

// Gestion de l'exécution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['fn'])) {
    $fn = $_POST['fn'];
    
    // Vérifier que la fonction existe dans la whitelist
    if (!in_array($fn, $projectFunctions)) {
        echo "<div class=err>Fonction non autorisée : " . htmlspecialchars($fn) . "</div>";
    } else {
        $argsRaw = $_POST['args'] ?? '';
        $args = [];
        
        if (trim($argsRaw) !== '') {
            $decoded = json_decode($argsRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "<div class=err>JSON d'arguments invalide : " . htmlspecialchars(json_last_error_msg()) . "</div>";
                $decoded = null;
            }
            if (is_array($decoded)) $args = $decoded;
        }

        if (in_array($fn, $dbModifyingFunctions)) {
            echo "<div class=warn><strong>⚠️ Attention :</strong> Cette fonction modifie la base de données.</div>";
        }

        echo "<h3>Appel : <code>" . htmlspecialchars($fn) . "()</code></h3>";
        if (!empty($args)) {
            echo "<p class=small><strong>Arguments :</strong> " . htmlspecialchars(json_encode($args)) . "</p>";
        }

        if (function_exists($fn)) {
            try {
                ob_start();
                $res = call_user_func_array($fn, $args);
                $out = ob_get_clean();
                
                if ($out) {
                    echo "<h4>Output (stdout) :</h4><pre>" . htmlspecialchars($out) . "</pre>";
                }
                echo "<h4>Retour :</h4><pre>" . htmlspecialchars(var_export($res, true)) . "</pre>";
            } catch (Throwable $e) {
                echo "<div class=err><strong>Erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div class=err>Fonction non trouvée.</div>";
        }
    }
}

?>
</body>
</html>
            "Notification générée avec succès",
            $NotifGenerateT !== false ? "Notification générée avec succès avec Id_notif = $NotifGenerateT": "Notification non générée",
            $NotifGenerateT !==false     
        );
        if ($NotifGenerateT !== false) {
            $notifCreated[] = $NotifGenerateT;
        }
        $NotifGenerateF = Generer_notif(19,3,16);
        addTestResult(
            "Generer_notif (existe déjà)",
            "Notification générée avec succès",
            $NotifGenerateF !== false ? "Notification générée avec succès avec Id_notif = $NotifGenerateF": "Notification non générée",
            $NotifGenerateF ==false
        );
        if ($NotifGenerateF !== false) {
            $notifCreated[] = $NotifGenerateF;
        }

        //Nombre de notifications 
        $nbrNotifs = Pastille_nombre(22);
        addTestResult(
            "Pastille_nombre ",
            "Nombre de notifications (int) ",
            is_integer($nbrNotifs) ? $nbrNotifs : "Erreur de récupération",
            is_integer($nbrNotifs)
        );

        //Listes des notifications
        $list = List_Notif(23, 'Medecin');
        if (is_array($list)) {
            $commList = "";
            $commList .= "Id_Notif: " ;
            // Itérer sur chaque notification et les afficher
            foreach ($list as $key => $value) {
                $commList .= $value['Id_notif'] . " - ";
            }}
        addTestResult(
            "List_Notif",
            "Liste des notifications",
            is_array($list) ? "Liste des notifications" : "Erreur de récupération",
            is_array($list),
            is_array($list)?  $commList : 'Erreur de récupération'
        );

        //Lire une notification
        Lire_notif(99, 11);
        addTestResult(
            'Lire_notif (users)',
            'Notification Ouverte',
            Obtenir_statut_notification(99, 11) == 'Ouvert'? 'Notification Ouverte' : 'Notification Non Ouverte',
            Obtenir_statut_notification(99, 11) == 'Ouvert'
        );
        Ne_plus_lire_notif(99,11);
        addTestResult(
            'Ne_plus_lire_notif (users)',
            'Notification Non Ouverte',
            Obtenir_statut_notification(99, 11) == 'Ouvert'? 'Notification Ouverte' : 'Notification Non Ouverte',
            Obtenir_statut_notification(99, 11) !== 'Ouvert'
        );

        //medecin
        session_start();
        $_SESSION['role'] = 'Medecin';
        Lire_notif(112, 14);
        addTestResult(
            'Lire_notif (medecin)',
            'Notification Ouverte',
            Obtenir_statut_notification(112, 14) == 'Ouvert'? 'Notification Ouverte' : 'Notification Non Ouverte',
            Obtenir_statut_notification(112, 14) == 'Ouvert'
        );
        Ne_plus_lire_notif(112,14);
        addTestResult(
            'Ne_plus_lire_notif (medecin)',
            'Notification Non Ouverte',
            Obtenir_statut_notification(112, 14) == 'Ouvert'? 'Notification Ouverte' : 'Notification Non Ouverte',
            Obtenir_statut_notification(112, 14) !== 'Ouvert'
        );
        session_destroy();

        // // Supprimer les notiications ajoutées pour les tests
        // foreach ($notifCreated as $Id) {
        //     $sql = $pdo->prepare("DELETE FROM `NOTIFICATION` WHERE `Id_notif` = :id_N;");
        //     $sql->execute(['id_N' => $Id]);
        // }
        // ?>
        </tbody>
    </table>
    <i>Les notifications générées avec les Id_notif = <?php foreach ($notifCreated as $Id) {echo $Id, " , ";}?> ont été supprimés de la BdD.</i>
    
    <body>
    <h2>Tests des Fonctions Générales</h2>
    <table>
        <thead>
            <tr>
                <th>Fonction</th>
                <th>Résultat Attendu</th>
                <th>Résultat Obtenu</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            <?php
            include_once 'Fonctions.php';

            //test de la fonction Get_id()
            $table = 'PATIENTS';
            $column = 'Id_patient';
            $list_id = Get_id($table, $column);
            addTestResult(
                'Get_id()',
                'liste des ID des patients',
                !empty($list_id)? 'Liste des ID des patients': 'Erreur de Récupération',
                !empty($list_id) == true 
            );

            $table = 'PATIENTS';
            $column = 'Id_patients';
            $list_id = Get_id($table, $column);
            addTestResult(
                'Get_id(avec une colonne incorrecte)',
                'Liste vide',
                empty($list_id)? 'Liste vide': 'Erreur de Récupération',
                empty($list_id) == true,
                $com = "Erreur: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'Id_patients' in 'field list'" 
            );

            $table = 'PATIENT';
            $column = 'Id_patient';
            $list_id = Get_id($table, $column);
            addTestResult(
                'Get_id(avec une table incorrecte)',
                'Liste vide',
                empty($list_id)? 'Liste vide': 'Erreur de Récupération',
                empty($list_id) == true,
                $com = "Erreur: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'website_db.patient' doesn't exist" 
            );

            //test de la fonction Get_entreprise_data
            $id_entreprise = 5;
            $data = Get_entreprise_data($id_entreprise);
            $entreprise = $data['entreprise'];
            $clinical_trials = $data['clinical_trials'];
            $medecins = $data['medecins'];
            addTestResult(
                'Get_entreprise_data()',
                'Array contenant l`ensemble des données de l`entreprise',
                !empty($entreprise) && is_array($entreprise)? 'Array contenant l`ensemble des données de l`entreprise': 'erreur',
                !empty($entreprise) && is_array($entreprise) == true
            );

            $id_entreprise = 4;
            $data = Get_entreprise_data($id_entreprise);
            $entreprise = $data['entreprise'];
            $clinical_trials = $data['clinical_trials'];
            $medecins = $data['medecins'];
            addTestResult(
                'Get_entreprise_data(quand l`identifiant est incorrect)',
                'Arrays vides',
                empty($entreprise) && is_array($entreprise)? 'Arrays vides': 'erreur',
                empty($entreprise) && is_array($entreprise) == true
            );

            //test de la fonction Get_essais
            $role = 'patient';
            $essais = Get_essais($role);
            addTestResult(
                'Get_essais($role = "patient")',
                'array contenants les informations des essais à afficher',
                !empty($data) && is_array($data)? 'array contenants les informations des essais à afficher': 'erreur',
                !empty($data) && is_array($data) == true
            );

            $role = 'medecin';
            $essais = Get_essais($role);
            addTestResult(
                'Get_essais($role = "medecin")',
                'array contenants les informations des essais à afficher',
                !empty($data) && is_array($data)? 'array contenants les informations des essais à afficher': 'erreur',
                !empty($data) && is_array($data) == true
            );

            $role = 'entreprise';
            $essais = Get_essais($role);
            addTestResult(
                'Get_essais($role = "entreprise")',
                'array contenants les informations des essais à afficher',
                !empty($data) && is_array($data)? 'array contenants les informations des essais à afficher': 'erreur',
                !empty($data) && is_array($data) == true
            );

            $role = 'visiteur';
            $essais = Get_essais($role);
            addTestResult(
                'Get_essais($role = "visiteur")',
                'array contenants les informations des essais à afficher',
                !empty($data) && is_array($data)? 'array contenants les informations des essais à afficher': 'erreur',
                !empty($data) && is_array($data) == true
            );

            $role = 'admin';
            $essais = Get_essais($role);
            addTestResult(
                'Get_essais($role = "admin")',
                'array contenants les informations des essais à afficher',
                !empty($data) && is_array($data)? 'array contenants les informations des essais à afficher': 'erreur',
                !empty($data) && is_array($data) == true
            );

            $role = 'fake';
            $essais = Get_essais($role);
            addTestResult(
                'Get_essais($role = "fake")',
                'array vide',
                empty($essais) && is_array($essais)? 'array vide': 'erreur',
                empty($essais) && is_array($essais) == true
            );

            //test de la fonction List_medecin
            $id_medecin = 16;
            $data = List_Medecin($id_medecin);
            addTestResult(
                'List_Medecin(quand l`identifiant est correct)',
                'Array contenant toutes les informations d`un medecin',
                !empty($data) && is_array($data)? 'Array contenant toutes les informations d`un medecin': 'erreur',
                !empty($data) && is_array($data) == true
            );

            $id_medecin = 4;
            $data = List_Medecin($id_medecin);
            addTestResult(
                'List_Medecin(quand l`identifiant est incorrect)',
                'Array vide',
                empty($data) && is_array($data)? 'Array vide': 'erreur',
                empty($data) && is_array($data) == true
            );

            $id_medecin = 'string';
            $data = List_Medecin($id_medecin);
            addTestResult(
                'List_Medecin(quand l`identifiant est une chaine de caractère)',
                'Array vide',
                empty($data) && is_array($data)? 'Array vide': 'erreur',
                empty($data) && is_array($data) == true
            );

            //test de la fonction recherche_EC
            $list_ec = Get_essais('patient');
            $recherche = '';
            $filtres = ['Tous', 'Tous'];
            $data = recherche_EC($list_ec, $recherche, $filtres);
            addTestResult(
                'recherche_EC(quand la recherche est vide et sans filtres)',
                'array identique à la liste complète',
                $data === $list_ec? 'array identique à la liste complète': 'erreur',
                ($data === $list_ec) == true
            );

            $list_ec = Get_essais('patient');
            $recherche = '';
            $filtres = ['Tous', 'Tous'];
            $data = recherche_EC($list_ec, $recherche, $filtres);
            addTestResult(
                'recherche_EC(quand la recherche a une correspondance)',
                'array comprenant la liste des essais à afficher',
                !empty($data) && is_array($data)? 'array comprenant la liste des essais à afficher': 'erreur',
                !empty($data) && is_array($data) == true
            );

            $list_ec = Get_essais('patient');
            $recherche = 'sdvsdgszdg';
            $filtres = ['Tous', 'Tous'];
            $data = recherche_EC($list_ec, $recherche, $filtres);
            addTestResult(
                'recherche_EC(quand la recherche n`a pas de correspondance)',
                'array vide',
                empty($data) && is_array($data)? 'array vide': 'erreur',
                empty($data) && is_array($data) == true,
                $com = "Aucun essai ne correspond à votre recherche."
            );

            $list_ec = Get_essais('patient');
            $recherche = 'DELETE TABLE PATIENTS';
            $filtres = ['Tous', 'Tous'];
            $data = recherche_EC($list_ec, $recherche, $filtres);
            addTestResult(
                'recherche_EC(quand la recherche tente une injection sql)',
                'array vide',
                empty($data) && is_array($data)? 'array vide': 'erreur',
                empty($data) && is_array($data) == true,
                $com = "Aucun essai ne correspond à votre recherche."
            );

            $list_ec = Get_essais('patient');
            $recherche = '//commentaire';
            $filtres = ['Tous', 'Tous'];
            $data = recherche_EC($list_ec, $recherche, $filtres);
            addTestResult(
                'recherche_EC(quand la recherche est un commentaire php)',
                'le contenu n`est pas pris en compte et les essais ne sont pas filtrés',
                $data === $list_ec? 'array identique à la liste complète': 'erreur',
                ($data === $list_ec) == true
            );

            ?>
</body>

<body>

    <h2>Tests des Fonctions d'ADMIN</h2>
    <i>En raison de l'architecture des pages .php, qui incluent à la fois le code manipulant les données et l'affichage dans un même fichier, les tests unitaires des fonctionnalités de l'Admin et des sections 'Mes Infos' ont été réalisés manuellement. Par ailleurs, le tableau correspondant a également été complété à la main.</i>
    <table>
        <thead>
            <tr>
                <th>Fonction</th>
                <th>Résultat Attendu</th>
                <th>Résultat Obtenu</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            <?php
            //Test d'accès à Home_Admin.php
            $manual_verification=' ';
            /*Lorsqu'on n'est pas connecter*/
            addTestResult(
                "Home_Admin.php (Visiteur, Patient, Medecin, Entreprise) ",
                "Accès Refusé, redirection vers la page de connexion",
                $manual_verification = "Accès Refusé, redirection vers la page de connexion",
                ($manual_verification == $manual_verification)

            );
            //Lorsqu'on est connecter à un compte
            addTestResult(
                "Home_Admin.php (Admin) ",
                "Accès autorisé",
                $manual_verification = "Accès autorisé",
                ($manual_verification == $manual_verification)
            );

            //Test d'accès à Liste_{Role}.php
            addTestResult(
                "Liste_patients.php (Visiteur, Patient, Medecin, Entreprise) ",
                "Accès Refusé, redirection vers la page de connexion",
                $manual_verification = "Accès Refusé, redirection vers la page de connexion",
                ($manual_verification == $manual_verification),
                "Idem pour Liste_medecins.php et Liste_entreprises.php"
            );
            addTestResult(
                "Liste_patients.php (Admin) ",
                "Accès autorisé",
                $manual_verification = "Accès autorisé",
                ($manual_verification == $manual_verification),
                "Idem pour Liste_medecins.php et Liste_entreprises.php"
            );
            
            //Test d'accès à Modifier_{Role}.php
            addTestResult(
                "Modifier_patients.php (Visiteur, Patient, Medecin, Entreprise) avec \$id_patient valide",
                "Accès Refusé, redirection vers la page de connexion",
                $manual_verification = "Accès Refusé, redirection vers la page de connexion",
                ($manual_verification == $manual_verification),
                "Idem pour Modifier_medecins.php et Modifier_entreprises.php"
            );
            addTestResult(
                "Modifier_patients.php (Visiteur, Patient, Medecin, Entreprise) avec \$id_patient invalide",
                "Accès Refusé, redirection vers la page de connexion",
                $manual_verification = "Accès Refusé, redirection vers la page de connexion",
                ($manual_verification == $manual_verification),
                "Idem pour Modifier_medecins.php et Modifier_entreprises.php"
            );
            addTestResult(
                "Modifier_patients.php (Admin) avec \$id_user valide ",
                "Renvoie sur la page de modification des informations grâce à l'id ",
                $manual_verification = "Renvoie sur la page de modification des informations grâce à l'id",
                ($manual_verification == $manual_verification),
                "Idem pour Modifier_medecins.php et Modifier_entreprises.php"
            );
            addTestResult(
                "Modifier_patients.php (Admin) avec \$id_patient invalide ",
                "Affiche patient introuvable",
                $manual_verification = "Affiche patient introuvable",
                ($manual_verification == $manual_verification),
                "Par exemple, si on prend id_patient=13 alors que id_user= 13 correspond à une entreprise. \n
                 Idem pour Liste_medecins.php et Liste_entreprises.php"
            );
            addTestResult(
                "Modifier_patients.php - Appuyer sur 'Enregistrer les modifications'",
                "Dirige vers la page Confirmer_modif.php",
                $manual_verification = "Dirige vers la page Confirmer_modif.php",
                ($manual_verification == $manual_verification),
            );
            addTestResult(
                "Modifier_patients.php - Appuyer sur 'Retour à la liste des {Role}'",
                "Retourne sur la page précédente contenant la liste des utilisateurs (Patient, Medecin ou Entreprise)",
                $manual_verification = "Retourne sur la page précédente",
                ($manual_verification == $manual_verification),
            );

            //Test Confirmer_modif.php
            addTestResult(
                "Confirmer_modif.php - Appuyer sur 'Valider",
                "Mise à jour de la Base de Donnée",
                $manual_verification = "Mise à jour de la Base de Donnée",
                ($manual_verification == $manual_verification),
            );
            addTestResult(
                "Confirmer_modif.php - Appuyer sur 'Annuler",
                "Retour sur la page Liste_{Role}.php selon le rôle de l'utilisateur dont on fait les modifications",
                $manual_verification = "Retour à la page Liste_{Role}.php",
                ($manual_verification == $manual_verification),
            );

            //Validation_en_attente.php
            addTestResult(
                "Validation_en_attente.php (Visiteur, Patient, Medecin, Entreprise)",
                "Accès Refusé, redirection vers la page de connexion",
                $manual_verification = "Accès Refusé, redirection vers la page de connexion",
                ($manual_verification == $manual_verification),
            );
            addTestResult(
                "Validation_en_attente.php (Admin)",
                "Accès autorisé",
                $manual_verification = "Accès autorisé à la page de Validation d'inscription",
                ($manual_verification == $manual_verification),
            );
            addTestResult(
                "Validation_en_attente.php - Appuyer sur 'Valider'",
                "Valider la demande d'inscription d'un utilisateur (medecin ou entreprise)",
                $manual_verification = "Valider la demande d'inscription d'un utilisateur",
                ($manual_verification == $manual_verification),
                "Verif_inscription=0 devient Verif_inscription=1"
            );
            addTestResult(
                "Validation_en_attente.php - Appuyer sur 'Supprimer'",
                "Supression de la demande d'inscription d'un utilisateur (medecin ou entreprise)",
                $manual_verification = "Suppression de l'utilisateur dans la BdD",
                ($manual_verification == $manual_verification),
            );

            //Supprimer_utilisateur.php
            addTestResult(
                "Supprimer_utilisateur.php",
                "Supression de la demande d'inscription d'un utilisateur (medecin ou entreprise)",
                $manual_verification = "Suppression de l'utilisateur dans la BdD",
                ($manual_verification == $manual_verification),
            );

?>

</tbody>
</body>
</table>

<body>
    <h2>Tests des Fonctions Mes Infos</h2>
    <table>
        <thead>
            <tr>
                <th>Fonction</th>
                <th>Résultat Attendu</th>
                <th>Résultat Obtenu</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            <?php
            //Test Menu_Mes_Infos.php()
            addTestResult(
                'Menu_Mes_Infos.php (Patient ou Medecin)',
                "Affichage seulement des boutons 'Mes Infos' et 'Mes Essais'",
                $manual_verification = "Affichage de deux boutons",
                ($manual_verification == $manual_verification) 
            );
            addTestResult(
                'Menu_Mes_Infos.php (Entreprise)',
                "Affichage trois boutons: 'Mes Infos', 'Mes Essais', 'Créer un essai'",
                $manual_verification = "Affichage trois boutons",
                ($manual_verification == $manual_verification) 
            );
            addTestResult(
                "Mes_Infos.php - Appuyer sur 'Modifier' avec les informations sous le bon format",
                "Modification dans la BdD",
                $manual_verification = "Modification dans la BdD",
                ($manual_verification == $manual_verification) 
            );
            addTestResult(
                "Mes_Infos.php - Appuyer sur 'Modifier' avec les informations sous le mauvais format",
                "Affichage d'un message d'erreur",
                $manual_verification = "Affichage d'un message d'erreur en précisant le champ invalide",
                ($manual_verification == $manual_verification),
                "Par exemple, Telephone nécessite 10 chiffres. Il y a donc un message d'erreur si celui contient des chaines de caractère ou celle ci ne contient pas exactement 10 chiffres"
            );
            addTestResult(
                "Page_Creer_Essai.php (Entreprise)",
                "Accès autorisé sur la page",
                $manual_verification = "Accès autorisé sur la page",
                ($manual_verification == $manual_verification),
            );
            addTestResult(
                "Page_Creer_Essai.php (Patient ou Medecin)",
                "Refus d'accès, redirection vers Menu_Mes_Infos.php",
                $manual_verification = "Refus d'accès, redirection vers Menu_Mes_Infos.php",
                ($manual_verification == $manual_verification),
            );
            addTestResult(
                "Page_Creer_Essai.php",
                "Création de l'essai (Ajout dans la BdD)",
                $manual_verification = "Création de l'essai",
                ($manual_verification == $manual_verification),
            );
            ?>
        </tbody>
    </table>
</body>  
<body>
<h2>Tests des Fonctions ESSSAIS INDIVIDUELS</h2>
<table>
<thead>
    <tr>
        <th>Fonction</th>
        <th>Résultat Attendu</th>
        <th>Résultat Obtenu</th>
        <th>Commentaire</th>
    </tr>
</thead>
<tbody>
    <?php
    include_once 'Fonctions_essai.php';

    //création d'utilisateurs propre aux tests de roles différents pour éviter les conflits avec notre base de donnée
    //Valider les users créés pour les utiliser par la suite 

    foreach ($userCreated as $user){
        $requete= $pdo -> prepare("SELECT Role FROM USERS WHERE Id_user = ?");
        $requete -> execute(array($user));
        $tableau = $requete->fetch();
        $role_cree = $tableau['Role'];
    // Mettre à jour la base de données en fonction du rôle
         if ($role_cree == 'Medecin') {
          $query = "UPDATE MEDECINS SET Statut_inscription = 1 WHERE Id_medecin = :userId";
         } elseif ($role_cree == 'Entreprise') {
            $query = "UPDATE ENTREPRISES SET Verif_inscription = 1 WHERE Id_entreprise = :userId";
         }else {break;}
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();}


    $Id_entreprise = Get_Entreprise(5);
    addTestResult( "Get_Entreprise(existe) ", "L'identifiant de l'entreprise est 8", 
    is_integer($Id_entreprise) ? "L'identifiant de l'entreprise est $Id_entreprise" : "Erreur base de donnée, 
    l'essai n'existe peutêtre pas: ",   is_integer($Id_entreprise) == True  );

    $Id_entreprise = Get_Entreprise(13);
    addTestResult( "Get_Entreprise(n'existe pas) ", "L'identifiant de l'entreprise est 8", 
    is_integer($Id_entreprise) ? "L'identifiant de l'entreprise est $Id_entreprise" : "Erreur base de donnée, 
    l'essai n'existe peut être pas ",   is_integer($Id_entreprise) == False  );

    $Statut_patient = Get_Statut_Patient(10,26);
    addTestResult( "Get_Statut_Patient(existe et est dans l'essai) ", "Le statut du patient est 'Termine'", 
    ($Statut_patient=='Termine') ? "Le statut du patient est $Statut_patient" : "Erreur base de donnée, 
    la patient n'existe pas ou existe mais n'est pas cet essai",   ($Statut_patient=='Termine') == True );

    $Statut_patient = Get_Statut_Patient(10,27);
    addTestResult( "Get_Statut_Patient(existe mais n'est pas dans l'essai) ", "Le statut du patient est 'Termine'", 
    ($Statut_patient=='Termine') ? "Le statut du patient est $Statut_patient" : "Erreur base de donnée, 
    la patient n'existe pas ou existe mais n'est pas cet essai",   ($Statut_patient=='Termine') == False );

    $Statut_patient = Get_Statut_Patient(10,150);
    addTestResult( "Get_Statut_Patient(n'existe pas) ", "Le statut du patient est 'Termine'", 
    ($Statut_patient=='Termine') ? "Le statut du patient est $Statut_patient" : "Erreur base de donnée, 
    la patient n'existe pas ou existe mais n'est pas cet essai",   ($Statut_patient=='Termine') == False );

    $Statut_medecin = Get_Statut_Medecin(1, 18);
    addTestResult( "Get_Statut_Medecin(existe et est dans cet essai) ", "Le statut du medecin est 'Termine'", 
    ($Statut_medecin=='Termine') ? "Le statut du medecin est $Statut_medecin" : "Erreur base de donnée, 
    le medecin n'existe pas ou existe mais n'est pas cet essai",  ($Statut_medecin=='Termine') == True );

    $Statut_medecin = Get_Statut_Medecin(2, 18);
    addTestResult( "Get_Statut_Medecin(existe mais n'est pas dans cet essai) ", "Le statut du medecin est 'Termine'", 
    ($Statut_medecin=='Termine') ? "Le statut du medecin est $Statut_medecin" : "Erreur base de donnée, 
    le medecin n'existe pas ou existe mais n'est pas cet essai",  ($Statut_medecin=='Termine') == False );

    $Statut_medecin = Get_Statut_Medecin(1, 30);
    addTestResult( "Get_Statut_Medecin(n'existe pas) ", "Le statut du medecin est 'Termine'", 
    ($Statut_medecin=='Termine') ? "Le statut du medecin est $Statut_medecin" : "Erreur base de donnée, 
    le medecin n'existe pas ou existe mais n'est pas cet essai",  ($Statut_medecin=='Termine') == False );




    //$Statut_medecin_initial = Get_Statut_Medecin(7,20);
    Demander_Medecin_essai(7,$id_Medecin);
    $Statut_medecin = Get_Statut_Medecin(7,$id_Medecin);
    //Retirer_Ligne_Medecin(7,20, $Statut_medecin_initial);
    addTestResult( "Demander_Medecin_essai(le medecin n'est pas déjà dans l'essai) ","Le statut du médecin est 'Sollicite'", 
    ($Statut_medecin=='Sollicite') ? "Le statut du médecin est $Statut_medecin" : "Erreur base de donnée, le medecin ne peut être sollicite",   
    ($Statut_medecin=='Sollicite') == True );

    Demander_Medecin_essai(5,13);
    $Statut_medecin = Get_Statut_Medecin(5,13);
    addTestResult( "Demander_Medecin_essai(le medecin est déjà sollicite) ","Erreur base de donnée, le medecin ne peut être sollicite", 
    ($Statut_medecin=='Sollicite') ? "Le statut du médecin est $Statut_medecin" : "Erreur base de donnée, le medecin ne peut être sollicite",   
    ($Statut_medecin=='Sollicite') == False );


    Postuler_Medecin_Essai(8, $id_Medecin);
    $Statut_medecin = Get_Statut_Medecin(8,$id_Medecin);
    addTestResult( "Postuler_Medecin_Essai(le medecin n'est pas déjà dans l'essai) ","Le statut du médecin est 'En attente'", 
    ($Statut_medecin=='En attente') ? "Le statut du médecin est $Statut_medecin" : "Erreur base de donnée, vous êtes déjà dans cet essai",   
    ($Statut_medecin=='En attente') == True);

    Postuler_Medecin_Essai(5, 13);
    $Statut_medecin = Get_Statut_Medecin(5,13);
    addTestResult( "Postuler_Medecin_Essai(le medecin n'est pas déjà dans l'essai) ","Erreur base de donnée, vous êtes déjà dans cet essai", 
    ($Statut_medecin=='En attente') ? "Le statut du médecin est $Statut_medecin" : "Erreur base de donnée, vous êtes déjà dans cet essai",   
    ($Statut_medecin=='En attente') == False);

    Retirer_Medecin_Essai(7, $id_Medecin);
    $Statut_medecin = Get_Statut_Medecin(7,$id_Medecin);
    addTestResult( "Retirer_Medecin_Essai(le medecin est dans l'essai) ","Le statut du médecin est 'Retire'", 
    ($Statut_medecin=='Retire') ? "Le statut du médecin est $Statut_medecin" : "Erreur base de donnée, vous n'êtes pas dans cet essai",   
    ($Statut_medecin=='Retire') == True);

    Retirer_Medecin_Essai(7, 13);
    $Statut_medecin = Get_Statut_Medecin(7,13);
    addTestResult( "Retirer_Medecin_Essai(le medecin n'est pas dans l'essai) ","Erreur base de donnée, vous n'êtes pas dans cet essai", 
    ($Statut_medecin=='Retire') ? "Le statut du médecin est $Statut_medecin" : "Erreur base de donnée, vous n'êtes pas dans cet essai",   
    ($Statut_medecin=='Retire') == False);
    
    Suspendre_Essai(6);
    $Statut_essai = Get_Statut_Essai(6);
    addTestResult( "Suspendre_Essai(l'essai n'est pas suspendu) ","l'essai est $Statut_essai", 
    ($Statut_essai=='Suspendu') ? "Le statut de l'essai est $Statut_essai" : "l'essai est déjà suspendu",   
    ($Statut_essai=='Suspendu') == True);

    Suspendre_Essai(0);
    $Statut_essai = Get_Statut_Essai(0);
    addTestResult( "Suspendre_Essai(l'essai est pas suspendu) ","l'essai est déjà suspendu", 
    ($Statut_essai=='Suspendu') ? "Le statut de l'essai est $Statut_essai" : "l'essai est déjà suspendu",   
    ($Statut_essai=='Suspendu') == True);

    Relancer_Essai(6);
    $Statut_essai = Get_Statut_Essai(6);
    addTestResult( "Relancer_Essai(l'essai est suspendu) ","L'essai est  $Statut_essai", 
    ($Statut_essai=='En cours') ? "l'essai est $Statut_essai" : "L'essai est déjà suspendu",   
    ($Statut_essai=='En cours') == True);

    Relancer_Essai(6);
    $Statut_essai = Get_Statut_Essai(6);
    addTestResult( "Relancer_Essai(l'essai n'était pas suspendu) ","L'essai n'était pas suspendu", 
    ($Statut_essai=='En cours') ? "l'essai est $Statut_essai" : "L'essai n'était pas suspendu",   
    ($Statut_essai=='En cours') == True);

    Postuler_Patient_Essai(2, $id_patent);
    $Statut_patient = Get_Statut_Patient(2, $id_patent);
    addTestResult( "Postuler_Patient_Essai(n'est pas dans l'essai) ","Le statut du patient est 'en attente'", 
    ($Statut_patient=='En attente') ? "le statut du patient est $Statut_patient" : "Erreur bdd: le patient est déjà dans l'essai",   
    ($Statut_patient=='En attente') == True);

    Postuler_Patient_Essai(2,44);
    $Statut_patient = Get_Statut_Patient(2,44);
    addTestResult( "Postuler_Patient_Essai(est déjà dans l'essai) ","Le patient est déjà dans l'essai", 
    ($Statut_patient=='En attente') ? "le statut du patient est $Statut_patient" : "Erreur bdd: le patient est déjà dans l'essai",   
    ($Statut_patient=='En attente') == False); 

    Retirer_Patient_Essai(2,$id_patent);
    $Statut_patient = Get_Statut_Patient(2,$id_patent);
    addTestResult( "Retirer_Patient_Essai(est dans l'essai) ","Le statut du patient est 'abandon'", 
    ($Statut_patient=='Abandon') ? "le statut du patient est $Statut_patient" : "Erreur bdd: le patient n'est pas dans l'essai",   
    ($Statut_patient=='Abandon') == True); 

    Retirer_Patient_Essai(6, 31);
    $Statut_patient = Get_Statut_Patient(6, 31);
    addTestResult( "Retirer_Patient_Essai(n'est pas dans l'essai) ","Erreur bdd: le patient n'est pas dans l'essai", 
    ($Statut_patient=='Abandon') ? "le statut du patient est $Statut_patient" : "Erreur bdd: le patient n'est pas dans l'essai",   
    ($Statut_patient=='Abandon') == False);

    Verif_nbMedecin_Essai(6, 'ok');
    //cet essai a suffisamment de médecin
    $Statut_essai = Get_Statut_Essai(6);
    addTestResult( "Verif_nbMedecin_Essai(a assez de médecin) ","Le statut de l'essai est 'en cours' ou 'recrutement'", 
    ($Statut_essai=='En cours') ? "le statut de l'essai est $Statut_essai" : "Erreur du statut de l'essai",   
    ($Statut_essai=='En cours') == True); 

    Verif_nbMedecin_Essai(7, 'pas ok');
    //cet essai n'a pas suffisamment de médecin
    $Statut_essai = Get_Statut_Essai(7);
    addTestResult( "Verif_nbMedecin_Essai(n'a pas assez de médecin) ","Le statut de l'essai est 'en attente'", 
    ($Statut_essai=='En attente') ? "le statut de l'essai est $Statut_essai" : "Erreur du statut de l'essai",   
    ($Statut_essai=='En attente') == True); 

    Verif_nbPatient_Essai(6); //essai qui a suffisamment de patients
    $Statut_essai = Get_Statut_Essai(6);
    addTestResult( "Verif_nbPatients_Essai(a assez de patients) ","Le statut de l'essai est 'en cours'", 
    ($Statut_essai=='En cours') ? "le statut de l'essai est $Statut_essai" : "Erreur du statut de l'essai",   
    ($Statut_essai=='En cours') == True); 

    Verif_nbPatient_Essai(11); //essai qui n'a pas suffisamment de patients
    $Statut_essai = Get_Statut_Essai(11);
    addTestResult( "Verif_nbPatients_Essai(n'a pas assez de patients) ","Le statut de l'essai est en 'recrutement'", 
    ($Statut_essai=='Recrutement') ? "le statut de l'essai est $Statut_essai" : "Erreur du statut de l'essai",   
    ($Statut_essai=='Recrutement') == True); 

    //medecin en attente donc
    Traiter_Candidature_Medecin(8, $id_Medecin, 1);
    $Statut_medecin = Get_Statut_Medecin(8, $id_Medecin);
    addTestResult( "Traiter_Candidature_Medecin(accepter) ","Le statut du médecin est 'actif'", 
    ($Statut_medecin=='Actif') ? "le statut du médecin est $Statut_medecin" : "Erreur du statut du médecin",   
    ($Statut_medecin=='Actif') == True); 

    Postuler_Medecin_Essai(7, $id_Medecin);  //demande de participation d'un médecin
    Traiter_Candidature_Medecin(7, $id_Medecin, 0); //refus
    $Statut_medecin = Get_Statut_Medecin(7, $id_Medecin);
    addTestResult( "Traiter_Candidature_Medecin(refuser) ","Le médecin n'est plus relié à cet essai", 
    ($Statut_medecin==null) ? "Le médecin n'est plus relié à cet essai (Statut_medecin = null)" : "Erreur du statut du médecin",   
    ($Statut_medecin==null) == True); 

    Postuler_Patient_Essai(11, $id_patent);
    Traiter_Candidature_Patient(11, $id_patent, 1);
    $Statut_patient = Get_Statut_Patient(11, $id_patent);
    addTestResult( "Traiter_Candidature_Patient(accepter) ","Le statut du patient est 'actif'", 
    ($Statut_patient=='Actif') ? "le statut du patient est $Statut_patient" : "Erreur du statut du patient",   
    ($Statut_patient=='Actif') == True); 


    $resultat = Verif_Participation_Patient(31); //actif dans un essai
    addTestResult("Verif_Participation_Patient(est actif dans un essai) ","Le patient est actif dans un essai", 
    ($resultat== True) ? "le patient est actif dans un essai" : "Le patient n'est pas actif dans un essai",   
    $resultat == True); 

    $resultat = Verif_Participation_Patient(26); //n'est pas actif dans un essai
    addTestResult("Verif_Participation_Patient(n'est pas actif dans un essai) ","Le patient n'est pas actif dans un essai", 
    ($resultat== True) ? "le patient est actif dans un essai" : "Le patient n'est pas actif dans un essai",   
    $resultat== False);

    $resultat = Verif_Patient_Cet_Essai(11,31); //membre de l'essai
    addTestResult("Verif_Patient_Cet_Essai(est dans l'essai) ","Le patient participe à cet essai", 
    ($resultat== True) ? "le patient participe à cet essai" : "Le patient ne participe pas à cet essai",   
    $resultat == True); 

    $resultat = Verif_Patient_Cet_Essai(11,26); //pas membre de l'essai
    addTestResult("Verif_Patient_Cet_Essai(n'est pas dans l'essai) ","Le patient ne participe pas à cet essai", 
    ($resultat== True) ? "le patient participe à cet essai" : "Le patient ne participe pas à cet essai",   
    $resultat== False);

    $resultat = Verif_Participation_Medecin(13,5);
    addTestResult("Verif_Participation_Medecin(est dans l'essai) ","Le medecin participe à cet essai", 
    ($resultat== True) ? "le medecin participe à cet essai" : "Le medecin ne participe pas à cet essai",   
    $resultat == True); 

    $resultat = Verif_Participation_Medecin(13,2);
    addTestResult("Verif_Participation_Medecin(n'est pas dans l'essai) ","Le medecin ne participe pas à cet essai", 
    ($resultat== True) ? "le medecin participe à cet essai" : "Le medecin ne participe pas à cet essai",   
    $resultat == False); 

    $resultat = Recup_Patients(1); //a des patients
    addTestResult(  "Recup_Patients(a des patients)",   "Renvoie un tableau non vide",   (!empty($resultat[0]) || !empty($resultat[1])) 
          ? "Renvoie un tableau non vide"  : "Pas de patients dans cet essai",  (!empty($resultat[0]) || !empty($resultat[1]))  );

    $resultat = Recup_Patients(3); // Exemple d'ID pour un essai sans patients
    addTestResult(  "Recup_Patients(n'a pas de patients)",   "Renvoie un tableau vide",  (empty($resultat[0]) && empty($resultat[1])) ? 
    "Pas de patients dans cet essai"   : "Renvoie un tableau non vide", (empty($resultat[0]) && empty($resultat[1])) );

    $resultat = Recup_Medecins(1); //a des patients
    addTestResult(  "Recup_Medecins(existe et a des médecins)",   "Renvoie un tableau non vide",   (!empty($resultat[0]) || !empty($resultat[1])) 
          ? "Renvoie un tableau non vide"  : "Pas de médecins dans cet essai",  (!empty($resultat[0]) || !empty($resultat[1]))  );

    $resultat = Recup_Medecins(15); // Essai qui n'existe pas
    addTestResult(  "Recup_Medecins(n'a pas de patients)",   "Renvoie un tableau vide",  (empty($resultat[0]) && empty($resultat[1])) ? 
    "Pas de médecins dans cet essai ou essai inexistant"   : "Renvoie un tableau non vide", (empty($resultat[0]) && empty($resultat[1])) );


     //Supprimer les utilisateurs ajoutés pour les tests
    foreach ($userCreated as $Id) {
        $sql = $pdo->prepare("DELETE FROM `USERS` WHERE `Id_user` = :id_user;");
        $sql->execute(['id_user' => $Id]);
   
   }
    ?>
</thead>
</table>
</body>

</html>     
</html>