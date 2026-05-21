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
- Carte interactive Mapbox avec marqueurs cliquables
- Fiche détaillée par lieu (description, image, pays, note moyenne)
- Système d'avis : note 1–5 étoiles + commentaire pour utilisateurs connectés
- Page pays avec liste des lieux et notes moyennes
- Page globale de tous les avis avec pagination et filtres (note, pays)
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
│   │   ├── LieuController.php
│   │   ├── PaysController.php
│   │   ├── AvisController.php          # POST création
│   │   └── AvisListeController.php     # GET liste paginée
│   ├── Models/
│   │   ├── UtilisateurModel.php
│   │   ├── PaysModel.php
│   │   ├── AvisModel.php
│   │   ├── LieuModel.php
│   │   └── AccueilModel.php
│   └── Views/
│       ├── layouts/principal.php
│       ├── partials/   (entete, navigation, pied, messages)
│       ├── accueil/    (index)
│       ├── connexion/  (index)
│       ├── inscription/(index)
│       ├── profil/     (index)
│       ├── carte/      (index)
│       ├── lieu/       (afficher)
│       ├── pays/       (afficher)
│       ├── avis/       (index)
│       └── erreurs/    (404)
├── sql/
│   ├── schema/database.sql
│   ├── seeds/seed_demo.sql
│   └── migrations/
│       ├── 2026_04_30_add_image_url_lieu.sql
│       └── 2026_04_30_add_titre_avis.sql
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
| GET     | `/lieu?id=…`        | `LieuController::afficher`                     |
| GET     | `/pays?id=…`        | `PaysController::afficher`                     |
| GET     | `/avis`             | `AvisListeController::index`                   |
| POST    | `/avis`             | `AvisController::traiterSoumission`            |

---

## Base de données

Voir [`sql/schema/database.sql`](sql/schema/database.sql) pour le schéma complet.
Données de démonstration dans [`sql/seeds/seed_demo.sql`](sql/seeds/seed_demo.sql).

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

   - Ouvrir phpMyAdmin (`http://localhost/phpmyadmin`)
   - Créer une base nommée `abs_db`
   - Importer `sql/schema/database.sql`
   - (optionnel) Importer `sql/seeds/seed_demo.sql` pour les données de démo

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

   Accéder à `http://localhost/` (ou l'URL de votre vhost) dans le navigateur.

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
