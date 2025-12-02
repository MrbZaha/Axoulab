<?php
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

function get_mes_projets_complets(PDO $bdd, int $id_compte=NULL): array {
    
    $sql_projets = "
        SELECT 
            p.ID_projet, 
            p.Nom_projet, 
            p.Description, 
            p.Confidentiel, 
            p.Validation, 
            pcg.Statut,
            p.Date_de_creation
        FROM projet p
        INNER JOIN projet_collaborateur_gestionnaire pcg
            ON p.ID_projet = pcg.ID_projet    ";
    

    // Ajout conditionnel du WHERE
    if ($id_compte !== null) {
        $sql_projets .= " WHERE pcg.ID_compte = :id_compte";
        $stmt = $bdd->prepare($sql_projets);
        $stmt->execute(['id_compte' => $id_compte]);
    } else {
        $stmt = $bdd->prepare($sql_projets);
        $stmt->execute();
    }

    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($projets)) {
        return [];
    }

    $ids_projets = array_column($projets, 'ID_projet');
    $in = str_repeat('?,', count($ids_projets) - 1) . '?';

    $sql_gestionnaires = "
        SELECT 
            pcg.ID_projet, 
            c.Nom, 
            c.Prenom
        FROM projet_collaborateur_gestionnaire pcg
        INNER JOIN compte c ON pcg.ID_compte = c.ID_compte
        WHERE pcg.Statut = 1 AND pcg.ID_projet IN ($in)
    ";
    $stmt2 = $bdd->prepare($sql_gestionnaires);
    $stmt2->execute($ids_projets);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $gestionnaires = [];
    foreach ($rows as $row) {
        $gestionnaires[$row['ID_projet']][] = $row['Prenom'] . ' ' . $row['Nom'];
    }

    foreach ($projets as &$p) {
        $p['Gestionnaires'] = $gestionnaires[$p['ID_projet']] ?? [];
        $p['Progression'] = progression_projet($bdd, (int)$p['ID_projet']);

    }

    return $projets;
}

function afficher_projets_pagines(array $projets, int $page_actuelle = 1, int $items_par_page = 6): void {
    $debut = ($page_actuelle - 1) * $items_par_page;
    $projets_page = array_slice($projets, $debut, $items_par_page);
    
    ?>
    <div class="liste">
        <?php if (empty($projets_page)): ?>
            <p class="no-projects">Aucun projet en cours</p>
        <?php else: ?>
            <?php foreach ($projets_page as $p): ?>
                <?php 
                $id = htmlspecialchars($p['ID_projet']);
                $nom = htmlspecialchars($p['Nom_projet']);
                $description = $p['Description'];
                $desc = strlen($description) > 200 
                    ? htmlspecialchars(substr($description, 0, 200)) . '…'
                    : htmlspecialchars($description);
                $date = htmlspecialchars($p['Date_de_creation']);
                $role = $p['Statut'] ? "Gestionnaire" : "Collaborateur";
                ?>
                
                <a class='projet-card' href='page_projet.php?id_projet=<?= $id ?>'>
                    <h3><?= $nom ?></h3>
                    <p><?= $desc ?></p>
                    <p><strong>Date de création :</strong> <?= $date ?></p>
                    <p><strong>Rôle :</strong> <?= $role ?></p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}


function get_mes_experiences_complets(PDO $bdd, int $id_compte=NULL): array {
    
    if ($id_compte==NULL){
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
    ";

    $stmt = $bdd->prepare($sql_experiences);
    $stmt->execute();
    }

    else(){
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
            s.Nom_salle,
            p.Nom_projet,
            p.ID_projet
        FROM experience e
        LEFT JOIN projet_experience pe
            ON pe.ID_experience = e.ID_experience
        LEFT JOIN projet p
            ON p.ID_projet = pe.ID_projet
        INNER JOIN experience_experimentateur ee
            ON e.ID_experience = ee.ID_experience
        LEFT JOIN materiel_experience me
            ON e.ID_experience = me.ID_experience
        LEFT JOIN salle_materiel s
            ON me.ID_materiel = s.ID_materiel
        WHERE ee.ID_compte = :id_compte
    ";

    $stmt = $bdd->prepare($sql_experiences);
    $stmt->execute(['id_compte' => $id_compte]);
    }


    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($experiences)) {
        return [];
    }
    return $experiences;    
}


function create_page(array $items, int $items_par_page = 6): int {
    $total_items = count($items);
    if ($total_items == 0) {
        return 1;
    }
    return (int)ceil($total_items / $items_par_page);
}

function filtrer_pro_exp(PDO $bdd, int $projet_exp=2){

    switch ($projet_exp) {
        case 0:
            $info=get_mes_projets_complets($bdd)
            break;
        
        case 1:
            $info=get_mes_experiences_complets($bdd)
            break;

        case 2:
        default:
            // Projets
            $projets = get_mes_projets_complets($bdd);
            foreach ($projets as &$p) {
                $p["Type"] = "projet";
            }

            // Expériences
            $experiences = get_mes_experiences_complets($bdd);
            foreach ($experiences as &$e) {
                $e["Type"] = "experience";
            }

            // Fusion
            $info = array_merge($projets, $experiences);
            break;
    }

    return $info;
}


function filtrer_projets(
    array $liste_projets, 
    ?string $texte = null, 
    bool $confid = null, 
    ?int $statut = null
): array {

    $resultat = [];
    $t = strtolower($texte ?? "");

    foreach ($liste_projets as $proj) {

        // --- 1. Filtre texte (Nom + Description + Gestionnaires)
        if (!empty($texte)) {
            $match = false;

            // Nom du projet
            if (str_contains(strtolower($proj["Nom_projet"] ?? ""), $t)) $match = true;

            // Description
            if (!$match && str_contains(strtolower($proj["Description"] ?? ""), $t)) $match = true;

            // Gestionnaires
            if (!$match && !empty($proj["Gestionnaires"]) && is_array($proj["Gestionnaires"])) {
                foreach ($proj["Gestionnaires"] as $g) {
                    if (str_contains(strtolower($g), $t)) {
                        $match = true;
                        break;
                    }
                }
            }

            // Si rien ne match → on skip
            if (!$match) continue;
        }

        // --- 2. Confidentialité
        if ($confid !== null && (($proj["Confidentiel"] ?? 0) != $confid)) continue;

        // --- 3. Progression
        if ($proj['Progression'] !== null && (($proj['Progression'] ?? 0) != $statut)) continue;

        // --- 4. Si tout passe → on garde
        $resultat[] = $proj;
    }

    return $resultat;
}






function progression_projet(PDO $bdd, int $IDprojet): int {
    $sql_projet_exp = "
        SELECT 
            p.ID_projet,
            ex.Statut_experience 
        FROM projet p
        INNER JOIN projet_experience AS pex
            ON p.ID_projet = pex.ID_projet    
        INNER JOIN experience AS ex
            ON pex.ID_experience = ex.ID_experience
        WHERE p.ID_projet= :id_projet";
    $stmt = $bdd->prepare($sql_projet_exp);
    $stmt->execute(['id_projet' => $IDprojet]);
    $proj_exp = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if (empty($proj_exp)) {
        return 0; // pas d'expériences = progression 0%
    }

    // Exemple : Statut_experience = 'fini'
    $finies = 0;
    foreach ($proj_exp as $exp) {
        if ((int)$exp['Statut_experience'] === 2) {
            $finies++;
        }
    }

    // Pourcentage arrondi
    $progression = (int) round(($finies / count($proj_exp)) * 100);

    return $progression;
}