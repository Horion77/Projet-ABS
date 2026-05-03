<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;
use Throwable;

/**
 * Données alimentant la page d'accueil : top lieux, derniers avis publics.
 */
class AccueilModel extends Modele
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function classementLieux(int $limite): array
    {
        try {
            $q = self::pdo()->query(
                "SELECT id_lieu, lieu, categorie, ville, pays, note_moyenne, nb_avis
                 FROM vue_classement_lieux
                 LIMIT " . (int) $limite
            );
            if ($q) {
                return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Throwable) {
        }
        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function derniersAvisLieux(int $limite): array
    {
        try {
            $st = self::pdo()->prepare(
                "SELECT a.id_avis, a.note, a.description, a.created_at, a.id_lieu,
                    u.prenom, u.nom, l.nom AS lieu
                 FROM avis a
                 JOIN utilisateur u ON u.id_utilisateur = a.id_utilisateur
                 LEFT JOIN lieu l ON a.id_lieu = l.id_lieu
                 WHERE a.visibility = 'public' AND a.id_lieu IS NOT NULL
                 ORDER BY a.created_at DESC
                 LIMIT " . (int) $limite
            );
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
        }
        return [];
    }
}
