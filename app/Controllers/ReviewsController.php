<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\AvisModel;
use App\Models\PaysModel;

class ReviewsController
{
    private const PAR_PAGE = 10;

    public function index() : void
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $filtreNote = null;
        if (isset($_GET['rating']) && $_GET['rating'] !== '') {
            $r = (int) $_GET['rating'];
            if ($r >= 1 && $r <= 5) {
                $filtreNote = $r;
            }
        }

        $filtrePays = null;
        if (isset($_GET['country_id']) && $_GET['country_id'] !== '') {
            $c = (int) $_GET['country_id'];
            if ($c > 0) {
                $filtrePays = $c;
            }
        }

        $total  = AvisModel::compterPublicsLieux($filtreNote, $filtrePays);
        $pages  = (int) max(1, (int) ceil($total / self::PAR_PAGE));
        if ($page > $pages) {
            $page = $pages;
        }

        $liste     = AvisModel::listePublicsLieuxPaginated($page, self::PAR_PAGE, $filtreNote, $filtrePays);
        $paysListe = PaysModel::listePourFiltre();

        View::render('reviews/index', [
            'liste'       => $liste,
            'page'        => $page,
            'pagesTotal'  => $pages,
            'totalAvis'   => $total,
            'parPage'     => self::PAR_PAGE,
            'filtreNote'  => $filtreNote,
            'filtrePays'  => $filtrePays,
            'paysListe'   => $paysListe,
            'pageTitre'   => 'Tous les avis',
        ], 'reviews');
    }
}
