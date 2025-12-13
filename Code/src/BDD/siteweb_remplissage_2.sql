-- Script de remplissage de données factices pour siteweb.sql
-- (Comptes, salles/matériel, projets, associations, expériences)

START TRANSACTION;
-- ========================================
-- INSERTION DES COMPTES (60 personnes)
-- ========================================
-- Etat: 1=étudiant, 2=professeur/chercheur, 3=ADMIN
-- Validation: 1=Validé, 0=Non validé

INSERT INTO compte (Nom, Prenom, Date_de_naissance, Email, Mdp, Etat, validation) VALUES
('Dubois', 'Marie', '1988-03-15', 'marie.dubois@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Martin', 'Pierre', '1992-07-22', 'pierre.martin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Bernard', 'Sophie', '1985-11-08', 'sophie.bernard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Petit', 'Lucas', '1990-05-12', 'lucas.petit@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Robert', 'Emma', '1987-09-30', 'emma.robert@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Richard', 'Thomas', '1995-01-18', 'thomas.richard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Durand', 'Léa', '1989-12-25', 'lea.durand@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Leroy', 'Hugo', '1993-04-07', 'hugo.leroy@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Moreau', 'Chloé', '1986-08-14', 'chloe.moreau@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Simon', 'Alexandre', '1991-06-19', 'alexandre.simon@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Laurent', 'Camille', '1994-02-28', 'camille.laurent@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Lefebvre', 'Nathan', '1988-10-03', 'nathan.lefebvre@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Michel', 'Julie', '1990-07-16', 'julie.michel@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Garcia', 'Antoine', '1987-03-22', 'antoine.garcia@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('David', 'Manon', '1992-11-09', 'manon.david@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Bertrand', 'Maxime', '1989-05-27', 'maxime.bertrand@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Roux', 'Laura', '1991-09-14', 'laura.roux@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Vincent', 'Nicolas', '1986-12-01', 'nicolas.vincent@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Fournier', 'Sarah', '1993-08-20', 'sarah.fournier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Girard', 'Julien', '1990-04-11', 'julien.girard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 3, 1),
('Bonnet', 'Océane', '1994-06-15', 'oceane.bonnet@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Lambert', 'Mathis', '1988-09-23', 'mathis.lambert@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Fontaine', 'Inès', '1991-02-11', 'ines.fontaine@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Rousseau', 'Théo', '1987-12-30', 'theo.rousseau@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Blanc', 'Clara', '1993-05-08', 'clara.blanc@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Guerin', 'Louis', '1989-11-17', 'louis.guerin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Muller', 'Jade', '1995-03-25', 'jade.muller@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Henry', 'Adam', '1986-07-14', 'adam.henry@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Faure', 'Lola', '1992-10-05', 'lola.faure@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Giraud', 'Gabriel', '1990-01-20', 'gabriel.giraud@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Andre', 'Zoé', '1994-08-28', 'zoe.andre@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Mercier', 'Tom', '1988-04-12', 'tom.mercier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Blanchard', 'Alice', '1991-12-19', 'alice.blanchard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Robin', 'Arthur', '1987-06-07', 'arthur.robin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Perrin', 'Rose', '1993-09-16', 'rose.perrin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Clement', 'Raphaël', '1989-02-24', 'raphael.clement@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Gauthier', 'Margaux', '1995-11-03', 'margaux.gauthier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Lemoine', 'Victor', '1986-05-29', 'victor.lemoine@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Masson', 'Lily', '1992-03-18', 'lily.masson@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Barbier', 'Paul', '1990-08-09', 'paul.barbier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 3, 1),
('Renard', 'Eva', '1994-01-14', 'eva.renard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Olivier', 'Noah', '1988-11-27', 'noah.olivier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Chevalier', 'Anna', '1991-05-03', 'anna.chevalier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Marchand', 'Luc', '1987-09-18', 'luc.marchand@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Dupont', 'Mia', '1993-12-22', 'mia.dupont@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Roche', 'Ethan', '1989-07-08', 'ethan.roche@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Rey', 'Charlotte', '1995-04-16', 'charlotte.rey@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Colin', 'Maxence', '1986-10-31', 'maxence.colin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Vidal', 'Lina', '1992-02-07', 'lina.vidal@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Arnaud', 'Dylan', '1988-08-25', 'dylan.arnaud@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Legrand', 'Elise', '1994-11-11', 'elise.legrand@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Aubert', 'Théo', '1990-06-19', 'theo.aubert@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Caron', 'Léna', '1987-01-28', 'lena.caron@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Picard', 'Hugo', '1993-03-04', 'hugo.picard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Girard', 'Sofia', '1991-10-12', 'sofia.girard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Roger', 'Mathéo', '1989-05-21', 'matheo.roger@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Moulin', 'Léonie', '1995-12-30', 'leonie.moulin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Dumas', 'Nathan', '1986-08-15', 'nathan.dumas@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 3, 1),
('Lefebvre', 'Juliette', '1992-04-23', 'juliette.lefebvre@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Blanchard', 'Antoine', '1988-09-06', 'antoine.blanchard@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1);


-- ========================================
-- INSERTION DES SALLES ET MATÉRIEL (5 salles, 5 matériels par salle)
-- ========================================
INSERT INTO salle_materiel (Nom_Salle, Materiel) VALUES
-- Salle A101 - Chimie
('A101', 'Microscope optique'),
('A101', 'Bécher 500ml'),
('A101', 'Pipette automatique'),
('A101', 'Centrifugeuse'),
('A101', 'Balance de précision'),

-- Salle A102 - Biologie
('A102', 'Microscope électronique'),
('A102', 'Incubateur'),
('A102', 'Autoclave'),
('A102', 'Hotte flux laminaire'),
('A102', 'Congélateur -80°C'),

-- Salle B201 - Physique
('B201', 'Oscilloscope'),
('B201', 'Générateur signaux'),
('B201', 'Multimètre'),
('B201', 'Alimentation stabilisée'),
('B201', 'Spectromètre'),

-- Salle B202 - Électronique
('B202', 'Station soudage'),
('B202', 'Analyseur spectre'),
('B202', 'Fer à souder'),
('B202', 'Plaque prototypage'),
('B202', 'Imprimante 3D'),

-- Salle C301 - Informatique
('C301', 'Serveur HPC'),
('C301', 'Station GPU'),
('C301', 'Oscilloscope numérique'),
('C301', 'Analyseur logique'),
('C301', 'Switch réseau');

-- ========================================
-- INSERTION DES PROJETS (30 projets)
-- ========================================
INSERT INTO projet (Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification) VALUES
('Nanotechnologie Médicale', 'Développement de nanoparticules pour le traitement ciblé du cancer. Collaboration avec le CHU local.', 1, 1, '2024-01-15', '2024-11-20'),
('Intelligence Artificielle Prédictive', 'Système de prédiction basé sur le machine learning pour l''analyse de données climatiques.', 0, 1, '2024-02-01', '2024-11-15'),
('Bioplastiques Innovants', 'Recherche sur des polymères biodégradables à partir de déchets agricoles.', 0, 1, '2024-01-20', '2024-10-30'),
('Optique Quantique', 'Étude des propriétés quantiques de la lumière pour des applications en cryptographie.', 1, 1, '2024-03-10', '2024-11-18'),
('Robotique Collaborative', 'Développement de robots collaboratifs pour l''assistance aux personnes âgées.', 0, 1, '2024-02-15', '2024-11-10'),
('Énergie Solaire 3G', 'Amélioration du rendement des cellules photovoltaïques par nanostructuration.', 0, 1, '2024-01-05', '2024-11-22'),
('Neurosciences Cognitives', 'Étude des mécanismes cérébraux de la mémoire par imagerie fonctionnelle.', 1, 1, '2024-03-01', '2024-11-12'),
('Matériaux Supraconducteurs', 'Recherche de nouveaux composés supraconducteurs à haute température critique.', 1, 1, '2024-02-20', '2024-11-08'),
('Microbiome Intestinal', 'Analyse de l''impact du microbiome sur les maladies inflammatoires chroniques.', 0, 1, '2024-01-25', '2024-11-05'),
('Systèmes Embarqués IoT', 'Conception de capteurs ultra-basse consommation pour l''Internet des Objets.', 0, 1, '2024-03-15', '2024-11-20'),
('Biotechnologie Marine', 'Extraction de molécules bioactives à partir de micro-algues pour applications pharmaceutiques.', 0, 1, '2024-04-05', '2024-11-25'),
('Impression 3D Médicale', 'Développement de prothèses personnalisées par fabrication additive métallique.', 1, 1, '2024-04-10', '2024-11-18'),
('Capteurs Environnementaux', 'Réseau de capteurs pour la surveillance de la qualité de l''air en milieu urbain.', 0, 1, '2024-03-20', '2024-11-14'),
('Batteries Lithium-Soufre', 'Développement de batteries nouvelle génération avec densité énergétique améliorée.', 1, 1, '2024-02-28', '2024-11-10'),
('Génomique Végétale', 'Séquençage et annotation du génome de plantes d''intérêt agronomique.', 0, 1, '2024-04-15', '2024-11-22'),
('Holographie Numérique', 'Systèmes d''affichage holographique pour applications médicales et industrielles.', 1, 1, '2024-03-25', '2024-11-16'),
('Purification de l''Eau', 'Membranes nanostructurées pour la désalinisation et la purification de l''eau.', 0, 1, '2024-04-20', '2024-11-19'),
('Catalyse Enzymatique', 'Développement d''enzymes modifiées pour la synthèse chimique verte.', 0, 1, '2024-02-10', '2024-11-08'),
('Véhicules Autonomes', 'Algorithmes de perception et de décision pour la conduite autonome en milieu urbain.', 1, 1, '2024-03-30', '2024-11-21'),
('Textiles Intelligents', 'Intégration de capteurs et d''électronique dans les fibres textiles.', 0, 1, '2024-04-25', '2024-11-17'),
('Fusion Nucléaire', 'Étude de plasmas confinés magnétiquement pour la fusion contrôlée.', 1, 1, '2024-01-30', '2024-11-13'),
('Biodiversité Urbaine', 'Analyse de la biodiversité en milieu urbain et stratégies de préservation.', 0, 1, '2024-05-01', '2024-11-23'),
('Cryptographie Post-Quantique', 'Développement d''algorithmes de chiffrement résistants aux ordinateurs quantiques.', 1, 1, '2024-03-05', '2024-11-09'),
('Agriculture de Précision', 'Optimisation des rendements agricoles par analyse de données satellite et drones.', 0, 1, '2024-05-05', '2024-11-24'),
('Immunothérapie Ciblée', 'Développement de thérapies cellulaires CAR-T pour le traitement des cancers.', 1, 1, '2024-02-05', '2024-11-11'),
('Réseaux Neuronaux Optiques', 'Circuits photoniques pour l''accélération de calculs d''intelligence artificielle.', 1, 1, '2024-04-30', '2024-11-20'),
('Cosmétiques Naturels', 'Formulation de produits cosmétiques à partir d''extraits végétaux locaux.', 0, 1, '2024-05-10', '2024-11-26'),
('Acoustique Architecturale', 'Optimisation acoustique des espaces publics par modélisation numérique.', 0, 1, '2024-03-12', '2024-11-15'),
('Biocapteurs Médicaux', 'Développement de capteurs implantables pour le monitoring continu de biomarqueurs.', 1, 1, '2024-05-15', '2024-11-27'),
('Recyclage Plastiques', 'Procédés innovants de valorisation chimique des déchets plastiques.', 0, 1, '2024-04-08', '2024-11-12');

-- 4) Associer pour chaque projet 1 gestionnaire et 0..3 collaborateurs
-- Gestionnaire: on cycle sur comptes 2..10
-- Collaborateurs: comptes 11..20 (some may be pending); will attach 0..3 via INSERT statements.

-- Projet -> Gestionnaire (1 par projet)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(1,2,1),(2,3,1),(3,4,1),(4,5,1),(5,6,1),(6,7,1),(7,8,1),(8,9,1),(9,10,1),
(10,2,1),(11,3,1),(12,4,1),(13,5,1),(14,6,1),(15,7,1),(16,8,1),(17,9,1),(18,10,1),
(19,2,1),(20,3,1),(21,4,1),(22,5,1),(23,6,1),(24,7,1),(25,8,1),(26,9,1),(27,10,1),
(28,2,1),(29,3,1),(30,4,1),(31,5,1),(32,6,1),(33,7,1),(34,8,1),(35,9,1),(36,10,1),
(37,2,1),(38,3,1),(39,4,1),(40,5,1);

-- Quelques collaborateurs (0..3 par projet) : we add a few examples
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(1,11,2),(1,12,2),(1,15,2),
(2,13,2),(2,16,2),
(3,17,2),
(5,18,2),(5,19,2),
(8,20,2),
(10,15,2),(10,16,2),(10,17,2),
(21,18,2),
(25,19,2),(25,20,2),
(30,15,2);

-- 5) Génération automatique d'expériences, associations et expérimentateurs via procédure
DELIMITER //
CREATE PROCEDURE populate_experiences_and_relations()
BEGIN
  DECLARE p INT DEFAULT 1;
  DECLARE n INT;
  DECLARE j INT;
  DECLARE last_exp_id BIGINT;
  DECLARE max_project INT DEFAULT 40;
  -- For reproducibility small seed via selecting RAND() not seeded here

  WHILE p <= max_project DO
    -- Nombre d'expériences aléatoire entre 1 et 8
    SET n = FLOOR(1 + RAND()*8);
    SET j = 1;
    WHILE j <= n DO
      -- Calculer date (certaines passées, certaines futures) : offset = p*j - 20
      INSERT INTO experience (Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Statut_experience, Date_de_creation, Date_de_modification)
      VALUES (CONCAT('Exp P', p, ' #', j),
              FLOOR(RAND()*2), -- Validation 0/1 random
              CONCAT('Description auto générée pour Exp P', p, ' #', j),
              DATE_ADD(CURDATE(), INTERVAL (p*j - 20) DAY),
              CONCAT(LPAD(FLOOR(8 + MOD(p+j,10)),2,'0'), ':00:00'),
              CONCAT(LPAD(FLOOR(9 + MOD(p+j,10)),2,'0'), ':00:00'),
              NULL,
              0,
              CURDATE(),
              CURDATE()
      );
      SET last_exp_id = LAST_INSERT_ID();

      -- Lier expérience au projet
      INSERT INTO projet_experience (ID_projet, ID_experience) VALUES (p, last_exp_id);

      -- Associer 0..3 matériels aléatoires (issus de la même salle ou global)
      -- choisir k = 0..3
      INSERT INTO materiel_experience (ID_experience, ID_materiel)
      SELECT last_exp_id, ID_materiel
      FROM salle_materiel
      ORDER BY RAND()
      LIMIT FLOOR(RAND()*4);

      -- Associer 0..3 expérimentateurs aléatoires (comptes 2..30)
      INSERT INTO experience_experimentateur (ID_experience, ID_compte)
      SELECT last_exp_id, ID_compte
      FROM compte
      WHERE Etat IN (1,2) -- étudiants/chercheurs
      ORDER BY RAND()
      LIMIT FLOOR(RAND()*4);

      SET j = j + 1;
    END WHILE;
    SET p = p + 1;
  END WHILE;
END;
//
DELIMITER ;

-- Appel de la procédure pour peupler les expériences
CALL populate_experiences_and_relations();

-- (facultatif) quelques notifications exemples
INSERT INTO notification_projet (ID_compte_envoyeur, ID_compte_receveur, ID_projet, Type_notif, Valider)
VALUES
(2,3,1,11,0),
(3,2,2,12,1);

INSERT INTO notification_experience (ID_compte_envoyeur, ID_compte_receveur, ID_experience, Type_notif, Valider)
SELECT 2, 3, e.ID_experience, 1, 0 FROM experience e LIMIT 3;

COMMIT;
