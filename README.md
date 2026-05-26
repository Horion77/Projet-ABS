# Projet ABS — Explorateur d'Avis de Lieux

Application web dynamique inspirée de Letterboxd, dédiée aux **lieux et pays**.
Les utilisateurs peuvent explorer une carte interactive du monde, découvrir des lieux, et partager leurs avis.

Projet réalisé dans le cadre du cours de Programmation Web (HTML / CSS / PHP / MySQL — aucun framework).

---

## Équipe

| Membre | Initiale |
|--------|----------|
| Alan   | A        |
| Basma  | B        |
| Sara   | S        |

---

## Technologies

| Couche | Technologie |
|--------|-------------|
| Structure | HTML5 |
| Style | CSS3 + [Tailwind CSS v4](https://tailwindcss.com/) (build local, classes via `@apply` sur le HTML existant) |
| Serveur | PHP 8.1+ (natif, aucun framework) |
| Base de données | MySQL (PDO) |
| Carte interactive | Mapbox GL JS |
| Versionnement | Git / GitHub |

---

## Architecture MVC

L'application suit une architecture **Modèle / Vue / Contrôleur** stricte avec un **front controller** unique.
Toutes les requêtes passent par `public/index.php`, qui appelle le **Routeur**, qui dispatche vers le bon **Contrôleur**, lequel sollicite ses **Modèles** puis rend une **Vue**.

```text
Navigateur → public/index.php → Routeur → Controller → Model(s) → BDD
                                            ↓
                                          Vue → HTML → Navigateur
```

Convention de nommage : dossiers structurels en anglais, fichiers PHP et URLs publiques en français.
Voir [docs/architecture.md](docs/architecture.md) pour le détail.

Fusion de plusieurs branches / contextes équipe : voir [docs/INTEGRATION_MATRIX.md](docs/INTEGRATION_MATRIX.md), [docs/TEAM_CONTRIBUTIONS.md](docs/TEAM_CONTRIBUTIONS.md) et [docs/MANUAL_CHECKLIST.md](docs/MANUAL_CHECKLIST.md).

---

## Fonctionnalités

- Page d'accueil avec présentation du site et aperçu des lieux populaires
- Inscription et connexion sécurisées (`password_hash`, sessions PHP)
- Carte interactive Mapbox (globe 3D) avec :
  - regroupement automatique des lieux proches (clustering natif Mapbox)
  - marqueurs « pins » colorés selon la note (style TripAdvisor) avec nom + catégorie
  - détection du pays au clic sur ses frontières réelles → panneau latéral (note moyenne, derniers avis)
  - panneau de navigation repliable (continents, pays populaires, filtre par type de lieu), en tiroir sur mobile
  - ajout d'un lieu directement depuis la carte (clic + géocodage inverse)
- Fiche détaillée par lieu (description, image, pays, note moyenne, avis, commentaires, likes)
- Système d'avis : note 1–5 étoiles + titre + commentaire pour utilisateurs connectés
- Likes sur les avis et commentaires (réponses imbriquées)
- Page **Découvrir** : formulaire de critères (type de lieu, note minimale, continent, pays, ville) → liste de lieux recommandés triés par note
- Page pays avec liste des lieux et notes moyennes
- Profil utilisateur affichant ses propres avis
- Validation côté serveur (PHP) et côté client (JavaScript)

---

## Structure du projet

```
Projet-ABS/
├── README.md
├── CHANGELOG.md
├── LICENSE
├── .gitignore
├── .htaccess                      # filet de sécurité → public/
├── public/                        # ⬅ DocumentRoot Apache/MAMP/XAMPP
│   ├── index.php                  # Front controller unique
│   ├── .htaccess                  # mod_rewrite : tout vers index.php
│   └── assets/
│       ├── css/  (style, home, auth, map, place, reviews)
│       ├── js/   (map.js, validation.js)
│       └── images/
├── app/
│   ├── bootstrap.php              # autoload PSR-4 + helpers + session
│   ├── Config/
│   │   ├── application.php
│   │   ├── bdd.php                # PDO (gitignored)
│   │   ├── bdd.exemple.php
│   │   ├── mapbox.exemple.php
│   │   └── routes.php             # déclaration des routes
│   ├── Core/                      # noyau du framework maison
│   │   ├── Routeur.php
│   │   ├── Controleur.php         # base abstraite
│   │   ├── Modele.php             # base abstraite
│   │   ├── BaseDeDonnees.php
│   │   ├── Vue.php
│   │   ├── Requete.php
│   │   ├── Reponse.php
│   │   ├── Session.php
│   │   └── Aides.php              # fonctions helpers globales
│   ├── Controllers/
│   │   ├── AccueilController.php
│   │   ├── ConnexionController.php
│   │   ├── InscriptionController.php
│   │   ├── ProfilController.php
│   │   ├── CarteController.php
│   │   ├── DecouvrirController.php     # GET page « Découvrir » (recherche de lieux)
│   │   ├── LieuController.php
│   │   ├── PaysController.php
│   │   ├── AvisController.php          # POST création
│   │   ├── AvisListeController.php     # GET liste paginée
│   │   ├── CommentaireController.php   # commentaires + likes commentaire
│   │   └── LikeController.php          # like d'un avis (AJAX)
│   ├── Models/
│   │   ├── UtilisateurModel.php
│   │   ├── PaysModel.php
│   │   ├── AvisModel.php
│   │   ├── LieuModel.php
│   │   ├── CategorieLieuModel.php
│   │   ├── CommentaireModel.php
│   │   ├── LikeModel.php
│   │   └── AccueilModel.php
│   └── Views/
│       ├── layouts/principal.php
│       ├── partials/   (entete, navigation, pied, messages)
│       ├── accueil/    (index)
│       ├── connexion/  (index)
│       ├── inscription/(index)
│       ├── profil/     (index)
│       ├── carte/      (index)
│       ├── decouvrir/  (index)
│       ├── lieu/       (afficher)
│       ├── pays/       (afficher)
│       ├── avis/       (index)
│       └── erreurs/    (404)
├── sql/
│   ├── schema/database.sql             # crée toute la base (schéma complet)
│   ├── seeds/
│   │   ├── seed_demo.sql
│   │   ├── seed_avis_complet.sql
│   │   ├── seed_monde_vivant.sql       # gros jeu de données (généré)
│   │   ├── _generate_monde_vivant.js   # générateur du seed ci-dessus
│   │   └── _fix_encoding.sql           # répare les accents corrompus à l'import
│   └── migrations/
│       ├── 2026_04_30_add_image_url_lieu.sql
│       ├── 2026_04_30_add_titre_avis.sql
│       ├── 2026_05_06_add_type_icon_lieu.sql
│       └── 2026_05_20_add_like_avis.sql
├── storage/                       # logs, uploads (hors doc-root)
└── docs/
    └── architecture.md
```

---

## URLs publiques

| Méthode | Chemin              | Contrôleur::action                            |
|---------|---------------------|------------------------------------------------|
| GET     | `/`                 | `AccueilController::index`                     |
| GET     | `/connexion`        | `ConnexionController::afficher`                |
| POST    | `/connexion`        | `ConnexionController::traiterConnexion`        |
| POST    | `/deconnexion`      | `ConnexionController::deconnecter`             |
| GET     | `/inscription`      | `InscriptionController::afficher`              |
| POST    | `/inscription`      | `InscriptionController::traiterInscription`    |
| GET     | `/profil`           | `ProfilController::afficher`                   |
| GET     | `/carte`            | `CarteController::index`                       |
| GET     | `/decouvrir`        | `DecouvrirController::index`                   |
| GET     | `/lieu?id=…`        | `LieuController::afficher`                     |
| POST    | `/lieu/creer`       | `LieuController::creer`                         |
| GET     | `/pays?id=…`        | `PaysController::afficher`                     |
| GET     | `/avis`             | `AvisListeController::index`                   |
| POST    | `/avis`             | `AvisController::traiterSoumission`            |
| POST    | `/avis/liker`       | `LikeController::likerAvis`                     |
| POST    | `/commentaire`      | `CommentaireController::creer`                 |
| POST    | `/commentaire/liker`| `CommentaireController::liker`                 |

---

## Base de données

Le schéma complet (toutes les tables, y compris `like_avis` et `like_commentaire`) est dans
[`sql/schema/database.sql`](sql/schema/database.sql) — ce fichier crée la base de zéro
(`DROP` + `CREATE` + toutes les tables et vues).

Jeux de données :

| Fichier | Contenu |
|---------|---------|
| `sql/seeds/seed_demo.sql` | petit jeu de démonstration |
| `sql/seeds/seed_avis_complet.sql` | utilisateurs + lieux + ~55 avis cohérents |
| `sql/seeds/seed_monde_vivant.sql` | gros jeu : ~90 utilisateurs, 29 pays (dont îles), 90+ lieux, 270+ avis, commentaires, likes |

> Le fichier `seed_monde_vivant.sql` est **généré** par `sql/seeds/_generate_monde_vivant.js`
> (lancer `node sql/seeds/_generate_monde_vivant.js` pour le régénérer).

> ⚠️ **Encodage** : toujours importer les `.sql` en forçant l'UTF-8, sinon les accents
> sont corrompus sur Windows :
> ```bash
> mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/schema/database.sql
> ```
> En cas d'accents déjà cassés, `sql/seeds/_fix_encoding.sql` répare les données en place.

---

## Installation

### Prérequis

- Serveur local : MAMP, WAMP ou XAMPP (Apache + PHP 8.1+ + MySQL)
- Module Apache `mod_rewrite` activé
- Git

### Étapes

1. **Cloner le dépôt**

   ```bash
   git clone <url-du-depot> Projet-ABS
   ```

2. **Pointer le DocumentRoot du serveur web sur `Projet-ABS/public/`**

   - **MAMP** : *Préférences → Serveur Web → Document Root* → choisir `…/Projet-ABS/public`
   - **XAMPP** : éditer `httpd.conf`, remplacer `DocumentRoot "…/htdocs"` par `DocumentRoot "…/Projet-ABS/public"` (et idem pour `<Directory …>`)
   - **WAMP** : modifier l'alias dans `httpd-vhosts.conf`

   À défaut, un `.htaccess` racine sert de filet de sécurité et redirige toutes les requêtes vers `public/`, mais la configuration recommandée est de pointer directement le DocumentRoot.

3. **Créer la base de données**

   En ligne de commande (recommandé — force l'UTF-8) :

   ```bash
   mysql --default-character-set=utf8mb4 -u root -p < sql/schema/database.sql
   mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_avis_complet.sql
   mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_monde_vivant.sql
   ```

   `database.sql` crée la base `abs_db` et toutes ses tables ; les deux seeds remplissent
   les données. Via phpMyAdmin, importer ces fichiers dans le même ordre (l'import gère
   l'encodage UTF-8 automatiquement).

4. **Configurer la connexion BDD**

   ```bash
   cp app/Config/bdd.exemple.php app/Config/bdd.php
   ```

   Éditer `app/Config/bdd.php` avec vos identifiants MySQL.

5. **Carte Mapbox (recommandé)**

   ```bash
   cp app/Config/mapbox.exemple.php app/Config/mapbox.php
   ```

   Renseigner votre [jeton public Mapbox](https://account.mapbox.com/access-tokens/), ou définir la variable d'environnement `MAPBOX_TOKEN`. Sans configuration, un jeton de secours intégré au code est utilisé pour la démo locale.

6. **Compiler les feuilles de style (Tailwind)**

   Les sources CSS se trouvent dans `resources/css/`. Les fichiers servis par l'application sont générés dans `public/assets/css/`.

   ```bash
   npm install
   npm run build:css
   ```

   Pendant le travail sur le design :

   ```bash
   npm run watch:css
   ```

   Ne pas modifier directement les CSS compilés dans `public/assets/css/` : ils sont écrasés à chaque build.

7. **Lancer le site**

   **MAMP** (Apache démarré) :

   - `http://localhost:8888/Projet-ABS/public/` (port 8888 par défaut)
   - ou `http://localhost/Projet-ABS/public/` si Apache écoute sur le port 80

   **Sans MAMP** (serveur PHP intégré, depuis la racine du projet) :

   ```bash
   npm run start
   ```

   Puis ouvrir **`http://localhost:8000/`**

   Les fichiers `app/Config/bdd.php` et `app/Config/mapbox.php` doivent exister (copiés depuis les `.exemple.php`).

---

## Stratégie Git

| Branche | Usage |
|---------|-------|
| `main` | Code stable et intégré |
| `feature/alan-map` | Développement carte & lieu |
| `feature/basma-auth` | Développement authentification & accueil |
| `feature/sara-reviews` | Développement avis & pages dynamiques |

Chaque membre travaille sur sa branche, puis merge dans `main` après validation.

---

## Licence

MIT — voir [LICENSE](LICENSE).
