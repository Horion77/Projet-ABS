-- Migration : ajout de la colonne avatar sur la table utilisateur
-- Date : 2026-05-27

ALTER TABLE utilisateur
    ADD COLUMN avatar VARCHAR(500) NULL DEFAULT NULL;
