<?php
/**
 * Fonctions
 */

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Raccourcit un texte (accueil : extraits d’avis) en échappant pour l’affichage.
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

function displayErrors(): string
{
    if (!isset($_SESSION['errors']) || !is_array($_SESSION['errors'])) {
        return '';
    }
    $html = '<div class="errors"><ul>';
    foreach ($_SESSION['errors'] as $err) {
        $html .= '<li>' . htmlspecialchars($err) . '</li>';
    }
    $html .= '</ul></div>';
    unset($_SESSION['errors']);
    return $html;
}

function displaySuccess(): string
{
    if (!isset($_SESSION['success']) || (string) $_SESSION['success'] === '') {
        return '';
    }
    $html = '<div class="success">' . htmlspecialchars((string) $_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
    return $html;
}

/** Préfixe relatif web vers la racine du site (ressources communes) */
function prefixRacine(): string
{
    $name = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_contains($name, '/pages/') || str_contains($name, '/actions/')) {
        return '../';
    }
    return '';
}
