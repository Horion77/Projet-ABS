<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Reponse;
use App\Models\LikeModel;
use App\Core\Session;

/**
 * Toggle like sur un avis. Répond en JSON.
 */
class LikeController extends Controleur
{

    /**
     * POST /avis/liker — Toggle like sur un avis, réponse JSON.
     * Appelé en fetch() depuis le JS de la fiche lieu (place.css / stars interactive).
     * toggleAvis() insère si absent, supprime si présent (idempotent).
     * La réponse contient 'liked' (nouvel état) et 'count' (total recalculé)
     * pour que le JS mette à jour le compteur sans rechargement.
     */
    public function likerAvis(): never
    {
        $this->exigerConnexion('connexion');

        $idAvis = $this->requete->postInt('id_avis');
        $user   = Session::utilisateur();

        $liked = LikeModel::toggleAvis((int) $user['id'], $idAvis);
        $count = LikeModel::compterAvis($idAvis);

        Reponse::json(['liked' => $liked, 'count' => $count]);
    }

}
