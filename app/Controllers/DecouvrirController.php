<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Models\CategorieLieuModel;
use App\Models\LieuModel;
use App\Models\PaysModel;

/**
 * Page « Découvrir » : un formulaire à la TripAdvisor (type de lieu, note mini,
 * continent / pays / ville) qui renvoie une liste de lieux recommandés triés
 * par note. Remplace l'ancienne liste brute d'avis.
 */
class DecouvrirController extends Controleur
{
    public function index(): void
    {
        // Lecture des critères (tous optionnels)
        $categorie = trim((string) $this->requete->get('cat', ''));
        $continent = trim((string) $this->requete->get('continent', ''));
        $noteMin   = $this->requete->getInt('note', 0);
        $idPays    = $this->requete->getInt('pays', 0);
        $idVille   = $this->requete->getInt('ville', 0);

        if ($noteMin < 1 || $noteMin > 5) {
            $noteMin = 0;
        }

        // On ne lance la recherche que si l'utilisateur a soumis au moins un critère
        // (évite d'afficher 120 lieux au premier chargement).
        $aRecherche = $this->requete->get('cat') !== null
            || $this->requete->get('continent') !== null
            || $this->requete->getInt('note', 0) > 0
            || $this->requete->getInt('pays', 0) > 0
            || $this->requete->getInt('ville', 0) > 0;

        $resultats = $aRecherche
            ? LieuModel::rechercheDecouverte(
                $categorie !== '' ? $categorie : null,
                $noteMin,
                $continent !== '' ? $continent : null,
                $idPays > 0 ? $idPays : null,
                $idVille > 0 ? $idVille : null,
                60
            )
            : [];

        $this->rendre('decouvrir/index', [
            'resultats'   => $resultats,
            'aRecherche'  => $aRecherche,
            'categorie'   => $categorie,
            'continent'   => $continent,
            'noteMin'     => $noteMin,
            'idPays'      => $idPays,
            'idVille'     => $idVille,
            'categories'  => CategorieLieuModel::toutes(),
            'continents'  => LieuModel::continentsDisponibles(),
            'paysListe'   => PaysModel::listePourFiltre(),
            'villesListe' => LieuModel::villesPourFiltre(),
            'pageTitre'   => 'Découvrir',
        ], 'decouvrir');
    }
}
