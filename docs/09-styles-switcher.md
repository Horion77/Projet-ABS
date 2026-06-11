# 09 — Changement de style de carte

## Contexte

Mapbox propose plusieurs styles cartographiques préfabriqués. On a voulu
laisser à l'utilisateur le choix entre 4 ambiances :

- **Sombre** (par défaut) : style nuit, idéal pour l'effet "carte du
  voyageur" avec étoiles.
- **Satellite** : vraies images satellite, utile pour visualiser le
  terrain réel.
- **Terrain** (outdoors) : carte topographique avec relief.
- **Rues** : style plat type Google Maps.

Le challenge : quand on change le style Mapbox, **toutes les couches et
sources personnalisées sont effacées**. Il faut donc les **re-ajouter**
après chaque changement.

## Solution retenue

- Configuration des styles dans un objet `STYLES` (déjà vu en section 01).
- Boutons HTML avec une classe `.style-btn` et un attribut `data-style="dark"`
  identifiant la clé du style.
- Au clic : reset des variables d'état + appel à `map.setStyle(...)`.
- Réutilisation du listener `style.load` (déjà attaché) qui re-ajoute
  automatiquement toutes les couches.

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Avoir plusieurs cartes Mapbox** (une par style) | Trop lourd en RAM, et il faudrait dupliquer toutes les couches. |
| **`map.setStyle({ diff: true })`** | L'option `diff` essaie de préserver les couches custom mais elle est fragile : ça marche pour quelques modifs, pas quand on ajoute beaucoup de sources. |
| **Recharger toute la page** | Solution paresseuse, casse l'état URL et l'expérience utilisateur. |
| **Liste de styles encodée côté serveur** | Surdimensionné pour 4 valeurs qui ne changent pas. |

## Code expliqué ligne par ligne

### Bloc 1 — Listener sur les boutons

```js
document.querySelectorAll('.style-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var key = btn.dataset.style;
    if (!STYLES[key] || key === currentStyleKey) return;
    currentStyleKey = key;
```

- `querySelectorAll('.style-btn')` : tous les boutons de style en une fois.
- Pour chaque bouton, on attache un listener.
- `btn.dataset.style` : valeur de l'attribut `data-style`.
- **Garde** : si la clé est invalide ou si c'est déjà le style courant,
  on ne fait rien (évite le rerendering inutile).
- Mise à jour de `currentStyleKey` (utilisée par `applyAtmosphere()` et
  `applyTerrain()`).

### Bloc 2 — Mise à jour visuelle des boutons

```js
document.querySelectorAll('.style-btn').forEach(function (b) { b.classList.remove('active'); });
btn.classList.add('active');
```

- On enlève la classe `.active` de tous les boutons.
- On l'ajoute sur celui qu'on vient de cliquer.
- En CSS, `.style-btn.active` a un fond différent (cf. `map.css`).

### Bloc 3 — Reset des états avant changement de style

```js
hoveredCountryId  = null;
selectedCountryId = null;

markers.forEach(function (m) { m.marker.remove(); });
markers = [];
map.setStyle(STYLES[key].url);
});
});
```

- On reset les variables de hover/sélection parce que les ids étaient liés
  à la couche `pays-fill` qui va être détruite et recréée. Sans reset,
  on garderait des références fantômes.
- On supprime tous les marqueurs DOM (ils n'ont rien à voir avec le style
  Mapbox mais on les recrée juste après).
- `map.setStyle(url)` : déclenche le rechargement. Mapbox va :
  1. Charger le nouveau JSON de style.
  2. Détruire toutes les couches/sources de l'ancien style.
  3. Détruire toutes les couches **personnalisées** qu'on avait ajoutées.
  4. Une fois prêt, émettre l'événement `style.load`.

### Bloc 4 — Le listener `style.load` qui fait tout repartir

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

(Déjà vu en section 01.)

Important : c'est `map.on('style.load', ...)` (pas `map.once`). Le listener
**reste actif en permanence** et se redéclenche à chaque changement de
style. C'est ce qui rend le système simple : on définit le pipeline de
réinit une fois, et il s'applique à chaque switch.

## Diagramme du flux

```
Clic sur bouton "Satellite"
    ↓
currentStyleKey = "satellite"
    ↓
Reset hover/selected/markers
    ↓
map.setStyle(URL satellite)
    ↓ (Mapbox détruit l'ancien style + couches custom)
    ↓ (Mapbox charge le nouveau style)
    ↓
Événement "style.load" → applyAtmosphere, applyTerrain, addCountryLayer, ...
    ↓
Tout est repeuplé, on voit la carte avec ses pins en style satellite.
```

## Évolutions possibles

- **Sauvegarder le style choisi** dans `localStorage` (à recharger au
  prochain démarrage).
- **Style personnalisé Mapbox Studio** : on pourrait créer un style ABS
  custom (couleurs aux teintes du site, plus uniforme).
- **Style sombre auto** selon l'heure de la journée.
- **Transition douce entre styles** : Mapbox ne le fait pas nativement,
  mais on pourrait fader la carte en blanc puis remettre.
- **Style "dégradé" entre satellite et streets** : impossible techniquement
  (deux datasets), à oublier.
