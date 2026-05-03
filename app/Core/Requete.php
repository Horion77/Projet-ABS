<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Wrapper sécurisé autour de $_GET / $_POST / $_SERVER.
 */
class Requete
{
    public function methode(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    /** Chemin seul (sans ?query), normalisé : toujours commence par /, sans slash final sauf /. */
    public function chemin(): string
    {
        $uri    = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $chemin = parse_url($uri, PHP_URL_PATH);
        if (!is_string($chemin) || $chemin === '') {
            return '/';
        }
        $chemin = '/' . ltrim($chemin, '/');
        if ($chemin !== '/' && str_ends_with($chemin, '/')) {
            $chemin = rtrim($chemin, '/');
        }
        return $chemin;
    }

    public function estPost(): bool
    {
        return $this->methode() === 'POST';
    }

    public function get(string $cle, mixed $defaut = null): mixed
    {
        return $_GET[$cle] ?? $defaut;
    }

    public function getInt(string $cle, int $defaut = 0): int
    {
        return isset($_GET[$cle]) ? (int) $_GET[$cle] : $defaut;
    }

    public function post(string $cle, mixed $defaut = null): mixed
    {
        return $_POST[$cle] ?? $defaut;
    }

    public function postString(string $cle, string $defaut = ''): string
    {
        $v = $_POST[$cle] ?? $defaut;
        return is_string($v) ? trim($v) : $defaut;
    }

    public function postInt(string $cle, int $defaut = 0): int
    {
        return isset($_POST[$cle]) ? (int) $_POST[$cle] : $defaut;
    }
}
