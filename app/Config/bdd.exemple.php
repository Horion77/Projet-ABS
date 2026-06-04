<?php
/**
 * Modèle de configuration PDO.
 * Copier en bdd.php et adapter aux identifiants locaux (bdd.php n'est pas versionné).
 * Ce fichier doit définir la variable $pdo (lue par Core/BaseDeDonnees.php).
 *
 * Étapes :
 *   cp app/Config/bdd.exemple.php app/Config/bdd.php
 *   # puis éditer bdd.php avec les vraies valeurs
 */

$host     = '127.0.0.1';
$dbname   = 'abs_db';
$user     = 'root';
$pass     = 'root';
$charset  = 'utf8mb4'; // utf8mb4 supporte les emojis et tous les caractères Unicode

$options = [
    // Lance une PDOException sur toute erreur SQL → pas d'erreur silencieuse.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Retourne les lignes sous forme de tableaux associatifs (clé = nom de colonne).
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Délègue la préparation à MySQL (vraies requêtes préparées, pas simulées) :
    // protection réelle contre les injections SQL et typage correct des paramètres.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Note : pas de port explicite ici → MySQL standard (3306).
// Pour MAMP, ajouter ;port=8889 dans le DSN (voir bdd.php).
$dsn = "mysql:host={$host};charset={$charset};dbname={$dbname}";

$pdo = new PDO($dsn, $user, $pass, $options);
