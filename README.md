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
| Style | CSS3 natif (aucun framework) |
| Serveur | PHP 8+ (natif, aucun framework) |
| Base de données | MySQL (PDO) |
| Carte interactive | Librairie JavaScript de cartographie (type Leaflet.js) |
| Versionnement | Git / GitHub |

---

## Fonctionnalités

- Page d'accueil attrayante avec présentation du site et aperçu des lieux populaires
- Inscription et connexion sécurisées (hashage `password_hash`, sessions PHP)
- Carte interactive du monde avec marqueurs cliquables pour chaque lieu
- Fiche détaillée par lieu (description, image, pays, note moyenne)
- Système d'avis : note sur 5 étoiles + commentaire, accessible aux utilisateurs connectés
- Page d'avis par pays avec liste des lieux et notes moyennes
- Page globale de tous les avis avec pagination et filtres
- Profil utilisateur affichant ses propres avis
- Validation des données côté serveur (PHP) et côté client (JavaScript)
- Navigation cohérente sur l'ensemble du site

---

## Structure du projet

```
Projet-ABS/
├── index.php                  # Page d'accueil
├── config/
│   └── database.php           # Connexion BDD (PDO)
├── includes/
│   ├── header.php             # En-tête commun (navbar)
│   ├── footer.php             # Pied de page commun
│   └── functions.php          # Fonctions utilitaires partagées
├── pages/
│   ├── login.php              # Page de connexion
│   ├── inscription.php           # Page d'inscription
│   ├── map.php                # Page carte interactive
│   ├── place.php              # Détail d'un lieu + avis
│   ├── pays.php            # Page pays + liste de lieux
│   ├── reviews.php         # Tous les avis (paginés + filtres)
│   ├── avis.php            # Redirige vers reviews.php
│   ├── country.php         # Fiche pays + lieux du pays
│   └── profil.php            # Profil utilisateur
├── actions/
│   ├── login_action.php       # Traitement formulaire connexion
│   ├── inscription_action.php    # Traitement formulaire inscription
│   ├── review_action.php    # Traitement formulaire avis (plan Sara)
│   ├── avis_action.php      # Alias → même traitement que review_action
│   └── logout.php             # Déconnexion
├── assets/
│   ├── css/
│   │   ├── style.css          # Styles globaux, variables, navbar, footer
│   │   ├── home.css           # Styles page d'accueil
│   │   ├── auth.css           # Styles login / register
│   │   ├── map.css            # Styles carte interactive
│   │   └── reviews.css        # Styles pages d'avis
│   ├── js/
│   │   ├── map.js             # Logique carte interactive
│   │   └── validation.js      # Validation front-end des formulaires
│   └── images/
└── sql/
    └── database.sql           # Schéma complet de la BDD
```

---

## Base de données

Quatre tables principales :

- **users** — comptes utilisateurs (username, email, mot de passe hashé)
- **pays** — pays référencés (nom, code ISO, coordonnées)
- **places** — lieux à découvrir (nom, description, coordonnées, image, lié à un pays)
- **avis** — avis des utilisateurs (note 1-5, titre, commentaire, lié à un utilisateur et un lieu)

### Schéma SQL

```sql
CREATE DATABASE IF NOT EXISTS projet_abs;
USE projet_abs;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(3) NOT NULL,
    lat DECIMAL(10,7),
    lng DECIMAL(10,7)
);

CREATE TABLE places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    pays_id INT NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    image_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pays_id) REFERENCES pays(id)
);

CREATE TABLE avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    place_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(150),
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (place_id) REFERENCES places(id)
);
```

---

## Installation - pour le lancement A.B.S

### Prérequis

- Serveur local : MAMP, WAMP ou XAMPP (Apache + PHP + MySQL)
- Git

### Étapes

1. **Cloner le dépôt**

   ```bash
   git clone <url-du-depot> Projet-ABS
   ```

2. **Placer le projet dans le dossier du serveur web**

   - MAMP : `/Applications/MAMP/htdocs/Projet-ABS`
   - WAMP : `C:\wamp64\www\Projet-ABS`
   - XAMPP : `C:\xampp\htdocs\Projet-ABS`

3. **Créer la base de données**

   - Ouvrir phpMyAdmin (`http://localhost/phpmyadmin`)
   - Créer une base de données nommée `projet_abs`
   - Importer le fichier `sql/database.sql`

4. **Configurer la connexion**

   Copier `config/database.example.php` en `config/database.php` et adapter les identifiants :

   ```php
   <?php
   $host = 'localhost';
   $dbname = 'projet_abs';
   $username = '******';
   $password = '******';
   ```

5. **Carte Mapbox (recommandé)**

   - Copier [`config/mapbox.example.php`](config/mapbox.example.php) en `config/mapbox.php` et y mettre votre [jeton d’accès public Mapbox](https://account.mapbox.com/access-tokens/), **ou** définir la variable d’environnement `MAPBOX_TOKEN`.
   - Sans `mapbox.php` ni variable d’environnement, le site utilise un jeton de secours intégré au code (pratique pour une démo locale uniquement).

   Bases déjà créées avant l’ajout de la colonne `lieu.image_url` : exécuter une fois [`sql/migration_add_image_url_lieu.sql`](sql/migration_add_image_url_lieu.sql).

   Bases créées avant l’ajout du **titre** sur les avis : exécuter [`sql/migration_add_titre_avis.sql`](sql/migration_add_titre_avis.sql).

6. **Lancer le site**

   Accéder à `http://localhost/Projet-ABS/` dans le navigateur.

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
