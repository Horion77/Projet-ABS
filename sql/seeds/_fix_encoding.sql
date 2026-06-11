-- Réparation d'encodage : corrige les accents corrompus par un import mysql.exe
-- effectué sans --default-character-set=utf8mb4 (fichier UTF-8 lu comme CP850).
-- La corruption est réversible : cp850 -> binaire -> utf8mb4.
--
-- On ne touche QUE les lignes contenant la signature de corruption
-- (caractères box-drawing ├ ┼ ┬ issus des octets de tête C3/C5/C2 de l'UTF-8),
-- donc les données déjà correctes (saisies via le web) ne sont pas modifiées.
--
-- À lancer ainsi (le charset n'a pas d'importance ici : aucune chaîne accentuée
-- n'est passée en littéral, tout se fait côté serveur) :
--   mysql -uroot -proot --default-character-set=utf8mb4 abs_db < sql/seeds/_fix_encoding.sql

USE abs_db;

SET @sig := 'E2949C|E294BC|E294AC';

UPDATE categorie_lieu SET libelle  = CONVERT(CAST(CONVERT(libelle     USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(libelle)     REGEXP @sig;
UPDATE pays        SET nom         = CONVERT(CAST(CONVERT(nom         USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(nom)         REGEXP @sig;
UPDATE pays        SET continent   = CONVERT(CAST(CONVERT(continent   USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(continent)   REGEXP @sig;
UPDATE ville       SET nom         = CONVERT(CAST(CONVERT(nom         USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(nom)         REGEXP @sig;

UPDATE lieu        SET nom         = CONVERT(CAST(CONVERT(nom         USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(nom)         REGEXP @sig;
UPDATE lieu        SET description = CONVERT(CAST(CONVERT(description USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(description) REGEXP @sig;
UPDATE lieu        SET adresse     = CONVERT(CAST(CONVERT(adresse     USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(adresse)     REGEXP @sig;

UPDATE avis        SET titre       = CONVERT(CAST(CONVERT(titre       USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(titre)       REGEXP @sig;
UPDATE avis        SET description = CONVERT(CAST(CONVERT(description USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(description) REGEXP @sig;

UPDATE commentaire SET texte       = CONVERT(CAST(CONVERT(texte       USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(texte)       REGEXP @sig;

UPDATE utilisateur SET nom         = CONVERT(CAST(CONVERT(nom         USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(nom)         REGEXP @sig;
UPDATE utilisateur SET prenom      = CONVERT(CAST(CONVERT(prenom      USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(prenom)      REGEXP @sig;
UPDATE utilisateur SET bio         = CONVERT(CAST(CONVERT(bio         USING cp850) AS BINARY) USING utf8mb4) WHERE HEX(bio)         REGEXP @sig;
