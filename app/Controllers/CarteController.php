<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Models\LieuModel;

/**
 * Carte interactive Mapbox : récupère les lieux géolocalisés et le jeton.
 * Logique extraite de l'ancien pages/map.php.
 */
class CarteController extends Controleur
{
    public function index(): void
    {
        $places    = LieuModel::tousAvecNotes();
        $countries = LieuModel::paysPourFiltreCarte();

        $this->rendre('carte/index', [
            'places'      => $places,
            'countries'   => $countries,
            'mapboxToken' => $this->resoudreTokenMapbox(),
            'pageTitre'   => 'Carte Interactive',
        ], 'map');
    }

    /** Ordre : variable d'environnement → fichier mapbox.php → jeton de secours (démo locale uniquement). */
    private function resoudreTokenMapbox(): string
    {
        $token = getenv('MAPBOX_TOKEN');
        if (!is_string($token) || trim($token) === '') {
            $token = '';
            $fichier = APP_CONFIG . '/mapbox.php';
            if (is_file($fichier)) {
                $loaded = require $fichier;
                if (is_string($loaded)) {
                    $token = trim($loaded);
                }
            }
        }
        if ($token === '' || $token === 'VOTRE_TOKEN_MAPBOX_PUBLIC_ICI') {
            // Jeton de secours pour démo locale ; préférer MAPBOX_TOKEN ou app/Config/mapbox.php.
            $token = 'pk.eyJ1IjoiYnItIiwiYSI6ImNtb2Z2eWpvZTBrZmoycHNibTg3OHliNWoifQ.JIgFJdRqYhu8VejEf_uasA';
        }
        return $token;
    }
}
