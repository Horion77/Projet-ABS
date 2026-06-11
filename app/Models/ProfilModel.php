<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;

/**
 * Opérations de mise à jour du profil utilisateur (table utilisateur).
 */
class ProfilModel extends Modele
{
    /**
     * Met à jour prénom, nom et email de l'utilisateur.
     */
    public static function modifierInfos(int $id, string $prenom, string $nom, string $email): bool
    {
        $st = self::pdo()->prepare(
            'UPDATE utilisateur SET prenom = :prenom, nom = :nom, email = :email WHERE id_utilisateur = :id'
        );
        return $st->execute([
            ':prenom' => $prenom,
            ':nom'    => $nom,
            ':email'  => $email,
            ':id'     => $id,
        ]);
    }

    /**
     * Met à jour le hash du mot de passe.
     */
    public static function modifierPassword(int $id, string $hash): bool
    {
        $st = self::pdo()->prepare(
            'UPDATE utilisateur SET password_hash = :hash WHERE id_utilisateur = :id'
        );
        return $st->execute([
            ':hash' => $hash,
            ':id'   => $id,
        ]);
    }

    /**
     * Met à jour le chemin de l'avatar.
     */
    public static function modifierAvatar(int $id, string $chemin): bool
    {
        $st = self::pdo()->prepare(
            'UPDATE utilisateur SET avatar = :avatar WHERE id_utilisateur = :id'
        );
        return $st->execute([
            ':avatar' => $chemin,
            ':id'     => $id,
        ]);
    }

    /**
     * Vérifie si l'email est déjà utilisé par un autre compte.
     * Différent de UtilisateurModel::emailExiste() qui ne s'utilise qu'à l'inscription :
     * ici on exclut l'utilisateur courant (id_utilisateur != :id) pour qu'un utilisateur
     * puisse garder son propre email sans déclencher une fausse erreur de doublon.
     */
    public static function emailExistePourAutre(string $email, int $idActuel): bool
    {
        $st = self::pdo()->prepare(
            'SELECT COUNT(*) FROM utilisateur WHERE email = :email AND id_utilisateur != :id'
        );
        $st->execute([
            ':email' => $email,
            ':id'    => $idActuel,
        ]);
        return (int) $st->fetchColumn() > 0;
    }
}
