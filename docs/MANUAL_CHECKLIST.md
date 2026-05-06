# Checklist de vérification manuelle

À exécuter avec le **DocumentRoot** pointant sur `public/` (voir `README.md`). Adapter la base URL si le projet est dans un sous-dossier (MAMP).

## Parcours minimum

| # | Action | URL / méthode | Résultat attendu |
|---|--------|---------------|------------------|
| 1 | Accueil | `GET /` | Page d’accueil sans erreur PHP |
| 2 | Connexion (formulaire) | `GET /connexion` | Formulaire affiché |
| 3 | Inscription (formulaire) | `GET /inscription` | Formulaire affiché |
| 4 | Carte | `GET /carte` | Carte Mapbox chargée (clé configurée) |
| 5 | Liste des avis | `GET /avis` | Liste + pagination ; filtres note / pays |
| 6 | Fiche pays | `GET /pays?id=<id_pays>` | Détail pays + lieux |
| 7 | Fiche lieu | `GET /lieu?id=<id_lieu>` | Détail lieu + avis + formulaire si connecté |
| 8 | Soumission avis | `POST /avis` (connecté) | Redirection lieu + message succès |
| 9 | Profil | `GET /profil` (connecté) | Liste des avis utilisateur |
| 10 | 404 | URL inexistante | Vue `erreurs/404` |

## Après intégration (cette branche)

- Confirmer qu’**aucun** lien ne pointe vers d’anciens chemins `pages/*.php` ou `actions/*.php`.
- Confirmer que les scripts charge bien `public/assets/js/validation.js` (onglet Réseau du navigateur).
