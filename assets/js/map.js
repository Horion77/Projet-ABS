// map.js — Logique carte 3D Mapbox GL JS — Projet ABS

mapboxgl.accessToken = 'pk.eyJ1IjoiaG9yaW9uNzciLCJhIjoiY21sN3RucGZrMDBldjNoczh0eHpncHZ5cSJ9.UAsZLpLQMoEWuJx5pL5poQ';

// ─── Styles disponibles ────────────────────────────────────────────────────
const STYLES = {
    'mapbox://styles/mapbox/dark-v11':             { fog: true,  buildings: false },
    'mapbox://styles/mapbox/satellite-streets-v12':{ fog: true,  buildings: true  },
    'mapbox://styles/mapbox/outdoors-v12':         { fog: false, buildings: false },
    'mapbox://styles/mapbox/streets-v12':          { fog: false, buildings: false },
};

let currentStyle = 'mapbox://styles/mapbox/dark-v11';

// ─── Initialisation de la carte ────────────────────────────────────────────
const map = new mapboxgl.Map({
    container: 'map',
    style: currentStyle,
    center: [20, 20],
    zoom: 1.8,
    projection: 'globe',
    antialias: true,
    // Désactive la rotation du label pour stabiliser les marqueurs DOM
    renderWorldCopies: false,
});

// ─── Contrôles de navigation ───────────────────────────────────────────────
map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');

// ─── Layers atmosphériques + marqueurs au chargement du style ──────────────
map.on('style.load', () => {
    applyAtmosphere();
    renderMarkers();
});

// Cache les marqueurs pendant le mouvement pour éviter le décalage visuel
// (la projection globe recalcule les positions en live, c'est moche)
map.on('movestart', () => {
    document.getElementById('map').classList.add('map-moving');
});
map.on('moveend', () => {
    document.getElementById('map').classList.remove('map-moving');
});

// ─── Atmosphère (fog + sky) ────────────────────────────────────────────────
function applyAtmosphere() {
    const cfg = STYLES[currentStyle] || {};

    if (cfg.fog) {
        map.setFog({
            color: 'rgb(10, 10, 30)',
            'high-color': 'rgb(36, 92, 223)',
            'horizon-blend': 0.015,
            'space-color': 'rgb(4, 4, 18)',
            'star-intensity': 0.85,
        });

        if (!map.getLayer('sky')) {
            map.addLayer({
                id: 'sky',
                type: 'sky',
                paint: {
                    'sky-type': 'atmosphere',
                    'sky-atmosphere-sun': [0, 90],
                    'sky-atmosphere-sun-intensity': 5,
                },
            });
        }
    } else {
        map.setFog(null);
    }

    if (cfg.buildings && map.getSource('composite')) {
        if (!map.getLayer('3d-buildings')) {
            map.addLayer({
                id: '3d-buildings',
                source: 'composite',
                'source-layer': 'building',
                filter: ['==', 'extrude', 'true'],
                type: 'fill-extrusion',
                minzoom: 14,
                paint: {
                    'fill-extrusion-color': '#aaa',
                    'fill-extrusion-height': ['get', 'height'],
                    'fill-extrusion-base': ['get', 'min_height'],
                    'fill-extrusion-opacity': 0.6,
                },
            });
        }
    }
}

// ─── Marqueurs et popups ───────────────────────────────────────────────────
let markers = [];
let activeCountryId = null;

function renderMarkers() {
    markers.forEach(m => m.remove());
    markers = [];

    const filtered = activeCountryId
        ? places.filter(p => String(p.country_id) === String(activeCountryId))
        : places;

    updateCount(filtered.length);

    filtered.forEach(place => {
        const el = document.createElement('div');
        el.className = 'abs-marker';
        el.setAttribute('title', place.name);

        if (place.avg_rating) {
            el.innerHTML = `<span class="marker-rating">${place.avg_rating}</span>`;
            el.classList.add('has-rating');
        } else {
            el.innerHTML = `<span class="marker-pin"></span>`;
        }

        const ratingHTML = place.avg_rating
            ? `<div class="popup-rating">${renderStars(place.avg_rating)} <span>${place.avg_rating}/5</span> <small>(${place.review_count} avis)</small></div>`
            : `<div class="popup-rating no-rating">Aucun avis pour l'instant</div>`;

        const imageHTML = place.image_url
            ? `<img class="popup-image" src="${escapeHtml(place.image_url)}" alt="${escapeHtml(place.name)}">`
            : '';

        const popup = new mapboxgl.Popup({
            offset: 36,
            maxWidth: '280px',
            className: 'abs-popup',
        }).setHTML(`
            <div class="popup-inner">
                ${imageHTML}
                <div class="popup-body">
                    <h3 class="popup-title">${escapeHtml(place.name)}</h3>
                    <p class="popup-country">${escapeHtml(place.country_name)}</p>
                    ${ratingHTML}
                    <a class="popup-link" href="place.php?id=${place.id}">Voir les avis →</a>
                </div>
            </div>
        `);

        const marker = new mapboxgl.Marker({ element: el, anchor: 'center' })
            .setLngLat([parseFloat(place.lng), parseFloat(place.lat)])
            .setPopup(popup)
            .addTo(map);

        markers.push(marker);
    });
}

// ─── Switcher de styles ────────────────────────────────────────────────────
document.querySelectorAll('.style-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const newStyle = this.dataset.style;
        if (newStyle === currentStyle) return;

        currentStyle = newStyle;

        // Mise à jour UI
        document.querySelectorAll('.style-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // Les marqueurs sont supprimés avant le changement de style
        // car le canvas Mapbox est réinitialisé
        markers.forEach(m => m.remove());
        markers = [];

        map.setStyle(newStyle);
        // style.load se déclenchera → applyAtmosphere() + renderMarkers()
    });
});

// ─── Filtre par pays ───────────────────────────────────────────────────────
const countryFilter = document.getElementById('country-filter');

if (countryFilter) {
    countryFilter.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        activeCountryId = this.value || null;
        renderMarkers();

        if (activeCountryId && opt.dataset.lat && opt.dataset.lng) {
            map.flyTo({
                center: [parseFloat(opt.dataset.lng), parseFloat(opt.dataset.lat)],
                zoom: 4.5,
                duration: 1800,
                essential: true,
            });
        } else {
            map.flyTo({ center: [20, 20], zoom: 1.8, duration: 1800, essential: true });
        }
    });
}

// ─── Helpers ───────────────────────────────────────────────────────────────
function updateCount(n) {
    const el = document.getElementById('count-number');
    if (!el) return;
    el.textContent = n;
    const txt = el.nextSibling;
    if (txt) txt.textContent = ' lieu' + (n > 1 ? 'x' : '');
}

function renderStars(rating) {
    const full = Math.floor(rating);
    const half = rating % 1 >= 0.5 ? 1 : 0;
    const empty = 5 - full - half;
    return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
