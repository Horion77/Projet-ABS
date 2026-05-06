<?php
// Page carte interactive — ABS
session_start();

require_once __DIR__ . '/../config/database.php';

// Récupération de tous les lieux avec note moyenne et nom du pays
$stmt = $pdo->query("
    SELECT p.id, p.name, p.description, p.lat, p.lng, p.image_url,
           p.type, p.icon,
           c.name AS country_name, c.id AS country_id,
           ROUND(AVG(r.rating), 1) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM places p
    JOIN countries c ON p.country_id = c.id
    LEFT JOIN reviews r ON r.place_id = p.id
    GROUP BY p.id
    ORDER BY c.name, p.name
");
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de tous les pays pour le filtre
$stmtCountries = $pdo->query("SELECT id, name, code, lat, lng FROM countries ORDER BY name");
$countries = $stmtCountries->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte — ABS</title>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Mapbox GL JS -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.4.0/mapbox-gl.js"></script>

    <!-- Police Inter (moderne, lisible) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles carte -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/map.css">
</head>
<body class="map-page">

<div id="map-wrapper">

    <!-- Contrôles au-dessus de la carte -->
    <div id="map-controls">
        <div id="map-controls-inner">
            <h1 id="map-title">Explorer le monde</h1>

            <!-- Filtre par pays -->
            <div id="country-filter-wrap">
                <label for="country-filter">Filtrer par pays</label>
                <select id="country-filter">
                    <option value="">Tous les pays</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= $country['id'] ?>"
                                data-lat="<?= $country['lat'] ?>"
                                data-lng="<?= $country['lng'] ?>">
                            <?= htmlspecialchars($country['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Switcher de styles -->
            <div id="style-switcher">
                <button class="style-btn active" data-style="mapbox://styles/mapbox/dark-v11">Sombre</button>
                <button class="style-btn" data-style="mapbox://styles/mapbox/satellite-streets-v12">Satellite</button>
                <button class="style-btn" data-style="mapbox://styles/mapbox/outdoors-v12">Terrain</button>
                <button class="style-btn" data-style="mapbox://styles/mapbox/streets-v12">Rues</button>
            </div>

            <!-- Compteur de lieux affichés -->
            <div id="places-count">
                <span id="count-number"><?= count($places) ?></span> lieu<?= count($places) > 1 ? 'x' : '' ?>
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

    <!-- Conteneur de la carte -->
    <div id="map"></div>

</div>

<!-- Données PHP → JavaScript -->
<script>
    const places = <?= json_encode($places, JSON_UNESCAPED_UNICODE) ?>;
    const countries = <?= json_encode($countries, JSON_UNESCAPED_UNICODE) ?>;
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>

<script src="../assets/js/map.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
