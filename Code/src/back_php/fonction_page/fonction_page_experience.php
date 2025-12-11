<?php
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();
verification_connexion($bdd);

$id_compte = $_SESSION['ID_compte'];
$id_experience = isset($_GET['id_experience']) ? (int)$_GET['id_experience'] : 0;

// Variable pour stocker les erreurs
$erreur = null;


/**
 * Vérifie si l'utilisateur a le droit d'accéder à une expérience donnée.
 *
 * Cette fonction implémente une logique de contrôle d'accès à trois niveaux :
 * 1. L'utilisateur est directement expérimentateur de cette expérience → accès autorisé
 * 2. L'expérience est liée à un projet non confidentiel → accès autorisé à tous
 * 3. L'expérience est liée à un projet confidentiel → accès uniquement aux gestionnaires du projet
 *
 * Elle vérifie d'abord si l'utilisateur est expérimentateur, puis, si ce n'est pas
 * le cas, remonte au projet parent pour appliquer les règles de confidentialité.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte utilisateur dont on vérifie les droits
 * @param int $id_experience ID de l'expérience à laquelle on souhaite accéder
 *
 * @return str 'modification' si la personne est experimentateur de l'experience ou gestionnaire du projet lié
 *             'acces' si elle est collaborateur du projet ou que le projet n'est pas confidentiel
 *             'none' dans les cas restants
 */
function verifier_acces_experience(PDO $bdd, int $id_compte, int $id_experience): string {
    // Vérifier si l'utilisateur est expérimentateur
    $sql_experimentateur = "
        SELECT 1 
        FROM experience_experimentateur 
        WHERE ID_experience = :id_experience 
        AND ID_compte = :id_compte
    ";
    $stmt = $bdd->prepare($sql_experimentateur);
    $stmt->execute([
        'id_experience' => $id_experience,
        'id_compte' => $id_compte
    ]);
    
    if ($stmt->fetch()) {
        return 'modification'; // L'utilisateur est expérimentateur
    }
    
    // Sinon, vérifier via le projet lié
    $sql_projet = "
        SELECT 
            p.Confidentiel,
            pcg.Statut
        FROM experience e
        LEFT JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        LEFT JOIN projet p ON pe.ID_projet = p.ID_projet
        LEFT JOIN projet_collaborateur_gestionnaire pcg 
            ON p.ID_projet = pcg.ID_projet AND pcg.ID_compte = :id_compte
        WHERE e.ID_experience = :id_experience
    ";
    
    $stmt2 = $bdd->prepare($sql_projet);
    $stmt2->execute([
        'id_experience' => $id_experience,
        'id_compte' => $id_compte
    ]);
    
    $result = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return 'none'; // Pas de projet lié ou projet inexistant
    }

    // Si personne gestionnaire -> droit de modification
    else if (isset($result['Statut']) && (int)$result['Statut'] === 1) {
        return 'modification';
    }

    // Si personne collaborateur -> droit d'accès
    else if (isset($result['Statut']) && (int)$result['Statut'] === 0) {
        return 'acces';
    }
    
    // Si projet non confidentiel → accessible
    else if ((int)$result['Confidentiel'] === 0) {
        return 'acces';
    }
    
    else {
        return 'none';
    }
}   


/**
 * Récupère toutes les informations détaillées d'une expérience.
 *
 * Cette fonction charge l'ensemble des données principales de l'expérience,
 * incluant ses métadonnées (nom, description, dates), son statut de validation,
 * ses résultats éventuels, et le projet auquel elle est associée. Elle effectue
 * une jointure avec les tables projet pour obtenir le contexte complet.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_experience ID de l'expérience à récupérer
 *
 * @return array|null Tableau associatif contenant les informations de l'expérience :
 *                    - 'ID_experience' (int) : Identifiant unique de l'expérience
 *                    - 'Nom' (string) : Nom de l'expérience
 *                    - 'Description' (string) : Description détaillée
 *                    - 'Date_reservation' (string) : Date au format 'Y-m-d'
 *                    - 'Heure_debut' (string) : Heure de début au format 'H:i:s'
 *                    - 'Heure_fin' (string) : Heure de fin au format 'H:i:s'
 *                    - 'Validation' (int) : Statut de validation (0=en attente, 1=validée)
 *                    - 'Resultat' (string|null) : Résultats de l'expérience (peut être null)
 *                    - 'Statut_experience' (int) : État d'avancement (0=en attente, 1=en cours, 2=terminée)
 *                    - 'ID_projet' (int|null) : ID du projet parent (null si non lié)
 *                    - 'Nom_projet' (string|null) : Nom du projet parent (null si non lié)
 *                    Retourne null si l'expérience n'existe pas
 */
function get_info_experience(PDO $bdd, int $id_experience): ?array {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Nom,
            e.Description,
            e.Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Validation,
            e.Resultat,
            e.Date_de_creation,
            e.Date_de_modification,
            e.Statut_experience,
            p.ID_projet,
            p.Nom_projet
        FROM experience e
        LEFT JOIN projet_experience pe ON e.ID_experience = pe.ID_experience
        LEFT JOIN projet p ON pe.ID_projet = p.ID_projet
        WHERE e.ID_experience = :id_experience
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    $experience = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $experience ?: null;
}


/**
 * Récupère les salles et le matériel utilisés pour une expérience.
 *
 * Cette fonction liste l'ensemble du matériel réservé pour l'expérience, en
 * incluant pour chaque élément la salle dans laquelle il se trouve. Les résultats
 * sont triés par nom de salle puis par matériel, facilitant l'organisation logistique.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_experience ID de l'expérience dont on souhaite connaître les ressources
 *
 * @return array Tableau de tableaux associatifs, chaque élément contenant :
 *               - 'Nom_salle' (string) : Nom de la salle où se trouve le matériel
 *               - 'Materiel' (string) : Nom/description du matériel utilisé
 *               Exemple : [
 *                   ['Nom_salle' => 'Lab 201', 'Materiel' => 'Microscope'],
 *                   ['Nom_salle' => 'Lab 201', 'Materiel' => 'Centrifugeuse']
 *               ]
 *               Retourne un tableau vide si aucun matériel n'est associé
 */
function get_salles_et_materiel(PDO $bdd, int $id_experience): array {
    $sql = "
        SELECT 
            sm.Nom_salle,
            sm.Materiel
        FROM salle_materiel sm
        JOIN materiel_experience me ON sm.ID_materiel = me.ID_materiel
        WHERE me.ID_experience = :id_experience
        ORDER BY sm.Nom_salle, sm.Materiel
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['id_experience' => $id_experience]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Affiche un message d'erreur dans un conteneur stylisé.
 *
 * Cette fonction génère le HTML nécessaire pour afficher un message d'erreur
 * de manière visuelle et cohérente avec le reste de l'interface. Elle est
 * utilisée pour informer l'utilisateur de problèmes d'accès ou d'expériences
 * inexistantes.
 *
 * @param string $erreur Message d'erreur à afficher (sera échappé pour sécurité HTML)
 *
 * @return void Affiche directement le HTML, ne retourne rien
 */
function afficher_erreur(string $erreur): void {
    ?>
    <div class="project-container">
        <div class="error-message">
            <?= htmlspecialchars($erreur) ?>
        </div>
    </div>
    <?php
}

/**
 * Affiche la page complète d'une expérience avec toutes ses informations.
 *
 * Cette fonction génère l'interface HTML complète pour visualiser une expérience,
 * incluant sa description, ses résultats, les informations pratiques (date, horaires),
 * le statut de validation, les expérimentateurs, les salles et le matériel utilisés.
 * Elle organise les données en sections thématiques et regroupe intelligemment
 * les salles et matériels pour éviter les doublons.
 *
 * @param array $experience Tableau associatif contenant les informations principales
 *                         (structure retournée par get_info_experience)
 * @param array $experimentateurs Tableau de chaînes "Prénom Nom" des expérimentateurs
 *                               (structure retournée par get_experimentateurs)
 * @param array $salles_materiel Tableau de paires salle/matériel
 *                              (structure retournée par get_salles_et_materiel)
 *
 * @return void Affiche directement le HTML de la page, ne retourne rien
 */
function afficher_experience(array $experience, array $experimentateurs, array $salles_materiel): void {
    // Regrouper les salles et le matériel
    $salles = [];
    $materiels = [];
    
    foreach ($salles_materiel as $item) {
        // Ajouter la salle (éviter les doublons)
        if (!in_array($item['Nom_salle'], $salles)) {
            $salles[] = $item['Nom_salle'];
        }
        
        // Ajouter le matériel avec son nombre
        if (!empty($item['Materiel'])) {
            $materiels[] = [
                'nom' => $item['Materiel'],
                'salle' => $item['Nom_salle']
            ];
        }
    }
    
    ?>
    <div class="projets">
        <section class="section-projets">
            <h2><?= htmlspecialchars($experience['Nom']) ?></h2>
            
            <div class="project-container">
                <div class="project-main">
                    <?php
                    global $bdd;
                    $canModify = false;
                    if (isset($_SESSION['ID_compte']) && isset($experience['ID_experience'])) {
                        $canModify = verifier_acces_experience($bdd, $_SESSION['ID_compte'], $experience['ID_experience']) === 'modification';
                    }
                    ?>
                    <?php if ($canModify): ?>
                        <div class="actions-experience">
                            <form action="page_modification_experience.php?id_experience=<?= $experience['ID_experience'] ?>" method="post">
                                <input type="submit" value="Modifier l'expérience" />
                            </form>
                        </div>
                    <?php endif; ?>
                    <!-- Description -->
                    <div class="project-description">
                        <h3>Description</h3>
                        <p><?= nl2br(htmlspecialchars($experience['Description'])) ?></p>
                        
                        <?php if (!empty($experience['Resultat'])): ?>
                            <h3 style="margin-top: 25px;">Résultats</h3>
                            <p><?= nl2br(htmlspecialchars($experience['Resultat'])) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Informations -->
                    <div class="project-info">
                        <h3>Informations</h3>
                        
                        <p><strong>Date de création:</strong> <?= date('d/m/Y', strtotime($experience['Date_de_creation'])) ?></p>
                        <p><strong>Date de l'expérience:</strong> <?= date('d/m/Y', strtotime($experience['Date_reservation'])) ?></p>
                        <p><strong>Horaires :</strong> <?= substr($experience['Heure_debut'], 0, 5) ?> - <?= substr($experience['Heure_fin'], 0, 5) ?></p>
                        
                        <p><strong>Statut :</strong> 
                            <span class="badge <?= $experience['Statut_experience'] ? 'badge-termine' : 'badge-en-cours' ?>">
                                <?= $experience['Statut_experience'] ? 'Terminée' : 'En cours' ?>
                            </span>
                        </p>
                        
                        <p><strong>Validation :</strong> 
                            <?= $experience['Validation'] ? 'Validée' : 'En attente' ?>
                        </p>
                        
                        <?php if (!empty($experience['Nom_projet'])): ?>
                            <h4>Projet lié</h4>
                            <p>
                                <a href="projet.php?id_projet=<?= $experience['ID_projet'] ?>" class="link-projet">
                                    <?= htmlspecialchars($experience['Nom_projet']) ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <h4>Expérimentateur(s)</h4>
                        <p><?= !empty($experimentateurs) ? htmlspecialchars(implode(', ', $experimentateurs)) : "Aucun" ?></p>
                        
                        <?php if (!empty($salles)): ?>
                            <h4>Salle(s) utilisée(s)</h4>
                            <p><?= htmlspecialchars(implode(', ', $salles)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Matériel utilisé -->
                <?php if (!empty($materiels)): ?>
                    <div class="section-projets" style="margin-top: 30px;">
                        <h3>Matériel utilisé (<?= count($materiels) ?>)</h3>
                        <div class="materiel-list">
                            <?php foreach ($materiels as $mat): ?>
                                <div class="materiel-card">
                                    <p><strong><?= htmlspecialchars($mat['nom']) ?></strong></p>
                                    <p>Salle : <?= htmlspecialchars($mat['salle']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php
}

/**
 * Charge toutes les données nécessaires à l'affichage d'une page d'expérience.
 *
 * Cette fonction orchestre la récupération complète des données d'une expérience
 * en effectuant plusieurs vérifications préalables. Elle suit une logique en cascade :
 * 1. Vérification de l'existence de l'expérience
 * 2. Vérification des droits d'accès de l'utilisateur
 * 3. Chargement de toutes les données associées (info, expérimentateurs, matériel)
 *
 * Elle centralise la gestion des erreurs et retourne un tableau unifié facilitant
 * l'affichage conditionnel dans la page HTML.
 *
 * @param PDO $bdd Connexion PDO à la base de données
 * @param int $id_compte ID du compte utilisateur qui tente d'accéder à l'expérience
 * @param int $id_experience ID de l'expérience à charger
 *
 * @return array Tableau associatif contenant :
 *               - 'erreur' (string|null) : Message d'erreur si problème détecté, null sinon.
 *                 Valeurs possibles : "Cette expérience n'existe pas" ou "Vous n'avez pas accès"
 *               - 'experience' (array|null) : Données de l'expérience (via get_info_experience)
 *                 ou null en cas d'erreur
 *               - 'experimentateurs' (array) : Liste des expérimentateurs (vide en cas d'erreur)
 *               - 'salles_materiel' (array) : Liste du matériel et salles (vide en cas d'erreur)
 */
function charger_donnees_experience(PDO $bdd, int $id_compte, int $id_experience): array {
    // Vérifier si l'expérience existe
    $sql_check = "SELECT ID_experience FROM experience WHERE ID_experience = :id_experience";
    $stmt = $bdd->prepare($sql_check);
    $stmt->execute(['id_experience' => $id_experience]);
    
    if (!$stmt->fetch()) {
        return [
            'erreur' => "Désolé, cette expérience n'existe pas.",
            'experience' => null,
            'experimentateurs' => [],
            'salles_materiel' => []
        ];
    }
    
    // Vérifier l'accès
    if (!(verifier_acces_experience($bdd, $id_compte, $id_experience) == 'acces' || verifier_acces_experience($bdd, $id_compte, $id_experience) == 'modification')) {
        return [
            'erreur' => "Vous n'avez pas accès à cette expérience.",
            'experience' => null,
            'experimentateurs' => [],
            'salles_materiel' => []
        ];
    }
    
    // Charger toutes les données
    return [
        'erreur' => null,
        'experience' => get_info_experience($bdd, $id_experience),
        'experimentateurs' => get_experimentateurs($bdd, $id_experience),
        'salles_materiel' => get_salles_et_materiel($bdd, $id_experience)
    ];
}
?>