<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Models\AvisModel;
use App\Models\PaysModel;

/**
 * Liste des avis publics : pagination + filtres (note, pays).
 */
class AvisListeController extends Controleur
{
    private const PAR_PAGE = 10;

    public function index(): void
    {
        $page = max(1, $this->requete->getInt('page', 1));

        $filtreNote = null;
        $r = $this->requete->getInt('rating', 0);
        if ($r >= 1 && $r <= 5) {
            $filtreNote = $r;
        }

        $filtrePays = null;
        $c = $this->requete->getInt('country_id', 0);
        if ($c > 0) {
            $filtrePays = $c;
        }

        $total = AvisModel::compterPublicsLieux($filtreNote, $filtrePays);
        $pages = (int) max(1, (int) ceil($total / self::PAR_PAGE));
        // Évite page=999 quand il n'y a qu'une page (ex. après filtre).
        if ($page > $pages) {
            $page = $pages;
        }

        $this->rendre('avis/index', [
            'liste'      => AvisModel::listePublicsLieuxPaginated($page, self::PAR_PAGE, $filtreNote, $filtrePays),
            'page'       => $page,
            'pagesTotal' => $pages,
            'totalAvis'  => $total,
            'parPage'    => self::PAR_PAGE,
            'filtreNote' => $filtreNote,
            'filtrePays' => $filtrePays,
            'paysListe'  => PaysModel::listePourFiltre(),
            'pageTitre'  => 'Tous les avis',
        ], 'reviews');
    }
}
