<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\HomeModel;

class HomeController
{
    public function index() : void
    {
        $lieuxPop    = HomeModel::classementLieux(6);
        $derniersAvis = HomeModel::derniersAvisLieux(5);

        View::render('home/index', [
            'lieuxPop'     => $lieuxPop,
            'derniersAvis'  => $derniersAvis,
            'pageTitre'     => 'Accueil',
        ], 'home');
    }
}
