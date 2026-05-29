**Change Log**

Change log du projet A.B.S.

**Dépôt Git officiel :** [github.com/Horion77/Projet-ABS](https://github.com/Horion77/Projet-ABS) — cloner avec `https://github.com/Horion77/Projet-ABS.git`.

Les entrées **commit** renvoient vers `https://github.com/Horion77/Projet-ABS/commit/<hash>`.

---

# Versions
## [0.2.2] - 2026-05-29 — Audit sécurité & durcissement

### Sécurité — Sessions
* Ajout de `session_regenerate_id(true)` dans `Session::connecter()` (`app/Core/Session.php`) pour prévenir les attaques de fixation de session.

### Sécurité — Hachage des mots de passe
* Remplacement de `PASSWORD_DEFAULT` (BCrypt) par `PASSWORD_ARGON2ID` avec paramètres renforcés (`memory_cost=65536`, `time_cost=4`) dans `InscriptionController::traiterInscription()` et `ProfilController::modifierPassword()`.

### Sécurité — Validation mot de passe
* Durcissement des règles dans `InscriptionController` et `ProfilController` : minimum porté à 12 caractères, majuscule obligatoire, chiffre obligatoire — règles désormais cohérentes entre inscription et changement de mot de passe.

### Sécurité — Base de données
* Suppression des comptes de données de test (IDs 5 à 14) partageant le hash BCrypt du mot de passe `password` (`abs_security_patch.sql`).
* Suppression de la colonne redondante `avatar_url` dans la table `utilisateur` (doublon de `avatar`).
* Ajout de la contrainte `CHECK (note BETWEEN 1 AND 5)` sur la table `avis`.

## [0.2.1] - 2026-05-27 — Refonte UI globale, Profil interactif et correctif BDD

### Profil Utilisateur (`app/Views/profil/index.php`, `resources/css/style.css`)
* Ajout de formulaires interactifs dans des sections dépliables (`<details>`) pour modifier ses informations personnelles, changer son mot de passe et uploader une photo de profil.
* Correction du bug d'affichage de la photo de profil via l'utilisation dynamique du helper `url()`.
* Création d'un avatar par défaut (initiales générées dynamiquement sur fond dégradé violet) pour les utilisateurs sans image.
* Amélioration visuelle de la liste d'informations avec l'intégration d'icônes (👤, ✉️, 📅).
* Formatage naturel des dates d'inscription (`d/m/Y`) et de publication des avis (`d/m/Y à H\hi`).
* Remplacement de la note textuelle (X / 5) par un système visuel dynamique d'étoiles pleines et vides (⭐⭐⭐⭐☆).

### Style Global & Authentification (`resources/css/style.css`, `resources/css/auth.css`, `public/assets/js/stars.js`)
* Uniformisation du style de tous les boutons du site et des éléments de navigation : application d'un dégradé violet (de `#7c3aed` à `#a855f7`) avec une animation de pulsation/scintillement au survol.
* Refonte complète des pages **Connexion** et **Inscription** : intégration d'une "card" sombre opaque centrée.
* Création d'un moteur d'arrière-plan étoilé (CSS + JavaScript générant des étoiles statiques scintillantes et des étoiles flottantes) appliqué sur l'Accueil et les pages d'authentification.
* Correction de l'alignement du formulaire d'inscription (case à cocher des CGU parfaitement alignée avec le texte).
* Masquage du bouton "S'inscrire" sur la page d'accueil lorsque l'utilisateur est déjà connecté.
* Mise en place du "cache busting" sur le helper `asset()` pour forcer l'effacement du cache navigateur à chaque modification CSS.

### Base de données
* Création et configuration de la table `like_avis` manquante pour résoudre de manière définitive l'erreur fatale `PDOException` (Table doesn't exist) lors de la consultation des lieux et des avis.

## [0.2.0] - 2026-05-26 — Carte interactive, page Découvrir, likes & commentaires

### Carte (`public/assets/js/map.js`, `resources/css/map.css`)
* **Clustering natif Mapbox** : les lieux proches sont regroupés en bulles ; le clic sur une bulle zoome pour l'éclater. Remplace l'ancien système de pins par seuils de zoom.
* **Détection des pays par leurs frontières réelles** (tileset `country-boundaries-v1`) au lieu de pins centrés sur la moyenne des avis ; clic → panneau latéral (note moyenne, derniers avis). Tolérance de clic pour les nations insulaires.
* **Pins style TripAdvisor** : cercle à icône de catégorie, badge note coloré (vert → rouge), label nom + catégorie.
* **Panneau gauche** repliable (desktop) / en tiroir (mobile) : continents, pays populaires (top 10), filtre par type de lieu, sections repliables, mini-fiche au survol.
* Barre de contrôles compacte et défilable sur mobile ; correction de chevauchements (légende, panneau).

### Page Découvrir (`DecouvrirController`, `app/Views/decouvrir/`)
* Nouveau formulaire de recherche de lieux par type, note minimale, continent, pays et ville ; résultats triés par note. Remplace l'ancienne liste brute d'avis dans la navigation.

### Avis : likes & commentaires
* Likes sur les avis (`LikeController`, `LikeModel`, table `like_avis`) en AJAX.
* Commentaires et réponses sur les avis (`CommentaireController`, `CommentaireModel`).
* `AvisModel::statsParLieu()` : les avis privés comptent désormais dans la note moyenne.

### Base de données
* `sql/schema/database.sql` complété avec la table `like_avis`.
* Nouveaux jeux de données : `seed_avis_complet.sql` et `seed_monde_vivant.sql` (généré par `_generate_monde_vivant.js`) — ~90 utilisateurs, 29 pays, 90+ lieux, 270+ avis.
* `_fix_encoding.sql` : réparation des accents corrompus lors d'imports sans `--default-character-set=utf8mb4`.

## [0.1.4] - 2026-05-11 à 2026-05-13 — Clone d’intégration, rewrite & schéma lieu

### Configuration (2026-05-11)
* **`base_url`** : passage de `/Projet-ABS/public` à `/Projet-ABS-integration/public` dans `app/Config/application.php` — commit [`5e2efc4`](https://github.com/Horion77/Projet-ABS/commit/5e2efc4).
* **`RewriteBase`** : même alignement sous-dossier MAMP dans `public/.htaccess` — commit [`808c0ef`](https://github.com/Horion77/Projet-ABS/commit/808c0ef).

### Base de données & carte (2026-05-13)
* **`sql/schema/database.sql`** : ajout des colonnes `lieu.type` (ENUM) et `lieu.icon` (VARCHAR).
* **`LieuModel::tousAvecNotes()`** : constantes `type` / `icon` en SELECT tant que la base n’est pas entièrement migrée ; `GROUP BY` ajusté ; commentaire pointant vers la migration `2026_05_06_add_type_icon_lieu.sql` — commit [`d1f7b7f`](https://github.com/Horion77/Projet-ABS/commit/d1f7b7f).

## [0.1.3] - 2026-05-07 - Intégration Sara V2
### Référence Git
* Lot principal (vues avis/pays, contrôleur, validation JS, entrée CHANGELOG) : [`f1b9c60`](https://github.com/Horion77/Projet-ABS/commit/f1b9c60).

### Adaptation Sara vers MVC
* Branche locale : `integration-v2`.
* Reprise de `Projet-ABS-Sara/pages/avis.php` dans `app/Views/avis/index.php` : structure `avis-container`, cartes d’avis, messages et pagination conservés, avec routes MVC.
* Reprise de `Projet-ABS-Sara/pages/pays.php` dans `app/Views/pays/afficher.php` : structure `pays-container`, grille de lieux, note moyenne et lien vers la fiche lieu adaptés au schéma actuel.
* Reprise de `Projet-ABS-Sara/actions/avis_action.php` dans `AvisController::traiterSoumission` : POST `/avis`, utilisateur connecté, validation, insertion via `AvisModel`, message de succès Sara.

### Conservation des autres apports
* Carte Mapbox Alan conservée (`CarteController`, `app/Views/carte/`, `public/assets/js/map.js`).
* BDD, connexion, déconnexion et inscription Basma conservées (`sql/`, `ConnexionController`, `InscriptionController`).

## [0.1.2] - 2026-05-06 - Branche d’intégration équipe (MVC unique)
### Références Git (lot intégration / carte)
* [`87e8b4b`](https://github.com/Horion77/Projet-ABS/commit/87e8b4b) — chore(integration) : arbre MVC-only, docs équipe, `.gitattributes`.
* [`8a259ae`](https://github.com/Horion77/Projet-ABS/commit/8a259ae) — merge `origin/dev`, conservation fichiers Basma.
* [`5158423`](https://github.com/Horion77/Projet-ABS/commit/5158423) — feat(carte) : port feature/alan-map → MVC (globe 3D, pins, panneau nav).
* [`766c7ec`](https://github.com/Horion77/Projet-ABS/commit/766c7ec) — feat(carte) : globe 3D Mapbox, terrain, styles, pins par catégorie.
* Travaux complémentaires le même jour : [`6ac8769`](https://github.com/Horion77/Projet-ABS/commit/6ac8769) (changelog), [`f74c9ce`](https://github.com/Horion77/Projet-ABS/commit/f74c9ce) (liens), [`bfe721e`](https://github.com/Horion77/Projet-ABS/commit/bfe721e) (CSS pages), [`ab0577d`](https://github.com/Horion77/Projet-ABS/commit/ab0577d) (version 1 Sara).

### Intégration
* Branche de travail : `integrate/all-team-mvc` (clone d’intégration sous `Projet-ensemble/Projet-ABS-integration`).
* Documentation : `docs/INTEGRATION_MATRIX.md` (cartographie ancien code → MVC), `docs/TEAM_CONTRIBUTIONS.md` (qui a livré quoi).
* Normalisation Git : `.gitattributes` (EOL LF) ; `core.filemode=false` recommandé sur le clone pour éviter les diffs de permissions.

### Nettoyage
* Suppression des reliquats legacy `actions/avis_action.php`, `pages/avis.php`, `pages/pays.php` et du doublon `assets/js/validation.js` (chemins et schéma SQL non alignés avec le MVC actuel ; la logique équivalente est dans `AvisController`, `AvisListeController`, `PaysController` et `public/assets/js/validation.js`).

## [0.1.1] - 2026-05-06 - Corrections MAMP + compatibilité BDD
### Références Git
* [`859dc14`](https://github.com/Horion77/Projet-ABS/commit/859dc14), [`f9249fd`](https://github.com/Horion77/Projet-ABS/commit/f9249fd), [`e726980`](https://github.com/Horion77/Projet-ABS/commit/e726980), [`73bf584`](https://github.com/Horion77/Projet-ABS/commit/73bf584) — routes, `.htaccess`, 404, URLs.

### Corrections
* Correction du chargement des assets/CSS en local MAMP avec sous-dossier (`/Projet-ABS/public`) via `base_url`.
* Helpers d'URL centralisés et fiabilisés (`url()`, `asset()`) pour générer des chemins compatibles sous-dossier.
* Redirections internes harmonisées pour respecter la base URL de l'application.
* Suppression d'un reliquat de debug (`var_dump` + `die`) dans `public/index.php`.
* Mise à jour des vues (liens/formulaires/scripts) pour remplacer les chemins absolus cassants (`/...`) par des helpers.

### Base de données
* Résolution des erreurs SQL `Unknown column` sur `l.image_url` et `a.titre`.
* Alignement du schéma avec le code via les migrations :
  * `2026_04_30_add_image_url_lieu.sql`
  * `2026_04_30_add_titre_avis.sql`
* Vérification des colonnes en base : `lieu.image_url` et `avis.titre`.

## [0.1.0] - 2026-05-03 - Refonte MVC professionnelle
### Références Git
* [`bcaf2c0`](https://github.com/Horion77/Projet-ABS/commit/bcaf2c0), [`f97310b`](https://github.com/Horion77/Projet-ABS/commit/f97310b), [`921282f`](https://github.com/Horion77/Projet-ABS/commit/921282f) — refonte MVC, commentaires, avis/pays.

### Refonte structurelle
* Front controller unique : toutes les requêtes passent par `public/index.php`.
* Nouveau **Routeur** déclaratif dans `app/Config/routes.php` (URLs en français).
* DocumentRoot du serveur web pointe désormais sur `public/` (le code applicatif sort de la racine web).
* Noyau enrichi sous `app/Core/` : `Routeur`, `Controleur` (base), `Modele` (base), `BaseDeDonnees`, `Vue`, `Requete`, `Reponse`, `Session`, `Aides`.
* Tous les Models héritent de `Core\Modele` ; tous les Controllers héritent de `Core\Controleur`.

### Suppressions
* Dossiers `pages/`, `actions/`, `includes/` supprimés (fichiers stubs remplacés par les Controllers).
* `index.php` racine supprimé (remplacé par `public/index.php`).

### Renommages
* `HomeController` → `AccueilController`
* `AuthController` → `ConnexionController`
* `CountryController` → `PaysController`
* `ReviewController` → `AvisController`
* `ReviewsController` → `AvisListeController`
* `HomeModel` → `AccueilModel`
* `app/Models/Database.php` → `app/Core/BaseDeDonnees.php`
* `app/Core/View.php` → `app/Core/Vue.php`

### Ajouts
* Nouveaux Controllers `CarteController` et `LieuController` (logique extraite des anciennes pages contenant du SQL inline).
* Nouveau Model `LieuModel` (concentre tout le SQL des lieux).
* Vue d'erreur `Views/erreurs/404.php`.
* Sous-dossiers de `Views/` réorganisés en français : `accueil/`, `connexion/`, `inscription/`, `profil/`, `carte/`, `lieu/`, `pays/`, `avis/`, `erreurs/`.
* Partials enrichis : `entete.php`, `navigation.php`, `pied.php`, `messages.php`.
* `sql/` réorganisé en `schema/`, `seeds/`, `migrations/` (préfixe daté).
* Nouveau dossier `storage/` (logs, uploads).
* Documentation `docs/architecture.md`.

## [0.0.4] - 2026-04-04 - Structure v2
### Ajout
*
*

### Références Git (période fin mars — avril)
* [`462cce2`](https://github.com/Horion77/Projet-ABS/commit/462cce2) — carte / mises à jour fin avril.
* [`133e3b3`](https://github.com/Horion77/Projet-ABS/commit/133e3b3), [`74d264a`](https://github.com/Horion77/Projet-ABS/commit/74d264a), [`0644eef`](https://github.com/Horion77/Projet-ABS/commit/0644eef), [`d413ac9`](https://github.com/Horion77/Projet-ABS/commit/d413ac9) — lieu / carte / BDD.
* [`cee5592`](https://github.com/Horion77/Projet-ABS/commit/cee5592), [`7295e98`](https://github.com/Horion77/Projet-ABS/commit/7295e98), [`82f596b`](https://github.com/Horion77/Projet-ABS/commit/82f596b), [`944f0f4`](https://github.com/Horion77/Projet-ABS/commit/944f0f4), [`9be3e4d`](https://github.com/Horion77/Projet-ABS/commit/9be3e4d) — versioning, Mapbox, accès BDD, évolutions code.

## [0.0.3] - 2026-03-21 - Structure v2
### Ajout
* Fichiers actions (`avis_action.php`, `inscription_action.php`, `login_action.php`, `logout.php`)
* Pages (`map.php`, `pays.php`, `place.php`)
* Feuilles de style (`auth.css`, `home.css`, `map.css`, `reviews.css`, `style.css`)
* Scripts JS (`map.js`, `validation.js`)
* Schema SQL complet (`database.sql`)

### Références Git
* [`43e2d08`](https://github.com/Horion77/Projet-ABS/commit/43e2d08), [`b4eaf0c`](https://github.com/Horion77/Projet-ABS/commit/b4eaf0c) — structure v2, changelog + README.

## [0.0.2] - 2026-03-21 - Mise en place structure
### Ajout
* Arborescence initiale du projet (pages, includes, actions, index)
* `.gitignore`

### Modification
* **README.md** : version complete (presentation, fonctionnalites, BDD, installation, strategie Git)

### Références Git
* [`6b4a245`](https://github.com/Horion77/Projet-ABS/commit/6b4a245), [`1b0ae40`](https://github.com/Horion77/Projet-ABS/commit/1b0ae40), [`b248832`](https://github.com/Horion77/Projet-ABS/commit/b248832) — structure, README.

## [0.0.1] - 2026-02-16 - Initial commit
### Ajout
* Creation du depot et README initial

### Référence Git
* [`138091b`](https://github.com/Horion77/Projet-ABS/commit/138091b)

---

# Annexe — historique Git complet (liens [Horion77/Projet-ABS](https://github.com/Horion77/Projet-ABS))

Chronologie **du plus ancien au plus récent** ; message = sujet du commit tel qu’enregistré dans Git.

| Date | Message | Commit |
|------|---------|--------|
| 2026-02-16 | Initial commit | [`138091b`](https://github.com/Horion77/Projet-ABS/commit/138091b) |
| 2026-03-21 | modif-readme | [`b248832`](https://github.com/Horion77/Projet-ABS/commit/b248832) |
| 2026-03-21 | modif-readme | [`1b0ae40`](https://github.com/Horion77/Projet-ABS/commit/1b0ae40) |
| 2026-03-21 | mise-en-place structure | [`6b4a245`](https://github.com/Horion77/Projet-ABS/commit/6b4a245) |
| 2026-03-21 | structure-v2 | [`43e2d08`](https://github.com/Horion77/Projet-ABS/commit/43e2d08) |
| 2026-03-21 | ajout-changelog+correction-readme | [`b4eaf0c`](https://github.com/Horion77/Projet-ABS/commit/b4eaf0c) |
| 2026-04-26 | coding-change-04-04-2026 | [`9be3e4d`](https://github.com/Horion77/Projet-ABS/commit/9be3e4d) |
| 2026-04-26 | change-access-database | [`944f0f4`](https://github.com/Horion77/Projet-ABS/commit/944f0f4) |
| 2026-04-26 | ajout-code-map | [`82f596b`](https://github.com/Horion77/Projet-ABS/commit/82f596b) |
| 2026-04-26 | ajout-token-Mapbox | [`7295e98`](https://github.com/Horion77/Projet-ABS/commit/7295e98) |
| 2026-04-26 | ajout-versionning | [`cee5592`](https://github.com/Horion77/Projet-ABS/commit/cee5592) |
| 2026-04-26 | modif-map | [`d413ac9`](https://github.com/Horion77/Projet-ABS/commit/d413ac9) |
| 2026-04-26 | modif-db-ajout_a_faire | [`0644eef`](https://github.com/Horion77/Projet-ABS/commit/0644eef) |
| 2026-04-26 | modification-map | [`74d264a`](https://github.com/Horion77/Projet-ABS/commit/74d264a) |
| 2026-04-26 | modification-place-ville-pays | [`133e3b3`](https://github.com/Horion77/Projet-ABS/commit/133e3b3) |
| 2026-04-30 | change-adding-focntion-update-map | [`462cce2`](https://github.com/Horion77/Projet-ABS/commit/462cce2) |
| 2026-05-03 | mise-en-place-fonciton-avis-pays | [`921282f`](https://github.com/Horion77/Projet-ABS/commit/921282f) |
| 2026-05-03 | refonte-struture-visible-model-MVC | [`bcaf2c0`](https://github.com/Horion77/Projet-ABS/commit/bcaf2c0) |
| 2026-05-03 | commentaire-simple-en-place | [`f97310b`](https://github.com/Horion77/Projet-ABS/commit/f97310b) |
| 2026-05-04 | modification-htaccess | [`73bf584`](https://github.com/Horion77/Projet-ABS/commit/73bf584) |
| 2026-05-04 | modification-router-access-site | [`e726980`](https://github.com/Horion77/Projet-ABS/commit/e726980) |
| 2026-05-05 | change-route-URL | [`f9249fd`](https://github.com/Horion77/Projet-ABS/commit/f9249fd) |
| 2026-05-05 | Test-404 | [`859dc14`](https://github.com/Horion77/Projet-ABS/commit/859dc14) |
| 2026-05-06 | Version 1 Sara | [`ab0577d`](https://github.com/Horion77/Projet-ABS/commit/ab0577d) |
| 2026-05-06 | adding-css-pages | [`bfe721e`](https://github.com/Horion77/Projet-ABS/commit/bfe721e) |
| 2026-05-06 | change-lien | [`f74c9ce`](https://github.com/Horion77/Projet-ABS/commit/f74c9ce) |
| 2026-05-06 | ajout-changelog | [`6ac8769`](https://github.com/Horion77/Projet-ABS/commit/6ac8769) |
| 2026-05-06 | feat(carte): globe 3D Mapbox, terrain, style switcher, pins typés par catégorie | [`766c7ec`](https://github.com/Horion77/Projet-ABS/commit/766c7ec) |
| 2026-05-06 | feat(carte): port feature/alan-map → MVC — globe 3D, pins typés, nav panel | [`5158423`](https://github.com/Horion77/Projet-ABS/commit/5158423) |
| 2026-05-06 | Merge origin/dev — conserve fichiers Basma (actions, pages, validation) | [`8a259ae`](https://github.com/Horion77/Projet-ABS/commit/8a259ae) |
| 2026-05-06 | chore(integration): MVC-only tree, team docs, gitattributes | [`87e8b4b`](https://github.com/Horion77/Projet-ABS/commit/87e8b4b) |
| 2026-05-07 | change-projet-intégration | [`f1b9c60`](https://github.com/Horion77/Projet-ABS/commit/f1b9c60) |
| 2026-05-11 | changement-env-folder | [`5e2efc4`](https://github.com/Horion77/Projet-ABS/commit/5e2efc4) |
| 2026-05-11 | change-lien-env-projet | [`808c0ef`](https://github.com/Horion77/Projet-ABS/commit/808c0ef) |
| 2026-05-13 | last-change | [`d1f7b7f`](https://github.com/Horion77/Projet-ABS/commit/d1f7b7f) |
