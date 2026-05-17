# Diagramme de classes — Projet ABS

Vue UML du modèle de données, basée sur le schéma SQL (`sql/schema/database.sql`).
Rendu directement sur GitHub grâce au support natif de Mermaid dans les fichiers Markdown.

```mermaid
classDiagram
    direction LR

    class Role {
        +int id_role
        +string libelle
    }

    class Utilisateur {
        +int id_utilisateur
        +string nom
        +string prenom
        +string email
        +string telephone
        +string password_hash
        +string avatar_url
        +string bio
        +datetime created_at
        +int id_role
    }

    class Pays {
        +int id_pays
        +string nom
        +string code_iso
        +string continent
    }

    class Ville {
        +int id_ville
        +string nom
        +int id_pays
    }

    class CategorieLieu {
        +int id_categorie
        +string libelle
    }

    class Lieu {
        +int id_lieu
        +enum type
        +string icon
        +string nom
        +string description
        +decimal latitude
        +decimal longitude
        +string adresse
        +string image_url
        +int id_categorie
        +int id_ville
    }

    class Avis {
        +int id_avis
        +tinyint note
        +string titre
        +string description
        +enum visibility
        +datetime created_at
        +int id_utilisateur
        +int id_pays
        +int id_ville
        +int id_lieu
    }

    class PhotoAvis {
        +int id_photo
        +string url
        +tinyint ordre
        +int id_avis
    }

    class Commentaire {
        +int id_commentaire
        +string texte
        +datetime created_at
        +int id_utilisateur
        +int id_avis
        +int id_parent
    }

    class LikeCommentaire {
        +int id_like
        +datetime created_at
        +int id_utilisateur
        +int id_commentaire
    }

    class Visite {
        +int id_visite
        +date date_visite
        +string note_perso
        +int id_utilisateur
        +int id_pays
        +int id_ville
        +int id_lieu
    }

    class Collection {
        +int id_collection
        +string titre
        +string description
        +string couverture_url
        +enum visibility
        +datetime created_at
        +int id_utilisateur
    }

    class CollectionItem {
        +int id_item
        +smallint ordre
        +datetime created_at
        +int id_collection
        +int id_pays
        +int id_ville
        +int id_lieu
    }

    Role "1" <-- "0..*" Utilisateur : possède
    Pays "1" <-- "0..*" Ville : contient
    Ville "1" <-- "0..*" Lieu : situé dans
    CategorieLieu "1" <-- "0..*" Lieu : classé en

    Utilisateur "1" <-- "0..*" Avis : rédige
    Pays "1" <-- "0..*" Avis : cible (optionnel)
    Ville "1" <-- "0..*" Avis : cible (optionnel)
    Lieu "1" <-- "0..*" Avis : cible (optionnel)

    Avis "1" <-- "0..*" PhotoAvis : illustré par

    Utilisateur "1" <-- "0..*" Commentaire : poste
    Avis "1" <-- "0..*" Commentaire : sur
    Commentaire "0..1" <-- "0..*" Commentaire : réponse à

    Utilisateur "1" <-- "0..*" LikeCommentaire : aime
    Commentaire "1" <-- "0..*" LikeCommentaire : liké

    Utilisateur "1" <-- "0..*" Visite : effectue
    Pays "1" <-- "0..*" Visite : cible (optionnel)
    Ville "1" <-- "0..*" Visite : cible (optionnel)
    Lieu "1" <-- "0..*" Visite : cible (optionnel)

    Utilisateur "1" <-- "0..*" Collection : crée
    Collection "1" <-- "0..*" CollectionItem : contient
    Pays "1" <-- "0..*" CollectionItem : cible (optionnel)
    Ville "1" <-- "0..*" CollectionItem : cible (optionnel)
    Lieu "1" <-- "0..*" CollectionItem : cible (optionnel)
```

## Lecture du diagramme

- **Avis, Visite, CollectionItem** sont polymorphiques : ils ciblent au choix un Pays, une Ville ou un Lieu (les 3 clés étrangères sont nullables, exclusivité gérée par contrainte métier).
- **Commentaire** est récursif via `id_parent` : permet de répondre à un commentaire (threads).
- **Utilisateur** a un rôle unique (admin, modérateur, utilisateur).
- Hiérarchie géographique : **Pays → Ville → Lieu**, suppression en cascade.
- **CategorieLieu** classe les lieux : musée, restaurant, monument, etc.
