<?php
declare(strict_types=1);

/**
 * Démarrage de l'application : constantes, autoloader PSR-4, helpers, session.
 * Inclus exclusivement par public/index.php.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', APP_ROOT . '/app');
}
if (!defined('APP_CONFIG')) {
    define('APP_CONFIG', APP_PATH . '/Config');
}

require_once APP_PATH . '/Core/Aides.php';

spl_autoload_register(static function (string $classe): void {
    if (!str_starts_with($classe, 'App\\')) {
        return;
    }
    $relatif = str_replace('\\', DIRECTORY_SEPARATOR, substr($classe, 4));
    $fichier = APP_PATH . DIRECTORY_SEPARATOR . $relatif . '.php';
    if (is_file($fichier)) {
        require_once $fichier;
    }
});

\App\Core\Session::demarrer();
