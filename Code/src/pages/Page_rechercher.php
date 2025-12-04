<?php
require __DIR__ . '/../back_php/fonctions_site_web.php';

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);
$id_compte = $_SESSION['ID_compte'];



function get_mes_projets_complets(PDO $bdd, int $id_compte=NULL): array {
    
    $sql_projets = "
        SELECT 
            p.ID_projet, 
            p.Nom_projet AS Nom, 
            p.Description, 
            p.Confidentiel, 
            p.Validation, 
            pcg.Statut,
            p.Date_de_creation,
            p.Date_de_modification
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


function filtrer_trier_pro_exp(PDO $bdd,
    array $types = ['projet','experience'], // types à inclure
    string $tri = 'A-Z',                    // critère de tri : 'A-Z', 'date_modif', 'date_creation'
    string $ordre = 'asc',                  // 'asc' ou 'desc'
    ?string $texte = null, 
    ?int $confid = null, 
    ?array $statut_proj = null,
    ?array $statut_exp = null
): array {

    $info = [];

    // --- Filtrer les projets si "projet" est dans le tableau
    if (in_array('projet', $types)) {
        $projets = get_mes_projets_complets($bdd);
        foreach ($projets as &$p) {
            $p["Type"] = "projet";
        }
        $projets_filtree = filtrer_projets($projets, $texte, $confid, $statut_proj);
    } else {
        $projets_filtree = [];
    }

    // --- Filtrer les expériences si "experience" est dans le tableau
    if (in_array('experience', $types)) {
        $experiences = get_mes_experiences_complets($bdd);
        foreach ($experiences as &$e) {
            $e["Type"] = "experience";
        }
        $exp_filtree = filtrer_experience($experiences, $texte, $statut_exp);
    } else {
        $exp_filtree = [];
    }

    // --- Fusionner les résultats
    $info = array_merge($projets_filtree, $exp_filtree);

    // --- Tri selon critère ($tri) et ordre ($ordre)
    if (!empty($info)) {
        usort($info, function($a, $b) use ($tri, $ordre) {
            $valA = null;
            $valB = null;

            switch ($tri) {
                case 'A-Z':
                    $valA = strtolower($a['Nom'] ?? $a['Nom'] ?? '');
                    $valB = strtolower($b['Nom'] ?? $b['Nom'] ?? '');
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

            if ($ordre === 'asc') {
                return ($valA < $valB) ? -1 : 1;
            } else { // 'desc'
                return ($valA > $valB) ? -1 : 1;
            }
        });
    }

    return $info;
}




function filtrer_projets(
    array $liste_projets, 
    ?string $texte = null, 
    ?bool $confid = null, 
    ?int $statut = null
): array {

    $resultat = [];
    $ids_vus = []; // <-- IDs déjà rencontrés
    $t = strtolower($texte ?? "");

    foreach ($liste_projets as $proj) {

        // --- 1. Filtre texte (Nom + Description + Gestionnaires + Collaborateurs)
        if (!empty($texte)) {
            $match = false;

            // Nom du projet
            if (str_contains(strtolower($proj["Nom"] ?? ""), $t)) $match = true;

            // Description
            if (!$match && str_contains(strtolower($proj["Description"] ?? ""), $t)) $match = true;

            // Gestionnaires
            if (
                !$match &&
                !empty($proj["Gestionnaires"]) &&
                is_array($proj["Gestionnaires"])
            ) {
                foreach ($proj["Gestionnaires"] as $g) {
                    if (str_contains(strtolower($g), $t)) {
                        $match = true;
                        break;
                    }
                }
            }

            // Collaborateurs
            if (
                !$match &&
                !empty($proj["Collaborateurs"]) &&
                is_array($proj["Collaborateurs"])
            ) {
                foreach ($proj["Collaborateurs"] as $c) {
                    if (str_contains(strtolower($c), $t)) {
                        $match = true;
                        break;
                    }
                }
            }

            if (!$match) continue;
        }

        // --- 2. Confidentialité
        if ($confid !== null && (($proj["Confidentiel"] ?? 0) != $confid)) continue;

        // --- 3. Progression / statut
        if ($statut !== null && (($proj['Progression'] ?? 0) != $statut)) continue;

        // --- 4. Anti-doublons grâce à ID_projet
        $id = $proj["ID_projet"] ?? null;
        if ($id !== null) {
            if (isset($ids_vus[$id])) continue; // déjà ajouté
            $ids_vus[$id] = true; // on marque comme vu
        }

        // --- 5. On garde le projet
        $resultat[] = $proj;
    }

    return $resultat;
}


function filtrer_experience(
    array $liste_experience, 
    ?string $texte = null, 
    ?array $statut = []
): array {

    $resultat = [];
    $ids_vus = []; // <-- Anti-doublons
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
        if (!empty($statuts)) {
            if (!in_array($exp["Statut_experience"], $statuts)) {
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


function afficher_projets_experiences_pagines(array $items, int $page_actuelle = 1, int $items_par_page = 6): void {
    // Calcul de la tranche à afficher
    $debut = ($page_actuelle - 1) * $items_par_page;
    $items_page = array_slice($items, $debut, $items_par_page);
    

    ?>
    <div class="liste">
        <?php if (empty($items_page)): ?>
            <p class="no-items">Aucun projet ou expérience à afficher</p>
        <?php else: ?>
            <?php foreach ($items_page as $item): ?>
                <?php if (($item['Type'] ?? '') === 'projet'): 
                    // Variables projets
                    $id = htmlspecialchars($item['ID_projet']);
                    $nom = htmlspecialchars($item['Nom']);
                    $description = $item['Description'];
                    $desc = strlen($description) > 200 
                        ? htmlspecialchars(substr($description, 0, 200)) . '…'
                        : htmlspecialchars($description);
                    $date = htmlspecialchars($item['Date_de_creation']);
                    $role = $item['Statut'] ? "Gestionnaire" : "Collaborateur";
                    ?>
                    <a class='projet-card' href='page_projet.php?id_projet=<?= $id ?>'>
                        <h3><?= $nom ?></h3>
                        <p><?= $desc ?></p>
                        <p><strong>Date de création :</strong> <?= $date ?></p>
                        <p><strong>Rôle :</strong> <?= $role ?></p>
                    </a>

                <?php elseif (($item['Type'] ?? '') === 'experience'): 
                    // Variables expériences
                    $id_experience = htmlspecialchars($item['ID_experience']);
                    $nom = htmlspecialchars($item['Nom']);
                    $description = $item['Description'];
                    $desc = strlen($description) > 200 
                        ? htmlspecialchars(substr($description, 0, 200)) . '…'
                        : htmlspecialchars($description);    
                    $date_reservation = htmlspecialchars($item['Date_reservation']);
                    $heure_debut = htmlspecialchars($item['Heure_debut']);
                    $heure_fin = htmlspecialchars($item['Heure_fin']);
                    $salle = htmlspecialchars($item['Salle'] ?? 'Non définie');
                    $nom_projet = htmlspecialchars($item['Nom'] ?? 'Sans projet');
                    $id_projet = htmlspecialchars($item['ID_projet']);
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
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}


function afficher_pagination_mixte(int $page_actuelle, int $total_pages, string $param = 'page'): void {
    if ($total_pages <= 1) return; // Pas besoin de pagination

    // Récupère les autres paramètres GET pour les préserver
    $query_params = $_GET;
    unset($query_params[$param]); // on va remplacer le paramètre de page actuel
    $base_url = '?' . http_build_query($query_params);

    ?>
    <div class="pagination">
        <?php if ($page_actuelle > 1): 
            $prev_page = $page_actuelle - 1;
            $url_prev = $base_url . ($base_url === '?' ? '' : '&') . "$param=$prev_page";
        ?>
            <a href="<?= $url_prev ?>" class="page-btn">« Précédent</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++):
            $url_i = $base_url . ($base_url === '?' ? '' : '&') . "$param=$i";
            if ($i == $page_actuelle): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= $url_i ?>" class="page-btn"><?= $i ?></a>
            <?php endif;
        endfor; ?>

        <?php if ($page_actuelle < $total_pages):
            $next_page = $page_actuelle + 1;
            $url_next = $base_url . ($base_url === '?' ? '' : '&') . "$param=$next_page";
        ?>
            <a href="<?= $url_next ?>" class="page-btn">Suivant »</a>
        <?php endif; ?>
    </div>
    <?php
}

$page_actuelle = $_GET['page'] ?? 1;
$projet_exp   = $_GET['type'] ?? [];          // 'projet' et/ou 'experience'
$tri          = $_GET['tri'] ?? 'A-Z';        // 'A-Z', 'date_modif', 'date_creation'
$ordre        = $_GET['ordre'] ?? 'asc';      // 'asc' ou 'desc'
$texte        = $_GET['texte'] ?? null;

// Confidentialité projets : true = afficher confidentiels, null = ne pas filtrer
$confid = isset($_GET['afficher_confidentiels']) ? 1 : null;

// Statut projet : true = afficher projets finis, null = ne pas filtrer
$statut_proj = isset($_GET['afficher_projets_finis']) ? 1 : null;

// Statut expérience : tableau de valeurs 'fini', 'encours', 'pascommence'
$statut_exp = [];
if (isset($_GET['statut_exp_fini'])) {
    $statut_exp[] = 'fini';
}
if (isset($_GET['statut_exp_encours'])) {
    $statut_exp[] = 'encours';
}
if (isset($_GET['statut_exp_pascommence'])) {
    $statut_exp[] = 'pascommence';
}

$items_par_page=10;

$liste_mixte=filtrer_trier_pro_exp($bdd, $projet_exp, $tri, $ordre, $texte, $confid, $statut_proj, $statut_exp);
$total_pages=create_page($liste_mixte,$items_par_page);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rechercher</title>
    <link rel="stylesheet" href="../css/page_mes_experiences.css">
    <link rel="stylesheet" href="../css/page_mes_projets.css">
    <link rel="stylesheet" href="../css/Bandeau_haut.css">
    <link rel="stylesheet" href="../css/Bandeau_bas.css">
    <link rel="stylesheet" href="../css/page_rechercher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php afficher_Bandeau_Haut($bdd, $id_compte,$recherche=false) ?>

<h1>Que recherchez-vous?</h1>

<form method="GET" action="Page_rechercher.php" style="text-align:center;">

    <!-- Barre de recherche principale -->
    <div class="searchbar">
        <input type="text" name="texte" value="<?= htmlspecialchars($_GET['texte'] ?? '') ?>" placeholder="Tapez votre recherche...">
    </div>

    <!-- Bouton Rechercher principal -->
    <button type="submit" class="search-btn">Rechercher</button>

    <!-- Container pour toggle + menu avancé -->
    <div class="advanced-container">

        <!-- Checkbox invisible pour contrôler le menu -->
        <input type="checkbox" id="toggle-adv" class="adv-toggle">
        <label for="toggle-adv" class="adv-btn">Recherche avancée</label>

        <!-- Menu avancé -->
        <div class="adv-menu">

            <!-- Type -->
            <div class="adv-row">
                <span class="adv-label">Type :</span>
                <input type="checkbox" id="type-projet" name="type[]" value="projet" <?= in_array('projet', $_GET['type'] ?? []) ? 'checked' : '' ?>>
                <label for="type-projet">Projet</label>

                <input type="checkbox" id="type-exp" name="type[]" value="experience" <?= in_array('experience', $_GET['type'] ?? []) ? 'checked' : '' ?>>
                <label for="type-exp">Expérience</label>
            </div>

            <!-- Options Projet -->
            <div class="adv-row adv-options projet-options">
                <span class="adv-label">Projet :</span>
                
                <!-- Case pour afficher les projets confidentiels -->
                <label>
                    <input type="checkbox" name="afficher_confidentiels" <?= isset($_GET['afficher_confidentiels']) ? 'checked' : '' ?>>
                    Afficher confidentiels
                </label>

                <!-- Case pour afficher les projets finis -->
                <label>
                    <input type="checkbox" name="afficher_projets_finis" <?= isset($_GET['afficher_projets_finis']) ? 'checked' : '' ?>>
                    Afficher projets finis
                </label>
            </div>


            <!-- --------------------------- -->
            <!-- Options de tri / ordre      -->
            <!-- --------------------------- -->
            <div class="adv-row">
                <span class="adv-label">Trier par :</span>
                <select name="tri">
                    <option value="A-Z" <?= ($_GET['tri'] ?? '')=='A-Z' ? 'selected' : '' ?>>A-Z</option>
                    <option value="date_modif" <?= ($_GET['tri'] ?? '')=='date_modif' ? 'selected' : '' ?>>Date de modification</option>
                    <option value="date_creation" <?= ($_GET['tri'] ?? '')=='date_creation' ? 'selected' : '' ?>>Date de création</option>
                </select>
            </div>

            <div class="adv-row">
                <span class="adv-label">Ordre :</span>
                <label><input type="radio" name="ordre" value="asc" <?= ($_GET['ordre'] ?? '')=='asc' ? 'checked' : '' ?>> Croissant</label>
                <label><input type="radio" name="ordre" value="desc" <?= ($_GET['ordre'] ?? '')=='desc' ? 'checked' : '' ?>> Décroissant</label>
            </div>

        </div>
    </div>
</form>





<div class="liste-mixte">
    <?php 
    // Affiche les projets et expériences filtrés/tris
    afficher_projets_experiences_pagines($liste_mixte, $page_actuelle, $items_par_page);

    // Pagination
    afficher_pagination_mixte($page_actuelle, $total_pages, 'page');
    ?>
</div>

<?php afficher_Bandeau_Bas() ?>
</body>
</html>
