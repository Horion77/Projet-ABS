<?php
namespace App\Controllers;

use App\Models\AvisModel;

/**
 * Soumission d’avis (plan Sara : place_id, rating, title, comment).
 * Compatible avec l’ancien flux (id_lieu, note, description optionnelle).
 */
class ReviewController
{
    public function traiterSoumissionPlace() : void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('../pages/reviews.php');
        }

        if (!isLoggedIn()) {
            $_SESSION['errors'] = ['Vous devez être connecté pour publier un avis.'];
            redirect('../pages/login.php');
        }

        $idLieu = isset($_POST['place_id']) ? (int) $_POST['place_id'] : (isset($_POST['id_lieu']) ? (int) $_POST['id_lieu'] : 0);
        $note   = isset($_POST['rating']) ? (int) $_POST['rating'] : (isset($_POST['note']) ? (int) $_POST['note'] : 0);

        $comment = trim((string) ($_POST['comment'] ?? ''));
        if ($comment === '') {
            $comment = trim((string) ($_POST['description'] ?? ''));
        }

        $titre = isset($_POST['title']) ? trim((string) $_POST['title']) : '';
        if ($titre === '') {
            $titre = null;
        } else {
            $titre = mb_substr($titre, 0, 200);
        }

        $vis = isset($_POST['visibility']) && $_POST['visibility'] === 'prive' ? 'prive' : 'public';

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

        if ($comment === '') {
            $_SESSION['errors'] = ['Le commentaire est obligatoire.'];
            redirect($retourPlace);
        }

        if (mb_strlen($comment) > 8000) {
            $_SESSION['errors'] = ['Le commentaire est trop long (8000 caractères max).'];
            redirect($retourPlace);
        }

        $uid = (int) $_SESSION['user_id'];

        if (AvisModel::utilisateurADejaAvisSurLieu($uid, $idLieu)) {
            $_SESSION['errors'] = ['Vous avez déjà publié un avis pour ce lieu.'];
            redirect($retourPlace);
        }

        try {
            AvisModel::creerPourLieu($uid, $idLieu, $note, $comment, $vis, $titre);
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
