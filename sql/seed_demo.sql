-- Données de démo (exécuter une seule fois après : mysql ... < database.sql
-- E-mail de test : basma@test.com — mot de passe : password

USE abs_db;

INSERT INTO pays (nom, code_iso, continent) VALUES ('Maroc', 'MAR', 'Afrique');
SET @p_maroc = LAST_INSERT_ID();

INSERT INTO ville (nom, id_pays) VALUES ('Rabat', @p_maroc);
SET @v_rabat = LAST_INSERT_ID();

-- Musée=1, Parc=5 d’après l’ordre d’insert dans database.sql
INSERT INTO lieu (nom, description, latitude, longitude, id_categorie, id_ville) VALUES
('Musée de la Médina', 'Lieu de test pour l’équipe.', 34.02, -6.84, 1, @v_rabat);
SET @lieu1 = LAST_INSERT_ID();

INSERT INTO lieu (nom, description, latitude, longitude, id_categorie, id_ville) VALUES
('Parc National du Souffle', 'Lieu de test, deuxième lieu.', 34.08, -6.80, 5, @v_rabat);
SET @lieu2 = LAST_INSERT_ID();

INSERT INTO utilisateur (nom, prenom, email, password_hash, id_role) VALUES
('Test', 'Basma', 'basma@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);
SET @u = LAST_INSERT_ID();

-- Avis publics sur des lieux (alimente vue_classement_lieux et l’accueil)
INSERT INTO avis (note, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Super accueil et trés beaux parcours.', 'public', @u, @lieu1);

INSERT INTO avis (note, description, visibility, id_utilisateur, id_lieu) VALUES
(4, 'Agréable pour une fin de matinée.', 'public', @u, @lieu2);
