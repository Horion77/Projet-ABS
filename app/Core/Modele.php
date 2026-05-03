<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base abstraite pour tous les modèles : expose un accès PDO partagé.
 */
abstract class Modele
{
    final protected static function pdo(): PDO
    {
        return BaseDeDonnees::pdo();
    }
}
