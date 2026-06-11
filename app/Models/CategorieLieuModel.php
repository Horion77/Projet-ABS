<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Catégories de lieux (Musée, Restaurant, Plage…).
 *
 * Ce modèle est en lecture seule : les catégories sont définies une fois pour toutes
 * dans les seeds SQL (categorie_lieu) et ne sont jamais créées ou supprimées
 * depuis l'application. Pas de méthode creer() / supprimer() par conception.
 *
 * Utilisé par :
 *   - CarteController  → chips de filtre par type dans le panneau nav
 *   - LieuController   → validation de l'id_categorie lors de la création d'un lieu
 *   - DecouvrirController → select "catégorie" du formulaire de recherche
 */
class CategorieLieuModel extends Modele
{
    /**
     * Retourne toutes les catégories triées alphabétiquement.
     * Utilisé pour peupler les dropdowns et les chips de filtre.
     *
     * @return list<array{id_categorie:int, libelle:string}>
     */
    public static function toutes(): array
    {
        // query() sans prepare() : pas de paramètre utilisateur dans cette requête,
        // donc aucun risque d'injection SQL. Plus simple que prepare()/execute().
        // ORDER BY libelle ASC : tri alphabétique pour un affichage cohérent dans
        // les selects/dropdowns, indépendamment de l'ordre d'insertion en BDD.
        $q = self::pdo()->query(
            'SELECT id_categorie, libelle FROM categorie_lieu ORDER BY libelle ASC'
        );
        // $q peut valoir false si PDO échoue sans lever d'exception (mode hors ERRMODE_EXCEPTION).
        // Le ternaire garantit un tableau vide plutôt qu'une erreur sur fetchAll(false).
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Vérifie qu'une catégorie existe en base avant d'insérer un lieu.
     * Empêche d'associer un lieu à un id_categorie fantôme passé par le client.
     */
    public static function existe(int $idCategorie): bool
    {
        // SELECT 1 LIMIT 1 : MySQL s'arrête dès la première ligne trouvée,
        // plus efficace que COUNT(*) qui parcourerait toutes les lignes correspondantes.
        $st = self::pdo()->prepare(
            'SELECT 1 FROM categorie_lieu WHERE id_categorie = :id LIMIT 1'
        );
        $st->execute([':id' => $idCategorie]);
        return (bool) $st->fetchColumn();
    }
}
