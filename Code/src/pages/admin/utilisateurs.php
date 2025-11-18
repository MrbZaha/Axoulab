


<?php

try     {
    $bdd = new PDO ('mysql:host=localhost;dbname=projet_site_web;charset=utf8',
                    'Tamagochi', '0uvrToua==1');
}
catch ( Exception $e ) {
    die ('Erreur : ' . $e->getMessage () );
    }

    # Écrire la requête qui permet de récupérer tous les profils
    # Plus tard, trouver un moyen d'afficher ça sur plusieurs pages plutôt qu'une seule liste déroulante
    # + trouver moyen d'avoir une barre de recherche pour une personne spécifique
    
?>

<!DOCTYPE html>
<html lang="en">
    <head>
    <title> Axoulab - Utilisateurs </title>
        <link rel="stylesheet" href="BarreNavigation.css"/>
    </head>
    <body>
    <h1> Utilisateurs </h1>

    <nav class="site_nav">
        <div id="site_nav_main">
            <a class="lab_logo" >
                <img src="Balblalba.png" alt="Logo_labo">
            </a>
            <form action="/search" method="GET">
            <input type="text" name="q" placeholder="Rechercher..." />
            <button type="submit">🔍</button>
                </form>
        </div>
        <div id="site_nav_links">
            <ul class="liste_links">
            <li class="main_links" >
                <a href="/contacts" class="Links">
                    Contacts
                </a>
            </li>
            <li class="main_links" >
                <a href="/explorer" class="Links">
                    Explorer
                </a>
            </li>
            <li class="main_links" >
                <a href="/mes_experiences" class="Links">
                    Mes expériences
                </a>
            </li>
            <li class="main_links" >
                <a href="/mes_projets" class="Links">
                    Mes_projets
                </a>
            </li>
            <li id="Notif">
                <a class="notif_logo">
                <img src="C:\xampp\htdocs\Cours_HTMLCSS\Projet_Site_Web\Assets\Notification_logo.png" alt="Logo_notif">
                    </a>
            </li>
            <li id="User">
                <a class="user_logo">
                <img src="Balblalba.png" alt="User_notif">
                    </a>
            </li>
        </ul>
        </div>
    </nav>


        <table > <caption> lalalalaaaaa </caption>
        <?php 
        $sql = $bdd->query("SELECT * FROM Table_Compte");
        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            ?>
            <tfoot> 
            <tr> 
                <th></th> 
                <th colspan="4"> BAC </th > 
            </tr > 
            </tfoot>

            <tbody>

            <tr>
                <td ></td >
                <td rowspan="3" >  <?php echo $row['Nom'];?> </td >
                <td rowspan="3" >  <?php echo $row['Prenom'];?> </td >
                <td rowspan="3" >  <?php echo $row['email'];?> </td >
            </tr >
            <tr>
                <td ></td >
            </tr >
            </tbody >
        <?php
        }
        ?>
        </table >



    </body>



</html>