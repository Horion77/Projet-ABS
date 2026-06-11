# 10 — La vue PHP `carte/index.php`

## Contexte

Le projet ABS suit une architecture **MVC** maison. La page carte est
construite côté serveur par :

- **`CarteController`** → récupère les données depuis les Models et appelle
  la vue.
- **`LieuModel::lieuxPourCarte()`** → renvoie la liste des lieux (avec
  leurs notes moyennes agrégées).
- **`LieuModel::paysPourFiltreCarte()`** → renvoie la liste des pays
  référencés avec leurs stats (centre GPS, nombre de lieux, note moyenne).
- **`carte/index.php`** → la vue : HTML + injection des données pour le JS.

La vue a une particularité : c'est un **pont PHP ↔ JavaScript**. Toutes
les données qui seront utilisées par `map.js` doivent être sérialisées en
JSON et injectées dans la page.

## Structure du fichier

```
1. En-tête PHP : extraction et préparation des variables
2. Configuration de JSON_UNESCAPED_UNICODE pour les emojis et accents
3. Tableau $mapData : objet de données pour JS
4. Tag <link> Mapbox CSS + Google Fonts
5. Conteneur #map-wrapper
   ├── #map-controls (barre du haut : titre, filtre, switcher, compteur)
   ├── #nav-panel (panneau gauche : continents + pays)
   ├── #map (la carte Mapbox)
   ├── #legende-carte (légende couleurs en bas à droite)
   └── #pays-hover-card (mini-card au survol, déplacée par JS)
6. <script> Mapbox GL JS depuis CDN
7. Injection JSON de $mapData dans window.MAP_DATA
8. <script src="map.js">
```

## Code expliqué

### Bloc 1 — En-tête : variables par défaut

```php
<?php
$places      = $places      ?? [];
$countries   = $countries   ?? [];
$mapboxToken = $mapboxToken ?? '';
```

- `$variable ?? valeur` : opérateur **null coalescent** PHP. Si la variable
  n'est pas définie ou vaut null, on prend la valeur de droite.
- Garde-fou : si le Controller a oublié de passer une variable, on ne crashe pas.

### Bloc 2 — Configuration JSON

```php
$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= constant('JSON_INVALID_UTF8_SUBSTITUTE');
}
```

- `JSON_UNESCAPED_UNICODE` : par défaut, `json_encode` échappe les caractères
  non-ASCII en `\uXXXX`. Avec ce flag on garde les caractères **tels quels**
  (utile pour les emojis et accents : "Brésil" reste "Brésil", pas
  `"Br\u00e9sil"`).
- `JSON_INVALID_UTF8_SUBSTITUTE` : si la BDD contient des caractères
  invalides UTF-8 (mojibake), on les remplace par un caractère de remplacement
  au lieu de faire planter `json_encode`. Cette constante n'existe que sur
  PHP 7.2+, d'où le `defined()`.
- `|=` : opérateur bit-à-bit OR-assign. Les flags JSON sont combinables
  comme un bitmask.

### Bloc 3 — Construction de $mapData

```php
$mapData = [
    'token'       => $mapboxToken,
    'places'      => $places,
    'countries'   => $countries,
    'placePath'   => url('lieu'),
    'paysPath'    => url('pays'),
    'regionsPath' => url('assets/data/regions.json'),
];
$nbLieux = count($places);
```

- Objet de données qui sera converti en JS.
- `url('lieu')` : helper qui prend un chemin relatif et le préfixe par le
  `base_url` configuré (ex: `/Projet-ABS/public/lieu`).
- Le JS accède ensuite à ces valeurs via `window.MAP_DATA.placePath`, etc.

### Bloc 4 — Inclusions CSS

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.css" rel="stylesheet">
```

- **Inter** : police sans-serif moderne, lisible, utilisée pour toute la
  carte. `display=swap` indique au navigateur d'afficher d'abord une police
  système puis switcher quand Inter est chargée (évite le FOIT, Flash Of
  Invisible Text).
- **Mapbox GL CSS** : indispensable pour le rendu des contrôles, popups
  et marqueurs natifs Mapbox.

### Bloc 5 — Classe `map-page` sur le body

```html
<script>document.body.classList.add('map-page');</script>
```

Ajoute une classe au `<body>` pour permettre des overrides CSS spécifiques
à la page carte (par exemple : `body.map-page { overflow: hidden; }`).
On le fait via JS parce que le layout commun ne sait pas qu'il sert une
page carte.

### Bloc 6 — Conteneur principal

```html
<div id="map-wrapper">
    <!-- ... -->
</div>
```

Wrapper avec une `position: relative` (en CSS) qui sert d'ancrage pour
tout ce qui est positionné en absolute : panneau, barre de contrôles,
légende, hover-card.

### Bloc 7 — Barre de contrôles (haut)

```html
<div id="map-controls">
    <div id="map-controls-inner">
        <h1 id="map-title">Explorer le monde</h1>
        <div id="country-filter-wrap">
            <label for="country-filter">Filtrer par pays</label>
            <select id="country-filter">
                <option value="">Tous les pays</option>
                <?php foreach ($countries as $c) : ?>
                <option value="<?= (int) $c['id_pays'] ?>"
                        data-lat="<?= e((string) ($c['lat'] ?? '0')) ?>"
                        data-lng="<?= e((string) ($c['lng'] ?? '0')) ?>">
                    <?= e((string) $c['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="style-switcher">
            <button class="style-btn active" data-style="dark">Sombre</button>
            <button class="style-btn" data-style="satellite">Satellite</button>
            <button class="style-btn" data-style="outdoors">Terrain</button>
            <button class="style-btn" data-style="streets">Rues</button>
        </div>
        <div id="places-count">
            <span id="count-number"><?= $nbLieux ?></span> lieu<?= $nbLieux > 1 ? 'x' : '' ?>
        </div>
    </div>
</div>
```

Points clés :

- `(int) $c['id_pays']` : cast en int avant injection, sécurité de base
  pour éviter une injection de valeur exotique.
- `e()` : helper d'échappement HTML. Tout ce qui vient de la BDD passe
  par `e()` pour éviter les XSS.
- `data-lat` / `data-lng` : attributs HTML 5 qui stockent les coordonnées,
  lues ensuite côté JS pour le `flyTo`.
- Les boutons `style-btn` portent un `data-style="dark"` qui correspond
  aux clés de l'objet `STYLES` côté JS.

### Bloc 8 — Panneau de navigation gauche

```html
<div id="nav-panel">
    <button id="nav-world" class="nav-btn nav-world-btn">Monde entier</button>
    <div class="nav-section">
        <span class="nav-label">Continents</span>
        <div id="nav-continents"></div>
    </div>
    <div class="nav-section">
        <span class="nav-label">Pays</span>
        <div id="nav-countries"></div>
    </div>
</div>
```

- Le panneau est en grande partie **vide** en HTML — les boutons sont
  générés par le JS (`buildNavPanel()` dans `map.js`).
- On a juste les conteneurs `<div id="nav-continents">` et `<div id="nav-countries">`
  qui servent de points d'ancrage.

### Bloc 9 — La carte

```html
<div id="map"></div>
```

Conteneur vide. Mapbox va y injecter tout son rendu (canvas + contrôles
natifs).

### Bloc 10 — Légende

```html
<aside id="legende-carte" class="legende-carte" aria-label="Légende des marqueurs">
    <h4>Types de lieux</h4>
    <ul>
        <li><span class="leg-puce leg-pays"></span>Pays</li>
        <li><span class="leg-puce leg-ville"></span>Ville</li>
        <li><span class="leg-puce leg-monument"></span>Monument</li>
    </ul>
</aside>
```

- Balise `<aside>` : élément HTML 5 sémantique pour du contenu lié mais
  secondaire (parfait pour une légende).
- `aria-label` : étiquette pour les lecteurs d'écran.
- Les `<span class="leg-puce leg-pays">` sont les pastilles colorées
  (styles dans `map.css`).

### Bloc 11 — Hover-card pays

```html
<div id="pays-hover-card" class="pays-hover-card" hidden></div>
```

- Conteneur vide. Le contenu est généré et positionné par JS au survol.
- Attribut `hidden` : caché par défaut, équivalent à `display: none`.

### Bloc 12 — Injection JS

```html
<script src="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.js"></script>
<script>
window.MAP_DATA = <?= json_encode($mapData, $jsonFlags) ?>;
</script>
<script src="<?= e(asset('js/map.js')) ?>"></script>
```

**Ordre crucial** :

1. D'abord on charge **Mapbox GL JS** depuis le CDN.
2. Ensuite on injecte les données serveur dans `window.MAP_DATA`.
3. Enfin on charge **`map.js`** qui va lire `window.MAP_DATA` et utiliser
   `mapboxgl`.

Si on inversait 1 et 3, `map.js` chercherait `mapboxgl` qui n'existerait
pas encore. Si on inversait 2 et 3, `map.js` chercherait `MAP_DATA` qui
serait `undefined`.

- `json_encode($mapData, $jsonFlags)` : sérialise le tableau PHP en JSON
  avec les flags définis plus haut (Unicode préservé).
- `asset('js/map.js')` : helper qui retourne le chemin complet vers le
  fichier statique. Permet de versionner ou changer le préfixe sans
  toucher au code.

## Évolutions possibles

- **Inclure une meta description spécifique** à la page carte pour le SEO.
- **Lazy loader Mapbox** : charger Mapbox seulement quand l'utilisateur
  interagit avec la carte (économise la bande passante au chargement).
- **Préchargement DNS** : `<link rel="preconnect" href="https://api.mapbox.com">`
  pour gagner ~50 ms au chargement.
- **Mode "skeleton"** : afficher un placeholder de carte pendant que Mapbox
  se charge.
- **Server-side rendering du JSON dans un `<script type="application/json">`**
  plutôt que dans une variable globale, pour respecter la CSP (Content
  Security Policy) si on l'active un jour.
