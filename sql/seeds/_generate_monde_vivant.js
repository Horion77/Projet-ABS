// Générateur du seed "monde vivant" pour ABS.
// Produit seed_monde_vivant.sql : ~90 nouveaux users (≈100 au total),
// de nouveaux pays (dont îles), beaucoup de lieux (USA, îles, monde),
// 2+ avis par user (200+ avis), commentaires et likes.
//
// Lancer : node sql/seeds/_generate_monde_vivant.js
// Puis    : mysql -uroot -proot abs_db < sql/seeds/seed_monde_vivant.sql

'use strict';
const fs = require('fs');
const path = require('path');

// PRNG déterministe (mulberry32) → seed reproductible
let _s = 1337;
function rng() {
  _s |= 0; _s = (_s + 0x6D2B79F5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
function pick(arr) { return arr[Math.floor(rng() * arr.length)]; }
function chance(p) { return rng() < p; }
function esc(s) { return String(s).replace(/'/g, "''"); }

// ── Pays (nom, iso3, continent) ───────────────────────────────────────────
const PAYS = [
  ['France', 'FRA', 'Europe'], ['Maroc', 'MAR', 'Afrique'], ['Japon', 'JPN', 'Asie'],
  ['Italie', 'ITA', 'Europe'], ['Espagne', 'ESP', 'Europe'], ['Portugal', 'PRT', 'Europe'],
  ['Pays-Bas', 'NLD', 'Europe'], ['États-Unis', 'USA', 'Amérique'], ['Grèce', 'GRC', 'Europe'],
  ['Thaïlande', 'THA', 'Asie'], ['Guadeloupe', 'GLP', 'Amérique'], ['Martinique', 'MTQ', 'Amérique'],
  ['La Réunion', 'REU', 'Afrique'], ['Polynésie française', 'PYF', 'Océanie'], ['Islande', 'ISL', 'Europe'],
  ['Indonésie', 'IDN', 'Asie'], ['Mexique', 'MEX', 'Amérique'], ['Brésil', 'BRA', 'Amérique'],
  ['Égypte', 'EGY', 'Afrique'], ['Émirats arabes unis', 'ARE', 'Asie'], ['Australie', 'AUS', 'Océanie'],
  ['Royaume-Uni', 'GBR', 'Europe'], ['Allemagne', 'DEU', 'Europe'], ['Croatie', 'HRV', 'Europe'],
  ['Turquie', 'TUR', 'Asie'], ['Pérou', 'PER', 'Amérique'], ['Inde', 'IND', 'Asie'],
  ['Chine', 'CHN', 'Asie'], ['Canada', 'CAN', 'Amérique'],
];

// ── Villes (nom, iso3 pays) ─────────────────────────────────────────────────
const VILLES = [
  ['Paris', 'FRA'], ['Lyon', 'FRA'], ['Nice', 'FRA'],
  ['Marrakech', 'MAR'], ['Casablanca', 'MAR'],
  ['Tokyo', 'JPN'], ['Kyoto', 'JPN'],
  ['Rome', 'ITA'], ['Florence', 'ITA'], ['Venise', 'ITA'],
  ['Barcelone', 'ESP'], ['Séville', 'ESP'], ['Madrid', 'ESP'],
  ['Lisbonne', 'PRT'], ['Porto', 'PRT'],
  ['Amsterdam', 'NLD'],
  ['New York', 'USA'], ['San Francisco', 'USA'], ['Los Angeles', 'USA'],
  ['Las Vegas', 'USA'], ['Washington', 'USA'], ['Chicago', 'USA'], ['Miami', 'USA'],
  ['Orlando', 'USA'], ['Seattle', 'USA'], ['La Nouvelle-Orléans', 'USA'],
  ['Grand Canyon', 'USA'], ['Yellowstone', 'USA'], ['Honolulu', 'USA'],
  ['Athènes', 'GRC'], ['Santorin', 'GRC'],
  ['Bangkok', 'THA'], ['Phuket', 'THA'],
  ['Pointe-à-Pitre', 'GLP'], ['Fort-de-France', 'MTQ'], ['Saint-Denis', 'REU'],
  ['Bora Bora', 'PYF'], ['Reykjavik', 'ISL'], ['Bali', 'IDN'],
  ['Mexico', 'MEX'], ['Cancún', 'MEX'], ['Rio de Janeiro', 'BRA'],
  ['Le Caire', 'EGY'], ['Louxor', 'EGY'], ['Dubaï', 'ARE'],
  ['Sydney', 'AUS'], ['Uluru', 'AUS'], ['Londres', 'GBR'],
  ['Berlin', 'DEU'], ['Munich', 'DEU'], ['Dubrovnik', 'HRV'], ['Plitvice', 'HRV'],
  ['Istanbul', 'TUR'], ['Göreme', 'TUR'], ['Cusco', 'PER'],
  ['Agra', 'IND'], ['Jaipur', 'IND'], ['Pékin', 'CHN'], ['Xi an', 'CHN'],
  ['Toronto', 'CAN'], ['Banff', 'CAN'],
];

// ── Lieux : [nom, categorie, ville, lat, lng, description] ───────────────────
const C = { mon: 'Monument', mus: 'Musée', plg: 'Plage', prc: 'Parc', nat: 'Site naturel', res: 'Restaurant', bar: 'Bar', hot: 'Hôtel', mar: 'Marché', aut: 'Autre' };
const LIEUX = [
  // Paris (densification)
  ['Arc de Triomphe', C.mon, 'Paris', 48.8738, 2.2950, "Arc monumental au sommet des Champs-Élysées, dédié aux armées françaises."],
  ['Cathédrale Notre-Dame', C.mon, 'Paris', 48.8530, 2.3499, "Chef-d'œuvre gothique au cœur de l'Île de la Cité, en cours de restauration."],
  ['Basilique du Sacré-Cœur', C.mon, 'Paris', 48.8867, 2.3431, "Basilique blanche dominant Paris depuis la butte Montmartre."],
  ["Musée d'Orsay", C.mus, 'Paris', 48.8600, 2.3266, "Ancienne gare abritant la plus belle collection impressionniste au monde."],
  ['Quartier Saint-Jean', C.aut, 'Lyon', 45.7620, 4.8270, "Cœur du Vieux Lyon Renaissance et ses traboules secrètes."],
  ['Promenade des Anglais', C.aut, 'Nice', 43.6950, 7.2650, "Front de mer mythique longeant la baie des Anges."],

  // USA — beaucoup de monuments
  ['Statue de la Liberté', C.mon, 'New York', 40.6892, -74.0445, "Symbole de liberté offert par la France en 1886, sur Liberty Island."],
  ['Times Square', C.aut, 'New York', 40.7580, -73.9855, "Carrefour électrique de panneaux lumineux, le cœur battant de Manhattan."],
  ['Empire State Building', C.mon, 'New York', 40.7484, -73.9857, "Gratte-ciel Art déco emblématique avec une vue à 360° sur la ville."],
  ['Pont de Brooklyn', C.mon, 'New York', 40.7061, -73.9969, "Pont suspendu historique reliant Manhattan à Brooklyn."],
  ['Golden Gate Bridge', C.mon, 'San Francisco', 37.8199, -122.4783, "Pont rouge iconique enjambant la baie de San Francisco."],
  ['Alcatraz', C.mon, 'San Francisco', 37.8267, -122.4230, "Ancienne prison fédérale sur une île au passé légendaire."],
  ["Fisherman's Wharf", C.aut, 'San Francisco', 37.8080, -122.4177, "Port animé connu pour ses otaries et ses fruits de mer."],
  ['Panneau Hollywood', C.mon, 'Los Angeles', 34.1341, -118.3215, "Lettres géantes surplombant la cité du cinéma."],
  ['Santa Monica Pier', C.aut, 'Los Angeles', 34.0089, -118.4973, "Jetée mythique avec fête foraine au bout de la Route 66."],
  ['Griffith Observatory', C.mus, 'Los Angeles', 34.1184, -118.3004, "Observatoire offrant une vue imprenable sur LA et les étoiles."],
  ['The Strip', C.aut, 'Las Vegas', 36.1147, -115.1728, "Boulevard de néons, casinos et démesure en plein désert."],
  ['Fontaines du Bellagio', C.aut, 'Las Vegas', 36.1126, -115.1767, "Ballet aquatique synchronisé sur fond de musique, gratuit chaque soir."],
  ['Grand Canyon (South Rim)', C.nat, 'Grand Canyon', 36.0544, -112.1401, "Gorge vertigineuse creusée par le Colorado sur des millions d'années."],
  ['Lincoln Memorial', C.mon, 'Washington', 38.8893, -77.0502, "Temple de marbre abritant la statue colossale d'Abraham Lincoln."],
  ['Maison Blanche', C.mon, 'Washington', 38.8977, -77.0365, "Résidence et bureau du président des États-Unis."],
  ['Capitole', C.mon, 'Washington', 38.8899, -77.0091, "Siège du Congrès américain, dôme néoclassique reconnaissable entre tous."],
  ['Cloud Gate (The Bean)', C.mon, 'Chicago', 41.8827, -87.6233, "Sculpture miroir en forme de haricot reflétant la skyline de Chicago."],
  ['Willis Tower Skydeck', C.aut, 'Chicago', 41.8789, -87.6359, "Plateforme de verre à 412 mètres au-dessus du vide."],
  ['South Beach', C.plg, 'Miami', 25.7826, -80.1340, "Plage Art déco, sable blanc et ambiance latino festive."],
  ['Walt Disney World', C.aut, 'Orlando', 28.3852, -81.5639, "Le plus grand complexe de parcs Disney au monde."],
  ['Space Needle', C.mon, 'Seattle', 47.6205, -122.3493, "Tour futuriste héritée de l'Expo universelle de 1962."],
  ['Quartier français', C.aut, 'La Nouvelle-Orléans', 29.9584, -90.0644, "Berceau du jazz, balcons en fer forgé et beignets sucrés."],
  ['Old Faithful', C.nat, 'Yellowstone', 44.4605, -110.8281, "Geyser ponctuel star du premier parc national du monde."],
  ['Waikiki Beach', C.plg, 'Honolulu', 21.2761, -157.8267, "Plage légendaire de surf au pied de Diamond Head."],
  ['Diamond Head', C.nat, 'Honolulu', 21.2620, -157.8050, "Cratère volcanique offrant une rando et un panorama sur Oahu."],
  ['Pearl Harbor', C.mon, 'Honolulu', 21.3645, -157.9398, "Mémorial poignant dédié aux marins de l'USS Arizona."],

  // Îles
  ['Plage de Grande Anse', C.plg, 'Pointe-à-Pitre', 16.3270, -61.7710, "Longue plage de sable doré bordée de cocotiers en Guadeloupe."],
  ['Chutes du Carbet', C.nat, 'Pointe-à-Pitre', 16.0469, -61.6486, "Cascades spectaculaires au cœur de la forêt tropicale guadeloupéenne."],
  ['Mémorial ACTe', C.mus, 'Pointe-à-Pitre', 16.2380, -61.5340, "Centre dédié à la mémoire de l'esclavage et de la traite négrière."],
  ['Plage des Salines', C.plg, 'Fort-de-France', 14.3897, -60.8430, "La plus belle plage de Martinique, eau turquoise et sable fin."],
  ['Montagne Pelée', C.nat, 'Fort-de-France', 14.8120, -61.1650, "Volcan emblématique dont l'éruption de 1902 marqua l'histoire."],
  ['Piton de la Fournaise', C.nat, 'Saint-Denis', -21.2440, 55.7080, "L'un des volcans les plus actifs du monde, paysage lunaire à La Réunion."],
  ['Cirque de Mafate', C.nat, 'Saint-Denis', -21.0700, 55.4500, "Cirque accessible uniquement à pied ou en hélicoptère, sauvage et grandiose."],
  ['Lagon de Bora Bora', C.plg, 'Bora Bora', -16.5004, -151.7415, "Lagon turquoise mythique entouré de bungalows sur pilotis."],
  ['Mont Otemanu', C.nat, 'Bora Bora', -16.5089, -151.7497, "Pic volcanique dominant l'île, paradis des plongeurs."],
  ['Blue Lagoon', C.nat, 'Reykjavik', 63.8804, -22.4495, "Sources géothermales laiteuses au milieu d'un champ de lave."],
  ['Gullfoss', C.nat, 'Reykjavik', 64.3271, -20.1199, "Chute d'eau monumentale du Cercle d'Or islandais."],
  ['Tanah Lot', C.mon, 'Bali', -8.6212, 115.0868, "Temple hindou perché sur un rocher battu par les vagues."],
  ['Temple d Uluwatu', C.mon, 'Bali', -8.8291, 115.0849, "Temple en bord de falaise célèbre pour ses couchers de soleil."],

  // Reste du monde
  ['Teotihuacan', C.mon, 'Mexico', 19.6925, -98.8438, "Cité précolombienne et ses pyramides du Soleil et de la Lune."],
  ['Chichén Itzá', C.mon, 'Cancún', 20.6843, -88.5678, "Pyramide maya de Kukulcán, l'une des 7 merveilles du monde moderne."],
  ['Christ Rédempteur', C.mon, 'Rio de Janeiro', -22.9519, -43.2105, "Statue géante du Christ veillant sur Rio depuis le Corcovado."],
  ['Plage de Copacabana', C.plg, 'Rio de Janeiro', -22.9711, -43.1822, "Plage mythique de 4 km au rythme de la samba et du foot."],
  ['Pain de Sucre', C.nat, 'Rio de Janeiro', -22.9486, -43.1566, "Mont accessible en téléphérique avec vue à 360° sur la baie."],
  ['Pyramides de Gizeh', C.mon, 'Le Caire', 29.9792, 31.1342, "Dernière des 7 merveilles antiques encore debout."],
  ['Temple de Karnak', C.mon, 'Louxor', 25.7188, 32.6573, "Immense complexe de temples dédié à Amon-Rê."],
  ['Burj Khalifa', C.mon, 'Dubaï', 25.1972, 55.2744, "Plus haute tour du monde, 828 mètres de prouesse architecturale."],
  ['Palm Jumeirah', C.aut, 'Dubaï', 25.1124, 55.1390, "Île artificielle en forme de palmier, luxe à perte de vue."],
  ['Opéra de Sydney', C.mon, 'Sydney', -33.8568, 151.2153, "Voiles de béton iconiques sur le port de Sydney."],
  ['Harbour Bridge', C.mon, 'Sydney', -33.8523, 151.2108, "Pont en arche surnommé le cintre, escaladable au sommet."],
  ['Uluru', C.nat, 'Uluru', -25.3444, 131.0369, "Monolithe rouge sacré pour les Aborigènes, magique au crépuscule."],
  ['Big Ben', C.mon, 'Londres', 51.5007, -0.1246, "Tour de l'horloge la plus célèbre du monde, au bord de la Tamise."],
  ['Tower Bridge', C.mon, 'Londres', 51.5055, -0.0754, "Pont bascule victorien aux deux tours néogothiques."],
  ['British Museum', C.mus, 'Londres', 51.5194, -0.1270, "Musée encyclopédique gratuit, de la Pierre de Rosette aux momies."],
  ['London Eye', C.aut, 'Londres', 51.5033, -0.1196, "Grande roue panoramique offrant une vue imprenable sur Londres."],
  ['Porte de Brandebourg', C.mon, 'Berlin', 52.5163, 13.3777, "Symbole de la réunification allemande, néoclassique majestueux."],
  ['Château de Neuschwanstein', C.mon, 'Munich', 47.5576, 10.7498, "Château de conte de fées ayant inspiré Disney, en Bavière."],
  ['Vieille ville de Dubrovnik', C.mon, 'Dubrovnik', 42.6407, 18.1077, "Remparts médiévaux surplombant l'Adriatique, décor de série culte."],
  ['Lacs de Plitvice', C.nat, 'Plitvice', 44.8654, 15.5820, "Cascades et lacs turquoise en escalier, parc national croate."],
  ['Sainte-Sophie', C.mon, 'Istanbul', 41.0086, 28.9802, "Ancienne basilique byzantine devenue mosquée, dôme grandiose."],
  ['Mosquée Bleue', C.mon, 'Istanbul', 41.0054, 28.9768, "Mosquée aux six minarets et 20 000 carreaux d'Iznik bleus."],
  ['Cappadoce', C.nat, 'Göreme', 38.6431, 34.8307, "Cheminées de fée et vols en montgolfière au lever du soleil."],
  ['Machu Picchu', C.mon, 'Cusco', -13.1631, -72.5450, "Cité inca perchée dans les Andes, merveille absolue."],
  ['Taj Mahal', C.mon, 'Agra', 27.1751, 78.0421, "Mausolée de marbre blanc, sommet de l'art moghol et de l'amour."],
  ['Hawa Mahal', C.mon, 'Jaipur', 26.9239, 75.8267, "Palais des Vents rose aux 953 fenêtres ajourées."],
  ['Grande Muraille (Mutianyu)', C.mon, 'Pékin', 40.4319, 116.5704, "Section restaurée de la Muraille serpentant sur les crêtes."],
  ['Cité Interdite', C.mon, 'Pékin', 39.9163, 116.3972, "Palais impérial labyrinthique au cœur de Pékin."],
  ['Armée de terre cuite', C.mus, 'Xi an', 34.3853, 109.2785, "Milliers de soldats en terre cuite gardant le tombeau de l'empereur."],
  ['Tour CN', C.mon, 'Toronto', 43.6426, -79.3871, "Tour de 553 m avec plancher de verre vertigineux."],
  ['Lac Louise', C.nat, 'Banff', 51.4254, -116.1773, "Lac glaciaire émeraude au pied des Rocheuses canadiennes."],
  ['Tour de Tokyo', C.mon, 'Tokyo', 35.6586, 139.7454, "Tour rouge inspirée de la Tour Eiffel, illuminée la nuit."],
  ['Carrefour de Shibuya', C.aut, 'Tokyo', 35.6595, 139.7004, "Le passage piéton le plus fréquenté de la planète."],
  ['Fushimi Inari', C.mon, 'Kyoto', 34.9671, 135.7727, "Sentier de milliers de torii vermillon à flanc de colline."],
  ['Kinkaku-ji', C.mon, 'Kyoto', 35.0394, 135.7292, "Pavillon d'Or se reflétant dans son étang miroir."],
  ['Fontaine de Trevi', C.mon, 'Rome', 41.9009, 12.4833, "Fontaine baroque où l'on jette une pièce pour revenir à Rome."],
  ['Basilique Saint-Pierre', C.mon, 'Rome', 41.9022, 12.4539, "Cœur du Vatican, dôme de Michel-Ange et place du Bernin."],
  ['Panthéon de Rome', C.mon, 'Rome', 41.8986, 12.4769, "Temple antique au dôme parfait percé d'un oculus."],
  ['Place Saint-Marc', C.mon, 'Venise', 45.4341, 12.3388, "Salon de Venise entre basilique byzantine et campanile."],
  ['Parc Güell', C.prc, 'Barcelone', 41.4145, 2.1527, "Parc onirique de Gaudí aux mosaïques colorées."],
  ['Las Ramblas', C.aut, 'Barcelone', 41.3809, 2.1730, "Avenue piétonne vivante reliant la Plaça Catalunya au port."],
  ['Palais Royal de Madrid', C.mon, 'Madrid', 40.4180, -3.7143, "Plus grand palais royal d'Europe occidentale."],
  ['Maison d Anne Frank', C.mus, 'Amsterdam', 52.3752, 4.8840, "Maison-musée bouleversante au bord du canal Prinsengracht."],
  ['Tour de Belém', C.mon, 'Lisbonne', 38.6916, -9.2160, "Tour fortifiée manuéline gardant l'entrée du Tage."],
  ['Caldeira de Santorin', C.nat, 'Santorin', 36.3932, 25.4615, "Villages blancs à dômes bleus surplombant la mer Égée."],
  ['Plage de Patong', C.plg, 'Phuket', 7.9038, 98.2970, "Plage festive de Phuket, eaux chaudes et vie nocturne."],
];

// ── Pools de noms pour 90 nouveaux users (multi-culturels) ──────────────────
const PRENOMS = ['Hugo', 'Léa', 'Sofia', 'Mehdi', 'Chloé', 'Adam', 'Inès', 'Tom', 'Jade', 'Noah',
  'Manon', 'Rayan', 'Louise', 'Gabriel', 'Sara', 'Nathan', 'Lina', 'Théo', 'Anaïs', 'Enzo',
  'Maya', 'Liam', 'Zoé', 'Youssef', 'Clara', 'Ethan', 'Nour', 'Paul', 'Eva', 'Samuel',
  'Alice', 'Karim', 'Juliette', 'Diego', 'Mia', 'Hiroshi', 'Aiko', 'Wei', 'Priya', 'Carlos',
  'Sofía', 'Lucas', 'Emma', 'Mateo', 'Olivia', 'Léna', 'Marco', 'Giulia', 'Pedro', 'Amélie'];
const NOMS = ['Bernard', 'Garcia', 'Rossi', 'Nakamura', 'Silva', 'Müller', 'Lopez', 'Dubois', 'Ferrari', 'Sato',
  'Petit', 'Hernandez', 'Schneider', 'Costa', 'Yamamoto', 'Roux', 'Marino', 'Fontaine', 'Mendes', 'Wong',
  'Girard', 'Torres', 'Bianchi', 'Kobayashi', 'Mercier', 'Diaz', 'Lefebvre', 'Greco', 'Ali', 'Sharma',
  'Morel', 'Ruiz', 'Conti', 'Tanaka', 'Andre', 'Blanc', 'Romano', 'Faure', 'Santos', 'Chen',
  'Lambert', 'Vidal', 'Esposito', 'Ito', 'Robin', 'Castro', 'Moreau', 'Gauthier', 'Reyes', 'Fischer'];

const BIOS = [
  "Voyageur compulsif, toujours un billet en poche.",
  "J'aime les vieilles pierres et les marchés locaux.",
  "Photographe amateur en quête de lumière.",
  "Plutôt plages désertes que grandes villes.",
  "Foodie : je voyage d'abord pour manger.",
  "Randonnée, volcans et grands espaces.",
  "Architecture et musées sont ma drogue.",
  "Carnet de voyage rempli, jamais rassasié.",
  "", "", "",
];

// ── Templates d'avis par note ───────────────────────────────────────────────
const TITRES = {
  5: ['Inoubliable', 'Coup de cœur absolu', 'À voir absolument', 'Magique', 'Incontournable', 'Le clou du voyage', 'Splendide', 'Au-delà des attentes'],
  4: ['Très belle découverte', 'Vraiment chouette', 'On a adoré', 'Belle surprise', 'Ça vaut le détour', 'Excellent moment'],
  3: ['Sympa sans plus', 'Correct', 'Mitigé', 'Bien mais bondé', 'Pas mal', 'Honnête'],
  2: ['Un peu déçu', 'Surcoté à mon goût', 'Bof', 'Attentes trop hautes'],
  1: ['Grosse déception', 'À éviter', 'Vraiment décevant'],
};
const TXT = {
  5: [
    "Une expérience qui restera gravée. On y retournerait sans hésiter.",
    "Tout simplement parfait, du début à la fin. À ne manquer sous aucun prétexte.",
    "Le genre d'endroit qui justifie tout le voyage à lui seul.",
    "Bluffant en vrai, bien au-delà des photos. Allez-y tôt pour en profiter au calme.",
    "Émotion garantie. On en prend plein les yeux à chaque instant.",
    "Mon meilleur souvenir de ce séjour, sans aucune hésitation.",
  ],
  4: [
    "Vraiment très bien, juste un peu de monde aux heures de pointe.",
    "Superbe découverte, on recommande chaudement. Prévoyez du temps.",
    "Très agréable, le cadre est magnifique. Un poil cher mais ça vaut le coup.",
    "On a passé un excellent moment, à refaire. Petit conseil : réservez en ligne.",
    "Belle étape du voyage, l'ambiance est top.",
  ],
  3: [
    "Correct dans l'ensemble, mais j'en attendais peut-être un peu plus.",
    "Sympa à voir une fois, sans être transcendant. Beaucoup de monde.",
    "Mitigé : le lieu est joli mais l'organisation laisse à désirer.",
    "Pas désagréable, mais clairement orienté touristes.",
    "Ça se visite, mais ne déplacez pas votre itinéraire juste pour ça.",
  ],
  2: [
    "Franchement surcoté par rapport à la réputation. Décevant.",
    "Trop de monde, trop cher, pas à la hauteur.",
    "L'attente interminable gâche complètement l'expérience.",
    "Bof, je ne reviendrai pas. Mieux vaut explorer ailleurs.",
  ],
  1: [
    "Grosse déception, je déconseille. Aucun intérêt selon moi.",
    "À éviter : foule, arnaques et zéro charme.",
    "On s'est sentis pris pour des pigeons. Une perte de temps.",
  ],
};

// ── Génération ──────────────────────────────────────────────────────────────
const out = [];
out.push("-- Seed 'monde vivant' — GÉNÉRÉ par _generate_monde_vivant.js. Ne pas éditer à la main.");
out.push("-- Idempotent : peut être rejoué (INSERT IGNORE / NOT EXISTS).");
out.push("USE abs_db;");
out.push("");

// Pays
out.push("-- Pays");
out.push("INSERT IGNORE INTO pays (nom, code_iso, continent) VALUES");
out.push(PAYS.map(p => `('${esc(p[0])}', '${p[1]}', '${esc(p[2])}')`).join(',\n') + ";");
out.push("");

// Villes (idempotent via NOT EXISTS)
out.push("-- Villes");
for (const [nom, iso] of VILLES) {
  out.push(
    `INSERT INTO ville (nom, id_pays) SELECT '${esc(nom)}', p.id_pays FROM pays p WHERE p.code_iso='${iso}' ` +
    `AND NOT EXISTS (SELECT 1 FROM ville v WHERE v.nom='${esc(nom)}' AND v.id_pays=p.id_pays);`
  );
}
out.push("");

// Lieux (idempotent via NOT EXISTS sur le nom)
out.push("-- Lieux");
for (const [nom, cat, ville, lat, lng, desc] of LIEUX) {
  out.push(
    `INSERT INTO lieu (nom, type, icon, description, latitude, longitude, id_categorie, id_ville) ` +
    `SELECT '${esc(nom)}', 'monument', '📍', '${esc(desc)}', ${lat}, ${lng}, ` +
    `(SELECT id_categorie FROM categorie_lieu WHERE libelle='${esc(cat)}' LIMIT 1), ` +
    `(SELECT id_ville FROM ville WHERE nom='${esc(ville)}' LIMIT 1) ` +
    `WHERE NOT EXISTS (SELECT 1 FROM lieu WHERE nom='${esc(nom)}');`
  );
}
out.push("");

// Users (90 nouveaux)
const users = [];
const usedEmail = new Set();
let ui = 0;
while (users.length < 90) {
  const prenom = PRENOMS[ui % PRENOMS.length];
  const nom = NOMS[(ui * 7 + 3) % NOMS.length];
  ui++;
  const base = `${prenom}.${nom}`.toLowerCase()
    .normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z.]/g, '');
  let email = `${base}@example.com`;
  let n = 1;
  while (usedEmail.has(email)) { email = `${base}${++n}@example.com`; }
  usedEmail.add(email);
  users.push({ prenom, nom, email, bio: pick(BIOS) });
}

out.push("-- Utilisateurs (mot de passe = 'Password1' pour tous)");
const HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
out.push("INSERT IGNORE INTO utilisateur (nom, prenom, email, password_hash, avatar_url, bio, id_role) VALUES");
out.push(users.map(u => {
  const avatar = `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(u.prenom + u.nom)}`;
  return `('${esc(u.nom)}', '${esc(u.prenom)}', '${esc(u.email)}', '${HASH}', '${esc(avatar)}', ${u.bio ? `'${esc(u.bio)}'` : 'NULL'}, 3)`;
}).join(',\n') + ";");
out.push("");

// Avis : chaque user note 2 à 4 lieux distincts
out.push("-- Avis (2 à 4 par utilisateur, notes pondérées, ~15% privés)");
const lieuNoms = LIEUX.map(l => l[0]);
let nbAvis = 0;
const avisPourComm = []; // garde quelques (titre, lieu, email) pour commenter ensuite
for (const u of users) {
  const nb = 2 + Math.floor(rng() * 3); // 2,3,4
  const choisis = new Set();
  while (choisis.size < nb) { choisis.add(pick(lieuNoms)); }
  for (const lieu of choisis) {
    // notes pondérées : surtout 4-5, un peu de 3, rarement 1-2
    const r = rng();
    const note = r < 0.42 ? 5 : r < 0.72 ? 4 : r < 0.88 ? 3 : r < 0.96 ? 2 : 1;
    const titre = pick(TITRES[note]);
    const txt = pick(TXT[note]);
    const vis = chance(0.15) ? 'prive' : 'public';
    out.push(
      `INSERT IGNORE INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu) ` +
      `SELECT ${note}, '${esc(titre)}', '${esc(txt)}', '${vis}', ` +
      `(SELECT id_utilisateur FROM utilisateur WHERE email='${esc(u.email)}'), ` +
      `(SELECT id_lieu FROM lieu WHERE nom='${esc(lieu)}' LIMIT 1);`
    );
    nbAvis++;
    if (vis === 'public' && avisPourComm.length < 40 && chance(0.3)) {
      avisPourComm.push({ titre, lieu, email: u.email });
    }
  }
}
out.push("");

// Commentaires : sur des avis publics existants
out.push("-- Commentaires sur quelques avis");
const COMMS = [
  "Tout à fait d'accord avec toi !", "Bon à savoir, merci pour le conseil.",
  "J'y étais le mois dernier, exactement la même impression.", "Hâte d'y aller grâce à cet avis.",
  "Pas convaincu perso, mais je comprends le point de vue.", "Le meilleur moment reste tôt le matin.",
  "Merci pour le retour, ça aide à préparer le voyage.", "Complètement validé, magique en vrai !",
];
let nbComm = 0;
for (const a of avisPourComm) {
  const auteur = pick(users).email;
  if (auteur === a.email) continue; // pas commenter sous soi-même (cosmétique)
  const texte = pick(COMMS);
  out.push(
    `INSERT INTO commentaire (texte, id_avis, id_utilisateur) ` +
    `SELECT '${esc(texte)}', ` +
    `(SELECT id_avis FROM avis WHERE titre='${esc(a.titre)}' AND id_lieu=(SELECT id_lieu FROM lieu WHERE nom='${esc(a.lieu)}' LIMIT 1) LIMIT 1), ` +
    `(SELECT id_utilisateur FROM utilisateur WHERE email='${esc(auteur)}') ` +
    `FROM DUAL WHERE (SELECT id_avis FROM avis WHERE titre='${esc(a.titre)}' AND id_lieu=(SELECT id_lieu FROM lieu WHERE nom='${esc(a.lieu)}' LIMIT 1) LIMIT 1) IS NOT NULL;`
  );
  nbComm++;
}
out.push("");

// Likes sur avis : un échantillon
out.push("-- Likes sur des avis publics (échantillon)");
out.push(
  "INSERT IGNORE INTO like_avis (id_utilisateur, id_avis) " +
  "SELECT u.id_utilisateur, a.id_avis FROM utilisateur u " +
  "JOIN avis a ON a.visibility='public' " +
  "WHERE u.id_role=3 AND (u.id_utilisateur + a.id_avis) % 7 = 0 " +
  "LIMIT 400;"
);
out.push("");

// Images : photo stable par lieu (placeholder fiable, remplaçable par de vraies URLs)
out.push("-- Images des lieux (photo stable seedée par id_lieu)");
out.push("UPDATE lieu SET image_url = CONCAT('https://picsum.photos/seed/abslieu', id_lieu, '/640/400') WHERE image_url IS NULL OR image_url = '';");
out.push("");

out.push(`-- Récapitulatif attendu : +${PAYS.length} pays (IGNORE), +${VILLES.length} villes, +${LIEUX.length} lieux, +${users.length} users, ~${nbAvis} avis, ~${nbComm} commentaires.`);

const target = path.join(__dirname, 'seed_monde_vivant.sql');
fs.writeFileSync(target, out.join('\n'), 'utf8');
console.log(`OK → ${target}`);
console.log(`users=${users.length} lieux=${LIEUX.length} villes=${VILLES.length} pays=${PAYS.length} avis~${nbAvis} comms~${nbComm}`);
