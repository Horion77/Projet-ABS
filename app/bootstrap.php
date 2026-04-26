<?php
/**
 * Démarrage (autoload, session) — à inclure en premier depuis index, pages, actions
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/includes/functions.php';

spl_autoload_register(function (string $classe) : void {
    if (!str_starts_with($classe, 'App\\')) {
        return;
    }
    $fichier = APP_ROOT . '/app/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($classe, 4)) . '.php';
    if (is_file($fichier)) {
        require_once $fichier;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
