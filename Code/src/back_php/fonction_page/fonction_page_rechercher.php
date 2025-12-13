<?php
include_once "../back_php/fonctions_site_web.php";

$bdd = connectBDD();
#On vérifie si l'utilisateur est bien connecté avant d'accéder à la page
verification_connexion($bdd);
$id_compte = $_SESSION['ID_compte'];

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