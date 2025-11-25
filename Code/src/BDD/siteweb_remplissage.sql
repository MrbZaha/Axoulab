-- ========================================
-- INSERTION DES COMPTES (20 personnes)
-- ========================================
-- Etat: 1=étudiant, 2=professeur/chercheur, 3=ADMIN
-- Validation: 1=Validé, 0=Non validé
INSERT INTO compte (Nom, Prenom, Date_de_naissance, Email, Mdp, Etat, validation) VALUES
('Dubois', 'Marie', '1988-03-15', 'marie.dubois@lab.fr', 'hash123', 2, 1),
('Martin', 'Pierre', '1992-07-22', 'pierre.martin@lab.fr', 'hash456', 1, 1),
('Bernard', 'Sophie', '1985-11-08', 'sophie.bernard@lab.fr', 'hash789', 2, 1),
('Petit', 'Lucas', '1990-05-12', 'lucas.petit@lab.fr', 'hash101', 1, 1),
('Robert', 'Emma', '1987-09-30', 'emma.robert@lab.fr', 'hash102', 2, 1),
('Richard', 'Thomas', '1995-01-18', 'thomas.richard@lab.fr', 'hash103', 1, 1),
('Durand', 'Léa', '1989-12-25', 'lea.durand@lab.fr', 'hash104', 2, 1),
('Leroy', 'Hugo', '1993-04-07', 'hugo.leroy@lab.fr', 'hash105', 1, 1),
('Moreau', 'Chloé', '1986-08-14', 'chloe.moreau@lab.fr', 'hash106', 2, 1),
('Simon', 'Alexandre', '1991-06-19', 'alexandre.simon@lab.fr', 'hash107', 1, 1),
('Laurent', 'Camille', '1994-02-28', 'camille.laurent@lab.fr', 'hash108', 1, 1),
('Lefebvre', 'Nathan', '1988-10-03', 'nathan.lefebvre@lab.fr', 'hash109', 2, 1),
('Michel', 'Julie', '1990-07-16', 'julie.michel@lab.fr', 'hash110', 1, 1),
('Garcia', 'Antoine', '1987-03-22', 'antoine.garcia@lab.fr', 'hash111', 2, 1),
('David', 'Manon', '1992-11-09', 'manon.david@lab.fr', 'hash112', 1, 1),
('Bertrand', 'Maxime', '1989-05-27', 'maxime.bertrand@lab.fr', 'hash113', 2, 1),
('Roux', 'Laura', '1991-09-14', 'laura.roux@lab.fr', 'hash114', 1, 1),
('Vincent', 'Nicolas', '1986-12-01', 'nicolas.vincent@lab.fr', 'hash115', 2, 1),
('Fournier', 'Sarah', '1993-08-20', 'sarah.fournier@lab.fr', 'hash116', 1, 1),
('Girard', 'Julien', '1990-04-11', 'julien.girard@lab.fr', 'hash117', 3, 1);

-- ========================================
-- INSERTION DES PROJETS
-- ========================================
-- Confidentiel: 0=non, 1=oui
-- Validation: 0=non validé, 1=validé
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
('Systèmes Embarqués IoT', 'Conception de capteurs ultra-basse consommation pour l''Internet des Objets.', 0, 1, '2024-03-15', '2024-11-20');

-- ========================================
-- INSERTION DES SALLES ET MATÉRIEL
-- ========================================
INSERT INTO salle_materiel (Salle, Materiel, Nombre) VALUES
-- Salle A101 - Chimie
('A101', 'Microscope optique', 5),
('A101', 'Bécher 500ml', 20),
('A101', 'Pipette automatique', 10),
('A101', 'Centrifugeuse', 2),
('A101', 'Balance de précision', 3),

-- Salle A102 - Biologie
('A102', 'Microscope électronique', 2),
('A102', 'Incubateur', 4),
('A102', 'Autoclave', 1),
('A102', 'Hotte à flux laminaire', 3),
('A102', 'Congélateur -80°C', 2),

-- Salle B201 - Physique
('B201', 'Oscilloscope', 8),
('B201', 'Générateur de signaux', 6),
('B201', 'Multimètre', 15),
('B201', 'Alimentation stabilisée', 10),
('B201', 'Spectromètre', 2),

-- Salle B202 - Électronique
('B202', 'Station de soudage', 12),
('B202', 'Analyseur de spectre', 3),
('B202', 'Fer à souder', 20),
('B202', 'Plaque de prototypage', 30),
('B202', 'Imprimante 3D', 2),

-- Salle C301 - Informatique
('C301', 'Serveur calcul haute performance', 5),
('C301', 'Station de travail GPU', 10),
('C301', 'Oscilloscope numérique', 4),
('C301', 'Analyseur logique', 3),
('C301', 'Switch réseau', 6),

-- Salle C302 - Robotique
('C302', 'Bras robotique', 3),
('C302', 'Capteur LIDAR', 5),
('C302', 'Caméra haute vitesse', 4),
('C302', 'Plateforme mobile', 2),
('C302', 'Contrôleur Arduino', 25),

-- Salle D401 - Optique
('D401', 'Laser He-Ne', 6),
('D401', 'Table optique', 4),
('D401', 'Spectrophotomètre', 3),
('D401', 'Fibre optique (rouleaux)', 10),
('D401', 'Détecteur CCD', 2),

-- Salle D402 - Matériaux
('D402', 'Four haute température', 2),
('D402', 'Machine de traction', 1),
('D402', 'Duromètre', 5),
('D402', 'Microscope MEB', 1),
('D402', 'Broyeur à billes', 3);

-- ========================================
-- INSERTION DES EXPÉRIENCES
-- ========================================
-- Validation: 0=non validée, 1=validée
-- Statut_experience: 0=non commencé, 1=en cours, 2=terminée
INSERT INTO experience (Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Statut_experience) VALUES
-- Expériences du Projet 1
('Synthèse de nanoparticules d''or', 1, 'Production de nanoparticules d''or par réduction chimique pour tests de ciblage cellulaire.', '2024-11-25', '09:00:00', '12:00:00', 'Nanoparticules de 20nm obtenues avec succès. Rendement: 85%.', 2),
('Test de cytotoxicité in vitro', 1, 'Évaluation de la toxicité des nanoparticules sur lignées cellulaires cancéreuses.', '2024-11-26', '14:00:00', '18:00:00', NULL, 1),
('Imagerie par microscopie électronique', 1, 'Caractérisation morphologique des nanoparticules synthétisées.', '2024-11-27', '10:00:00', '13:00:00', NULL, 0),

-- Expériences du Projet 2
('Collecte de données climatiques', 1, 'Récupération et nettoyage des données météorologiques sur 20 ans.', '2024-11-25', '08:00:00', '17:00:00', 'Dataset de 2 millions d''entrées préparé et validé.', 2),
('Entraînement modèle ML', 1, 'Formation d''un réseau de neurones pour la prédiction de températures.', '2024-11-28', '09:00:00', '16:00:00', NULL, 1),

-- Expériences du Projet 3
('Extraction de cellulose', 1, 'Isolement de cellulose à partir de paille de blé.', '2024-11-24', '13:00:00', '17:00:00', 'Rendement d''extraction: 72%. Pureté satisfaisante.', 2),
('Polymérisation enzymatique', 1, 'Synthèse de bioplastique par catalyse enzymatique.', '2024-11-29', '09:00:00', '15:00:00', NULL, 1),
('Tests de biodégradabilité', 0, 'Mesure du taux de dégradation en conditions de compostage.', '2024-12-01', '10:00:00', '12:00:00', NULL, 0),

-- Expériences du Projet 4
('Configuration intrication quantique', 1, 'Mise en place d''un système de paires de photons intriqués.', '2024-11-26', '08:30:00', '12:30:00', NULL, 1),
('Test de cryptographie quantique', 1, 'Validation du protocole BB84 sur fibre optique.', '2024-11-30', '14:00:00', '18:00:00', NULL, 0),

-- Expériences du Projet 5
('Programmation comportement robot', 1, 'Développement d''algorithmes d''évitement d''obstacles.', '2024-11-25', '10:00:00', '16:00:00', 'Navigation autonome fonctionnelle dans environnement contrôlé.', 2),
('Interface homme-machine', 1, 'Conception d''une interface tactile intuitive pour seniors.', '2024-11-27', '13:00:00', '17:00:00', NULL, 1),
('Tests en conditions réelles', 0, 'Validation du système en résidence pour personnes âgées.', '2024-12-02', '09:00:00', '12:00:00', NULL, 0),

-- Expériences du Projet 6
('Dépôt couches minces', 1, 'Fabrication de cellules solaires par pulvérisation cathodique.', '2024-11-24', '09:00:00', '14:00:00', 'Couches uniformes obtenues. Épaisseur: 500nm.', 2),
('Mesure de rendement photovoltaïque', 1, 'Caractérisation IV des cellules sous illumination standard.', '2024-11-28', '11:00:00', '15:00:00', NULL, 1),

-- Expériences du Projet 7
('IRM fonctionnelle sujets témoins', 1, 'Acquisition d''images cérébrales pendant tâches mémorielles.', '2024-11-26', '09:00:00', '17:00:00', NULL, 1),
('Analyse activation hippocampique', 1, 'Traitement des données d''imagerie et analyse statistique.', '2024-11-29', '10:00:00', '18:00:00', NULL, 0),

-- Expériences du Projet 8
('Synthèse composés cuprates', 1, 'Production de cuprates supraconducteurs par voie solide.', '2024-11-25', '08:00:00', '16:00:00', 'Composé YBa2Cu3O7 obtenu. Phase pure confirmée par DRX.', 2),
('Mesure température critique', 1, 'Détermination de Tc par mesure de résistivité électrique.', '2024-11-27', '09:00:00', '13:00:00', NULL, 1),

-- Expériences du Projet 9
('Séquençage ADN 16S', 1, 'Identification des espèces bactériennes présentes dans échantillons.', '2024-11-24', '10:00:00', '18:00:00', 'Séquençage de 50 échantillons complété. Profondeur satisfaisante.', 2),
('Analyse bio-informatique', 1, 'Traitement des données de séquençage et analyse de diversité.', '2024-11-28', '09:00:00', '17:00:00', NULL, 1),

-- Expériences du Projet 10
('Conception PCB capteur', 1, 'Design du circuit imprimé pour capteur température/humidité.', '2024-11-25', '13:00:00', '18:00:00', 'PCB dessiné et envoyé en fabrication. Délai: 10 jours.', 2),
('Test consommation énergétique', 1, 'Mesure de la consommation en différents modes de fonctionnement.', '2024-11-29', '10:00:00', '14:00:00', NULL, 1);

-- ========================================
-- ATTRIBUTION DES GESTIONNAIRES ET COLLABORATEURS
-- ========================================
-- Statut: 0=collaborateur, 1=gestionnaire
-- Projet 1: Marie (gestionnaire), Pierre, Sophie, Lucas (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(1, 1, 1), (1, 2, 0), (1, 3, 0), (1, 4, 0);

-- Projet 2: Emma (gestionnaire), Thomas, Léa, Hugo (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(2, 5, 1), (2, 6, 0), (2, 7, 0), (2, 8, 0);

-- Projet 3: Chloé (gestionnaire), Alexandre, Camille (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(3, 9, 1), (3, 10, 0), (3, 11, 0);

-- Projet 4: Nathan (gestionnaire), Julie, Antoine, Manon (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(4, 12, 1), (4, 13, 0), (4, 14, 0), (4, 15, 0);

-- Projet 5: Maxime (gestionnaire), Laura, Nicolas (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(5, 16, 1), (5, 17, 0), (5, 18, 0);

-- Projet 6: Sarah (gestionnaire), Julien, Marie, Pierre (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(6, 19, 1), (6, 20, 0), (6, 1, 0), (6, 2, 0);

-- Projet 7: Sophie (gestionnaire), Lucas, Emma, Thomas (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(7, 3, 1), (7, 4, 0), (7, 5, 0), (7, 6, 0);

-- Projet 8: Léa (gestionnaire), Hugo, Chloé (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(8, 7, 1), (8, 8, 0), (8, 9, 0);

-- Projet 9: Alexandre (gestionnaire), Camille, Nathan, Julie (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(9, 10, 1), (9, 11, 0), (9, 12, 0), (9, 13, 0);

-- Projet 10: Antoine (gestionnaire), Manon, Maxime, Laura (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(10, 14, 1), (10, 15, 0), (10, 16, 0), (10, 17, 0);

-- ========================================
-- ASSOCIATION EXPÉRIENCES - EXPÉRIMENTATEURS
-- ========================================
-- Expérience 1 (Projet 1)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(1, 1), (1, 2), (1, 3);

-- Expérience 2 (Projet 1)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(2, 1), (2, 4);

-- Expérience 3 (Projet 1)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(3, 2), (3, 3);

-- Expérience 4 (Projet 2)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(4, 5), (4, 6);

-- Expérience 5 (Projet 2)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(5, 5), (5, 7), (5, 8);

-- Expérience 6 (Projet 3)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(6, 9), (6, 10);

-- Expérience 7 (Projet 3)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(7, 9), (7, 11);

-- Expérience 8 (Projet 3)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(8, 10), (8, 11);

-- Expérience 9 (Projet 4)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(9, 12), (9, 13), (9, 14);

-- Expérience 10 (Projet 4)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(10, 12), (10, 15);

-- Expérience 11 (Projet 5)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(11, 16), (11, 17);

-- Expérience 12 (Projet 5)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(12, 16), (12, 18);

-- Expérience 13 (Projet 5)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(13, 17), (13, 18);

-- Expérience 14 (Projet 6)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(14, 19), (14, 20);

-- Expérience 15 (Projet 6)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(15, 19), (15, 1);

-- Expérience 16 (Projet 7)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(16, 3), (16, 4), (16, 5);

-- Expérience 17 (Projet 7)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(17, 3), (17, 6);

-- Expérience 18 (Projet 8)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(18, 7), (18, 8);

-- Expérience 19 (Projet 8)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(19, 7), (19, 9);

-- Expérience 20 (Projet 9)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(20, 10), (20, 11), (20, 12);

-- Expérience 21 (Projet 9)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(21, 10), (21, 13);

-- Expérience 22 (Projet 10)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(22, 14), (22, 15);

-- Expérience 23 (Projet 10)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(23, 14), (23, 16);

-- ========================================
-- ASSOCIATION PROJETS - EXPÉRIENCES
-- ========================================
INSERT INTO projet_experience (ID_projet, ID_experience) VALUES
(1, 1), (1, 2), (1, 3),
(2, 4), (2, 5),
(3, 6), (3, 7), (3, 8),
(4, 9), (4, 10),
(5, 11), (5, 12), (5, 13),
(6, 14), (6, 15),
(7, 16), (7, 17),
(8, 18), (8, 19),
(9, 20), (9, 21),
(10, 22), (10, 23);

-- ========================================
-- ASSOCIATION SALLES - EXPÉRIENCES
-- ========================================
-- Expérience 1: Salle A101 (chimie)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(1, 1), (1, 2), (1, 3);

-- Expérience 2: Salle A102 (biologie)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(2, 6), (2, 7), (2, 8);

-- Expérience 3: Salle A102
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(3, 6);

-- Expérience 4: Salle C301 (informatique)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(4, 21), (4, 22);

-- Expérience 5: Salle C301
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(5, 21), (5, 22);

-- Expérience 6: Salle A101
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(6, 1), (6, 2), (6, 4);

-- Expérience 7: Salle A101
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(7, 1), (7, 4);

-- Expérience 8: Salle A102
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(8, 7), (8, 10);

-- Expérience 9: Salle D401 (optique)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(9, 31), (9, 32), (9, 34);

-- Expérience 10: Salle D401
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(10, 31), (10, 34);

-- Expérience 11: Salle C302 (robotique)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(11, 26), (11, 27), (11, 30);

-- Expérience 12: Salle C302
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(12, 26), (12, 28);

-- Expérience 13: Salle C302
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(13, 26), (13, 29);

-- Expérience 14: Salle D402 (matériaux)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(14, 36), (14, 37);

-- Expérience 15: Salle D401
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(15, 33), (15, 35);

-- Expérience 16: Salle A102
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(16, 6), (16, 7);

-- Expérience 17: Salle C301
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(17, 21), (17, 22);

-- Expérience 18: Salle D402
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(18, 36), (18, 40);

-- Expérience 19: Salle D402
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(19, 36), (19, 39);

-- Expérience 20: Salle A102
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(20, 6), (20, 8), (20, 10);

-- Expérience 21: Salle C301
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(21, 21), (21, 22);

-- Expérience 22: Salle B202 (électronique)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(22, 16), (22, 18), (22, 19), (22, 20);

-- Expérience 23: Salle B201 (physique)
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
(23, 11), (23, 13);

-- ========================================
-- INSERTION DES NOTIFICATIONS
-- ========================================
-- Notifications pour expériences
INSERT INTO notification_experience (ID_compte_envoyeur, ID_compte_receveur, ID_experience, Type_notif, Date_envoi) VALUES
(1, 2, 1, 1, '2024-11-20 09:15:00'),
(2, 3, 1, 2, '2024-11-20 14:30:00'),
(5, 6, 4, 1, '2024-11-21 10:00:00'),
(9, 10, 6, 3, '2024-11-22 11:45:00'),
(12, 13, 9, 1, '2024-11-23 08:30:00'),
(16, 17, 11, 2, '2024-11-23 15:20:00'),
(3, 4, 16, 1, '2024-11-24 09:00:00'),
(7, 8, 18, 3, '2024-11-24 13:45:00');

-- Notifications pour projets
INSERT INTO notification_projet (ID_compte_envoyeur, ID_compte_receveur, ID_projet, Type_notif, Date_envoi) VALUES
(1, 2, 1, 1, '2024-11-15 10:30:00'),
(1, 3, 1, 1, '2024-11-15 10:31:00'),
(5, 6, 2, 2, '2024-11-16 14:00:00'),
(9, 10, 3, 1, '2024-11-17 09:15:00'),
(12, 13, 4, 3, '2024-11-18 11:30:00'),
(16, 17, 5, 1, '2024-11-19 08:45:00'),
(19, 20, 6, 2, '2024-11-20 15:00:00'),
(3, 4, 7, 1, '2024-11-21 10:20:00'),
(10, 11, 9, 3, '2024-11-22 13:00:00'),
(14, 15, 10, 1, '2024-11-23 09:30:00');