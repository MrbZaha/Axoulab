CREATE TABLE compte (
    ID_compte BIGINT PRIMARY KEY AUTO_INCREMENT,
    Nom VARCHAR(64),
    Prenom VARCHAR(64),
    Date_de_naissance DATE,
    Email VARCHAR(64),
    Mdp VARCHAR(64),
    Etat TINYINT,
    validation TINYINT
) ENGINE=InnoDB;

CREATE TABLE projet (
    ID_projet BIGINT PRIMARY KEY AUTO_INCREMENT,
    Nom_projet VARCHAR(50),
    Description VARCHAR(2000),
    Confidentiel TINYINT,
    Validation TINYINT,
    Date_de_creation DATE,
    Date_de_modification DATE
) ENGINE=InnoDB;

CREATE TABLE experience (
    ID_experience BIGINT PRIMARY KEY AUTO_INCREMENT,
    Nom VARCHAR(500),
    Validation TINYINT DEFAULT 0,
    Description VARCHAR(10000),
    Date_reservation DATE,
    Heure_debut TIME,
    Heure_fin TIME,
    Resultat VARCHAR(4000),
    Fin_experience TINYINT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE salle_materiel (
    ID_salle BIGINT PRIMARY KEY AUTO_INCREMENT,
    Salle VARCHAR(64),
    Materiel VARCHAR(64)
) ENGINE=InnoDB;

CREATE TABLE experience_experimentateur (
    ID_experience BIGINT,
    ID_compte BIGINT,
    PRIMARY KEY (ID_experience, ID_compte),
    FOREIGN KEY (ID_experience) REFERENCES experience(ID_experience) ON DELETE CASCADE,
    FOREIGN KEY (ID_compte) REFERENCES compte(ID_compte) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE projet_collaborateur_gestionnaire (
    ID_projet BIGINT,
    ID_compte BIGINT,
    Statut TINYINT,
    PRIMARY KEY (ID_projet, ID_compte),
    FOREIGN KEY (ID_projet) REFERENCES projet(ID_projet) ON DELETE CASCADE,
    FOREIGN KEY (ID_compte) REFERENCES compte(ID_compte) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE projet_experience (
    ID_projet BIGINT,
    ID_experience BIGINT,
    PRIMARY KEY (ID_projet, ID_experience),
    FOREIGN KEY (ID_projet) REFERENCES projet(ID_projet) ON DELETE CASCADE,
    FOREIGN KEY (ID_experience) REFERENCES experience(ID_experience) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE salle_experience (
    ID_experience BIGINT,
    ID_salle BIGINT,
    PRIMARY KEY (ID_experience, ID_salle),
    FOREIGN KEY (ID_experience) REFERENCES experience(ID_experience) ON DELETE CASCADE,
    FOREIGN KEY (ID_salle) REFERENCES salle_materiel(ID_salle) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notification_experience (
    ID_notification_experience BIGINT PRIMARY KEY AUTO_INCREMENT,
    ID_compte_envoyeur BIGINT,
    ID_compte_receveur BIGINT,
    ID_experience BIGINT,
    Type_notif INT,
    Date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_compte_envoyeur) REFERENCES compte(ID_compte) ON DELETE CASCADE,
    FOREIGN KEY (ID_compte_receveur) REFERENCES compte(ID_compte) ON DELETE CASCADE,
    FOREIGN KEY (ID_experience) REFERENCES experience(ID_experience) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notification_projet (
    ID_notification_projet BIGINT PRIMARY KEY AUTO_INCREMENT,
    ID_compte_envoyeur BIGINT,
    ID_compte_receveur BIGINT,
    ID_projet BIGINT,
    Type_notif INT,
    Date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_compte_envoyeur) REFERENCES compte(ID_compte) ON DELETE CASCADE,
    FOREIGN KEY (ID_compte_receveur) REFERENCES compte(ID_compte) ON DELETE CASCADE,
    FOREIGN KEY (ID_projet) REFERENCES projet(ID_projet) ON DELETE CASCADE
) ENGINE=InnoDB;