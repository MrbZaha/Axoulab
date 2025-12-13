-- ========================================
-- INSERTION DES COMPTES (20 personnes)
-- ========================================
-- Etat: 1=étudiant, 2=professeur/chercheur, 3=ADMIN
-- Validation: 1=Validé, 0=Non validé
INSERT IGNORE INTO compte (Nom, Prenom, Date_de_naissance, Email, Mdp, Etat, validation) VALUES
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
('Lopez', 'Adrien', '1996-06-12', 'adrien.lopez@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Muller', 'Claire', '1984-02-19', 'claire.muller@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Nguyen', 'Paul', '1995-09-03', 'paul.nguyen@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Renaud', 'Isabelle', '1983-07-27', 'isabelle.renaud@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Benali', 'Yassine', '1997-01-10', 'yassine.benali@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Caron', 'Élodie', '1991-04-05', 'elodie.caron@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Schmidt', 'Lucas', '1994-12-18', 'lucas.schmidt@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Blanc', 'Aurélie', '1986-08-09', 'aurelie.blanc@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Diallo', 'Mamadou', '1993-03-14', 'mamadou.diallo@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Perrin', 'Mathieu', '1989-11-02', 'mathieu.perrin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Morel', 'Kevin', '1996-05-08', 'kevin.morel@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Faure', 'Celine', '1985-09-17', 'celine.faure@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Giraud', 'Thomas', '1992-01-26', 'thomas.giraud@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Colin', 'Marion', '1988-06-30', 'marion.colin@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Benoit', 'Julien', '1994-10-12', 'julien.benoit@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Lemoine', 'Audrey', '1986-03-04', 'audrey.lemoine@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Rolland', 'Bastien', '1995-07-21', 'bastien.rolland@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Leger', 'Pauline', '1991-12-09', 'pauline.leger@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Aubert', 'Romain', '1989-08-16', 'romain.aubert@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Pichon', 'Laura', '1997-02-11', 'laura.pichon@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Barbier', 'Florian', '1993-04-28', 'florian.barbier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Bourgeois', 'Sandrine', '1984-11-06', 'sandrine.bourgeois@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Rey', 'Antoine', '1996-09-15', 'antoine.rey@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Guillot', 'Nathalie', '1987-01-23', 'nathalie.guillot@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Charles', 'Mathis', '1995-06-02', 'mathis.charles@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Monnier', 'Aurore', '1988-10-19', 'aurore.monnier@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1),
('Clement', 'Julien', '1992-12-27', 'julien.clement@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Pages', 'Lucie', '1994-05-31', 'lucie.pages@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Hoarau', 'Jordan', '1993-08-07', 'jordan.hoarau@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 1, 1),
('Delmas', 'Isabelle', '1985-02-14', 'isabelle.delmas@axoulab.fr', '$2y$10$qfCTiM6N9cDeq8/FpEcMteWA5YfkmFZBUMurOxf7F5xSXXBbidRW.', 2, 1);

INSERT IGNORE INTO projet (Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification) VALUES
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
('Bioinformatique avancée', 'Analyse de grandes bases de données génomiques.', 0, 1, '2024-03-05', '2024-12-01'),
('Chimie verte', 'Développement de procédés chimiques respectueux de l''environnement.', 0, 1, '2024-03-12', '2024-12-03'),
('Énergie éolienne innovante', 'Optimisation des turbines pour faible vent.', 0, 1, '2024-03-15', '2024-12-05'),
('Réalité augmentée éducative', 'Plateforme immersive pour l''apprentissage scientifique.', 0, 1, '2024-03-18', '2024-12-06'),
('Immunologie cellulaire', 'Étude de l''activation des lymphocytes T.', 1, 1, '2024-03-20', '2024-12-07'),
('Nanorobots médicaux', 'Conception de nanorobots pour administration ciblée.', 1, 1, '2024-03-22', '2024-12-08'),
('Biomécanique sportive', 'Analyse des mouvements pour prévention des blessures.', 0, 1, '2024-03-25', '2024-12-09'),
('Neurofeedback VR', 'Interface VR pour entraînement cognitif.', 0, 1, '2024-03-28', '2024-12-10'),
('Hydrogène propre', 'Électrolyse de l''eau avec rendement optimisé.', 0, 1, '2024-03-30', '2024-12-11'),
('Microfluidique appliquée', 'Systèmes microfluidiques pour analyse biologique.', 0, 1, '2024-04-01', '2024-12-12'),
('Cryogénie quantique', 'Refroidissement de systèmes pour applications quantiques.', 1, 1, '2024-04-03', '2024-12-13'),
('Écologie urbaine', 'Étude de la biodiversité en milieu urbain.', 0, 1, '2024-04-05', '2024-12-14'),
('Robotique chirurgicale', 'Développement d''assistants robotiques pour chirurgiens.', 1, 1, '2024-04-08', '2024-12-15'),
('Big Data santé', 'Analyse prédictive des maladies chroniques.', 0, 1, '2024-04-10', '2024-12-16'),
('Matériaux composites', 'Création de composites légers pour l''aérospatial.', 0, 1, '2024-04-12', '2024-12-17'),
('Agritech', 'Optimisation de l''irrigation par capteurs intelligents.', 0, 1, '2024-04-15', '2024-12-18'),
('Optogénétique', 'Contrôle de neurones par lumière.', 1, 1, '2024-04-18', '2024-12-19'),
('Deep Learning médical', 'Détection automatique d''anomalies sur images IRM.', 0, 1, '2024-04-20', '2024-12-20'),
('Transport autonome', 'Véhicules autonomes en environnement urbain.', 0, 1, '2024-04-22', '2024-12-21'),
('Simulation climatique', 'Modèles numériques de changement climatique.', 0, 1, '2024-04-25', '2024-12-22');

-- ========================================
-- ATTRIBUTION DES GESTIONNAIRES ET COLLABORATEURS
-- ========================================
-- Statut: 0=collaborateur, 1=gestionnaire
-- Projet 1: Marie (gestionnaire), Pierre, Sophie, Lucas (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(1, 1, 1), (1, 2, 0), (1, 3, 0), (1, 4, 0);

-- Projet 2: Emma (gestionnaire), Thomas, Léa, Hugo (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(2, 5, 1), (2, 6, 0), (2, 7, 0), (2, 8, 0);

-- Projet 3: Chloé (gestionnaire), Alexandre, Camille (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(3, 9, 1), (3, 10, 0), (3, 11, 0);

-- Projet 4: Nathan (gestionnaire), Julie, Antoine, Manon (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(4, 12, 1), (4, 13, 0), (4, 14, 0), (4, 15, 0);

-- Projet 5: Maxime (gestionnaire), Laura, Nicolas (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(5, 16, 1), (5, 17, 0), (5, 18, 0);

-- Projet 6: Sarah (gestionnaire), Julien, Marie, Pierre (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(6, 19, 1), (6, 20, 0), (6, 1, 0), (6, 2, 0);

-- Projet 7: Sophie (gestionnaire), Lucas, Emma, Thomas (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(7, 3, 1), (7, 4, 0), (7, 5, 0), (7, 6, 0);

-- Projet 8: Léa (gestionnaire), Hugo, Chloé (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(8, 7, 1), (8, 8, 0), (8, 9, 0);

-- Projet 9: Alexandre (gestionnaire), Camille, Nathan, Julie (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(9, 10, 1), (9, 11, 0), (9, 12, 0), (9, 13, 0);

-- Projet 10: Antoine (gestionnaire), Manon, Maxime, Laura (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(10, 14, 1), (10, 15, 0), (10, 16, 0), (10, 17, 0);
-- Projet 11: Kevin (gestionnaire), Celine, Thomas (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(11, 31, 1), (11, 32, 0), (11, 33, 0);

-- Projet 12: Marion (gestionnaire), Julien, Audrey (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(12, 34, 1), (12, 35, 0), (12, 36, 0);

-- Projet 13: Bastien (gestionnaire), Pauline, Romain (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(13, 37, 1), (13, 38, 0), (13, 39, 0);

-- Projet 14: Laura (gestionnaire), Florian, Sandrine (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(14, 40, 1), (14, 41, 0), (14, 42, 0);

-- Projet 15: Antoine (gestionnaire), Nathalie, Mathis (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(15, 43, 1), (15, 44, 0), (15, 45, 0);

-- Projet 16: Aurore (gestionnaire), Julien, Lucie (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(16, 46, 1), (16, 47, 0), (16, 48, 0);

-- Projet 17: Jordan (gestionnaire), Isabelle, Julien (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(17, 49, 1), (17, 50, 0), (17, 51, 0);

-- Projet 18: Lucie (gestionnaire), Kevin, Celine (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(18, 48, 1), (18, 31, 0), (18, 32, 0);

-- Projet 19: Thomas (gestionnaire), Marion, Bastien (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(19, 33, 1), (19, 34, 0), (19, 37, 0);

-- Projet 20: Pauline (gestionnaire), Romain, Laura (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(20, 38, 1), (20, 39, 0), (20, 40, 0);

-- Projet 21: Florian (gestionnaire), Sandrine, Antoine (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(21, 41, 1), (21, 42, 0), (21, 43, 0);

-- Projet 22: Nathalie (gestionnaire), Mathis, Aurore (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(22, 44, 1), (22, 45, 0), (22, 46, 0);

-- Projet 23: Julien (gestionnaire), Lucie, Jordan (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(23, 47, 1), (23, 48, 0), (23, 49, 0);

-- Projet 24: Isabelle (gestionnaire), Julien, Kevin (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(24, 50, 1), (24, 51, 0), (24, 31, 0);

-- Projet 25: Celine (gestionnaire), Thomas, Marion (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(25, 32, 1), (25, 33, 0), (25, 34, 0);

-- Projet 26: Bastien (gestionnaire), Pauline, Romain (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(26, 37, 1), (26, 38, 0), (26, 39, 0);

-- Projet 27: Laura (gestionnaire), Florian, Sandrine (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(27, 40, 1), (27, 41, 0), (27, 42, 0);

-- Projet 28: Antoine (gestionnaire), Nathalie, Mathis (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(28, 43, 1), (28, 44, 0), (28, 45, 0);

-- Projet 29: Aurore (gestionnaire), Julien, Lucie (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(29, 46, 1), (29, 47, 0), (29, 48, 0);

-- Projet 30: Jordan (gestionnaire), Isabelle, Julien (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(30, 49, 1), (30, 50, 0), (30, 51, 0);

-- ========================================
-- INSERTION DES EXPÉRIENCES
-- ========================================
-- Validation: 0=non validée, 1=validée
-- Statut_experience: 0=non commencé, 1=en cours, 2=terminée
INSERT IGNORE INTO experience (Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Statut_experience, Date_de_creation, Date_de_modification) VALUES
-- Expériences du Projet 1
('Synthèse de nanoparticules d''or', 1, 'Production de nanoparticules d''or par réduction chimique pour tests de ciblage cellulaire.', '2024-11-25', '09:00:00', '12:00:00', 'Nanoparticules de 20nm obtenues avec succès. Rendement: 85%.', 2, '2024-11-10', '2024-11-15'),
('Test de cytotoxicité in vitro', 1, 'Évaluation de la toxicité des nanoparticules sur lignées cellulaires cancéreuses.', '2024-11-26', '14:00:00', '18:00:00', NULL, 1, '2024-11-11', '2024-11-18'),
('Imagerie par microscopie électronique', 1, 'Caractérisation morphologique des nanoparticules synthétisées.', '2024-11-27', '10:00:00', '13:00:00', NULL, 0, '2024-11-12', '2024-11-20'),

-- Expériences du Projet 2
('Collecte de données climatiques', 1, 'Récupération et nettoyage des données météorologiques sur 20 ans.', '2024-11-25', '08:00:00', '17:00:00', 'Dataset de 2 millions d''entrées préparé et validé.', 2, '2024-11-05', '2024-11-15'),
('Entraînement modèle ML', 1, 'Formation d''un réseau de neurones pour la prédiction de températures.', '2024-11-28', '09:00:00', '16:00:00', NULL, 1, '2024-11-06', '2024-11-19'),

-- Expériences du Projet 3
('Extraction de cellulose', 1, 'Isolement de cellulose à partir de paille de blé.', '2024-11-24', '13:00:00', '17:00:00', 'Rendement d''extraction: 72%. Pureté satisfaisante.', 2, '2024-11-08', '2024-11-14'),
('Polymérisation enzymatique', 1, 'Synthèse de bioplastique par catalyse enzymatique.', '2024-11-29', '09:00:00', '15:00:00', NULL, 1, '2024-11-09', '2024-11-21'),
('Tests de biodégradabilité', 0, 'Mesure du taux de dégradation en conditions de compostage.', '2024-12-01', '10:00:00', '12:00:00', NULL, 0, '2024-11-10', '2024-11-25'),

-- Expériences du Projet 4
('Configuration intrication quantique', 1, 'Mise en place d''un système de paires de photons intriqués.', '2024-11-26', '08:30:00', '12:30:00', NULL, 1, '2024-11-07', '2024-11-17'),
('Test de cryptographie quantique', 1, 'Validation du protocole BB84 sur fibre optique.', '2024-11-30', '14:00:00', '18:00:00', NULL, 0, '2024-11-08', '2024-11-22'),

-- Expériences du Projet 5
('Programmation comportement robot', 1, 'Développement d''algorithmes d''évitement d''obstacles.', '2024-11-25', '10:00:00', '16:00:00', 'Navigation autonome fonctionnelle dans environnement contrôlé.', 2, '2024-11-05', '2024-11-15'),
('Interface homme-machine', 1, 'Conception d''une interface tactile intuitive pour seniors.', '2024-11-27', '13:00:00', '17:00:00', NULL, 1, '2024-11-06', '2024-11-18'),
('Tests en conditions réelles', 0, 'Validation du système en résidence pour personnes âgées.', '2024-12-02', '09:00:00', '12:00:00', NULL, 0, '2024-11-07', '2024-11-25'),

-- Expériences du Projet 6
('Dépôt couches minces', 1, 'Fabrication de cellules solaires par pulvérisation cathodique.', '2024-11-24', '09:00:00', '14:00:00', 'Couches uniformes obtenues. Épaisseur: 500nm.', 2, '2024-11-05', '2024-11-12'),
('Mesure de rendement photovoltaïque', 1, 'Caractérisation IV des cellules sous illumination standard.', '2024-11-28', '11:00:00', '15:00:00', NULL, 1, '2024-11-06', '2024-11-19'),

-- Expériences du Projet 7
('IRM fonctionnelle sujets témoins', 1, 'Acquisition d''images cérébrales pendant tâches mémorielles.', '2024-11-26', '09:00:00', '17:00:00', NULL, 1, '2024-11-07', '2024-11-18'),
('Analyse activation hippocampique', 1, 'Traitement des données d''imagerie et analyse statistique.', '2024-11-29', '10:00:00', '18:00:00', NULL, 0, '2024-11-08', '2024-11-21'),

-- Expériences du Projet 8
('Synthèse composés cuprates', 1, 'Production de cuprates supraconducteurs par voie solide.', '2024-11-25', '08:00:00', '16:00:00', 'Composé YBa2Cu3O7 obtenu. Phase pure confirmée par DRX.', 2, '2024-11-05', '2024-11-15'),
('Mesure température critique', 1, 'Détermination de Tc par mesure de résistivité électrique.', '2024-11-27', '09:00:00', '13:00:00', NULL, 1, '2024-11-06', '2024-11-18'),

-- Expériences du Projet 9
('Séquençage ADN 16S', 1, 'Identification des espèces bactériennes présentes dans échantillons.', '2024-11-24', '10:00:00', '18:00:00', 'Séquençage de 50 échantillons complété. Profondeur satisfaisante.', 2, '2024-11-05', '2024-11-12'),
('Analyse bio-informatique', 1, 'Traitement des données de séquençage et analyse de diversité.', '2024-11-28', '09:00:00', '17:00:00', NULL, 1, '2024-11-06', '2024-11-19'),

-- Expériences du Projet 10
('Conception PCB capteur', 1, 'Design du circuit imprimé pour capteur température/humidité.', '2024-11-25', '13:00:00', '18:00:00', 'PCB dessiné et envoyé en fabrication. Délai: 10 jours.', 2, '2024-11-05', '2024-11-15'),
('Test consommation énergétique', 1, 'Mesure de la consommation en différents modes de fonctionnement.', '2024-11-29', '10:00:00', '14:00:00', NULL, 1, '2024-11-06', '2024-11-18'),
-- Projet 11
('Analyse génomique avancée', 1, 'Pipeline de traitement de données séquencées.', '2024-12-03', '09:00:00', '12:00:00', NULL, 1, '2024-11-20', '2024-11-25'),
('Visualisation de mutations', 1, 'Cartographie des mutations sur génomes modèles.', '2024-12-04', '13:00:00', '17:00:00', NULL, 0, '2024-11-21', '2024-11-26'),

-- Projet 12
('Synthèse chimique verte', 1, 'Réactions utilisant solvants écologiques.', '2024-12-02', '10:00:00', '15:00:00', NULL, 1, '2024-11-19', '2024-11-23'),
('Analyse de rendement', 1, 'Mesure du rendement et pureté des produits.', '2024-12-05', '09:00:00', '12:00:00', NULL, 0, '2024-11-20', '2024-11-25'),

-- Projet 13
('Optimisation turbines', 1, 'Tests aérodynamiques en soufflerie.', '2024-12-01', '08:00:00', '14:00:00', NULL, 1, '2024-11-18', '2024-11-22'),
('Simulation numérique', 1, 'Validation des modèles CFD.', '2024-12-06', '09:00:00', '16:00:00', NULL, 0, '2024-11-21', '2024-11-26'),

-- Projet 14
('Plateforme RA éducative', 1, 'Développement de contenus interactifs.', '2024-12-03', '10:00:00', '16:00:00', NULL, 1, '2024-11-20', '2024-11-25'),
('Tests utilisateurs', 1, 'Feedback sur l''ergonomie et immersion.', '2024-12-07', '09:00:00', '12:00:00', NULL, 0, '2024-11-22', '2024-11-27'),

-- Projet 15
('Activation lymphocytes T', 1, 'Expérimentation sur cultures cellulaires.', '2024-12-04', '09:00:00', '15:00:00', NULL, 1, '2024-11-21', '2024-11-26'),
('Analyse cytokines', 1, 'Mesure de sécrétion de cytokines par ELISA.', '2024-12-08', '10:00:00', '14:00:00', NULL, 0, '2024-11-23', '2024-11-28'),

-- Projet 16
('Nanorobots ciblés', 1, 'Tests de navigation dans microfluidique.', '2024-12-05', '08:00:00', '12:00:00', NULL, 1, '2024-11-22', '2024-11-27'),
('Évaluation biodistribution', 1, 'Suivi par microscopie confocale.', '2024-12-09', '09:00:00', '13:00:00', NULL, 0, '2024-11-24', '2024-11-29'),

-- Projet 17
('Analyse biomécanique', 1, 'Capture de mouvements et modélisation.', '2024-12-03', '08:00:00', '14:00:00', NULL, 1, '2024-11-20', '2024-11-25'),
('Étude fatigue musculaire', 1, 'Mesure EMG et récupération.', '2024-12-07', '09:00:00', '12:00:00', NULL, 0, '2024-11-22', '2024-11-27'),

-- Projet 18
('Neurofeedback VR', 1, 'Entraînement cognitif immersif.', '2024-12-06', '10:00:00', '16:00:00', NULL, 1, '2024-11-23', '2024-11-28'),
('Analyse EEG', 1, 'Traitement des données EEG collectées.', '2024-12-10', '09:00:00', '13:00:00', NULL, 0, '2024-11-25', '2024-11-30'),

-- Projet 19
('Électrolyse optimisée', 1, 'Fabrication et test de piles à hydrogène.', '2024-12-04', '08:00:00', '14:00:00', NULL, 1, '2024-11-22', '2024-11-27'),
('Mesure rendement', 1, 'Analyse efficacité conversion H2/O2.', '2024-12-08', '09:00:00', '12:00:00', NULL, 0, '2024-11-24', '2024-11-29'),

-- Projet 20
('Microfluidique analyse', 1, 'Tests de canaux et pompes intégrées.', '2024-12-05', '08:00:00', '12:00:00', NULL, 1, '2024-11-23', '2024-11-28'),
('Validation capteurs', 1, 'Détection de biomarqueurs modèle.', '2024-12-09', '09:00:00', '13:00:00', NULL, 0, '2024-11-25', '2024-11-30'),

-- Projet 21
('Cryogénie systèmes', 1, 'Refroidissement de détecteurs quantiques.', '2024-12-06', '08:00:00', '14:00:00', NULL, 1, '2024-11-23', '2024-11-28'),
('Mesure températures', 1, 'Surveillance T et stabilité.', '2024-12-10', '09:00:00', '12:00:00', NULL, 0, '2024-11-25', '2024-11-30'),

-- Projet 22
('Biodiversité urbaine', 1, 'Inventaire espèces et échantillonnage.', '2024-12-06', '08:00:00', '14:00:00', NULL, 1, '2024-11-23', '2024-11-28'),
('Analyse sol/eau', 1, 'Mesure pH, nutriments, contaminants.', '2024-12-10', '09:00:00', '12:00:00', NULL, 0, '2024-11-25', '2024-11-30'),

-- Projet 23
('Robotique chirurgicale', 1, 'Tests simulateur opératoire.', '2024-12-07', '08:00:00', '14:00:00', NULL, 1, '2024-11-24', '2024-11-29'),
('Validation précision', 1, 'Mesure mouvements et temps réaction.', '2024-12-11', '09:00:00', '12:00:00', NULL, 0, '2024-11-26', '2024-12-01'),

-- Projet 24
('Big Data santé', 1, 'Agrégation bases patients.', '2024-12-07', '08:00:00', '14:00:00', NULL, 1, '2024-11-24', '2024-11-29'),
('Modélisation prédictive', 1, 'Analyse régression/machine learning.', '2024-12-11', '09:00:00', '12:00:00', NULL, 0, '2024-11-26', '2024-12-01'),

-- Projet 25
('Matériaux composites', 1, 'Fabrication échantillons et tests mécaniques.', '2024-12-08', '08:00:00', '14:00:00', NULL, 1, '2024-11-25', '2024-11-30'),
('Analyse résistance', 1, 'Mesures traction et flexion.', '2024-12-12', '09:00:00', '12:00:00', NULL, 0, '2024-11-27', '2024-12-02'),

-- Projet 26
('Optimisation irrigation', 1, 'Tests capteurs et réseaux irrigation.', '2024-12-08', '08:00:00', '14:00:00', NULL, 1, '2024-11-25', '2024-11-30'),
('Analyse rendement cultures', 1, 'Mesure croissance et consommation eau.', '2024-12-12', '09:00:00', '12:00:00', NULL, 0, '2024-11-27', '2024-12-02'),

-- Projet 27
('Optogénétique neurones', 1, 'Activation contrôlée par lumière.', '2024-12-09', '08:00:00', '14:00:00', NULL, 1, '2024-11-26', '2024-12-01'),
('Mesure réponses neuronales', 1, 'Analyse calcium imaging.', '2024-12-13', '09:00:00', '12:00:00', NULL, 0, '2024-11-28', '2024-12-03'),

-- Projet 28
('Deep Learning IRM', 1, 'Entraînement modèles détection anomalies.', '2024-12-09', '08:00:00', '14:00:00', NULL, 1, '2024-11-26', '2024-12-01'),
('Validation prédiction', 1, 'Test sur dataset indépendant.', '2024-12-13', '09:00:00', '12:00:00', NULL, 0, '2024-11-28', '2024-12-03'),

-- Projet 29
('Transport autonome urbain', 1, 'Simulation circulation et capteurs.', '2024-12-10', '08:00:00', '14:00:00', NULL, 1, '2024-11-27', '2024-12-02'),
('Tests sécurité', 1, 'Scénarios imprévus et réaction véhicule.', '2024-12-14', '09:00:00', '12:00:00', NULL, 0, '2024-11-29', '2024-12-04'),

-- Projet 30
('Simulation climatique', 1, 'Modélisation températures et précipitations.', '2024-12-10', '08:00:00', '14:00:00', NULL, 1, '2024-11-27', '2024-12-02'),
('Analyse impacts', 1, 'Étude des conséquences sur biodiversité et agriculture.', '2024-12-14', '09:00:00', '12:00:00', NULL, 0, '2024-11-29', '2024-12-04');

-- ========================================
-- ASSOCIATION EXPÉRIENCES - EXPÉRIMENTATEURS
-- ========================================
-- Expérience 1 (Projet 1)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(1, 1), (1, 2), (1, 3);

-- Expérience 2 (Projet 1)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(2, 1), (2, 4);

-- Expérience 3 (Projet 1)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(3, 2), (3, 3);

-- Expérience 4 (Projet 2)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(4, 5), (4, 6);

-- Expérience 5 (Projet 2)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(5, 5), (5, 7), (5, 8);

-- Expérience 6 (Projet 3)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(6, 9), (6, 10);

-- Expérience 7 (Projet 3)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(7, 9), (7, 11);

-- Expérience 8 (Projet 3)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(8, 10), (8, 11);

-- Expérience 9 (Projet 4)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(9, 12), (9, 13), (9, 14);

-- Expérience 10 (Projet 4)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(10, 12), (10, 15);

-- Expérience 11 (Projet 5)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(11, 16), (11, 17);

-- Expérience 12 (Projet 5)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(12, 16), (12, 18);

-- Expérience 13 (Projet 5)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(13, 17), (13, 18);

-- Expérience 14 (Projet 6)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(14, 19), (14, 20);

-- Expérience 15 (Projet 6)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(15, 19), (15, 1);

-- Expérience 16 (Projet 7)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(16, 3), (16, 4), (16, 5);

-- Expérience 17 (Projet 7)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(17, 3), (17, 6);

-- Expérience 18 (Projet 8)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(18, 7), (18, 8);

-- Expérience 19 (Projet 8)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(19, 7), (19, 9);

-- Expérience 20 (Projet 9)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(20, 10), (20, 11), (20, 12);

-- Expérience 21 (Projet 9)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(21, 10), (21, 13);

-- Expérience 22 (Projet 10)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(22, 14), (22, 15);

-- Expérience 23 (Projet 10)
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(23, 14), (23, 16), (23, 17);

INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(24, 1), (24, 2), (24, 3), (24, 4);

INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(25, 1), (25, 4);

-- Expérience 26-27: Projet 12
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(26, 5), (26, 6), (26, 7),
(27, 5), (27, 7), (27, 8);

-- Expérience 28-29: Projet 13
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(28, 8), (28, 9), (28, 10),
(29, 8), (29, 10), (29, 11);

-- Expérience 30-31: Projet 14
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(30, 11), (30, 12),
(31, 11), (31, 13);

-- Expérience 32-33: Projet 15
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(32, 14), (32, 15),
(33, 14), (33, 16);

-- Expérience 34-35: Projet 16
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(34, 17), (34, 18),
(35, 17), (35, 19);

-- Expérience 36-37: Projet 17
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(36, 20), (36, 1),
(37, 20), (37, 2);

-- Expérience 38-39: Projet 18
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(38, 3), (38, 4),
(39, 3), (39, 5);

-- Expérience 40-41: Projet 19
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(40, 6), (40, 7),
(41, 6), (41, 8);

-- Expérience 42-43: Projet 20
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(42, 9), (42, 10),
(43, 9), (43, 11);

-- Expérience 44-45: Projet 21
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(44, 12), (44, 13),
(45, 12), (45, 14);

-- Expérience 46-47: Projet 22
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(46, 15), (46, 16),
(47, 15), (47, 17);

-- Expérience 48-49: Projet 23
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(48, 18), (48, 19),
(49, 18), (49, 20);

-- Expérience 50-51: Projet 24
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(50, 1), (50, 2),
(51, 1), (51, 3);

-- Expérience 52-53: Projet 25
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(52, 4), (52, 5),
(53, 4), (53, 6);

-- Expérience 54-55: Projet 26
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(54, 7), (54, 8),
(55, 7), (55, 9);

-- Expérience 56-57: Projet 27
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(56, 10), (56, 11),
(57, 10), (57, 12);

-- Expérience 58-59: Projet 28
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(58, 13), (58, 14),
(59, 13), (59, 15);

-- Expérience 60: Projet 29
INSERT IGNORE INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(60, 16), (60, 17);

-- ========================================
-- ASSOCIATION SALLES - EXPÉRIENCES
-- ========================================
-- Expérience 1: Salle A101 (chimie)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(1, 1), (1, 2), (1, 3);

-- Expérience 2: Salle A102 (biologie)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(2, 6), (2, 7), (2, 8);

-- Expérience 3: Salle A102
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(3, 6);

-- Expérience 4: Salle C301 (informatique)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(4, 21), (4, 22);

-- Expérience 5: Salle C301
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(5, 21), (5, 22);

-- Expérience 6: Salle A101
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(6, 1), (6, 2), (6, 4);

-- Expérience 7: Salle A101
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(7, 1), (7, 4);

-- Expérience 8: Salle A102
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(8, 7), (8, 10);

-- Expérience 9: Salle D401 (optique)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(9, 31), (9, 32), (9, 34);

-- Expérience 10: Salle D401
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(10, 31), (10, 34);

-- Expérience 11: Salle C302 (robotique)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(11, 26), (11, 27), (11, 30);

-- Expérience 12: Salle C302
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(12, 26), (12, 28);

-- Expérience 13: Salle C302
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(13, 26), (13, 29);

-- Expérience 14: Salle D402 (matériaux)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(14, 36), (14, 37);

-- Expérience 15: Salle D401
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(15, 33), (15, 35);

-- Expérience 16: Salle A102
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(16, 6), (16, 7);

-- Expérience 17: Salle C301
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(17, 21), (17, 22);

-- Expérience 18: Salle D402
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(18, 36), (18, 40);

-- Expérience 19: Salle D402
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(19, 36), (19, 39);

-- Expérience 20: Salle A102
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(20, 6), (20, 8), (20, 10);

-- Expérience 21: Salle C301
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(21, 21), (21, 22);

-- Expérience 22: Salle B202 (électronique)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(22, 16), (22, 18), (22, 19), (22, 20);

-- Expérience 23: Salle B201 (physique)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(23, 11), (23, 13);

INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(24, 1), (24, 2), (24, 3), (24, 4);

INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(25, 1), (25, 4);

-- Expérience 26-27: Projet 12 (Salle A102)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(26, 6), (26, 7), (26, 8),
(27, 6), (27, 8), (27, 9);

-- Expérience 28-29: Projet 13 (Salle B201)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(28, 11), (28, 12), (28, 13),
(29, 11), (29, 13), (29, 14);

-- Expérience 30-31: Projet 14 (Salle B202)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(30, 16), (30, 18), (30, 19),
(31, 16), (31, 19), (31, 20);

-- Expérience 32-33: Projet 15 (Salle C301)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(32, 21), (32, 22), (32, 23),
(33, 21), (33, 23), (33, 24);

-- Expérience 34-35: Projet 16 (Salle C302)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(34, 26), (34, 27),
(35, 26), (35, 28);

-- Expérience 36-37: Projet 17 (Salle D401)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(36, 31), (36, 32),
(37, 31), (37, 33);

-- Expérience 38-39: Projet 18 (Salle D402)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(38, 36), (38, 37),
(39, 36), (39, 38);

-- Expérience 40-41: Projet 19 (Salle A101)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(40, 1), (40, 2),
(41, 1), (41, 3);

-- Expérience 42-43: Projet 20 (Salle A102)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(42, 6), (42, 7),
(43, 6), (43, 8);

-- Expérience 44-45: Projet 21 (Salle B201)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(44, 11), (44, 12),
(45, 11), (45, 13);

-- Expérience 46-47: Projet 22 (Salle B202)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(46, 16), (46, 17),
(47, 16), (47, 18);

-- Expérience 48-49: Projet 23 (Salle C301)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(48, 21), (48, 22),
(49, 21), (49, 23);

-- Expérience 50-51: Projet 24 (Salle C302)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(50, 26), (50, 27),
(51, 26), (51, 28);

-- Expérience 52-53: Projet 25 (Salle D401)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(52, 31), (52, 32),
(53, 31), (53, 33);

-- Expérience 54-55: Projet 26 (Salle D402)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(54, 36), (54, 37), (54, 38),
(55, 36), (55, 38), (55, 39);

-- Expérience 56-57: Projet 27 (Salle A101)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(56, 1), (56, 2), (56, 3),
(57, 1), (57, 3), (57, 4);

-- Expérience 58-59: Projet 28 (Salle A102)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(58, 6), (58, 7), (58, 8),
(59, 6), (59, 8), (59, 9);

-- Expérience 60: Projet 29 (Salle B201)
INSERT IGNORE INTO materiel_experience (ID_experience, ID_materiel) VALUES
(60, 11), (60, 12);

-- ========================================
-- ASSOCIATION PROJETS - EXPÉRIENCES
-- ========================================
INSERT IGNORE INTO projet_experience (ID_projet, ID_experience) VALUES
(1, 1), (1, 2), (1, 3),
(2, 4), (2, 5),
(3, 6), (3, 7), (3, 8),
(4, 9), (4, 10),
(5, 11), (5, 12), (5, 13),
(6, 14), (6, 15),
(7, 16), (7, 17),
(8, 18), (8, 19),
(9, 20), (9, 21),
(10, 22), (10, 23),
(11, 24), (11, 25),
(12, 26), (12, 27),
(13, 28), (13, 29),
(14, 30), (14, 31),
(15, 32), (15, 33),
(16, 34), (16, 35),
(17, 36), (17, 37),
(18, 38), (18, 39),
(19, 40), (19, 41),
(20, 42), (20, 43),
(21, 44), (21, 45),
(22, 46), (22, 47),
(23, 48), (23, 49),
(24, 50), (24, 51),
(25, 52), (25, 53),
(26, 54), (26, 55),
(27, 56), (27, 57),
(28, 58), (28, 59),
(29, 60);



-- ========================================
-- ATTRIBUTION DES GESTIONNAIRES ET COLLABORATEURS
-- ========================================
-- Statut: 0=collaborateur, 1=gestionnaire
-- Projet 1: Marie (gestionnaire), Pierre, Sophie, Lucas (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(1, 1, 1), (1, 2, 0), (1, 3, 0), (1, 4, 0);

-- Projet 2: Emma (gestionnaire), Thomas, Léa, Hugo (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(2, 5, 1), (2, 6, 0), (2, 7, 0), (2, 8, 0);

-- Projet 3: Chloé (gestionnaire), Alexandre, Camille (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(3, 9, 1), (3, 10, 0), (3, 11, 0);

-- Projet 4: Nathan (gestionnaire), Julie, Antoine, Manon (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(4, 12, 1), (4, 13, 0), (4, 14, 0), (4, 15, 0);

-- Projet 5: Maxime (gestionnaire), Laura, Nicolas (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(5, 16, 1), (5, 17, 0), (5, 18, 0);

-- Projet 6: Sarah (gestionnaire), Julien, Marie, Pierre (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(6, 19, 1), (6, 20, 0), (6, 1, 0), (6, 2, 0);

-- Projet 7: Sophie (gestionnaire), Lucas, Emma, Thomas (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(7, 3, 1), (7, 4, 0), (7, 5, 0), (7, 6, 0);

-- Projet 8: Léa (gestionnaire), Hugo, Chloé (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(8, 7, 1), (8, 8, 0), (8, 9, 0);

-- Projet 9: Alexandre (gestionnaire), Camille, Nathan, Julie (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(9, 10, 1), (9, 11, 0), (9, 12, 0), (9, 13, 0);

-- Projet 10: Antoine (gestionnaire), Manon, Maxime, Laura (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(10, 14, 1), (10, 15, 0), (10, 16, 0), (10, 17, 0);

-- Projet 11: Compte 1 (gestionnaire), 2, 3, 4 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(11, 1, 1), (11, 2, 0), (11, 3, 0), (11, 4, 0);

-- Projet 12: Compte 5 (gestionnaire), 6, 7, 8 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(12, 5, 1), (12, 6, 0), (12, 7, 0), (12, 8, 0);

-- Projet 13: Compte 9 (gestionnaire), 10, 11 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(13, 9, 1), (13, 10, 0), (13, 11, 0);

-- Projet 14: Compte 12 (gestionnaire), 13, 14, 15 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(14, 12, 1), (14, 13, 0), (14, 14, 0), (14, 15, 0);

-- Projet 15: Compte 16 (gestionnaire), 17, 18 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(15, 16, 1), (15, 17, 0), (15, 18, 0);

-- Projet 16: Compte 19 (gestionnaire), 20, 1, 2 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(16, 19, 1), (16, 20, 0), (16, 1, 0), (16, 2, 0);

-- Projet 17: Compte 3 (gestionnaire), 4, 5, 6 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(17, 3, 1), (17, 4, 0), (17, 5, 0), (17, 6, 0);

-- Projet 18: Compte 7 (gestionnaire), 8, 9 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(18, 7, 1), (18, 8, 0), (18, 9, 0);

-- Projet 19: Compte 10 (gestionnaire), 11, 12, 13 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(19, 10, 1), (19, 11, 0), (19, 12, 0), (19, 13, 0);

-- Projet 20: Compte 14 (gestionnaire), 15, 16, 17 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(20, 14, 1), (20, 15, 0), (20, 16, 0), (20, 17, 0);

-- Projet 21: Compte 18 (gestionnaire), 19, 20 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(21, 18, 1), (21, 19, 0), (21, 20, 0);

-- Projet 22: Compte 1 (gestionnaire), 2, 3 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(22, 1, 1), (22, 2, 0), (22, 3, 0);

-- Projet 23: Compte 4 (gestionnaire), 5, 6 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(23, 4, 1), (23, 5, 0), (23, 6, 0);

-- Projet 24: Compte 7 (gestionnaire), 8, 9 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(24, 7, 1), (24, 8, 0), (24, 9, 0);

-- Projet 25: Compte 10 (gestionnaire), 11, 12 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(25, 10, 1), (25, 11, 0), (25, 12, 0);

-- Projet 26: Compte 13 (gestionnaire), 14, 15 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(26, 13, 1), (26, 14, 0), (26, 15, 0);

-- Projet 27: Compte 16 (gestionnaire), 17, 18 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(27, 16, 1), (27, 17, 0), (27, 18, 0);

-- Projet 28: Compte 19 (gestionnaire), 20, 1 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(28, 19, 1), (28, 20, 0), (28, 1, 0);

-- Projet 29: Compte 2 (gestionnaire), 3, 4 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(29, 2, 1), (29, 3, 0), (29, 4, 0);

-- Projet 30: Compte 5 (gestionnaire), 6, 7 (collaborateurs)
INSERT IGNORE INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(30, 5, 1), (30, 6, 0), (30, 7, 0);
