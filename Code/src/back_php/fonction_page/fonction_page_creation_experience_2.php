<?php
require_once __DIR__ . '/../fonctions_site_web.php';  // Sans "back_php/" !

$bdd = connectBDD();
verification_connexion($bdd);

/**
 * Permet de récupérer toute les salles de la BDD
 *
 * @param PDO $bdd Connexion à la base de données
 * @return array Tableau contenant les noms des salles 
 */
function recup_salles(PDO $bdd) {
    $sql = "SELECT DISTINCT Nom_salle FROM salle_materiel ORDER BY Nom_salle";
    $stmt = $bdd->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère le matériel de chaque salle
 * @param PDO $bdd Connexion à la base de données
 * @param string $nom_salle nom de la salle
 * @return array Tableau contenant les matériels présent dans les salles
*/ 
function recuperer_materiels_salle(PDO $bdd, string $nom_salle) {
    $sql = "
        SELECT ID_materiel, Materiel
        FROM salle_materiel
        WHERE Nom_salle = :nom_salle
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['nom_salle' => $nom_salle]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Permet de récupérer l'identifiant du matériel d'une salle à partir de leurs noms
 *
 * @param PDO $bdd Connexion à la base de données
 * @param string $nom_materiel nom du materiel
 * @param string $nom_salle nom de la salle
 * @return int l'identifiant du matériel, null si le matériel n'existe pas
 */
function recuperer_id_materiel_par_nom(PDO $bdd, string $nom_materiel, string $nom_salle) {
    $sql = "
        SELECT ID_materiel
        FROM salle_materiel
        WHERE Materiel = :materiel AND Nom_salle = :nom_salle
        LIMIT 1
    ";
    $stmt = $bdd->prepare($sql);
    $stmt->execute(['materiel' => $nom_materiel, 'nom_salle' => $nom_salle]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['ID_materiel'] : null;
}

/**
 * Récupère toutes les réservations d’une salle pour une semaine donnée.
 *
 * Cette fonction retourne l’ensemble des expériences réservées dans une salle
 * entre deux dates (généralement du lundi au dimanche).  
 * Elle inclut les informations sur l’expérience, le projet associé, les matériels utilisés
 * ainsi que les expérimentateurs impliqués.
 *
 * @param PDO   $bdd         Connexion PDO à la base de données
 * @param string $nom_salle  Nom de la salle pour laquelle récupérer les réservations
 * @param date  $date_debut  Date de début de période (inclus), typiquement le lundi
 * @param date  $date_fin    Date de fin de période (inclus), typiquement le dimanche
 *
 * @return array Tableau contenant, pour chaque réservation :
 *               - 'ID_experience' (int) : Identifiant de l'expérience
 *               - 'nom_experience' (string) : Nom de l'expérience
 *               - 'Date_reservation' (date) : Date de la réservation
 *               - 'Heure_debut' (string) : Heure de début
 *               - 'Heure_fin' (string) : Heure de fin
 *               - 'Description' (string|null) : Description de l'expérience
 *               - 'Statut_experience' (int) : Statut de la réservation
 *               - 'Nom_projet' (string|null) : Nom du projet associé (s'il existe)
 *               - 'materiels_utilises' (string) : Liste des matériels utilisés (concaténés)
 *               - 'experimentateurs' (string) : Liste des expérimentateurs impliqués (concaténés)
 */
function recuperer_reservations_semaine(PDO $bdd, string $nom_salle,string $date_debut,string $date_fin) {
    $sql = "
        SELECT 
            e.ID_experience,
            e.Nom AS nom_experience,
            DATE(e.Date_reservation) AS Date_reservation,
            e.Heure_debut,
            e.Heure_fin,
            e.Description,
            e.Statut_experience,
            p.Nom_projet,
            GROUP_CONCAT(DISTINCT sm.Materiel SEPARATOR ', ') AS materiels_utilises,
            GROUP_CONCAT(DISTINCT CONCAT(c.Prenom, ' ', c.Nom) SEPARATOR ', ') AS experimentateurs
        FROM experience e
        INNER JOIN materiel_experience em ON em.ID_experience = e.ID_experience
        INNER JOIN salle_materiel sm ON sm.ID_materiel = em.ID_materiel
        LEFT JOIN projet_experience pe ON pe.ID_experience = e.ID_experience
        LEFT JOIN projet p ON p.ID_projet = pe.ID_projet
        LEFT JOIN experience_experimentateur ee ON ee.ID_experience = e.ID_experience
        LEFT JOIN compte c ON c.ID_compte = ee.ID_compte
        WHERE sm.Nom_salle = :nom_salle
        AND DATE(e.Date_reservation) BETWEEN :date_debut AND :date_fin
        GROUP BY e.ID_experience, e.Nom, e.Date_reservation, e.Heure_debut, e.Heure_fin, e.Description, e.Statut_experience, p.Nom_projet
        ORDER BY e.Date_reservation, e.Heure_debut
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'nom_salle' => $nom_salle,
        'date_debut' => $date_debut,
        'date_fin' => $date_fin
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Génère la liste des dates correspondant à une semaine donnée.
 *
 * Cette fonction calcule les dates du lundi au dimanche à partir d’une date
 * de référence. Si aucune date n’est fournie, la date du jour est utilisée.
 * Elle renvoie un tableau contenant pour chaque jour : la date brute, le nom
 * du jour, son numéro dans la semaine et une version formatée.
 *
 * @param string|null $date_reference Date utilisée pour déterminer la semaine.
 *                                    Format attendu : 'Y-m-d'. Si null, utilise la date actuelle.
 *
 * @return array Tableau associatif contenant 7 éléments (un par jour), chacun avec :
 *               - 'date' (string) : Date au format 'Y-m-d'
 *               - 'jour' (string) : Nom du jour en français (Lundi → Dimanche)
 *               - 'numero_jour' (int) : Position du jour dans la semaine (1 à 7)
 *               - 'date_formatee' (string) : Date formatée en 'd/m/Y'
 */
function get_dates_semaine($date_reference = null) {
    if ($date_reference === null) { $date_reference = date('Y-m-d'); }
    $date = new DateTime($date_reference);
    $jour_semaine = (int)$date->format('N');
    $date->modify('-' . ($jour_semaine - 1) . ' days');
    $jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
    $dates_semaine = [];
    for ($i=0;$i<7;$i++){
        $dates_semaine[] = [
            'date' => $date->format('Y-m-d'),
            'jour' => $jours[$i],
            'numero_jour' => $i+1,
            'date_formatee' => $date->format('d/m/Y')
        ];
        $date->modify('+1 day');
    }
    return $dates_semaine;
}

/**
 * Vérifie si un créneau horaire est occupé pour un jour donné.
 *
 * Cette fonction analyse une liste de réservations et détermine si un créneau
 * d'une heure spécifique est déjà pris.  
 * Elle renvoie toutes les réservations qui occupent cette heure.
 *
 * @param array $reservations Liste des réservations, chaque élément contenant :
 *                            - 'Date_reservation' (string)
 *                            - 'Heure_debut' (string)
 *                            - 'Heure_fin' (string)
 * @param array $jour Tableau représentant un jour de la semaine généré par get_dates_semaine()
 *                    - 'date' (string) : Date du jour au format 'Y-m-d'
 * @param int $heure Heure à vérifier (entre 0 et 23)
 *
 * @return array Tableau des réservations qui occupent ce créneau.
 *               Retourne un tableau vide si aucune réservation ne couvre cette heure.
 */
function creneau_est_occupe($reservations, $jour, $heure) {
    $creneaux = [];
    foreach ($reservations as $reservation) {
        $res_date = $reservation['Date_reservation'];
        if ($res_date === $jour['date']) {
            $hd = (int)date('G', strtotime($reservation['Heure_debut']));
            $hf = (int)date('G', strtotime($reservation['Heure_fin']));
            if ($heure >= $hd && $heure < $hf) {
                $creneaux[] = $reservation;
            }
        }
    }
    return $creneaux;
}

/**
 * Vérifie si un matériel est disponible pour un créneau donné
 *
 * @param PDO $bdd Connexion à la base de données
 * @param int $id_materiel ID du matériel à vérifier
 * @param string $date Date de la réservation (format Y-m-d)
 * @param string $heure_debut Heure de début (format H:i)
 * @param string $heure_fin Heure de fin (format H:i)
 * @return array Tableau contenant 'disponible' (bool) et 'conflit' (string) avec le nom de l'expérience en conflit
 */
function verifier_disponibilite_materiel($bdd, $id_materiel, $date, $heure_debut, $heure_fin) {
    $sql = "
        SELECT 
            e.Nom AS nom_experience,
            e.Heure_debut,
            e.Heure_fin
        FROM experience e
        INNER JOIN materiel_experience me ON me.ID_experience = e.ID_experience
        WHERE me.ID_materiel = :id_materiel
        AND DATE(e.Date_reservation) = :date
        AND (
            (e.Heure_debut < :heure_fin AND e.Heure_fin > :heure_debut)
        )
    ";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'id_materiel' => $id_materiel,
        'date' => $date,
        'heure_debut' => $heure_debut,
        'heure_fin' => $heure_fin
    ]);
    
    $conflit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'disponible' => !$conflit,
        'conflit' => $conflit ? $conflit['nom_experience'] : null
    ];
}

/**
 * Organise les réservations par date et par créneau horaire.
 *
 * Cette fonction génère une structure de planning dans laquelle chaque date et
 * chaque heure contiennent un tableau des réservations associées.
 *
 * @param array $reservations Liste complète des réservations de la semaine
 * @param array $dates_semaine Tableau généré par get_dates_semaine()
 * @param array $heures Liste des heures à afficher dans le planning (ex : [8,9,10,...])
 *
 * @return array Tableau indexé par date puis par heure, contenant les réservations correspondantes
 */
function organiser_reservations_par_creneau($reservations, $dates_semaine, $heures) {
    $planning = [];
    
    // Initialiser le planning
    foreach ($dates_semaine as $jour) {
        $planning[$jour['date']] = [];
        foreach ($heures as $heure) {
            $planning[$jour['date']][$heure] = [];
        }
    }
    
    // Remplir le planning
    foreach ($reservations as $res) {
        $date = $res['Date_reservation'];
        $heure_debut = (int)date('G', strtotime($res['Heure_debut']));
        $heure_fin = (int)date('G', strtotime($res['Heure_fin']));
        
        // Ajouter la réservation à toutes les heures concernées
        for ($h = $heure_debut; $h < $heure_fin; $h++) {
            if (isset($planning[$date][$h])) {
                $planning[$date][$h][] = $res;
            }
        }
    }
    
    return $planning;
}


/**
 * Crée une nouvelle expérience dans la base de données.
 *
 * @param PDO    $bdd              Connexion PDO
 * @param string $nom_experience   Nom de l'expérience
 * @param string $description       Description de l'expérience
 * @param string $date_reservation  Date prévue (Y-m-d)
 * @param string $heure_debut       Heure de début (HH:MM:SS)
 * @param string $heure_fin         Heure de fin (HH:MM:SS)
 * @param string $nom_salle         Nom de la salle (non utilisé ici)
 *
 * @return int|false ID de l'expérience créée ou false en cas d'échec
 */
function creer_experience($bdd, $validation, $nom_experience, $description, $date_reservation, $date_creation, $heure_debut, $heure_fin, $nom_salle) {
    $sql = $bdd->prepare("
        INSERT INTO experience (Nom, Description, Date_reservation, Date_de_creation, Heure_debut, Heure_fin, Statut_experience, Validation)
        VALUES (?, ?, ?,?, ?, ?, 'En attente', 0)
    ");

    if ($sql->execute([$nom_experience, $description, $date_reservation, $date_creation, $heure_debut, $heure_fin])) {
        return $bdd->lastInsertId();
    }
    return false;
}

/**
 * Associe une expérience à un projet.
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet ID du projet
 * @param int $id_experience ID de l'expérience
 *
 * @return bool True si l'insertion réussit
 */
function associer_experience_projet($bdd, $id_projet, $id_experience) {
    $sql = $bdd->prepare("
        INSERT INTO projet_experience (ID_projet, ID_experience)
        VALUES (?, ?)
    ");
    return $sql->execute([$id_projet, $id_experience]);
}

/**
 * Ajoute une liste d'expérimentateurs à une expérience.
 *
 * @param PDO   $bdd Connexion PDO
 * @param int   $id_experience ID de l'expérience
 * @param array $experimentateurs Liste des ID_compte à associer
 */
function ajouter_experimentateurs($bdd, $id_experience, $experimentateurs) {
    $sql = $bdd->prepare("
        INSERT INTO experience_experimentateur (ID_experience, ID_compte)
        VALUES (?, ?)
    ");

    foreach ($experimentateurs as $id_compte) {
        $sql->execute([$id_experience, $id_compte]);
    }
}

/**
 * Associe plusieurs matériels à une expérience.
 *
 * @param PDO   $bdd Connexion PDO
 * @param int   $id_experience ID de l'expérience
 * @param array $materiels_ids Liste des ID_materiel à associer
 */
function associer_materiel_experience($bdd, $id_experience, $materiels_ids) {
    $sql_insert = $bdd->prepare("
        INSERT INTO materiel_experience (ID_experience, ID_materiel)
        VALUES (?, ?)
    ");
    
    foreach ($materiels_ids as $id_materiel) {
        $sql_insert->execute([$id_experience, $id_materiel]);
    }
}

/**
 * Récupère le nom d'un projet à partir de son identifiant.
 *
 * @param PDO $bdd Connexion PDO
 * @param int $id_projet Identifiant du projet
 *
 * @return string|null Nom du projet ou null s'il n'existe pas
 */
function get_nom_projet($bdd, $id_projet) :string{
    $sql = "
        SELECT 
            p.Nom_projet
        FROM projet p
        WHERE p.ID_projet = :id_projet
    ";

    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        'id_projet' => $id_projet,
    ]);
    $nom_projet = $stmt->fetchColumn();
    return $nom_projet;
}

?>