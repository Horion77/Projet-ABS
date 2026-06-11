# 01 — Architecture Mapbox : init, projection globe, atmosphère, terrain

## Contexte

Le projet ABS est inspiré de Letterboxd mais pour les voyages. Le but est
d'avoir une **carte interactive** sur laquelle on peut explorer des pays,
des villes et des monuments, et lire des avis. Une carte 2D plate (style
Google Maps) était possible, mais on voulait un rendu **plus immersif**
pour donner envie de voyager : une **carte 3D façon globe** avec ciel étoilé.

## Solution retenue

Utilisation de **Mapbox GL JS v3.4.0** avec :
- la projection **`globe`** (sphère 3D au lieu d'une carte plate)
- une **atmosphère** (fog) bleue qui simule la diffusion de la lumière
- un **terrain 3D** (relief des montagnes) avec exagération ×1.5
- une couche **sky** pour ajouter des étoiles en arrière-plan en style sombre

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Leaflet** | Très bien pour le 2D mais pas de projection globe native, pas de terrain 3D. |
| **Cesium** | Globe 3D ultra-puissant mais beaucoup plus lourd (~3 Mo de JS), plus complexe pour un projet étudiant, et le rendu visuel des styles cartographiques est moins propre que Mapbox. |
| **Google Maps API** | Payant au-delà d'un certain quota, et le rendu 3D nécessite la version la plus chère. |
| **OpenLayers** | Très complet mais courbe d'apprentissage plus raide, pas de globe 3D natif. |

Le choix s'est porté sur Mapbox parce que c'est gratuit jusqu'à 50 000 chargements
par mois, l'API est bien documentée, et la projection globe est intégrée d'usine.

## Code expliqué ligne par ligne

### Bloc 1 — Garde de chargement

```js
(function () {
  'use strict';
  if (typeof mapboxgl === 'undefined' || !window.MAP_DATA) return;
```

- `(function () { ... }())` : c'est une **IIFE** (Immediately Invoked Function Expression).
  Tout ce qui est déclaré dedans reste local et ne pollue pas `window`.
  C'est l'équivalent d'un `namespace` ou d'un fichier-module en C++/Java.
- `'use strict'` : active le mode strict (erreurs plus parlantes, interdit `this` global, etc.).
- `if (typeof mapboxgl === 'undefined' ...)` : on vérifie que la librairie Mapbox
  a bien été chargée par le `<script>` en CDN dans la page. Si elle n'est pas là
  on ne fait rien (évite des erreurs en cascade dans la console).
- `!window.MAP_DATA` : `MAP_DATA` est l'objet injecté par PHP dans la page
  (token Mapbox, liste des lieux, etc.). S'il n'existe pas, on n'a rien à faire.

### Bloc 2 — Récupération des données injectées par PHP

```js
var d         = window.MAP_DATA;
var places    = d.places    || [];
var countries = d.countries || [];
var pathLieu  = d.placePath || '/lieu';
var pathPays  = d.paysPath  || '/pays';
```

- `window.MAP_DATA` est rempli côté serveur par `carte/index.php` via
  `<?= json_encode($mapData) ?>`. C'est un pont PHP → JavaScript.
- `|| []` et `|| '/lieu'` sont des **valeurs par défaut** : si la propriété
  est `undefined` ou `null`, on utilise la valeur à droite.
- `places` contient la liste des lieux (pays, villes, monuments) avec leurs
  coordonnées GPS, notes moyennes, etc.
- `countries` contient la liste des pays référencés en base, avec leurs stats
  agrégées (note moyenne, nombre de lieux).
- `pathLieu` / `pathPays` : URLs vers les pages détaillées. Elles sont
  générées côté PHP par le helper `url()`, pour gérer correctement le
  `base_url` qui peut changer entre l'environnement local (`/Projet-ABS/public`)
  et la prod.

### Bloc 3 — Token Mapbox

```js
mapboxgl.accessToken = d.token;
```

- Mapbox demande un token d'authentification pour chaque session.
- Le token est stocké dans `application.php` côté serveur et transmis
  via `MAP_DATA` (jamais hardcodé dans le JS pour ne pas le commit
  par erreur).

### Bloc 4 — Configuration des styles

```js
var STYLES = {
  dark:      { url: 'mapbox://styles/mapbox/dark-v11',              fog: true,  buildings: true,  terrain: true  },
  satellite: { url: 'mapbox://styles/mapbox/satellite-streets-v12', fog: true,  buildings: true,  terrain: true  },
  outdoors:  { url: 'mapbox://styles/mapbox/outdoors-v12',          fog: false, buildings: false, terrain: true  },
  streets:   { url: 'mapbox://styles/mapbox/streets-v12',           fog: false, buildings: false, terrain: false },
};
var currentStyleKey = 'dark';
```

- Mapbox propose plusieurs **styles** prédéfinis (sombre, satellite, plein air, rues).
- Chaque style est identifié par une URL `mapbox://styles/...`
- À chaque style on associe des **flags** maison :
  - `fog: true` → on active l'atmosphère sur ce style
  - `buildings: true` → on active les bâtiments 3D en vue rapprochée
  - `terrain: true` → on active le relief
- `currentStyleKey` retient le style actuellement affiché (par défaut "dark").

### Bloc 5 — Seuils de zoom par type

```js
var ZOOM_RANGE = {
  pays:     { min: 0, max: 5.5 },
  ville:    { min: 4, max: 9   },
  monument: { min: 9, max: 22  },
};
```

- Un marqueur "pays" (gros pin avec drapeau) ne doit s'afficher que quand
  on est dézoomé (zoom 0 à 5.5).
- Un marqueur "ville" est visible entre 4 et 9.
- Un marqueur "monument" individuel n'apparaît qu'à partir du zoom 9.
  En dessous, c'est le **clustering** (voir `03-clustering-monuments.md`)
  qui prend le relais pour éviter d'afficher des centaines de pins en même temps.

### Bloc 6 — Liste des continents pour le panneau navigation

```js
var CONTINENTS = [
  { name: 'Europe',    lat: 54,  lng: 15,   zoom: 3.2 },
  { name: 'Asie',      lat: 34,  lng: 100,  zoom: 2.8 },
  { name: 'Amériques', lat: 10,  lng: -80,  zoom: 2.3 },
  { name: 'Afrique',   lat: 0,   lng: 20,   zoom: 3.0 },
  { name: 'Oceanie',   lat: -25, lng: 135,  zoom: 3.2 },
];
```

Liste statique des continents pour le bouton "voler vers" dans le panneau
de gauche. Chaque continent a son centre GPS et un niveau de zoom qui
encadre bien la zone.

### Bloc 7 — État global

```js
var activeCountryId   = null;
var markers           = [];
var hoveredCountryId  = null;
var selectedCountryId = null;
```

- `activeCountryId` : id du pays actuellement filtré (les markers d'autres
  pays sont cachés).
- `markers` : tableau de tous les marqueurs HTML actuellement sur la carte.
  Sert à les supprimer/recréer quand on change de filtre.
- `hoveredCountryId` : ISO3 du pays survolé (utilisé pour le hover-card).
- `selectedCountryId` : ISO3 du pays cliqué (mis en surbrillance).

### Bloc 8 — Lecture de l'URL au chargement

```js
function lireEtatUrl() {
  var p    = new URLSearchParams(window.location.search);
  var lng  = parseFloat(p.get('lng'));
  var lat  = parseFloat(p.get('lat'));
  var zoom = parseFloat(p.get('zoom'));
  if (isNaN(lng) || isNaN(lat) || isNaN(zoom)) return null;
  return { lng: lng, lat: lat, zoom: zoom };
}
var etatUrl = lireEtatUrl();
```

Permet de partager une vue précise via une URL. Voir `06-url-sync.md` pour le détail.

### Bloc 9 — Création de la carte

```js
var CENTRE_DEFAUT = [20, 30];
var ZOOM_DEFAUT   = 1.8;

var map = new mapboxgl.Map({
  container:         'map',
  style:             STYLES[currentStyleKey].url,
  center:            etatUrl ? [etatUrl.lng, etatUrl.lat] : CENTRE_DEFAUT,
  zoom:              etatUrl ? etatUrl.zoom              : ZOOM_DEFAUT,
  pitch:             45,
  bearing:           -10,
  projection:        'globe',
  antialias:         true,
  renderWorldCopies: false,
});
```

Détail des options :

| Option | Rôle |
|---|---|
| `container: 'map'` | Id du `<div>` dans lequel Mapbox va injecter la carte (cf. `carte/index.php` ligne 83). |
| `style` | URL du style Mapbox à charger. |
| `center` | Position initiale `[longitude, latitude]`. **Attention** : Mapbox prend `[lng, lat]` (contre-intuitif, on a tendance à mettre lat en premier). |
| `zoom` | Niveau de zoom initial (0 = vue planète entière, 22 = bâtiments individuels). |
| `pitch: 45` | Inclinaison de la caméra en degrés. 0 = vue de dessus, 60 = vue très inclinée. 45 donne un effet 3D sans être trop incliné. |
| `bearing: -10` | Rotation de la carte autour de l'axe vertical (l'azimut). -10° fait pencher légèrement à gauche. |
| `projection: 'globe'` | Active la projection sphérique 3D. Sans ça, on aurait une carte de Mercator plate. |
| `antialias: true` | Activer l'anti-crénelage (lisse les bords). Légèrement plus lourd mais beaucoup plus joli. |
| `renderWorldCopies: false` | Empêche de "répéter" le monde sur les côtés quand on défile horizontalement. Évite de voir 3 fois l'Europe en s'éloignant. |

La valeur de `center: [20, 30]` est expliquée en commentaire dans le code :
"lat 30 (au lieu de 20) → le globe est visuellement plus haut". On avait
remarqué que le globe paraissait un peu trop bas dans le viewport au
chargement ; en augmentant la latitude de centrage on le remonte.

### Bloc 10 — Ajout des contrôles natifs

```js
map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');
```

- `NavigationControl` : ajoute les boutons +/- de zoom, une boussole, et avec
  `visualizePitch: true` un petit indicateur de l'inclinaison de la caméra.
- `FullscreenControl` : bouton plein écran.
- `'bottom-right'` : position dans la carte (Mapbox accepte 4 ancrages :
  top-left, top-right, bottom-left, bottom-right).

### Bloc 11 — Événement `style.load`

```js
map.on('style.load', function () {
  applyAtmosphere();
  applyTerrain();
  addCountryLayer();
  addMonumentClusters();
  renderMarkers();
  map.once('zoom', tryLoadRegions);
  tryLoadRegions();
});
```

`style.load` est tiré **à chaque fois que le style change** (pas juste au
premier chargement). C'est important parce que :
- `map.setStyle(...)` (changement de style) **efface** toutes les couches
  et sources personnalisées. Il faut donc les **re-ajouter** à chaque fois.
- C'est pour ça qu'on met TOUS les ajouts de couches dans ce callback.

Différence entre `map.on(...)` et `map.once(...)` :
- `on` : écouteur permanent (déclenché à chaque événement).
- `once` : écouteur déclenché **une seule fois** puis automatiquement retiré.

### Bloc 12 — Atmosphère

```js
function applyAtmosphere() {
  var cfg = STYLES[currentStyleKey] || {};
  if (cfg.fog) {
    map.setFog({
      color:            'rgb(10, 10, 30)',
      'high-color':     'rgb(36, 92, 223)',
      'horizon-blend':  0.015,
      'space-color':    'rgb(4, 4, 18)',
      'star-intensity': 0.85,
    });
```

`map.setFog(...)` configure l'**atmosphère** du globe (uniquement visible
en projection `globe`).

| Propriété | Effet |
|---|---|
| `color` | Couleur du brouillard à hauteur de la surface (proche de l'horizon). |
| `high-color` | Couleur en altitude (le bleu du ciel vu de haut). |
| `horizon-blend` | Largeur de la transition à l'horizon (0 = net, 1 = très diffus). |
| `space-color` | Couleur du fond derrière le globe (l'espace). |
| `star-intensity` | Densité des étoiles dans le fond. 0 = aucune, 1 = très fournies. |

### Bloc 13 — Sky layer

```js
if (!map.getLayer('sky')) {
  map.addLayer({ id: 'sky', type: 'sky', paint: {
    'sky-type':                     'atmosphere',
    'sky-atmosphere-sun':           [0, 90],
    'sky-atmosphere-sun-intensity': 5,
  }});
}
```

- On vérifie d'abord avec `map.getLayer('sky')` que la couche n'existe pas déjà
  (sinon Mapbox jette une erreur "layer with this id already exists").
- `type: 'sky'` est un type de couche spécial qui rend le ciel autour
  de la caméra (utile surtout quand on incline beaucoup la caméra).
- `'sky-atmosphere-sun': [0, 90]` : position du soleil en `[azimuth, élévation]`.
  [0, 90] = soleil au zénith droit devant.

### Bloc 14 — Bâtiments 3D

```js
if (cfg.buildings && map.getSource('composite') && !map.getLayer('3d-buildings')) {
  map.addLayer({
    id: '3d-buildings', source: 'composite', 'source-layer': 'building',
    filter: ['==', 'extrude', 'true'], type: 'fill-extrusion', minzoom: 14,
    paint: {
      'fill-extrusion-color':   '#aaa',
      'fill-extrusion-height':  ['get', 'height'],
      'fill-extrusion-base':    ['get', 'min_height'],
      'fill-extrusion-opacity': 0.6,
    },
  });
}
```

- `source: 'composite'` : c'est la source par défaut de Mapbox qui contient
  les bâtiments, les routes, les frontières, etc.
- `'source-layer': 'building'` : on cible la couche "building" dans cette source.
- `filter: ['==', 'extrude', 'true']` : on filtre pour ne garder que les
  bâtiments marqués "extrudables" (les autres ne sont pas modélisés en 3D
  par Mapbox).
- `type: 'fill-extrusion'` : type de couche qui transforme un polygone 2D
  en volume 3D.
- `'fill-extrusion-height': ['get', 'height']` : la hauteur du bâtiment est
  lue dans la propriété `height` de la feature.
- `minzoom: 14` : visible seulement à partir du zoom 14 (à 13 et moins ça
  serait surchargé).

### Bloc 15 — Terrain 3D

```js
function applyTerrain() {
  var cfg = STYLES[currentStyleKey] || {};
  if (!cfg.terrain) return;
  if (!map.getSource('mapbox-dem')) {
    map.addSource('mapbox-dem', {
      type: 'raster-dem',
      url:  'mapbox://mapbox.mapbox-terrain-dem-v1',
      tileSize: 512, maxzoom: 14,
    });
  }
  map.setTerrain({ source: 'mapbox-dem', exaggeration: 1.5 });
}
```

- **DEM** = Digital Elevation Model. C'est une grille de valeurs d'altitude
  fournie par Mapbox sous forme de tuiles raster.
- `type: 'raster-dem'` : type de source spécial pour les données d'altitude.
- `tileSize: 512` : taille des tuiles en pixels. 512 est la valeur recommandée
  pour le DEM.
- `maxzoom: 14` : Mapbox ne fournit pas de DEM plus précis que le zoom 14.
- `map.setTerrain({ source: ..., exaggeration: 1.5 })` : active le relief.
  L'exaggération 1.5× rend les montagnes plus visibles (sans ça l'effet est
  trop subtil à cause de l'échelle planétaire).

### Bloc 16 — Suivi des mouvements de carte

```js
map.on('movestart', function () { document.getElementById('map').classList.add('map-moving'); });
map.on('moveend',   function () { document.getElementById('map').classList.remove('map-moving'); updateVisibility(); ecrireEtatUrl(); });
map.on('zoom', updateVisibility);
```

- `movestart` / `moveend` : événements tirés quand l'utilisateur commence /
  finit de déplacer la carte (drag, flyTo, etc.).
- On ajoute / retire la classe `map-moving` sur le `<div id="map">` pour
  désactiver certaines interactions CSS pendant le mouvement (curseur,
  transitions...).
- `moveend` déclenche aussi `updateVisibility()` (montre/cache les markers
  selon le zoom actuel) et `ecrireEtatUrl()` (synchronise l'URL).
- `zoom` est tiré en continu pendant un changement de zoom : on appelle
  `updateVisibility` pour que les pins apparaissent/disparaissent
  progressivement et pas seulement à la fin du zoom.

## Évolutions possibles

- **Différencier les styles par flag plus fin** : actuellement chaque style
  a juste 3 booléens (fog/buildings/terrain). On pourrait passer à un objet
  de configuration par style avec couleurs d'atmosphère customisées.
- **Charger Mapbox en local** au lieu du CDN pour fonctionner hors-ligne
  pendant le dev (mais alourdit le repo).
- **Heure dynamique du soleil** : faire varier `sky-atmosphere-sun` en
  fonction de l'heure réelle pour avoir un jour/nuit réaliste.
- **Mettre les seuils de zoom dans une config** côté PHP pour pouvoir les
  ajuster sans toucher au JS.
