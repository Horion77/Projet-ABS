-- Données de test pour la carte — plusieurs pays/continents
-- Exécuter après database.sql + seed_demo.sql
-- mysql -u root -proot abs_db < sql/seeds/seed_map_test.sql

USE abs_db;

-- ── Pays ────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO pays (nom, code_iso, continent) VALUES
  ('France',        'FRA', 'Europe'),
  ('Japon',         'JPN', 'Asie'),
  ('États-Unis',    'USA', 'Amériques'),
  ('Brésil',        'BRA', 'Amériques'),
  ('Australie',     'AUS', 'Océanie'),
  ('Kenya',         'KEN', 'Afrique'),
  ('Italie',        'ITA', 'Europe'),
  ('Thaïlande',     'THA', 'Asie');

-- ── Villes ──────────────────────────────────────────────────────────────────
INSERT IGNORE INTO ville (nom, id_pays) VALUES
  ('Paris',        (SELECT id_pays FROM pays WHERE code_iso='FRA')),
  ('Lyon',         (SELECT id_pays FROM pays WHERE code_iso='FRA')),
  ('Tokyo',        (SELECT id_pays FROM pays WHERE code_iso='JPN')),
  ('Kyoto',        (SELECT id_pays FROM pays WHERE code_iso='JPN')),
  ('New York',     (SELECT id_pays FROM pays WHERE code_iso='USA')),
  ('San Francisco',(SELECT id_pays FROM pays WHERE code_iso='USA')),
  ('Rio de Janeiro',(SELECT id_pays FROM pays WHERE code_iso='BRA')),
  ('Sydney',       (SELECT id_pays FROM pays WHERE code_iso='AUS')),
  ('Nairobi',      (SELECT id_pays FROM pays WHERE code_iso='KEN')),
  ('Rome',         (SELECT id_pays FROM pays WHERE code_iso='ITA')),
  ('Bangkok',      (SELECT id_pays FROM pays WHERE code_iso='THA'));

-- ── Lieux ───────────────────────────────────────────────────────────────────
-- Monument=4 dans categorie_lieu (ordre INSERT database.sql : Musée=1, Resto=2, Plage=3, Monument=4, Parc=5)

INSERT INTO lieu (nom, description, type, icon, latitude, longitude, image_url, id_categorie, id_ville) VALUES
-- France
('Tour Eiffel',     'Monument emblématique de Paris, construit en 1889.',        'monument', '🗼', 48.8584,   2.2945,  'https://images.unsplash.com/photo-1511739001486-6bfe10ce785f?w=800&q=80', 4, (SELECT id_ville FROM ville WHERE nom='Paris')),
('Musée du Louvre', 'Le plus grand musée du monde, ancienne résidence royale.',   'monument', '🏛️', 48.8606,   2.3376,  'https://images.unsplash.com/photo-1499856871958-5b9357976b82?w=800&q=80', 1, (SELECT id_ville FROM ville WHERE nom='Paris')),
('Vieux Lyon',      'Quartier Renaissance classé UNESCO, bouchons et traboules.','monument', '🏘️', 45.7640,   4.8271,  NULL, 4, (SELECT id_ville FROM ville WHERE nom='Lyon')),

-- Japon
('Mont Fuji',       'Plus haute montagne du Japon, symbole national.',            'monument', '🗻', 35.3606, 138.7274,  'https://images.unsplash.com/photo-1490806843957-31f4c9a91c65?w=800&q=80', 5, (SELECT id_ville FROM ville WHERE nom='Kyoto')),
('Temple Senso-ji', 'Plus vieux temple de Tokyo, quartier Asakusa.',              'monument', '⛩️', 35.7148, 139.7967,  'https://images.unsplash.com/photo-1545569341-9eb8b30979d9?w=800&q=80', 4, (SELECT id_ville FROM ville WHERE nom='Tokyo')),
('Forêt de bambous de Arashiyama', 'Allée de bambous géants, Kyoto.',            'monument', '🎋', 35.0094, 135.6724,  NULL, 5, (SELECT id_ville FROM ville WHERE nom='Kyoto')),

-- USA
('Central Park',    'Parc urbain de 341 ha au coeur de Manhattan.',               'monument', '🌳', 40.7851,  -73.9683,  'https://images.unsplash.com/photo-1568515387631-8b650bbcdb90?w=800&q=80', 5, (SELECT id_ville FROM ville WHERE nom='New York')),
('Golden Gate',     'Pont suspendu emblématique de San Francisco.',               'monument', '🌉', 37.8199, -122.4783,  'https://images.unsplash.com/photo-1449034446853-66c86144b0ad?w=800&q=80', 4, (SELECT id_ville FROM ville WHERE nom='San Francisco')),

-- Brésil
('Christ Rédempteur','Statue de 30 m dominant Rio, classée 7e merveille.',        'monument', '✝️', -22.9519,  -43.2105,  'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=800&q=80', 4, (SELECT id_ville FROM ville WHERE nom='Rio de Janeiro')),
('Plage de Copacabana','Plage mythique de 4 km, coeur de Rio.',                   'monument', '🏖️', -22.9711,  -43.1822,  NULL, 3, (SELECT id_ville FROM ville WHERE nom='Rio de Janeiro')),

-- Australie
('Opéra de Sydney', 'Chef-d''oeuvre architectural sur le port de Sydney.',        'monument', '🎭', -33.8568,  151.2153,  'https://images.unsplash.com/photo-1548391350-968f58dedaed?w=800&q=80', 4, (SELECT id_ville FROM ville WHERE nom='Sydney')),

-- Kenya
('Parc Masai Mara', 'Réserve naturelle, grande migration des gnous.',             'monument', '🦁', -1.5023,   35.1480,  'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=800&q=80', 5, (SELECT id_ville FROM ville WHERE nom='Nairobi')),

-- Italie
('Colisée',         'Amphithéâtre romain du 1er siècle, symbole de Rome.',        'monument', '🏟️', 41.8902,   12.4922,  'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=800&q=80', 4, (SELECT id_ville FROM ville WHERE nom='Rome')),
('Fontaine de Trevi','Fontaine baroque du XVIIIe, la plus grande de Rome.',       'monument', '⛲', 41.9009,   12.4833,  NULL, 4, (SELECT id_ville FROM ville WHERE nom='Rome')),

-- Thaïlande
('Grand Palais Bangkok','Complexe royal de 218 000 m², résidence des rois.',      'monument', '🏯', 13.7500,  100.4913,  'https://images.unsplash.com/photo-1563492065599-3520f775eeed?w=800&q=80', 4, (SELECT id_ville FROM ville WHERE nom='Bangkok'));

SELECT CONCAT('Lieux insérés : ', COUNT(*)) AS resultat FROM lieu;
