<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * LieuModel — tout ce qui touche aux lieux en base de données.
 *
 * Un "lieu" c'est un point sur la carte : monument, resto, plage...
 * Il appartient à une ville, qui appartient à un pays.
 * Il peut avoir des avis, une catégorie, une image, des coordonnées GPS.
 */
class LieuModel extends Modele
{
    /**
     * Récupère un lieu par son id avec toutes ses infos de localisation.
     * Utilisé pour la page de détail d'un lieu (/lieu?id=X).
     * Retourne null si le lieu n'existe pas.
     */
    public static function trouverParIdAvecLocalisation(int $idLieu): ?array
    {
        $st = self::pdo()->prepare(
            // On remonte toute la hiérarchie : lieu → catégorie + ville + pays
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
        // fetch() retourne false si rien trouvé → on retourne null
        return $r ?: null;
    }

    /**
     * Récupère TOUS les lieux géolocalisés avec leur note moyenne.
     * C'est la requête principale qui alimente la carte (MAP_DATA.places).
     *
     * LEFT JOIN avis : on garde les lieux sans avis (sinon ils n'apparaitraient pas sur la carte)
     * GROUP BY l.id_lieu : une seule ligne par lieu avec AVG(note) calculée
     * WHERE latitude IS NOT NULL : on ne prend que les lieux avec des coordonnées GPS
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
                l.type,                    -- 'pays', 'ville' ou 'monument'
                l.icon,                    -- emoji pour le pin
                cl.libelle AS categorie,   -- Musée, Restaurant, etc.
                p.nom AS country_name,
                p.id_pays,
                -- COALESCE pour renvoyer NULL (et pas 0) si aucun avis
                COALESCE(ROUND(AVG(a.note), 2), NULL) AS avg_rating,
                COUNT(CASE WHEN a.visibility = ‘public’ THEN 1 END) AS review_count
             FROM lieu l
             JOIN categorie_lieu cl ON cl.id_categorie = l.id_categorie
             JOIN ville vi ON vi.id_ville = l.id_ville
             JOIN pays p ON p.id_pays = vi.id_pays
             -- LEFT JOIN = on garde les lieux même sans avis
             LEFT JOIN avis a ON a.id_lieu = l.id_lieu AND a.visibility = 'public'
             WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL
             GROUP BY l.id_lieu, l.nom, l.latitude, l.longitude, l.image_url, l.type, l.icon, p.nom, p.id_pays, cl.libelle, vi.nom";

        $q = self::pdo()->query($sql);
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Cherche une ville par nom + pays, ou la crée si elle n'existe pas encore.
     * Utilisé quand un utilisateur ajoute un lieu via la carte.
     *
     * Le LOWER() évite les doublons genre "paris" vs "Paris" vs "PARIS".
     */
    public static function trouverOuCreerVille(string $nom, int $idPays): int
    {
        $nom = trim($nom);
        if ($nom === '') {
            throw new \InvalidArgumentException('Nom de ville vide.');
        }

        // On cherche d'abord si la ville existe déjà (insensible à la casse)
        $st = self::pdo()->prepare(
            'SELECT id_ville FROM ville
              WHERE LOWER(nom) = LOWER(:nom) AND id_pays = :p
              LIMIT 1'
        );
        $st->execute([':nom' => $nom, ':p' => $idPays]);
        $id = $st->fetchColumn();

        // fetchColumn() renvoie false si aucun résultat → on crée la ville
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
     * Insère un nouveau lieu en base.
     * Appelé par LieuController::creer() après validation du formulaire.
     * Toutes les données ont déjà été nettoyées et validées côté contrôleur.
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
        // lastInsertId() retourne l'id auto-incrémenté du lieu créé
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Renvoie les continents distincts présents en base.
     * Utilisé pour peupler le formulaire de filtres de la page Découvrir.
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
     * Villes qui ont au moins un lieu — pour le filtre de la page Découvrir.
     * On GROUP BY pour éviter les doublons (une ville peut avoir plusieurs lieux).
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
     * Recherche de lieux pour la page "Découvrir".
     * Filtre par catégorie, note minimale, continent, pays, ville.
     * Triés : les mieux notés d'abord, les lieux sans note en dernier.
     *
     * Les paramètres null = pas de filtre sur ce critère.
     * $limite est borné entre 1 et 120 pour éviter des requêtes trop lourdes.
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
        $limite = max(1, min(120, $limite)); // on s'assure que c'est raisonnable

        // Requête de base — on calcule note + nb avis par lieu
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
                WHERE l.type <> 'pays'"; // on ne veut pas afficher les "lieux pays" ici
        $params = [];

        // On ajoute les filtres dynamiquement selon ce qui est renseigné
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
     * Pays ayant au moins un lieu géolocalisé — pour le filtre et les stats de la carte.
     *
     * On calcule :
     * - le centre géographique du pays (moyenne des lat/lng de ses lieux) → pour le flyTo
     * - le nombre de lieux distincts
     * - la note moyenne (avis publics uniquement)
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
                    ROUND(AVG(l2.latitude), 4)  AS lat,   -- centre approximatif du pays
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

    /**
     * Vérifie qu'un lieu avec cet id existe bien en base.
     * Utilisé par AvisController avant d'accepter la soumission d'un avis.
     */
    public static function lieuExiste(int $idLieu): bool
    {
        $st = self::pdo()->prepare('SELECT 1 FROM lieu WHERE id_lieu = :id LIMIT 1');
        $st->execute([':id' => $idLieu]);
        return (bool) $st->fetchColumn();
    }
}
