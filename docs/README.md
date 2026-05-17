# Documentation technique — Module Carte

Cette doc couvre la **page Carte 3D** du projet ABS (`/carte`).
Elle est rédigée pour quelqu'un qui ne connaît pas JavaScript mais qui a déjà
codé dans un autre langage : chaque ligne d'API Mapbox y est expliquée.

## Périmètre couvert

- `app/Views/carte/index.php` — la vue PHP de la page carte
- `public/assets/js/map.js` — toute la logique JavaScript de la carte
- `public/assets/css/map.css` — styles spécifiques (légende, hover-card, popups)
- `app/Models/LieuModel.php::paysPourFiltreCarte()` — méthode utilisée pour
  enrichir les données passées au JS

Le reste (auth, profil, listing avis) n'est pas couvert ici, c'est la responsabilité
des autres membres de l'équipe.

## Sommaire

| Fichier | Sujet |
|---|---|
| [diagramme-classes.md](diagramme-classes.md) | Diagramme UML du modèle de données |
| [01-architecture-mapbox.md](01-architecture-mapbox.md) | Init Mapbox, projection globe, atmosphère, terrain 3D |
| [02-couche-pays.md](02-couche-pays.md) | Couche pays (hover, sélection, popup) |
| [03-clustering-monuments.md](03-clustering-monuments.md) | Clustering des monuments en vue dézoomée |
| [04-marqueurs-dom.md](04-marqueurs-dom.md) | Marqueurs HTML et popups Mapbox |
| [05-couche-regions.md](05-couche-regions.md) | Couche régions (Natural Earth) chargée à la demande |
| [06-url-sync.md](06-url-sync.md) | Synchronisation de la vue dans l'URL |
| [07-optim-mousemove.md](07-optim-mousemove.md) | Optimisation des événements `mousemove` |
| [08-navigation-filtres.md](08-navigation-filtres.md) | Panneau navigation gauche, filtre pays, continents |
| [09-styles-switcher.md](09-styles-switcher.md) | Changement de style de carte |
| [10-vue-php.md](10-vue-php.md) | La vue PHP `carte/index.php` |
| [11-css-composants.md](11-css-composants.md) | CSS des composants ajoutés |

## Comment lire cette doc

Chaque fiche suit le même plan :

1. **Contexte / problème** — pourquoi ce bout de code existe
2. **Solution retenue** — ce qui a été fait
3. **Alternatives écartées** — ce qui a été testé / envisagé et pourquoi ça n'a pas été retenu
4. **Code expliqué ligne par ligne** — y compris les appels à l'API Mapbox
5. **Évolutions possibles** — pour que la doc reste utile quand le code évoluera

## Stack utilisée

- **Mapbox GL JS v3.4.0** — librairie de cartographie JavaScript (chargée en CDN)
- **Projection** : `globe` (rendu 3D sphérique, façon Google Earth)
- **Style par défaut** : `mapbox://styles/mapbox/dark-v11`
- **Données pays** : tileset officiel Mapbox `country-boundaries-v1` (gratuit)
- **Données régions** : GeoJSON Natural Earth (admin-1, ~37 Mo, chargé à la demande)
- **Backend** : PHP 8 + PDO, communication par JSON via `window.MAP_DATA`

## Conventions de code

- **JavaScript** : pas de framework, vanilla JS, syntaxe ES5 compatible (`var`, `function`).
  Le code est dans une IIFE (`(function () { ... }())`) pour ne pas polluer `window`.
- **Nommage** : variables et fonctions en français (`trouverPaysDB`, `pathLieu`, `ecrireEtatUrl`).
- **Commentaires** : un commentaire par bloc fonctionnel, en français.
