<?php
namespace App\Models;

/**
 * Contenu de la page d’accueil
 */
class HomeModel
{
    public static function classementLieux(int $limite) : array
    {
        try {
            $pdo = Database::getPdo();
            $q   = $pdo->query(
                "SELECT id_lieu, lieu, categorie, ville, pays, note_moyenne, nb_avis
                 FROM vue_classement_lieux
                 LIMIT " . (int) $limite
            );
            if ($q) {
                return $q->fetchAll() ?: [];
            }
        } catch (\Throwable $e) {
        }
        return [];
    }

    public static function derniersAvisLieux(int $limite) : array
    {
        try {
            $pdo = Database::getPdo();
            $st  = $pdo->prepare(
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
            return $st->fetchAll() ?: [];
        } catch (\Throwable $e) {
        }
        return [];
    }
}
