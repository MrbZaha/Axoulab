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
    ?>

    <nav class="site_nav">
        <div id="site_nav_main">
            <a href="Main_page_connected.php" class="lab_logo">
                <img src="../assets/logo_labo.png" alt="Logo_labo">
            </a>
            <div class="searchbar">
                <input type="text" name="q" placeholder="Rechercher..." />
                <span class="searchbar-icon"><i class="fas fa-search"></i></span>
            </div>
        </div>

        <div id="site_nav_links">
            <ul class="liste_links">
                <li class="main_links">
                    <a href="page_explorer.php" class="Links">Explorer</a>
                </li>
                <li class="main_links">
                    <a href="page_mes_experiences.php" class="Links">Mes expériences</a>
                </li>
                <li class="main_links">
                    <a href="page_mes_projets.php" class="Links">Mes projets</a>
                </li>

                <!-- Icône notification -->
                <li id="Notif">
                    <!-- Icone notification -->
                    <label for="notif_toggle" class="notif_logo">
                        <img src="../assets/Notification_logo.png" alt="Notification">
                    </label>

                    <!-- Checkbox pour toggle -->
                    <input type="checkbox" id="notif_toggle" hidden>

                    <!-- Overlay notifications -->
                    <div class="overlay">
                        <?php
                        $notifications = get_last_notif($bdd, $userID);
                        if (empty($notifications)) {
                            echo "<p>Aucune notification pour le moment.</p>";
                        } else {
                            foreach ($notifications as $notif):
                             ?>
                                <a class="notif_case" href="<?= htmlspecialchars($notif['link']) ?>">
                                    <?= htmlspecialchars($notif['texte']) ?><br>
                                    <small><?= htmlspecialchars($notif['date']) ?></small>
                            </a>
                        <?php endforeach; } ?>

                        <label for="notif_toggle" class="close_overlay">Fermer</label>
                    </div>
                </li>

                <!-- Icône utilisateur -->
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
            NULL AS Nom_experience,
            p.ID_projet,
            NULL AS ID_experience
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
            e.Nom AS Nom_experience,
            NULL AS ID_projet,
            e.ID_experience
        FROM notification_experience AS ne
        JOIN experience AS e ON ne.ID_experience = e.ID_experience
        JOIN compte AS Ce ON ne.ID_compte_envoyeur = Ce.ID_compte
        WHERE ne.ID_compte_receveur = ?
    ");
    $notif_experience->execute([$IDuser]);

    // Fusion
    $tab_notifications = array_merge(
        $notif_projet->fetchAll(PDO::FETCH_ASSOC),
        $notif_experience->fetchAll(PDO::FETCH_ASSOC)
    );

    // Tri par date décroissante
    usort($tab_notifications, function($a, $b) {
        return strtotime($b['Date_envoi']) - strtotime($a['Date_envoi']);
    });

    // Limite au nombre demandé
    $notifications = array_slice($tab_notifications, 0, $limit);

    // Textes
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
        'type15' => '{Nom_projet} a été modifié par {Nom_envoyeur} {Prenom_envoyeur}'
    ];

    // Construction finale
    $result = [];

    foreach ($notifications as $notif) {

        $type = 'type'.$notif['Type_notif'];
        $texte = $texte_notifications[$type] ?? 'Notification inconnue';

        // Construction du lien
        if ($notif['Type_notif'] >= 1 && $notif['Type_notif'] <= 5) {
            // Expériences
            $link = "experience.php?id_projet=".$notif['ID_projet']."&id_experience=".$notif['ID_experience'];
            $texte = str_replace(
                ['{Nom_envoyeur}', '{Prenom_envoyeur}', '{Nom_experience}'],
                [$notif['Nom_envoyeur'], $notif['Prenom_envoyeur'], $notif['Nom_experience']],
                $texte
            );

        } elseif ($notif['Type_notif'] >= 11 && $notif['Type_notif'] <= 15) {
            // Projets
            $link = "projet.php?id_projet=".$notif['ID_projet'];
            $texte = str_replace(
                ['{Nom_envoyeur}', '{Prenom_envoyeur}', '{Nom_projet}'],
                [$notif['Nom_envoyeur'], $notif['Prenom_envoyeur'], $notif['Nom_projet']],
                $texte
            );
        } else {
            $link = "#"; // fallback
        }

        $result[] = [
            'texte' => $texte,
            'date'  => $notif['Date_envoi'],
            'link'  => $link
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
function get_mes_experiences_complets(PDO $bdd, int $id_compte): array {
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
            s.Salle,
            p.Nom_projet,
            p.ID_projet
        FROM experience e
        LEFT JOIN projet_experience pe
            ON pe.ID_experience = e.ID_experience
        LEFT JOIN projet p
            ON p.ID_projet = pe.ID_projet
        INNER JOIN experience_experimentateur ee
            ON e.ID_experience = ee.ID_experience
        LEFT JOIN salle_experience se
            ON e.ID_experience = se.ID_experience
        LEFT JOIN salle_materiel s
            ON se.ID_salle = s.ID_salle
        WHERE ee.ID_compte = :id_compte
    ";

    $stmt = $bdd->prepare($sql_experiences);
    $stmt->execute(['id_compte' => $id_compte]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($experiences)) {
        return [];
    }
    return $experiences;    
}

// =======================  Gère le nombre de pages qui devront être créées =======================
function create_page(array $items, int $items_par_page = 6): int {
    $total_items = count($items);
    if ($total_items == 0) {
        return 1;
    }
    return (int)ceil($total_items / $items_par_page);
}

// =======================  affichage des expériences sur plusieurs pages =======================
function afficher_experiences_pagines(array $experiences, int $page_actuelle = 1, int $items_par_page = 6): void {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $experiences_page = array_slice($experiences, $debut, $items_par_page);
    
    ?>
    <div class="liste">
        <?php if (empty($experiences_page)): ?>
            <p class="no-experiences">Aucune expérience à afficher</p>
        <?php else: ?>
            <?php foreach ($experiences_page as $exp): ?>
                <?php 
                $id_experience = htmlspecialchars($exp['ID_experience']);
                $nom = htmlspecialchars($exp['Nom']);
                $description = $exp['Description'];
                $desc = strlen($description) > 200 
                    ? htmlspecialchars(substr($description, 0, 200)) . '…'
                    : htmlspecialchars($description);    
                $date_reservation = htmlspecialchars($exp['Date_reservation']);
                $heure_debut = htmlspecialchars($exp['Heure_debut']);
                $heure_fin = htmlspecialchars($exp['Heure_fin']);
                $salle = htmlspecialchars($exp['Salle'] ?? 'Non définie');
                $nom_projet = htmlspecialchars($exp['Nom_projet'] ?? 'Sans projet');
                $id_projet = htmlspecialchars($exp['ID_projet']);
                ?>
                
                <a class='experience-card' href='page_experience.php?id_projet=<?= $id_projet ?>&id_experience=<?= $id_experience ?>'>
                    <div class="experience-header">
                        <h3><?= $nom ?></h3>
                        <span class="projet-badge"><?= $nom_projet ?></span>
                    </div>
                    <p class="description"><?= $desc ?></p>
                    <div class="experience-details">
                        <p><strong>Date :</strong> <?= $date_reservation ?></p>
                        <p><strong>Horaires :</strong> <?= $heure_debut ?> - <?= $heure_fin ?></p>
                        <p><strong>Salle :</strong> <?= $salle ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function afficher_pagination(int $page_actuelle, int $total_pages, string $type = 'a_venir'): void {
    if ($total_pages <= 1) return;
    
    // Préserver l'autre paramètre de page dans l'URL
    $autre_type = ($type === 'a_venir') ? 'terminees' : 'a_venir';
    $autre_page = isset($_GET["page_$autre_type"]) ? (int)$_GET["page_$autre_type"] : 1;
    
    ?>
    <div class="pagination">
        <?php if ($page_actuelle > 1): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle - 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">« Précédent</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page_actuelle): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page_<?= $type ?>=<?= $i ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page_actuelle < $total_pages): ?>
            <a href="?page_<?= $type ?>=<?= $page_actuelle + 1 ?>&page_<?= $autre_type ?>=<?= $autre_page ?>" class="page-btn">Suivant »</a>
        <?php endif; ?>
    </div>
    <?php
}
?>