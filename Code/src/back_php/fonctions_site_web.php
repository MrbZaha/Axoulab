<?php
// ======================= FICHIER DE FONCTIONS =======================
// Ce fichier contient toutes les fonctions réutilisables pour les pages
// L'objectif est de centraliser le code pour qu'il soit plus propre,
// facile à maintenir et réutilisable sur plusieurs pages.

// =======================  CONNEXION À LA BASE DE DONNÉES =======================

/**
 * Établit une connexion à la base de données MySQL via PDO.
 *
 * Cette fonction crée une connexion PDO vers la base de données
 * `projet_site_web` hébergée en local. Elle utilise un bloc try/catch
 * afin de gérer proprement les erreurs de connexion.
 *
 * @return PDO Objet PDO représentant la connexion à la base de données.
 *
 * @throws Exception Arrête le script si la connexion échoue.
 */

function connectBDD() :PDO{
    try {
        // Tentative de connexion à la base de données
        $bdd = new PDO(
            "mysql:host=localhost;dbname=projet_site_web;charset=utf8",
            "root",
            ""
        );
        return $bdd;
    } catch (Exception $e) {
        // Si une erreur se produit lors de la connexion, affichage du message d'erreur
        die("Erreur de connexion à la base de donnée");
    }
}

// ======================= VÉRIFICATION EMAIL EXISTANT =======================

/**
 * Vérifie si une adresse email existe déjà dans la base de données.
 *
 * Cette fonction interroge la table `compte` afin de déterminer
 * si un compte est associé à l’adresse email fournie.
 *
 * @param PDO    $bdd   Connexion PDO à la base de données.
 * @param string $email Adresse email à vérifier.
 *
 * @return bool True si l’email existe, false sinon.
 */
function email_existe(PDO $bdd, string $email) :bool{
    // Préparation de la requête SQL pour vérifier l'existence de l'email
    $stmt = $bdd->prepare("SELECT 1 FROM compte WHERE email = ?");
    $stmt->execute([$email]);

    // Vérifie si le nombre de lignes renvoyées est supérieur à 0
    return $stmt->rowCount() > 0;
}

// =======================  CONNEXION VALIDE =======================

/**
 * Vérifie si les identifiants de connexion d’un utilisateur sont valides.
 *
 * La connexion est considérée comme valide si :
 *  - l’email existe en base
 *  - le mot de passe correspond à celui stocké
 *
 * @param PDO    $bdd   Connexion PDO à la base de données.
 * @param string $email Adresse email de l’utilisateur.
 * @param string $mdp   Mot de passe fourni par l’utilisateur.
 *
 * @return bool True si la connexion est valide, false sinon.
 */
function connexion_valide(PDO $bdd, string $email, string $mdp) :bool{
    return email_existe($bdd, $email) && mot_de_passe_correct($bdd, $email, $mdp);
}

// =======================  RÉCUPÉRER ID COMPTE =======================

/**
 * Récupère l’identifiant d’un compte à partir de son adresse email.
 *
 * Cette fonction permet d’obtenir l’ID unique d’un utilisateur
 * afin de l’utiliser dans d’autres traitements (sessions, relations, etc.).
 *
 * @param PDO    $bdd   Connexion PDO à la base de données.
 * @param string $email Adresse email du compte recherché.
 *
 * @return int|null Identifiant du compte si trouvé, null sinon.
 */
function recuperer_id_compte(PDO $bdd, string $email) : ?int{
    // Préparation de la requête SQL pour récupérer l'ID en fonction de l'email
    $stmt = $bdd->prepare("SELECT ID_compte FROM compte WHERE email = ?");
    $stmt->execute([$email]);

    // Si l'email est trouvé, on retourne l'ID du compte
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return $user["ID_compte"];
    }

    // Si l'email n'est pas trouvé, retourne null
    return null;
}

// =======================  VÉRIFICATION MOT DE PASSE CORRECT =======================

/**
 * Vérifie la conformité d’un mot de passe selon des règles de sécurité.
 *
 * Le mot de passe doit contenir :
 *  - au moins 8 caractères
 *  - au moins une lettre majuscule
 *  - au moins une lettre minuscule
 *  - au moins un chiffre
 *  - au moins un caractère spécial
 *
 * @param string $mdp Mot de passe à vérifier.
 *
 * @return array Tableau vide si le mot de passe est valide,
 *               sinon un tableau contenant les messages d’erreur.
 */
function verifier_mdp(string $mdp) :array{

    // Tableau où seront ajoutées les erreurs éventuelles
    $erreurs = [];

    // Longueur minimale
    if (strlen($mdp) < 8)
        $erreurs[] = "au moins 8 caractères";

    // Présence d’une lettre majuscule
    if (!preg_match('/[A-Z]/', $mdp))
        $erreurs[] = "au moins une majuscule";

    // Présence d’une lettre minuscule
    if (!preg_match('/[a-z]/', $mdp))
        $erreurs[] = "au moins une minuscule";

    // Présence d'un chiffre
    if (!preg_match('/[0-9]/', $mdp))
        $erreurs[] = "au moins un chiffre";

    // Présence d'un caractère spécial
    if (!preg_match('/[\W_]/', $mdp))
        $erreurs[] = "au moins un caractère spécial (!@#$%^&*...)";

    // Retourne le tableau : vide si OK, rempli si erreurs
    return $erreurs;
}


// =======================  AFFICHAGE BANDEAU DU HAUT =======================
/* Affiche le Bandeau du haut */
/**
 * Affiche le bandeau de navigation en haut de la page avec logo, recherche, liens et notifications.
 *
 * Cette fonction gère également le traitement POST des notifications :
 * - Validation ou rejet des notifications
 * - Mise à jour des statuts d'expérience ou de projet selon le type
 * - Envoi automatique de notifications de retour
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $userID ID de l'utilisateur connecté
 * @param bool $recherche Affiche ou non la barre de recherche (défaut true)
 * @return void
 */
function afficher_Bandeau_Haut_notification(PDO $bdd, int $userID, bool $recherche = true) :void{

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
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
            // ===== RÉCUPÉRATION DES IDs UNE SEULE FOIS AU DÉBUT =====
            $idProjet = $notif['ID_projet'] ?? null;
            $idExperience = $notif['ID_experience'] ?? null;
            $idEnvoyeurOriginal = $notif['ID_compte_envoyeur'];
            $typeNotif = $notif['Type_notif'];
            
            // DEBUG - Ajout temporaire pour diagnostic
            error_log("DEBUG NOTIF: idNotif=$idNotif, typeNotif=$typeNotif, isProjet=$isProjet, idProjet=$idProjet, idExperience=$idExperience");
            error_log("DEBUG NOTIF DATA: " . print_r($notif, true));

            $nouvelEtat = 0;
            $typeRetour = 0;

            switch ($action) {
                case "valider":
                    $nouvelEtat = 1;
                    if ($typeNotif == 16) {
                        // Notification simple d'ajout collaborateur - pas de réponse
                        $typeRetour = null;
                    } elseif (in_array($typeNotif, [2,3,4,5,12,13])) {
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
            }

            // Mettre à jour l'état de la notification
            $update = $bdd->prepare("UPDATE $table SET Valider = ? WHERE $idCol = ?");
            $update->execute([$nouvelEtat, $idNotif]);

            // --- Validation expérience par gestionnaire ---
            if (!$isProjet && $typeNotif == 1) {
                if ($idExperience) {
                    // Ne mettre à jour que si c'est validé (1) ou refusé (2)
                    if (in_array($nouvelEtat, [1, 2])) {
                        $updateExp = $bdd->prepare("
                            UPDATE experience 
                            SET Validation = ?, Date_de_modification = NOW() 
                            WHERE ID_experience = ?
                        ");
                        $updateExp->execute([$nouvelEtat, $idExperience]);
                    }

                     // --- NOTIFICATION POUR LES EXPÉRIMENTATEURS ---
                    if ($nouvelEtat == 1) { // uniquement si validée
                        // Récupérer les expérimentateurs liés à cette expérience
                        $experimentateurs = get_experimentateurs_ids($bdd, $idExperience);

                        if (!empty($experimentateurs)) {

                            // Nom de l'expérience
                            $stmtNomExp = $bdd->prepare("SELECT Nom FROM experience WHERE ID_experience = ?");
                            $stmtNomExp->execute([$idExperience]);
                            $nomExp = $stmtNomExp->fetchColumn();

                            // Récupérer l'ID du créateur de l'expérience
                            $stmtCreateurExp = $bdd->prepare("SELECT ID_compte FROM experience_experimentateur WHERE ID_experience = ?");
                            $stmtCreateurExp->execute([$idExperience]);
                            $idCreateurExp = $stmtCreateurExp->fetchColumn();

                            // Retirer le gestionnaire actuel de la liste pour éviter de s'envoyer une notification
                            $experimentateurs = array_diff($experimentateurs, [$idCreateurExp]);

                            // Créer une notification pour chaque expérimentateur
                            foreach ($experimentateurs  as $idExpUser) {
                                
                                envoyerNotification(
                                    $bdd,
                                    4, 
                                    $idUtilisateur, // gestionnaire qui valide
                                    ['ID_experience' => $idExperience, 'Nom_experience' => $nomExp],
                                    [$idExpUser]
                                );
                            }
                        }
                    }
                }
            }

            // === GESTION SPÉCIFIQUE POUR LES PROJETS ===
            if ($isProjet && $typeNotif == 11) {
                // Récupérer l'état du créateur du projet
                $stmtCreateur = $bdd->prepare("SELECT c.Etat FROM compte c WHERE c.ID_compte = ?");
                $stmtCreateur->execute([$idEnvoyeurOriginal]);
                $etatCreateur = $stmtCreateur->fetchColumn();

                if ($etatCreateur == 1) {
                            // === CAS ÉTUDIANT ===
            // Récupérer l'état actuel du projet
                $stmtProjetEtat = $bdd->prepare("SELECT Validation FROM projet WHERE ID_projet = ?");
                $stmtProjetEtat->execute([$idProjet]);
                $validationActuelle = $stmtProjetEtat->fetchColumn();
                
                if ($nouvelEtat == 1) {
                    // === VALIDATION ===
                    if (in_array($validationActuelle, [0,2])) {
                        $up = $bdd->prepare("UPDATE projet SET Validation = 1, Date_de_modification = NOW() WHERE ID_projet = ?");
                        $up->execute([$idProjet]);
                    }
                    // On garde ce gestionnaire dans la table participants
                } elseif ($nouvelEtat == 2) {
                    // === REFUS ===
                    if ($validationActuelle == 0) {
                        // Premier refus → mettre projet refusé
                        $up = $bdd->prepare("UPDATE projet SET Validation = 2, Date_de_modification = NOW() WHERE ID_projet = ?");
                        $up->execute([$idProjet]);
                    }
                    // Retirer ce gestionnaire de la table, même si projet déjà validé
                    $deleteGest = $bdd->prepare("
                        DELETE FROM projet_collaborateur_gestionnaire 
                        WHERE ID_projet = ? AND ID_compte = ?
                    ");
                    $deleteGest->execute([$idProjet, $idUtilisateur]);
                }

                // Envoyer notification à l'étudiant
                $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                $stmtNomProjet->execute([$idProjet]);
                $nomProjet = $stmtNomProjet->fetchColumn();

                envoyerNotification(
                    $bdd,
                    $nouvelEtat == 1 ? 12 : 13,
                    $idUtilisateur,
                    ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet],
                    [$idEnvoyeurOriginal]
                );

                } else {
                    // === CAS CHERCHEUR/ADMIN ===
                    if ($nouvelEtat == 2) {
                        $deleteGest = $bdd->prepare("
                            DELETE FROM projet_collaborateur_gestionnaire 
                            WHERE ID_projet = ? AND ID_compte = ?
                        ");
                        $deleteGest->execute([$idProjet, $idUtilisateur]);
                        
                        $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                        $stmtNomProjet->execute([$idProjet]);
                        $nomProjet = $stmtNomProjet->fetchColumn();
                        
                        envoyerNotification($bdd, 13, $idUtilisateur, ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet], [$idEnvoyeurOriginal]);
                        $typeRetour = null;
                        
                    } elseif ($nouvelEtat == 1) {
                        $stmtNomProjet = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                        $stmtNomProjet->execute([$idProjet]);
                        $nomProjet = $stmtNomProjet->fetchColumn();
                        
                        envoyerNotification($bdd, 12, $idUtilisateur, ['ID_projet' => $idProjet, 'Nom_projet' => $nomProjet], [$idEnvoyeurOriginal]);
                        $typeRetour = null;
                    }
                }
            }

            // ===== ENVOI DE NOTIFICATION DE RETOUR (CAS GÉNÉRAUX) =====
            if (!empty($typeRetour)) {
                if ($isProjet) {
                    if ($idProjet) {
                        $stmtNom = $bdd->prepare("SELECT Nom_projet FROM projet WHERE ID_projet = ?");
                        $stmtNom->execute([$idProjet]);
                        $nomItem = $stmtNom->fetchColumn();
                        $donneesNotif = ['ID_projet' => $idProjet, 'Nom_projet' => $nomItem];
                    } else {
                        error_log("DEBUG: ID_projet manquant pour la notif $idNotif");
                        $donneesNotif = null;
                    }
                } else {
                    if ($idExperience) {
                        $stmtNom = $bdd->prepare("SELECT Nom FROM experience WHERE ID_experience = ?");
                        $stmtNom->execute([$idExperience]);
                        $nomItem = $stmtNom->fetchColumn();
                        $donneesNotif = ['ID_experience' => $idExperience, 'Nom_experience' => $nomItem];
                    } else {
                        error_log("DEBUG: ID_experience manquant pour la notif $idNotif");
                        $donneesNotif = null;
                    }
                }   
                
                if ($donneesNotif) {
                    $colID = $isProjet ? 'ID_projet' : 'ID_experience';
                    $valID = $isProjet ? $idProjet : $idExperience;
                    
                    $verif = $bdd->prepare("SELECT COUNT(*) FROM $table 
                        WHERE $colID = ? AND ID_compte_envoyeur = ? AND ID_compte_receveur = ? AND Type_notif = ?");
                    $verif->execute([$valID, $idUtilisateur, $idEnvoyeurOriginal, $typeRetour]);
                    
                    if (!$verif->fetchColumn()) {
                        envoyerNotification($bdd, $typeRetour, $idUtilisateur, $donneesNotif, [$idEnvoyeurOriginal]);
                    }
                }
            }

            // Supprimer la notification traitée
            $delete = $bdd->prepare("DELETE FROM $table WHERE $idCol = ?");
            $delete->execute([$idNotif]);

            // Recharger la page en préservant les paramètres GET
            $redirect_url = $_SERVER['PHP_SELF'];
            if (!empty($_GET)) {
                $redirect_url .= '?' . http_build_query($_GET);
            }
            header("Location: " . $redirect_url);
            exit;
        }
    }
}


/**
 * Affiche le bandeau de navigation supérieur du site pour un utilisateur connecté.
 *
 * Cette fonction génère le HTML complet du bandeau, incluant :
 * - Le logo du site
 * - Une barre de recherche (optionnelle)
 * - Les liens de navigation (Dashboard si admin, Explorer, Mes expériences, Mes projets)
 * - Les notifications récentes avec indicateur du nombre de notifications non traitées
 *   et actions possibles (valider, rejeter)
 * - L'avatar de l'utilisateur avec lien vers son profil
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $userID ID de l'utilisateur connecté
 * @param bool $recherche Indique si la barre de recherche doit être affichée (true par défaut)
 *
 * @return void Cette fonction n'a pas de valeur de retour, elle affiche directement le HTML.
 *
 * Comportement :
 * - Les notifications sont récupérées via `get_last_notif()` et triées en fonction de leur statut.
 * - Les actions disponibles sur chaque notification sont affichées si elle n'a pas encore été traitée.
 * - Le nombre de notifications non traitées est affiché sous forme de badge.
 * - La photo de profil de l'utilisateur est affichée, avec une image par défaut si aucun fichier n'existe.
 * - La barre de recherche est affichée uniquement si `$recherche` est true.
 */

function afficher_Bandeau_Haut(PDO $bdd, int $userID, $recherche = true) :void{  
    $notifications = get_last_notif($bdd, $userID);
    $nb_non_traitees = count(array_filter($notifications, fn($n) => $n['valide'] == 0));
    afficher_Bandeau_Haut_notification($bdd, $userID, $recherche=true);
    

    ?>
    <nav class="site_nav">
        <div id="site_nav_main">
            <a href="Main_page_connected.php" class="lab_logo">
                <img src="../assets/logo_labo.png" alt="Logo_labo">
            </a>

        <?php if ($recherche): ?>
            <!-- Barre de recherche affichée uniquement si $recherche = true -->
            <form class="searchbar" action="page_rechercher.php" method="GET">
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
                <?php if (est_admin_par_id($bdd, $_SESSION["ID_compte"])): ?>
                    <li class="main_links"><a href="page_admin.php" class="Links">Dashboard</a></li>
                <?php endif; ?>
                <li class="main_links"><a href="page_rechercher.php?&afficher_confidentiels=on&tri=A-Z&ordre=asc" class="Links">Explorer</a></li>
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
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="id_notif" value="<?= $notif['id'] ?>">
                                                        <input type="hidden" name="action_notif" value="<?= $act ?>">
                                                        <input type="hidden" name="is_projet" value="<?= $notif['is_projet'] ? 1 : 0 ?>">
                                                        <button type="submit">
                                                            <?= match($act) {
                                                                'valider' => '✓ Valider',
                                                                'rejeter' => '✗ Rejeter',
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
/**
 * Vérifie si un compte est administrateur à partir de son adresse email.
 *
 * Un utilisateur est considéré comme administrateur lorsque
 * la valeur de la colonne `etat` de son compte est égale à 3.
 *
 * @param PDO    $bdd   Objet PDO représentant la connexion à la base de données.
 * @param string $email Adresse email du compte à vérifier.
 *
 * @return bool True si l’utilisateur est administrateur, false sinon.
 */

function est_admin(PDO $bdd, string $email) :bool{
    // Prépare la requête pour récupérer l'état du compte
    $stmt = $bdd->prepare("SELECT etat FROM compte WHERE email = ?");
    $stmt->execute([$email]);

    // Vérifie si l'email existe dans la base
    if ($stmt->rowCount() > 0) {
        // Récupère les informations du compte
        $user = $stmt->fetch();
        // Retourne true si etat == 3 (admin)
        return $user["etat"] == 3;
    }

    // Si aucun compte trouvé ou non admin
    return false;
}

/**
 * Vérifie si un compte est administrateur à partir de son identifiant.
 *
 * Cette fonction récupère l’état du compte correspondant à l’ID fourni
 * et vérifie s’il correspond à un rôle administrateur (Etat = 3).
 *
 * @param PDO $bdd Objet PDO représentant la connexion à la base de données.
 * @param int $id_compte Identifiant du compte à vérifier.
 *
 * @return bool True si le compte est administrateur, false sinon.
 */

function est_admin_par_id(PDO $bdd, int $id_compte): bool {
    // Prépare la requête SQL pour récupérer l'état du compte
    $stmt = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id_compte]);

    // Récupère la ligne correspondante
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifie que l'état existe et correspond à un admin
    return $row && isset($row['Etat']) && (int)$row['Etat'] === 3;
}

// =======================  RÉCUPÉRER LES DERNIÈRES NOTIFICATIONS =======================
/**
 * Récupère les dernières notifications d’un utilisateur.
 *
 * Cette fonction regroupe les notifications liées aux projets
 * et aux expériences, les fusionne, les trie par date décroissante
 * et retourne un tableau structuré prêt à être affiché côté front-end.
 *
 * Règles appliquées :
 *  - Les notifications avec Valider = 0 sont considérées comme non traitées
 *  - Les notifications projets et expériences sont fusionnées
 *  - Le résultat est limité par le paramètre $limit
 *
 * @param PDO $bdd Objet PDO représentant la connexion à la base de données.
 * @param int $IDuser Identifiant de l’utilisateur connecté.
 * @param int $limit Nombre maximum de notifications retournées (par défaut 10).
 *
 * @return array Tableau contenant les notifications formatées.
 */

function get_last_notif(PDO $bdd, int $IDuser, int $limit = 10) :array{

    // ======================= NOTIFICATIONS DE PROJETS =======================
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

    // ======================= NOTIFICATIONS D'EXPÉRIENCES =======================
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

    // ======================= FUSION + TRI PAR DATE =======================
    $tab_notifications = array_merge(
        $notif_projet->fetchAll(PDO::FETCH_ASSOC),
        $notif_experience->fetchAll(PDO::FETCH_ASSOC)
    );

    usort($tab_notifications, function($a, $b) {
        return strtotime($b['Date_envoi']) - strtotime($a['Date_envoi']);
    });

    // Limitation au nombre souhaité
    $notifications = array_slice($tab_notifications, 0, $limit);

    // ======================= TEXTES DES NOTIFICATIONS =======================
    $texte_notifications = [
        1  => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé l\'expérience {Nom_experience}',
        2  => '{Nom_envoyeur} {Prenom_envoyeur} a validé l\'expérience {Nom_experience}',
        3  => '{Nom_envoyeur} {Prenom_envoyeur} a refusé l\'expérience {Nom_experience}',
        4  => '{Nom_envoyeur} {Prenom_envoyeur} vous a ajouté comme experimentateur sur l\'expérience {Nom_experience}',
        11 => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé de créer le projet {Nom_projet}',
        12 => '{Nom_envoyeur} {Prenom_envoyeur} a validé le projet {Nom_projet}',
        13 => '{Nom_envoyeur} {Prenom_envoyeur} a refusé le projet {Nom_projet}',
        16 => '{Nom_envoyeur} {Prenom_envoyeur} vous a ajouté comme collaborateur sur le projet {Nom_projet}',
    ];

    // ======================= CONSTRUCTION DU RÉSULTAT FINAL =======================
    $result = [];
    foreach ($notifications as $notif) {

        $type = $notif['Type_notif'];

        // Génération du texte à afficher
        $texte = str_replace(
            ['{Nom_envoyeur}', '{Prenom_envoyeur}', '{Nom_experience}', '{Nom_projet}'],
            [
                $notif['Nom_envoyeur'],
                $notif['Prenom_envoyeur'],
                $notif['Nom_experience'] ?? '',
                $notif['Nom_projet'] ?? ''
            ],
            $texte_notifications[$type] ?? 'Notification inconnue'
        );

        // Détermination du lien associé à la notification
        $link = ($type >= 1 && $type <= 4)
            ? "page_experience.php?id_projet=".$notif['ID_projet']."&id_experience=".$notif['ID_experience']
            : ($type >= 11 && $type <= 13
                ? "page_projet.php?id_projet=".$notif['ID_projet']
                : "#"
            );

        // Actions disponibles pour la notification
        $actions = [];

        if ($notif['Valider'] == 0) {
            if (in_array($type, [1, 11, 16])) {
                $actions = ['valider', 'rejeter'];
                if ($type == 16) $actions = ['valider'];
            } elseif (in_array($type, [2,3,4,5,12,13,14,15])) {
                $actions = ['valider'];
            }
        }

        // Texte du statut de validation
        $statut_texte = '';
        switch($notif['Valider']) {
            case 0: $statut_texte = 'Non traitée'; break;
            case 1: $statut_texte = 'Validée'; break;
            case 2: $statut_texte = 'Refusée'; break;
        }

        // Ajout au tableau final
        $result[] = [
            'id' => $notif['ID_notification'],
            'texte' => $texte,
            'date' => date('d/m/Y H:i', strtotime($notif['Date_envoi'])),
            'link' => $link,
            'valide' => $notif['Valider'],
            'statut_texte' => $statut_texte,
            'type' => $type,
            'actions' => $actions,
            'is_projet' => ($type >= 11)
        ];
    }

    return $result;
}


/**
 * Affiche le bandeau de bas de page du site.
 *
 * Cette fonction génère le footer du site contenant les informations
 * de contact, la mention "Powered by" ainsi que la liste des concepteurs.
 * Le contenu est affiché directement en HTML.
 *
 * @return void
 */

function afficher_Bandeau_Bas() :void{
    ?>
    <nav class="site_footer">
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

/**
 * Récupère la liste complète des expériences.
 *
 * Cette fonction récupère toutes les expériences depuis la base de données,
 * en incluant les projets associés, les salles utilisées et les expérimentateurs.
 * Si un identifiant de compte est fourni, seules les expériences liées à
 * cet utilisateur sont retournées.
 *
 * @param PDO      $bdd       Connexion à la base de données
 * @param int|null $id_compte ID du compte utilisateur (optionnel)
 *
 * @return array Tableau associatif contenant les expériences
 */
function get_mes_experiences_complets(PDO $bdd, ?int $id_compte = null): array {
    $sql_experiences = "
        SELECT DISTINCT
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
            GROUP_CONCAT(DISTINCT s.Nom_Salle SEPARATOR ', ') AS Nom_Salle,
            GROUP_CONCAT(DISTINCT p.Nom_projet SEPARATOR ', ') AS Nom_projet,
            GROUP_CONCAT(DISTINCT p.ID_projet SEPARATOR ',') AS ID_projet
        FROM experience e
        LEFT JOIN projet_experience pe
            ON pe.ID_experience = e.ID_experience
        LEFT JOIN projet p
            ON p.ID_projet = pe.ID_projet
        LEFT JOIN materiel_experience se
            ON e.ID_experience = se.ID_experience
        LEFT JOIN salle_materiel s
            ON se.ID_materiel = s.ID_materiel
        LEFT JOIN experience_experimentateur ee
            ON e.ID_experience = ee.ID_experience
    ";

    if ($id_compte !== null) {
        $sql_experiences .= " WHERE (ee.ID_compte = :id_compte
            OR EXISTS (
                SELECT 1 FROM projet_experience pe2
                JOIN projet_collaborateur_gestionnaire pcg ON pcg.ID_projet = pe2.ID_projet
                WHERE pe2.ID_experience = e.ID_experience AND pcg.ID_compte = :id_compte
            )
        )";
    }

    $sql_experiences .= " GROUP BY e.ID_experience";

    $stmt = $bdd->prepare($sql_experiences);

    if ($id_compte !== null) {
        $stmt->execute(['id_compte' => $id_compte]);
    } else {
        $stmt->execute();
    }

    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($experiences)) {
        return [];
    }

    $ids_exp = array_column($experiences, 'ID_experience');
    $in = str_repeat('?,', count($ids_exp) - 1) . '?';

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

    $experimentateurs = [];
    foreach ($rows as $row) {
        $experimentateurs[$row['ID_experience']][] = $row['Prenom'] . ' ' . $row['Nom'];
    }

    foreach ($experiences as &$exp) {
        $exp['Experimentateurs'] = $experimentateurs[$exp['ID_experience']] ?? [];
    }

    return $experiences;
}

/**
 * Récupère tous les projets existants.
 *
 * Cette fonction retourne tous les projets de la base de données,
 * enrichis de la progression et du statut de l'utilisateur connecté
 * (Gestionnaire, Collaborateur ou Aucun).
 *
 * @param PDO $bdd       Connexion à la base de données
 * @param int $id_compte ID de l'utilisateur connecté
 *
 * @return array Tableau associatif contenant les projets
 */

function get_all_projet(PDO $bdd, int $id_compte): array {
    $sql_projets = "
        SELECT 
            p.ID_projet, 
            p.Nom_projet AS Nom, 
            p.Description, 
            p.Confidentiel, 
            p.Validation, 
            p.Date_de_creation,
            p.Date_de_modification
        FROM projet p
    ";
    
    $stmt = $bdd->prepare($sql_projets);
    $stmt->execute();
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($projets)) {
        return [];
    }

    $ids_projets = array_column($projets, 'ID_projet');
    $in = str_repeat('?,', count($ids_projets) - 1) . '?';

    $sql_statut_user = "
        SELECT 
            ID_projet,
            Statut
        FROM projet_collaborateur_gestionnaire
        WHERE ID_compte = ? AND ID_projet IN ($in)
    ";
    $params = array_merge([$id_compte], $ids_projets);
    $stmt2 = $bdd->prepare($sql_statut_user);
    $stmt2->execute($params);
    $statuts_user = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $statut_par_projet = [];
    foreach ($statuts_user as $row) {
        $statut_par_projet[$row['ID_projet']] = $row['Statut'];
    }

    foreach ($projets as &$p) {
        $p['Progression'] = progression_projet($bdd, (int)$p['ID_projet']);
        if (isset($statut_par_projet[$p['ID_projet']])) {
            $p['Statut'] = $statut_par_projet[$p['ID_projet']] == 1 ? 'Gestionnaire' : 'Collaborateur';
        } else {
            $p['Statut'] = 'Aucun';
        }
    }

    return $projets;
}


/**
 * Calcule le nombre de pages nécessaires pour afficher tous les éléments.
 *
 * @param array $items        Tableau des éléments à paginer
 * @param int   $items_par_page Nombre d'éléments par page (défaut 6)
 *
 * @return int Nombre total de pages à créer
 */
function create_page(array $items, int $items_par_page = 6): int {
    $total_items = count($items);
    if ($total_items == 0) {
        return 1;
    }
    return (int)ceil($total_items / $items_par_page);
}

/**
 * Affiche une liste paginée d'expériences.
 *
 * Cette fonction génère le HTML pour afficher les expériences en
 * fonction de la page actuelle et du nombre d'éléments par page.
 * Les administrateurs voient des boutons de modification et suppression
 * si la page est une page admin.
 *
 * @param PDO   $bdd            Objet PDO de la base de données
 * @param array $experiences    Tableau des expériences
 * @param int   $page_actuelle  Page en cours (défaut 1)
 * @param int   $items_par_page Nombre d'éléments par page (défaut 6)
 * @param bool  $page_admin     Indique si la page est une page d'administration
 *
 * @return void Affiche le HTML directement
 */
function afficher_experiences_pagines(PDO $bdd, array $experiences, int $page_actuelle = 1, int $items_par_page = 6, bool $page_admin = false): void {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $experiences_page = array_slice($experiences, $debut, $items_par_page);
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
                $salle = htmlspecialchars($exp['Nom_Salle'] ?? 'Non définie');
                $nom_projet = htmlspecialchars($exp['Nom_projet'] ?? 'Sans projet');
                $id_projet = htmlspecialchars($exp['ID_projet']);
                ?>
                <div class='experience-card' onclick="location.href='page_experience.php?id_projet=<?= $id_projet ?>&id_experience=<?= $id_experience ?>'">
                    <div class="experience-header">
                        <h3><?= $nom ?></h3>
                        <span class="projet-badge"><?= $nom_projet ?></span>
                        <?php if (experience_confidentiel($bdd, $id_experience)) :?>
                            <span class="conf-badge"></span>
                        <?php endif; ?>
                    </div>
                    <p class="description"><?= $desc ?></p>
                    <div class="experience-details">
                        <p><strong>Date :</strong> <?= $date_reservation ?></p>
                        <p><strong>Horaires :</strong> <?= $heure_debut ?> - <?= $heure_fin ?></p>
                        <p><strong>Salle :</strong> <?= $salle ?></p>
                        <?php if (est_admin_par_id($bdd, $_SESSION["ID_compte"])) {
                            if ($page_admin == true) {
                                // Si on est admin ET qu'on est sur une page admin
                                // ajoute 2 boutons : modification et suppression 
                                ?>
                                    <!-- Bouton Modifier (reste en GET, pas critique côté CSRF) -->
                                    <button class="btn btnViolet"  
                                        onclick="event.stopPropagation(); location.href='page_modification_experience.php?id_experience=<?= $id_experience ?>'">
                                        Modifier
                                    </button>

                                    <!-- Formulaire suppression sécurisé en POST avec CSRF -->
                                    <form method="post" action="page_admin_experiences.php" style="display:inline;" 
                                        onsubmit="return confirm('Voulez-vous vraiment supprimer cette expérience ?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id_experience" value="<?= (int)$id_experience ?>">
                                        <button type="submit" class="btn btnRouge" onclick="event.stopPropagation();">
                                            Supprimer
                                        </button>
                                    </form>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            <?php endforeach; 
        endif; ?>
    </div>
    <?php
}

/**
 * Affiche la pagination des pages.
 *
 * Génère les liens "Précédent", "Suivant" et les numéros de page.
 *
 * @param int $page_actuelle Numéro de la page courante
 * @param int $total_pages   Nombre total de pages
 *
 * @return void Affiche le HTML directement
 */
function afficher_pagination(int $page_actuelle, int $total_pages): void {
    if ($total_pages <= 1) return;
    ?>
    <div class="pagination">
        <?php if ($page_actuelle > 1): ?>
            <a href="?page=<?= $page_actuelle - 1 ?>" class="page-btn">« Précédent</a>
        <?php endif;
        
        for ($i = 1; $i <= $total_pages; $i++):
            if ($i == $page_actuelle): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>" class="page-btn"><?= $i ?></a>
            <?php endif; 
        endfor;
        
        if ($page_actuelle < $total_pages): ?>
            <a href="?page=<?= $page_actuelle + 1 ?>" class="page-btn">Suivant »</a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Envoie des notifications aux utilisateurs.
 *
 * Selon le type de notification, cette fonction insère une entrée
 * dans la table `notification_experience` ou `notification_projet`.
 *
 * @param PDO   $bdd             Objet PDO de la base de données
 * @param int   $typeNotification Type de notification (1-5 pour expériences, 11-15 pour projets)
 * @param int   $idEnvoyeur       ID de l'utilisateur qui envoie la notification
 * @param array $donnees          Données dynamiques (ex: ID_experience, ID_projet)
 * @param array $destinataires    ID ou tableau d'IDs des destinataires
 *
 * @return void
 */
function envoyerNotification(PDO $bdd, int $typeNotification, int $idEnvoyeur, array $donnees, array $destinataires) :void{
    if (empty($destinataires)) return;
    $date_envoi = date('Y-m-d H:i:s');

    foreach ($destinataires as $idDestinataire) {
        try {
            if (in_array($typeNotification, [1,2,3,4,5])) {
                $idExperience = $donnees['ID_experience'] ?? null;
                $stmt = $bdd->prepare("
                    INSERT INTO notification_experience 
                        (ID_compte_envoyeur, ID_compte_receveur, ID_experience, Type_notif, Date_envoi, Valider)
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([$idEnvoyeur, $idDestinataire, $idExperience, $typeNotification, $date_envoi]);
            } else {
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



/**
 * Vérifie si l'utilisateur est connecté et valide son existence en base.
 *
 * Si l'utilisateur n'est pas connecté ou si son ID n'existe pas en base,
 * la fonction redirige vers une page d'erreur ou de connexion.
 *
 * @param PDO $bdd Objet PDO de la base de données
 *
 * @return void Redirige ou appelle layout_erreur() si non autorisé
 */
function verification_connexion(PDO $bdd) :void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['ID_compte'])) {
        layout_erreur();
    }

    $query = $bdd->prepare("SELECT COUNT(*) FROM compte WHERE ID_COMPTE = ?");
    $query->execute([$_SESSION['ID_compte']]);

    if ($query->fetchColumn() == 0) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

/**
 * Affiche une page d'erreur standard.
 *
 * Cette page est affichée lorsqu'une erreur survient ou si l'utilisateur
 * tente d'accéder à une page non autorisée.
 *
 * @return void Affiche le HTML et arrête l'exécution
 */
function layout_erreur() :void{
    ?>
    <html lang='en'>
    <head>
        <link rel="stylesheet" href="../css/layout_erreur.css">
        <title>Erreur</title>
    </head>
    <body>        
        <div class="small_dog">
            <img alt='Sleeping dog' class='dog' src='../assets/dog-sleep.gif'>
        </div>
        <p id="text_error">Il y a eu une erreur. Veuillez retourner à la page précédente.</p>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Retourne le titre correspondant à un état utilisateur.
 *
 * @param int $etat Code de l'état (1 = Étudiant, 2 = Chercheur, 3 = Administrateur)
 *
 * @return string Nom du statut ou "Erreur" si non reconnu
 */
function get_etat(int $etat) :string{
    if ($etat==1) {
        return "Étudiant";
    } elseif ($etat==2) {
        return "Chercheur";
    } elseif ($etat==3) {
        return "Administrateur";
    } else {
        return "Erreur";
    }
}

/**
 * Supprime une expérience à partir de son identifiant.
 *
 * @param PDO $bdd           Objet PDO de la base de données
 * @param int $id_experience ID de l'expérience à supprimer
 *
 * @return void
 */
function supprimer_experience(PDO $bdd, int $id_experience) :void{
    $stmt = $bdd->prepare("DELETE FROM experience WHERE ID_experience = ?");
    $stmt->execute([$id_experience]);
}

/**
 * Supprime un utilisateur à partir de son identifiant.
 *
 * @param PDO $bdd    Objet PDO de la base de données
 * @param int $id_user ID de l'utilisateur à supprimer
 *
 * @return void
 */
function supprimer_utilisateur(PDO $bdd, int $id_user) :void{
    $stmt = $bdd->prepare("DELETE FROM compte WHERE ID_compte = ?");
    $stmt->execute([$id_user]);
}

/**
 * Supprime un projet ainsi que toutes les expériences qui lui sont liées.
 *
 * @param PDO $bdd       Objet PDO de la base de données
 * @param int $id_projet ID du projet à supprimer
 *
 * @return void
 * @throws Exception En cas d'erreur lors de la suppression
 */
function supprimer_projet(PDO $bdd, int $id_projet) :void{
    try {
        $bdd->beginTransaction();

        $stmt = $bdd->prepare("
            DELETE FROM experience
            WHERE ID_experience IN (
                SELECT ID_experience
                FROM projet_experience
                WHERE ID_projet = ?
            )
        ");
        $stmt->execute([$id_projet]);

        $stmt = $bdd->prepare("DELETE FROM projet WHERE ID_projet = ?");
        $stmt->execute([$id_projet]);

        $bdd->commit();
    } catch (Exception $e) {
        $bdd->rollBack();
        throw $e;
    }
}

/**
 * Accepte l'inscription d'un utilisateur en validant son compte.
 *
 * @param PDO $bdd    Objet PDO de la base de données
 * @param int $id_user ID de l'utilisateur à valider
 *
 * @return void
 */
function accepter_utilisateur(PDO $bdd, int $id_user) :void{
    $stmt = $bdd->prepare("UPDATE compte SET validation = 1 WHERE ID_compte = ?");
    $stmt->execute([$id_user]);
}

/**
 * Filtre et trie les projets et/ou expériences pour un utilisateur donné.
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $id_compte ID de l'utilisateur connecté
 * @param array $types Types à inclure : 'projet', 'experience' ou les deux
 * @param string $tri Critère de tri : 'A-Z', 'date_modif', 'date_creation'
 * @param string $ordre Ordre du tri : 'asc' ou 'desc'
 * @param string|null $texte Texte à rechercher dans le nom/description
 * @param int|null $confid Filtre sur la confidentialité (0 = public, 1 = confidentiel)
 * @param int|null $statut_proj Filtre sur le statut des projets (ex : progression)
 * @param array|null $statut_exp Filtre sur le statut des expériences
 *
 * @return array Tableau fusionné de projets et expériences filtrés et triés
 */
function filtrer_trier_pro_exp(PDO $bdd, 
    int $id_compte,
    array $types = ['projet','experience'],
    string $tri = 'A-Z',
    string $ordre = 'asc',
    ?string $texte = null, 
    ?int $confid = null, 
    ?int $statut_proj = null,
    ?array $statut_exp = null
): array {

    $info = [];

    
    // --- Filtrer les projets si "projet" est dans le tableau
    if (in_array('projet', $types) || empty($type)) {
        $projets = get_all_projet($bdd, $id_compte); 
        foreach ($projets as &$p) {
            $p["Type"] = "projet";
        }
        $projets_filtree = filtrer_projets($projets, $texte, $confid, $statut_proj);
    } else {
        $projets_filtree = [];
    }

    // --- Filtrer les expériences si "experience" est dans le tableau
    if (in_array('experience', $types)|| empty($type)) {
        $experiences = get_mes_experiences_complets($bdd);
        foreach ($experiences as &$e) {
            $e["Type"] = "experience";
        }
        $exp_filtree = filtrer_experience($experiences, $texte, $statut_exp);
    } else {
        $exp_filtree = [];
    }

    $info = array_merge($projets_filtree, $exp_filtree);

    if (!empty($info)) {
        usort($info, function($a, $b) use ($tri, $ordre) {
            $valA = null;
            $valB = null;

            switch ($tri) {
                case 'A-Z':
                    $valA = strtolower($a['Nom'] ?? '');
                    $valB = strtolower($b['Nom'] ?? '');
                    break;
                case 'date_creation':
                    $valA = strtotime($a['Date_de_creation'] ?? '0');
                    $valB = strtotime($b['Date_de_creation'] ?? '0');
                    break;
                case 'date_modif':
                    $valA = strtotime($a['Date_de_modification'] ?? '0');
                    $valB = strtotime($b['Date_de_modification'] ?? '0');
                    break;
                default:
                    $valA = 0;
                    $valB = 0;
            }

            if ($valA == $valB) return 0;

            return ($ordre === 'asc') ? (($valA < $valB) ? -1 : 1) : (($valA > $valB) ? -1 : 1);
        });
    }

    return $info;
}

/**
 * Filtre une liste de projets selon différents critères.
 *
 * @param array $liste_projets Tableau de projets à filtrer
 * @param string|null $texte Texte à rechercher dans le nom, description, gestionnaires ou collaborateurs
 * @param int|null $confid Filtre sur la confidentialité (0 = public, 1 = confidentiel)
 * @param int|null $statut Filtre sur le statut/progression du projet
 *
 * @return array Tableau de projets filtrés
 */
function filtrer_projets(
    array $liste_projets, 
    ?string $texte = null, 
    ?int $confid = null, 
    ?int $statut = null
): array {

    $resultat = [];
    $ids_vus = [];
    $t = strtolower($texte ?? "");

    foreach ($liste_projets as $proj) {

        if (!empty($texte)) {
            $match = false;

            if (str_contains(strtolower($proj["Nom"] ?? ""), $t)) $match = true;
            if (!$match && str_contains(strtolower($proj["Description"] ?? ""), $t)) $match = true;

            if (!$match && !empty($proj["Gestionnaires"]) && is_array($proj["Gestionnaires"])) {
                foreach ($proj["Gestionnaires"] as $g) {
                    if (str_contains(strtolower($g), $t)) { $match = true; break; }
                }
            }

            if (!$match && !empty($proj["Collaborateurs"]) && is_array($proj["Collaborateurs"])) {
                foreach ($proj["Collaborateurs"] as $c) {
                    if (str_contains(strtolower($c), $t)) { $match = true; break; }
                }
            }

            if (!$match) continue;
        }

        if ($confid === null && (($proj["Confidentiel"] ?? 0) == 1)) continue;
        if ($statut === null && (($proj['Progression'] ?? 0) == 100)) continue;

        $id = $proj["ID_projet"] ?? null;
        if ($id !== null) {
            if (isset($ids_vus[$id])) continue;
            $ids_vus[$id] = true;
        }

        $resultat[] = $proj;
    }

    return $resultat;
}



/**
 * Filtre une liste de d'experience selon plusieurs critères
 *
 * @param array $liste_experience Tableau des expériences à filtrer
 * @param string|null $texte Texte à rechercher dans le nom, description, experimentateurs
 * @param array|null $statut Tableau des états d'experiences acceptées
 *
 * @return array Tableau des experiences filtrées
 */

function filtrer_experience(
    array $liste_experience, 
    ?string $texte = null, 
    ?array $statut = []
): array {

    $resultat = [];
    $ids_vus = [];
    $t = strtolower($texte ?? "");

    foreach ($liste_experience as $exp) {

        // --- 1. Filtre texte (Nom + Description + Experimentateur)
        if (!empty($texte)) {
            $match = false;

            // Nom
            if (str_contains(strtolower($exp["Nom"] ?? ""), $t)) $match = true;

            // Description
            if (!$match && str_contains(strtolower($exp["Description"] ?? ""), $t)) $match = true;

            // Nom expérimentateur
            if (
                !$match &&
                !empty($exp["Nom_experimentateur"]) &&
                str_contains(strtolower($exp["Nom_experimentateur"]), $t)
            ) {
                $match = true;
            }

            if (!$match) continue;
        }

        // --- 2. Statut (plusieurs possibles)
        if (!empty($statut)) {
            if (!in_array($exp["Statut_experience"], $statut)) {
                continue;
            }
        }

        // --- 3. Anti-doublons
        $id = $exp["ID_experience"] ?? null;

        if ($id !== null) {
            if (isset($ids_vus[$id])) continue; // déjà ajouté
            $ids_vus[$id] = true;
        }

        // --- 4. Ajout final
        $resultat[] = $exp;
    }

    return $resultat;
}


/**
 * Calcule le nombre d'expériences terminées et le total pour un projet donné.
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $IDprojet ID du projet
 * @return array Tableau associatif ['finies' => int, 'total' => int]
 */
function progression_projet(PDO $bdd, int $IDprojet): array {
    $sql_projet_exp = "
        SELECT 
            p.ID_projet,
            ex.Statut_experience 
        FROM projet p
        INNER JOIN projet_experience AS pex
            ON p.ID_projet = pex.ID_projet    
        INNER JOIN experience AS ex
            ON pex.ID_experience = ex.ID_experience
        WHERE p.ID_projet = :id_projet";
    $stmt = $bdd->prepare($sql_projet_exp);
    $stmt->execute(['id_projet' => $IDprojet]);
    $proj_exp = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($proj_exp)) {
        return ['finies' => 0, 'total' => 0];
    }

    $finies = 0;
    foreach ($proj_exp as $exp) {
        if ((int)$exp['Statut_experience'] === 2) $finies++;
    }

    return ['finies' => $finies, 'total' => count($proj_exp)];
}

/**
 * Affiche une barre de progression HTML pour un projet.
 *
 * @param int $finies Nombre d'expériences terminées
 * @param int $total Nombre total d'expériences
 * @return string HTML complet de la barre de progression
 */
function afficher_barre_progression(int $finies, int $total): string {
    $pourcentage = $total > 0 ? ($finies / $total) * 100 : 0;
    $couleur = '#27ae60'; // Vert

    $html = '
    <div class="barre-progression-container">
        <div class="barre-progression-fond">
            <div class="barre-progression-remplissage" style="width: ' . $pourcentage . '%; background-color: ' . $couleur . ';">
                <span class="barre-progression-texte">' . $finies . '/' . $total . '</span>
            </div>
        </div>
    </div>

    <style>
        .barre-progression-container { width: 100%; margin: 10px 0; }
        .barre-progression-fond { width: 100%; height: 30px; background-color: #ecf0f1; border-radius: 15px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); position: relative; }
        .barre-progression-remplissage { height: 100%; transition: width 0.5s ease-in-out; display: flex; align-items: center; justify-content: center; border-radius: 15px; position: relative; min-width: 60px; }
        .barre-progression-texte { color: white; font-weight: bold; font-size: 14px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); position: absolute; left: 50%; transform: translateX(-50%); }
    </style>';

    return $html;
}

/**
 * Modifie le statut d'une expérience dans la base de données.
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $id ID de l'expérience
 * @param int $value Nouveau statut (0 = pas commencée, 1 = en cours, 2 = terminée)
 * @return void
 */
function modifie_value_exp(PDO $bdd, int $id, int $value): void {
    $sql_maj_bdd = "
        UPDATE experience
        SET Statut_experience = :Statut_experience
        WHERE ID_experience = :id
    ";
    $stmt = $bdd->prepare($sql_maj_bdd);
    $stmt->execute([
        ':Statut_experience' => $value,
        ':id' => $id
    ]);
}

/**
 * Met à jour automatiquement le statut de toutes les expériences en fonction de la date/heure actuelle.
 *
 * Statuts :
 * - 0 : Pas encore commencée
 * - 1 : En cours
 * - 2 : Terminée
 *
 * @param PDO $bdd Connexion à la base de données
 * @return void
 */
function maj_bdd_experience(PDO $bdd): void {
    $now = new DateTime();
    $now_datetime = new DateTime($now->format('Y-m-d H:i'));

    $sql = "
        SELECT 
            ID_experience, 
            Date_reservation, 
            Heure_debut,
            Heure_fin, 
            Statut_experience
        FROM experience
        WHERE Statut_experience IN (0, 1)
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute();
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($experiences as $exp) {
        $exp_datetime_debut = new DateTime($exp['Date_reservation'] . ' ' . $exp['Heure_debut']);
        $exp_datetime_fin = new DateTime($exp['Date_reservation'] . ' ' . $exp['Heure_fin']);

        $nouveau_statut = null;
        if ($now_datetime < $exp_datetime_debut) $nouveau_statut = 0;
        elseif ($now_datetime >= $exp_datetime_debut && $now_datetime <= $exp_datetime_fin) $nouveau_statut = 1;
        elseif ($now_datetime > $exp_datetime_fin) $nouveau_statut = 2;

        if ($nouveau_statut !== null && (int)$exp['Statut_experience'] !== $nouveau_statut) {
            modifie_value_exp($bdd, $exp['ID_experience'], $nouveau_statut);
        }
    }
}


// =======================  Fonction d'affichage des projets =======================

/**
 * Affiche une liste paginée de projets avec leurs détails et barre de progression.
 *
 * Chaque projet affiche :
 * - Nom, description (max 200 caractères), date de création
 * - Barre de progression (expériences terminées / total)
 * - Rôle de l'utilisateur (Gestionnaire / Collaborateur / Aucun)
 * - Boutons Modifier / Supprimer si l'utilisateur est admin et sur une page admin
 *
 * @param PDO $bdd Connexion à la base de données
 * @param array $projets Liste complète des projets
 * @param int $page_actuelle Page actuelle (par défaut 1)
 * @param int $items_par_page Nombre d'éléments par page (par défaut 6)
 * @param bool $page_admin Indique si on est sur une page admin (affiche les boutons)
 * @return void
 */
function afficher_projets_pagines(PDO $bdd, array $projets, int $page_actuelle = 1, int $items_par_page = 6, bool $page_admin = false): void {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $projets_page = array_slice($projets, $debut, $items_par_page);
    
    ?>
    <div class="liste">
        <?php if (empty($projets_page)): ?>
            <p class="no-projects">Aucun projet en cours</p>
        <?php else: 
            foreach ($projets_page as $p):
                $id = htmlspecialchars($p['ID_projet']);
                $progression = progression_projet($bdd, $id);
                $nom = htmlspecialchars($p['Nom']);
                $description = $p['Description'];
                $desc = strlen($description) > 200 
                    ? htmlspecialchars(substr($description, 0, 200)) . '…'
                    : htmlspecialchars($description);
                $date = htmlspecialchars($p['Date_de_creation']);
                $role = $p['Statut'];
                $confidentiel = $p['Confidentiel']; ?>
                

                    <div class='projet-card' onclick="location.href='page_projet.php?id_projet=<?= $id ?>'">
                        <h3><?= $nom ?></h3>
                        <p><?= $desc ?></p>
                    <?php if ($confidentiel) :?>
                        <span class="conf-badge"></span>
                    <?php endif; ?>
                        
                        <?php echo afficher_barre_progression($progression['finies'], $progression['total']); ?>
                    <div class="projet-details">
                        <p><strong>Date de création :</strong> <?= $date ?></p>
                        <p><strong>Rôle :</strong> <?= $role ?></p>
                        <?php if (est_admin($bdd, $_SESSION["email"]) && $page_admin): ?>
                            <!-- Bouton Modifier -->
                            <form action="page_modification_projet.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id_projet" value="<?= $id ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button class="btn btnViolet" type="submit">Modifier</button>
                            </form>

                            <!-- Bouton Supprimer -->
                            <form action="page_admin_projets.php" method="POST" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce projet ?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button class="btn btnRouge" type="submit" onclick="event.stopPropagation()">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Récupère les IDs des expérimentateurs associés à une expérience donnée.
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $id_experience ID de l'expérience
 * @return array Tableau d'IDs (int) des comptes expérimentateurs
 */
function get_experimentateurs_ids(PDO $bdd, int $id_experience): array {
    $sql = "
        SELECT ee.ID_compte
        FROM experience_experimentateur ee
        WHERE ee.ID_experience = :id_experience
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


//Fonction permettant d'afficher les résultats en remplacant les balises par les fichiers correspondants
/**
 * Transforme un texte contenant des placeholders de fichiers en HTML prêt à afficher.
 *
 * Remplace les placeholders [[file:nom_du_fichier]] par des balises <img> correspondantes.
 * Les chemins sont sécurisés et convertis pour affichage web.
 * Les sauts de ligne sont convertis en <br>.
 *
 * @param string $text Le texte brut contenant éventuellement des placeholders [[file:xxx]]
 * @param int $id_experience L'identifiant de l'expérience, utilisé pour localiser les fichiers
 * @return string HTML sécurisé avec les images insérées à la place des placeholders
 */
function afficher_resultats(string $text, int $id_experience) :string{

    $uploadDir = "../assets/resultats/" . $id_experience . "/";
    $webUploadDir = "../assets/resultats/" . $id_experience . "/"; // chemin relatif pour <img src=>

    // Sécuriser le texte et conserver les sauts de ligne
    $successHtml = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $successHtml = nl2br($successHtml);

    // Remplacer les placeholders [[file:xxx]] par les images correspondantes
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

    return $successHtml;
}


/**
 * Récupère la liste des personnes disponibles pour être ajoutées à un projet.
 *
 * Fonctionnement :
 *  - Sélectionne tous les comptes validés
 *  - Peut exclure certains IDs (gestionnaires ou collaborateurs déjà présents)
 *  - Peut filtrer pour n’inclure que les non-étudiants (Etat > 1)
 *  - Trie par nom puis prénom
 *
 * Retourne :
 *   - Un tableau de comptes disponibles sous forme associative
 *
 * @param PDO   $bdd Connexion PDO
 * @param array $ids_exclus Liste d’IDs à exclure du résultat
 * @param bool  $seulement_non_etudiants Si true → filtre Etat > 1
 * @return array
 */
function get_personnes_disponibles(PDO $bdd, array $ids_exclus = [], bool $seulement_non_etudiants = false): array {
    $sql = "SELECT ID_compte, Nom, Prenom, Etat, Email FROM compte WHERE validation = 1";

    if ($seulement_non_etudiants) {
        $sql .= " AND Etat > 1";
    }

    if (!empty($ids_exclus)) {
        $placeholders = implode(',', array_fill(0, count($ids_exclus), '?'));
        $sql .= " AND ID_compte NOT IN ($placeholders)";
    }

    $sql .= " ORDER BY Nom, Prenom";

    $stmt = $bdd->prepare($sql);
    $stmt->execute($ids_exclus);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Trouve l'ID d'un compte à partir d'une chaîne contenant un email
 * Format attendu : "Prénom Nom (Rôle) — email@domaine"
 *
 * @param PDO $bdd
 * @param string $email
 * @return int|null
 */
function trouver_id_par_email(PDO $bdd, string $email): ?int {

    // Extraction de l'email
    if (!preg_match(
        '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,})/i',
        $email,
        $match
    )) {
        return null;
    }

    $mail = strtolower($match[1]);

    $stmt = $bdd->prepare("
        SELECT ID_compte
        FROM compte
        WHERE Email = ?
          AND validation = 1
        LIMIT 1
    ");
    $stmt->execute([$mail]);

    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}


// =======================  FONCTION POPUP =======================
/**
 * Génère le HTML d'une popup d'information ou d'erreur.
 *
 * @param string $titre Le titre affiché dans la popup
 * @param string $texte Le texte ou message affiché
 * @param string $type Type de popup : 'success' ou 'error' (défaut 'success')
 * @param string $page Page de redirection lors de la fermeture de la popup (nom sans .php)
 * @return string HTML complet de la popup
 */
function afficher_popup(string $titre,string $texte, string $type = "success", string $page) :string{
    $classe = ($type === "error") ? "popup-error" : "popup-success";
    return '
    <div class="popup-overlay" id="popup">
        <div class="popup-box ' . $classe . '">
            <h3>' . htmlspecialchars($titre) . '</h3>
            <p>' . htmlspecialchars($texte) . '</p>
            <a href="'.$page.'.php" class="popup-close">Fermer</a>
        </div>
    </div>';
}

/**
 * Vérifie si deux mots de passe sont identiques.
 *
 * @param string $mdp1 Le premier mot de passe
 * @param string $mdp2 Le mot de passe de confirmation
 * @return bool Retourne true si les mots de passe sont identiques, false sinon
 */
function mot_de_passe_identique(string $mdp1, string $mdp2) :bool{
    return $mdp1 === $mdp2;
}

/**
 * Cette fonction permet de vérifier si une expérience est confidentielle
 *
 * @param PDO $bdd permet d'établir la connexion avec la base de données
 * @param int $id_experience Id de l'expérience
 * @return bool Renvoie true si l'experience est confidentiel
 */
function experience_confidentiel(PDO $bdd, int $id_experience) :bool{
    $stmt = $bdd->prepare("
    SELECT Confidentiel FROM experience INNER JOIN projet_experience
	    ON experience.ID_experience = projet_experience.ID_experience
        INNER JOIN projet 
        ON projet_experience.ID_projet = projet.ID_projet
        WHERE experience.ID_experience= ?");
    $stmt->execute([$id_experience]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result && (bool)$result['Confidentiel'];
}

/**
 * Vérifie si un compte est gestionnaire d’un projet.
 *
 * Effectue la vérification suivante :
 *  - Recherche dans la table projet_collaborateur_gestionnaire si l'utilisateur
 *    possède le statut 1 (gestionnaire) pour le projet donné.
 *
 * Retourne :
 *   - true  : si l'utilisateur est gestionnaire
 *   - false : sinon
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte à vérifier
 * @param int $id_projet ID du projet concerné
 * @return bool
 */

function est_gestionnaire(PDO $bdd, int $id_compte, int $id_projet): bool{
    $sql = "SELECT Statut FROM projet_collaborateur_gestionnaire 
            WHERE ID_projet = :id_projet AND ID_compte = :id_compte AND Statut = 1";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet, 'id_compte' => $id_compte]);
    return $stmt->fetch() !== false;
}



// ======================= CSRF =======================

/**
 * Génère ou récupère un token CSRF unique pour la session.
 *
 * Fonctionnement :
 *  - Vérifie si un token CSRF est déjà stocké dans la session.
 *  - Si aucun token n'existe, en crée un nouveau via `random_bytes` et le stocke.
 *  - Retourne le token actuel pour l'inclure dans les formulaires.
 *
 * @return string Token CSRF de 64 caractères hexadécimaux
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité d'un token CSRF envoyé via POST.
 *
 * Fonctionnement :
 *  - Vérifie que le token CSRF est présent dans $_POST et dans la session.
 *  - Compare les deux tokens avec `hash_equals` pour éviter les attaques par timing.
 *  - Si la vérification échoue, renvoie une erreur 403 et stoppe l'exécution.
 *
 * @throws 403 si le token CSRF est absent ou invalide
 */
function check_csrf() {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("Action non autorisée (CSRF détecté)");
    }
}

?>