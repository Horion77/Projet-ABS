<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use App\Models\LieuModel;

/**
 * Fiche d'un lieu : description, image, note moyenne, formulaire d'avis, avis publics.
 * Logique extraite de l'ancien pages/place.php.
 */
class LieuController extends Controleur
{
    public function afficher(): void
    {
        $id = $this->requete->getInt('id');

        $lieu       = null;
        $avis       = [];
        $noteMoy    = null;
        $erreur     = null;
        $dejaAvis   = false;

        if ($id < 1) {
            $erreur = 'Identifiant de lieu invalide.';
        } else {
            $lieu = LieuModel::trouverParIdAvecLocalisation($id);
            if (!$lieu) {
                $erreur = 'Ce lieu n’existe pas ou n’est plus disponible.';
            } else {
                $avis    = AvisModel::publicsParLieu($id);
                $stats   = AvisModel::statsParLieu($id);
                $noteMoy = $stats['n'] > 0 ? $stats['moy'] : null;

                if (Session::estConnecte()) {
                    $dejaAvis = AvisModel::utilisateurADejaAvisSurLieu(
                        (int) ($_SESSION['user_id'] ?? 0),
                        $id
                    );
                }
            }
        }

        $this->rendre('lieu/afficher', [
            'lieu'      => $lieu,
            'avis'      => $avis,
            'noteMoy'   => $noteMoy,
            'erreur'    => $erreur,
            'dejaAvis'  => $dejaAvis,
            'pageTitre' => $lieu['nom'] ?? 'Lieu',
        ], 'place');
    }
}
