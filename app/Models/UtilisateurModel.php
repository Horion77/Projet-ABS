<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Données du compte utilisateur (table utilisateur).
 */
class UtilisateurModel extends Modele
{
    public static function parEmail(string $email): ?array
    {
        // SELECT * : récupère toutes les colonnes dont password_hash, avatar, bio, id_role…
        // Intentionnel : si la table évolue (nouvelle colonne), le résultat est automatiquement
        // disponible sans modifier ce modèle. La connexion ne se fait que sur cette méthode.
        // LIMIT 1 : défensif — email est UNIQUE en BDD, donc au plus 1 résultat.
        $st = self::pdo()->prepare('SELECT * FROM utilisateur WHERE email = :e LIMIT 1');
        $st->execute([':e' => $email]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function parId(int $id): ?array
    {
        // Même choix SELECT * que parEmail() : id_utilisateur est la PK, donc LIMIT 1 = 0 coût.
        $st = self::pdo()->prepare('SELECT * FROM utilisateur WHERE id_utilisateur = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function emailExiste(string $email): bool
    {
        $st = self::pdo()->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :e');
        $st->execute([':e' => $email]);
        return (int) $st->fetchColumn() > 0;
    }

    public static function creer(string $nom, string $prenom, string $email, string $hash): int
    {
        $st = self::pdo()->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, password_hash) VALUES (:nom, :prenom, :email, :ph)'
        );
        $st->execute([
            ':nom'    => $nom,
            ':prenom' => $prenom,
            ':email'  => $email,
            ':ph'     => $hash,
        ]);
        // lastInsertId() retourne l'id auto-incrémenté du compte créé.
        // Utilisé immédiatement pour connecter l'utilisateur (Session::connecter).
        return (int) self::pdo()->lastInsertId();
    }
}
