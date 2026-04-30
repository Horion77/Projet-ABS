<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\AvisModel;

class AvisController
{
    public function listePage() : void
    {
        $liste = AvisModel::listePubliqueRecents(80);
        View::render('avis/index', [
            'liste'     => $liste,
            'pageTitre' => 'Avis récents',
        ], 'reviews');
    }

    public function traiterSoumission() : void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('../pages/avis.php');
        }

        if (!isLoggedIn()) {
            $_SESSION['errors'] = ['Vous devez être connecté pour publier un avis.'];
            redirect('../pages/login.php');
        }

        $idLieu = isset($_POST['id_lieu']) ? (int) $_POST['id_lieu'] : 0;
        $note   = isset($_POST['note']) ? (int) $_POST['note'] : 0;
        $desc   = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
        $vis    = isset($_POST['visibility']) && $_POST['visibility'] === 'prive' ? 'prive' : 'public';

        $retourPlace = $idLieu >= 1 ? '../pages/place.php?id=' . $idLieu : '../pages/map.php';

        if ($idLieu < 1) {
            $_SESSION['errors'] = ['Lieu invalide.'];
            redirect($retourPlace);
        }

        if (!AvisModel::lieuExiste($idLieu)) {
            $_SESSION['errors'] = ['Ce lieu n’existe pas.'];
            redirect($retourPlace);
        }

        if ($note < 1 || $note > 5) {
            $_SESSION['errors'] = ['La note doit être entre 1 et 5.'];
            redirect($retourPlace);
        }

        if (mb_strlen($desc) > 8000) {
            $_SESSION['errors'] = ['Le commentaire est trop long (8000 caractères max).'];
            redirect($retourPlace);
        }

        $uid = (int) $_SESSION['user_id'];

        if (AvisModel::utilisateurADejaAvisSurLieu($uid, $idLieu)) {
            $_SESSION['errors'] = ['Vous avez déjà publié un avis pour ce lieu.'];
            redirect($retourPlace);
        }

        try {
            AvisModel::creerPourLieu($uid, $idLieu, $note, $desc === '' ? null : $desc, $vis);
        } catch (\Throwable $e) {
            $_SESSION['errors'] = ['Impossible d’enregistrer l’avis pour le moment.'];
            redirect($retourPlace);
        }

        $_SESSION['success'] = $vis === 'public'
            ? 'Votre avis a été publié.'
            : 'Votre avis a été enregistré (privé).';
        redirect($retourPlace);
    }
}
