<?php

declare(strict_types=1);

use App\Controllers\AccueilController;
use App\Controllers\AvisController;
use App\Controllers\AvisListeController;
use App\Controllers\CarteController;
use App\Controllers\CommentaireController;
use App\Controllers\ConnexionController;
use App\Controllers\DecouvrirController;
use App\Controllers\InscriptionController;
use App\Controllers\LieuController;
use App\Controllers\LikeController;
use App\Controllers\PaysController;
use App\Controllers\ProfilController;

/**
 * Table de routage : [METHODE_HTTP, CHEMIN, [Controleur::class, 'methode']].
 * Le chemin est comparé tel quel (ex. /lieu) ; les paramètres passent en ?id=… dans la requête.
 */

return [
    //['GET',  '/',             [AccueilController::class,     'index']],
    // ❌ Actuellement (chemin absolu complet, jamais matché par le routeur)
    //['GET', '/Projet-ABS/public/', [AccueilController::class, 'index']],

    // ✅ Ce que ça devrait être (chemin relatif après public/)
    ['GET', '/', [AccueilController::class, 'index']],

    ['GET',  '/connexion',    [ConnexionController::class,   'afficher']],
    ['POST', '/connexion',    [ConnexionController::class,   'traiterConnexion']],
    ['POST', '/deconnexion',  [ConnexionController::class,   'deconnecter']],

    ['GET',  '/inscription',  [InscriptionController::class, 'afficher']],
    ['POST', '/inscription',  [InscriptionController::class, 'traiterInscription']],

    ['GET',  '/profil',                [ProfilController::class,      'afficher']],
    ['POST', '/profil/modifier-infos',     [ProfilController::class,      'modifierInfos']],
    ['POST', '/profil/modifier-password',  [ProfilController::class,      'modifierPassword']],
    ['POST', '/profil/modifier-avatar',    [ProfilController::class,      'modifierAvatar']],

    ['GET',  '/carte',        [CarteController::class,       'index']],

    ['GET',  '/decouvrir',    [DecouvrirController::class,   'index']],

    ['GET',  '/lieu',         [LieuController::class,        'afficher']],
    ['POST', '/lieu/creer',   [LieuController::class,        'creer']],

    ['GET',  '/pays',         [PaysController::class,        'afficher']],

    ['GET',  '/avis',              [AvisListeController::class,   'index']],
    ['POST', '/avis',              [AvisController::class,        'traiterSoumission']],
    ['POST', '/avis/liker',        [LikeController::class,        'likerAvis']],

    ['POST', '/commentaire',       [CommentaireController::class, 'creer']],
    ['POST', '/commentaire/liker', [CommentaireController::class, 'liker']],
];
