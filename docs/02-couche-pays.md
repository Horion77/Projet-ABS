# 02 — Couche pays : hover, sélection, popup

## Contexte

Sur la carte, on voulait que l'utilisateur puisse :
1. **Survoler** un pays et voir un **mini-aperçu** (note moyenne + nombre de lieux).
2. **Cliquer** sur un pays pour ouvrir un **popup** avec un bouton "Voir les avis →"
   qui mène vers la fiche pays.
3. Avoir un **highlight visuel** du pays sous le curseur, et du pays sélectionné.

Mapbox fournit un tileset officiel `mapbox.country-boundaries-v1` qui contient
les frontières de **tous les pays du monde** déjà optimisées pour le rendu carte.
Pas besoin de charger un GeoJSON nous-mêmes.

## Solution retenue

Utilisation de la **feature-state API** de Mapbox pour gérer les états `hover`
et `selected` directement au niveau du moteur de rendu, sans manipuler le DOM.
C'est beaucoup plus rapide qu'une approche classique CSS+DOM, surtout au survol
qui se déclenche à 60 fps.

Le mini-aperçu au survol utilise une `<div>` HTML positionnée par JS
(la **hover-card**), pas un popup Mapbox : c'est plus léger et ça suit
le curseur sans flicker.

Le clic, lui, ouvre un vrai **popup Mapbox** (`mapboxgl.Popup`) au centre
du pays.

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Tracer les frontières en GeoJSON nous-mêmes** | Le fichier des frontières mondiales fait ~10 Mo, trop lourd à charger. Mapbox a déjà un tileset optimisé en vector tiles. |
| **Highlight via une classe CSS sur le DOM** | Impossible : les pays ne sont pas des éléments DOM mais des polygones rendus sur le canvas WebGL. Il faut utiliser l'API feature-state. |
| **Popup Mapbox aussi pour le hover** | Crée un flicker au passage rapide entre deux pays (le popup se ferme puis rouvre). Une `<div>` qu'on déplace est plus fluide. |
| **Surcoucher tous les pays avec un calque rouge transparent** | Trop visuel, fait perdre la lisibilité du fond de carte. |

## Code expliqué ligne par ligne

### Bloc 1 — Déclaration de la source

```js
function addCountryLayer() {
  if (!map.getSource('pays-source')) {
    map.addSource('pays-source', {
      type:      'vector',
      url:       'mapbox://mapbox.country-boundaries-v1',
      promoteId: { country_boundaries: 'iso_3166_1_alpha_3' },
    });
  }
```

- `map.addSource(...)` enregistre une **source de données** dans Mapbox.
  Une source n'affiche rien à elle seule, c'est juste un "réservoir" auquel
  on attache ensuite des **couches** (layers) pour afficher.
- `type: 'vector'` : source en tuiles vectorielles (au lieu de raster).
- `url: 'mapbox://mapbox.country-boundaries-v1'` : URL spéciale Mapbox qui
  pointe vers le tileset gratuit `country-boundaries-v1`.
- `promoteId: { country_boundaries: 'iso_3166_1_alpha_3' }` : **point clé**.
  Par défaut chaque feature Mapbox a un `id` numérique généré automatiquement,
  qui change à chaque rendu et n'est pas stable. Avec `promoteId` on dit :
  "utilise plutôt la propriété `iso_3166_1_alpha_3` (le code ISO du pays
  sur 3 lettres, type FRA, USA, JPN) comme id de la feature".
  Ça nous permet de retrouver un pays par son code ISO depuis notre base de
  données.

### Bloc 2 — Couche fill (remplissage du pays)

```js
var sous = map.getLayer('country-label') ? 'country-label' : undefined;

if (!map.getLayer('pays-fill')) {
  map.addLayer({
    id:             'pays-fill',
    type:           'fill',
    source:         'pays-source',
    'source-layer': 'country_boundaries',
    filter:         ['match', ['get', 'worldview'], ['all', 'US'], true, false],
    paint: {
      'fill-color':   '#6490ff',
      'fill-opacity': ['case',
        ['boolean', ['feature-state', 'selected'], false], 0.30,
        ['boolean', ['feature-state', 'hover'],    false], 0.12,
        0,
      ],
    },
  }, sous);
}
```

- `var sous = map.getLayer('country-label') ? 'country-label' : undefined;`
  → on récupère l'id de la couche "country-label" (les labels textuels des
  pays comme "France", "Japon"). Le 2e argument de `addLayer(layer, beforeId)`
  permet d'insérer une couche **en dessous** d'une autre. On la met en dessous
  des labels pour ne pas les masquer.

- `id: 'pays-fill'` : identifiant unique de la couche.

- `type: 'fill'` : type de couche qui remplit un polygone avec une couleur.

- `'source-layer': 'country_boundaries'` : nom de la couche **à l'intérieur**
  du tileset Mapbox. Un tileset peut contenir plusieurs source-layers
  (frontières pays, frontières régions, océans, etc.). Ici on prend juste
  les frontières pays.

- `filter: ['match', ['get', 'worldview'], ['all', 'US'], true, false]` :
  syntaxe d'expression Mapbox. Décodage :
  - `['get', 'worldview']` → lit la propriété `worldview` de la feature.
  - `['all', 'US']` → tableau des valeurs à matcher.
  - L'expression complète dit : "si `worldview` vaut 'all' ou 'US', garder
    la feature, sinon la cacher".
  - Pourquoi ? Le tileset Mapbox contient plusieurs versions des frontières
    selon les pays qui les reconnaissent (Inde / Chine ne sont pas d'accord
    sur l'Arunachal Pradesh par exemple). On choisit la worldview US par
    défaut pour avoir un rendu cohérent.

- `'fill-color': '#6490ff'` : bleu clair, choisi pour bien contraster avec
  le fond sombre tout en restant discret.

- `'fill-opacity': ['case', ...]` : opacité conditionnelle. La syntaxe `case`
  fonctionne comme un `if / else if / else` en code :
  - SI `feature-state.selected` = true → opacité 0.30
  - SINON SI `feature-state.hover` = true → opacité 0.12
  - SINON → opacité 0 (le pays est invisible)

  C'est ça qui permet d'avoir un highlight très réactif sans toucher au DOM :
  on change un feature-state, et Mapbox redessine instantanément avec la
  nouvelle opacité.

### Bloc 3 — Couche outline (contour du pays sélectionné)

```js
if (!map.getLayer('pays-outline')) {
  map.addLayer({
    id:             'pays-outline',
    type:           'line',
    source:         'pays-source',
    'source-layer': 'country_boundaries',
    filter:         ['match', ['get', 'worldview'], ['all', 'US'], true, false],
    paint: {
      'line-color':   '#7eb3ff',
      'line-width':   ['case', ['boolean', ['feature-state', 'selected'], false], 2, 0],
      'line-opacity': 0.9,
    },
  });
}
```

Couche `line` qui dessine juste le **contour** du pays. La largeur passe de
0 (invisible) à 2 pixels uniquement sur le pays sélectionné. C'est ce qui
donne l'effet "halo bleu autour du pays cliqué".

### Bloc 4 — Hover : highlight + mini-card

```js
var hoverCard = document.getElementById('pays-hover-card');

map.on('mousemove', 'pays-fill', function (e) {
  if (!e.features.length) return;
  var id = e.features[0].id;
```

- `map.on('mousemove', 'pays-fill', fn)` : événement spécial Mapbox qui se
  déclenche quand la souris bouge **par-dessus une feature de la couche**
  `pays-fill`. Ça simplifie beaucoup la détection par rapport à un
  `mousemove` global suivi d'un test géométrique.
- `e.features` : tableau des features sous le curseur. On prend la première.
- `e.features[0].id` est l'id de la feature, qui vaut le code ISO grâce au
  `promoteId` configuré plus haut.

### Bloc 5 — Changement de pays sous le curseur

```js
if (hoveredCountryId !== id) {
  if (hoveredCountryId) {
    map.setFeatureState(
      { source: 'pays-source', sourceLayer: 'country_boundaries', id: hoveredCountryId },
      { hover: false }
    );
  }
  hoveredCountryId = id;
  map.setFeatureState(
    { source: 'pays-source', sourceLayer: 'country_boundaries', id: id },
    { hover: true }
  );
  map.getCanvas().style.cursor = 'pointer';
```

C'est ici qu'on applique l'**optimisation change detection**
(voir `07-optim-mousemove.md` pour le détail).

L'idée : le `mousemove` peut se déclencher 60 fois par seconde. Reconstruire
la hover-card à chaque pixel serait du gaspillage. Donc on stocke le pays
actuellement survolé dans `hoveredCountryId`, et on ne fait le travail
"lourd" que quand l'id change.

- `map.setFeatureState(...)` : met à jour l'état d'une feature.
  Le premier argument identifie la feature (source + sourceLayer + id),
  le deuxième est l'objet d'état à appliquer/fusionner.
- `{ hover: false }` sur l'ancien pays survolé → repasse à l'opacité 0.
- `{ hover: true }` sur le nouveau pays survolé → opacité 0.12.
- `map.getCanvas().style.cursor = 'pointer'` : change le curseur souris en
  petite main (signal visuel "c'est cliquable").

### Bloc 6 — Mise à jour du contenu de la hover-card

```js
if (hoverCard) {
  var paysDB = trouverPaysDB(String(id));
  if (paysDB) {
    var nb       = parseInt(paysDB.places_count || 0, 10);
    var note     = paysDB.avg_rating;
    var statsTxt = nb + ' lieu' + (nb > 1 ? 'x' : '');
    if (note) statsTxt += ' · ★ ' + note + '/5';
    hoverCard.innerHTML =
      '<h5>' + escapeHtml(paysDB.nom) + '</h5>' +
      '<div class="phc-stats">' + statsTxt + '</div>';
    hoverCard.hidden = false;
  } else {
    hoverCard.hidden = true;
  }
}
```

- `trouverPaysDB(iso3)` : cherche dans le tableau `countries` (envoyé par PHP)
  le pays correspondant au code ISO survolé. Retourne `null` si le pays n'est
  pas dans notre base (par exemple si on survole le Groenland et qu'on n'a
  aucun avis sur ce pays).
- Si trouvé : on construit le texte des stats (`5 lieux · ★ 4.2/5`) et on
  l'injecte dans la `<div>` hover-card.
- `escapeHtml(...)` : protection contre les injections XSS (cf. plus bas).
- `hoverCard.hidden = false` : utilise l'attribut HTML `hidden` (équivalent
  à `display: none` en CSS, mais plus sémantique).

### Bloc 7 — Position de la hover-card (bloc léger)

```js
if (hoverCard && !hoverCard.hidden) {
  hoverCard.style.left = (e.point.x + 16) + 'px';
  hoverCard.style.top  = (e.point.y + 16) + 'px';
}
```

Ce bout-là s'exécute **à chaque pixel** (en dehors du `if` change detection).
On déplace juste la `<div>` pour qu'elle suive le curseur, avec un offset de
16 pixels pour pas être pile sous la souris.

`e.point.x` / `e.point.y` sont les coordonnées **écran** du curseur dans le
viewport de la carte.

C'est très léger parce qu'on modifie juste 2 propriétés CSS (le navigateur
fait juste un repaint GPU). Pas de reflow, pas de recalcul de layout.

### Bloc 8 — Sortie de la carte

```js
map.on('mouseleave', 'pays-fill', function () {
  if (hoveredCountryId) {
    map.setFeatureState(
      { source: 'pays-source', sourceLayer: 'country_boundaries', id: hoveredCountryId },
      { hover: false }
    );
  }
  hoveredCountryId = null;
  map.getCanvas().style.cursor = '';
  if (hoverCard) hoverCard.hidden = true;
});
```

- `mouseleave` sur `pays-fill` : tiré quand le curseur quitte la couche.
- On remet l'état hover à false sur le pays qu'on avait sous le curseur,
  et on cache la hover-card.

### Bloc 9 — Clic : popup + zoom + sélection

```js
map.on('click', 'pays-fill', function (e) {
  if (e.defaultPrevented) return;
  if (!e.features.length) return;
  var f    = e.features[0];
  var iso3 = String(f.id);
```

- `e.defaultPrevented` : vrai si un autre handler a appelé `e.preventDefault()`
  avant. C'est utilisé par la couche régions (voir `05-couche-regions.md`)
  qui consomme le clic pour ne pas qu'on remonte au pays.

### Bloc 10 — Désélection si reclic sur le même pays

```js
if (selectedCountryId) {
  map.setFeatureState(
    { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
    { selected: false }
  );
}

if (selectedCountryId === iso3) {
  selectedCountryId = null;
  deselectionnerPays();
  return;
}
```

- On enlève d'abord la sélection visuelle de l'ancien pays.
- Si on reclique sur le **même** pays → désélection complète (retour à
  la vue monde).

### Bloc 11 — Sélection + popup + zoom

```js
selectedCountryId = iso3;
map.setFeatureState(
  { source: 'pays-source', sourceLayer: 'country_boundaries', id: iso3 },
  { selected: true }
);

var paysDB = trouverPaysDB(iso3);
if (paysDB) {
  activeCountryId = String(paysDB.id_pays);
  syncCountryFilter(paysDB.id_pays);
  renderMarkers();

  new mapboxgl.Popup({ offset: 14, maxWidth: '280px', className: 'abs-popup' })
    .setLngLat([parseFloat(paysDB.lng), parseFloat(paysDB.lat)])
    .setHTML(buildPaysPopup(paysDB))
    .addTo(map);

  map.flyTo({
    center: [parseFloat(paysDB.lng), parseFloat(paysDB.lat)],
    zoom: 5, pitch: 50, duration: 1800, essential: true,
  });
}
```

- On marque visuellement le pays comme sélectionné (contour bleu).
- `syncCountryFilter(...)` : aligne le `<select>` du haut sur le pays cliqué.
- `renderMarkers()` : recalcule la liste des marqueurs visibles (filtre les
  lieux d'autres pays).

- `new mapboxgl.Popup({...})` : crée un popup Mapbox.
  - `offset: 14` : décale le popup de 14 pixels par rapport au point GPS
    pour ne pas couvrir le marqueur sous-jacent.
  - `maxWidth: '280px'` : largeur max du popup.
  - `className: 'abs-popup'` : classe CSS personnalisée pour styler le popup
    (cf. `map.css`).

- `.setLngLat([lng, lat])` : ancre le popup à une position GPS. Le popup
  reste collé à ce point même si on bouge la carte.
- `.setHTML(...)` : contenu HTML du popup (généré par `buildPaysPopup()`).
- `.addTo(map)` : ajoute le popup à la carte.

- `map.flyTo({...})` : animation de caméra qui se déplace doucement vers
  un nouveau point.
  - `center` : point GPS d'arrivée.
  - `zoom: 5` : niveau de zoom à l'arrivée.
  - `pitch: 50` : inclinaison cible (légèrement plus inclinée qu'au départ).
  - `duration: 1800` : durée en millisecondes.
  - `essential: true` : flag obligatoire pour que l'animation joue même si
    l'utilisateur a activé "prefers-reduced-motion" dans son OS. À utiliser
    quand l'animation transporte une info essentielle.

### Bloc 12 — Cas du pays hors BDD

```js
} else {
  map.flyTo({
    center: [e.lngLat.lng, e.lngLat.lat],
    zoom: 5, pitch: 45, duration: 1600, essential: true,
  });
}
```

Si le pays cliqué n'est pas dans notre base de données (par ex. l'utilisateur
clique sur la Mongolie qu'on n'a pas seedée), on fait juste un zoom centré
sur le point cliqué, sans popup.

`e.lngLat.lng` / `e.lngLat.lat` : coordonnées GPS du clic (et pas du centre
du pays, parce qu'on n'a pas cette info).

### Bloc 13 — Construction du HTML du popup pays

```js
function buildPaysPopup(paysDB) {
  var nb     = parseInt(paysDB.places_count || 0, 10);
  var rating = paysDB.avg_rating ? parseFloat(paysDB.avg_rating) : null;

  var ratingHTML = rating
    ? '<div class="popup-rating">' + renderStars(rating) +
      ' <span>' + rating.toFixed(1) + '/5</span></div>'
    : '<div class="popup-rating no-rating">Aucun avis pour l\'instant</div>';

  var lieuxTxt = nb + ' lieu' + (nb > 1 ? 'x' : '') + ' référencé' + (nb > 1 ? 's' : '');
  var link     = pathPays + '?id=' + encodeURIComponent(String(paysDB.id_pays));

  return '<div class="popup-inner">' +
    '<div class="popup-body">' +
      '<div class="popup-type-badge">Pays</div>' +
      '<h3 class="popup-title">' + escapeHtml(paysDB.nom) + '</h3>' +
      '<p class="popup-country">' + escapeHtml(lieuxTxt) + '</p>' +
      ratingHTML +
      '<a class="popup-link" href="' + escapeHtml(link) + '">Voir les avis →</a>' +
    '</div>' +
  '</div>';
}
```

- `parseInt(..., 10)` : conversion en entier en base 10 (le 10 évite que
  JavaScript tente de deviner la base, par ex. octale pour `"010"`).
- `parseFloat(...)` : conversion en nombre à virgule.
- `rating.toFixed(1)` : arrondi à 1 décimale (`4.247` → `"4.2"`).
- `nb > 1 ? 'x' : ''` : accord pluriel (`1 lieu`, `2 lieux`).
- `encodeURIComponent(...)` : encode un paramètre d'URL (échappe les
  caractères spéciaux comme espaces, accents). Indispensable pour ne pas
  casser l'URL.
- `escapeHtml(...)` : échappe les caractères dangereux (`<`, `>`, `&`, `"`, `'`)
  pour éviter les injections XSS si jamais le nom d'un pays contenait du HTML.

## Évolutions possibles

- **Afficher la liste des villes** dans le popup pays au lieu de juste la
  note globale.
- **Drapeau** du pays dans le popup (utiliser `code_iso` pour générer un
  emoji drapeau ou une image).
- **Multiples worldviews** : permettre à l'utilisateur de choisir sa worldview
  (US, CN, IN, JP) via un setting.
- **Highlight au survol même hors BDD** : actuellement on highlight tous les
  pays, mais on pourrait griser ceux qui ne sont pas dans notre BDD pour
  signaler à l'utilisateur qu'il n'y aura pas de fiche.
