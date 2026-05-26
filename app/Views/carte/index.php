<?php
/**
 * Carte 3D Mapbox — variables : $places, $countries, $mapboxToken
 */
$places          = $places          ?? [];
$countries       = $countries       ?? [];
$categories      = $categories      ?? [];
$myReviewedLieux = $myReviewedLieux ?? [];
$mapboxToken     = $mapboxToken     ?? '';
$paysNoms        = $paysNoms        ?? [];

$jsonFlags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= constant('JSON_INVALID_UTF8_SUBSTITUTE');
}

$mapData = [
    'token'           => $mapboxToken,
    'places'          => $places,
    'countries'       => $countries,
    'categories'      => $categories,
    'myReviewedLieux' => $myReviewedLieux,
    'paysNoms'        => $paysNoms,
    'placePath'       => url('lieu'),
    'paysPath'        => url('pays'),
    'regionsPath'     => url('assets/data/regions.json'),
    'creerPath'       => url('lieu/creer'),
    'isLoggedIn'      => isLoggedIn(),
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

            <!-- Filtre par type d'avis (Tous / Avec avis / Mes avis) -->
            <div id="avis-filter-wrap">
                <label for="avis-filter">Filtrer par avis</label>
                <select id="avis-filter">
                    <option value="tous">Tous les lieux</option>
                    <option value="avecAvis">Lieux avec avis</option>
                    <?php if (isLoggedIn()): ?>
                    <option value="mesAvis">Mes avis uniquement</option>
                    <?php endif; ?>
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

            <!-- Barre de recherche d'adresse Mapbox (composant officiel).
                 Le token est posé en attribut pour être disponible dès la 1re frappe
                 (sinon la lib envoie ses premières requêtes sans token = 401). -->
            <div id="recherche-wrap">
                <mapbox-search-box
                    id="recherche-lieu"
                    access-token="<?= e($mapboxToken) ?>"
                    proximity="auto"
                    placeholder="Rechercher une adresse, ville…">
                </mapbox-search-box>
            </div>

            <!-- Bouton ajout de lieu (visible uniquement si connecté) -->
            <?php if (isLoggedIn()): ?>
            <button id="btn-ajouter-lieu" class="btn-add-pin" type="button" title="Ajouter un nouveau lieu sur la carte">
                <span class="plus">+</span> Ajouter un lieu
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bandeau d'aide affiché pendant le mode « Ajouter un lieu » -->
    <div id="add-mode-hint" class="add-mode-hint" hidden>
        Cliquez sur la carte pour placer votre lieu.
        <button type="button" id="add-mode-cancel">Annuler</button>
    </div>

    <!-- Bouton d'ouverture du panneau (visible quand le panneau est replié / sur mobile) -->
    <button id="nav-open-btn" class="nav-open-btn" type="button" aria-label="Afficher les filtres">
        <span class="nav-open-ic" aria-hidden="true"></span> Filtres
    </button>

    <!-- Fond semi-transparent derrière le tiroir sur mobile -->
    <div id="nav-backdrop" class="nav-backdrop"></div>

    <!-- Panneau navigation gauche -->
    <div id="nav-panel">
        <div class="nav-panel-head">
            <span class="nav-panel-title">Explorer</span>
            <button id="nav-close-btn" class="nav-close-btn" type="button" aria-label="Masquer le panneau">×</button>
        </div>

        <button id="nav-world" class="nav-btn nav-world-btn">Monde entier</button>

        <div class="nav-section">
            <span class="nav-label">Continents</span>
            <div id="nav-continents"></div>
        </div>

        <div class="nav-section">
            <span class="nav-label">Pays populaires</span>
            <div id="nav-countries"></div>
        </div>

        <div class="nav-section">
            <span class="nav-label">Types de lieu</span>
            <div id="nav-categories"></div>
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

    <!-- Panneau latéral droite : info pays au clic -->
    <aside id="pays-panel" class="pays-panel" hidden aria-label="Informations du pays">
        <button id="pays-panel-close" class="pays-panel-close" aria-label="Fermer le panneau">×</button>
        <div id="pays-panel-content"></div>
    </aside>

</div>

<script src="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.js"></script>
<!-- Composant Web officiel Mapbox pour l'autocomplete d'adresse (configuré dans map.js) -->
<script id="search-js" src="https://api.mapbox.com/search-js/v1.0.0-beta.22/web.js" defer></script>
<script>
window.MAP_DATA = <?= json_encode($mapData, $jsonFlags) ?>;
</script>
<script src="<?= e(asset('js/map.js')) ?>"></script>
