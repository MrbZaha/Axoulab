-- Script de remplissage de données factices pour siteweb.sql
-- (Comptes, salles/matériel, projets, associations, expériences)

START TRANSACTION;

-- 1) Comptes (ID_compte explicites pour associations faciles)
INSERT INTO compte (ID_compte, Nom, Prenom, Date_de_naissance, Email, Mdp, Etat, validation) VALUES
(1, 'Admin', 'Principal', '1980-01-01', 'admin1@axoulab.fr', 'password', 3, 1),
(2, 'Dupont', 'Alice', '1996-03-12', 'alice.dupont@axoulab.fr', 'password', 1, 1),
(3, 'Martin', 'Bob', '1990-07-08', 'bob.martin@axoulab.fr', 'password', 2, 1),
(4, 'Bernard', 'Claire', '1988-11-02', 'claire.bernard@axoulab.fr', 'password', 2, 1),
(5, 'Leroy', 'David', '1998-05-20', 'david.leroy@axoulab.fr', 'password', 1, 1),
(6, 'Nguyen', 'Emma', '1995-12-30', 'emma.nguyen@axoulab.fr', 'password', 1, 1),
(7, 'Rossi', 'Francesco', '1985-04-11', 'francesco.rossi@axoulab.fr', 'password', 2, 1),
(8, 'Gonzalez', 'Hugo', '1993-09-17', 'hugo.gonzalez@axoulab.fr', 'password', 1, 1),
(9, 'Khan', 'Iman', '1992-02-26', 'iman.khan@axoulab.fr', 'password', 2, 1),
(10,'Moreau', 'Julie', '1997-06-06', 'julie.moreau@axoulab.fr', 'password', 1, 1),

-- quelques comptes en attente de validation (validation = 0)
(11,'Petit','Karim','1999-08-12','karim.petit@axoulab.fr','password',1,0),
(12,'Dubois','Laura','2000-10-02','laura.dubois@axoulab.fr','password',1,0),
(13,'Fischer','Mark','1991-03-03','mark.fischer@axoulab.fr','password',2,0),

-- un second admin optionnel
(14,'Admin','Secondaire','1979-02-14','admin2@axoulab.fr','password',3,1),

-- autres utilisateurs pour peupler projets/expériences
(15,'Gautier','Nina','1994-01-21','nina.gautier@axoulab.fr','password',1,1),
(16,'Lam','Oscar','1987-12-05','oscar.lam@axoulab.fr','password',2,1),
(17,'Ibrahim','Pasha','1984-07-30','pasha.ibrahim@axoulab.fr','password',2,1),
(18,'Silva','Rosa','1996-04-04','rosa.silva@axoulab.fr','password',1,1),
(19,'Sato','Takumi','1993-11-11','takumi.sato@axoulab.fr','password',2,1),
(20,'Olsen','Ulla','1990-09-09','ulla.olsen@axoulab.fr','password',1,1),
(21,'Vega','Luis','1995-08-08','luis.vega@axoulab.fr','password',1,1),
(22,'Becker','Mia','1998-05-30','mia.becker@axoulab.fr','password',1,1),
(23,'Kowalski','Jan','1986-02-02','jan.kowalski@axoulab.fr','password',2,1),
(24,'Ivanov','Olga','1989-06-16','olga.ivanov@axoulab.fr','password',2,1),
(25,'Singh','Raj','1992-12-12','raj.singh@axoulab.fr','password',2,1),
(26,'Brown','Zoe','1997-07-07','zoe.brown@axoulab.fr','password',1,1),
(27,'Ng','Wei','1991-10-10','wei.ng@axoulab.fr','password',2,1),
(28,'Chen','Ying','1994-03-03','ying.chen@axoulab.fr','password',1,1),
(29,'Martinez','Carlos','1990-01-01','carlos.martinez@axoulab.fr','password',2,1),
(30,'Lopez','Ana','1996-09-09','ana.lopez@axoulab.fr','password',1,1);

-- 2) Salles et matériel
-- On crée plusieurs salles, chacune avec plusieurs matériels
INSERT INTO salle_materiel (ID_materiel, Nom_Salle, Materiel) VALUES
(1,'Salle A','Microscope A1'),
(2,'Salle A','Centrifugeuse A2'),
(3,'Salle A','Pipettes A pack'),
(4,'Salle B','Oscilloscope B1'),
(5,'Salle B','Générateur B2'),
(6,'Salle B','Station PC B'),
(7,'Salle C','Chambre froide C1'),
(8,'Salle C','Agitateur C2'),
(9,'Salle C','Hotplate C3'),
(10,'Salle D','Spectromètre D1'),
(11,'Salle D','Balance D2'),
(12,'Salle E','Caméra E1'),
(13,'Salle E','Projecteur E2'),
(14,'Salle F','Imprimante 3D F1'),
(15,'Salle F','Station de soudure F2'),
(16,'Salle G','Analyseur G1'),
(17,'Salle G','Pompe G2'),
(18,'Salle H','Laser H1'),
(19,'Salle H','Optique H2'),
(20,'Salle I','Réseau I1'),
(21,'Salle I','Switch I2'),
(22,'Salle J','Table vibrante J1'),
(23,'Salle J','Enceinte J2');

-- 3) Projets (40 projets)
INSERT INTO projet (ID_projet, Nom_projet, Description, Confidentiel, Validation, Date_de_creation, Date_de_modification) VALUES
(1,'Projet Alpha','Description du projet Alpha',0,1,DATE_SUB(CURDATE(), INTERVAL 400 DAY), DATE_SUB(CURDATE(), INTERVAL 200 DAY)),
(2,'Projet Beta','Description du projet Beta',0,1,DATE_SUB(CURDATE(), INTERVAL 380 DAY), DATE_SUB(CURDATE(), INTERVAL 120 DAY)),
(3,'Projet Gamma','Description du projet Gamma',1,1,DATE_SUB(CURDATE(), INTERVAL 360 DAY), DATE_SUB(CURDATE(), INTERVAL 100 DAY)),
(4,'Projet Delta','Description du projet Delta',0,1,DATE_SUB(CURDATE(), INTERVAL 350 DAY), DATE_SUB(CURDATE(), INTERVAL 90 DAY)),
(5,'Projet Epsilon','Description du projet Epsilon',0,0,DATE_SUB(CURDATE(), INTERVAL 340 DAY), DATE_SUB(CURDATE(), INTERVAL 80 DAY)),
(6,'Projet Zeta','Description du projet Zeta',1,1,DATE_SUB(CURDATE(), INTERVAL 330 DAY), DATE_SUB(CURDATE(), INTERVAL 70 DAY)),
(7,'Projet Eta','Description du projet Eta',0,1,DATE_SUB(CURDATE(), INTERVAL 320 DAY), DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(8,'Projet Theta','Description du projet Theta',0,1,DATE_SUB(CURDATE(), INTERVAL 310 DAY), DATE_SUB(CURDATE(), INTERVAL 50 DAY)),
(9,'Projet Iota','Description du projet Iota',0,1,DATE_SUB(CURDATE(), INTERVAL 300 DAY), DATE_SUB(CURDATE(), INTERVAL 40 DAY)),
(10,'Projet Kappa','Description du projet Kappa',0,1,DATE_SUB(CURDATE(), INTERVAL 290 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(11,'Projet Lambda','Description du projet Lambda',0,1,DATE_SUB(CURDATE(), INTERVAL 280 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY)),
(12,'Projet Mu','Description du projet Mu',0,1,DATE_SUB(CURDATE(), INTERVAL 270 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(13,'Projet Nu','Description du projet Nu',0,0,DATE_SUB(CURDATE(), INTERVAL 260 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
(14,'Projet Xi','Description du projet Xi',0,1,DATE_SUB(CURDATE(), INTERVAL 250 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(15,'Projet Omicron','Description du projet Omicron',0,1,DATE_SUB(CURDATE(), INTERVAL 240 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(16,'Projet Pi','Description du projet Pi',0,1,DATE_SUB(CURDATE(), INTERVAL 230 DAY), DATE_SUB(CURDATE(), INTERVAL 9 DAY)),
(17,'Projet Rho','Description du projet Rho',0,1,DATE_SUB(CURDATE(), INTERVAL 220 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
(18,'Projet Sigma','Description du projet Sigma',0,1,DATE_SUB(CURDATE(), INTERVAL 210 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(19,'Projet Tau','Description du projet Tau',0,1,DATE_SUB(CURDATE(), INTERVAL 200 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(20,'Projet Upsilon','Description du projet Upsilon',1,1,DATE_SUB(CURDATE(), INTERVAL 190 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(21,'Projet Phi','Description du projet Phi',0,1,DATE_SUB(CURDATE(), INTERVAL 180 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(22,'Projet Chi','Description du projet Chi',0,1,DATE_SUB(CURDATE(), INTERVAL 170 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(23,'Projet Psi','Description du projet Psi',0,1,DATE_SUB(CURDATE(), INTERVAL 160 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(24,'Projet Omega','Description du projet Omega',0,1,DATE_SUB(CURDATE(), INTERVAL 150 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(25,'Projet A1','Description du projet A1',0,1,DATE_SUB(CURDATE(), INTERVAL 140 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(26,'Projet B2','Description du projet B2',0,1,DATE_SUB(CURDATE(), INTERVAL 130 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(27,'Projet C3','Description du projet C3',0,1,DATE_SUB(CURDATE(), INTERVAL 120 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(28,'Projet D4','Description du projet D4',0,1,DATE_SUB(CURDATE(), INTERVAL 110 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(29,'Projet E5','Description du projet E5',0,1,DATE_SUB(CURDATE(), INTERVAL 100 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(30,'Projet F6','Description du projet F6',0,1,DATE_SUB(CURDATE(), INTERVAL 90 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(31,'Projet G7','Description du projet G7',0,1,DATE_SUB(CURDATE(), INTERVAL 80 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(32,'Projet H8','Description du projet H8',0,1,DATE_SUB(CURDATE(), INTERVAL 70 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(33,'Projet I9','Description du projet I9',0,1,DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(34,'Projet J10','Description du projet J10',0,0,DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(35,'Projet K11','Description du projet K11',0,1,DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(36,'Projet L12','Description du projet L12',0,1,DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(37,'Projet M13','Description du projet M13',0,1,DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(38,'Projet N14','Description du projet N14',0,1,DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(39,'Projet O15','Description du projet O15',0,1,DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(40,'Projet P16','Description du projet P16',0,1,DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY));

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
