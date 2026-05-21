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

if (!is_file(APP_CONFIG . '/bdd.php')) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>ABS — configuration</title></head><body style="font-family:sans-serif;max-width:36rem;margin:2rem auto;padding:0 1rem;">';
    echo '<h1>Configuration manquante</h1>';
    echo '<p>Le fichier <code>app/Config/bdd.php</code> est absent. Créez-le à partir du modèle :</p>';
    echo '<pre style="background:#f4f4f4;padding:1rem;">cp app/Config/bdd.exemple.php app/Config/bdd.php</pre>';
    echo '<p>Puis importez <code>sql/schema/database.sql</code> dans MySQL (base <strong>abs_db</strong>) et adaptez identifiants / port dans <code>bdd.php</code>.</p>';
    echo '</body></html>';
    exit;
}

$appConfig = require APP_CONFIG . '/application.php';
if (!defined('APP_BASE_URL')) {
    $baseUrl = '/';
    if (is_array($appConfig) && isset($appConfig['base_url']) && is_string($appConfig['base_url'])) {
        $trimmed = trim($appConfig['base_url']);
        if ($trimmed !== '') {
            $baseUrl = '/' . trim($trimmed, '/');
        }
    }
    // Détection automatique du préfixe (MAMP / sous-dossier / php -S)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($dir !== '' && $dir !== '.') {
        $baseUrl = $dir;
    }
    if (php_sapi_name() === 'cli-server') {
        $baseUrl = '';
    }
    define('APP_BASE_URL', $baseUrl === '/' ? '' : $baseUrl);
}

require_once APP_PATH . '/Core/Aides.php';

// Autoload PSR-4 simplifié : App\X\Y → app/X/Y.php (pas de Composer dans ce projet).
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
