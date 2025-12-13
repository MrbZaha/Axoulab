<?php
// --- En-tête : includes et connexion BDD ---
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();
verification_connexion($bdd);

$id_compte = $_SESSION['ID_compte'];
$id_projet = isset($_GET['id_projet']) ? (int)$_GET['id_projet'] : 0;

$erreur = null;

if ($id_projet === 0) {
    $erreur = "ID de projet manquant.";
}

/**
 * Vérifie si l'utilisateur a le droit d'accéder au projet.
 *
 * Un projet est accessible si :
 *  - l'utilisateur est un administrateur (Etat = 3)
 *  - OU le projet n'est PAS confidentiel
 *  - OU l'utilisateur est gestionnaire du projet (Statut = 1)
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte utilisateur
 * @param int $id_projet ID du projet à vérifier
 * @return bool true si accès autorisé, false sinon
 */
function verifier_confidentialite(PDO $bdd, int $id_compte, int $id_projet): bool {
    // Vérifier si l'utilisateur est admin
    $stmt_admin = $bdd->prepare("SELECT Etat FROM compte WHERE ID_compte = ?");
    $stmt_admin->execute([$id_compte]);
    $etat = $stmt_admin->fetchColumn();
    if ((int)$etat === 3) return true; // Admin

    // Vérifier projet et droits
    $stmt = $bdd->prepare("
        SELECT p.Confidentiel, pcg.Statut
        FROM projet p
        LEFT JOIN projet_collaborateur_gestionnaire pcg
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = ?
        WHERE p.ID_projet = ?
    ");
    $stmt->execute([$id_compte, $id_projet]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) return false; // Projet inexistant

    // Projet non confidentiel → tout le monde peut accéder
    if ((int)$result['Confidentiel'] === 0) return true;

    // Projet confidentiel → accès seulement si Statut = 0 (collab) ou 1 (gest.)
    if (!isset($result['Statut'])) return false; // Non trouvé → pas d'accès
    $statut = (int)$result['Statut'];
    return in_array($statut, [0, 1], true);
}


/**
 * Récupère les information d'un projet
 *
 * @param PDO $bdd : Connexion à la base de données
 * @param int $id_compte : ID de l'expérience
 * @param int $id_projet : ID_projet
 * @return array : un tableau contenant les informations du projet, ou null dans le cas où il n'y a pas de projet
 */
function get_info_projet(PDO $bdd, int $id_compte, int $id_projet) {
    if (!verifier_confidentialite($bdd, $id_compte, $id_projet)) {
        return null;
    }

    $sql_projet = "
        SELECT 
            p.ID_projet, 
            p.Nom_projet, 
            p.Description, 
            p.Confidentiel, 
            p.Validation, 
            pcg.Statut,
            p.Date_de_creation
        FROM projet p
        LEFT JOIN projet_collaborateur_gestionnaire pcg
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE p.ID_projet = :id_projet
    ";

    $stmt = $bdd->prepare($sql_projet);
    $stmt->execute([
        'id_compte' => $id_compte,
        'id_projet' => $id_projet
    ]);

    $projet = $stmt->fetch(PDO::FETCH_ASSOC);

    return $projet ?: null;
}

/**
 * Récupère les noms et prénoms des gestionnaires du projet
 *
 * @param PDO $bdd : Connexion à la base de données
 * @param int $id_projet : ID_projet
 * @return array : un tableau contenant les Nom et Prénoms des gestionnaires du projet
 */
function get_gestionnaires(PDO $bdd, int $id_projet): array {
    $sql = "
        SELECT c.Nom, c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.ID_projet = :id_projet AND pcg.Statut = 1
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    $gestionnaires = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gestionnaires[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $gestionnaires;
}

/**
 * Récupère les noms et prénoms des collaborateurs du projet
 *
 * @param PDO $bdd : Connexion à la base de données
 * @param int $id_projet : ID_projet
 * @return array : un tableau contenant les Nom et Prénoms des collaborateurs du projet
 */
function get_collaborateurs(PDO $bdd, int $id_projet): array {
    $sql = "
        SELECT c.Nom, c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.ID_projet = :id_projet AND pcg.Statut = 0
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);
    
    $collaborateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $collaborateurs[] = $row['Prenom'] . ' ' . $row['Nom'];
    }
    
    return $collaborateurs;
}

/**
 * Récupère les informations des expériences du projet
 *
 * @param PDO $bdd : Connexion à la base de données
 * @param int $id_projet : ID_projet
 * @return array : un tableau contenant les informations des expériences du projet
 */
function get_experiences(PDO $bdd, int $id_projet): array {
    $sql = "
        SELECT DISTINCT
            e.ID_experience,
            e.Description,
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Validation,
            e.Resultat,
            e.Nom,
            e.Statut_experience,
            sm.Nom_salle
        FROM experience e
        INNER JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        LEFT JOIN materiel_experience me ON e.ID_experience = me.ID_experience
        LEFT JOIN salle_materiel sm ON me.ID_materiel = sm.ID_materiel
        WHERE pe.ID_projet = :id_projet
        ORDER BY e.Date_reservation DESC, e.Heure_debut DESC
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_projet' => $id_projet]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Permet de charger les données du projet
 *
 * @param PDO $bdd : Connexion à la base de données
 * @param int $id_compte : ID_compte
 * @param int $id_projet : ID_projet
 * @return array Tableau associatif contenant :
 *               - 'erreur' (string|null) : Message d'erreur si le projet n'existe pas ou n'est pas accessible, null sinon
 *               - 'projet' (array|null) : Informations du projet (ID, nom, description, etc.) ou null en cas d'erreur
 *               - 'gestionnaires' (array) : Liste des gestionnaires du projet (tableau de chaînes "Prénom Nom")
 *               - 'collaborateurs' (array) : Liste des collaborateurs du projet (tableau de chaînes "Prénom Nom")
 *               - 'experiences' (array) : Liste des expériences liées au projet (tableau associatif)
 */
function charger_donnees_projet(PDO $bdd, int $id_compte, int $id_projet): array {
    $sql_check = "SELECT ID_projet FROM projet WHERE ID_projet = :id_projet";
    $stmt = $bdd->prepare($sql_check);
    $stmt->execute(['id_projet' => $id_projet]);
    
    if (!$stmt->fetch()) {
        return [
            'erreur' => "Désolé, ce projet n'existe pas.",
            'projet' => null,
            'gestionnaires' => [],
            'collaborateurs' => [],
            'experiences' => []
        ];
    }
    
    $projet = get_info_projet($bdd, $id_compte, $id_projet);
    
    if ($projet === null) {
        return [
            'erreur' => "Il s'agit d'un projet confidentiel auquel vous n'avez pas accès.",
            'projet' => null,
            'gestionnaires' => [],
            'collaborateurs' => [],
            'experiences' => []
        ];
    }
    
    return [
        'erreur' => null,
        'projet' => $projet,
        'gestionnaires' => get_gestionnaires($bdd, $id_projet),
        'collaborateurs' => get_collaborateurs($bdd, $id_projet),
        'experiences' => get_experiences($bdd, $id_projet)
    ];
}

/**
 * Affiche les informations complètes d'un projet avec ses gestionnaires, 
 * collaborateurs et expériences dans une mise en page structurée.
 *
 * @param array $projet Informations du projet contenant :
 *                      - 'Nom_projet' (string) : Nom du projet
 *                      - 'Description' (string) : Description détaillée
 *                      - 'Date_de_creation' (string) : Date de création
 *                      - 'Validation' (int) : Statut de validation (0 = en attente, 1 = validé)
 *                      - 'Confidentiel' (int) : Indicateur de confidentialité (0 = non, 1 = oui)
 * @param array $gestionnaires Liste des gestionnaires du projet (tableau de chaînes "Prénom Nom")
 * @param array $collaborateurs Liste des collaborateurs du projet (tableau de chaînes "Prénom Nom")
 * @param array $experiences Liste des expériences liées au projet. Chaque expérience contient :
 *                          - 'ID_experience' (int) : Identifiant de l'expérience
 *                          - 'Nom' (string) : Nom de l'expérience
 *                          - 'Description' (string) : Description de l'expérience
 *                          - 'Date_reservation' (string) : Date de l'expérience
 *                          - 'Heure_debut' (string) : Heure de début
 *                          - 'Heure_fin' (string) : Heure de fin
 *                          - 'Statut_experience' (int) : Statut (0 = en attente, 1 = en cours, 2 = terminée)
 *                          - 'Nom_salle' (string|null) : Nom de la salle (optionnel)
 *                          - 'Resultat' (string|null) : Résultat de l'expérience (optionnel)
 * @return void Affiche directement le HTML du projet
 */
function afficher_projet(array $projet, array $gestionnaires, array $collaborateurs, $experiences): void {
    ?>
    <div class="projet-container">
        <!-- En-tête du projet -->
        <div class="projet-header">
            <h2><?= htmlspecialchars($projet['Nom_projet']) ?></h2>
            <div class="projet-badges">
                <?php if ($projet['Validation'] == 1): ?>
                    <span class="badge valide">Validé</span>
                <?php elseif ($projet['Validation'] === 2): ?>
                    <span class="badge refuse">Refusé</span>
                <?php else: ?>
                    <span class="badge en-attente">En attente</span>
                <?php endif; ?>
                
                <?php if ($projet['Confidentiel'] == 1): ?>
                    <span class="badge confidentiel">Confidentiel</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Layout principal : Description + Infos -->
        <div class="project-main">
            <!-- Description -->
            <div class="project-description">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($projet['Description'])) ?></p>
                <p class="date-creation"><strong>Créé le :</strong> <?= htmlspecialchars($projet['Date_de_creation']) ?></p>
            </div>

            <!-- Informations équipe -->
            <div class="project-info">
                <h3>Équipe</h3>
                
                <h4><i class="fas fa-user-tie"></i> Gestionnaires</h4>
                <?php if (empty($gestionnaires)): ?>
                    <p class="vide">Aucun gestionnaire</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($gestionnaires as $gestionnaire): ?>
                            <li><?= htmlspecialchars($gestionnaire) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h4><i class="fas fa-users"></i> Collaborateurs</h4>
                <?php if (empty($collaborateurs)): ?>
                    <p class="vide">Aucun collaborateur</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($collaborateurs as $collaborateur): ?>
                            <li><?= htmlspecialchars($collaborateur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section Expériences -->
        <div class="experiences">
            <h3><i class="fas fa-flask"></i> Expériences du projet</h3>
            
            <?php if (empty($experiences)): ?>
                <p class="vide">Aucune expérience pour le moment</p>
            <?php else: ?>
                <?php foreach ($experiences as $exp): 
                    $statut_class = '';
                    $statut_text = '';
                    
                    switch ((int)$exp['Statut_experience']) {
                        case 0:
                            $statut_class = 'en-attente';
                            $statut_text = 'En attente';
                            break;
                        case 1:
                            $statut_class = 'en-cours';
                            $statut_text = 'En cours';
                            break;
                        case 2:
                            $statut_class = 'termine';
                            $statut_text = 'Terminée';
                            break;
                    }
                ?>
                    <div class="experience-card">
                        <div class="experience-header">
                            <h4><?= htmlspecialchars($exp['Nom']) ?></h4>
                            <span class="badge <?= $statut_class ?>"><?= $statut_text ?></span>
                        </div>
                        
                        <p class="experience-description"><?= nl2br(htmlspecialchars($exp['Description'])) ?></p>
                        
                        <div class="experience-details">
                            <p><i class="fas fa-calendar"></i> <?= htmlspecialchars($exp['Date_reservation']) ?></p>
                            <p><i class="fas fa-clock"></i> <?= htmlspecialchars($exp['Heure_debut']) ?> - <?= htmlspecialchars($exp['Heure_fin']) ?></p>
                            <?php if (!empty($exp['Nom_salle'])): ?>
                                <p><i class="fas fa-door-open"></i> <?= htmlspecialchars($exp['Nom_salle']) ?></p>
                            <?php endif; ?>
                        </div>
                                                
                        <a href="page_experience.php?id_experience=<?= $exp['ID_experience'] ?>" class="btn-voir-plus">
                            Voir les détails <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}