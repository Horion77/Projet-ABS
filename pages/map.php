<?php
require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php'; 
use App\Models\Database;

// 1. Récupération des lieux (on s'assure qu'ils ont bien des coordonnées GPS)
$pdo = Database::getPdo();
$sql = "SELECT id_lieu, lieu AS name, latitude AS lat, longitude AS lng, pays AS country_name, note_moyenne AS avg_rating 
        FROM vue_classement_lieux 
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$stmt = $pdo->query($sql);
$places = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// 2. VARIABLES REQUISES POUR NE PAS FAIRE PLANTER LE HEADER
$pageTitre = 'Carte Interactive';
$fichierCssPage = 'map';
$prefixRacine = prefixRacine(); // LE VOILÀ, LE DÉTAIL QUI DÉBLOQUE TOUT !

require __DIR__ . '/../app/Views/partials/head.php';
?>

<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />

<div class="conteneur">
    <h1 style="margin-bottom: 15px;">Explorez les lieux sur la carte</h1>
    
    <div id="map" style="height: 70vh; width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);"></div>
</div>

<script>
    // Configuration
    mapboxgl.accessToken = 'pk.eyJ1IjoiYnItIiwiYSI6ImNtb2Z2eWpvZTBrZmoycHNibTg3OHliNWoifQ.JIgFJdRqYhu8VejEf_uasA';
    const placesData = <?= json_encode($places) ?>;
    
    // Création de la carte
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [2.3522, 48.8566], // Centré sur Paris par défaut
        zoom: 2 // Vue mondiale
    });
    
    // Ajout des boutons de zoom
    map.addControl(new mapboxgl.NavigationControl());
    
    // Placement des marqueurs
    placesData.forEach(place => {
        // On vérifie que la latitude et longitude sont valides
        if (place.lng && place.lat) {
            const noteText = place.avg_rating ? place.avg_rating + '/5' : 'Aucun avis';
            
            // Design du petit popup au clic
            const popupContent = `
                <div style="font-family: sans-serif;">
                    <h3 style="margin: 0 0 5px 0; color: #0d5c63;">${place.name}</h3>
                    <p style="margin: 3px 0; color: #555;"><strong>Pays :</strong> ${place.country_name}</p>
                    <p style="margin: 3px 0; color: #555;"><strong>Note :</strong> ${noteText}</p>
                    <a href="place.php?id=${place.id_lieu}" style="display: inline-block; margin-top: 8px; padding: 6px 12px; background: #0d5c63; color: white; text-decoration: none; border-radius: 4px; font-size: 0.9rem;">Voir la fiche</a>
                </div>
            `;
            
            const popup = new mapboxgl.Popup({ offset: 25 }).setHTML(popupContent);
            
            // Création du marqueur sur la carte
            new mapboxgl.Marker({ color: '#0d5c63' })
                .setLngLat([parseFloat(place.lng), parseFloat(place.lat)])
                .setPopup(popup)
                .addTo(map);
        }
    });
</script>

<?php require __DIR__ . '/../app/Views/partials/foot.php'; ?>