CREATE TABLE role (
id_role INT NOT NULL,
libelle VARCHAR(30) NOT NULL,
PRIMARY KEY (id_role)
);

CREATE TABLE utilisateur (
id_utilisateur INT NOT NULL,
nom VARCHAR(80) NOT NULL,
prenom VARCHAR(80) NOT NULL,
email VARCHAR(180) NOT NULL,
telephone VARCHAR(20),
password_hash VARCHAR(255) NOT NULL,
avatar_url VARCHAR(500),
bio TEXT,
created_at DATETIME NOT NULL,
id_role INT NOT NULL,
PRIMARY KEY (id_utilisateur),
FOREIGN KEY (id_role) REFERENCES role (id_role)
);

CREATE TABLE pays (
id_pays INT NOT NULL,
nom VARCHAR(100) NOT NULL,
code_iso CHAR(3) NOT NULL,
continent VARCHAR(50),
PRIMARY KEY (id_pays)
);

CREATE TABLE ville (
id_ville INT NOT NULL,
nom VARCHAR(120) NOT NULL,
id_pays INT NOT NULL,
PRIMARY KEY (id_ville),
FOREIGN KEY (id_pays) REFERENCES pays (id_pays)
);

CREATE TABLE categorie_lieu (
id_categorie INT NOT NULL,
libelle VARCHAR(60) NOT NULL,
PRIMARY KEY (id_categorie)
);

CREATE TABLE lieu (
id_lieu INT NOT NULL,
type VARCHAR(20) NOT NULL,
icon VARCHAR(10) NOT NULL,
nom VARCHAR(150) NOT NULL,
description TEXT,
latitude DECIMAL(9,6),
longitude DECIMAL(9,6),
adresse VARCHAR(255),
image_url VARCHAR(500),
id_categorie INT NOT NULL,
id_ville INT NOT NULL,
PRIMARY KEY (id_lieu),
FOREIGN KEY (id_categorie) REFERENCES categorie_lieu (id_categorie),
FOREIGN KEY (id_ville) REFERENCES ville (id_ville)
);

CREATE TABLE avis (
id_avis INT NOT NULL,
note INT NOT NULL,
titre VARCHAR(200),
description TEXT,
visibility VARCHAR(10) NOT NULL,
created_at DATETIME NOT NULL,
id_utilisateur INT NOT NULL,
id_pays INT,
id_ville INT,
id_lieu INT,
PRIMARY KEY (id_avis),
FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
FOREIGN KEY (id_pays) REFERENCES pays (id_pays),
FOREIGN KEY (id_ville) REFERENCES ville (id_ville),
FOREIGN KEY (id_lieu) REFERENCES lieu (id_lieu)
);

CREATE TABLE photo_avis (
id_photo INT NOT NULL,
url VARCHAR(500) NOT NULL,
ordre INT NOT NULL,
id_avis INT NOT NULL,
PRIMARY KEY (id_photo),
FOREIGN KEY (id_avis) REFERENCES avis (id_avis)
);

CREATE TABLE commentaire (
id_commentaire INT NOT NULL,
texte TEXT NOT NULL,
created_at DATETIME NOT NULL,
id_utilisateur INT NOT NULL,
id_avis INT NOT NULL,
id_parent INT,
PRIMARY KEY (id_commentaire),
FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
FOREIGN KEY (id_avis) REFERENCES avis (id_avis),
FOREIGN KEY (id_parent) REFERENCES commentaire (id_commentaire)
);

CREATE TABLE like_commentaire (
id_like INT NOT NULL,
created_at DATETIME NOT NULL,
id_utilisateur INT NOT NULL,
id_commentaire INT NOT NULL,
PRIMARY KEY (id_like),
FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
FOREIGN KEY (id_commentaire) REFERENCES commentaire (id_commentaire)
);

CREATE TABLE like_avis (
id_utilisateur INT NOT NULL,
id_avis INT NOT NULL,
created_at DATETIME NOT NULL,
PRIMARY KEY (id_utilisateur, id_avis),
FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
FOREIGN KEY (id_avis) REFERENCES avis (id_avis)
);

CREATE TABLE signalement (
id_signalement INT NOT NULL,
cible_type VARCHAR(15) NOT NULL,
cible_id INT NOT NULL,
motif VARCHAR(60) NOT NULL,
details VARCHAR(500),
statut VARCHAR(15) NOT NULL,
created_at DATETIME NOT NULL,
id_utilisateur INT NOT NULL,
PRIMARY KEY (id_signalement),
FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
);

CREATE TABLE visite (
id_visite INT NOT NULL,
date_visite DATE,
note_perso TEXT,
id_utilisateur INT NOT NULL,
id_pays INT,
id_ville INT,
id_lieu INT,
PRIMARY KEY (id_visite),
FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
FOREIGN KEY (id_pays) REFERENCES pays (id_pays),
FOREIGN KEY (id_ville) REFERENCES ville (id_ville),
FOREIGN KEY (id_lieu) REFERENCES lieu (id_lieu)
);

CREATE TABLE collection (
id_collection INT NOT NULL,
titre VARCHAR(150) NOT NULL,
description TEXT,
couverture_url VARCHAR(500),
visibility VARCHAR(10) NOT NULL,
created_at DATETIME NOT NULL,
id_utilisateur INT NOT NULL,
PRIMARY KEY (id_collection),
FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
);

CREATE TABLE collection_item (
id_item INT NOT NULL,
ordre INT NOT NULL,
created_at DATETIME NOT NULL,
id_collection INT NOT NULL,
id_pays INT,
id_ville INT,
id_lieu INT,
PRIMARY KEY (id_item),
FOREIGN KEY (id_collection) REFERENCES collection (id_collection),
FOREIGN KEY (id_pays) REFERENCES pays (id_pays),
FOREIGN KEY (id_ville) REFERENCES ville (id_ville),
FOREIGN KEY (id_lieu) REFERENCES lieu (id_lieu)
);
