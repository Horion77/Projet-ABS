# 05 — Couche régions (Natural Earth)

## Contexte

Mapbox affiche déjà les **frontières des pays**, mais pas les frontières
**internes** (régions, états, départements). On voulait, dès qu'on zoome
un peu, voir un découpage par région pour donner une meilleure
contextualisation géographique.

Le tileset Mapbox ne propose pas ça gratuitement. Il existe en revanche
le dataset **Natural Earth admin-1** (sous licence libre) qui contient
toutes les régions du monde au format GeoJSON.

Problème : le fichier complet fait **~37 Mo**. Le charger au démarrage de
la carte tuerait le temps de chargement initial.

## Solution retenue

**Chargement à la demande (lazy load)** : on n'ajoute la couche `regions`
qu'à partir du **zoom 3.5**, c'est-à-dire quand l'utilisateur commence
à se rapprocher d'une zone précise. En vue globale (zoom 0-3), c'est inutile
de toutes les charger.

Le GeoJSON est servi en static depuis `public/assets/data/regions.json`,
chemin transmis au JS via `MAP_DATA.regionsPath`.

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Charger le GeoJSON au démarrage** | +37 Mo de transfert + ~1-2 s de parsing JSON bloquant le thread principal. Pire UX. |
| **Découper le GeoJSON par pays** | Faisable mais nécessite un script Python en amont, ajoute de la complexité, et le bénéfice est limité une fois en cache navigateur. |
| **Utiliser un tileset Mapbox payant** | Mapbox vend un tileset `mapbox.boundaries-adm1-v3` qui contient les régions, mais c'est dans un plan payant. |
| **Pas afficher les régions du tout** | C'était le cas avant, mais on perdait beaucoup en contextualisation à mid-zoom. |

## Code expliqué ligne par ligne

### Bloc 1 — Mécanisme de déclenchement

```js
map.on('style.load', function () {
  // ...
  map.once('zoom', tryLoadRegions);
  tryLoadRegions();
});
```

- `tryLoadRegions()` est appelé immédiatement (cas où on démarre déjà à un
  zoom élevé via l'URL).
- `map.once('zoom', tryLoadRegions)` arme un déclencheur pour le premier
  zoom à venir.

### Bloc 2 — `tryLoadRegions()` : seuil de zoom

```js
function tryLoadRegions() {
  if (map.getZoom() >= 3.5 && !map.getSource('regions-source')) {
    addRegionsLayer();
  } else if (map.getZoom() < 3.5) {
    map.once('zoom', tryLoadRegions);
  }
}
```

Trois cas possibles :
1. **Zoom ≥ 3.5 et source pas encore chargée** → on charge.
2. **Zoom ≥ 3.5 et déjà chargée** → on ne fait rien (la condition `!map.getSource(...)`
   évite le double-chargement).
3. **Zoom < 3.5** → on n'a pas besoin des régions, on **re-arme** le listener
   pour le prochain zoom (`map.once`). Ça crée une chaîne d'écouteurs qui
   se relancent jusqu'à ce que le seuil soit atteint.

C'est plus efficace qu'un `map.on('zoom', ...)` permanent parce qu'une fois
le seuil dépassé, on n'est plus appelés du tout.

### Bloc 3 — `addRegionsLayer()` : ajout de la source

```js
function addRegionsLayer() {
  var regionsUrl = (d.regionsPath || '');
  if (!regionsUrl) return;

  if (!map.getSource('regions-source')) {
    map.addSource('regions-source', {
      type:      'geojson',
      data:      regionsUrl,
      promoteId: 'ne_id',
    });
  }
```

- `d.regionsPath` : URL passée par PHP (typiquement
  `/Projet-ABS/public/assets/data/regions.json`).
- `type: 'geojson'` + `data: 'url'` : Mapbox va faire un `fetch(url)`
  et parser le JSON en arrière-plan dans un Worker. Le thread principal
  n'est pas bloqué.
- `promoteId: 'ne_id'` : on utilise la propriété `ne_id` (identifiant unique
  Natural Earth) comme id de feature pour le feature-state hover.

### Bloc 4 — Couche fill (remplissage)

```js
var sous = map.getLayer('country-label') ? 'country-label' : undefined;

if (!map.getLayer('regions-fill')) {
  map.addLayer({
    id:     'regions-fill',
    type:   'fill',
    source: 'regions-source',
    minzoom: 4,
    paint: {
      'fill-color':   '#a0c4ff',
      'fill-opacity': ['case',
        ['boolean', ['feature-state', 'hover'], false], 0.18,
        0,
      ],
    },
  }, sous);
}
```

- `minzoom: 4` : la couche n'est rendue qu'à partir du zoom 4 (en dessous,
  les régions sont trop petites de toute façon).
- `'fill-color': '#a0c4ff'` : bleu plus clair que celui des pays pour
  hiérarchiser visuellement (pays = plus foncé, région = plus clair).
- Même pattern d'opacité conditionnelle qu'avec les pays.

### Bloc 5 — Couche outline (contours)

```js
if (!map.getLayer('regions-outline')) {
  map.addLayer({
    id:     'regions-outline',
    type:   'line',
    source: 'regions-source',
    minzoom: 4,
    paint: {
      'line-color':   '#7eb3ff',
      'line-width':   0.6,
      'line-opacity': 0.35,
    },
  });
}
```

Les contours sont **toujours visibles** (pas conditionnels au feature-state)
pour montrer le découpage en permanence. Largeur fine (0.6 px) et opacité
réduite (0.35) pour rester discret.

### Bloc 6 — Hover sur les régions

```js
map.on('mousemove', 'regions-fill', function (e) {
  if (!e.features.length) return;
  var id = e.features[0].id;
  if (hoveredRegionId !== null && hoveredRegionId !== id) {
    map.setFeatureState({ source: 'regions-source', id: hoveredRegionId }, { hover: false });
  }
  hoveredRegionId = id;
  map.setFeatureState({ source: 'regions-source', id: id }, { hover: true });
});

map.on('mouseleave', 'regions-fill', function () {
  if (hoveredRegionId !== null) {
    map.setFeatureState({ source: 'regions-source', id: hoveredRegionId }, { hover: false });
  }
  hoveredRegionId = null;
});
```

Même pattern de change detection que pour les pays (voir `07-optim-mousemove.md`),
en version simplifiée puisqu'on n'a pas de hover-card associée.

À noter : pour la source GeoJSON il n'y a pas de `sourceLayer` à indiquer
dans `setFeatureState` (contrairement aux sources vector).

### Bloc 7 — Clic région : zoom sans remonter au pays

```js
map.on('click', 'regions-fill', function (e) {
  if (!e.features.length) return;
  e.preventDefault();
  var bounds = getFeatureBounds(e.features[0]);
  if (!bounds) return;
  map.fitBounds(bounds, { padding: 60, pitch: 50, duration: 1500, maxZoom: 9, essential: true });
});
```

- `e.preventDefault()` : marque le clic comme "déjà géré". Le handler de la
  couche `pays-fill` vérifie `e.defaultPrevented` au début et abandonne.
  Sans ça, cliquer sur une région déclencherait **aussi** le clic pays
  (puisque la région est par-dessus le pays géographiquement).

- `getFeatureBounds(feature)` : calcule la bounding box (rectangle minimal
  qui englobe la géométrie). Détail plus bas.

- `map.fitBounds(bounds, options)` : positionne la caméra pour que toute
  la zone définie par `bounds` rentre dans le viewport.
  - `padding: 60` : marge en pixels autour de la zone (pour ne pas être
    pile contre les bords).
  - `maxZoom: 9` : limite supérieure pour éviter de zoomer trop fort sur
    une toute petite région (genre Monaco passerait à zoom 16 sinon).

### Bloc 8 — `getFeatureBounds()` : calcul de bounding box

```js
function getFeatureBounds(feature) {
  if (!feature || !feature.geometry) return null;
  var bounds = new mapboxgl.LngLatBounds();
  (function extend(c) {
    if (typeof c[0] === 'number') bounds.extend(c);
    else c.forEach(extend);
  })(feature.geometry.coordinates);
  return bounds;
}
```

- `mapboxgl.LngLatBounds()` : objet Mapbox qui représente une bounding box
  géographique.
- `.extend(coords)` : étend la box pour inclure le point passé.
- La structure des coordonnées GeoJSON est **imbriquée** : un Point est
  `[lng, lat]`, un Polygon est `[[[lng, lat], [lng, lat], ...]]` (tableau
  de tableaux de couples), un MultiPolygon ajoute un niveau de plus.
- Le **IIFE récursif** descend dans cette structure jusqu'à trouver des
  nombres :
  - `typeof c[0] === 'number'` → on est sur un point `[lng, lat]`, on étend.
  - Sinon → on a un tableau de sous-géométries, on récurse.

C'est une astuce concise mais qui peut être déroutante. La version "pour
débutant" serait :

```js
function extend(coords) {
  if (typeof coords[0] === 'number') {
    bounds.extend(coords);
    return;
  }
  for (var i = 0; i < coords.length; i++) {
    extend(coords[i]);
  }
}
extend(feature.geometry.coordinates);
```

## Évolutions possibles

- **Servir le GeoJSON gzippé** : Apache fait déjà du gzip sur le `.json`
  normalement, mais on peut forcer via `.htaccess` pour passer de ~37 Mo
  à ~6 Mo en transfert.
- **Pré-filtrer le GeoJSON par pays** : si le pays sélectionné est connu,
  on pourrait charger juste un sous-GeoJSON pour ce pays (script de découpe
  en amont).
- **Cache localStorage** : stocker le GeoJSON parsé dans `localStorage`
  pour éviter le re-fetch au prochain chargement (mais ~5 Mo de quota
  côté navigateur, à surveiller).
- **Différencier le rendu par worldview** : actuellement Natural Earth est
  neutre, mais on pourrait charger un fichier différent par locale.
- **Données métier sur les régions** : agréger les avis par région et
  colorer chaque région selon la note moyenne (choropleth map).
