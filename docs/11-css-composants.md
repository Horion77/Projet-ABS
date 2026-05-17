# 11 — CSS des composants ajoutés

## Contexte

Trois composants visuels ont été ajoutés au-dessus de la base existante
`map.css` pour les nouvelles features :

1. **Popups Mapbox stylisés** (`.abs-popup` et descendants).
2. **Légende couleurs** en bas à droite (`.legende-carte`).
3. **Mini-card au survol d'un pays** (`.pays-hover-card`).

Tous suivent la même charte : fond sombre semi-transparent + bordure
bleutée + ombre portée + flou de fond (`backdrop-filter`) pour un effet
glassmorphism cohérent.

## Pourquoi du CSS et pas du JS pour positionner ?

CSS est **beaucoup plus rapide** que JS pour le rendu visuel : le
navigateur peut accélérer le rendu via le GPU sur les propriétés simples
(`opacity`, `transform`, `position`). C'est seulement pour le mouvement
dynamique (suivi du curseur dans la hover-card) qu'on touche au DOM
depuis JS.

## Variables CSS de base (rappel)

Définies dans `body.map-page` en haut de `map.css` :

```css
--carte-bg:        #050514;
--carte-surface:   rgba(10, 14, 32, 0.88);
--carte-surface-2: rgba(16, 22, 48, 0.92);
--carte-border:    rgba(120, 160, 255, 0.18);
--carte-border-h:  rgba(120, 160, 255, 0.45);
--carte-text:      #e6ecff;
--carte-text-dim:  rgba(200, 215, 255, 0.65);
--carte-accent:    #7eb3ff;
--carte-gold:      #ffc857;
--carte-glow:      0 0 0 1px rgba(120, 160, 255, 0.12), 0 10px 30px rgba(0, 0, 30, 0.55);
```

Les couleurs sont déclarées une seule fois ici puis réutilisées partout
via `var(--carte-accent)`. Si on veut changer la palette, on modifie
juste ces 10 lignes.

## Composant 1 — Popups Mapbox

```css
.mapboxgl-popup.abs-popup .mapboxgl-popup-content {
    background: var(--carte-surface-2);
    border: 1px solid var(--carte-border);
    border-radius: 14px;
    color: var(--carte-text);
    padding: 0;
    overflow: hidden;
    max-width: 290px;
    backdrop-filter: blur(10px);
}
```

- **`.mapboxgl-popup.abs-popup ...`** : on cible la classe `.abs-popup`
  qu'on injecte via JS (`new mapboxgl.Popup({ className: 'abs-popup' })`)
  **combinée** avec la classe Mapbox par défaut `.mapboxgl-popup`. Le
  double sélecteur sans espace augmente la **spécificité** : nos règles
  écrasent celles par défaut de Mapbox sans avoir besoin d'`!important`.
- `border-radius: 14px` : coins arrondis modernes (pas carrés type
  Mapbox vanilla).
- `padding: 0` + `overflow: hidden` : permet que l'image en haut du popup
  touche les bords arrondis sans débordement.
- `backdrop-filter: blur(10px)` : flou le fond derrière le popup
  (glassmorphism). Pas supporté sur Firefox sans flag mais dégradation
  gracieuse.

```css
.mapboxgl-popup.abs-popup .mapboxgl-popup-tip {
    border-top-color: var(--carte-surface-2);
}
```

- Le "tip" est la petite flèche/triangle qui pointe du popup vers son
  ancrage. Sa couleur doit matcher le fond du popup.
- `border-top-color` parce que la flèche est faite avec un triangle CSS
  classique (combinaison de `border-X` transparents et `border-Y` colorée).

```css
.mapboxgl-popup.abs-popup .mapboxgl-popup-close-button {
    color: var(--carte-text-dim);
    font-size: 1.3rem;
    padding: 6px 12px;
    background: transparent;
    line-height: 1;
}
.mapboxgl-popup.abs-popup .mapboxgl-popup-close-button:hover { color: #fff; }
```

Bouton fermer (le `×`) restylé pour s'intégrer au thème sombre.

### Lien "Voir les avis"

```css
.popup-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, var(--carte-accent-2), var(--carte-accent));
    color: #fff;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 4px 12px rgba(79, 124, 232, 0.35);
}
.popup-link:hover {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(79, 124, 232, 0.55);
}
```

- **`linear-gradient(135deg, ...)`** : dégradé bleu en diagonale (135° =
  du coin haut-gauche au coin bas-droit).
- **`transition: transform 0.15s`** : au survol, le bouton se soulève
  légèrement (`translateY(-1px)`). L'œil perçoit ça comme un feedback
  haptique virtuel.
- **`box-shadow`** : ombre bleutée pour donner de la profondeur, qui
  s'intensifie au hover.

## Composant 2 — Légende

```css
.legende-carte {
    position: absolute; bottom: 100px; right: 14px; z-index: 5;
    background: var(--carte-surface);
    color: var(--carte-text);
    border: 1px solid var(--carte-border);
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 0.78rem;
    min-width: 140px;
    pointer-events: none;
    box-shadow: var(--carte-glow);
}
```

- `position: absolute` + `bottom: 100px; right: 14px` : ancrage en bas
  à droite du `#map-wrapper`. Le 100px de marge bottom évite de recouvrir
  les contrôles natifs Mapbox (zoom +/- et fullscreen).
- `z-index: 5` : au-dessus de la carte (qui est à z-index 0) mais
  en-dessous des contrôles haut (z-index 10).
- **`pointer-events: none`** : crucial. Sans ça, la légende intercepterait
  les clics et les drags sur la carte qui passent par-dessus. Avec, la
  carte reste interactive même sous la légende.

```css
.legende-carte h4 {
    margin: 0 0 6px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--carte-text-dim);
    padding-bottom: 5px;
    border-bottom: 1px solid var(--carte-border);
}
```

Titre en petites majuscules espacées, avec un séparateur horizontal en
dessous. Style "indicateur secondaire" pour ne pas voler la vedette.

```css
.legende-carte ul { margin: 0; padding: 0; list-style: none; }
.legende-carte li {
    display: flex; align-items: center;
    gap: 9px;
    margin: 4px 0;
    color: var(--carte-text-dim);
}
.leg-puce {
    width: 12px; height: 12px;
    border-radius: 50%;
    display: inline-block;
}
```

- `list-style: none` + `padding: 0` : neutralise le style par défaut des
  `<ul>` (puces noires et indentation).
- `.leg-puce` est la pastille de couleur. `border-radius: 50%` → cercle
  parfait. Sa couleur est appliquée par les classes spécifiques
  `.leg-pays`, `.leg-ville`, `.leg-monument` (pas montrées ici mais
  définies en dessous dans le CSS).

## Composant 3 — Mini-card au survol

```css
.pays-hover-card {
    position: absolute;
    pointer-events: none;
    background: var(--carte-surface-2);
    border: 1px solid var(--carte-border);
    border-radius: 10px;
    padding: 10px 14px;
    color: var(--carte-text);
    font-size: 0.82rem;
    z-index: 8;
    max-width: 240px;
    box-shadow: 0 8px 28px rgba(0, 0, 30, 0.65);
}
```

- `position: absolute` : nécessaire pour pouvoir la déplacer via
  `style.left/top` depuis JS.
- `pointer-events: none` : indispensable. La hover-card est positionnée
  juste à côté du curseur. Si elle interceptait le pointeur, elle
  empêcherait le `mousemove` sur le pays d'être détecté.
- `z-index: 8` : au-dessus de la légende, en-dessous des contrôles haut.
- Pas d'effet `transition` parce que le déplacement est piloté par JS à
  chaque frame ; une transition CSS créerait du retard visuel.

```css
.pays-hover-card h5 {
    margin: 0 0 4px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #fff;
}
.pays-hover-card .phc-stats {
    color: var(--carte-text-dim);
    font-size: 0.74rem;
}
```

Hiérarchie typographique : titre du pays plus gros + en blanc, stats en
dessous plus petites + plus discrètes.

## Responsive : cacher la légende en mobile

```css
@media (max-width: 768px) {
    /* ... */
    .legende-carte { display: none; }
}
```

Sur mobile, l'écran est trop petit pour afficher en plus la légende sans
gêner. On la cache. La hover-card est aussi désactivée implicitement
parce que `mousemove` n'existe pas en tactile.

## Glassmorphism : ingrédients

Le style "verre dépoli" qui revient sur les 3 composants combine :

1. **Fond semi-transparent** : `rgba(...)` avec alpha < 1.
2. **`backdrop-filter: blur(...)`** : floute ce qu'il y a derrière.
3. **Bordure très claire** : `rgba(120, 160, 255, 0.18)`.
4. **Ombre portée généreuse** : `box-shadow: 0 10px 30px ...`.

Cette palette donne un effet "interface futuriste" sans nécessiter
d'images de fond.

## Évolutions possibles

- **Animation d'entrée** : fade-in + scale sur les popups à l'apparition
  (CSS animation sur `.abs-popup`).
- **Mode "high contrast"** : version alternative des couleurs pour
  l'accessibilité.
- **Thème clair** : déclarer un set de variables `--carte-*` alternatif
  appliqué via une classe `.theme-light` sur `body.map-page`.
- **Réduction de motion** : `@media (prefers-reduced-motion: reduce)`
  pour désactiver les transitions sur les utilisateurs qui ont activé
  l'option système.
- **Tailwind/utility-first** : refactorer en classes utilitaires si on
  intègre un framework. Pas urgent.
