**Change Log**

Change log du projet A.B.S.

---

# Versions

## [0.1.2] - 2026-05-06 - Branche d’intégration équipe (MVC unique)
### Intégration
* Branche de travail : `integrate/all-team-mvc` (clone d’intégration sous `Projet-ensemble/Projet-ABS-integration`).
* Documentation : `docs/INTEGRATION_MATRIX.md` (cartographie ancien code → MVC), `docs/TEAM_CONTRIBUTIONS.md` (qui a livré quoi).
* Normalisation Git : `.gitattributes` (EOL LF) ; `core.filemode=false` recommandé sur le clone pour éviter les diffs de permissions.

### Nettoyage
* Suppression des reliquats legacy `actions/avis_action.php`, `pages/avis.php`, `pages/pays.php` et du doublon `assets/js/validation.js` (chemins et schéma SQL non alignés avec le MVC actuel ; la logique équivalente est dans `AvisController`, `AvisListeController`, `PaysController` et `public/assets/js/validation.js`).

## [0.1.1] - 2026-05-06 - Corrections MAMP + compatibilité BDD
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

## [0.0.3] - 2026-03-21 - Structure v2
### Ajout
* Fichiers actions (`avis_action.php`, `inscription_action.php`, `login_action.php`, `logout.php`)
* Pages (`map.php`, `pays.php`, `place.php`)
* Feuilles de style (`auth.css`, `home.css`, `map.css`, `reviews.css`, `style.css`)
* Scripts JS (`map.js`, `validation.js`)
* Schema SQL complet (`database.sql`)

## [0.0.2] - 2026-03-21 - Mise en place structure
### Ajout
* Arborescence initiale du projet (pages, includes, actions, index)
* `.gitignore`

### Modification
* **README.md** : version complete (presentation, fonctionnalites, BDD, installation, strategie Git)

## [0.0.1] - 2026-02-16 - Initial commit
### Ajout
* Creation du depot et README initial
