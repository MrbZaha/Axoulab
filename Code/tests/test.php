<?php 
echo  'Propriétaire du script courant : ' . get_current_user().'</br>';
echo 'commande whoami : '. exec('whoami');
?>




function afficher_Bandeau_Bas($bdd, $userID) {
    <nav class="site_nav">
        <!-- Création de 3 div les unes après les autres -->
        <div id="contact">
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
            <p class="titre">Contact</p>
            <p class="text">Campus SophiaTech</p>
            <p class="text">930 Route des Colles</p>
            <p class="text">BP 145, 06903 Sophia Antipolis</p>
        </div>
    </nav>
}




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes projets</title>
    <link rel="stylesheet" href="../src/css/Bandeau_bas.css">
    <link rel="stylesheet" href="../src/css/Bandeau_haut.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php $affiche = afficher_Bandeau_Haut($pdo, $id_compte) ?>

    <?php $affiche ?>
</head>
<body>
