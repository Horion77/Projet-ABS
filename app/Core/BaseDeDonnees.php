<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Connexion PDO partagée (singleton).
 * La configuration vit dans app/Config/bdd.php (gitignored).
 */
class BaseDeDonnees
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $fichier = APP_CONFIG . '/bdd.php';
            $pdo = null;
            require $fichier;
            if (!$pdo instanceof PDO) {
                throw new RuntimeException('Connexion BDD non initialisée (vérifier app/Config/bdd.php).');
            }
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }
}
