CREATE TABLE szerepkor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nev VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE felhasznalo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nev VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    jelszo VARCHAR(255) NOT NULL,
    profilkep VARCHAR(255),
    mikor_keszult DATETIME DEFAULT CURRENT_TIMESTAMP,
    szerepkor_id INT,
    FOREIGN KEY (szerepkor_id) REFERENCES szerepkor(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE esemeny_kategoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nev VARCHAR(100) NOT NULL,
    szin VARCHAR(20) DEFAULT '#4caf50'
) ENGINE=InnoDB;

CREATE TABLE esemeny (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nev VARCHAR(255) NOT NULL,
    leiras TEXT,
    kezdet DATETIME NOT NULL,
    vege DATETIME NOT NULL,
    esemenykat_id INT,
    felhasznalo_id INT,
    FOREIGN KEY (esemenykat_id) REFERENCES esemeny_kategoria(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (felhasznalo_id) REFERENCES felhasznalo(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE csoport (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nev VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE csoport_tag (
    csoport_id INT,
    felhasznalo_id INT,
    PRIMARY KEY (csoport_id, felhasznalo_id),
    FOREIGN KEY (csoport_id) REFERENCES csoport(id)
        ON DELETE CASCADE,
    FOREIGN KEY (felhasznalo_id) REFERENCES felhasznalo(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE esemeny_tag (
    esemeny_id INT,
    felhasznalo_id INT,
    PRIMARY KEY (esemeny_id, felhasznalo_id),
    FOREIGN KEY (esemeny_id) REFERENCES esemeny(id)
        ON DELETE CASCADE,
    FOREIGN KEY (felhasznalo_id) REFERENCES felhasznalo(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE teendo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nev VARCHAR(255) NOT NULL,
    leiras TEXT,
    hatarido DATETIME,
    kesz BOOLEAN DEFAULT FALSE,
    felhasznalo_id INT,
    FOREIGN KEY (felhasznalo_id) REFERENCES felhasznalo(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO szerepkor (nev) VALUES
('Admin'),
('Felhasználó');