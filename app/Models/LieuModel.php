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
        // LEFT JOIN avis + GROUP BY : une ligne par lieu, AVG sur les avis publics seulement.
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
                COUNT(CASE WHEN a.visibility = 'public' THEN 1 END) AS review_count
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
     * Pays distincts ayant au moins un lieu géolocalisé — pour le filtre de la carte.
     *
     * @return list<array{id_pays:int|string, nom:string}>
     */
    public static function paysPourFiltreCarte(): array
    {
        $q = self::pdo()->query(
            'SELECT p.id_pays, p.nom,
                    ROUND(AVG(l2.latitude), 4)  AS lat,
                    ROUND(AVG(l2.longitude), 4) AS lng
             FROM pays p
             JOIN ville v  ON v.id_pays  = p.id_pays
             JOIN lieu l2  ON l2.id_ville = v.id_ville
             WHERE l2.latitude IS NOT NULL AND l2.longitude IS NOT NULL
             GROUP BY p.id_pays, p.nom
             ORDER BY p.nom'
        );
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
