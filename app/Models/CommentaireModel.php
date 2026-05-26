<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Commentaires sur les avis, avec support des réponses (id_parent).
 */
class CommentaireModel extends Modele
{

    /**
     * Récupère tous les commentaires d'un avis, triés par date.
     * Inclut l'auteur et le nombre de likes.
     *
     * @return list<array<string, mixed>>
     */
    public static function parAvis(int $idAvis): array
    {
        $st = self::pdo()->prepare(
            'SELECT c.id_commentaire, c.texte, c.created_at, c.id_parent,
                    u.id_utilisateur, u.prenom, u.nom,
                    (SELECT COUNT(*) FROM like_commentaire lc
                     WHERE lc.id_commentaire = c.id_commentaire) AS nb_likes
             FROM commentaire c
             JOIN utilisateur u ON u.id_utilisateur = c.id_utilisateur
             WHERE c.id_avis = :id
             ORDER BY c.created_at ASC'
        );
        $st->execute([':id' => $idAvis]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    /**
     * Crée un commentaire (ou une réponse si id_parent est renseigné).
     * Retourne l'id du nouveau commentaire.
     */
    public static function creer(int $idAvis, int $idUser, string $texte, ?int $idParent = null): int
    {
        $st = self::pdo()->prepare(
            'INSERT INTO commentaire (texte, id_avis, id_utilisateur, id_parent)
             VALUES (:texte, :id_avis, :id_user, :id_parent)'
        );
        $st->execute([
            ':texte'     => mb_substr(trim($texte), 0, 2000),
            ':id_avis'   => $idAvis,
            ':id_user'   => $idUser,
            ':id_parent' => $idParent,
        ]);
        return (int) self::pdo()->lastInsertId();
    }


    /**
     * Vérifie qu'un commentaire existe (utile pour valider id_parent).
     */
    public static function existe(int $id): bool
    {
        $st = self::pdo()->prepare('SELECT 1 FROM commentaire WHERE id_commentaire = :id LIMIT 1');
        $st->execute([':id' => $id]);
        return (bool) $st->fetchColumn();
    }

}
