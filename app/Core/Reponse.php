<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Helpers HTTP : redirections et réponses JSON.
 */
class Reponse
{
    public static function rediriger(string $url, int $code = 302): never
    {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function json(mixed $donnees, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function notFound(string $message = 'Page introuvable'): never
    {
        http_response_code(404);
        $vue = APP_ROOT . '/app/Views/erreurs/404.php';
        if (is_file($vue)) {
            require $vue;
        } else {
            echo '<h1>404 — ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</h1>';
        }
        exit;
    }
}
