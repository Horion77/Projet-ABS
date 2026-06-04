<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Gestion des likes sur les avis et les commentaires.
 * Chaque méthode "toggle" ajoute le like s'il n'existe pas, le retire sinon.
 */
class LikeModel extends Modele
{

    /**
     * Toggle like sur un avis.
     * Retourne true si le like vient d'être ajouté, false s'il vient d'être retiré.
     *
     * Pattern lecture-avant-écriture (2 requêtes) : on lit l'état actuel pour savoir
     * quelle action faire ET pour retourner le nouvel état au contrôleur.
     * Alternative possible : INSERT ON DUPLICATE KEY UPDATE + lecture du résultat,
     * mais plus complexe et moins lisible pour le même résultat.
     */
    public static function toggleAvis(int $idUser, int $idAvis): bool
    {
        if (self::userALikeAvis($idUser, $idAvis)) {
            self::pdo()->prepare(
                'DELETE FROM like_avis WHERE id_utilisateur = :u AND id_avis = :a'
            )->execute([':u' => $idUser, ':a' => $idAvis]);
            return false;
        }

        self::pdo()->prepare(
            'INSERT INTO like_avis (id_utilisateur, id_avis) VALUES (:u, :a)'
        )->execute([':u' => $idUser, ':a' => $idAvis]);
        return true;
    }


    /**
     * Toggle like sur un commentaire.
     * Retourne true si ajouté, false si retiré.
     */
    public static function toggleCommentaire(int $idUser, int $idCommentaire): bool
    {
        if (self::userALikeCommentaire($idUser, $idCommentaire)) {
            self::pdo()->prepare(
                'DELETE FROM like_commentaire WHERE id_utilisateur = :u AND id_commentaire = :c'
            )->execute([':u' => $idUser, ':c' => $idCommentaire]);
            return false;
        }

        self::pdo()->prepare(
            'INSERT INTO like_commentaire (id_utilisateur, id_commentaire) VALUES (:u, :c)'
        )->execute([':u' => $idUser, ':c' => $idCommentaire]);
        return true;
    }


    /**
     * Compte les likes sur un avis.
     */
    public static function compterAvis(int $idAvis): int
    {
        $st = self::pdo()->prepare(
            'SELECT COUNT(*) FROM like_avis WHERE id_avis = :a'
        );
        $st->execute([':a' => $idAvis]);
        return (int) $st->fetchColumn();
    }


    /**
     * Compte les likes sur un commentaire.
     */
    public static function compterCommentaire(int $idCommentaire): int
    {
        $st = self::pdo()->prepare(
            'SELECT COUNT(*) FROM like_commentaire WHERE id_commentaire = :c'
        );
        $st->execute([':c' => $idCommentaire]);
        return (int) $st->fetchColumn();
    }


    /**
     * Vérifie si l'utilisateur a liké un avis.
     */
    public static function userALikeAvis(int $idUser, int $idAvis): bool
    {
        $st = self::pdo()->prepare(
            'SELECT 1 FROM like_avis WHERE id_utilisateur = :u AND id_avis = :a LIMIT 1'
        );
        $st->execute([':u' => $idUser, ':a' => $idAvis]);
        return (bool) $st->fetchColumn();
    }


    /**
     * Vérifie si l'utilisateur a liké un commentaire.
     */
    public static function userALikeCommentaire(int $idUser, int $idCommentaire): bool
    {
        $st = self::pdo()->prepare(
            'SELECT 1 FROM like_commentaire WHERE id_utilisateur = :u AND id_commentaire = :c LIMIT 1'
        );
        $st->execute([':u' => $idUser, ':c' => $idCommentaire]);
        return (bool) $st->fetchColumn();
    }

}
