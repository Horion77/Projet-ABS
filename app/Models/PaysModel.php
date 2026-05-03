<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Données pays + lieux d'un pays.
 */
class PaysModel extends Modele
{
    public static function parId(int $idPays): ?array
    {
        $st = self::pdo()->prepare('SELECT * FROM pays WHERE id_pays = :id LIMIT 1');
        $st->execute([':id' => $idPays]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /**
     * @return list<array{id_pays:int|string,nom:string}>
     */
    public static function listePourFiltre(): array
    {
        $q = self::pdo()->query('SELECT id_pays, nom FROM pays ORDER BY nom ASC');
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Lieux du pays avec note moyenne (avis publics sur lieu uniquement).
     *
     * @return list<array<string, mixed>>
     */
    public static function lieuxAvecNotes(int $idPays): array
    {
        $st = self::pdo()->prepare(
            "SELECT l.id_lieu, l.nom, l.image_url, vi.nom AS ville_nom,
                COUNT(a.id_avis) AS nb_avis,
                COALESCE(ROUND(AVG(a.note), 2), NULL) AS avg_rating
             FROM lieu l
             JOIN ville vi ON vi.id_ville = l.id_ville
             LEFT JOIN avis a ON a.id_lieu = l.id_lieu AND a.visibility = 'public'
             WHERE vi.id_pays = :p
             GROUP BY l.id_lieu, l.nom, l.image_url, vi.nom
             ORDER BY avg_rating IS NULL, avg_rating DESC, l.nom ASC"
        );
        $st->execute([':p' => $idPays]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
