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

    /**
     * Retourne le chemin de la requête, normalisé (commence par /, pas de slash final).
     * $_GET['url'] est peuplé par la règle mod_rewrite du .htaccess :
     *   RewriteRule ^(.*)$ index.php?url=$1
     * Ainsi /lieu?id=3 devient $_GET['url'] = 'lieu' et $_GET['id'] = '3'.
     */
    public function chemin(): string
    {
        $url = $_GET['url'] ?? '/';
        // Normalise : ajoute le slash de début, retire celui de fin
        $chemin = '/' . trim($url, '/');
        return $chemin === '' ? '/' : $chemin;
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

    // postString() trim automatiquement la valeur : le contrôleur n'a pas besoin
    // d'appeler trim() manuellement. Comportement différent de post() (pas de trim).
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
