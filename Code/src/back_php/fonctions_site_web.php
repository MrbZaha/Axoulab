<?php
// ======================= FICHIER DE FONCTIONS =======================
// Ce fichier contient toutes les fonctions réutilisables pour les pages
// inscription et connexion du projet de cahier de laboratoire.
// L'objectif est de centraliser le code pour qu'il soit plus propre,
// facile à maintenir et réutilisable sur plusieurs pages.

// ======================= 1. CONNEXION À LA BASE DE DONNÉES =======================
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

// ======================= 2. VÉRIFICATION FORCE DU MOT DE PASSE =======================
/* Vérifie que le mot de passe respecte les règles de sécurité :
   - Au moins 8 caractères
   - Au moins une majuscule
   - Au moins une minuscule
   - Au moins un chiffre
   - Au moins un caractère spécial
   Retourne un tableau d'erreurs (vide si mot de passe valide) */
function verifier_mdp($mdp) {
    $erreurs = [];
    if (strlen($mdp) < 8) $erreurs[] = "au moins 8 caractères";
    if (!preg_match('/[A-Z]/', $mdp)) $erreurs[] = "au moins une majuscule";
    if (!preg_match('/[a-z]/', $mdp)) $erreurs[] = "au moins une minuscule";
    if (!preg_match('/[0-9]/', $mdp)) $erreurs[] = "au moins un chiffre";
    if (!preg_match('/[\W_]/', $mdp)) $erreurs[] = "au moins un caractère spécial (!@#$%^&*...)";
    return $erreurs;
}

// ======================= 3. VÉRIFICATION EMAIL EXISTANT =======================
/* Vérifie si une adresse email existe déjà dans la base de données
   Retourne true si l'email existe, false sinon */
function email_existe($bdd, $email) {
    $stmt = $bdd->prepare("SELECT * FROM compte WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// ======================= 4. COMPARAISON DE MOT DE PASSE =======================
/* Vérifie si deux mots de passe sont identiques (mot de passe et "réécrire mot de passe") */
function mot_de_passe_identique($mdp1, $mdp2) {
    return $mdp1 === $mdp2;
}

// ======================= 5. VALIDATION EMAIL AXOULAB =======================
/* Vérifie que l'email est au format prenom.nom@axoulab.fr
   Retourne true si le format est correct, false sinon */
function verifier_email_axoulab($email) {
    return preg_match('/^[a-zA-Z]+\.[a-zA-Z]+@axoulab\.fr$/', $email);
}

// ======================= 6. INSÉRER UN UTILISATEUR =======================
/* Insère un nouvel utilisateur dans la base de données
   Retourne true si insertion réussie, false sinon */
function inserer_utilisateur($bdd, $nom, $prenom, $date, $etat, $email, $mdp_hash) {
    $sql = $bdd->prepare("INSERT INTO compte (Nom, Prenom, date_de_naissance, etat, email, mdp) VALUES (?, ?, ?, ?, ?, ?)");
    return $sql->execute([$nom, $prenom, $date, $etat, $email, $mdp_hash]);
}

// ======================= 7. NOTIFICATION ADMIN =======================
/* Fonction pour envoyer une notification à l'administrateur lors
   d'une nouvelle inscription. Ici c'est un exemple, tu peux utiliser mail() ou autre */
function envoyer_notification_admin($email, $nom, $prenom) {
    // Exemple : on pourrait utiliser mail() pour notifier l'admin
    // mail("admin@axoulab.fr", "Nouvel utilisateur", "Nouvel utilisateur inscrit : $prenom $nom ($email)");
}

// ======================= 8. MOT DE PASSE CORRECT =======================
/* Vérifie si le mot de passe saisi correspond au hash stocké en base
   Retourne true si mot de passe correct, false sinon */
function mot_de_passe_correct($bdd, $email, $mdp) {
    $stmt = $bdd->prepare("SELECT mdp FROM compte WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();       # Récupère un tableau avec l'ensemble des attributs de l'utilisateur
        return $user["mdp"] === $mdp; #ensuite mettre password_verify($mdp, $user["mdp"]);
    }
    return false; // Email inexistant
}

// ======================= 9. CONNEXION VALIDE =======================
/* Vérifie si un utilisateur peut se connecter (email + mot de passe corrects)
   Retourne true si connexion possible, false sinon */
function connexion_valide($bdd, $email, $mdp) {
    return email_existe($bdd, $email) && mot_de_passe_correct($bdd, $email, $mdp);
}

// ======================= 10. RÉCUPÉRER ID COMPTE =======================
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

// ======================= 11. AFFICHAGE BANDEAU DU HAUT =======================
/* Affiche le Bandeau du haut */

function afficher_Bandeau_Haut($bdd, $userID) {
    ?>

    <!-- Checkbox caché pour l’overlay notifications -->
    <input type="checkbox" id="notif_toggle" hidden>

    <nav class="site_nav">
        <div id="site_nav_main">
            <a class="lab_logo">
                <img src="../assets/logo_labo.png" alt="Logo_labo">
            </a>
            <div class="searchbar">
                <!-- <form action="/search" method="GET"> -->
                    <input type="text" name="q" placeholder="Rechercher..." />
                    <span class="searchbar-icon"><i class="fas fa-search"></i></span>
                <!-- </form>         -->
            </div>        
        </div>
        <div id="site_nav_links">
            <ul class="liste_links">
                <li class="main_links">
                    <a href="Page_explorer.php" class="Links">Explorer</a>
                </li>
                <li class="main_links">
                    <a href="mes_experiences.php" class="Links">Mes expériences</a>
                </li>
                <li class="main_links">
                    <a href="mes_projets.php" class="Links">Mes projets</a>
                </li>
                <!-- Icone notification qui agit comme un label -->
                <li id="Notif">
                    <label for="notif_toggle" class="notif_logo">
                        <img src="..\assets\Notification_logo.png">
                    </label>
                </li>
                <li id="User">
                    <a href="mon_profil.php" class="user_logo">
                        <?php
                        $path = "../assets/profile_pictures/" . $userID . ".jpg";
                        if (!file_exists($path)) {
                            $path = "../assets/profile_pictures/model.jpg";
                        }
                        ?>
                        <img src="<?= $path ?>" alt="Photo de profil">
                    </a>
                </li>
            </ul>
        </div>
    </nav>

        <!-- Overlay notifications -->
    <div class="overlay">
        <?php
        $notifications = get_last_notif($bdd, $userID);

        if (empty($notifications)) {
            echo "<p>Aucune notification pour le moment.</p>";
        } else {
            foreach ($notifications as $notif):
                $texte = $notif['texte'];
        ?>
                <div class="notif_case">
                    <?= htmlspecialchars($texte) ?><br>
                    <small><?= htmlspecialchars($notif['date']) ?></small>
                </div>
        <?php
            endforeach;
        }
        ?>
        <label for="notif_toggle" class="close_overlay">Fermer</label>
    </div>


    <?php
}



// ======================= 11. VÉRIFIER SI ADMIN =======================
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

// ======================= 12. VÉRIFIER SI COMPTE EN COURS DE VALIDATION =======================
/* Vérifie si le compte est en cours de validation
   Retourne true si validation ,  false sinon */
function en_cours_validation($bdd, $email) {
    $stmt = $bdd->prepare("SELECT validation, etat FROM compte WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();

        // Compte en cours de validation si :
        // - validation = false (0)
        // - ou etat = 0
        return ($user["validation"] == 0 || $user["etat"] == 0);
    }

    return false; // si aucun compte trouvé
}



// ======================= 13. RÉCUPERER LES DERNIERE NOTIFICATIONS =======================
/* Récupère les données relatives aux notification et les  */

function get_last_notif($bdd, $IDuser, $limit = 4) {

    // Notifications projets
    $notif_projet = $bdd->prepare("
        SELECT 
            Ce.Nom AS Nom_envoyeur, 
            Ce.Prenom AS Prenom_envoyeur,
            np.Type_notif, 
            np.Date_envoi, 
            p.Nom_projet,
            NULL AS Nom_experience
        FROM notification_projet AS np
        JOIN projet AS p ON np.ID_projet = p.ID_projet
        JOIN compte AS Ce ON np.ID_compte_envoyeur = Ce.ID_compte
        WHERE np.ID_compte_receveur = ?
    ");
    $notif_projet->execute([$IDuser]);

    // Notifications expériences
    $notif_experience = $bdd->prepare("
        SELECT 
            Ce.Nom AS Nom_envoyeur, 
            Ce.Prenom AS Prenom_envoyeur,
            ne.Type_notif, 
            ne.Date_envoi, 
            NULL AS Nom_projet,
            e.Nom
        FROM notification_experience AS ne
        JOIN experience AS e ON ne.ID_experience = e.ID_experience
        JOIN compte AS Ce ON ne.ID_compte_envoyeur = Ce.ID_compte
        WHERE ne.ID_compte_receveur = ?
    ");
    $notif_experience->execute([$IDuser]);

    $tab_projets = $notif_projet->fetchAll(PDO::FETCH_ASSOC);
    $tab_experiences = $notif_experience->fetchAll(PDO::FETCH_ASSOC);

    $tab_notifications = array_merge($tab_projets, $tab_experiences);

    usort($tab_notifications, function($a, $b) {
        return strtotime($b['Date_envoi']) - strtotime($a['Date_envoi']);
    });

    $notifications = array_slice($tab_notifications, 0, $limit);

    // Tableau des textes
    $texte_notifications = [
        'type1'  => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé de créer l\'expérience {Nom_experience}',
        'type2'  => '{Nom_envoyeur} {Prenom_envoyeur} a validé l\'experience {Nom_experience}',
        'type3'  => '{Nom_envoyeur} {Prenom_envoyeur} a refusé l\'experience {Nom_experience}',
        'type4'  => '{Nom_envoyeur} {Prenom_envoyeur} vous a invité à modifier l\'experience {Nom_experience}',
        'type5'  => '{Nom_experience} a été modifiée par {Nom_envoyeur} {Prenom_envoyeur}',
        'type11' => '{Nom_envoyeur} {Prenom_envoyeur} vous a proposé de créer le projet {Nom_projet}',
        'type12' => '{Nom_envoyeur} {Prenom_envoyeur} a validé le projet {Nom_projet}',
        'type13' => '{Nom_envoyeur} {Prenom_envoyeur} a refusé le projet {Nom_projet}',
        'type14' => '{Nom_envoyeur} {Prenom_envoyeur} vous a invité à modifier le projet {Nom_projet}',
        'type15' => '{Nom_projet} a été modifiée par {Nom_envoyeur} {Prenom_envoyeur}',
    ];

    // Formater toutes les notifications
    $result = [];
    foreach ($notifications as $notif) {
        $texte = $texte_notifications['type'.$notif['Type_notif']] ?? 'Notification inconnue';
        echo('type'.$notif['Type_notif']);
        if ($notif['Type_notif'] >= 1 && $notif['Type_notif'] <= 5) {
            $texte = str_replace(
                ['{Nom_envoyeur}','{Prenom_envoyeur}','{Nom_experience}'],
                [$notif['Nom_envoyeur'],$notif['Prenom_envoyeur'],$notif['Nom']],
                $texte
            );
        } elseif ($notif['Type_notif'] >= 11 && $notif['Type_notif'] <= 15) {
            $texte = str_replace(
                ['{Nom_envoyeur}','{Prenom_envoyeur}','{Nom_projet}'],
                [$notif['Nom_envoyeur'],$notif['Prenom_envoyeur'],$notif['Nom_projet']],
                $texte
            );
        }

        $result[] = [
            'texte' => $texte,
            'date'  => $notif['Date_envoi']
        ];
    }

    return $result;
}





// ======================= 14. Affichage bandeau du bas =======================
/* Affiche le bandeau du bas de page  */ 
function afficher_Bandeau_Bas() { ?>
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
<?php } ?>

