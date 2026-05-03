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
        (new ReviewController())->traiterSoumissionPlace();
    }
}
