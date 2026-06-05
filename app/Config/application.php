<?php

declare(strict_types=1);

/**
 * Configuration générale de l'application.
 * Chargé une fois par le bootstrap (index.php) et accessible via la config globale.
 *
 * ⚠️  base_url est hardcodée pour le développement local avec MAMP.
 *     En production, la remplacer par '' (racine du domaine) ou utiliser
 *     la variable d'environnement APP_BASE_URL.
 */

return [
    'nom'      => 'ABS',
    // Lit APP_ENV depuis l'environnement (variable serveur ou .env) ;
    // retombe sur 'dev' si non définie — évite de crasher sur un poste sans config.
    'env'      => getenv('APP_ENV') ?: 'dev',
    // Préfixe commun à toutes les URL générées : utilisé par le routeur pour
    // ignorer ce segment dans le chemin de la requête entrante.
    'base_url' => '/real-abs/public',
];
