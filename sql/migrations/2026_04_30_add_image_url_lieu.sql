-- À exécuter une fois sur une base déjà créée avant l’ajout de la colonne image_url sur lieu.
-- Les nouvelles installations peuvent se contenter de database.sql à jour.

USE abs_db;

ALTER TABLE lieu
  ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER adresse;
