# Projet ABS — Explorateur d'Avis de Lieux

Application web dynamique dédiée à la découverte de lieux et au partage d'avis.  
Les utilisateurs peuvent explorer une carte interactive du monde, noter des lieux et publier des avis.

> Projet réalisé dans le cadre du cours de Programmation Web — HTML / CSS / PHP / MySQL, sans framework.

---

## Membres du groupe et pseudos GitHub

| Nom complet      | Pseudo GitHub                         | Branche de travail        |
|------------------|---------------------------------------|---------------------------|
| Alan André       | [@Horion77](https://github.com/Horion77) | `feature/alan-map`     |
| Basma Rhilane    | [@PseudoACompléter](#)                | `feature/basma-auth`      |
| Sara El Abassi   | [@PseudoACompléter](#)                | `feature/sara-reviews`    |

> ⚠️ Remplacer `@PseudoACompléter` par les vrais pseudos GitHub de Basma et Sara.

---

## Configurations requises

### Logiciels à installer

| Composant | Version minimale | Rôle |
|-----------|-----------------|------|
| **MAMP / XAMPP / WAMP** | — | Serveur local Apache + PHP + MySQL |
| **PHP** | 8.1+ | Langage serveur (PDO, `password_hash`, `str_contains`…) |
| **MySQL** | 8.0+ | Base de données (window functions utilisées) |
| **Apache** | — | Module `mod_rewrite` obligatoirement activé |
| **Node.js + npm** | 18+ | Compilation des feuilles de style Tailwind CSS |
| **Git** | — | Clonage et versionnement |

### Ports selon l'environnement

| Environnement | Port Apache | Port MySQL |
|---------------|------------|------------|
| MAMP (macOS)  | `8888`     | `8889`     |
| XAMPP / WAMP  | `80`       | `3306`     |

### Fichiers de configuration à créer (non versionnés)

| Fichier à créer            | Modèle fourni                  | Contenu |
|----------------------------|--------------------------------|---------|
| `app/Config/bdd.php`       | `app/Config/bdd.exemple.php`   | Identifiants MySQL locaux |
| `app/Config/mapbox.php`    | `app/Config/mapbox.exemple.php`| Jeton public Mapbox (optionnel) |

---

## Procédure d'exécution étape par étape

### Étape 1 — Cloner le dépôt

```bash
git clone https://github.com/Horion77/Projet-ABS.git
cd Projet-ABS
```

---

### Étape 2 — Configurer le serveur web

Pointer le **DocumentRoot** d'Apache sur le sous-dossier `public/` du projet (**pas** la racine) :

**MAMP (macOS)**  
`Préférences → Serveur Web → Document Root` → sélectionner `.../Projet-ABS/public`

**XAMPP**  
Éditer `httpd.conf` :
```
DocumentRoot "C:/xampp/htdocs/Projet-ABS/public"
<Directory "C:/xampp/htdocs/Projet-ABS/public">
```

**WAMP**  
Modifier l'alias dans `httpd-vhosts.conf` pour pointer vers `.../Projet-ABS/public`.

> Un fichier `.htaccess` à la racine du projet redirige automatiquement vers `public/`
> si le DocumentRoot n'est pas configuré — mais configurer le DocumentRoot est recommandé.

---

### Étape 3 — Créer la base de données

**Via la ligne de commande** (recommandé — garantit l'encodage UTF-8) :

```bash
# Crée la base abs_db avec toutes les tables et vues
mysql --default-character-set=utf8mb4 -u root -p < sql/schema/database.sql

# Insère les données de démonstration
mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_avis_complet.sql

# Insère le grand jeu de données (90+ users, 270+ avis)
mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_monde_vivant.sql
```

**Via phpMyAdmin** (alternative) :  
Importer les trois fichiers dans l'ordre ci-dessus. L'encodage UTF-8 est géré automatiquement.

> ⚠️ L'ordre est important : `database.sql` en premier (crée la base), puis les seeds.

---

### Étape 4 — Configurer la connexion à la base de données

```bash
cp app/Config/bdd.exemple.php app/Config/bdd.php
```

Ouvrir `app/Config/bdd.php` et adapter les valeurs selon l'environnement :

```php
$host   = '127.0.0.1';
$dbname = 'abs_db';
$user   = 'root';
$pass   = 'root';          // mot de passe MySQL local
```

Adapter également le port dans la ligne `$dsn` :

```php
// MAMP (port MySQL 8889)
$dsn = "mysql:host={$host};port=8889;charset={$charset};dbname={$dbname}";

// XAMPP / WAMP (port MySQL standard 3306)
$dsn = "mysql:host={$host};charset={$charset};dbname={$dbname}";
```

---

### Étape 5 — Configurer la carte Mapbox (optionnel)

```bash
cp app/Config/mapbox.exemple.php app/Config/mapbox.php
```

Obtenir un jeton public (`pk.…`) sur [account.mapbox.com](https://account.mapbox.com/access-tokens/) et le renseigner dans `app/Config/mapbox.php` :

```php
return 'pk.VOTRE_JETON_PUBLIC_ICI';
```

> Sans ce fichier, un jeton de secours intégré au code est utilisé pour la démonstration locale.  
> Pour la production, préférer la variable d'environnement `MAPBOX_TOKEN`.

---

### Étape 6 — Compiler les feuilles de style

Installer les dépendances Node.js puis lancer le build Tailwind CSS :

```bash
npm install
npm run build:css
```

> Ne pas modifier les fichiers dans `public/assets/css/` directement : ils sont écrasés à chaque build.

Pour recompiler automatiquement à chaque modification CSS pendant le développement :

```bash
npm run watch:css
```

---

### Étape 7 — Lancer l'application

**Avec MAMP / XAMPP / WAMP** (Apache démarré) :

| Environnement | URL d'accès |
|---------------|-------------|
| MAMP (port 8888) | `http://localhost:8888/` |
| XAMPP / WAMP   | `http://localhost/` |

**Sans serveur Apache** (serveur PHP intégré) :

```bash
npm run start
```

Puis ouvrir **`http://localhost:8000/`** dans le navigateur.

---

### Résumé des commandes (installation complète)

```bash
# 1. Cloner
git clone https://github.com/Horion77/Projet-ABS.git && cd Projet-ABS

# 2. Base de données
mysql --default-character-set=utf8mb4 -u root -p < sql/schema/database.sql
mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_avis_complet.sql
mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_monde_vivant.sql

# 3. Configuration
cp app/Config/bdd.exemple.php app/Config/bdd.php
cp app/Config/mapbox.exemple.php app/Config/mapbox.php
# → éditer bdd.php avec vos identifiants MySQL

# 4. CSS
npm install && npm run build:css

# 5. Lancer (serveur PHP intégré)
npm run start
```

---

## Comptes de démonstration

Une fois les seeds importés, les comptes suivants sont disponibles :

| Rôle | E-mail | Mot de passe |
|------|--------|-------------|
| Administrateur | `admin@abs.fr` | `Password1` |
| Utilisateur | `user@abs.fr` | `Password1` |

> Tous les comptes générés par les seeds ont le mot de passe `Password1`.

---

## Technologies utilisées

| Couche | Technologie |
|--------|-------------|
| Frontend | HTML5, CSS3, JavaScript (vanilla) |
| Style | Tailwind CSS v4 (build local) |
| Backend | PHP 8.1+ (architecture MVC maison, sans framework) |
| Base de données | MySQL 8.0+ (PDO, window functions) |
| Carte interactive | Mapbox GL JS v3 |
| Versionnement | Git / GitHub |
