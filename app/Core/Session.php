<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Wrapper minimaliste autour de $_SESSION : flash messages, ancien input, utilisateur courant.
 */
class Session
{
    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function estConnecte(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function utilisateur(): ?array
    {
        if (!self::estConnecte()) {
            return null;
        }
        return [
            'id'      => (int) $_SESSION['user_id'],
            'prenom'  => (string) ($_SESSION['user_prenom'] ?? ''),
            'nom'     => (string) ($_SESSION['user_nom'] ?? ''),
            'id_role' => (int)    ($_SESSION['user_role'] ?? 3),
        ];
    }

    /**
     * Vrai si l'utilisateur courant a le rôle admin (id_role = 1).
     * Une session déjà ouverte avant cette évolution renverra false ;
     * l'utilisateur doit alors se reconnecter pour récupérer son rôle.
     */
    public static function estAdmin(): bool
    {
        return self::estConnecte() && (int) ($_SESSION['user_role'] ?? 0) === 1;
    }

    /**
     * Vrai si l'utilisateur peut modérer le contenu : admin (1) OU
     * modérateur (2). Sert à gater la page « Modération des signalements ».
     */
    public static function estModerateur(): bool
    {
        $role = (int) ($_SESSION['user_role'] ?? 0);
        return self::estConnecte() && ($role === 1 || $role === 2);
    }

    public static function connecter(int $id, string $prenom, string $nom, int $idRole = 3): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']     = $id;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom']    = $nom;
        $_SESSION['user_role']   = $idRole;
    }

    public static function deconnecter(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Messages flash : stockés en session puis lus une fois (displayErrors / displaySuccess les unset).
     * @param string|string[] $erreurs
     */
    public static function flashErreurs(array|string $erreurs): void
    {
        $_SESSION['errors'] = is_array($erreurs) ? array_values($erreurs) : [$erreurs];
    }

    public static function flashSucces(string $message): void
    {
        $_SESSION['success'] = $message;
    }

    /** Reprise des champs formulaire après erreur (ex. old_login). */
    public static function flashAncien(string $cle, array $valeurs): void
    {
        $_SESSION[$cle] = $valeurs;
    }

    /** Lit puis supprime la clé : une seule requête HTTP voit ces données. */
    public static function recupererAncien(string $cle): array
    {
        $val = $_SESSION[$cle] ?? [];
        unset($_SESSION[$cle]);
        return is_array($val) ? $val : [];
    }
}
