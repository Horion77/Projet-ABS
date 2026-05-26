<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Models\AccueilModel;

/**
 * Page d'accueil : top lieux + derniers avis publics.
 */
class AccueilController extends Controleur
{
    public function index(): void
    {
        $this->rendre('accueil/index', [
            'lieuxPop'     => AccueilModel::classementLieux(6),
            'derniersAvis' => AccueilModel::derniersAvisLieux(5),
            'pageTitre'    => 'Accueil',
            'bodyClass' => 'page-stars',
        ], 'home');
    }
}
