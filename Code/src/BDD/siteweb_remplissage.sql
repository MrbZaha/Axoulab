-- ========================================
-- Script de test pour la base de données
-- RÈGLE IMPORTANTE : Projets confidentiels = UNIQUEMENT des gestionnaires (Statut = 1)
--                    Projets non confidentiels = Gestionnaires (1) et Collaborateurs (0)
-- ========================================

-- Nettoie les données existantes (optionnel, décommentez si nécessaire)
DELETE FROM notification_experience;
DELETE FROM notification_projet;
DELETE FROM salle_experience;
DELETE FROM projet_experience;
DELETE FROM experience_experimentateur;
DELETE FROM experience;
DELETE FROM projet_collaborateur_gestionnaire;
DELETE FROM projet;
DELETE FROM compte;
DELETE FROM salle_materiel;

-- ========================================
-- INSERTION DES COMPTES (utilisateurs)
-- ========================================
INSERT INTO compte (ID_compte, Nom, Prenom, Date_de_naissance, Email, Mdp, Etat, Validation, Photo_de_profil) VALUES
(1, 'Dupont', 'Marie', '1990-05-15', 'marie.dupont@axoulab.fr', 'password123', 1, 1, NULL),
(2, 'Martin', 'Pierre', '1988-03-22', 'pierre.martin@axoulab.fr', 'password123', 2, 1, NULL),
(3, 'Bernard', 'Sophie', '1992-11-08', 'sophie.bernard@axoulab.fr', 'password123', 1, 1, NULL),
(4, 'Dubois', 'Lucas', '1995-07-30', 'lucas.dubois@axoulab.fr', 'password123', 1, 1, NULL),
(5, 'Thomas', 'Emma', '1991-02-14', 'emma.thomas@axoulab.fr', 'password123', 2, 1, NULL),
(6, 'Robert', 'Hugo', '1989-09-25', 'hugo.robert@axoulab.fr', 'password123', 1, 1, NULL),
(7, 'Petit', 'Léa', '1993-12-03', 'lea.petit@axoulab.fr', 'password123', 1, 1, NULL),
(8, 'Moreau', 'Antoine', '1987-06-18', 'antoine.moreau@axoulab.fr', 'password123', 1, 1, NULL),
(9, 'Leroy', 'Camille', '1994-04-12', 'camille.leroy@axoulab.fr', 'password123', 2, 1, NULL),
(10, 'Girard', 'Thomas', '1986-08-30', 'thomas.girard@axoulab.fr', 'password123', 3, 1, NULL);

-- ========================================
-- INSERTION DES SALLES
-- ========================================
INSERT INTO salle_materiel (ID_salle, Salle, Materiel) VALUES
(1, 'A101', 'Microscopes'),
(2, 'A101', 'Pipettes'),
(3, 'A102', 'Centrifugeuse'),
(4, 'A102', 'pH-mètre')
(5, 'B201', 'Spectrophotomètre'),
(6, 'B202', 'Chromatographe'),
(7, 'C301', 'Hotte chimique'),
(8, 'C301', 'Balance'),
(9, 'C302', 'PCR')
(10,'C302', 'Thermocycleur');

-- ========================================
-- INSERTION DES PROJETS
-- ========================================
INSERT INTO projet (ID_projet, Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification) VALUES
-- Projets NON confidentiels (peuvent avoir des collaborateurs)
(1, 'Étude Microbiologie', 'Analyse de la résistance bactérienne aux antibiotiques. Ce projet vise à identifier de nouvelles souches résistantes et à comprendre les mécanismes de résistance.', 0, 1, '2024-01-15', '2024-01-15'),
(2, 'Analyse Environnementale', 'Étude de la pollution des eaux par les microplastiques dans la région. Mesures et analyses sur plusieurs sites pendant 6 mois.', 0, 1, '2024-03-10', '2024-03-10'),
(3, 'Chimie des Matériaux', 'Développement de nouveaux polymères biodégradables pour remplacer les plastiques traditionnels.', 0, 1, '2024-05-20', '2024-05-20'),
(4, 'Toxicologie', 'Évaluation de la toxicité de nouveaux pesticides sur les organismes aquatiques.', 0, 1, '2024-07-08', '2024-07-08'),
(5, 'Biochimie Métabolique', 'Étude du métabolisme lipidique chez les patients diabétiques.', 0, 1, '2024-09-01', '2024-09-01'),

-- Projets CONFIDENTIELS (UNIQUEMENT des gestionnaires, PAS de collaborateurs)
(6, 'Synthèse Organique', 'Développement de nouvelles molécules pharmaceutiques pour le traitement du cancer. Projet en collaboration avec plusieurs laboratoires internationaux.', 1, 1, '2024-02-01', '2024-02-01'),
(7, 'Recherche Génétique', 'Étude des mutations génétiques liées aux maladies rares. Projet confidentiel financé par un consortium européen.', 1, 0, '2024-04-05', '2024-04-05'),
(8, 'Biotechnologie Appliquée', 'Production d\"enzymes industrielles par fermentation. Optimisation des conditions de culture.', 1, 1, '2024-06-12', '2024-06-12'),
(9, 'Nanotechnologie', 'Synthèse de nanoparticules pour applications médicales. Projet hautement confidentiel.', 1, 0, '2024-08-15', '2024-08-15'),
(10, 'Pharmacologie', 'Tests précliniques de nouveaux médicaments anti-inflammatoires.', 1, 1, '2024-10-10', '2024-10-10');

-- ========================================
-- ASSIGNATION DES GESTIONNAIRES ET COLLABORATEURS
-- Statut : 0 = Collaborateur, 1 = Gestionnaire
-- RÈGLE : Projets confidentiels = UNIQUEMENT Statut 1
-- ========================================

-- Projet 1 (NON confidentiel): Marie (gestionnaire), Pierre et Sophie (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(1, 1, 1), -- Marie = Gestionnaire
(1, 2, 0), -- Pierre = Collaborateur
(1, 3, 0); -- Sophie = Collaborateur

-- Projet 2 (NON confidentiel): Sophie (gestionnaire), Emma, Hugo et Camille (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(2, 3, 1), -- Sophie = Gestionnaire
(2, 5, 0), -- Emma = Collaborateur
(2, 6, 0), -- Hugo = Collaborateur
(2, 9, 0); -- Camille = Collaborateur

-- Projet 3 (NON confidentiel): Emma (gestionnaire), Léa, Antoine et Thomas (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(3, 5, 1),  -- Emma = Gestionnaire
(3, 7, 0),  -- Léa = Collaborateur
(3, 8, 0),  -- Antoine = Collaborateur
(3, 10, 0); -- Thomas = Collaborateur

-- Projet 4 (NON confidentiel): Léa (gestionnaire), Pierre et Sophie (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(4, 7, 1), -- Léa = Gestionnaire
(4, 2, 0), -- Pierre = Collaborateur
(4, 3, 0); -- Sophie = Collaborateur

-- Projet 5 (NON confidentiel): Marie (gestionnaire), Emma et Lucas (collaborateurs)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(5, 1, 1), -- Marie = Gestionnaire
(5, 5, 0), -- Emma = Collaborateur
(5, 4, 0); -- Lucas = Collaborateur

-- Projet 6 (CONFIDENTIEL): Pierre et Lucas (co-gestionnaires uniquement)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(6, 2, 1), -- Pierre = Gestionnaire
(6, 4, 1); -- Lucas = Gestionnaire (pas de collaborateurs!)

-- Projet 7 (CONFIDENTIEL): Lucas (gestionnaire seul)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(7, 4, 1); -- Lucas = Gestionnaire

-- Projet 8 (CONFIDENTIEL): Hugo et Antoine (co-gestionnaires)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(8, 6, 1), -- Hugo = Gestionnaire
(8, 8, 1); -- Antoine = Gestionnaire

-- Projet 9 (CONFIDENTIEL): Antoine, Camille et Thomas (co-gestionnaires)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(9, 8, 1),  -- Antoine = Gestionnaire
(9, 9, 1),  -- Camille = Gestionnaire
(9, 10, 1); -- Thomas = Gestionnaire

-- Projet 10 (CONFIDENTIEL): Pierre, Emma et Hugo (co-gestionnaires)
INSERT INTO projet_collaborateur_gestionnaire (ID_projet, ID_compte, Statut) VALUES
(10, 2, 1), -- Pierre = Gestionnaire
(10, 5, 1), -- Emma = Gestionnaire
(10, 6, 1); -- Hugo = Gestionnaire

-- ========================================
-- INSERTION DES EXPÉRIENCES
-- ========================================

-- Expériences pour Projet 1 (NON confidentiel - 5 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Fin_experience) VALUES
(1, 'Test de sensibilité aux antibiotiques', 1, 'Test de sensibilité aux antibiotiques sur E.coli. Méthode de diffusion en gélose avec 8 antibiotiques différents.', '2024-11-10', '09:00:00', '12:00:00', 'Résistance observée à l\"ampicilline. Sensibilité maintenue pour la ciprofloxacine.', 1),
(2, 'Culture bactérienne et isolation', 1, 'Culture bactérienne en milieu liquide puis isolation sur gélose nutritive. Purification par repiquages successifs.', '2024-11-12', '14:00:00', '17:00:00', 'Colonies isolées avec succès. Pureté confirmée par coloration de Gram.', 1),
(3, 'Analyse génétique des souches', 0, 'Séquençage du gène de résistance par méthode Sanger. Extraction d\"ADN et PCR des régions d\"intérêt.', '2024-11-15', '10:00:00', '16:00:00', NULL, 0),
(4, 'Test CMI', 1, 'Détermination de la concentration minimale inhibitrice par dilution en bouillon pour 5 antibiotiques.', '2024-11-18', '08:30:00', '11:30:00', 'CMI déterminée : 8 µg/mL pour la tétracycline.', 1),
(5, 'Validation PCR', 0, 'Validation des résultats par PCR quantitative. Amplification des gènes de résistance identifiés.', '2024-11-20', '13:00:00', '18:00:00', NULL, 0);

-- Expériences pour Projet 2 (NON confidentiel - 4 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(6, 'Prélèvement Site A', 1, 'Prélèvement d\"eau à la rivière principale. Filtration sur membrane 0.45µm et conservation à 4°C.', '2024-11-01', '09:00:00', '11:00:00', 'Concentration en microplastiques : 450 particules/L', 1),
(7, 'Prélèvement Site B', 1, 'Prélèvement à la confluence. Triple prélèvement pour validation statistique.', '2024-11-03', '09:00:00', '11:00:00', 'Concentration en microplastiques : 680 particules/L', 1),
(8, 'Analyse spectroscopique', 1, 'Identification des polymères par spectroscopie infrarouge FTIR.', '2024-11-06', '14:00:00', '18:00:00', 'Identification : 65% polyéthylène, 25% polypropylène, 10% autres.', 1),
(9, 'Prélèvement Site C', 0, 'Prélèvement en zone urbaine à proximité d\"une station d\"épuration.', '2024-11-25', '09:00:00', '11:00:00', NULL, 0);

-- Expériences pour Projet 3 (NON confidentiel - 6 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(10, 'Polymérisation PLA modifié', 1, 'Polymérisation du PLA avec ajout de plastifiants biosourcés. Contrôle strict température et pression.', '2024-10-20', '10:00:00', '14:00:00', 'Masse molaire Mw = 85 000 g/mol. Indice de polydispersité = 1.8', 1),
(11, 'Test biodégradabilité', 1, 'Test de biodégradabilité en milieu aqueux selon norme ISO 14851. Suivi pendant 30 jours.', '2024-10-25', '09:00:00', '12:00:00', 'Dégradation de 45% après 30 jours.', 1),
(12, 'Analyse thermogravimétrique', 1, 'Caractérisation thermique par ATG sous azote. Rampe de température 10°C/min.', '2024-10-28', '13:00:00', '16:00:00', 'Td = 285°C. Stabilité thermique satisfaisante.', 1),
(13, 'Test résistance mécanique', 1, 'Essai de traction sur éprouvettes normalisées. Machine universelle Instron.','2024-11-02', '10:00:00', '13:00:00', 'Module de Young = 1.2 GPa. Élongation à la rupture = 8%.', 1),
(14, 'Optimisation formulation', 0, 'Tests de différentes proportions de plastifiants pour améliorer les propriétés.', '2024-11-16', '08:00:00', '15:00:00', NULL, 0),
(15, 'Test compostage industriel', 0, 'Essai de compostabilité selon norme EN 13432. Conditions industrielles simulées.', '2024-11-24', '09:00:00', '12:00:00', NULL, 0);

-- Expériences pour Projet 4 (NON confidentiel - 4 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(16, 'Test aigu Daphnia', 1, 'Test de toxicité aiguë sur Daphnia magna selon norme OCDE 202.', '2024-10-15', '09:00:00', '17:00:00', 'CL50 (48h) = 12 mg/L. Toxicité modérée.', 1),
(17, 'Test chronique poissons', 1, 'Test chronique sur embryons de poissons zèbre. Exposition pendant 96h.', '2024-10-22', '08:00:00', '16:00:00', 'NOEC = 2 mg/L. Effets sur la reproduction observés.', 1),
(18, 'Bioaccumulation', 0, 'Étude de bioaccumulation dans les tissus musculaires et hépatiques.', '2024-11-19', '10:00:00', '15:00:00', NULL, 0),
(19, 'Analyse métabolites', 0, 'Identification des métabolites par chromatographie liquide couplée à la spectrométrie de masse.', '2024-11-27', '09:00:00', '14:00:00', NULL, 0);

-- Expériences pour Projet 5 (NON confidentiel - 3 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(20, 'Dosage triglycérides', 1, 'Dosage enzymatique des triglycérides plasmatiques sur 50 patients.', '2024-10-18', '09:00:00', '12:00:00', 'Moyenne patients diabétiques : 2.8 mmol/L vs contrôle : 1.2 mmol/L', 1),
(21, 'Chromatographie acides gras', 1, 'Analyse du profil en acides gras par chromatographie en phase gazeuse.', '2024-10-25', '13:00:00', '17:00:00', 'Profil lipidique altéré : augmentation des AG saturés.', 1),
(22, 'Western blot enzymes', 0, 'Détection des enzymes du métabolisme lipidique par Western blot.', '2024-11-23', '08:00:00', '15:00:00', NULL, 0);

-- Expériences pour Projet 6 (CONFIDENTIEL - 3 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(23, 'Synthèse composé XY-247', 1, 'Synthèse multi-étapes du composé principal. Purification par chromatographie sur colonne.','2024-11-05', '09:00:00', '15:00:00', 'Rendement de 78%. Pureté > 99% confirmée par HPLC.', 1),
(24, 'Test cytotoxicité in vitro', 1, 'Évaluation de la cytotoxicité sur 5 lignées cellulaires cancéreuses.','2024-11-08', '10:00:00', '14:00:00', 'IC50 = 2.3 µM sur cellules cancéreuses HeLa.', 1),
(25, 'Optimisation conditions', 0, 'Optimisation des conditions de réaction pour améliorer le rendement.',  '2024-11-22', '08:00:00', '12:00:00', NULL, 0);

-- Expériences pour Projet 7 (CONFIDENTIEL - 2 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(26, 'Séquençage patient 001', 0, 'Séquençage génomique complet par technologie NGS Illumina.', '2024-11-14', '08:00:00', '17:00:00', NULL, 0),
(27, 'Analyse mutations', 0, 'Analyse comparative des mutations entre patients et groupes témoins.', '2024-11-28', '09:00:00', '16:00:00', NULL, 0);

-- Expériences pour Projet 8 (CONFIDENTIEL - 1 expérience)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(28, 'Fermentation E.coli recombinant', 1, 'Fermentation en batch avec suivi en ligne de la biomasse et de l\"activité enzymatique.', '2024-11-07', '08:00:00', '18:00:00', 'Production enzyme : 2.5 g/L. Activité spécifique : 450 U/mg.', 1);

-- Expériences pour Projet 9 (CONFIDENTIEL - 2 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description,  Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(29, 'Synthèse nanoparticules or', 0, 'Synthèse de nanoparticules d_or par réduction chimique. Contrôle de la taille par spectroscopie UV-Vis.', '2024-11-11', '09:00:00', '13:00:00', NULL, 0),
(30, 'Fonctionnalisation surface', 0, 'Fonctionnalisation des nanoparticules avec des ligands bioactifs.', '2024-11-21', '10:00:00', '16:00:00', NULL, 0);

-- Expériences pour Projet 10 (CONFIDENTIEL - 5 expériences)
INSERT INTO experience (ID_experience, Nom, Validation, Description, Date_reservation, Heure_debut, Heure_fin, Resultat, Piece_jointe, Fin_experience) VALUES
(31, 'Test in vivo 10mg/kg', 1, 'Évaluation sur modèle murin d\"inflammation aiguë. Administration par voie orale.', '2024-10-12', '08:00:00', '18:00:00', 'Réduction de l\"inflammation de 65%. Aucun effet secondaire.', 1),
(32, 'Test in vivo 50mg/kg', 1, 'Test à dose élevée pour évaluation de la toxicité potentielle.', '2024-10-19', '08:00:00', '18:00:00', 'Réduction de l\"inflammation de 85%. Légère toxicité hépatique.' 1),
(33, 'Pharmacocinétique', 1, 'Étude de l\"absorption, distribution, métabolisme et excrétion (ADME).', '2024-10-26', '09:00:00', '16:00:00', 'Tmax = 2h. Biodisponibilité = 78%. Demi-vie = 6h.', 1),
(34, 'Toxicité répétée 28 jours', 0, 'Étude de toxicité subchronique selon directive OCDE 407.', '2024-11-17', '08:00:00', '17:00:00', NULL,  0),
(35, 'Évaluation génotoxicité', 0, 'Tests d\"Ames et test du micronoyau sur lymphocytes humains.', '2024-11-26', '10:00:00', '15:00:00', NULL, 0);

-- ========================================
-- LIAISON PROJETS-EXPÉRIENCES
-- ========================================
INSERT INTO projet_experience (ID_projet, ID_experience) VALUES
-- Projet 1
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
-- Projet 2
(2, 6), (2, 7), (2, 8), (2, 9),
-- Projet 3
(3, 10), (3, 11), (3, 12), (3, 13), (3, 14), (3, 15),
-- Projet 4
(4, 16), (4, 17), (4, 18), (4, 19),
-- Projet 5
(5, 20), (5, 21), (5, 22),
-- Projet 6
(6, 23), (6, 24), (6, 25),
-- Projet 7
(7, 26), (7, 27),
-- Projet 8
(8, 28),
-- Projet 9
(9, 29), (9, 30),
-- Projet 10
(10, 31), (10, 32), (10, 33), (10, 34), (10, 35);

-- ========================================
-- LIAISON EXPÉRIENCES-EXPÉRIMENTATEURS
-- ========================================

-- Projet 1 - Expériences (Marie gestionnaire, Pierre et Sophie collaborateurs)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(1, 1), (1, 2),    -- Exp 1: Marie et Pierre
(2, 3), (2, 2),    -- Exp 2: Sophie et Pierre
(3, 1),            -- Exp 3: Marie
(4, 2), (4, 3),    -- Exp 4: Pierre et Sophie
(5, 1), (5, 3);    -- Exp 5: Marie et Sophie

-- Projet 2 - Expériences (Sophie gestionnaire, Emma, Hugo et Camille collaborateurs)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(6, 3), (6, 5),    -- Exp 6: Sophie et Emma
(7, 5), (7, 6),    -- Exp 7: Emma et Hugo
(8, 3), (8, 9),    -- Exp 8: Sophie et Camille
(9, 6);            -- Exp 9: Hugo

-- Projet 3 - Expériences (Emma gestionnaire, Léa, Antoine et Thomas collaborateurs)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(10, 5), (10, 7),  -- Exp 10: Emma et Léa
(11, 7), (11, 8),  -- Exp 11: Léa et Antoine
(12, 5),           -- Exp 12: Emma
(13, 8), (13, 10), -- Exp 13: Antoine et Thomas
(14, 7), (14, 10), -- Exp 14: Léa et Thomas
(15, 5), (15, 8);  -- Exp 15: Emma et Antoine

-- Projet 4 - Expériences (Léa gestionnaire, Pierre et Sophie collaborateurs)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(16, 7), (16, 2),  -- Exp 16: Léa et Pierre
(17, 3), (17, 7),  -- Exp 17: Sophie et Léa
(18, 2),           -- Exp 18: Pierre
(19, 3);           -- Exp 19: Sophie

-- Projet 5 - Expériences (Marie gestionnaire, Emma et Lucas collaborateurs)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(20, 1), (20, 5),  -- Exp 20: Marie et Emma
(21, 5), (21, 4),  -- Exp 21: Emma et Lucas
(22, 1);           -- Exp 22: Marie

-- Projet 6 - Expériences CONFIDENTIEL (Pierre et Lucas gestionnaires)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(23, 2), (23, 4),  -- Exp 23: Pierre et Lucas
(24, 4),           -- Exp 24: Lucas
(25, 2);           -- Exp 25: Pierre

-- Projet 7 - Expériences CONFIDENTIEL (Lucas gestionnaire seul)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(26, 4),           -- Exp 26: Lucas
(27, 4);           -- Exp 27: Lucas

-- Projet 8 - Expériences CONFIDENTIEL (Hugo et Antoine gestionnaires)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(28, 6), (28, 8);  -- Exp 28: Hugo et Antoine

-- Projet 9 - Expériences CONFIDENTIEL (Antoine, Camille et Thomas gestionnaires)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(29, 8), (29, 9),  -- Exp 29: Antoine et Camille
(30, 9), (30, 10); -- Exp 30: Camille et Thomas

-- Projet 10 - Expériences CONFIDENTIEL (Pierre, Emma et Hugo gestionn
-- Projet 10 - Expériences CONFIDENTIEL (Pierre, Emma et Hugo gestionnaires)
INSERT INTO experience_experimentateur (ID_experience, ID_compte) VALUES
(31, 2), (31, 5),  -- Exp 31: Pierre et Emma
(32, 5), (32, 6),  -- Exp 32: Emma et Hugo
(33, 2), (33, 6),  -- Exp 33: Pierre et Hugo
(34, 2), (34, 5),  -- Exp 34: Pierre et Emma
(35, 5), (35, 6);  -- Exp 35: Emma et Hugo

-- ========================================
-- LIAISON SALLES-EXPÉRIENCES
-- ========================================
INSERT INTO salle_experience (ID_experience, ID_salle) VALUES
-- Salle A101 (Microscopes, Pipettes)
(1, 1), (2, 1), (13, 1), (16, 1), (17, 1), (22, 1), (31, 1), (32, 1), (34, 1),
-- Salle A102 (Centrifugeuse, pH-mètre)
(4, 2), (11, 2), (15, 2), (18, 2), (20, 2), (24, 2), (28, 2),
-- Salle B201 (Spectrophotomètre)
(3, 3), (5, 3), (8, 3), (19, 3), (35, 3),
-- Salle B202 (Chromatographe)
(6, 4), (7, 4), (12, 4), (21, 4), (33, 4),
-- Salle C301 (Hotte chimique, Balance)
(10, 5), (14, 5), (23, 5), (25, 5), (29, 5), (30, 5),
-- Salle C302 (PCR, Thermocycleur)
(26, 6), (27, 6);

-- ========================================
-- INSERTION DES NOTIFICATIONS EXPÉRIENCES
-- ========================================
INSERT INTO notification_experience (ID_compte_envoyeur, ID_compte_receveur, ID_experience, Type_notif, Date_envoi) VALUES
-- Notifications pour expériences en attente de validation
(1, 2, 3, 1, '2024-11-15 16:30:00'),  -- Marie notifie Pierre pour validation exp 3
(5, 3, 9, 1, '2024-11-25 11:30:00'),  -- Emma notifie Sophie pour validation exp 9
(7, 5, 14, 1, '2024-11-16 15:45:00'), -- Léa notifie Emma pour validation exp 14
(2, 7, 18, 1, '2024-11-19 15:20:00'), -- Pierre notifie Léa pour validation exp 18
(1, 5, 22, 1, '2024-11-23 15:10:00'), -- Marie notifie Emma pour validation exp 22
(4, 2, 25, 1, '2024-11-22 12:15:00'), -- Lucas notifie Pierre pour validation exp 25
(4, 6, 26, 1, '2024-11-14 17:30:00'), -- Lucas notifie Hugo pour validation exp 26
(8, 9, 29, 1, '2024-11-11 13:25:00'), -- Antoine notifie Camille pour validation exp 29
(9, 10, 30, 1, '2024-11-21 16:40:00'),-- Camille notifie Thomas pour validation exp 30
(2, 6, 34, 1, '2024-11-17 17:15:00'), -- Pierre notifie Hugo pour validation exp 34
(5, 2, 35, 1, '2024-11-26 15:30:00'); -- Emma notifie Pierre pour validation exp 35

-- ========================================
-- INSERTION DES NOTIFICATIONS PROJETS
-- ========================================
INSERT INTO notification_projet (ID_compte_envoyeur, ID_compte_receveur, ID_projet, Type_notif, Date_envoi) VALUES
-- Notifications pour projets en attente de validation
(3, 1, 2, 2, '2024-03-10 14:20:00'),   -- Sophie notifie Marie pour validation projet 2
(5, 3, 3, 2, '2024-05-20 11:15:00'),   -- Emma notifie Sophie pour validation projet 3
(7, 5, 4, 2, '2024-07-08 09:45:00'),   -- Léa notifie Emma pour validation projet 4
(1, 7, 5, 2, '2024-09-01 10:30:00'),   -- Marie notifie Léa pour validation projet 5
(2, 4, 6, 2, '2024-02-01 16:25:00'),   -- Pierre notifie Lucas pour validation projet 6
(4, 6, 7, 2, '2024-04-05 13:40:00'),   -- Lucas notifie Hugo pour validation projet 7
(6, 8, 8, 2, '2024-06-12 15:20:00'),   -- Hugo notifie Antoine pour validation projet 8
(8, 10, 9, 2, '2024-08-15 11:10:00'),  -- Antoine notifie Thomas pour validation projet 9
(2, 5, 10, 2, '2024-10-10 14:50:00'); -- Pierre notifie Emma pour validation projet 10

-- ========================================
-- INSERTION DES PIECES JOINTES
-- ========================================

INSERT INTO experience_fichier (ID_experience,path_file) VALUES
(2, 'rapport_culture.pdf'),
(7, 'site_a_analyse.pdf'),
(10, 'pla_caracterisation.pdf'),
(17, 'zebrafish_toxicity.pdf'),
(21, 'lipid_profile.pdf'),
(23, 'synthese_xy247.pdf'),
(28,  'fermentation_rapport.pdf'),
(31,  'preclinical_10mg.pdf');





