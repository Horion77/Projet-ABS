<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Catégories de lieux (Musée, Restaurant, Plage…) — injectées dans la carte pour
 * alimenter le select du formulaire de création de lieu.
 */
class CategorieLieuModel extends Modele
{
    /**
     * @return list<array{id_categorie:int, libelle:string}>
     */
    public static function toutes(): array
    {
        $q = self::pdo()->query(
            'SELECT id_categorie, libelle FROM categorie_lieu ORDER BY libelle ASC'
        );
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function existe(int $idCategorie): bool
    {
        $st = self::pdo()->prepare(
            'SELECT 1 FROM categorie_lieu WHERE id_categorie = :id LIMIT 1'
        );
        $st->execute([':id' => $idCategorie]);
        return (bool) $st->fetchColumn();
    }
}
