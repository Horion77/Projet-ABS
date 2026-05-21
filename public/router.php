<?php
/**
 * Routeur pour le serveur PHP intégré : php -S localhost:8000 -t public public/router.php
 * Simule le rewrite de .htaccess (paramètre url → index.php).
 */
declare(strict_types=1);

$uri = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if ($uri !== '/' && $uri !== '' && is_file(__DIR__ . $uri)) {
    return false;
}

$path = trim($uri, '/');
$_GET['url'] = $path === '' ? '/' : $path;

require __DIR__ . '/index.php';
