<?php
/**
 * Carte interactive Mapbox — variables : $places, $countries, $mapboxToken
 */
$places      = $places      ?? [];
$countries   = $countries   ?? [];
$mapboxToken = $mapboxToken ?? '';

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= constant('JSON_INVALID_UTF8_SUBSTITUTE');
}

$mapData = [
    'token'     => $mapboxToken,
    'places'    => $places,
    'countries' => $countries,
    'placePath' => url('lieu'),
];
?>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css">

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
<script src="<?= e(asset('js/map.js')) ?>" defer></script>
