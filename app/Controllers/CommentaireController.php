<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Reponse;
use App\Models\AvisModel;
use App\Models\CommentaireModel;
use App\Models\LikeModel;
use App\Core\Session;

/**
 * Gère la création de commentaires et les likes sur commentaires.
 */
class CommentaireController extends Controleur
{

    /**
     * POST /commentaire
     * Crée un commentaire (ou une réponse) sur un avis.
     * Redirige vers la fiche lieu après soumission.
     */
    public function creer(): never
    {
        $this->exigerConnexion('connexion');

        $idAvis    = $this->requete->postInt('id_avis');
        $idParent  = $this->requete->postInt('id_parent') ?: null;
        $texte     = trim($this->requete->postString('texte'));
        $idLieu    = $this->requete->postInt('id_lieu');

        // Validations basiques
        if ($texte === '' || mb_strlen($texte) > 2000) {
            Session::flashErreurs(['Le commentaire doit faire entre 1 et 2000 caractères.']);
            $this->rediriger('lieu?id=' . $idLieu);
        }

        if (!AvisModel::lieuExiste($idLieu)) {
            Reponse::notFound('Avis introuvable.');
        }

        // Si c'est une réponse, on vérifie que le commentaire parent existe
        if ($idParent !== null && !CommentaireModel::existe($idParent)) {
            $idParent = null;
        }

        $user = Session::utilisateur();
        CommentaireModel::creer($idAvis, (int) $user['id'], $texte, $idParent);

        $this->rediriger('lieu?id=' . $idLieu . '#avis-' . $idAvis);
    }


    /**
     * POST /commentaire/liker
     * Toggle like sur un commentaire. Répond en JSON.
     */
    public function liker(): never
    {
        $this->exigerConnexion('connexion');

        $idCommentaire = $this->requete->postInt('id_commentaire');
        $user          = Session::utilisateur();

        $liked = LikeModel::toggleCommentaire((int) $user['id'], $idCommentaire);
        $count = LikeModel::compterCommentaire($idCommentaire);

        Reponse::json(['liked' => $liked, 'count' => $count]);
    }

}
