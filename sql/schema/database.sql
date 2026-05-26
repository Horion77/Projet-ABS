-- ============================================================
--  ABS — Application web
--  Script de création de la base de données
--  Moteur : MySQL 8.0+  |  Encodage : utf8mb4
-- ============================================================

DROP DATABASE IF EXISTS abs_db;

CREATE DATABASE IF NOT EXISTS abs_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE abs_db;

-- ============================================================
-- 1. ROLE
--    Gestion des droits : admin / moderateur / utilisateur
-- ============================================================
CREATE TABLE role (
  id_role      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  libelle      VARCHAR(30)     NOT NULL,
  PRIMARY KEY (id_role),
  UNIQUE KEY uq_role_libelle (libelle)
) ENGINE=InnoDB;

-- Données initiales
INSERT INTO role (libelle) VALUES ('admin'), ('moderateur'), ('utilisateur');


-- ============================================================
-- 2. UTILISATEUR
-- ============================================================
CREATE TABLE utilisateur (
  id_utilisateur  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  nom             VARCHAR(80)     NOT NULL,
  prenom          VARCHAR(80)     NOT NULL,
  email           VARCHAR(180)    NOT NULL,
  telephone       VARCHAR(20)     DEFAULT NULL,
  password_hash   VARCHAR(255)    NOT NULL,
  avatar_url      VARCHAR(500)    DEFAULT NULL,
  bio             TEXT            DEFAULT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_role         INT UNSIGNED    NOT NULL DEFAULT 3,    -- 'utilisateur' par défaut

  PRIMARY KEY (id_utilisateur),
  UNIQUE KEY uq_utilisateur_email (email),
  CONSTRAINT fk_utilisateur_role
    FOREIGN KEY (id_role) REFERENCES role (id_role)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_utilisateur_role ON utilisateur (id_role);


-- ============================================================
-- 3. PAYS
-- ============================================================
CREATE TABLE pays (
  id_pays     INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  nom         VARCHAR(100)    NOT NULL,
  code_iso    CHAR(3)         NOT NULL,
  continent   VARCHAR(50)     DEFAULT NULL,

  PRIMARY KEY (id_pays),
  UNIQUE KEY uq_pays_code_iso (code_iso),
  CONSTRAINT chk_pays_code_iso CHECK (CHAR_LENGTH(code_iso) = 3)
) ENGINE=InnoDB;


-- ============================================================
-- 4. VILLE
-- ============================================================
CREATE TABLE ville (
  id_ville    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  nom         VARCHAR(120)    NOT NULL,
  id_pays     INT UNSIGNED    NOT NULL,

  PRIMARY KEY (id_ville),
  CONSTRAINT fk_ville_pays
    FOREIGN KEY (id_pays) REFERENCES pays (id_pays)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_ville_pays ON ville (id_pays);


-- ============================================================
-- 5. CATEGORIE_LIEU
--    Ex : musée, restaurant, plage, monument, parc, hôtel…
-- ============================================================
CREATE TABLE categorie_lieu (
  id_categorie  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  libelle       VARCHAR(60)     NOT NULL,

  PRIMARY KEY (id_categorie),
  UNIQUE KEY uq_categorie_libelle (libelle)
) ENGINE=InnoDB;

-- Quelques catégories de base
INSERT INTO categorie_lieu (libelle) VALUES
  ('Musée'), ('Restaurant'), ('Plage'), ('Monument'), ('Parc'),
  ('Hôtel'), ('Bar'), ('Marché'), ('Site naturel'), ('Autre');


-- ============================================================
-- 6. LIEU
--    Point d'intérêt précis avec coordonnées GPS
-- ============================================================
CREATE TABLE lieu (
  id_lieu         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  type            ENUM('pays', 'ville', 'monument') NOT NULL DEFAULT 'monument',
  icon            VARCHAR(10)     NOT NULL DEFAULT '📍',
  nom             VARCHAR(150)    NOT NULL,
  description     TEXT            DEFAULT NULL,
  latitude        DECIMAL(9, 6)   DEFAULT NULL,
  longitude       DECIMAL(9, 6)   DEFAULT NULL,
  adresse         VARCHAR(255)    DEFAULT NULL,
  image_url       VARCHAR(500)    DEFAULT NULL,
  id_categorie    INT UNSIGNED    NOT NULL,
  id_ville        INT UNSIGNED    NOT NULL,

  PRIMARY KEY (id_lieu),
  CONSTRAINT fk_lieu_categorie
    FOREIGN KEY (id_categorie) REFERENCES categorie_lieu (id_categorie)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lieu_ville
    FOREIGN KEY (id_ville) REFERENCES ville (id_ville)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT chk_lieu_latitude
    CHECK (latitude  IS NULL OR (latitude  BETWEEN -90  AND 90)),
  CONSTRAINT chk_lieu_longitude
    CHECK (longitude IS NULL OR (longitude BETWEEN -180 AND 180))
) ENGINE=InnoDB;

CREATE INDEX idx_lieu_ville      ON lieu (id_ville);
CREATE INDEX idx_lieu_categorie  ON lieu (id_categorie);
-- Index spatial pour les recherches par proximité GPS
CREATE INDEX idx_lieu_coords     ON lieu (latitude, longitude);


-- ============================================================
-- 7. AVIS
--    Un avis cible EXACTEMENT un niveau géographique :
--    soit un pays, soit une ville, soit un lieu précis.
--    La contrainte CHECK enforce cette règle métier.
-- ============================================================
CREATE TABLE avis (
  id_avis         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  note            TINYINT         NOT NULL,
  titre           VARCHAR(200)    DEFAULT NULL,
  description     TEXT            DEFAULT NULL,
  visibility      ENUM('public','prive') NOT NULL DEFAULT 'public',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_utilisateur  INT UNSIGNED    NOT NULL,
  id_pays         INT UNSIGNED    DEFAULT NULL,
  id_ville        INT UNSIGNED    DEFAULT NULL,
  id_lieu         INT UNSIGNED    DEFAULT NULL,

  PRIMARY KEY (id_avis),

  -- Note entre 1 et 5
  CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5),

  -- Un utilisateur = un seul avis par pays / ville / lieu
  UNIQUE KEY uq_avis_user_pays  (id_utilisateur, id_pays),
  UNIQUE KEY uq_avis_user_ville (id_utilisateur, id_ville),
  UNIQUE KEY uq_avis_user_lieu  (id_utilisateur, id_lieu),

  CONSTRAINT fk_avis_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_avis_pays
    FOREIGN KEY (id_pays)  REFERENCES pays  (id_pays)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_avis_ville
    FOREIGN KEY (id_ville) REFERENCES ville (id_ville)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_avis_lieu
    FOREIGN KEY (id_lieu)  REFERENCES lieu  (id_lieu)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_avis_utilisateur ON avis (id_utilisateur);
CREATE INDEX idx_avis_pays        ON avis (id_pays);
CREATE INDEX idx_avis_ville       ON avis (id_ville);
CREATE INDEX idx_avis_lieu        ON avis (id_lieu);


-- ============================================================
-- 8. PHOTO_AVIS
--    Plusieurs photos par avis, triées par ordre d'affichage
-- ============================================================
CREATE TABLE photo_avis (
  id_photo    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  url         VARCHAR(500)    NOT NULL,
  ordre       TINYINT         NOT NULL DEFAULT 0,
  id_avis     INT UNSIGNED    NOT NULL,

  PRIMARY KEY (id_photo),
  CONSTRAINT fk_photo_avis
    FOREIGN KEY (id_avis) REFERENCES avis (id_avis)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_photo_avis ON photo_avis (id_avis);


-- ============================================================
-- 9. COMMENTAIRE
--    Commentaires sur un avis, avec support des threads
--    (id_parent = réponse à un commentaire parent)
-- ============================================================
CREATE TABLE commentaire (
  id_commentaire  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  texte           TEXT            NOT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_utilisateur  INT UNSIGNED    NOT NULL,
  id_avis         INT UNSIGNED    NOT NULL,
  id_parent       INT UNSIGNED    DEFAULT NULL,   -- NULL = commentaire racine

  PRIMARY KEY (id_commentaire),
  CONSTRAINT fk_commentaire_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_commentaire_avis
    FOREIGN KEY (id_avis) REFERENCES avis (id_avis)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_commentaire_parent
    FOREIGN KEY (id_parent) REFERENCES commentaire (id_commentaire)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_commentaire_avis        ON commentaire (id_avis);
CREATE INDEX idx_commentaire_utilisateur ON commentaire (id_utilisateur);
CREATE INDEX idx_commentaire_parent      ON commentaire (id_parent);


-- ============================================================
-- 10. LIKE_COMMENTAIRE
--     Un utilisateur ne peut liker un commentaire qu'une fois
-- ============================================================
CREATE TABLE like_commentaire (
  id_like         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_utilisateur  INT UNSIGNED    NOT NULL,
  id_commentaire  INT UNSIGNED    NOT NULL,

  PRIMARY KEY (id_like),
  UNIQUE KEY uq_like (id_utilisateur, id_commentaire),
  CONSTRAINT fk_like_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_like_commentaire
    FOREIGN KEY (id_commentaire) REFERENCES commentaire (id_commentaire)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_like_commentaire ON like_commentaire (id_commentaire);


-- ============================================================
-- 10b. LIKE_AVIS
--     Un utilisateur ne peut liker un avis qu'une seule fois.
--     Clé primaire composite (utilisateur + avis) = pas de doublon possible.
-- ============================================================
CREATE TABLE like_avis (
  id_utilisateur  INT UNSIGNED    NOT NULL,
  id_avis         INT UNSIGNED    NOT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id_utilisateur, id_avis),
  CONSTRAINT fk_like_avis_user
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_like_avis_avis
    FOREIGN KEY (id_avis) REFERENCES avis (id_avis)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_like_avis_avis ON like_avis (id_avis);


-- ============================================================
-- 11. VISITE
--     Journal de voyage : enregistre les lieux visités.
--     Même logique polymorphique que AVIS (pays / ville / lieu).
-- ============================================================
CREATE TABLE visite (
  id_visite       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  date_visite     DATE            DEFAULT NULL,
  note_perso      TEXT            DEFAULT NULL,   -- note privée, pas une note publique
  id_utilisateur  INT UNSIGNED    NOT NULL,
  id_pays         INT UNSIGNED    DEFAULT NULL,
  id_ville        INT UNSIGNED    DEFAULT NULL,
  id_lieu         INT UNSIGNED    DEFAULT NULL,

  PRIMARY KEY (id_visite),

  CONSTRAINT fk_visite_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_visite_pays
    FOREIGN KEY (id_pays)  REFERENCES pays  (id_pays)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_visite_ville
    FOREIGN KEY (id_ville) REFERENCES ville (id_ville)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_visite_lieu
    FOREIGN KEY (id_lieu)  REFERENCES lieu  (id_lieu)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_visite_utilisateur ON visite (id_utilisateur);
CREATE INDEX idx_visite_pays        ON visite (id_pays);
CREATE INDEX idx_visite_ville       ON visite (id_ville);
CREATE INDEX idx_visite_lieu        ON visite (id_lieu);


-- ============================================================
-- 12. COLLECTION
--     Listes publiques ou privées à la Letterboxd.
--     Ex : "Mes restos incontournables à Rome", "Wishlist Asie"
-- ============================================================
CREATE TABLE collection (
  id_collection   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  titre           VARCHAR(150)    NOT NULL,
  description     TEXT            DEFAULT NULL,
  couverture_url  VARCHAR(500)    DEFAULT NULL,
  visibility      ENUM('public','prive') NOT NULL DEFAULT 'public',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_utilisateur  INT UNSIGNED    NOT NULL,

  PRIMARY KEY (id_collection),
  CONSTRAINT fk_collection_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_collection_utilisateur ON collection (id_utilisateur);


-- ============================================================
-- 13. COLLECTION_ITEM
--     Éléments d'une collection.
--     Même logique polymorphique (pays / ville / lieu).
--     Un lieu peut appartenir à plusieurs collections.
--     L'ordre d'affichage est géré par la colonne `ordre`.
-- ============================================================
CREATE TABLE collection_item (
  id_item         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  ordre           SMALLINT        NOT NULL DEFAULT 0,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_collection   INT UNSIGNED    NOT NULL,
  id_pays         INT UNSIGNED    DEFAULT NULL,
  id_ville        INT UNSIGNED    DEFAULT NULL,
  id_lieu         INT UNSIGNED    DEFAULT NULL,

  PRIMARY KEY (id_item),

  -- Evite les doublons dans une même collection
  UNIQUE KEY uq_item_coll_pays  (id_collection, id_pays),
  UNIQUE KEY uq_item_coll_ville (id_collection, id_ville),
  UNIQUE KEY uq_item_coll_lieu  (id_collection, id_lieu),

  CONSTRAINT fk_item_collection
    FOREIGN KEY (id_collection) REFERENCES collection (id_collection)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_item_pays
    FOREIGN KEY (id_pays)  REFERENCES pays  (id_pays)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_item_ville
    FOREIGN KEY (id_ville) REFERENCES ville (id_ville)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_item_lieu
    FOREIGN KEY (id_lieu)  REFERENCES lieu  (id_lieu)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_item_collection ON collection_item (id_collection);


-- ============================================================
-- VUES UTILES
-- ============================================================

-- Classement des pays par note moyenne
CREATE OR REPLACE VIEW vue_classement_pays AS
SELECT
  p.id_pays,
  p.nom                           AS pays,
  p.continent,
  COUNT(a.id_avis)                AS nb_avis,
  ROUND(AVG(a.note), 2)           AS note_moyenne
FROM pays p
LEFT JOIN avis a ON a.id_pays = p.id_pays AND a.visibility = 'public'
GROUP BY p.id_pays, p.nom, p.continent
ORDER BY note_moyenne DESC;

-- Classement des villes par note moyenne
CREATE OR REPLACE VIEW vue_classement_villes AS
SELECT
  v.id_ville,
  v.nom                           AS ville,
  p.nom                           AS pays,
  COUNT(a.id_avis)                AS nb_avis,
  ROUND(AVG(a.note), 2)           AS note_moyenne
FROM ville v
JOIN pays p ON p.id_pays = v.id_pays
LEFT JOIN avis a ON a.id_ville = v.id_ville AND a.visibility = 'public'
GROUP BY v.id_ville, v.nom, p.nom
ORDER BY note_moyenne DESC;

-- Classement des lieux par note moyenne
CREATE OR REPLACE VIEW vue_classement_lieux AS
SELECT
  l.id_lieu,
  l.nom                           AS lieu,
  cl.libelle                      AS categorie,
  vi.nom                          AS ville,
  p.nom                           AS pays,
  l.latitude,
  l.longitude,
  COUNT(a.id_avis)                AS nb_avis,
  ROUND(AVG(a.note), 2)           AS note_moyenne
FROM lieu l
JOIN categorie_lieu cl ON cl.id_categorie = l.id_categorie
JOIN ville vi          ON vi.id_ville     = l.id_ville
JOIN pays p            ON p.id_pays       = vi.id_pays
LEFT JOIN avis a ON a.id_lieu = l.id_lieu AND a.visibility = 'public'
GROUP BY l.id_lieu, l.nom, cl.libelle, vi.nom, p.nom, l.latitude, l.longitude
ORDER BY note_moyenne DESC;
