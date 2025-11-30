<?php
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);

function get_mes_projets_complets(PDO $bdd, int $id_compte): array {
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
            ON p.ID_projet = pcg.ID_projet
        WHERE pcg.ID_compte = :id_compte
    ";
    $stmt = $bdd->prepare($sql_projets);
    $stmt->execute(['id_compte' => $id_compte]);
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
    }

    return $projets;
}


