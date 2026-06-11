-- Seed : utilisateurs, pays, villes, lieux, avis, commentaires, likes
-- Données cohérentes pour le projet ABS
-- Mots de passe = bcrypt de "Password1" (tous les comptes test)

USE abs_db;

-- ============================================================
-- Utilisateurs fictifs
-- ============================================================

INSERT IGNORE INTO utilisateur (nom, prenom, email, password_hash, id_role) VALUES
('Martin',   'Lucas',   'lucas.martin@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Benali',   'Yasmine', 'yasmine.benali@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Dupont',   'Emma',    'emma.dupont@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Fernandez','Carlos',  'carlos.fernandez@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Tanaka',   'Yuki',    'yuki.tanaka@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Moreira',  'Ana',     'ana.moreira@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Klein',    'Jonas',   'jonas.klein@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Leroy',    'Camille', 'camille.leroy@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);


-- ============================================================
-- Pays
-- ============================================================

INSERT IGNORE INTO pays (nom, code_iso, continent) VALUES
('France',       'FRA', 'Europe'),
('Maroc',        'MAR', 'Afrique'),
('Japon',        'JPN', 'Asie'),
('Italie',       'ITA', 'Europe'),
('Espagne',      'ESP', 'Europe'),
('Portugal',     'PRT', 'Europe'),
('Pays-Bas',     'NLD', 'Europe'),
('États-Unis',   'USA', 'Amérique'),
('Grèce',        'GRC', 'Europe'),
('Thaïlande',    'THA', 'Asie');


-- ============================================================
-- Villes
-- ============================================================

INSERT IGNORE INTO ville (nom, id_pays) VALUES
('Paris',      (SELECT id_pays FROM pays WHERE code_iso = 'FRA')),
('Lyon',       (SELECT id_pays FROM pays WHERE code_iso = 'FRA')),
('Marrakech',  (SELECT id_pays FROM pays WHERE code_iso = 'MAR')),
('Casablanca', (SELECT id_pays FROM pays WHERE code_iso = 'MAR')),
('Tokyo',      (SELECT id_pays FROM pays WHERE code_iso = 'JPN')),
('Kyoto',      (SELECT id_pays FROM pays WHERE code_iso = 'JPN')),
('Rome',       (SELECT id_pays FROM pays WHERE code_iso = 'ITA')),
('Florence',   (SELECT id_pays FROM pays WHERE code_iso = 'ITA')),
('Barcelone',  (SELECT id_pays FROM pays WHERE code_iso = 'ESP')),
('Séville',    (SELECT id_pays FROM pays WHERE code_iso = 'ESP')),
('Lisbonne',   (SELECT id_pays FROM pays WHERE code_iso = 'PRT')),
('Porto',      (SELECT id_pays FROM pays WHERE code_iso = 'PRT')),
('Amsterdam',  (SELECT id_pays FROM pays WHERE code_iso = 'NLD')),
('New York',   (SELECT id_pays FROM pays WHERE code_iso = 'USA')),
('Athènes',    (SELECT id_pays FROM pays WHERE code_iso = 'GRC')),
('Bangkok',    (SELECT id_pays FROM pays WHERE code_iso = 'THA'));


-- ============================================================
-- Lieux (monuments / musées / restaurants…)
-- ============================================================

INSERT IGNORE INTO lieu (nom, type, icon, description, latitude, longitude, id_categorie, id_ville) VALUES

('Tour Eiffel',
 'monument', '📍',
 'Monument emblématique de Paris, construit en 1889 pour l''Exposition Universelle.',
 48.8584, 2.2945,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Paris' LIMIT 1)),

('Musée du Louvre',
 'monument', '📍',
 'Le plus grand musée d''art au monde, abritant la Joconde et la Vénus de Milo.',
 48.8606, 2.3376,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Musée' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Paris' LIMIT 1)),

('Jardin Majorelle',
 'monument', '📍',
 'Jardin botanique aux couleurs vives, ancienne propriété d''Yves Saint Laurent.',
 31.6419, -8.0030,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Parc' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Marrakech' LIMIT 1)),

('Place Jemaa el-Fna',
 'monument', '📍',
 'Grande place animée au cœur de Marrakech, classée au patrimoine immatériel de l''UNESCO.',
 31.6258, -7.9892,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Marrakech' LIMIT 1)),

('Temple de Sensoji',
 'monument', '📍',
 'Le plus ancien temple bouddhiste de Tokyo, dans le quartier d''Asakusa.',
 35.7148, 139.7967,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Tokyo' LIMIT 1)),

('Quartier Gion',
 'monument', '📍',
 'Quartier des geishas à Kyoto, avec ses maisons en bois et ses ruelles pavées.',
 35.0035, 135.7754,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Site naturel' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Kyoto' LIMIT 1)),

('Colosseum',
 'monument', '📍',
 'Amphithéâtre antique du Ier siècle, symbole de la Rome impériale.',
 41.8902, 12.4922,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Rome' LIMIT 1)),

('Sagrada Família',
 'monument', '📍',
 'Basilique inachevée de Gaudí, chef-d''œuvre de l''architecture moderniste catalan.',
 41.4036, 2.1744,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Barcelone' LIMIT 1)),

('Monastère des Hiéronymites',
 'monument', '📍',
 'Joyau de l''architecture manuéline à Belém, inscrit à l''UNESCO.',
 38.6977, -9.2064,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Lisbonne' LIMIT 1)),

('Rijksmuseum',
 'monument', '📍',
 'Musée national néerlandais avec les chefs-d''œuvre de Rembrandt et Vermeer.',
 52.3600, 4.8852,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Musée' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Amsterdam' LIMIT 1)),

('Central Park',
 'monument', '📍',
 'Immense parc urbain au cœur de Manhattan, poumon vert de New York.',
 40.7851, -73.9683,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Parc' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'New York' LIMIT 1)),

('Acropole d''Athènes',
 'monument', '📍',
 'Colline sacrée avec le Parthénon, symbole de la Grèce antique.',
 37.9715, 23.7267,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Athènes' LIMIT 1)),

('Wat Pho',
 'monument', '📍',
 'Temple du Bouddha couché à Bangkok, l''un des plus grands temples de Thaïlande.',
 13.7465, 100.4930,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Bangkok' LIMIT 1)),

('Offices de Florence',
 'monument', '📍',
 'L''un des plus grands musées d''art au monde avec la Naissance de Vénus de Botticelli.',
 43.7686, 11.2553,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Musée' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Florence' LIMIT 1)),

('Cathédrale de Séville',
 'monument', '📍',
 'La plus grande cathédrale gothique du monde, tombe de Christophe Colomb.',
 37.3861, -5.9925,
 (SELECT id_categorie FROM categorie_lieu WHERE libelle = 'Monument' LIMIT 1),
 (SELECT id_ville FROM ville WHERE nom = 'Séville' LIMIT 1));


-- ============================================================
-- Avis (55 avis, mélange public/privé, notes variées)
-- Variables locales pour les IDs utilisateurs et lieux
-- ============================================================

-- Tour Eiffel
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Un incontournable', 'Vue imprenable depuis le sommet, surtout au coucher du soleil. La file d''attente est longue mais ça vaut vraiment le coup.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Tour Eiffel')),

(4, 'Magnifique mais bondé', 'L''endroit est splendide, mais la foule en été est étouffante. Préférez y aller tôt le matin.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Tour Eiffel')),

(3, 'Décevant la nuit', 'Les illuminations durent seulement quelques minutes, puis plus rien. La montée vaut plus que le spectacle nocturne.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Tour Eiffel')),

(5, 'Mon rêve réalisé', 'J''en rêvais depuis enfant. La réalité dépasse l''imaginaire. Moment magique.', 'prive',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'ana.moreira@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Tour Eiffel'));


-- Musée du Louvre
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Une journée entière ne suffit pas', 'Tellement de chefs-d''œuvre que c''est presque écrasant. La Joconde est plus petite qu''on imagine.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Musée du Louvre')),

(4, 'Incontournable', 'La collection est phénoménale. Achetez les billets en ligne, la queue est interminable sinon.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yuki.tanaka@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Musée du Louvre')),

(2, 'Trop grand, trop monde', 'Difficile de profiter sereinement avec autant de visiteurs. Le contenu est extraordinaire mais l''expérience stressante.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'jonas.klein@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Musée du Louvre')),

(5, 'Coup de cœur absolu', 'La Vénus de Milo m''a cloué sur place. Un temple de l''art.', 'prive',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'camille.leroy@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Musée du Louvre'));


-- Jardin Majorelle
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Oasis de couleurs', 'Le bleu Majorelle est encore plus vibrant en vrai. Un havre de paix au milieu du chaos de Marrakech.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Jardin Majorelle')),

(4, 'Très beau', 'Plantes rares, couleurs intenses, endroit calme. Un peu cher pour la taille mais ça vaut le détour.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Jardin Majorelle')),

(5, 'Coup de cœur de Marrakech', 'Meilleur endroit de toute la ville selon moi. Mieux que la médina en termes de sérénité.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'ana.moreira@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Jardin Majorelle'));


-- Place Jemaa el-Fna
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Vivant et authentique', 'La nuit tombée, la place s''embrase. Musiciens, conteurs, vendeurs de jus... un spectacle gratuit permanent.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Place Jemaa el-Fna')),

(3, 'Attention aux arnaques', 'L''ambiance est unique mais soyez vigilants aux photographes avec singes ou serpents qui réclament de l''argent.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Place Jemaa el-Fna')),

(4, 'Expérience inoubliable', 'Incroyable melting-pot. Venez le soir pour profiter de l''atmosphère des food stalls.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'camille.leroy@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Place Jemaa el-Fna'));


-- Temple de Sensoji
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Spirituel et grandiose', 'Le torii géant et les lanternes rouges créent une atmosphère hors du temps. Incontournable à Tokyo.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yuki.tanaka@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Temple de Sensoji')),

(4, 'Magique mais touristique', 'Très beau temple, mais le marché attenant est clairement orienté touristes. L''architecture vaut le déplacement.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'jonas.klein@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Temple de Sensoji')),

(5, 'Mon endroit préféré au Japon', 'La fumée de l''encens et les prières du matin... une atmosphère unique. Venez à l''ouverture.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Temple de Sensoji')),

(3, 'Bien mais surcoté', 'Le temple en lui-même est beau mais la foule rend l''expérience difficile. Préférez les temples plus calmes de Kyoto.', 'prive',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Temple de Sensoji'));


-- Quartier Gion
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Comme dans un film', 'Se balader dans les ruelles de Gion au crépuscule est une expérience hors du temps. J''ai croisé une vraie geisha.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Quartier Gion')),

(4, 'Authentique', 'Le quartier a su préserver son architecture traditionnelle. Evitez les heures de pointe pour en profiter vraiment.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Quartier Gion')),

(5, 'L''âme du Japon', 'Gion représente parfaitement ce que j''aime dans la culture japonaise. La beauté dans les détails.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'camille.leroy@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Quartier Gion'));


-- Colosseum
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Impressionnant', 'Difficile de ne pas ressentir le poids de l''histoire en entrant dans l''arène. Prenez l''audioguide.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'ana.moreira@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Colosseum')),

(4, 'Magnifique vestige', 'L''état de conservation est remarquable pour 2000 ans d''histoire. La visite du forum romain est incluse, ne la ratez pas.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'jonas.klein@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Colosseum')),

(3, 'Bien mais file interminable', 'Réservez absolument en ligne. Sinon compter 3h de queue minimum en été. L''intérieur en vaut la peine.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yuki.tanaka@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Colosseum')),

(5, 'Frissons garantis', 'Imaginer les gladiateurs ici donne vraiment le vertige. Un des sites antiques les mieux préservés du monde.', 'prive',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Colosseum'));


-- Sagrada Família
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Une œuvre vivante', 'Gaudí a pensé chaque pierre. L''intérieur baigné de lumière colorée est irréel. Un chef-d''œuvre en construction.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Sagrada Família')),

(5, 'Époustouflant', 'Aucune photo ne rend justice à l''effet que ça produit en vrai. La forêt de colonnes à l''intérieur est sublime.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Sagrada Família')),

(4, 'Très beau mais cher', 'L''entrée est onéreuse mais la visite est dense. Prenez le billet avec accès aux tours pour la vue sur Barcelone.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Sagrada Família')),

(2, 'Surcoté selon moi', 'Je ne suis pas fan de l''architecture de Gaudí en général. L''extérieur est impressionnant mais l''intérieur m''a moins convaincu.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'camille.leroy@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Sagrada Família'));


-- Monastère des Hiéronymites
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Architecture manuéline parfaite', 'Les sculptures en pierre sont d''une finesse incroyable. L''une des plus belles constructions que j''aie jamais vues.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'ana.moreira@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Monastère des Hiéronymites')),

(4, 'Beau et gratuit le dimanche', 'L''entrée est gratuite le dimanche matin. L''architecture est vraiment unique, typiquement portugaise.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Monastère des Hiéronymites')),

(5, 'Incontournable à Lisbonne', 'On est soufflé par la richesse des détails sculptés. À ne pas manquer avec la Tour de Belém juste à côté.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Monastère des Hiéronymites'));


-- Rijksmuseum
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Le meilleur musée d''Europe', 'La Ronde de nuit de Rembrandt en vrai c''est une autre dimension. Le bâtiment lui-même est une œuvre.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'jonas.klein@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Rijksmuseum')),

(4, 'Collection impressionnante', 'L''âge d''or hollandais est superbement représenté. Prévoyez au moins 3h.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yuki.tanaka@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Rijksmuseum')),

(4, 'Incontournable', 'Si vous êtes à Amsterdam, ce musée est un passage obligé. La cour intérieure est magnifique.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Rijksmuseum'));


-- Central Park
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Poumon de New York', 'Se balader ici après des heures dans la jungle de béton new-yorkaise, c''est une renaissance. Immense et varié.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Central Park')),

(4, 'Très agréable', 'Le lac, les pelouses, le zoo... on peut y passer une journée entière. Idéal pour un pique-nique.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'camille.leroy@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Central Park')),

(3, 'Bien mais moins fou qu''attendu', 'C''est un beau parc, mais j''en attendais peut-être trop. Intéressant surtout pour le contexte urbain autour.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'ana.moreira@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Central Park'));


-- Acropole d''Athènes
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Berceau de l''Occident', 'Monter sur l''Acropole et voir le Parthénon depuis là-haut est un moment que je n''oublierai jamais.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Acropole d''Athènes')),

(4, 'Majestueux', 'Malheureusement en travaux lors de ma visite mais même ainsi l''émotion est là. La vue sur Athènes vaut le détour.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Acropole d''Athènes')),

(5, 'Intemporel', 'Des pierres vieilles de 2500 ans qui tiennent encore debout. C''est presque miraculeux.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'jonas.klein@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Acropole d''Athènes'));


-- Wat Pho
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Bouddha couché immense', 'Le Bouddha couché de 46 mètres est une vision saisissante. Les pieds en nacre sont fascinants.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yuki.tanaka@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Wat Pho')),

(4, 'Très beau temple', 'Moins connu que le Palais Royal mais tout aussi impressionnant. Le massage thaï proposé sur place est excellent.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Wat Pho')),

(4, 'Paisible et splendide', 'On oublie facilement qu''on est en pleine capitale. Prenez le temps de flâner entre les stupas dorés.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Wat Pho'));


-- Offices de Florence
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'La Naissance de Vénus en vrai', 'Voir le tableau de Botticelli de ses propres yeux est une expérience unique. La collection est extraordinaire.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'camille.leroy@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Offices de Florence')),

(4, 'Musée incontournable', 'File d''attente très longue même avec réservation. Mais la qualité des collections justifie tout.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'ana.moreira@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Offices de Florence')),

(5, 'Paradis de la Renaissance', 'Raphaël, Michel-Ange, Léonard... on en sort transformé. Prévoir la journée entière.', 'prive',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Offices de Florence'));


-- Cathédrale de Séville
INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) VALUES
(5, 'Immensité gothique', 'La plus grande cathédrale gothique au monde... et ça se voit. La Giralda offre une vue panoramique sur Séville.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Cathédrale de Séville')),

(4, 'Impressionnante', 'La tombe de Christophe Colomb est portée par quatre rois... un symbole fort. L''intérieur est richissime.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'jonas.klein@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Cathédrale de Séville')),

(3, 'Bien mais trop de monde', 'L''architecture est magnifique mais la gestion du flux de visiteurs laisse à désirer. Allez-y tôt.', 'public',
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yuki.tanaka@example.com'),
 (SELECT id_lieu FROM lieu WHERE nom = 'Cathédrale de Séville'));


-- ============================================================
-- Commentaires sur quelques avis
-- ============================================================

INSERT INTO commentaire (texte, id_avis, id_utilisateur) VALUES

('Totalement d''accord ! Le coucher de soleil depuis le sommet est magique.',
 (SELECT id_avis FROM avis WHERE titre = 'Un incontournable' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com')),

('Le mieux c''est d''y aller en semaine pour éviter la foule du week-end.',
 (SELECT id_avis FROM avis WHERE titre = 'Un incontournable' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com')),

('Je confirme, acheter en ligne c''est indispensable au Louvre.',
 (SELECT id_avis FROM avis WHERE titre = 'Incontournable' AND id_lieu = (SELECT id_lieu FROM lieu WHERE nom = 'Musée du Louvre') LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com')),

('Le bleu Majorelle est une couleur vraiment unique, on ne la retrouve nulle part ailleurs.',
 (SELECT id_avis FROM avis WHERE titre = 'Oasis de couleurs' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com')),

('Oui, venez tôt le matin au Sensoji, c''est beaucoup plus serein avant les touristes.',
 (SELECT id_avis FROM avis WHERE titre = 'Spirituel et grandiose' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com')),

('La Ronde de nuit m''a aussi scotché ! Le format est gigantesque en vrai.',
 (SELECT id_avis FROM avis WHERE titre = 'Le meilleur musée d''Europe' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'yasmine.benali@example.com')),

('Les Hiéronymites sont souvent moins visités que la Tour de Belém mais c''est bien meilleur !',
 (SELECT id_avis FROM avis WHERE titre = 'Architecture manuéline parfaite' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'emma.dupont@example.com')),

('Gaudí est un génie. Chaque visite révèle de nouveaux détails.',
 (SELECT id_avis FROM avis WHERE titre = 'Une œuvre vivante' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com')),

('J''ai aussi été frappé par la taille du Wat Pho. Le massage thaï sur place est vraiment bien.',
 (SELECT id_avis FROM avis WHERE titre = 'Bouddha couché immense' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'lucas.martin@example.com')),

('La vue depuis la Giralda de nuit est également splendide si vous pouvez y aller le soir.',
 (SELECT id_avis FROM avis WHERE titre = 'Immensité gothique' LIMIT 1),
 (SELECT id_utilisateur FROM utilisateur WHERE email = 'carlos.fernandez@example.com'));


-- ============================================================
-- Quelques likes pour tester les interactions
-- ============================================================

INSERT IGNORE INTO like_avis (id_utilisateur, id_avis)
SELECT u.id_utilisateur, a.id_avis
FROM utilisateur u, avis a
WHERE u.email IN ('lucas.martin@example.com', 'yasmine.benali@example.com')
  AND a.titre IN ('Un incontournable', 'Spirituel et grandiose', 'Une œuvre vivante', 'Berceau de l''Occident')
  AND a.visibility = 'public'
LIMIT 8;

INSERT IGNORE INTO like_commentaire (id_utilisateur, id_commentaire)
SELECT u.id_utilisateur, c.id_commentaire
FROM utilisateur u, commentaire c
WHERE u.email = 'lucas.martin@example.com'
LIMIT 3;
