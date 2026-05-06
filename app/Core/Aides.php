<?php
declare(strict_types=1);

/**
 * Fonctions d'aide globales : échappement, formatage, messages flash, étoiles…
 *
 * Toutes les anciennes fonctions de includes/functions.php sont conservées,
 * sauf prefixRacine() qui devient inutile (les assets sont maintenant servis
 * en chemin absolu /assets/… via le front controller).
 *
 * if (!function_exists(...)) : évite une erreur fatale si le fichier est inclus deux fois.
 */

// e() : toujours utiliser pour afficher du texte utilisateur/BDD dans le HTML (protection XSS).
if (!function_exists('e')) {
    function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize')) {
    function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): never
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = url($url);
        }
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('tronque_e')) {
    /**
     * Raccourcit un texte (ex. extraits d'avis sur l'accueil) en l'échappant pour l'affichage.
     */
    function tronque_e(string $texte, int $max = 160): string
    {
        if (function_exists('mb_strimwidth')) {
            return e(mb_strimwidth($texte, 0, $max, '…', 'UTF-8'));
        }
        if (strlen($texte) <= $max) {
            return e($texte);
        }
        return e(substr($texte, 0, $max - 1) . '…');
    }
}

if (!function_exists('displayErrors')) {
    function displayErrors(): string
    {
        if (!isset($_SESSION['errors']) || !is_array($_SESSION['errors'])) {
            return '';
        }
        $html = '<div class="errors"><ul>';
        foreach ($_SESSION['errors'] as $err) {
            $html .= '<li>' . e((string) $err) . '</li>';
        }
        $html .= '</ul></div>';
        unset($_SESSION['errors']);
        return $html;
    }
}

if (!function_exists('displaySuccess')) {
    function displaySuccess(): string
    {
        if (!isset($_SESSION['success']) || (string) $_SESSION['success'] === '') {
            return '';
        }
        $html = '<div class="success">' . e((string) $_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
        return $html;
    }
}

if (!function_exists('starsRatingHtml')) {
    /**
     * Affichage en lecture seule d'étoiles 1–5 (Unicode).
     */
    function starsRatingHtml(int $note): string
    {
        $note = max(1, min(5, $note));
        $html = '<span class="stars-readonly" role="img" aria-label="' . e((string) $note) . ' sur 5">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= $i <= $note ? '★' : '☆';
        }
        return $html . '</span>';
    }
}

if (!function_exists('reviews_pagination_query')) {
    /**
     * Query string pour la pagination de la page avis (filtres + page).
     *
     * @param array<string,mixed> $filtres
     */
    function reviews_pagination_query(array $filtres, int $page): string
    {
        $q = $filtres;
        $q['page'] = max(1, $page);
        return http_build_query($q);
    }
}

if (!function_exists('url')) {
    /**
     * Helper d'URL absolue tenant compte d'un éventuel sous-dossier (ex. MAMP).
     */
    function url(string $chemin = ''): string
    {
        $base = defined('APP_BASE_URL') ? (string) APP_BASE_URL : '/';
        $base = '/' . trim($base, '/');
        if ($base === '//') {
            $base = '/';
        }

        $chemin = ltrim($chemin, '/');
        if ($chemin === '') {
            return $base;
        }
        return rtrim($base, '/') . '/' . $chemin;
    }
}

if (!function_exists('asset')) {
    function asset(string $chemin): string
    {
        return url('assets/' . ltrim($chemin, '/'));
    }
}
