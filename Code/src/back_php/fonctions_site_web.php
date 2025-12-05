<?php
// ======================= FICHIER DE FONCTIONS =======================
// Ce fichier contient toutes les fonctions réutilisables pour les pages
// L'objectif est de centraliser le code pour qu'il soit plus propre,
// facile à maintenir et réutilisable sur plusieurs pages.

// =======================  CONNEXION À LA BASE DE DONNÉES =======================
/* Fonction pour connecter la base de données avec PDO
   Utilise try/catch pour gérer les erreurs de connexion
   Retourne l'objet PDO si connexion réussie */
function connectBDD() {
    try {
        $bdd = new PDO("mysql:host=localhost;dbname=projet_site_web;charset=utf8","root","");
        return $bdd;
    } catch (Exception $e) {
        // Si erreur de connexion, on arrête le script et on affiche le message
        die("Erreur : " . $e->getMessage());
    }
}

// ======================= VÉRIFICATION EMAIL EXISTANT =======================
/* Vérifie si une adresse email existe déjà dans la base de données
   Retourne true si l'email existe, false sinon */
function email_existe($bdd, $email) {
    $stmt = $bdd->prepare("SELECT * FROM compte WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// =======================  CONNEXION VALIDE =======================
/* Vérifie si un utilisateur peut se connecter (email + mot de passe corrects)
   Retourne true si connexion possible, false sinon */
function connexion_valide($bdd, $email, $mdp) {
    return email_existe($bdd, $email) && mot_de_passe_correct($bdd, $email, $mdp);
}

// =======================  RÉCUPÉRER ID COMPTE =======================
/* Récupère l'ID du compte à partir de l'email
   Retourne ID du compte si trouvé, null sinon */
function recuperer_id_compte($bdd, $email) {
    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return $user["ID_compte"];
    }
    return null;
}

// =======================  AFFICHAGE BANDEAU DU HAUT =======================
/* Affiche le Bandeau du haut */
function afficher_Bandeau_Haut($bdd, $userID) {
    // S'assurer que la session est démarrée (utilisé plus bas)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Accepter une variable globale $recherche si elle existe,
    // sinon la définir par défaut à false pour éviter l'erreur undefined variable.
    global $recherche;
    if (!isset($recherche)) {
        $recherche = false;
    }
// ------------------- TRAITEMENT DES NOTIFICATIONS POST -------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['id_notif'], $_POST['action_notif'], $_POST['is_projet'])) {

    $idNotif = intval($_POST['id_notif']);
    $action = $_POST['action_notif'];
    $isProjet = intval($_POST['is_projet']);
    $idUtilisateur = $_SESSION['ID_compte'];

    $table = $isProjet ? "notification_projet" : "notification_experience";
    $idCol = $isProjet ? "ID_notification_projet" : "ID_notification_experience";

    $stmt = $bdd->prepare("SELECT * FROM $table WHERE $idCol = ?");
    $stmt->execute([$idNotif]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($notif) {
        $idProjet = $notif['ID_projet'] ?? null;
        $idEnvoyeurOriginal = $notif['ID_compte_envoyeur'];
        $typeNotif = $notif['Type_notif'];

        $nouvelEtat = 0;
        $typeRetour = 0;

        switch ($action) {
            case "valider":
                $nouvelEtat = 1;
                if ($typeNotif == 16) {
                    // Notification simple d'ajout collaborateur - pas de réponse
                    $typeRetour = null;
                } elseif (in_array($typeNotif, [2,3,4,5,12,13,14,15])) {
                    // Notifications de retour - pas de nouvelle réponse
                    $typeRetour = null;
                } else {
                    // Notification type 1, 11 - Envoyer validation
                    $typeRetour = $isProjet ? 12 : 2;
                }
                break;
            case "rejeter":
                $nouvelEtat = 2;
                $typeRetour = $isProjet ? 13 : 3;
                break;
            case "modifier":
                $nouvelEtat = 3;
                $typeRetour = $isProjet ? 14 : 4;
                break;
        }

        // Mettre à jour l'état de la notification
        $update = $bdd->prepare("UPDATE $table SET Valider = ? WHERE $idCol = ?");
        $update->execute([$nouvelEtat, $idNotif]);

        // === GESTION SPÉCIFIQUE POUR LES PROJETS ===
        if ($isProjet && $typeNotif == 11) {
            // Récupérer l'état du créateur du projet
            $stmtCreateur = $bdd->prepare("SELECT c.Etat FROM compte c WHERE c.ID_compte = ?");
            $stmtCreateur->execute([$idEnvoyeurOriginal]);
            $etatCreateur = $stmtCreateur->fetchColumn();

            if ($etatCreateur == 1) {
                // === CAS ÉTUDIANT ===
                if ($nouvelEtat == 1) {
                    // Validation par un gestionnaire
                    // Vérifier si TOUS les gestionnaires ont validé
                    $stmtGestionnaires = $bdd->prepare("
                        SELECT COUNT(*) as total_gest
                        FROM projet_collaborateur_gestionnaire
                        WHERE ID_projet = ? AND Statut = 1
                    ");
                    $stmtGestionnaires->execute([$idProjet]);
                    $totalGest = $stmtGestionnaires->fetchColumn();

                    $stmtValidations = $bdd->prepare("
                        SELECT COUNT(DISTINCT np.ID_compte_receveur) as nb_validations
                        FROM notification_projet np
                        WHERE np.ID_projet = ? 
                        AND np.Type_notif = 11 
                        AND np.Valider = 1
                    ");
                    $stmtValidations->execute([$idProjet]);
                    $nbValidations = $stmtValidations->fetchColumn();

                    // Si tous ont validé, valider le projet
                    if ($nbValidations >= $totalGest) {
                        $up = $bdd->prepare("UPDATE projet SET Validation = 1, Date_de_modification = NOW() WHERE ID_projet = ?");
                        $up->execute([$idProjet]);
                        
                        // Envoyer notification de validation à l'étudiant
                        $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                        $stmtNomProjet->execute([$idProjet]);
                        $nomProjet = $stmtNomProjet->fetchColumn();
                        
                        envoyerNotification($bdd, 12, $idUtilisateur, ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet], [$idEnvoyeurOriginal]);
                        $typeRetour = null; // Déjà envoyé
                    }

                } elseif ($nouvelEtat == 2) {
                    // Refus par un gestionnaire - SUPPRIMER le projet
                    
                    // Récupérer le nom du projet avant suppression
                    $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                    $stmtNomProjet->execute([$idProjet]);
                    $nomProjet = $stmtNomProjet->fetchColumn();
                    
                    // Marquer le projet comme refusé
                    $up = $bdd->prepare("UPDATE projet SET Validation = 2, Date_de_modification = NOW() WHERE ID_projet = ?");
                    $up->execute([$idProjet]);

                    // Annuler toutes les autres notifications en attente pour ce projet
                    $cancelNotifs = $bdd->prepare("UPDATE notification_projet SET Valider = 2 WHERE ID_projet = ? AND Type_notif = 11 AND Valider = 0");
                    $cancelNotifs->execute([$idProjet]);

                    // Notifier l'étudiant créateur du refus (type 13)
                    envoyerNotification($bdd, 13, $idUtilisateur, ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet], [$idEnvoyeurOriginal]);

                    // Supprimer physiquement le projet après un délai (optionnel)
                    // Pour l'instant on le garde avec Validation = 2 pour l'historique
                    // $deleteProjet = $bdd->prepare("DELETE FROM projet WHERE ID_projet = ?");
                    // $deleteProjet->execute([$idProjet]);
                    
                    $typeRetour = null; // Déjà envoyé ci-dessus
                    
                } elseif ($nouvelEtat == 3) {
                    // Demande de modification
                    $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                    $stmtNomProjet->execute([$idProjet]);
                    $nomProjet = $stmtNomProjet->fetchColumn();
                    
                    envoyerNotification($bdd, 14, $idUtilisateur, ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet], [$idEnvoyeurOriginal]);
                    $typeRetour = null; // Déjà envoyé
                }

            } else {
                // === CAS CHERCHEUR/ADMIN ===
                if ($nouvelEtat == 2) {
                    // Un gestionnaire refuse de participer - PROJET RESTE VALIDÉ
                    // Juste retirer ce gestionnaire du projet
                    $deleteGest = $bdd->prepare("
                        DELETE FROM projet_collaborateur_gestionnaire 
                        WHERE ID_projet = ? AND ID_compte = ?
                    ");
                    $deleteGest->execute([$idProjet, $idUtilisateur]);
                    
                    // Envoyer quand même une notification de refus au créateur
                    $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                    $stmtNomProjet->execute([$idProjet]);
                    $nomProjet = $stmtNomProjet->fetchColumn();
                    
                    envoyerNotification($bdd, 13, $idUtilisateur, ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet], [$idEnvoyeurOriginal]);
                    $typeRetour = null; // Déjà envoyé
                    
                } elseif ($nouvelEtat == 1) {
                    // Le gestionnaire accepte de participer - envoyer notification
                    $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                    $stmtNomProjet->execute([$idProjet]);
                    $nomProjet = $stmtNomProjet->fetchColumn();
                    
                    envoyerNotification($bdd, 12, $idUtilisateur, ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet], [$idEnvoyeurOriginal]);
                    $typeRetour = null; // Déjà envoyé
                }
                // Le projet garde Validation = 1 (pas de modification du statut du projet)
            }
        }

        // Envoyer notification de retour si nécessaire (cas généraux non traités ci-dessus)
        if (!empty($typeRetour)) {
            // Récupérer le nom du projet/expérience
            if ($isProjet) {
                $stmtNom = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                $stmtNom->execute([$idProjet]);
                $nomItem = $stmtNom->fetchColumn();
                $donneesNotif = ['ID_projet' => $idProjet, 'Nom_projet' => $nomItem];
            } else {
                $idExperience = $notif['ID_experience'] ?? null;
                $stmtNom = $bdd->prepare("SELECT Nom FROM experience WHERE ID_experience = ?");
                $stmtNom->execute([$idExperience]);
                $nomItem = $stmtNom->fetchColumn();
                $donneesNotif = ['ID_experience' => $idExperience, 'Nom_experience' => $nomItem];
            }
            
            // Éviter les doublons
            $colID = $isProjet ? 'ID_projet' : 'ID_experience';
            $valID = $isProjet ? $idProjet : ($notif['ID_experience'] ?? null);
            
            $verif = $bdd->prepare("SELECT COUNT(*) FROM $table 
                WHERE $colID = ? AND ID_compte_envoyeur = ? AND ID_compte_receveur = ? AND Type_notif = ?");
            $verif->execute([$valID, $idUtilisateur, $idEnvoyeurOriginal, $typeRetour]);
            
            if (!$verif->fetchColumn()) {
                envoyerNotification($bdd, $typeRetour, $idUtilisateur, $donneesNotif, [$idEnvoyeurOriginal]);
            }
        }

        // Supprimer la notification traitée
        $delete = $bdd->prepare("DELETE FROM $table WHERE $idCol = ?");
        $delete->execute([$idNotif]);

        // Recharger la page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

    // ------------------- AFFICHAGE DU BANDEAU -------------------
    $notifications = get_last_notif($bdd, $userID);
    $nb_non_traitees = count(array_filter($notifications, fn($n) => $n['valide'] == 0));

    ?>
    <nav class="site_nav">
        <div id="site_nav_main">
            <a href="Main_page_connected.php" class="lab_logo">
                <img src="../assets/logo_labo.png" alt="Logo_labo">
            </a>

        <?php if ($recherche): ?>
            <!-- Barre de recherche affichée uniquement si $recherche = true -->
            <form class="searchbar" action="Page_rechercher.php" method="GET">
                <!-- Texte saisi -->
                <input type="text" name="texte" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['texte'] ?? '') ?>" />

                <!-- Paramètres fixes -->
                <input type="hidden" name="type[]" value="projet">
                <input type="hidden" name="type[]" value="experience">
                <input type="hidden" name="tri" value="A-Z">
                <input type="hidden" name="ordre" value="asc">

                <button type="submit" class="searchbar-icon">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        <?php endif; ?>
    </div>

        <div id="site_nav_links">
            <ul class="liste_links">
                <li class="main_links"><a href="Page_rechercher.php?texte=&type%5B0%5D=projet&type%5B1%5D=experience&tri=A-Z&ordre=asc" class="Links">Explorer</a></li>
                <li class="main_links"><a href="page_mes_experiences.php" class="Links">Mes expériences</a></li>
                <li class="main_links"><a href="page_mes_projets.php" class="Links">Mes projets</a></li>

                <!-- Notifications -->
                <li id="Notif">
                    <label for="notif_toggle" class="notif_logo">
                        <img src="../assets/Notification_logo.png" alt="Notification">
                        <?php if ($nb_non_traitees > 0): ?>
                            <span class="notif-badge"><?= $nb_non_traitees ?></span>
                        <?php endif; ?>
                    </label>
                    <input type="checkbox" id="notif_toggle" hidden>
                    <div class="overlay">
                        <h3 class="overlay-title">Notifications</h3>
                        <?php if (empty($notifications)): ?>
                            <p class="no-notif">Aucune notification pour le moment.</p>
                        <?php else: ?>
                            <div class="notifications-list">
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="notif-card <?= $notif['valide'] == 0 ? 'notif-non-traitee' : 'notif-traitee' ?>">
                                        <div class="notif-content">
                                            <p class="notif-texte"><?= htmlspecialchars($notif['texte']) ?></p>
                                            <small class="notif-date"><?= htmlspecialchars($notif['date']) ?></small>
                                        </div>

                                        <?php if ($notif['valide'] == 0 && !empty($notif['actions'])): ?>
                                            <div class="notif-actions">
                                                <?php foreach ($notif['actions'] as $act): ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="id_notif" value="<?= $notif['id'] ?>">
                                                        <input type="hidden" name="action_notif" value="<?= $act ?>">
                                                        <input type="hidden" name="is_projet" value="<?= $notif['is_projet'] ? 1 : 0 ?>">
                                                        <button type="submit">
                                                            <?= match($act) {
                                                                'valider' => '✓ Valider',
                                                                'rejeter' => '✗ Rejeter',
                                                                'modifier' => '✎ Demander modification',
                                                                default => 'Action'
                                                            } ?>
                                                        </button>
                                                    </form>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="notif-statut">
                                                <?php
                                                $statusLabels = [
                                                    1 => '✓ Validée',
                                                    2 => '✗ Refusée',
                                                    3 => '✎ Modification demandée'
                                                ];
                                                echo $notif['valide'] ? $statusLabels[$notif['valide']] : '';
                                                ?>
                                            </div>
                                        <?php endif; ?>

                                        <a href="<?= htmlspecialchars($notif['link']) ?>" class="notif-link">Voir détails →</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <label for="notif_toggle" class="close_overlay">Fermer</label>
                    </div>
                </li>

                <!-- Utilisateur -->
                <li id="User">
                    <a href="page_profil.php" class="user_logo">
                        <?php
                        $path = "../assets/profile_pictures/" . $userID . ".png";
                        if (!file_exists($path)) $path = "../assets/profile_pictures/model.jpg";
                        ?>
                        <img src="<?= $path ?>" alt="Photo de profil">
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <?php
}


// =======================  VÉRIFIER SI ADMIN =======================
/* Vérifie si un compte est administrateur
   Retourne true si l'utilisateur est admin, false sinon */
function est_admin($bdd, $email) {
    $stmt = $bdd->prepare("SELECT etat FROM compte WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return $user["etat"] == 3;
    }
    return false;
}


// =======================  RÉCUPERER LES DERNIERE NOTIFICATIONS =======================

/**
 * Récupère les notifications d'un utilisateur
 * Les notifications non traitées (Valider=0) sont considérées comme "non lues"
 */
function get_last_notif($bdd, $IDuser, $limit = 10) {
    // Notifications projets
    $notif_projet = $bdd->prepare("
        SELECT 
            Ce.Nom AS Nom_envoyeur, 
            Ce.Prenom AS Prenom_envoyeur,
            np.Type_notif, 
            np.Date_envoi, 
            np.Valider,
            np.ID_notification_projet AS ID_notification,
            p.Nom_projet,
            NULL AS Nom_experience,
            p.ID_projet,
            NULL AS ID_experience
        FROM notification_projet AS np
        JOIN projet AS p ON np.ID_projet = p.ID_projet
        JOIN compte AS Ce ON np.ID_compte_envoyeur = Ce.ID_compte
        WHERE np.ID_compte_receveur = ?
        ORDER BY np.Date_envoi DESC
    ");
    $notif_projet->execute([$IDuser]);

    // Notifications expériences
    $notif_experience = $bdd->prepare("
        SELECT 
            Ce.Nom AS Nom_envoyeur,
            Ce.Prenom AS Prenom_envoyeur,
            ne.Type_notif,
            ne.Date_envoi,
            ne.Valider,
            ne.ID_notification_experience AS ID_notification,
            NULL AS Nom_projet,
            e.Nom AS Nom_experience,
            NULL AS ID_projet,
            e.ID_experience
        FROM notification_experience AS ne
        JOIN experience AS e ON ne.ID_experience = e.ID_experience
        JOIN compte AS Ce ON ne.ID_compte_envoyeur = Ce.ID_compte
        WHERE ne.ID_compte_receveur = ?
        ORDER BY ne.Date_envoi DESC
    ");
    $notif_experience->execute([$IDuser]);

    // Fusionner et trier
    $tab_notifications = array_merge(
        $notif_projet->fetchAll(PDO::FETCH_ASSOC),
        $notif_experience->fetchAll(PDO::FETCH_ASSOC)
    );

    usort($tab_notifications, function($a, $b) {
        return strtotime($b['Date_envoi']) - strtotime($a['Date_envoi']);
    });

    $notifications = array_slice($tab_notifications, 0, $limit);

    // Textes des notifications
    $texte_notifications = [
        1  => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé de créer l\'expérience {Nom_experience}',
        2  => '{Nom_envoyeur} {Prenom_envoyeur} a validé l\'expérience {Nom_experience}',
        3  => '{Nom_envoyeur} {Prenom_envoyeur} a refusé l\'expérience {Nom_experience}',
        4  => '{Nom_envoyeur} {Prenom_envoyeur} vous a invité à modifier l\'expérience {Nom_experience}',
        5  => '{Nom_experience} a été modifiée par {Nom_envoyeur} {Prenom_envoyeur}',
        11 => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé de créer le projet {Nom_projet}',
        12 => '{Nom_envoyeur} {Prenom_envoyeur} a validé le projet {Nom_projet}',
        13 => '{Nom_envoyeur} {Prenom_envoyeur} a refusé le projet {Nom_projet}',
        14 => '{Nom_envoyeur} {Prenom_envoyeur} vous a invité à modifier le projet {Nom_projet}',
        15 => '{Nom_projet} a été modifié par {Nom_envoyeur} {Prenom_envoyeur}',
        16 => '{Nom_envoyeur} {Prenom_envoyeur} vous a ajouté comme collaborateur sur le projet {Nom_projet}',
    ];

    $result = [];
    foreach ($notifications as $notif) {
        $type = $notif['Type_notif'];
        $texte = str_replace(
            ['{Nom_envoyeur}', '{Prenom_envoyeur}', '{Nom_experience}', '{Nom_projet}'],
            [$notif['Nom_envoyeur'], $notif['Prenom_envoyeur'], $notif['Nom_experience'] ?? '', $notif['Nom_projet'] ?? ''],
            $texte_notifications[$type] ?? 'Notification inconnue'
        );

        $link = ($type >= 1 && $type <= 5)
            ? "page_experience.php?id_projet=".$notif['ID_projet']."&id_experience=".$notif['ID_experience']
            : ($type >= 11 && $type <= 15 ? "page_projet.php?id_projet=".$notif['ID_projet'] : "#");

        $actions = [];

        if ($notif['Valider'] == 0) {
            if (in_array($type, [1, 11, 16])) {
             // notifications de création de projet/expérience ou ajout collaborateur
            $actions = ['valider', 'rejeter', 'modifier'];
                    if ($type == 16) $actions = ['valider']; // le collaborateur peut juste valider
            } elseif (in_array($type, [2,3,4,5,12,13,14,15])) {
            // notifications de retour au créateur ou info
                $actions = ['valider'];
            }
        }

        $statut_texte = '';
        switch($notif['Valider']) {
            case 0: $statut_texte = 'Non traitée'; break;
            case 1: $statut_texte = 'Validée'; break;
            case 2: $statut_texte = 'Refusée'; break;
            case 3: $statut_texte = 'Modification demandée'; break;
        }

        $result[] = [
            'id' => $notif['ID_notification'],
            'texte' => $texte,
            'date' => date('d/m/Y H:i', strtotime($notif['Date_envoi'])),
            'link' => $link,
            'valide' => $notif['Valider'], // clé front-end
            'statut_texte' => $statut_texte,
            'type' => $type,
            'actions' => $actions,
            'is_projet' => ($type >= 11)
        ];
    }

    return $result;
}


// =======================  INSÉRER UN UTILISATEUR =======================
/* Insère un nouvel utilisateur dans la base de données
   Retourne true si insertion réussie, false sinon */
function inserer_utilisateur($bdd, $nom, $prenom, $date, $etat, $email, $mdp_hash) {
    $sql = $bdd->prepare("INSERT INTO compte (Nom, Prenom, date_de_naissance, etat, email, Mdp) VALUES (?, ?, ?, ?, ?, ?)");
    return $sql->execute([$nom, $prenom, $date, $etat, $email, $mdp_hash]);
}

// =======================  Affichage bandeau du bas =======================
/* Affiche le bandeau du bas de page  */ 
function afficher_Bandeau_Bas() { 
    ?>
    <nav class="site_footer">
        <!-- Création de 3 div les unes après les autres -->
        <div id="Contact">
            <p class="titre">Contact</p>
            <p class="text">Campus SophiaTech</p>
            <p class="text">930 Route des Colles</p>
            <p class="text">BP 145, 06903 Sophia Antipolis</p>
        </div>
        <div id="Powered">
            <p class="milieu">Powered</p>
            <p class="milieu">by la Polyteam</p>
        </div>
        <div id="Concepteurs">
            <p class="titre">Concepteurs</p>
            <p class="text">Bardet Trouilloud Juliette</p>
            <p class="text">Helias Ewan</p>
            <p class="text">Marhabi Zahir Hajar</p>
            <p class="text">Rey Axel</p>

        </div>
    </nav>
<?php } 

// =======================  Récupération de l'ensemble des expériences =======================
function get_mes_experiences_complets(PDO $bdd, ?int $id_compte = null): array {

    // --- 1. Requête principale
    $sql_experiences = "
        SELECT 
            e.ID_experience, 
            e.Nom, 
            e.Validation, 
            e.Description, 
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Resultat,
            e.Statut_experience,
            e.Date_de_creation,
            e.Date_de_modification,
            s.Nom_Salle,
            p.Nom_projet,
            p.ID_projet
        FROM experience e
        LEFT JOIN projet_experience pe
            ON pe.ID_experience = e.ID_experience
        LEFT JOIN projet p
            ON p.ID_projet = pe.ID_projet
        LEFT JOIN materiel_experience se
            ON e.ID_experience = se.ID_experience
        LEFT JOIN salle_materiel s
            ON se.ID_materiel = s.ID_materiel
        INNER JOIN experience_experimentateur ee
            ON e.ID_experience = ee.ID_experience
    ";

    if ($id_compte !== null) {
        $sql_experiences .= " WHERE ee.ID_compte = :id_compte";
        $stmt = $bdd->prepare($sql_experiences);
        $stmt->execute(['id_compte' => $id_compte]);
    } else {
        $stmt = $bdd->prepare($sql_experiences);
        $stmt->execute();
    }

    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($experiences)) {
        return [];
    }

    // --- 2. Récupérer tous les IDs d'expérience
    $ids_exp = array_column($experiences, 'ID_experience');
    $in = str_repeat('?,', count($ids_exp) - 1) . '?';

    // --- 3. Requête pour récupérer tous les expérimentateurs
    $sql_experimentateurs = "
        SELECT 
            ee.ID_experience,
            c.Nom,
            c.Prenom
        FROM experience_experimentateur ee
        INNER JOIN compte c
            ON ee.ID_compte = c.ID_compte
        WHERE ee.ID_experience IN ($in)
    ";
    $stmt2 = $bdd->prepare($sql_experimentateurs);
    $stmt2->execute($ids_exp);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. Regrouper les expérimentateurs par expérience
    $experimentateurs = [];
    foreach ($rows as $row) {
        $experimentateurs[$row['ID_experience']][] = $row['Prenom'] . ' ' . $row['Nom'];
    }

    // --- 5. Ajouter les expérimentateurs et progression
    foreach ($experiences as &$exp) {
        $exp['Experimentateurs'] = $experimentateurs[$exp['ID_experience']] ?? [];
        

    return $experiences;
}
}

// =======================  Gère le nombre de pages qui devront être créées =======================
function create_page(array $items, int $items_par_page = 6): int {
    $total_items = count($items);
    if ($total_items == 0) {
        return 1;
    }
    return (int)ceil($total_items / $items_par_page);  # Retourne le nombre de pages qui seront créées
}

// =======================  affichage des expériences sur plusieurs pages =======================
function afficher_experiences_pagines(array $experiences, int $page_actuelle = 1, int $items_par_page = 6): void {
    // On récupère l'indice de la première expérience qui sera affichée
    $debut = ($page_actuelle - 1) * $items_par_page;
    $experiences_page = array_slice($experiences, $debut, $items_par_page);
    $bdd = connectBDD();
    
    ?>
    <div class="liste">
        <?php if (empty($experiences_page)): ?>
            <p class="no-experiences">Aucune expérience à afficher</p>
        <?php else:
            foreach ($experiences_page as $exp):
                $id_experience = htmlspecialchars($exp['ID_experience']);
                $nom = htmlspecialchars($exp['Nom']);
                $description = $exp['Description'];
                $desc = strlen($description) > 200 
                    ? htmlspecialchars(substr($description, 0, 200)) . '…'
                    : htmlspecialchars($description);    
                $date_reservation = htmlspecialchars($exp['Date_reservation']);
                $heure_debut = htmlspecialchars($exp['Heure_debut']);
                $heure_fin = htmlspecialchars($exp['Heure_fin']);
                $salle = htmlspecialchars($exp['Nom_salle'] ?? 'Non définie');
                $nom_projet = htmlspecialchars($exp['Nom_projet'] ?? 'Sans projet');
                $id_projet = htmlspecialchars($exp['ID_projet']);
                ?>
                
                <div class='experience-card' onclick="location.href='page_experience.php?id_projet=<?= $id_projet ?>&id_experience=<?= $id_experience ?>'">
                    <div class="experience-header">
                        <h3><?= $nom ?></h3>
                        <span class="projet-badge"><?= $nom_projet ?></span>
                    </div>
                    <p class="description"><?= $desc ?></p>
                    <div class="experience-details">
                        <p><strong>Date :</strong> <?= $date_reservation ?></p>
                        <p><strong>Horaires :</strong> <?= $heure_debut ?> - <?= $heure_fin ?></p>
                        <p><strong>Salle :</strong> <?= $salle ?></p>
                        <?php if (est_admin($bdd, $_SESSION["email"])) {
                            // lance une fonction qui ajoute 2 boutons : modification et suppression 
                            ?>
                            <div class="right-section">
                                <div class="box">
                                    <button class="btn btnBlanc"  
                                        onclick="event.stopPropagation(); location.href='page_modifier_experience.php'">
                                        Modifier</button>
                                    <a href="page_admin_experiences.php?action=supprimer&id=<?php echo $id_experience; ?>"
                                        class="btn btnRouge"
                                        onclick="event.stopPropagation();">
                                        Supprimer</a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    </div>
            <?php endforeach;
        endif; ?>
    </div>
    <?php
}

function afficher_pagination(int $page_actuelle, int $total_pages, string $type = 'a_venir'): void {
    if ($total_pages <= 1) return;
    
    // Préserver l'autre paramètre de page dans l'URL
    # Debug :  Ces 2 lignes changeront probablement car ne fonctionnent pas avec la page admin
    $autre_type = ($type === 'a_venir') ? 'terminees' : 'a_venir';
    $autre_page = isset($_GET["page_$autre_type"]) ? (int)$_GET["page_$autre_type"] : 1;
    
    ?>
    <div class="pagination">
        <?php if ($page_actuelle > 1): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle - 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">« Précédent</a>
        <?php endif;
        
        for ($i = 1; $i <= $total_pages; $i++):
            if ($i == $page_actuelle): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page_<?= $type ?>=<?= $i ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn"><?= $i ?></a>
            <?php endif; 
        endfor;
        
        if ($page_actuelle < $total_pages): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle + 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">Suivant »</a>
        <?php endif; ?>
    </div>
    <?php
}
?>

<?php
    // Configuration des types de notifications
$TYPES_NOTIFICATIONS = [
    1 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé de créer l\'expérience {Nom_experience}',
        'destinataire' => 'gestionnaire',
        'actions' => ['valider', 'rejeter', 'modifier']
    ],
    2 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} a validé l\'expérience {Nom_experience}',
        'destinataire' => 'utilisateur_connecte',
        'actions' => ['valider']
    ],
    3 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} a refusé l\'expérience {Nom_experience}',
        'destinataire' => 'utilisateur_connecte',
        'actions' => ['valider']
    ],
    4 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} vous a invité à modifier l\'expérience {Nom_experience}',
        'destinataire' => 'utilisateur_connecte',
        'actions' => ['valider']
    ],
    5 => [
        'texte' => '{Nom_experience} a été modifiée par {Nom_envoyeur} {Prenom_envoyeur}',
        'destinataire' => 'experimentateur',
        'actions' => ['valider']
    ],
    11 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé de créer le projet {Nom_projet}',
        'destinataire' => 'chercheur',
        'actions' => ['valider', 'rejeter', 'modifier']
    ],
    12 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} a validé le projet {Nom_projet}',
        'destinataire' => 'etudiant',
        'actions' => ['valider']
    ],
    13 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} a refusé le projet {Nom_projet}',
        'destinataire' => 'etudiant',
        'actions' => ['valider']
    ],
    14 => [
        'texte' => '{Nom_envoyeur} {Prenom_envoyeur} vous a invité à modifier le projet {Nom_projet}',
        'destinataire' => 'etudiant',
        'actions' => ['valider']
    ],
    15 => [
        'texte' => '{Nom_projet} a été modifiée par {Nom_envoyeur} {Prenom_envoyeur}',
        'destinataire' => 'etudiant',
        'actions' => ['valider']
    ]
];

/**
 * Fonction principale pour envoyer une notification
 * 
 * $bdd connexion à la base de données
 * $typeNotification Type de notification (1-5, 11-15)
 * $idEnvoyeur ID de l'utilisateur qui envoie
 * $donnees Données dynamiques (Nom_experience, Nom_projet, etc.)
 * $destinataires ID ou tableau d'IDs des destinataires (optionnel)
 * True si succès, false si échec
 */
function envoyerNotification($bdd, $typeNotification, $idEnvoyeur, $donnees, $destinataires) {
    if (empty($destinataires)) return;
    $date_envoi = date('Y-m-d H:i:s');

    foreach ($destinataires as $idDestinataire) {
        try {
            if (in_array($typeNotification, [1,2,3,4,5])) {
                // Notification expérience
                $idExperience = $donnees['ID_experience'] ?? null;
                $stmt = $bdd->prepare("
                    INSERT INTO notification_experience 
                        (ID_compte_envoyeur, ID_compte_receveur, ID_experience, Type_notif, Date_envoi, Valider)
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([$idEnvoyeur, $idDestinataire, $idExperience, $typeNotification, $date_envoi]);
            } else {
                // Notification projet
                $idProjet = $donnees['ID_projet'] ?? null;
                $stmt = $bdd->prepare("
                    INSERT INTO notification_projet 
                        (ID_compte_envoyeur, ID_compte_receveur, ID_projet, Type_notif, Date_envoi, Valider)
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([$idEnvoyeur, $idDestinataire, $idProjet, $typeNotification, $date_envoi]);
            }
        } catch (Exception $e) {
            error_log("Erreur notification: ".$e->getMessage());
        }
    }
}



// =======================  Vérifie si l'utilisateur est connecté =======================
// Permet de limiter l'accès aux pages qui requiert une connexion
function verification_connexion($bdd) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['ID_compte'])) {
        layout_erreur();
    }

    // Vérifier que l'utilisateur existe en base
    $query = $bdd->prepare("SELECT COUNT(*) FROM compte WHERE ID_COMPTE = ?");
    $query->execute([$_SESSION['ID_compte']]);

    if ($query->fetchColumn() == 0) {
        // L'id n'est pas valide ⇒ on déconnecte
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

// =======================  Lançage d'une page d'erreur =======================
// Si une erreur se produit, ou si l'utilisateur n'est pas censé avec accès à cette page,
// cette page est affichée à la place
function layout_erreur() {
    ?>
    <html lang='en'>
    <head>
        <!-- On appelle le css ici car sinon il n'a pas le temps de charger -->
        <link rel="stylesheet" href="../css/layout_erreur.css">
        <title>Erreur</title>
    </head>

    <body>        
        <div class="small_dog">
            <img alt='Sleeping dog' class='dog1' src='../assets/dog-sleep.png'>
            <img alt='Sleeping dog' class='dog2' src='../assets/dog-sleep.gif'>
            <!-- <img alt="Sleepy dog." src="../assets/dog-sleep.gif" onclick="audio.play();"> -->
        </div>

        <p id=text_error> Il y a eu une erreur. Veuillez retourner à la page précédente.</p>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
            let audio = new Audio('../assets/sound/error.mp3'); // Musique de Mario64
            audio.loop = true;

            // On veut faire en sorte que lorsque l'image est cliquée, le gif se lance avec un son
            // Lorsqu'on clique à nouveau, le gif s'arrête et le son également
            $(document).on('click', '.small_dog', function() {
                let isPlaying = $(this).hasClass('playing');

                if (!isPlaying) {
                    // Passe en mode "gif + son"
                    $(this).addClass('playing');
                    $('.dog1').css('opacity', 0);
                    $('.dog2').css('opacity', 1);
                    audio.play();
                } else {
                    // Reviens à l'image normale + stop son
                    $(this).removeClass('playing');
                    $('.dog1').css('opacity', 1);
                    $('.dog2').css('opacity', 0);
                    audio.pause();
                }
            });
        </script>
    </body>
</html>
    <?php
    exit;
}

// =======================  Récupération du titre d'un utilisateur =======================
function get_etat($etat) {
    if ($etat==1) {
        return "Étudiant";
    }
    elseif ($etat==2) {
        return "Chercheur";
    }
    elseif ($etat==3) {
        return "Administrateur";
    }
    else {
        return "Erreur";
    }
}

// =======================  Supprime une experience à partir de son identifiant =======================
function supprimer_experience($bdd, $id_experience) {
    $stmt = $bdd->prepare("DELETE FROM experience WHERE ID_experience = ?");
    $stmt->execute([$id_experience]);
}

// =======================  Suppression d'un utilisateur à partir de son identifiant =======================
function supprimer_utilisateur($bdd, $id_user) {
    $stmt = $bdd->prepare("DELETE FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id_user]);
}

// =======================  Acceptation de l'inscription d'un utilisateur =======================
function accepter_utilisateur($bdd, $id_user) {
    $stmt = $bdd->prepare("UPDATE compte SET validation = 1 WHERE ID_compte = ?");
    $stmt->execute([$id_user]);
}

/**
 * Notifie les gestionnaires d'un projet lorsqu'un utilisateur propose une expérience
 *
 * @param PDO $bdd Connexion base de données
 * @param int $idProjet ID du projet concerné
 * @param int $idEnvoyeur ID de l'utilisateur qui propose l'expérience
 * @param int $idExperience ID de l'expérience proposée
 */
function notifier_gestionnaires_experience($bdd, $idProjet, $idEnvoyeur, $idExperience) {
    // 1. Vérifier si le projet est confidentiel
    $stmt = $bdd->prepare("SELECT Confidentialite FROM projet WHERE ID_projet = ?");
    $stmt->execute([$idProjet]);
    $confidentialite = $stmt->fetchColumn();

    if ($confidentialite == 1) return; // Projet confidentiel → pas de notification

    // 2. Récupérer les gestionnaires actifs du projet
    $stmt = $bdd->prepare("
        SELECT ID_compte 
        FROM projet_collaborateur_gestionnaire
        WHERE ID_projet = ? AND Statut = 1
    ");
    $stmt->execute([$idProjet]);
    $gestionnaires = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($gestionnaires)) return; // Pas de gestionnaires

    // 3. Retirer l'envoyeur s'il est gestionnaire
    $destinataires = array_diff($gestionnaires, [$idEnvoyeur]);
    if (empty($destinataires)) return; // Plus personne à notifier

    // 4. Préparer les données pour la notification
    $donnees = ['ID_experience' => $idExperience];

    // 5. Envoyer la notification type 1 (proposition expérience)
    envoyerNotification($bdd, 1, $idEnvoyeur, $donnees, $destinataires);
}


?>