<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Signalements : un utilisateur signale un avis ou un commentaire,
 * un admin peut consulter la file d'attente et traiter (marquer ou supprimer
 * le contenu fautif).
 */
class SignalementModel extends Modele
{
    public const STATUTS = ['en_attente', 'traite', 'rejete'];
    public const TYPES   = ['avis', 'commentaire'];

    /**
     * Crée un signalement. Renvoie l'id, ou 0 si l'utilisateur a déjà signalé
     * ce contenu (contrainte unique).
     */
    public static function creer(
        int $idUtilisateur,
        string $cibleType,
        int $cibleId,
        string $motif,
        ?string $details = null
    ): int {
        if (!in_array($cibleType, self::TYPES, true)) {
            return 0;
        }

        $st = self::pdo()->prepare(
            'INSERT IGNORE INTO signalement
               (cible_type, cible_id, motif, details, id_utilisateur)
             VALUES (:t, :ci, :m, :d, :u)'
        );
        $st->execute([
            ':t'  => $cibleType,
            ':ci' => $cibleId,
            ':m'  => mb_substr($motif, 0, 60),
            ':d'  => $details !== null ? mb_substr($details, 0, 500) : null,
            ':u'  => $idUtilisateur,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** A déjà signalé ce contenu : utile pour cacher le bouton côté UI. */
    public static function aDejaSignale(int $idUtilisateur, string $cibleType, int $cibleId): bool
    {
        $st = self::pdo()->prepare(
            'SELECT 1 FROM signalement
              WHERE id_utilisateur = :u AND cible_type = :t AND cible_id = :ci
              LIMIT 1'
        );
        $st->execute([':u' => $idUtilisateur, ':t' => $cibleType, ':ci' => $cibleId]);
        return (bool) $st->fetchColumn();
    }

    /**
     * File d'attente pour l'admin : signalements en attente, avec l'auteur
     * du signalement, un extrait du contenu visé et son auteur d'origine.
     *
     * @return list<array<string, mixed>>
     */
    public static function listePourAdmin(string $statut = 'en_attente', int $limite = 100): array
    {
        $statut  = in_array($statut, self::STATUTS, true) ? $statut : 'en_attente';
        $limite  = max(1, min(500, $limite));

        // CASE pour extraire le bon contenu et auteur cible selon cible_type.
        $sql = "SELECT s.id_signalement, s.cible_type, s.cible_id, s.motif,
                       s.details, s.statut, s.created_at,
                       s.id_utilisateur AS sig_user_id,
                       u_sig.prenom AS sig_prenom, u_sig.nom AS sig_nom,
                       CASE s.cible_type
                           WHEN 'avis'        THEN a.description
                           WHEN 'commentaire' THEN c.texte
                       END AS contenu,
                       CASE s.cible_type
                           WHEN 'avis'        THEN u_a.prenom
                           WHEN 'commentaire' THEN u_c.prenom
                       END AS cible_prenom,
                       CASE s.cible_type
                           WHEN 'avis'        THEN u_a.nom
                           WHEN 'commentaire' THEN u_c.nom
                       END AS cible_nom,
                       a.id_lieu AS avis_id_lieu
                FROM signalement s
                JOIN utilisateur u_sig
                  ON u_sig.id_utilisateur = s.id_utilisateur
                LEFT JOIN avis a
                  ON s.cible_type = 'avis' AND a.id_avis = s.cible_id
                LEFT JOIN utilisateur u_a
                  ON u_a.id_utilisateur = a.id_utilisateur
                LEFT JOIN commentaire c
                  ON s.cible_type = 'commentaire' AND c.id_commentaire = s.cible_id
                LEFT JOIN utilisateur u_c
                  ON u_c.id_utilisateur = c.id_utilisateur
                WHERE s.statut = :statut
                ORDER BY s.created_at DESC
                LIMIT " . (int) $limite;

        $st = self::pdo()->prepare($sql);
        $st->execute([':statut' => $statut]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Compte les signalements par statut (pour le badge dans la nav admin). */
    public static function compterParStatut(string $statut = 'en_attente'): int
    {
        $statut = in_array($statut, self::STATUTS, true) ? $statut : 'en_attente';
        $st     = self::pdo()->prepare('SELECT COUNT(*) FROM signalement WHERE statut = :s');
        $st->execute([':s' => $statut]);
        return (int) $st->fetchColumn();
    }

    /** Change le statut d'un signalement (traite / rejete). */
    public static function changerStatut(int $idSignalement, string $statut): bool
    {
        if (!in_array($statut, self::STATUTS, true)) {
            return false;
        }
        $st = self::pdo()->prepare(
            'UPDATE signalement SET statut = :s WHERE id_signalement = :id'
        );
        return $st->execute([':s' => $statut, ':id' => $idSignalement]);
    }

    /**
     * Récupère un signalement (pour les actions ciblées : suppression du
     * contenu fautif). Renvoie null si introuvable.
     */
    public static function trouver(int $idSignalement): ?array
    {
        $st = self::pdo()->prepare(
            'SELECT id_signalement, cible_type, cible_id, statut
             FROM signalement WHERE id_signalement = :id LIMIT 1'
        );
        $st->execute([':id' => $idSignalement]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /**
     * Supprime le contenu visé (avis ou commentaire) puis marque tous les
     * signalements qui le visaient comme « traités » d'un coup.
     */
    public static function supprimerCible(string $cibleType, int $cibleId): bool
    {
        if (!in_array($cibleType, self::TYPES, true)) {
            return false;
        }
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            if ($cibleType === 'avis') {
                $st = $pdo->prepare('DELETE FROM avis WHERE id_avis = :id');
            } else {
                $st = $pdo->prepare('DELETE FROM commentaire WHERE id_commentaire = :id');
            }
            $st->execute([':id' => $cibleId]);

            // Tous les signalements sur cette cible deviennent « traités »
            $upd = $pdo->prepare(
                "UPDATE signalement SET statut = 'traite'
                  WHERE cible_type = :t AND cible_id = :ci"
            );
            $upd->execute([':t' => $cibleType, ':ci' => $cibleId]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
