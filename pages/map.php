<?php
// 1. LES LIGNES MAGIQUES (Affichent les erreurs au lieu d'une page blanche)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../app/bootstrap.php';
// Sécurité : on force le chargement des fonctions si bootstrap ne le fait pas
require_once __DIR__ . '/../includes/functions.php'; 

use App\Models\Database;

// 2. RÉCUPÉRATION SÉCURISÉE DES DONNÉES
try {
    $pdo = Database::getPdo();
    // On utilise les vraies tables au lieu de la vue, pour être 100% sûr que ça marche
    $sql = "SELECT l.id_lieu, l.nom AS name, l.latitude AS lat, l.longitude AS lng, p.nom AS country_name 
            FROM lieu l
            JOIN ville v ON l.id_ville = v.id_ville
            JOIN pays p ON v.id_pays = p.id_pays
            WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL";
            
    $stmt = $pdo->query($sql);
    $places = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    // Si la base de données plante, on affiche l'erreur proprement
    die("<div style='color:red; padding:20px;'>Erreur de base de données : " . $e->getMessage() . "</div>");
}

$pageTitre = 'Carte Interactive';
$fichierCssPage = 'map';

// 3. INCLUSION DU HEADER
require __DIR__ . '/../app/Views/partials/head.php';
?>

<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />

<div class="conteneur">
    <h1>Explorez les lieux sur la carte</h1>
    
    <div id="map" style="height: 70vh; width: 100%; border-radius: 8px; margin-top: 20px; background-color: #e5e5e5; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
</div>

<script>
    const mapboxToken = 'pk.eyJ1IjoiYnItIiwiYSI6ImNtb2Z2eWpvZTBrZmoycHNibTg3OHliNWoifQ.JIgFJdRqYhu8VejEf_uasA';
    const placesData = <?= json_encode($places) ?>;
    
    // Astuce de pro : on affiche les données dans la console pour vérifier que PHP a bien travaillé
    console.log("Lieux chargés depuis PHP :", placesData);
</script>

<script src="../assets/js/map.js"></script>

<?php
// 5. INCLUSION DU FOOTER
require __DIR__ . '/../app/Views/partials/foot.php';
?>