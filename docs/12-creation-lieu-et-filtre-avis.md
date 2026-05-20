# 12 — Création de lieu par l'utilisateur + filtre par avis

## Contexte / problème

Jusqu'à cette version, la carte affichait uniquement les lieux **insérés en BDD
à la main** (via les seeds SQL). L'application restait passive : un utilisateur
lit des avis sur des lieux qui lui sont imposés. Deux besoins se sont posés :

1. **Permettre aux utilisateurs d'ajouter eux-mêmes** des pins (un restaurant
   qu'ils connaissent, un monument absent de la liste…) pour que tout le monde
   puisse ensuite y déposer un avis.
2. **Filtrer la carte** par type d'avis — voir « tous les lieux », « ceux qui
   ont au moins un avis » ou « ceux où je suis déjà intervenu ».

## Solution retenue

### Création d'un lieu

- **Barre de recherche d'adresse** (composant officiel `<mapbox-search-box>`)
  intégrée dans la barre de contrôles haute. Permet de zoomer rapidement
  sur une zone avant de poser un pin.
- **Bouton « + Ajouter un lieu »** visible uniquement pour les utilisateurs
  connectés. Au clic il active un *add-mode* (curseur croix + bandeau d'aide).
- **Pose du pin** : un clic sur la carte fait apparaître un marqueur jaune
  draggable. En arrière-plan, un appel à l'API **Geocoding inverse** de Mapbox
  remplit automatiquement adresse, ville et pays.
- **Formulaire popup** : nom, catégorie (alimentée par la BDD), description et
  photo optionnelle. L'envoi se fait en AJAX via `POST /lieu/creer`.
- **Auto-création de ville/pays** : si la ville ou le pays détecté par Mapbox
  n'existent pas en BDD, ils sont créés à la volée par le contrôleur.

### Filtre par avis

Un second `<select>` à côté du filtre pays propose trois modes :

| Mode | Effet |
|---|---|
| `tous` | Comportement original — tous les lieux visibles |
| `avecAvis` | Seuls les lieux ayant au moins un avis public (`review_count > 0`) |
| `mesAvis` | Seuls les lieux où l'utilisateur connecté a personnellement avisé |

Le filtre s'applique à la fois sur les marqueurs DOM **et** sur la source
GeoJSON du clustering, sinon le cluster décompterait des pins qu'on a cachés.

## Alternatives écartées

- **Plugin tiers pour l'autocomplete adresse** : envisagé, mais Mapbox fournit
  déjà un composant officiel (`<mapbox-search-box>`) compatible avec le free
  tier. Inutile d'ajouter une dépendance externe supplémentaire.
- **Modération admin avant publication d'un pin** : volontairement non
  implémentée pour la V1 (pas d'interface admin existante). Tous les pins
  utilisateur sont publics immédiatement.
- **Photo uploadable pour les avis** : laissée hors scope, la table
  `photo_avis` existe déjà et c'est l'équipière en charge des avis qui
  ajoutera l'UI plus tard.
- **Recherche de doublons à proximité** (« ce lieu existe déjà à 12 m de
  votre clic ») : utile mais demande une vraie réflexion produit (rayon ?
  matching sur le nom ?). Reportée à une V2.
- **Filtre par avis sous forme de checkbox** : un simple toggle ne suffisait
  pas pour les trois modes demandés. Un `<select>` reste plus lisible et
  cohérent avec le filtre pays voisin.

## Code expliqué

### Backend — `app/Controllers/LieuController.php::creer()`

1. Vérifie que la requête est bien `POST` et que l'utilisateur est connecté.
   En cas d'échec : réponse JSON 401/405.
2. Lit et valide les champs : `nom`, `type`, `id_categorie`, coordonnées GPS,
   ville et pays renvoyés par le reverse-geocoding.
3. Traite l'upload photo : vérification du MIME réel avec `finfo` (pas
   l'extension), taille max 5 Mo, nom de fichier unique généré côté serveur,
   stockage dans `public/uploads/lieux/`. Un `.htaccess` désactive
   l'exécution PHP dans ce dossier.
4. Résout le pays via `PaysModel::trouverOuCreerParCodeIso()`. Cette méthode
   convertit le code ISO 2 lettres renvoyé par Mapbox (`fr`) vers le code 3
   lettres stocké en BDD (`FRA`) grâce à une table statique embarquée
   (constante `ISO2_VERS_ISO3` couvrant les 250 pays ISO 3166-1).
5. Résout la ville via `LieuModel::trouverOuCreerVille()` (recherche
   insensible à la casse, création si absente).
6. Insère le lieu via `LieuModel::creer()` et renvoie un JSON contenant la
   représentation complète du nouveau pin (id, coords, image, etc.) — pour
   que le JS l'ajoute à la carte sans rechargement.

### Frontend — `public/assets/js/map.js`

- **Recherche** : le composant `<mapbox-search-box>` reçoit son token via
  l'attribut HTML `access-token` (pour qu'il soit dispo dès la première
  frappe). Une fois le composant défini (`customElements.whenDefined`), on le
  branche à la carte et on écoute l'évènement `retrieve` pour déclencher un
  `flyTo` sur la suggestion choisie.
- **Add-mode** : un drapeau global `addMode`. Le bouton bascule l'état et
  ajoute la classe `map-add-mode` au `<body>` pour activer le curseur croix.
- **Marker temporaire** : créé par `poserMarkerTemp()`. Draggable : si
  l'utilisateur déplace le pin, on relance le reverse-geocoding pour
  rafraîchir les champs adresse / ville / pays.
- **Soumission** : `soumettreFormulaire()` envoie un `FormData` complet (y
  compris le fichier photo si présent) en `fetch` POST. À succès : on pousse
  le nouveau lieu dans `places`, on rafraîchit la source GeoJSON du cluster
  (`refreshMonumentSource`) et on re-rend les markers DOM.
- **Filtre avis** : la fonction `appliquerFiltreAvis()` est centralisée et
  utilisée à la fois par `renderMarkers()` (markers DOM) et par
  `buildMonumentFeatures()` (source GeoJSON du cluster). Sans cette
  centralisation, le cluster afficherait des pins que le DOM filtre.

### Synchronisation cluster / DOM

Le clustering Mapbox repose sur une source GeoJSON figée au chargement de la
page. Lors d'un ajout de lieu **ou** d'un changement de filtre avis, il faut
appeler `map.getSource('monuments-source').setData(...)` pour que le cluster
reflète la nouvelle réalité. C'est le rôle de `refreshMonumentSource()`.

## Évolutions possibles

- **Galerie photos** pour un lieu (plusieurs images, pas une seule)
- **Édition / suppression** d'un lieu créé par soi-même
- **Modération** (statut `pending/approved/rejected` à ajouter sur `lieu`)
- **Détection de doublons** à la création (recherche dans un rayon de 50 m)
- **Catégorie auto-détectée** à partir du POI Mapbox (restaurant, musée…)
- **Champ `id_utilisateur_createur`** sur la table `lieu` pour savoir qui a
  ajouté chaque pin (utile pour des stats ou une page profil enrichie)
- **Filtres combinés persistés dans l'URL** (`?avis=mesAvis&pays=FRA`) pour
  pouvoir partager un état précis de la carte
