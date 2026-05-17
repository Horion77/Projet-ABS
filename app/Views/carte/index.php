<?php
/**
 * Carte 3D Mapbox — variables : $places, $countries, $mapboxToken
 */
$places      = $places      ?? [];
$countries   = $countries   ?? [];
$mapboxToken = $mapboxToken ?? '';

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= constant('JSON_INVALID_UTF8_SUBSTITUTE');
}

$mapData = [
    'token'       => $mapboxToken,
    'places'      => $places,
    'countries'   => $countries,
    'placePath'   => url('lieu'),
    'paysPath'    => url('pays'),
    'regionsPath' => url('assets/data/regions.json'),
];
$nbLieux = count($places);
?>
<!-- Font Inter + Mapbox -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.css" rel="stylesheet">

<!-- Ajoute la classe map-page au body pour les overrides CSS -->
<script>document.body.classList.add('map-page');</script>

<div id="map-wrapper">

    <!-- Barre de contrôles (haut) -->
    <div id="map-controls">
        <div id="map-controls-inner">
            <h1 id="map-title">Explorer le monde</h1>

            <!-- Filtre par pays -->
            <div id="country-filter-wrap">
                <label for="country-filter">Filtrer par pays</label>
                <select id="country-filter">
                    <option value="">Tous les pays</option>
                    <?php foreach ($countries as $c) : ?>
                    <option value="<?= (int) $c['id_pays'] ?>"
                            data-lat="<?= e((string) ($c['lat'] ?? '0')) ?>"
                            data-lng="<?= e((string) ($c['lng'] ?? '0')) ?>">
                        <?= e((string) $c['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Switcher de styles -->
            <div id="style-switcher">
                <button class="style-btn active" data-style="dark">Sombre</button>
                <button class="style-btn" data-style="satellite">Satellite</button>
                <button class="style-btn" data-style="outdoors">Terrain</button>
                <button class="style-btn" data-style="streets">Rues</button>
            </div>

            <!-- Compteur -->
            <div id="places-count">
                <span id="count-number"><?= $nbLieux ?></span> lieu<?= $nbLieux > 1 ? 'x' : '' ?>
            </div>
        </div>
    </div>

    <!-- Panneau navigation gauche -->
    <div id="nav-panel">
        <button id="nav-world" class="nav-btn nav-world-btn">Monde entier</button>

        <div class="nav-section">
            <span class="nav-label">Continents</span>
            <div id="nav-continents"></div>
        </div>

        <div class="nav-section">
            <span class="nav-label">Pays</span>
            <div id="nav-countries"></div>
        </div>
    </div>

    <!-- Carte Mapbox -->
    <div id="map"></div>

    <!-- Légende couleurs des marqueurs -->
    <aside id="legende-carte" class="legende-carte" aria-label="Légende des marqueurs">
        <h4>Types de lieux</h4>
        <ul>
            <li><span class="leg-puce leg-pays"></span>Pays</li>
            <li><span class="leg-puce leg-ville"></span>Ville</li>
            <li><span class="leg-puce leg-monument"></span>Monument</li>
        </ul>
    </aside>

    <!-- Mini-card au survol d'un pays (positionnée par JS) -->
    <div id="pays-hover-card" class="pays-hover-card" hidden></div>

</div>

<script src="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.js"></script>
<script>
window.MAP_DATA = <?= json_encode($mapData, $jsonFlags) ?>;
</script>
<script src="<?= e(asset('js/map.js')) ?>"></script>
