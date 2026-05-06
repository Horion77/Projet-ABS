CREATE DATABASE IF NOT EXISTS projet_abs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE projet_abs;

-- ── TABLES ────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS places;
DROP TABLE IF EXISTS countries;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE countries (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(3)   NOT NULL,
    lat  DECIMAL(10,7),
    lng  DECIMAL(10,7)
);

CREATE TABLE places (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    country_id  INT          NOT NULL,
    lat         DECIMAL(10,7) NOT NULL,
    lng         DECIMAL(10,7) NOT NULL,
    image_url   VARCHAR(255),
    type        ENUM('pays','ville','monument') NOT NULL DEFAULT 'monument',
    icon        VARCHAR(10)  NOT NULL DEFAULT '📍',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id)
);

CREATE TABLE reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT    NOT NULL,
    place_id   INT    NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title      VARCHAR(150),
    comment    TEXT   NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id),
    FOREIGN KEY (place_id) REFERENCES places(id)
);

-- ── UTILISATEURS TEST ─────────────────────────────────────────────────────
INSERT INTO users (id, username, email, password) VALUES
(1, 'alan',  'alan@abs.fr',  '$2y$10$dummyhash1'),
(2, 'basma', 'basma@abs.fr', '$2y$10$dummyhash2'),
(3, 'sara',  'sara@abs.fr',  '$2y$10$dummyhash3');

-- ── PAYS ──────────────────────────────────────────────────────────────────
INSERT INTO countries (id, name, code, lat, lng) VALUES
( 1, 'France',       'FRA',  46.6034,    1.8883),
( 2, 'Japon',        'JPN',  36.2048,  138.2529),
( 3, 'Maroc',        'MAR',  31.7917,   -7.0926),
( 4, 'Italie',       'ITA',  41.8719,   12.5674),
( 5, 'Brésil',       'BRA', -14.2350,  -51.9253),
( 6, 'Australie',    'AUS', -25.2744,  133.7751),
( 7, 'États-Unis',   'USA',  37.0902,  -95.7129),
( 8, 'Inde',         'IND',  20.5937,   78.9629),
( 9, 'Pérou',        'PER',  -9.1899,  -75.0152),
(10, 'Grèce',        'GRC',  39.0742,   21.8243),
(11, 'Espagne',      'ESP',  40.4637,   -3.7492),
(12, 'Turquie',      'TUR',  38.9637,   35.2433),
(13, 'Thaïlande',    'THA',  15.8700,  100.9925),
(14, 'Mexique',      'MEX',  23.6345, -102.5528),
(15, 'Égypte',       'EGY',  26.8206,   30.8025);

-- ── LIEUX — TYPE PAYS (zoom 0–5) ──────────────────────────────────────────
INSERT INTO places (id, name, description, country_id, lat, lng, type, icon) VALUES
(1,  'France',      'Le pays de la gastronomie, de la mode et des châteaux.',            1,  46.6034,    1.8883, 'pays', '🇫🇷'),
(2,  'Japon',       'L\'archipel du soleil levant entre tradition et modernité.',        2,  36.2048,  138.2529, 'pays', '🇯🇵'),
(3,  'Maroc',       'Royaume aux mille couleurs entre désert, mer et montagnes.',         3,  31.7917,   -7.0926, 'pays', '🇲🇦'),
(4,  'Italie',      'Art, histoire, cuisine et dolce vita au cœur de la Méditerranée.',  4,  41.8719,   12.5674, 'pays', '🇮🇹'),
(5,  'Brésil',      'Géant tropical entre Amazonie, plages et carnaval.',               5, -14.2350,  -51.9253, 'pays', '🇧🇷'),
(6,  'Australie',   'Continent-île sauvage entre récifs, déserts et villes modernes.',  6, -25.2744,  133.7751, 'pays', '🇦🇺'),
(7,  'États-Unis',  'De NY aux grands espaces de l\'Ouest, le pays des contrastes.',    7,  37.0902,  -95.7129, 'pays', '🇺🇸'),
(8,  'Inde',        'Civilisation millénaire aux mille visages et saveurs.',             8,  20.5937,   78.9629, 'pays', '🇮🇳'),
(9,  'Pérou',       'Berceau de l\'empire inca, des Andes à l\'Amazonie.',              9,  -9.1899,  -75.0152, 'pays', '🇵🇪'),
(10, 'Grèce',       'Berceau de la démocratie, de la philosophie et de la mer Égée.',  10,  39.0742,   21.8243, 'pays', '🇬🇷'),
(11, 'Espagne',     'Flamenco, soleil, gastronomie et architecture unique.',             11,  40.4637,   -3.7492, 'pays', '🇪🇸'),
(12, 'Turquie',     'Carrefour de l\'Orient et de l\'Occident entre Bosphore et steppes.',12, 38.9637,   35.2433, 'pays', '🇹🇷'),
(13, 'Thaïlande',   'Temple, plages paradisiaques et cuisine explosive.',               13,  15.8700,  100.9925, 'pays', '🇹🇭'),
(14, 'Mexique',     'Civilisations anciennes, volcans et gastronomie classée UNESCO.',  14,  23.6345, -102.5528, 'pays', '🇲🇽'),
(15, 'Égypte',      'Pharaons, pyramides et le Nil — l\'une des civilisations les plus anciennes.', 15, 26.8206, 30.8025, 'pays', '🇪🇬');

-- ── LIEUX — TYPE VILLE (zoom 4–9) ─────────────────────────────────────────
INSERT INTO places (id, name, description, country_id, lat, lng, type, icon) VALUES
(16, 'Paris',       'La Ville Lumière : culture, mode et gastronomie mondiale.',         1,  48.8566,    2.3522, 'ville', '🏙️'),
(17, 'Tokyo',       'Mégapole futuriste mêlant tradition ancestrale et technologie.',   2,  35.6762,  139.6503, 'ville', '🏙️'),
(18, 'Marrakech',   'La ville ocre : souks, riads et place Jemaa el-Fna.',             3,  31.6295,   -7.9811, 'ville', '🏙️'),
(19, 'Rome',        'La Ville Éternelle : 2800 ans d\'histoire à ciel ouvert.',         4,  41.9028,   12.4964, 'ville', '🏛️'),
(20, 'Rio de Janeiro','Ville entre montagne et mer, carnaval et corcovado.',            5, -22.9068,  -43.1729, 'ville', '🏙️'),
(21, 'Sydney',      'Opéra, Harbour Bridge et plages de sable blanc.',                  6, -33.8688,  151.2093, 'ville', '🏙️'),
(22, 'New York',    'La ville qui ne dort jamais : Manhattan, Central Park, Broadway.',  7,  40.7128,  -74.0060, 'ville', '🏙️'),
(23, 'Kyoto',       'Ancienne capitale impériale, temples et cerisiers en fleurs.',     2,  35.0116,  135.7681, 'ville', '🏯'),
(24, 'Barcelone',   'Gaudí, tapas, plages et architecture moderniste catalane.',        11,  41.3851,    2.1734, 'ville', '🏙️'),
(25, 'Istanbul',    'Ville entre deux continents, coupoles et minarets au bord du Bosphore.', 12, 41.0082, 28.9784, 'ville', '🕌'),
(26, 'Bangkok',     'Temple du Bouddha Émeraude, tuk-tuks et street food légendaire.', 13,  13.7563,  100.5018, 'ville', '🏙️'),
(27, 'Mexico',      'Mégapole aztèque sur un ancien lac, culture et gastronomie.',     14,  19.4326,  -99.1332, 'ville', '🏙️'),
(28, 'Athènes',     'Berceau de la philosophie et de la démocratie, sous l\'Acropole.',10,  37.9755,   23.7348, 'ville', '🏛️'),
(29, 'Varanasi',    'Ville sainte hindoue sur les rives du Gange, ghâts et rituels.',   8,  25.3176,   82.9739, 'ville', '🕍'),
(30, 'Cusco',       'Ancienne capitale inca à 3400 m d\'altitude dans les Andes.',     9, -13.5320,  -71.9675, 'ville', '🏙️');

-- ── LIEUX — TYPE MONUMENT (zoom 8+) ──────────────────────────────────────
INSERT INTO places (id, name, description, country_id, lat, lng, type, icon) VALUES
(31, 'Tour Eiffel',      'Fer de 324 m, symbole de Paris, 7 millions de visiteurs/an.',  1,  48.8584,    2.2945, 'monument', '🗼'),
(32, 'Mont Fuji',        'Volcan sacré et plus haut sommet du Japon à 3776 m.',          2,  35.3606,  138.7274, 'monument', '🗻'),
(33, 'Jemaa el-Fna',     'Place la plus animée d\'Afrique, classée par l\'UNESCO.',      3,  31.6258,   -7.9891, 'monument', '🎪'),
(34, 'Colisée',          'Amphithéâtre romain du Ier siècle, joyau de l\'Antiquité.',   4,  41.8902,   12.4922, 'monument', '🏟️'),
(35, 'Christ Rédempteur','Statue de 38 m dominant Rio depuis le Corcovado.',            5, -22.9519,  -43.2105, 'monument', '✝️'),
(36, 'Opéra de Sydney',  'Chef-d\'œuvre architectural sur le port de Sydney.',          6, -33.8568,  151.2153, 'monument', '🎭'),
(37, 'Grand Canyon',     'Canyon de 446 km creusé par le Colorado, Arizona.',           7,  36.0544, -112.1401, 'monument', '🏜️'),
(38, 'Taj Mahal',        'Mausolée en marbre blanc, merveille moghole d\'Agra.',        8,  27.1751,   78.0421, 'monument', '🕌'),
(39, 'Machu Picchu',     'Cité inca perchée à 2430 m dans les nuages péruviens.',      9, -13.1631,  -72.5450, 'monument', '🏔️'),
(40, 'Acropole d\'Athènes','Citadelle antique du Ve siècle av. J.-C., symbole de la Grèce.', 10, 37.9715, 23.7267, 'monument', '🏛️'),
(41, 'Sagrada Família',  'Basilique de Gaudí en construction depuis 1882, génie catalan.',11, 41.4036,    2.1744, 'monument', '⛪'),
(42, 'Hagia Sophia',     'Basilique devenue mosquée, chef-d\'œuvre byzantin d\'Istanbul.',12, 41.0086,  28.9802, 'monument', '🕌'),
(43, 'Wat Phra Kaew',    'Temple du Bouddha Émeraude, joyau sacré de Bangkok.',       13,  13.7516,  100.4920, 'monument', '⛩️'),
(44, 'Chichen Itza',     'Pyramide maya de Kukulcán, merveille du monde antique.',    14,  20.6843,  -88.5678, 'monument', '🔺'),
(45, 'Pyramides de Gizeh','Les seules des 7 merveilles encore debout, bâties en -2560.',15, 29.9792,   31.1342, 'monument', '🔺');

-- ── AVIS FICTIFS ─────────────────────────────────────────────────────────
INSERT INTO reviews (user_id, place_id, rating, title, comment) VALUES
-- Pays
(1,  1,  5, 'Magnifique pays',       'La France c\'est incomparable, chaque région a son caractère.'),
(2,  1,  4, 'Incontournable',        'Paris est sublime mais l\'arrière-pays mérite le détour.'),
(3,  2,  5, 'Dépaysement total',     'Le Japon m\'a retourné le cerveau dans le bon sens.'),
(1,  2,  5, 'Mon pays préféré',      'L\'harmonie entre tradition et modernité est stupéfiante.'),
(2,  3,  4, 'Accueil chaleureux',    'Les Marocains sont adorables, la cuisine divine.'),
(3,  4,  5, 'Pasta & Amore',         'L\'Italie m\'a volé mon cœur entre le Colisée et les pizzas.'),
(1,  5,  4, 'Nature sauvage',        'L\'Amazonie brésilienne change vraiment la vision du monde.'),
(2,  6,  5, 'Bout du monde',         'Kangourous, koalas et Grande Barrière — tout est vrai.'),
(3,  7,  4, 'Pays de contrastes',    'De NYC aux parcs nationaux, les USA ne ressemblent à rien d\'autre.'),
(1,  8,  5, 'Spiritualité intense',  'L\'Inde m\'a submergé d\'émotions, de couleurs et de foi.'),
(2,  9,  5, 'Terre des Incas',       'Le Pérou reste la destination la plus marquante de ma vie.'),
(3, 10,  5, 'Civilisation berceau',  'Marcher sur les pas de Socrate dans la lumière grecque.'),
(1, 11,  5, 'Viva España',           'Tapas, soleil et joie de vivre — l\'Espagne sans filtre.'),
(2, 12,  4, 'Orient-Occident',       'Istanbul m\'a fait comprendre que le monde est plus grand qu\'on croit.'),
-- Villes
(1, 16,  5, 'Paris je t\'aime',      'La Seine au coucher du soleil depuis le Pont des Arts, inoubliable.'),
(2, 16,  4, 'Parfois surcoté',       'Beau mais bondé. Y aller hors saison pour vraiment apprécier.'),
(3, 17,  5, 'Tokyo choc culturel',   'Je n\'ai jamais mangé aussi bien de ma vie, nuit et jour.'),
(1, 17,  5, 'Ville du futur',        'La propreté, la ponctualité et la gentillesse des Japonais — modèle.'),
(2, 18,  4, 'Couleurs et parfums',   'Marrakech c\'est un tableau vivant à chaque coin de rue.'),
(3, 19,  5, 'Rome éternelle',        'J\'ai pleuré en voyant le Colisée pour la première fois.'),
(1, 20,  4, 'Energia Carioca',       'Rio vibre à un rythme unique entre jungle et plage.'),
(2, 22,  5, 'New York New York',     'Central Park, Times Square, Brooklyn Bridge — tout dépasse les attentes.'),
(3, 23,  5, 'Kyoto hors du temps',   'Les geishas du Gion, les temples zen — l\'antidote à la modernité.'),
(1, 24,  5, 'Barcelona olé',         'La Sagrada Família en vrai est 10x plus grande que sur les photos.'),
(2, 25,  5, 'Deux continents',       'Istanbul au coucher du soleil depuis le Bosphore : frisson garanti.'),
-- Monuments
(1, 31,  5, 'Tour Eiffel, émue',     'Vue depuis le 2e étage au crépuscule — moment de pure magie.'),
(2, 31,  4, 'Vaut le détour',        'Longue file mais ça vaut vraiment la peine d\'aller jusqu\'en haut.'),
(3, 32,  5, 'Sommet spirituel',      'Voir le Fuji depuis le lac Kawaguchiko à l\'aube — épiphanie.'),
(1, 34,  5, 'Frisson antique',       'Imaginer les gladiateurs dans l\'arène — émotion unique.'),
(2, 34,  5, 'Chef-d\'œuvre romain',  'Impressionnant de se dire que c\'est 2000 ans d\'histoire.'),
(3, 35,  5, 'Bras ouverts',          'Le Christ Rédempteur surplombe tout Rio — vue à couper le souffle.'),
(1, 37,  5, 'Grand Canyon WOW',      'Photos ne rendent pas justice. Il faut le voir pour y croire.'),
(2, 37,  4, 'Immense et vertigineux','Longer le bord sans barrière — sensation d\'infini.'),
(3, 38,  5, 'Taj Mahal, larmes',     'La beauté de ce mausolée m\'a mis les larmes aux yeux.'),
(1, 39,  5, 'Machu Picchu ++ ',      'Dans les nuages, entouré de montagnes — moment intemporel.'),
(2, 40,  5, 'Acropole mythique',     'Le Parthénon dans la lumière dorée du soir — beauté pure.'),
(3, 41,  5, 'Gaudí génie absolu',    'La Sagrada Família est le monument le plus fou que j\'aie vu.'),
(1, 44,  4, 'Maya mystère',          'Chichen Itza donne la chair de poule — que faisaient-ils là ?'),
(2, 45,  5, 'Pyramides, sans mots',  'Les pyramides de nuit sous les étoiles : émotion maximale.'),
(3, 45,  5, 'Héritage de l\'humanité','Se tenir devant 4500 ans d\'histoire — vertige total.');
