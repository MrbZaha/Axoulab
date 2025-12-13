<?php
include_once "../back_php/fonctions_site_web.php";

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);
$id_compte = $_SESSION['ID_compte'];


/**
 * Affiche une liste paginée de projets et d'expériences.
 *
 * Cette fonction prend un tableau d'items (projets et expériences) et affiche
 * uniquement les éléments correspondant à la page actuelle, avec un nombre
 * d'items par page défini. Les projets et expériences sont affichés dans des
 * cartes HTML distinctes avec leurs informations principales.
 *
 * @param array $items Tableau d'items à afficher. Chaque item doit contenir :
 *                     - 'Type' => 'projet' ou 'experience'
 *                     - Pour les projets : 'ID_projet', 'Nom', 'Description',
 *                       'Date_de_creation', 'Statut', 'Progression'
 *                     - Pour les expériences : 'ID_experience', 'Nom', 'Description',
 *                       'Date_reservation', 'Heure_debut', 'Heure_fin', 'Nom_Salle',
 *                       'Nom_projet', 'ID_projet'
 * @param int $page_actuelle Numéro de la page à afficher (1 par défaut)
 * @param int $items_par_page Nombre d'items à afficher par page (6 par défaut)
 *
 * @return void Cette fonction n'a pas de valeur de retour, elle affiche directement le HTML.
 *
 * Comportement :
 * - Si aucun item n'est présent pour la page, un message "Aucun projet ni aucune expérience à afficher" est affiché.
 * - Les descriptions sont tronquées à 200 caractères si elles sont trop longues.
 * - Chaque projet et expérience est affiché sous forme de carte cliquable menant
 *   vers sa page détaillée respective.
 */

function afficher_projets_experiences_pagines(array $items, int $page_actuelle = 1, int $items_par_page = 6): void {
    // Calcul de la tranche à afficher
    $debut = ($page_actuelle - 1) * $items_par_page;
    $items_page = array_slice($items, $debut, $items_par_page);
    ?>

    <div class="liste">
        <?php if (empty($items_page)): ?>
            <p class="no-items">Aucun projet ni aucune expérience à afficher</p>

        <?php else: ?>
            <?php foreach ($items_page as $item): ?>

                <?php if (($item['Type'] ?? '') === 'projet'): 

                    $id = htmlspecialchars($item['ID_projet']);
                    $nom = htmlspecialchars($item['Nom']);
                    $description = $item['Description'];
                    $desc = strlen($description) > 200 
                        ? htmlspecialchars(substr($description, 0, 200)) . '…'
                        : htmlspecialchars($description);
                    $date = htmlspecialchars($item['Date_de_creation']);
                    $role = $item['Statut'];
                    $progress = (int)($item['Progression'] ?? 0);
                    $confidentiel = $item['Confidentiel'];
                    ?>
    
                    <a class='projet-card' href='page_projet.php?id_projet=<?= $id ?>'>
                        <h3><?= $nom ?></h3>
                        <p><?= $desc ?></p>
                        <p><strong>Date de création :</strong> <?= $date ?></p>
                        <p><strong>Rôle :</strong> <?= $role ?></p>
                    </a>



                <?php elseif (($item['Type'] ?? '') === 'experience'): ?>


                    <!-- Pas modifié -->
                    <?php
                    $id_experience = htmlspecialchars($item['ID_experience']);
                    $nom = htmlspecialchars($item['Nom']);
                    $description = $item['Description'];
                    $desc = strlen($description) > 200 
                        ? htmlspecialchars(substr($description, 0, 200)) . '…'
                        : htmlspecialchars($description);    
                    $date_reservation = htmlspecialchars($item['Date_reservation']);
                    $heure_debut = htmlspecialchars($item['Heure_debut']);
                    $heure_fin = htmlspecialchars($item['Heure_fin']);
                    $salle = htmlspecialchars($item['Nom_Salle'] ?? 'Non définie');
                    $nom_projet = htmlspecialchars($item['Nom_projet'] ?? 'Sans projet');
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

<?php }

/**
 * Affiche une barre de pagination pour une liste d'éléments.
 *
 * Cette fonction génère le HTML d'une pagination mixte avec liens "Précédent" et "Suivant",
 * ainsi que des liens vers toutes les pages. Elle préserve les autres paramètres GET
 * existants dans l'URL afin de ne pas perdre le contexte lors du changement de page.
 *
 * @param int $page_actuelle Numéro de la page actuellement affichée
 * @param int $total_pages Nombre total de pages disponibles
 * @param string $param Nom du paramètre GET utilisé pour la page (par défaut 'page')
 *
 * @return void Cette fonction n'a pas de valeur de retour, elle affiche directement le HTML.
 *
 * Comportement :
 * - Si le nombre total de pages est inférieur ou égal à 1, rien n'est affiché.
 * - Les liens "Précédent" et "Suivant" sont affichés uniquement si applicable.
 * - Le paramètre GET de la page est remplacé par la page actuelle dans les liens.
 * - Les autres paramètres GET existants sont conservés dans les URLs.
 */

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

?>