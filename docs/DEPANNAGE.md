# Dépannage rapide — ABS

## « Ce site est inaccessible » / ERR_CONNECTION_REFUSED

Le serveur web n’est pas démarré.

1. Ouvrir **MAMP** → **Démarrer les serveurs**
2. Tester : `http://localhost:8888/Projet-ABS/public/`
3. Ou, sans MAMP : `npm run start` puis `http://localhost:8000/`

---

## Erreur MySQL / PDO

1. MySQL doit tourner (MAMP : voyant vert)
2. Créer la base **abs_db** dans phpMyAdmin
3. Importer `sql/schema/database.sql` (+ `sql/seeds/seed_demo.sql` optionnel)
4. Vérifier `app/Config/bdd.php` :
   - MAMP : port **8889**, user **root**, pass **root**
   - XAMPP : port **3306**, pass souvent vide

---

## Page 404 sur toutes les URLs

- Vérifier que `mod_rewrite` est activé (Apache)
- `public/.htaccess` : `RewriteBase` doit correspondre à votre URL (`/Projet-ABS/public/`)

---

## Pas de style (thème cosmic)

```bash
npm install
npm run build:css
```

Puis recharger avec **Cmd+Shift+R**.

---

## Fichiers de config locaux (non versionnés)

| Fichier | Commande |
|---------|----------|
| `app/Config/bdd.php` | `cp app/Config/bdd.exemple.php app/Config/bdd.php` |
| `app/Config/mapbox.php` | `cp app/Config/mapbox.exemple.php app/Config/mapbox.php` |
