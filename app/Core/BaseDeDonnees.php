<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Connexion PDO partagée (singleton).
 * Une seule connexion ouverte par requête HTTP, réutilisée par tous les modèles.
 * La configuration vit dans app/Config/bdd.php (gitignored).
 */
class BaseDeDonnees
{
    // Singleton : null = pas encore initialisé, PDO = connexion prête.
    // static permet de partager l'instance entre tous les appels sans passer
    // l'objet en paramètre à chaque modèle.
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $fichier = APP_CONFIG . '/bdd.php';
            // On initialise $pdo à null avant le require : bdd.php doit l'écraser
            // avec une instance PDO. Si le fichier ne le fait pas (erreur de config),
            // la vérification instanceof ci-dessous lève une exception claire.
            // On utilise require (pas require_once) pour que la variable locale $pdo
            // soit bien assignée dans la portée courante à chaque exécution.
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
