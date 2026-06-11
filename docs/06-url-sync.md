# 06 — Synchronisation de la vue dans l'URL

## Contexte

Sur Google Maps ou Letterboxd, on peut **copier-coller l'URL** pour
partager une vue précise à un ami. L'URL contient les coordonnées + le
zoom, et la page se réouvre exactement au même endroit.

C'est très pratique pour partager "regarde tous les lieux que j'ai notés
à Rome" sans devoir naviguer manuellement à chaque fois.

## Solution retenue

Deux fonctions complémentaires :

1. **`lireEtatUrl()`** : appelée **une fois** au chargement, elle lit les
   paramètres `?lng=...&lat=...&zoom=...` et les utilise pour initialiser
   la carte.
2. **`ecrireEtatUrl()`** : appelée à chaque `moveend` de la carte, elle
   met à jour ces paramètres dans l'URL **sans recharger la page**.

Le mécanisme utilisé pour modifier l'URL est `history.replaceState()`
(remplace l'entrée d'historique courante, contrairement à `pushState` qui
ajoute une nouvelle entrée). C'est important : sinon chaque petit
déplacement de carte créerait une nouvelle entrée dans l'historique
navigateur, et le bouton "Précédent" deviendrait inutilisable.

L'écriture est **debounced** à 350 ms pour éviter d'appeler `replaceState`
trop souvent pendant un drag continu.

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Stocker la position en `localStorage`** | Marche mais ne permet pas de partager une URL. Pas le cas d'usage qu'on voulait. |
| **`pushState` au lieu de `replaceState`** | Polluerait l'historique navigateur, le bouton "Précédent" deviendrait inutilisable. |
| **Écrire à chaque `move` au lieu de `moveend`** | Trop d'appels (60 fps), gaspillage de CPU. Le `moveend` arrive une seule fois à la fin d'un drag. |
| **Pas de debounce** | Même si `moveend` est rare, une animation `flyTo` peut envoyer plusieurs `moveend` consécutifs. Un debounce de 350 ms les fusionne. |
| **Format hash `#lat,lng,zoom`** | Plus propre visuellement mais moins parsable que des `?param=value`. Pas plus court. |

## Code expliqué ligne par ligne

### Bloc 1 — `lireEtatUrl()` : lecture initiale

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

- `window.location.search` : partie de l'URL après le `?`, par ex.
  `?lng=2.35&lat=48.85&zoom=12`.
- `new URLSearchParams(...)` : objet natif du navigateur pour parser
  facilement les query strings. `p.get('lng')` retourne `"2.35"` (string).
- `parseFloat(...)` : conversion string → number.
- `isNaN(...)` : si la conversion a échoué (paramètre absent ou invalide),
  on retourne `null` → la carte utilisera les valeurs par défaut.
- `etatUrl` est ensuite utilisée à la création de la carte :
  ```js
  center: etatUrl ? [etatUrl.lng, etatUrl.lat] : CENTRE_DEFAUT,
  zoom:   etatUrl ? etatUrl.zoom              : ZOOM_DEFAUT,
  ```

### Bloc 2 — `ecrireEtatUrl()` : écriture avec debounce

```js
var urlTimer = null;
function ecrireEtatUrl() {
  clearTimeout(urlTimer);
  urlTimer = setTimeout(function () {
    var c = map.getCenter();
    var p = new URLSearchParams(window.location.search);
    p.set('lng',  c.lng.toFixed(4));
    p.set('lat',  c.lat.toFixed(4));
    p.set('zoom', map.getZoom().toFixed(2));
    history.replaceState(null, '', '?' + p.toString());
  }, 350);
}
```

- `clearTimeout(urlTimer)` : annule le timeout en attente s'il y en a un.
- `setTimeout(fn, 350)` : appelle `fn` dans 350 ms.
- Si on appelle `ecrireEtatUrl()` 10 fois en 350 ms (par exemple pendant
  un drag), seule la **dernière** sera effectivement exécutée. C'est le
  principe du **debounce** : on regroupe une rafale d'appels en un seul.

- `map.getCenter()` : retourne un objet `{ lng, lat }` du centre de la
  carte actuelle.
- `c.lng.toFixed(4)` : arrondi à 4 décimales (précision ~10 m, suffisante
  pour la navigation et évite des URL à rallonge).
- `map.getZoom().toFixed(2)` : précision 2 décimales sur le zoom.

- `p.set('lng', ...)` : modifie ou ajoute le paramètre `lng`.
- `history.replaceState(null, '', '?' + p.toString())` :
  - 1er argument `null` : state object (pas utilisé ici, c'est pour la
    History API avancée).
  - 2e argument `''` : titre du document (les navigateurs l'ignorent
    aujourd'hui, mais l'argument est obligatoire).
  - 3e argument : nouvelle URL. Le `?` reste, le chemin ne change pas.

### Bloc 3 — Branchement sur `moveend`

```js
map.on('moveend', function () {
  document.getElementById('map').classList.remove('map-moving');
  updateVisibility();
  ecrireEtatUrl();
});
```

À la fin de chaque mouvement de carte (drag, flyTo, easeTo, zoom...), on
met à jour l'URL.

## Détail : pourquoi `replaceState` et pas `window.location.search = ...` ?

Si on assigne directement `window.location.search = '?lng=...'`, le
navigateur **recharge la page**. C'est exactement ce qu'on veut éviter.

`history.replaceState()` est une API spécifique pour modifier l'URL **sans
recharger**. C'est ce que font les SPAs (Single Page Applications) modernes.

## Évolutions possibles

- **Ajouter d'autres états dans l'URL** : style de carte (`&style=satellite`),
  pays sélectionné (`&pays=FRA`), filtres actifs, etc.
- **Bouton "copier le lien"** dans l'UI pour faciliter le partage.
- **Encodage plus compact** : `?at=2.35,48.85,12` au lieu de trois paramètres.
- **Vérifier les bornes** : si les valeurs URL sont absurdes (lat > 90),
  les ignorer plutôt que d'appliquer une vue cassée.
- **Sync inverse depuis l'URL** : si l'utilisateur modifie l'URL manuellement
  ou navigue via le bouton précédent, on pourrait écouter `popstate` pour
  resyncer la carte.
