<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Lieux : fiche détaillée et liste géolocalisée pour la carte.
 * Concentre tout le SQL extrait de l'ancien pages/place.php et pages/map.php.
 */
class LieuModel extends Modele
{
    /**
     * Fiche d'un lieu avec sa catégorie, sa ville et son pays.
     */
    public static function trouverParIdAvecLocalisation(int $idLieu): ?array
    {
        $st = self::pdo()->prepare(
            'SELECT l.id_lieu, l.nom, l.description, l.latitude, l.longitude, l.image_url,
                cl.libelle AS categorie, vi.nom AS ville, p.nom AS pays, p.id_pays
             FROM lieu l
             JOIN categorie_lieu cl ON cl.id_categorie = l.id_categorie
             JOIN ville vi ON vi.id_ville = l.id_ville
             JOIN pays p ON p.id_pays = vi.id_pays
             WHERE l.id_lieu = :id'
        );
        $st->execute([':id' => $idLieu]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /**
     * Tous les lieux géolocalisés avec leur note moyenne (avis publics) — pour la carte.
     *
     * @return list<array<string, mixed>>
     */
    public static function tousAvecNotes(): array
    {
        // COUNT(CASE WHEN a.visibility = ‘public’ THEN 1 END) : compte uniquement les
        // avis publics sans HAVING (qui filtrerait les lieux sans avis public). Cela
        // permet de retourner tous les lieux sur la carte, même ceux sans avis.
        // La moyenne (AVG) porte sur tous les avis (public + privé) pour être cohérente
        // avec statsParLieu() sur la fiche lieu.
        $sql = "SELECT
                l.id_lieu,
                l.nom AS name,
                l.latitude AS lat,
                l.longitude AS lng,
                l.image_url,
                l.type,
                l.icon,
                cl.libelle AS categorie,
                p.nom AS country_name,
                p.id_pays,
                COALESCE(ROUND(AVG(a.note), 2), NULL) AS avg_rating,
                COUNT(CASE WHEN a.visibility = ‘public’ THEN 1 END) AS review_count
             FROM lieu l
             JOIN categorie_lieu cl ON cl.id_categorie = l.id_categorie
             JOIN ville vi ON vi.id_ville = l.id_ville
             JOIN pays p ON p.id_pays = vi.id_pays
             LEFT JOIN avis a ON a.id_lieu = l.id_lieu AND a.visibility = 'public'
             WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL
             GROUP BY l.id_lieu, l.nom, l.latitude, l.longitude, l.image_url, l.type, l.icon, p.nom, p.id_pays, cl.libelle, vi.nom";
        $q = self::pdo()->query($sql);
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Cherche une ville par nom + pays, sinon la crée. Évite les doublons quand
     * un utilisateur ajoute un lieu dans une ville déjà connue (matching insensible
     * à la casse).
     */
    public static function trouverOuCreerVille(string $nom, int $idPays): int
    {
        $nom = trim($nom);
        if ($nom === '') {
            throw new \InvalidArgumentException('Nom de ville vide.');
        }

        $st = self::pdo()->prepare(
            'SELECT id_ville FROM ville
              WHERE LOWER(nom) = LOWER(:nom) AND id_pays = :p
              LIMIT 1'
        );
        $st->execute([':nom' => $nom, ':p' => $idPays]);
        $id = $st->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $st = self::pdo()->prepare(
            'INSERT INTO ville (nom, id_pays) VALUES (:nom, :p)'
        );
        $st->execute([':nom' => $nom, ':p' => $idPays]);
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Insère un nouveau lieu créé par un utilisateur depuis la carte.
     * Les champs ville/pays sont déjà résolus en IDs par le contrôleur.
     *
     * @param array{
     *   nom: string, type: string, icon: string, description: ?string,
     *   latitude: float, longitude: float, adresse: ?string, image_url: ?string,
     *   id_categorie: int, id_ville: int
     * } $data
     */
    public static function creer(array $data): int
    {
        $st = self::pdo()->prepare(
            'INSERT INTO lieu (type, icon, nom, description, latitude, longitude,
                               adresse, image_url, id_categorie, id_ville)
             VALUES (:type, :icon, :nom, :description, :latitude, :longitude,
                     :adresse, :image_url, :id_categorie, :id_ville)'
        );
        $st->execute([
            ':type'         => $data['type'],
            ':icon'         => $data['icon'],
            ':nom'          => $data['nom'],
            ':description'  => $data['description'],
            ':latitude'     => $data['latitude'],
            ':longitude'    => $data['longitude'],
            ':adresse'      => $data['adresse'],
            ':image_url'    => $data['image_url'],
            ':id_categorie' => $data['id_categorie'],
            ':id_ville'     => $data['id_ville'],
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Continents distincts présents en base — pour le formulaire « Découvrir ».
     *
     * @return list<string>
     */
    public static function continentsDisponibles(): array
    {
        $q = self::pdo()->query(
            "SELECT DISTINCT continent FROM pays
              WHERE continent IS NOT NULL AND continent <> ''
              ORDER BY continent ASC"
        );
        return $q ? array_map('strval', $q->fetchAll(PDO::FETCH_COLUMN)) : [];
    }

    /**
     * Villes (avec leur pays) ayant au moins un lieu — pour le filtre « Découvrir ».
     *
     * @return list<array{id_ville:int|string, nom:string, pays:string}>
     */
    public static function villesPourFiltre(): array
    {
        $q = self::pdo()->query(
            "SELECT v.id_ville, v.nom, p.nom AS pays
             FROM ville v
             JOIN pays p ON p.id_pays = v.id_pays
             JOIN lieu l ON l.id_ville = v.id_ville
             GROUP BY v.id_ville, v.nom, p.nom
             ORDER BY p.nom ASC, v.nom ASC"
        );
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Recherche « Découvrir » : lieux filtrés par catégorie / note mini /
     * continent / pays / ville, triés par note moyenne puis popularité.
     * La note et le nombre d'avis sont calculés sur les avis publics.
     *
     * @return list<array<string, mixed>>
     */
    public static function rechercheDecouverte(
        ?string $categorie,
        int $noteMin,
        ?string $continent,
        ?int $idPays,
        ?int $idVille,
        int $limite = 60
    ): array {
        $limite = max(1, min(120, $limite));

        $sql = "SELECT l.id_lieu, l.nom, l.image_url, l.latitude, l.longitude,
                    cl.libelle AS categorie,
                    vi.nom AS ville, p.nom AS pays, p.continent, p.id_pays,
                    COUNT(a.id_avis)        AS nb_avis,
                    ROUND(AVG(a.note), 1)   AS note_moy
                FROM lieu l
                JOIN categorie_lieu cl ON cl.id_categorie = l.id_categorie
                JOIN ville vi          ON vi.id_ville     = l.id_ville
                JOIN pays p            ON p.id_pays       = vi.id_pays
                LEFT JOIN avis a       ON a.id_lieu = l.id_lieu AND a.visibility = 'public'
                WHERE l.type <> 'pays'";
        $params = [];

        if ($categorie !== null && $categorie !== '') {
            $sql .= ' AND cl.libelle = :cat';
            $params[':cat'] = $categorie;
        }
        if ($continent !== null && $continent !== '') {
            $sql .= ' AND p.continent = :cont';
            $params[':cont'] = $continent;
        }
        if ($idPays !== null && $idPays > 0) {
            $sql .= ' AND p.id_pays = :pays';
            $params[':pays'] = $idPays;
        }
        if ($idVille !== null && $idVille > 0) {
            $sql .= ' AND vi.id_ville = :ville';
            $params[':ville'] = $idVille;
        }

        $sql .= ' GROUP BY l.id_lieu, l.nom, l.image_url, l.latitude, l.longitude,
                           cl.libelle, vi.nom, p.nom, p.continent, p.id_pays';

        // HAVING (et non WHERE) car on filtre sur un agrégat AVG calculé après GROUP BY.
        // Effet de bord voulu : les lieux sans avis (AVG = NULL) sont aussi exclus
        // quand un noteMin est demandé — cohérent avec l'UX "note minimum X".
        if ($noteMin >= 1 && $noteMin <= 5) {
            $sql .= ' HAVING AVG(a.note) >= :noteMin';
            $params[':noteMin'] = $noteMin;
        }

        // (note_moy IS NULL) retourne 0 (faux) ou 1 (vrai) → ORDER BY ... ASC place
        // les non-NULL en premier (0 < 1). C'est le moyen le plus portable en MySQL
        // pour trier NULL en dernier sans NULLS LAST (disponible en MySQL 8.0.26+).
        $sql .= ' ORDER BY (note_moy IS NULL) ASC, note_moy DESC, nb_avis DESC, l.nom ASC';
        $sql .= ' LIMIT ' . (int) $limite;

        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Pays distincts ayant au moins un lieu géolocalisé — pour le filtre de la carte.
     *
     * @return list<array{id_pays:int|string, nom:string}>
     */
    public static function paysPourFiltreCarte(): array
    {
        // places_count + avg_rating alimentent la mini-card au hover sur la carte.
        // ROUND(AVG(latitude / longitude)) : centroïde géographique de tous les lieux
        // du pays — utilisé pour centrer la caméra lors du flyTo sur un pays.
        // LEFT JOIN avis : conserve les pays dont aucun avis n'est encore public.
        $q = self::pdo()->query(
            "SELECT p.id_pays, p.nom, p.code_iso,
                    ROUND(AVG(l2.latitude), 4)  AS lat,
                    ROUND(AVG(l2.longitude), 4) AS lng,
                    COUNT(DISTINCT l2.id_lieu)  AS places_count,
                    ROUND(AVG(a.note), 1)       AS avg_rating
             FROM pays p
             JOIN ville v  ON v.id_pays  = p.id_pays
             JOIN lieu l2  ON l2.id_ville = v.id_ville
             LEFT JOIN avis a ON a.id_lieu = l2.id_lieu AND a.visibility = 'public'
             WHERE l2.latitude IS NOT NULL AND l2.longitude IS NOT NULL
             GROUP BY p.id_pays, p.nom, p.code_iso
             ORDER BY p.nom"
        );
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
