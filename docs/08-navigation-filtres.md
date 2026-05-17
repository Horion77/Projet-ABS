# 08 — Panneau de navigation et filtres

## Contexte

La carte sphérique 3D, c'est beau mais ça peut être déroutant pour
l'utilisateur qui ne sait pas où chercher. On voulait deux aides à la
navigation :

1. Un **panneau gauche** avec :
   - Un bouton "Monde entier" pour reset la vue.
   - Une liste des continents (Europe, Asie, Amériques, Afrique, Océanie)
     pour voler vers une grande zone.
   - Une liste des pays présents en BDD pour voler vers un pays précis.

2. Un **select de filtre** en haut qui se synchronise avec le panneau gauche.
   Sélectionner un pays dans le select doit avoir le même effet que
   cliquer sur le pays dans le panneau gauche, et inversement.

## Solution retenue

- Liste des continents **codée en dur** dans le JS (5 entrées, pas besoin
  de table SQL pour ça).
- Liste des pays **dynamique** : générée depuis le tableau `countries`
  passé par PHP, qui vient de `LieuModel::paysPourFiltreCarte()`.
- Synchronisation panneau ↔ select via une fonction utilitaire
  `syncCountryFilter(id)`.
- À chaque clic sur un pays/continent : `flyTo` vers les coordonnées du
  centre + filtre des markers + mise à jour de la classe `.active` sur
  le bouton.

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Recherche par nom (autocomplétion)** | Plus complexe à implémenter et moins immédiat qu'une liste cliquable. À considérer si la liste de pays devient trop longue. |
| **Génération de la liste continents depuis la BDD** | Surdimensionné pour 5 entrées qui ne changeront jamais. |
| **Sync via observer pattern** | Trop sophistiqué pour 2 contrôles couplés. Une fonction utilitaire suffit. |
| **Stocker l'état actif en `data-attribute`** | Moins flexible que des variables JS globales pour la logique. |

## Code expliqué ligne par ligne

### Bloc 1 — Construction du panneau au démarrage

```js
(function buildNavPanel() {
  var contList    = document.getElementById('nav-continents');
  var countryList = document.getElementById('nav-countries');
  if (!contList || !countryList) return;
```

- **IIFE nommée** : `(function buildNavPanel() { ... }())`. Le nom est
  optionnel mais aide au debug (stack trace plus lisible).
- On récupère les conteneurs HTML déclarés dans `carte/index.php`.
  Si l'un manque, on annule (sécurité défensive).

### Bloc 2 — Bouton "Monde entier"

```js
var worldBtn = document.getElementById('nav-world');
if (worldBtn) {
  worldBtn.addEventListener('click', function () {
    setActiveNav(null);
    deselectionnerPays();
  });
}
```

- `setActiveNav(null)` : enlève la classe `.active` de tous les boutons
  nav (aucun n'est sélectionné).
- `deselectionnerPays()` : reset complet — enlève la sélection visuelle,
  remet le filtre à null, recrée les markers, et vole vers la vue monde.

### Bloc 3 — Génération des boutons continents

```js
CONTINENTS.forEach(function (cont) {
  var btn = document.createElement('button');
  btn.className   = 'nav-btn nav-continent-btn';
  btn.textContent = cont.name;
  btn.addEventListener('click', function () {
    setActiveNav(btn);
    activeCountryId = null;
    syncCountryFilter(null);
    renderMarkers();
    map.flyTo({ center: [cont.lng, cont.lat], zoom: cont.zoom, pitch: 45, duration: 1800, essential: true });
  });
  contList.appendChild(btn);
});
```

Pour chaque continent dans `CONTINENTS` :
- Création d'un `<button>` avec les bonnes classes CSS.
- `textContent` (pas `innerHTML`) : sécurité, pas d'interprétation HTML.
- Au clic :
  - On marque le bouton comme actif.
  - On reset le filtre pays (`activeCountryId = null` et
    `syncCountryFilter(null)`).
  - On recrée les markers (tous les lieux, plus de filtre).
  - On vole vers le centre du continent.

### Bloc 4 — Génération des boutons pays

```js
countries.forEach(function (c) {
  var btn = document.createElement('button');
  btn.className   = 'nav-btn nav-country-btn';
  btn.dataset.id  = c.id_pays;
  btn.textContent = c.nom;
  btn.addEventListener('click', function () {
    setActiveNav(btn);
    activeCountryId = String(c.id_pays);
    syncCountryFilter(c.id_pays);
    renderMarkers();
```

- `btn.dataset.id = c.id_pays` : équivalent de `data-id="X"` en HTML.
  Très pratique pour retrouver un bouton par son id sans utiliser de
  variables globales.
- Au clic, on définit `activeCountryId` (filtre actif) et on synchronise
  le select.

### Bloc 5 — Mise à jour de la sélection visuelle sur la carte

```js
if (c.code_iso && map.getLayer('pays-fill')) {
  if (selectedCountryId) {
    map.setFeatureState(
      { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
      { selected: false }
    );
  }
  selectedCountryId = c.code_iso.toUpperCase();
  map.setFeatureState(
    { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
    { selected: true }
  );
}
var lat = parseFloat(c.lat || 0);
var lng = parseFloat(c.lng || 0);
map.flyTo({ center: [lng, lat], zoom: 5, pitch: 50, duration: 1800, essential: true });
});
countryList.appendChild(btn);
});
}());
```

- On désélectionne d'abord l'ancien pays visuellement, puis on sélectionne
  le nouveau avec `setFeatureState`.
- `c.code_iso.toUpperCase()` parce que le tileset Mapbox utilise des codes
  ISO en majuscules (`FRA`, `JPN`...).
- `flyTo` vers le centre du pays.

### Bloc 6 — `setActiveNav()` : gestion de la classe active

```js
function setActiveNav(btn) {
  document.querySelectorAll('.nav-btn').forEach(function (b) { b.classList.remove('active'); });
  if (btn) btn.classList.add('active');
}
```

- `querySelectorAll('.nav-btn')` : retourne tous les boutons (continents +
  pays) en une seule fois.
- `.classList.remove('active')` partout, puis on ajoute sur le bon.
- Si `btn` est `null`, aucun n'est marqué actif (cas du clic sur "Monde").

### Bloc 7 — `syncCountryFilter()` : synchroniser le select

```js
function syncCountryFilter(id) {
  var sel = document.getElementById('country-filter');
  if (sel) sel.value = id ? String(id) : '';
}
```

- On met directement la propriété `.value` du `<select>` à la bonne valeur.
- Si `id` est null, on met chaîne vide (= option "Tous les pays").
- Modifier `.value` ne déclenche **pas** d'événement `change`, ce qui est
  ce qu'on veut (sinon ça bouclerait : le clic sur le panneau modifierait
  le select qui déclencherait à son tour un clic).

### Bloc 8 — Listener du select pays

```js
var filterEl = document.getElementById('country-filter');
if (filterEl) {
  filterEl.addEventListener('change', function () {
    var opt = filterEl.options[filterEl.selectedIndex];
    activeCountryId = filterEl.value || null;
    renderMarkers();
    document.querySelectorAll('.nav-country-btn').forEach(function (b) {
      b.classList.toggle('active', b.dataset.id === filterEl.value);
    });
```

- `filterEl.options[filterEl.selectedIndex]` : récupère l'`<option>`
  actuellement sélectionnée pour accéder à ses `data-*` (lat, lng).
- `filterEl.value || null` : convertit la chaîne vide en `null`.
- `classList.toggle('active', condition)` : ajoute ou retire la classe
  selon `condition`. Pratique pour synchroniser plusieurs boutons d'un
  coup.

### Bloc 9 — Animation après changement de filtre

```js
    if (activeCountryId && opt.dataset.lat && opt.dataset.lng) {
      map.flyTo({ center: [parseFloat(opt.dataset.lng), parseFloat(opt.dataset.lat)], zoom: 4.5, duration: 1800, essential: true });
    } else {
      map.flyTo({ center: CENTRE_DEFAUT, zoom: ZOOM_DEFAUT, duration: 1800, essential: true });
    }
  });
}
```

Vol vers le pays sélectionné, ou retour à la vue monde si "Tous les pays".

## Évolutions possibles

- **Recherche / filtre dans la liste pays** : un input texte au-dessus de
  la liste pour filtrer par nom.
- **Groupement des pays par continent** dans le panneau.
- **Notification du nombre de lieux par pays** dans le bouton (ex: "France (12)").
- **Mémorisation de la dernière sélection** en `localStorage` pour reprendre
  où on s'est arrêté.
- **Animation slide-in du panneau** quand la page se charge.
- **Mode "compact"** sur mobile : panneau caché par défaut, ouvrable via
  un bouton hamburger.
