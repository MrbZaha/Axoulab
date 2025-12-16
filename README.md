
# Projet Site Web GB5 - Cahier de laboratoire
Site web de cahier de laboratoire, permettant le suivi des projets et experiences


Ce projet est un site web développé en PHP/MySQL permettant aux utilisateurs
de créer des projets, d’y associer des expériences, et de gérer les droits
d’accès selon les rôles (gestionnaire, collaborateur, expérimentateur).


## Technologies
- PHP 8.2.12
- MySQL / MariaDB
- HTML / CSS
- XAMPP v3.3.0

## Utilisation
 - Installer XAMPP
 - Dans le dossier xampp/php/php.ini , il faut activer l'extension gd (supprimer le ";" au debut de la ligne 931)
 - Cloner le github ou copier le code dans le dossier htdocs de XAMPP
 - Lancer Apache et MySQL
 - Créer la BDD avec le script siteweb.sql
 - Remplir la BDD avec site_web_remplissage.sql
 - Se rendre sur la page Main_page.php

## Strucure du projet

.
├───Code
│   └───src
│       ├───assets
│       │   ├───profile_pictures
│       │   ├───resultats
│       │   │   ├───1
│       │   │   ├───15
│       │   │   ├───2
│       │   │   ├───24
│       │   │   ├───3
│       │   │   └───4
│       │   └───sound
│       ├───back_php
│       │   └───fonction_page
│       ├───BDD
│       ├───css
│       ├───lib
│       └───pages
└───developement


## Auteur
Rey Axel
Helias Ewan 
Trouilloud Juliette
Marhabi Zahir Hajar
Projet réalisé dans le cadre d’un projet académique .

