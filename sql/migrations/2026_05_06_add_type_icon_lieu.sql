-- Ajout des colonnes type et icon à la table lieu (carte interactive — pins typés)
ALTER TABLE lieu
  ADD COLUMN type ENUM('pays', 'ville', 'monument') NOT NULL DEFAULT 'monument' AFTER id_lieu,
  ADD COLUMN icon VARCHAR(10) NOT NULL DEFAULT '📍' AFTER type;
