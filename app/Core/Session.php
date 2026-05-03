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
            'id'     => (int) $_SESSION['user_id'],
            'prenom' => (string) ($_SESSION['user_prenom'] ?? ''),
            'nom'    => (string) ($_SESSION['user_nom'] ?? ''),
        ];
    }

    public static function connecter(int $id, string $prenom, string $nom): void
    {
        $_SESSION['user_id']     = $id;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom']    = $nom;
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
