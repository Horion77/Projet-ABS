<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use App\Models\CategorieLieuModel;
use App\Models\LieuModel;

/**
 * Carte interactive Mapbox : récupère les lieux géolocalisés et le jeton.
 * Logique extraite de l'ancien pages/map.php.
 */
class CarteController extends Controleur
{
    public function index(): void
    {
        $places     = LieuModel::tousAvecNotes();
        $countries  = LieuModel::paysPourFiltreCarte();
        $categories = CategorieLieuModel::toutes();

        // Fusionner les derniers avis dans chaque pays (pré-chargé → panneau instantané)
        $derniersAvisParPays = AvisModel::derniersAvisParPays(3);
        foreach ($countries as &$pays) {
            $idPays = (int) $pays['id_pays'];
            $pays['derniers_avis'] = $derniersAvisParPays[$idPays] ?? [];
        }
        unset($pays);

        // Pour le filtre « Mes avis » : liste des id_lieu où l'utilisateur connecté
        // a déjà laissé un avis. Tableau vide si invité (le filtre est alors
        // simplement inutile, le JS masque l'option).
        $myReviewedLieux = Session::estConnecte()
            ? AvisModel::idsLieuxParUtilisateur((int) ($_SESSION['user_id'] ?? 0))
            : [];

        // Noms FR de tous les pays (fallback pour pays sans avis dans la BDD)
        $paysNoms = require APP_CONFIG . '/pays_iso_noms.php';

        $this->rendre('carte/index', [
            'places'          => $places,
            'countries'       => $countries,
            'categories'      => $categories,
            'myReviewedLieux' => $myReviewedLieux,
            'paysNoms'        => $paysNoms,
            'mapboxToken'     => $this->resoudreTokenMapbox(),
            'pageTitre'       => 'Carte Interactive',
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
