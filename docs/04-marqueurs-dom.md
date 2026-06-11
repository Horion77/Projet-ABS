# 04 — Marqueurs DOM et popups

## Contexte

Mapbox propose deux façons d'afficher un point sur la carte :

1. **Couches Mapbox** (`circle`, `symbol`) → rendues dans le canvas WebGL,
   très performantes mais limitées en personnalisation (formes, animations
   CSS, contenu HTML libre impossible).
2. **Markers DOM** (`mapboxgl.Marker`) → chaque marqueur est une vraie
   `<div>` HTML qu'on peut styler en CSS, animer, et qui peut contenir du
   texte et des icônes. Plus lourd, mais beaucoup plus flexible.

Pour les **pays** et les **villes** on voulait des pins avec :
- un emoji icône (🇫🇷, 🏛, etc.)
- un label texte ("France", "Paris")
- une note moyenne (★ 4.2)

Impossible à faire propre avec une couche `symbol`, surtout pour les emojis
en font system. Donc on est partis sur des markers DOM.

Pour les monuments, on a un rendu mixte :
- **DOM marker** quand on est zoomé (vue ≥ 9) → pour avoir un beau pin avec
  emoji
- **Couche `unclustered-monuments`** en vue dézoomée → géré par le clustering
  (voir `03-clustering-monuments.md`)

## Solution retenue

- Une fonction `createPinEl(place)` qui génère la `<div>` HTML du marqueur
  avec son contenu (icône + nom + note).
- Une fonction `renderMarkers()` qui boucle sur tous les lieux à afficher,
  crée les markers, leur attache un popup, et les ajoute à la carte.
- À chaque changement de filtre (sélection d'un pays par exemple), on
  **supprime tous les markers existants** et on les recrée. C'est moins
  optimisé qu'une mise à jour incrémentale, mais beaucoup plus simple à
  maintenir et largement suffisant pour quelques dizaines de markers.

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Tous les markers en couche Mapbox** | Impossible d'avoir l'emoji + label + note dans un beau bouton arrondi sans HTML. |
| **Tous les markers en DOM** | Lag confirmé au-delà de ~200 markers visibles (chaque marker = un DOM element à repositionner à chaque mouvement). |
| **Mise à jour incrémentale** (ajouter/retirer juste les markers qui changent) | Plus performant en théorie mais complexité multipliée par 3, et on n'en a pas besoin à l'échelle actuelle. À considérer si on monte à 1000+ markers. |
| **Lazy rendering** (ne rendre que les markers visibles à l'écran) | Mapbox ne le fait pas tout seul pour les markers DOM. Faisable mais idem, sur-engineering pour notre volume. |

## Code expliqué ligne par ligne

### Bloc 1 — `createPinEl(place)` : création du HTML du pin

```js
function createPinEl(place) {
  var el   = document.createElement('div');
  var type = place.type || 'monument';
  el.className = 'abs-marker pin-' + type;
```

- `document.createElement('div')` : crée une `<div>` HTML détachée (pas
  encore dans le DOM).
- `el.className = 'abs-marker pin-' + type` : deux classes CSS :
  - `abs-marker` : styles communs à tous les markers (taille, ombre, etc.)
  - `pin-pays` / `pin-ville` / `pin-monument` : styles spécifiques au type
    (couleur, taille de la pastille).

### Bloc 2 — Génération du contenu HTML

```js
var note = place.avg_rating
  ? '<span class="pin-note">' + parseFloat(place.avg_rating).toFixed(1) + '★</span>'
  : '';

var icon = escapeHtml(place.icon || '📍');

if (type === 'pays' || type === 'ville') {
  el.innerHTML = '<div class="pin-inner">' +
    '<span class="pin-icon">' + icon + '</span>' +
    '<span class="pin-label">' + escapeHtml(place.name) + '</span>' +
    note + '</div>';
} else {
  el.innerHTML = '<div class="pin-inner">' +
    '<span class="pin-icon">' + icon + '</span>' +
    note + '</div>';
}
return el;
}
```

- `place.avg_rating ? ... : ''` : opérateur ternaire. Si `avg_rating` est
  défini et non-nul, on construit le HTML de la note, sinon chaîne vide.
- `parseFloat(...).toFixed(1)` : conversion en float puis arrondi à 1 décimale
  pour formater la note (`4` → `"4.0"`).
- `escapeHtml(...)` : protège contre les caractères qui pourraient casser le
  HTML ou injecter du script (sécurité de base).
- `place.icon || '📍'` : fallback emoji par défaut si pas d'icône en base.
- Différence pays/ville vs monument : les pays et villes ont un **label
  textuel** ("France", "Paris") parce qu'on est dézoomé et qu'on a la place.
  Les monuments n'ont que l'icône, parce qu'à zoom 9+ on aurait trop de
  labels qui se superposent.

### Bloc 3 — `renderMarkers()` : suppression + recréation

```js
function renderMarkers() {
  markers.forEach(function (m) { m.marker.remove(); });
  markers = [];
```

- On boucle sur tous les markers actuellement affichés et on les supprime
  avec `.remove()` (méthode Mapbox qui détache le DOM et nettoie les
  listeners).
- On vide ensuite le tableau `markers`.

### Bloc 4 — Filtrage par pays sélectionné

```js
var filtered = activeCountryId
  ? places.filter(function (p) { return String(p.id_pays) === String(activeCountryId); })
  : places;

updateCount(filtered.length);
```

- Si un pays est sélectionné (`activeCountryId` non null), on garde
  uniquement les lieux de ce pays.
- Sinon, on garde tous les lieux.
- `String(...)` des deux côtés du `===` parce que `id_pays` peut être un
  nombre côté JSON et une string côté JS si on l'a stocké comme tel.
  Comparaison stricte avec `===` qui ne convertit pas → on sécurise en
  forçant tout en string.
- `updateCount(...)` met à jour le badge "X lieux" dans l'UI.

### Bloc 5 — Boucle de création

```js
filtered.forEach(function (place) {
  var lat = parseFloat(place.lat);
  var lng = parseFloat(place.lng);
  if (isNaN(lat) || isNaN(lng)) return;

  var type   = place.type || 'monument';
  var el     = createPinEl(place);
  var marker = new mapboxgl.Marker({ element: el, anchor: 'center' })
    .setLngLat([lng, lat])
    .addTo(map);
```

- `parseFloat(...)` : conversion string → number. `parseFloat("48.85")` = `48.85`.
- `if (isNaN(...))` : si la conversion a échoué (par ex. coordonnée
  manquante en base), on ignore ce lieu et on passe au suivant.
- `new mapboxgl.Marker({ element: el, anchor: 'center' })` :
  - `element: el` : on passe notre `<div>` custom. Sans cette option, Mapbox
    génère un marker par défaut (épingle bleue).
  - `anchor: 'center'` : le point GPS du marker correspond au **centre** de
    la `<div>`. Autres valeurs possibles : 'top', 'bottom', 'left', etc.
- `.setLngLat([lng, lat])` : ancre au point GPS.
- `.addTo(map)` : ajoute à la carte.

### Bloc 6 — Popup attachée au marker

```js
var popup = new mapboxgl.Popup({ offset: 28, maxWidth: '280px', className: 'abs-popup' })
  .setHTML(buildPopup(place));
marker.setPopup(popup);

markers.push({ marker: marker, type: type });
});

updateVisibility();
}
```

- Création d'un popup Mapbox (cf. `02-couche-pays.md` pour les options).
- **`marker.setPopup(popup)`** : associe le popup au marker. Mapbox gère
  automatiquement l'ouverture/fermeture au clic. Pas besoin d'écouter le
  clic nous-mêmes.
- On stocke `{ marker, type }` dans le tableau global pour pouvoir le
  retrouver plus tard (suppression, mise à jour de visibilité).
- `updateVisibility()` : applique la visibilité en fonction du zoom courant
  (un marker monument créé alors qu'on est à zoom 3 doit être caché).

### Bloc 7 — `buildPopup(place)` : HTML du popup standard

```js
function buildPopup(place) {
  var rating = place.avg_rating ? parseFloat(place.avg_rating) : null;
  var ratingHTML = rating
    ? '<div class="popup-rating">' + renderStars(rating) +
      ' <span>' + rating.toFixed(1) + '/5</span>' +
      ' <small>(' + (place.review_count || 0) + ' avis)</small></div>'
    : '<div class="popup-rating no-rating">Aucun avis pour l\'instant</div>';

  var imgHTML = place.image_url
    ? '<img class="popup-image" src="' + escapeHtml(place.image_url) + '" alt="' + escapeHtml(place.name) + '">'
    : '';

  var typeLabel = { pays: 'Pays', ville: 'Ville', monument: 'Monument' };
  var link = pathLieu + '?id=' + encodeURIComponent(String(place.id_lieu));

  return '<div class="popup-inner">' +
    imgHTML +
    '<div class="popup-body">' +
      '<div class="popup-type-badge">' + (typeLabel[place.type] || '') + '</div>' +
      '<h3 class="popup-title">' + escapeHtml(place.name) + '</h3>' +
      '<p class="popup-country">' + escapeHtml(place.country_name) + '</p>' +
      ratingHTML +
      '<a class="popup-link" href="' + escapeHtml(link) + '">Voir les avis →</a>' +
    '</div>' +
  '</div>';
}
```

Construction du contenu HTML du popup :

1. **Note** : si `avg_rating` est défini, on affiche les étoiles + la note
   chiffrée + le nombre d'avis. Sinon, message "Aucun avis pour l'instant".
2. **Image** : si une image existe (`image_url`), on l'ajoute en haut du popup.
3. **Badge type** : "Pays" / "Ville" / "Monument" selon le type.
4. **Titre** + **pays** + **note**.
5. **Lien "Voir les avis →"** vers la fiche détaillée.

Tous les textes injectés sont passés par `escapeHtml()` pour éviter les
problèmes de sécurité (XSS) si la BDD contient des caractères spéciaux.

### Bloc 8 — `updateVisibility()` : montrer/cacher selon le zoom

```js
function updateVisibility() {
  var z = map.getZoom();
  markers.forEach(function (m) {
    var range   = ZOOM_RANGE[m.type] || { min: 0, max: 22 };
    var visible = z >= range.min && z <= range.max;
    var el      = m.marker.getElement();
    el.style.opacity       = visible ? '1' : '0';
    el.style.pointerEvents = visible ? 'auto' : 'none';
  });
}
```

- `map.getZoom()` : niveau de zoom actuel (peut être un nombre à virgule).
- Pour chaque marker, on regarde dans `ZOOM_RANGE` les bornes min/max
  d'affichage de son type.
- On modifie deux propriétés CSS :
  - `opacity: 0/1` → invisible ou visible (avec transition CSS douce).
  - `pointer-events: none/auto` → empêche le clic sur un marker invisible
    (sinon les markers cachés intercepteraient quand même les clics).

Cette fonction est appelée :
- au `style.load` (rendu initial)
- au `moveend` (fin d'un déplacement)
- au `zoom` (en continu pendant un zoom, pour une transition fluide)

## Évolutions possibles

- **Mise à jour incrémentale** : au lieu de tout supprimer/recréer, comparer
  l'ancien et le nouveau filtre et ne mettre à jour que les différences.
- **LOD (Level of Detail)** : afficher des markers simplifiés en dézoomé,
  plus détaillés en zoomé. Faisable en jouant sur des classes CSS via
  `updateVisibility`.
- **Animation d'apparition** : effet fade-in + scale quand un marker
  apparaît (CSS animation sur la classe `.abs-marker`).
- **Pin dépendant de la catégorie** : changer l'icône selon `categorie_lieu`
  plutôt que de mettre tous les monuments avec le même emoji.
- **Lazy rendering** : si on monte à 1000+ markers, ne créer le DOM que pour
  ceux dans le viewport (avec un `IntersectionObserver` sur le canvas).
