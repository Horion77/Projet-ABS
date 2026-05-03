<?php
namespace App\Controllers;

use App\Models\PaysModel;

/**
 * Fiche pays : liste des lieux avec notes (plan Sara : country.php).
 */
class CountryController
{
    public function afficher() : void
    {
        $prefixRacine = prefixRacine();
        $id            = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $pageTitre     = 'Pays';
        $fichierCssPage = 'reviews';
        $pays          = null;
        $lieux         = [];
        $erreur        = null;

        if ($id < 1) {
            $erreur = 'Identifiant de pays invalide.';
        } else {
            $pays = PaysModel::parId($id);
            if (!$pays) {
                $erreur = 'Ce pays n’existe pas ou n’est plus disponible.';
            } else {
                $pageTitre = (string) $pays['nom'];
                $lieux     = PaysModel::lieuxAvecNotes($id);
            }
        }

        require __DIR__ . '/../Views/partials/head.php';
        require __DIR__ . '/../Views/country/show.php';
        require __DIR__ . '/../Views/partials/foot.php';
    }
}
