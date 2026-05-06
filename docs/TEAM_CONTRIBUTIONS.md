# Contributions par membre (référence équipe)

Synthèse pour la branche d’intégration **`integrate/all-team-mvc`**, basée sur l’historique Git du dépôt `Projet-ABS` (commits et branches fusionnées).

## Basma

- Mise en place et consolidation de la **refonte MVC** : `app/Core/*`, `Routeur`, `Controleur`, `Modele`, `Session`, conventions des vues sous `app/Views/`.
- Organisation **SQL** : `sql/schema/`, `sql/migrations/`, `sql/seeds/`.
- Documentation d’architecture : `docs/architecture.md`.
- Évolutions UX / formulaires et helpers d’URL (`url()`, `asset()`) pour environnements type MAMP sous sous-dossier (cf. `CHANGELOG.md`).

## Alan

- Fonctionnalité **carte** avancée : globe 3D Mapbox, terrain, changement de style, **pins typés** par catégorie, panneau de navigation (commits `feat(carte)` / port MVC vers `CarteController` et vues associées).
- Intégration des assets carte dans `public/assets/` (CSS/JS carte).

## Sara

- Travail sur les **avis** : liste paginée, filtrage, expérience « tous les avis » (branche `feature/sara-reviews`, commit *Version 1 Sara*).
- Côté MVC, la logique équivalente est portée dans `AvisListeController` + `AvisModel` + `app/Views/avis/`, et la soumission dans `AvisController` avec compatibilité des noms de champs legacy (`place_id`, `rating`, etc.).

## Résolution des conflits / dette technique

- Un merge intermédiaire avait réintroduit des fichiers legacy sous `pages/` et `actions/` **incompatibles** avec le schéma MVC actuel ; ils ont été retirés de l’arbre d’intégration pour éviter les erreurs et les doubles sources de vérité.
- Normalisation Git : `.gitattributes` (EOL LF) et `core.filemode=false` sur le clone d’intégration pour limiter les diffs bruit (permissions).

Pour le détail des correctifs versionnés, voir `CHANGELOG.md` (sections **0.1.1** et suivantes).
