-- Ajout du titre optionnel sur les avis (plan Sara). Exécuter sur une BDD existante.

USE abs_db;

ALTER TABLE avis
  ADD COLUMN titre VARCHAR(200) DEFAULT NULL AFTER note;
