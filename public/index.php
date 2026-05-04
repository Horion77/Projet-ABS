<?php

declare(strict_types=1);

// Point d'entrée unique (front controller) : Apache envoie toutes les URLs ici via public/.htaccess.
require dirname(__DIR__) . '/app/bootstrap.php';

(new \App\Core\Routeur(require APP_CONFIG . '/routes.php'))->dispatch();

var_dump($_SERVER['REQUEST_URI']);
die();
