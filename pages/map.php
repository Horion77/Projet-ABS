<?php
require __DIR__ . '/../app/bootstrap.php';
use App\Models\Database;

// 1. Récupération des lieux depuis la base de données
$pdo = Database::getPdo();
$stmt = $pdo->query("SELECT id_lieu, lieu AS name, latitude AS lat, longitude AS lng, pays AS country_name, note_moyenne AS avg_rating FROM vue_classement_lieux");
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables pour le layout global
$pageTitre = 'Carte Interactive';
$fichierCssPage = 'map'; 

// 2. INCLUSION DU HAUT DE LA PAGE (Navbar + CSS globaux)
require __DIR__ . '/../app/Views/partials/head.php';
?>

<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />

<div class="conteneur">
    <h1>Explorez les lieux sur la carte</h1>
    
    <div id="map" style="height: 70vh; width: 100%; border-radius: 8px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
</div>

<script>
    const mapboxToken = 'pk.eyJ1IjoiYnItIiwiYSI6ImNtb2Z2eWpvZTBrZmoycHNibTg3OHliNWoifQ.JIgFJdRqYhu8VejEf_uasA';
    const placesData = <?= json_encode($places) ?>;
</script>

<script src="../assets/js/map.js"></script>

<?php
// 4. INCLUSION DU BAS DE LA PAGE (Footer)
require __DIR__ . '/../app/Views/partials/foot.php';
?>