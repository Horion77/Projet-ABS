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
            'lieuxPop'     => AccueilModel::classementLieux(6),   // top 6 lieux par note moyenne
            'derniersAvis' => AccueilModel::derniersAvisLieux(5), // 5 derniers avis publics
            'pageTitre'    => 'Accueil',
            // page-stars : déclenche l'effet étoiles JS (home.css + stars.js)
            'bodyClass' => 'page-stars',
        // 'home' : troisième argument = nom du layout CSS chargé (resources/css/home.css)
        ], 'home');
    }
}
