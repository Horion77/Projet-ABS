<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use App\Models\CategorieLieuModel;
use App\Models\LieuModel;

/**
 * CarteController — gère la page de la carte interactive Mapbox.
 *
 * Son seul rôle : récupérer toutes les données nécessaires depuis la base
 * (lieux, pays, catégories, token Mapbox...) et les passer à la vue.
 * C'est ensuite map.js qui fait tout le travail visuel côté navigateur.
 */
class CarteController extends Controleur
{
    /**
     * Point d'entrée : GET /carte
     * On charge tout ici en une seule requête HTTP pour éviter des appels AJAX
     * supplémentaires au chargement — la carte s'affiche directement avec ses données.
     */
    public function index(): void
    {
        // Tous les lieux géolocalisés avec leur note moyenne (LEFT JOIN avis)
        // → devient MAP_DATA.places en JS, utilisé pour dessiner les pins
        $places     = LieuModel::tousAvecNotes();

        // Pays qui ont au moins un lieu + stats (note, nb lieux, centre géo)
        // → utilisé pour le filtre et les mini-cards au survol
        $countries  = LieuModel::paysPourFiltreCarte();

        // Liste des catégories (Musée, Restaurant...) pour les chips de filtre
        $categories = CategorieLieuModel::toutes();

        // Pré-charge les 3 derniers avis de chaque pays en une seule requête groupée,
        // puis les fusionne dans $countries pour éviter N requêtes dans la vue.
        // Les données sont sérialisées en JSON et injectées dans window.MAP_DATA côté JS :
        // le panneau pays s'ouvre instantanément sans requête AJAX supplémentaire.
        $derniersAvisParPays = AvisModel::derniersAvisParPays(3);
        foreach ($countries as &$pays) {
            $idPays = (int) $pays['id_pays'];
            // ?? [] = tableau vide si ce pays n'a aucun avis encore
            $pays['derniers_avis'] = $derniersAvisParPays[$idPays] ?? [];
        }
        // unset obligatoire : $pays est une référence (& dans le foreach).
        // Sans cet unset, $pays continuerait de pointer sur le dernier élément
        // et une affectation ultérieure à $pays écraserait le tableau.
        unset($pays);

        // Pour le filtre "Mes avis" : on envoie au JS la liste des id_lieu
        // où l'utilisateur connecté a déjà posté un avis.
        // Si l'utilisateur n'est pas connecté → tableau vide (le JS cache l'option)
        $myReviewedLieux = Session::estConnecte()
            ? AvisModel::idsLieuxParUtilisateur((int) ($_SESSION['user_id'] ?? 0))
            : [];

        // Table ISO3 → nom FR de tous les pays du monde
        // Sert de fallback pour afficher le nom d'un pays pas encore dans la BDD
        $paysNoms = require APP_CONFIG . '/pays_iso_noms.php';

        // On rend la vue 'carte/index' avec le layout 'map'
        // Toutes ces variables sont encodées en JSON dans la vue (window.MAP_DATA)
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

    /**
     * Résoudre le token Mapbox selon priorité :
     * 1. Variable d'environnement MAPBOX_TOKEN (prod)
     * 2. Fichier app/Config/mapbox.php (dev local, gitignored)
     * 3. Token de secours codé en dur (démo uniquement, à remplacer en prod)
     */
    private function resoudreTokenMapbox(): string
    {
        // D'abord on regarde la variable d'env (la plus propre en production)
        $token = getenv('MAPBOX_TOKEN');

        if (!is_string($token) || trim($token) === '') {
            // Sinon on regarde le fichier local mapbox.php (non versionné)
            $token   = '';
            $fichier = APP_CONFIG . '/mapbox.php';
            if (is_file($fichier)) {
                $loaded = require $fichier;
                if (is_string($loaded)) {
                    $token = trim($loaded);
                }
            }
        }

        // Si on n'a toujours rien, token de secours pour la démo locale
        // (quota limité, ne pas utiliser en prod)
        if ($token === '' || $token === 'VOTRE_TOKE') {
            $token = 'pk.eyJ1IjoiYnItIiwiYSI6ImNtb2Z2eWpvZTBrZmoycHNibTg3OHliNWoifQ.JIgFJdRqYhu8VejEf_uasA';
        }

        return $token;
    }
}
