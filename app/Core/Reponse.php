<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Helpers HTTP : redirections, JSON, 404.
 * Toutes les méthodes ont le type de retour never (PHP 8.1) : elles appellent
 * systématiquement exit et ne rendent jamais la main à l'appelant.
 */
class Reponse
{
    public static function rediriger(string $url, int $code = 302): never
    {
        if (!preg_match('#^https?://#i', $url) && function_exists('url')) {
            // Évite de préfixer deux fois le base URL (ex. /Projet-ABS/public) :
            // si l'URL passée commence déjà par ce préfixe, url() ne l'applique pas une seconde fois.
            $base = defined('APP_BASE_URL') ? (string) APP_BASE_URL : '';
            $dejaPrefixe = $base !== '' && (
                str_starts_with($url, $base . '/') || $url === $base
            );
            if (!$dejaPrefixe) {
                $url = url($url);
            }
        }
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function json(mixed $donnees, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        // JSON_UNESCAPED_UNICODE : conserve les caractères UTF-8 lisibles (é, à, ★…)
        //   au lieu de les encoder en \uXXXX.
        // JSON_UNESCAPED_SLASHES : évite d'échapper les / dans les URLs.
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
            // Fallback inline si la vue 404 n'existe pas encore (ex. en début de projet).
            echo '<h1>404 — ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</h1>';
        }
        exit;
    }
}
