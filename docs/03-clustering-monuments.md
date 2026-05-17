# 03 — Clustering des monuments

## Contexte

Quand la base contiendra beaucoup de monuments (objectif : plusieurs centaines
voire milliers), afficher **tous** les pins en même temps en vue dézoomée
poserait deux problèmes :

1. **Visuel** : on aurait une bouillie de pins superposés, illisible.
2. **Performance** : chaque pin DOM est une `<div>` HTML, donc beaucoup
   de pins = beaucoup d'éléments à mettre à jour à chaque mouvement de
   carte. Au-delà de quelques centaines, le navigateur rame.

Le bon pattern UX dans ce cas est le **clustering** : on regroupe les pins
proches dans un cercle qui indique le nombre de lieux. Quand on clique
dessus, ça zoome jusqu'à éclater le groupe.

## Solution retenue

Utilisation de la **fonction native de clustering** de Mapbox sur une source
GeoJSON. Au lieu de pins DOM, on utilise des **couches Mapbox** (rendues
directement dans le canvas WebGL), ce qui est beaucoup plus rapide.

Trois couches sur la même source :
- `clusters-monuments` : les cercles oranges avec un nombre dedans
- `clusters-count` : le label du nombre (couche symbole superposée)
- `unclustered-monuments` : les pins individuels qui ne sont pas dans un cluster

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Garder tous les pins en DOM** | Lag visible à partir de ~200 markers. Ingérable à long terme. |
| **Algorithme de clustering maison** | Réinventer la roue ; Mapbox fait ça en C++ optimisé en interne. |
| **Supercluster en JS direct** | Possible mais nécessite de gérer la mise à jour manuellement à chaque move/zoom. Mapbox l'intègre déjà. |
| **Cacher les pins en dessous d'un certain zoom** | C'est ce qu'on faisait au début mais on perdait l'info "il y a des choses ici". Un cluster montre qu'il y a quelque chose **et** son volume. |

## Code expliqué ligne par ligne

### Bloc 1 — Préparation des features GeoJSON

```js
function addMonumentClusters() {
  if (map.getSource('monuments-source')) return;

  var features = places
    .filter(function (p) { return (p.type || 'monument') === 'monument'; })
    .map(function (p) {
      var lat = parseFloat(p.lat);
      var lng = parseFloat(p.lng);
      if (isNaN(lat) || isNaN(lng)) return null;
      return {
        type: 'Feature',
        properties: {
          id_lieu: p.id_lieu,
          name:    p.name,
          rating:  p.avg_rating || 0,
        },
        geometry: { type: 'Point', coordinates: [lng, lat] },
      };
    })
    .filter(function (f) { return f !== null; });
```

- `if (map.getSource('monuments-source')) return;` : garde anti double-ajout.
  Si la source existe déjà, on ne fait rien.
- `places.filter(...)` : on ne garde que les lieux de type "monument" (les
  pays et villes ont leur propre traitement via `renderMarkers`).
- `.map(...)` : transforme chaque place en **feature GeoJSON**.
- Format GeoJSON Feature :
  ```json
  {
    "type": "Feature",
    "properties": { ... },  // données métier accessibles depuis Mapbox
    "geometry": {
      "type": "Point",
      "coordinates": [lng, lat]   // ATTENTION ordre [longitude, latitude]
    }
  }
  ```
- On stocke `id_lieu`, `name` et `rating` dans les properties pour pouvoir
  les récupérer au clic.
- `.filter(function (f) { return f !== null; })` : nettoie les features
  invalides (coordonnées manquantes).

### Bloc 2 — Création de la source avec clustering activé

```js
map.addSource('monuments-source', {
  type:           'geojson',
  data:           { type: 'FeatureCollection', features: features },
  cluster:        true,
  clusterMaxZoom: 9,
  clusterRadius:  50,
});
```

- `type: 'geojson'` : on construit une source GeoJSON in-memory (au lieu de
  charger une URL).
- `data: { type: 'FeatureCollection', features: features }` : structure
  GeoJSON standard. Un FeatureCollection est juste un tableau de Feature.
- **`cluster: true`** : active la magie. Mapbox va automatiquement regrouper
  les points proches.
- **`clusterMaxZoom: 9`** : zoom **à partir duquel** les clusters disparaissent
  et les points individuels apparaissent. Au-dessus de 9, plus de cluster.
- **`clusterRadius: 50`** : rayon en pixels dans lequel les points sont
  considérés "proches" et regroupés. 50 est un bon compromis : assez petit
  pour ne pas tout fusionner, assez grand pour éviter les chevauchements.

### Bloc 3 — Couche des cercles cluster

```js
map.addLayer({
  id:     'clusters-monuments',
  type:   'circle',
  source: 'monuments-source',
  filter: ['has', 'point_count'],
  paint: {
    'circle-color':        ['step', ['get', 'point_count'], '#b56a1a', 10, '#d4842a', 30, '#e8a347'],
    'circle-radius':       ['step', ['get', 'point_count'], 16, 10, 22, 30, 28],
    'circle-stroke-width': 2,
    'circle-stroke-color': 'rgba(255,255,255,0.6)',
    'circle-opacity':      0.92,
  },
});
```

- `type: 'circle'` : type de couche qui dessine un cercle plein à chaque
  point de la source.
- `filter: ['has', 'point_count']` : on ne dessine cette couche que pour les
  features qui ont une propriété `point_count` (= les clusters générés
  automatiquement par Mapbox). Les points individuels n'en ont pas.

- `'circle-color': ['step', ['get', 'point_count'], '#b56a1a', 10, '#d4842a', 30, '#e8a347']` :
  syntaxe d'expression `step` (équivalent à un escalier ou un `switch` par paliers).
  Décodage :
  - `['get', 'point_count']` → lit la propriété point_count (nombre de points
    dans le cluster).
  - Valeur par défaut (en dessous de 10) → couleur `#b56a1a` (orange foncé)
  - À partir de 10 → `#d4842a` (orange moyen)
  - À partir de 30 → `#e8a347` (orange clair)
  - Logique : plus le cluster est gros, plus la couleur est claire = signal visuel.

- `'circle-radius': ['step', ['get', 'point_count'], 16, 10, 22, 30, 28]` :
  même logique pour le rayon (en pixels). Petit cluster = 16 px, gros cluster = 28 px.

- `'circle-stroke-width': 2` : épaisseur de la bordure du cercle.
- `'circle-stroke-color': 'rgba(255,255,255,0.6)'` : bordure blanche
  semi-transparente (donne un effet "halo" lumineux sur fond sombre).
- `'circle-opacity': 0.92` : légère transparence pour laisser deviner la
  carte en dessous.

### Bloc 4 — Couche du compteur (label dans le cluster)

```js
map.addLayer({
  id:     'clusters-count',
  type:   'symbol',
  source: 'monuments-source',
  filter: ['has', 'point_count'],
  layout: {
    'text-field': '{point_count_abbreviated}',
    'text-size':  13,
    'text-font':  ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
  },
  paint: { 'text-color': '#fff' },
});
```

- `type: 'symbol'` : type de couche pour afficher du texte ou des icônes.
- `'text-field': '{point_count_abbreviated}'` : Mapbox génère
  automatiquement deux propriétés sur les clusters :
  - `point_count` : nombre exact (ex: 1234)
  - `point_count_abbreviated` : version abrégée (ex: "1.2k")
- `'text-font': [...]` : polices à essayer (la première qui est dispo).
  DIN Offc Pro Medium est la police par défaut de Mapbox.

Cette couche est dessinée **par-dessus** `clusters-monuments` parce qu'elle
est ajoutée après dans le code (l'ordre d'ajout détermine l'ordre Z).

### Bloc 5 — Couche des pins individuels (non clusterisés)

```js
map.addLayer({
  id:     'unclustered-monuments',
  type:   'circle',
  source: 'monuments-source',
  filter: ['!', ['has', 'point_count']],
  paint: {
    'circle-color':        '#d4842a',
    'circle-radius':       8,
    'circle-stroke-width': 2,
    'circle-stroke-color': 'rgba(255,255,255,0.7)',
    'circle-opacity':      0.95,
  },
});
```

- `filter: ['!', ['has', 'point_count']]` : le `!` est la négation.
  L'expression dit : "garder les features qui **n'ont pas** `point_count`",
  c'est-à-dire les points individuels (pas les clusters).
- Petits cercles oranges de 8 px qui apparaissent au zoom ≥ 9.
- Ils sont rendus directement par WebGL, pas en DOM, donc on peut en afficher
  beaucoup sans perte de perfs (contrairement aux marqueurs DOM).

### Bloc 6 — Clic sur un cluster → zoom à l'intérieur

```js
map.on('click', 'clusters-monuments', function (e) {
  var f = map.queryRenderedFeatures(e.point, { layers: ['clusters-monuments'] })[0];
  if (!f) return;
  map.getSource('monuments-source').getClusterExpansionZoom(f.properties.cluster_id, function (err, z) {
    if (err) return;
    map.easeTo({ center: f.geometry.coordinates, zoom: z, duration: 800 });
  });
});
```

- `map.queryRenderedFeatures(e.point, ...)` : recherche toutes les features
  rendues au point écran `e.point`, en se limitant à certaines couches.
  On prend la première (`[0]`).
- `getClusterExpansionZoom(cluster_id, callback)` : méthode magique de
  Mapbox qui calcule **automatiquement** le niveau de zoom auquel le cluster
  va éclater (c'est-à-dire au zoom où il se sépare en plusieurs clusters
  plus petits ou en pins individuels). Callback async parce qu'il faut
  parfois interroger les tuiles du worker.
- `map.easeTo(...)` : animation de caméra (variante moins spectaculaire que
  `flyTo`, utile pour des transitions courtes). Durée 800 ms.

### Bloc 7 — Clic sur un pin individuel → popup

```js
map.on('click', 'unclustered-monuments', function (e) {
  if (!e.features.length) return;
  var idLieu = e.features[0].properties.id_lieu;
  var coords = e.features[0].geometry.coordinates.slice();
  var place  = trouverLieuParId(idLieu);
  if (!place) return;
  new mapboxgl.Popup({ offset: 14, maxWidth: '280px', className: 'abs-popup' })
    .setLngLat(coords)
    .setHTML(buildPopup(place))
    .addTo(map);
});
```

- `e.features[0].properties.id_lieu` : l'id qu'on avait mis dans les
  properties à la création de la source.
- `e.features[0].geometry.coordinates.slice()` : on **copie** le tableau
  des coordonnées avec `.slice()`. Pourquoi ? Parce que sinon, si la carte
  est défilée sur plusieurs world-copies (cas désactivé chez nous mais
  c'est une bonne pratique), Mapbox pourrait modifier le tableau original.
- `trouverLieuParId(idLieu)` : helper qui retrouve l'objet complet dans
  `places` à partir de l'id (parce qu'on ne stocke pas tout dans les
  properties pour rester léger).
- Le popup est créé puis ancré à `coords` et ajouté à la map.

### Bloc 8 — Curseurs au survol

```js
map.on('mouseenter', 'clusters-monuments',     function () { map.getCanvas().style.cursor = 'pointer'; });
map.on('mouseleave', 'clusters-monuments',     function () { map.getCanvas().style.cursor = ''; });
map.on('mouseenter', 'unclustered-monuments',  function () { map.getCanvas().style.cursor = 'pointer'; });
map.on('mouseleave', 'unclustered-monuments',  function () { map.getCanvas().style.cursor = ''; });
```

`mouseenter` / `mouseleave` sont les variantes "non-bubbling" de `mouseover`/
`mouseout`. Elles signalent l'entrée/sortie de la couche.

On change le curseur en `pointer` (petite main) pour indiquer que c'est
cliquable, et on remet la valeur vide à la sortie (le navigateur reprend
le curseur par défaut).

## Évolutions possibles

- **Couleur par catégorie** au lieu de par taille : musée en bleu, restaurant
  en jaune, etc. Faisable avec une expression `match` sur `categorie`.
- **Animation au survol des clusters** : grossir légèrement quand le curseur
  passe dessus (via `'circle-radius-transition'`).
- **Filtre par catégorie** depuis l'UI : utiliser `setFilter` sur la couche
  `unclustered-monuments`.
- **Spiderfy** : quand un cluster ne peut plus se séparer (points GPS
  exactement identiques), montrer une "araignée" qui éclate en étoile.
  Pas natif Mapbox, il existe des plugins externes.
- **Cluster mixte** (pays + ville + monument) : actuellement on ne clusterise
  que les monuments. On pourrait étendre, mais ça mélangerait des sémantiques
  différentes.
