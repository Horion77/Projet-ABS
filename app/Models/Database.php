<?php
namespace App\Models;

use PDO;

/**
 * Accès PDO (connexion partagée)
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function getPdo() : PDO
    {
        if (self::$pdo === null) {
            $fichier = \dirname(__DIR__, 2) . '/config/database.php';
            $pdo = null;
            require $fichier;
            if ($pdo === null) {
                throw new \RuntimeException('Connexion BDD non initialisée.');
            }
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }
}
