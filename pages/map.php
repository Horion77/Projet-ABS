<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use App\Models\Database;

$pdo = Database::getPdo();

$places = [];
$countries = [];

$sql = 'SELECT
    l.id_lieu,
    l.nom AS name,
    l.latitude AS lat,
    l.longitude AS lng,
    p.nom AS country_name,
    p.id_pays,
    COALESCE(ROUND(AVG(a.note), 2), NULL) AS avg_rating
 FROM lieu l
 JOIN categorie_lieu cl ON cl.id_categorie = l.id_categorie
 JOIN ville vi ON vi.id_ville = l.id_ville
 JOIN pays p ON p.id_pays = vi.id_pays
 LEFT JOIN avis a ON a.id_lieu = l.id_lieu AND a.visibility = \'public\'
 WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL
 GROUP BY l.id_lieu, l.nom, l.latitude, l.longitude, p.nom, p.id_pays, cl.libelle, vi.nom';

$stmt = $pdo->query($sql);
if ($stmt) {
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$cStmt = $pdo->query(
    'SELECT DISTINCT p.id_pays, p.nom
     FROM pays p
     JOIN ville v ON v.id_pays = p.id_pays
     JOIN lieu l2 ON l2.id_ville = v.id_ville
     WHERE l2.latitude IS NOT NULL AND l2.longitude IS NOT NULL
     ORDER BY p.nom'
);
if ($cStmt) {
    $countries = $cStmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitre = 'Carte Interactive';
$fichierCssPage = 'map';
$prefixRacine = prefixRacine();

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= constant('JSON_INVALID_UTF8_SUBSTITUTE');
}

$mapData = [
    'token' => 'pk.eyJ1IjoiYnItIiwiYSI6ImNtb2Z2eWpvZTBrZmoycHNibTg3OHliNWoifQ.JIgFJdRqYhu8VejEf_uasA',
    'places' => $places,
    'countries' => $countries,
    'placePath' => 'place.php',
];

require __DIR__ . '/../app/Views/partials/head.php';
?>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" />

<div class="conteneur conteneur-map">
    <h1 class="titre-carte">Explorez les lieux sur la carte</h1>

    <div class="map-toolbar" role="region" aria-label="Filtrer la carte par pays">
        <label for="map-country-filter" class="map-filter-label">Pays</label>
        <select id="map-country-filter" class="map-country-select">
            <option value="">Tous les pays</option>
            <?php foreach ($countries as $c) : ?>
            <option value="<?= (int) $c['id_pays'] ?>"><?= e((string) $c['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="map" class="map-canvas" aria-label="Carte des lieux"></div>
</div>

<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js" defer></script>
<script>
window.MAP_DATA = <?= json_encode($mapData, $jsonFlags) ?>;
</script>
<script src="<?= e($prefixRacine) ?>assets/js/map.js" defer></script>

<?php
require __DIR__ . '/../app/Views/partials/foot.php';
