# CLAUDE.md — Contexte projet ABS (Alan)

Ce fichier est lu automatiquement par Claude Code à chaque session.
Il permet la continuité entre le PC fixe et le PC de cours.

## Projet
**ABS** — Application web de notation de lieux de voyage (style Letterboxd).
- Cours : Programmation Web (HTML / CSS / PHP / MySQL — aucun framework)
- Équipe : Alan (A), Basma (B), Sara (S)
- Repo : https://github.com/Horion77/Projet-ABS

## Rôle d'Alan
Branche : `feature/alan-map`
Fichiers responsables :
- `pages/map.php` — Page carte interactive
- `pages/place.php` — Détail d'un lieu + avis
- `assets/js/map.js` — Logique JS de la carte
- `assets/css/map.css` — Styles carte

## Stack technologique
- Backend : PHP 8+ natif (PDO), aucun framework
- Frontend : HTML5, CSS3 natif, JavaScript vanilla
- Carte : **Mapbox GL JS** (CDN, plan gratuit) — globe 3D avec fond étoilé
- BDD : MySQL — tables : `users`, `countries`, `places`, `reviews`
- Serveur local : XAMPP/WAMP/MAMP → http://localhost/Projet-ABS/

## Choix technique carte
- **Mapbox GL JS** (pas Leaflet) pour le rendu 3D globe avec atmosphère/étoiles
- Référence visuelle : geojson.io (globe 3D, fond sombre avec étoiles)
- Token Mapbox : à renseigner dans `assets/js/map.js` (variable `MAPBOX_TOKEN`)
- Projection : `globe`
- Sky layer pour les étoiles et l'atmosphère
- 3D buildings layer (fill-extrusion)

## Base de données
Tables utilisées par Alan (en lecture pour reviews) :
```sql
countries  → id, name, code, lat, lng
places     → id, name, description, country_id, lat, lng, image_url, created_at
reviews    → id, user_id, place_id, rating, title, comment, created_at (lecture seule)
```

## État d'avancement
- [x] Branche `feature/alan-map` créée (locale, pas encore pushée)
- [x] `.gitignore` mis à jour (`.claude/` exclu du git)
- [x] `pages/map.php` — terminé (globe Mapbox GL JS, filtre pays, données PHP→JS)
- [x] `assets/js/map.js` — terminé (globe, fog/étoiles, sky layer, 3D buildings, marqueurs, popups, filtre)
- [x] `assets/css/map.css` — terminé (dark space theme, marqueurs, popups, responsive)
- [ ] `pages/place.php` — à construire
- [ ] Token Mapbox : inclus directement dans map.js (token Alan)

## Notes importantes
- Le token Mapbox doit être fourni par Alan (compte Mapbox.com gratuit)
- `config/database.php` est ignoré par git (fondation commune à faire en équipe)
- Les fichiers `.claude/` sont ignorés par git — la mémoire de session est dans ce CLAUDE.md
- Pour changer de PC : `git pull origin feature/alan-map` récupère tout le contexte via ce fichier
