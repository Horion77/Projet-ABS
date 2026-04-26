<?php
namespace App\Models;

/**
 * Données compte utilisateur (table utilisateur)
 */
class UtilisateurModel
{
    public static function parEmail(string $email) : ?array
    {
        $st = Database::getPdo()->prepare('SELECT * FROM utilisateur WHERE email = :e LIMIT 1');
        $st->execute([':e' => $email]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function parId(int $id) : ?array
    {
        $st = Database::getPdo()->prepare('SELECT * FROM utilisateur WHERE id_utilisateur = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function emailExiste(string $email) : bool
    {
        $st = Database::getPdo()->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :e');
        $st->execute([':e' => $email]);
        return (int) $st->fetchColumn() > 0;
    }

    public static function creer(string $nom, string $prenom, string $email, string $hash) : int
    {
        $st = Database::getPdo()->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, password_hash) VALUES (:nom, :prenom, :email, :ph)'
        );
        $st->execute([
            ':nom'   => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':ph'    => $hash,
        ]);
        return (int) Database::getPdo()->lastInsertId();
    }
}
