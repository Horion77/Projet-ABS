<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Models\PaysModel;

/**
 * Fiche pays : liste des lieux du pays avec leur note moyenne.
 */
class PaysController extends Controleur
{
    public function afficher(): void
    {
        $id     = $this->requete->getInt('id');
        $pays   = null;
        $lieux  = [];
        $erreur = null;

        if ($id < 1) {
            $erreur = 'Identifiant de pays invalide.';
        } else {
            $pays = PaysModel::parId($id);
            if (!$pays) {
                $erreur = 'Ce pays n’existe pas ou n’est plus disponible.';
            } else {
                $lieux = PaysModel::lieuxAvecNotes($id);
            }
        }

        $this->rendre('pays/afficher', [
            'pays'      => $pays,
            'lieux'     => $lieux,
            'erreur'    => $erreur,
            'pageTitre' => $pays['nom'] ?? 'Pays',
        ], 'reviews');
    }
}
