# Matrice d’intégration (Alan / Basma / Sara)

Ce dépôt suit une **architecture MVC** (`app/`, `public/`, `sql/`). Les anciennes branches utilisaient parfois une structure `pages/`, `actions/`, `includes/` avec un schéma SQL différent (`places`, `countries`, `reviews`). Ces fichiers **ne sont plus utilisables tels quels** à côté du schéma actuel (`lieu`, `pays`, `avis`, etc.).

## Cartographie fonctionnelle → MVC

| Zone | Ancienne source (équipe / branche) | Implémentation MVC actuelle | Notes |
|------|-------------------------------------|-----------------------------|--------|
| Accueil | Base projet | `AccueilController`, `app/Views/accueil/` | |
| Connexion / déconnexion | Legacy `actions/login_*` | `ConnexionController`, `app/Views/connexion/` | POST `/connexion`, `/deconnexion` |
| Inscription | Legacy `actions/inscription_*` | `InscriptionController`, `app/Views/inscription/` | |
| Profil | Legacy `pages/profil.php` | `ProfilController`, `app/Views/profil/` | |
| Carte / globe Mapbox | Alan (`feature/alan-map`, port MVC) | `CarteController`, `app/Views/carte/`, `public/assets/js/map.js` | Pins typés, styles, terrain |
| Fiche lieu | Legacy `pages/place.php` | `LieuController`, `app/Views/lieu/afficher.php` | SQL centralisé dans `LieuModel` |
| Pays + lieux | Legacy `pages/pays.php` | `PaysController`, `app/Views/pays/afficher.php` | Schéma `pays` / `lieu`, pas `countries` / `places` |
| Liste des avis + pagination + filtres | Sara (`feature/sara-reviews`) | `AvisListeController`, `app/Views/avis/index.php` | Filtres note + pays en plus de la pagination |
| Soumission d’avis (POST) | Sara `actions/avis_action.php` | `AvisController::traiterSoumission`, route `POST /avis` | Champs acceptés : `place_id` / `id_lieu`, `rating` / `note`, `comment` / `description`, `title`, `visibility` |
| Validation JS formulaires | Partagé | `public/assets/js/validation.js` | Référencé depuis `app/Views/partials/pied.php` |
| Schéma BDD | Évolutions successives | `sql/schema/database.sql` + `sql/migrations/` | Colonnes `lieu.image_url`, `avis.titre`, etc. |

## Stratégie anti-perte

- **Ne pas réintroduire** les scripts legacy qui pointent vers `config/database.php` ou `includes/` : ils ne correspondent plus à l’arborescence ni au schéma.
- Pour toute nouvelle fonctionnalité : **Controller + Model + Vue** + route dans `app/Config/routes.php`.
- En cas de doute sur un comportement historique : consulter le commit `ab0577d` (*Version 1 Sara*) ou la branche `feature/sara-reviews` sur le dépôt distant, puis **porter** la logique vers le MVC (pas de copier-coller de chemins legacy).
