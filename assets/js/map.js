// assets/js/map.js

// 1. Initialisation avec votre clé
mapboxgl.accessToken = mapboxToken;

// 2. Création de la carte
const map = new mapboxgl.Map({
    container: 'map', // ID du conteneur HTML
    style: 'mapbox://styles/mapbox/streets-v12', // Style visuel de la carte
    center: [2.3522, 48.8566], // Coordonnées de départ (ex: Paris [Longitude, Latitude])
    zoom: 2 // Niveau de zoom initial pour voir le monde entier
});

// Ajout des contrôles de navigation (zoom +/-)
map.addControl(new mapboxgl.NavigationControl());

// 3. Boucle sur les données pour créer les marqueurs
placesData.forEach(place => {
    // On s'assure que les coordonnées existent
    if (place.lng && place.lat) {
        
        // Préparation du contenu du popup (HTML)
        const noteText = place.avg_rating ? `${place.avg_rating}/5` : 'Aucun avis';
        const popupContent = `
            <h3>${place.name}</h3>
            <p><strong>Pays :</strong> ${place.country_name}</p>
            <p><strong>Note moy. :</strong> ${noteText}</p>
            <a href="place.php?id=${place.id_lieu}" class="btn-popup">Voir les avis</a>
        `;

        // Création du Popup
        const popup = new mapboxgl.Popup({ offset: 25 })
            .setHTML(popupContent);

        // Création du Marqueur et ajout à la carte
        new mapboxgl.Marker({ color: '#0d5c63' })
            .setLngLat([place.lng, place.lat])
            .setPopup(popup)
            .addTo(map);
    }
});