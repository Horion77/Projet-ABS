<?php
declare(strict_types=1);

/**
 * Constantes globales (nom, env, base URL). Fichier prêt pour une évolution du bootstrap ;
 * pour l’instant le démarrage ne fait que définir APP_ROOT / APP_CONFIG.
 */

return [
    'nom'      => 'ABS',
    'env'      => getenv('APP_ENV') ?: 'dev',
    'base_url' => '',
];
