<?php

declare(strict_types=1);

use App\Controllers\AccueilController;
use App\Controllers\AvisController;
use App\Controllers\AvisListeController;
use App\Controllers\CarteController;
use App\Controllers\ConnexionController;
use App\Controllers\InscriptionController;
use App\Controllers\LieuController;
use App\Controllers\PaysController;
use App\Controllers\ProfilController;

/**
 * Table de routage : [METHODE_HTTP, CHEMIN, [Controleur::class, 'methode']].
 * Le chemin est comparé tel quel (ex. /lieu) ; les paramètres passent en ?id=… dans la requête.
 */

return [
    ['GET',  '/',             [AccueilController::class,     'index']],

    ['GET',  '/connexion',    [ConnexionController::class,   'afficher']],
    ['POST', '/connexion',    [ConnexionController::class,   'traiterConnexion']],
    ['POST', '/deconnexion',  [ConnexionController::class,   'deconnecter']],

    ['GET',  '/inscription',  [InscriptionController::class, 'afficher']],
    ['POST', '/inscription',  [InscriptionController::class, 'traiterInscription']],

    ['GET',  '/profil',       [ProfilController::class,      'afficher']],

    ['GET',  '/carte',        [CarteController::class,       'index']],

    ['GET',  '/lieu',         [LieuController::class,        'afficher']],

    ['GET',  '/pays',         [PaysController::class,        'afficher']],

    ['GET',  '/avis',         [AvisListeController::class,   'index']],
    ['POST', '/avis',         [AvisController::class,        'traiterSoumission']],
];
