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
use App\Controllers\SignalementController;

/**
 * Table de routage de l'application.
 *
 * Format : [METHODE_HTTP, CHEMIN, [Controleur::class, 'methode']]
 *
 * Conventions importantes :
 *   - Le chemin est comparé après suppression du préfixe base_url (ex. /Projet-ABS/public).
 *     On déclare donc '/lieu' et non '/Projet-ABS/public/lieu'.
 *   - Les paramètres dynamiques (id, filtres) ne sont PAS dans le chemin (pas de :id).
 *     Ils transitent par la query string (?id=…) et sont lus via $_GET dans le contrôleur.
 *   - La vérification d'authentification et des droits (admin, connecté…) est faite
 *     dans le contrôleur, pas dans ce fichier.
 */

return [
    //['GET',  '/',             [AccueilController::class,     'index']],
    // ❌ Ce chemin absolu n'est jamais matché par le routeur (inclut le préfixe MAMP)
    //['GET', '/Projet-ABS/public/', [AccueilController::class, 'index']],

    // ✅ Chemin relatif correct après suppression de base_url
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

    // GET /avis  → liste paginée des avis (AvisListeController)
    // POST /avis → soumission d'un nouvel avis (AvisController)
    // Même chemin, deux contrôleurs distincts selon la méthode HTTP.
    ['GET',  '/avis',              [AvisListeController::class,   'index']],
    ['POST', '/avis',              [AvisController::class,        'traiterSoumission']],
    ['POST', '/avis/liker',        [LikeController::class,        'likerAvis']],

    ['POST', '/commentaire',       [CommentaireController::class, 'creer']],
    ['POST', '/commentaire/liker', [CommentaireController::class, 'liker']],

    // Signalements (utilisateur connecté) + modération (admin).
    // Le préfixe /admin/ est une convention de nommage : il n'y a pas de middleware
    // d'authentification au niveau du routeur. C'est SignalementController qui vérifie
    // que l'utilisateur est admin avant d'afficher ou de traiter les signalements.
    ['POST', '/signalement',              [SignalementController::class, 'creer']],
    ['GET',  '/admin/signalements',       [SignalementController::class, 'index']],
    ['POST', '/admin/signalements/traiter',[SignalementController::class, 'traiter']],
];
