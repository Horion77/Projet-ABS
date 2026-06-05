<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use Throwable;

/**
 * Traitement du formulaire de soumission d'avis (POST /avis).
 *
 * Double compatibilité de nommage des champs :
 *   place_id / rating / title / comment  → noms utilisés par l'ancien code Sara
 *   id_lieu  / note   / titre / description → noms MVC courants
 * Les deux conventions sont acceptées pour éviter de casser les formulaires
 * existants pendant la migration.
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

        // URL de retour définie tôt : toutes les redirections d'erreur pointent vers
        // la fiche lieu si l'id est valide, ou vers la carte en dernier recours.
        $retour = $idLieu >= 1 ? '/lieu?id=' . $idLieu : '/carte';

        // Valider les données avant enregistrement.
        if ($idLieu < 1) {
            Session::flashErreurs(['Lieu invalide.']);
            $this->rediriger($retour);
        }

        if (!AvisModel::lieuExiste($idLieu)) {
            Session::flashErreurs(['Ce lieu n'existe pas.']);
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
            AvisModel::creerPourLieu($uid, $idLieu, $note, $comment, $vis, $titre);
        } catch (Throwable) {
            // Throwable capture aussi bien les Exception que les Error PHP 8
            // (ex. PDOException sur contrainte d'unicité non détectée en amont).
            Session::flashErreurs(['Impossible d'enregistrer l'avis pour le moment.']);
            $this->rediriger($retour);
        }

        Session::flashSucces($vis === 'public'
            ? 'Votre avis a bien été enregistré !'
            : 'Votre avis a été enregistré (privé).');
        $this->rediriger($retour);
    }
}
