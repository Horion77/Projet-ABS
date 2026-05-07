<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use Throwable;

/**
 * Traitement formulaire avis adapté depuis `Projet-ABS-Sara/actions/avis_action.php`.
 * Compatible avec les anciens formulaires (place_id/rating/title/comment ET id_lieu/note/description).
 */
class AvisController extends Controleur
{
    public function traiterSoumission(): void
    {
        // Vérifier que la requête est bien un POST (formulaire envoyé).
        if (!$this->requete->estPost()) {
            $this->rediriger('/avis');
        }

        // Utilisateur connecté ou pas.
        if (!Session::estConnecte()) {
            Session::flashErreurs(['Vous devez être connecté pour publier un avis.']);
            $this->rediriger('/connexion');
        }

        // Récupération des champs Sara (place_id/rating/title/comment), avec compatibilité MVC.
        $idLieu = $this->requete->postInt('place_id', $this->requete->postInt('id_lieu'));
        $note   = $this->requete->postInt('rating', $this->requete->postInt('note'));

        $comment = $this->requete->postString('comment');
        if ($comment === '') {
            $comment = $this->requete->postString('description');
        }

        $titre = $this->requete->postString('title');
        $titre = $titre === '' ? null : mb_substr($titre, 0, 200);

        $vis = $this->requete->post('visibility') === 'prive' ? 'prive' : 'public';

        $retour = $idLieu >= 1 ? '/lieu?id=' . $idLieu : '/carte';

        // Valider les données avant enregistrement.
        if ($idLieu < 1) {
            Session::flashErreurs(['Lieu invalide.']);
            $this->rediriger($retour);
        }

        if (!AvisModel::lieuExiste($idLieu)) {
            Session::flashErreurs(['Ce lieu n’existe pas.']);
            $this->rediriger($retour);
        }

        if ($note < 1 || $note > 5) {
            Session::flashErreurs(['La note doit être entre 1 et 5.']);
            $this->rediriger($retour);
        }

        if ($comment === '') {
            Session::flashErreurs(['Le commentaire est obligatoire.']);
            $this->rediriger($retour);
        }

        if (mb_strlen($comment) > 8000) {
            Session::flashErreurs(['Le commentaire est trop long (8000 caractères max).']);
            $this->rediriger($retour);
        }

        $uid = (int) ($_SESSION['user_id'] ?? 0);

        if (AvisModel::utilisateurADejaAvisSurLieu($uid, $idLieu)) {
            Session::flashErreurs(['Vous avez déjà publié un avis pour ce lieu.']);
            $this->rediriger($retour);
        }

        try {
            // Enregistre l'avis dans la base de données via le Model MVC (`avis`, pas `reviews`).
            AvisModel::creerPourLieu($uid, $idLieu, $note, $comment, $vis, $titre);
        } catch (Throwable) {
            Session::flashErreurs(['Impossible d’enregistrer l’avis pour le moment.']);
            $this->rediriger($retour);
        }

        Session::flashSucces($vis === 'public'
            ? 'Votre avis a bien été enregistré !'
            : 'Votre avis a été enregistré (privé).');
        $this->rediriger($retour);
    }
}
