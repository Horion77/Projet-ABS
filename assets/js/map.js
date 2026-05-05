// map.js — Logique carte 3D Mapbox GL JS — Projet ABS

mapboxgl.accessToken = 'pk.eyJ1IjoiaG9yaW9uNzciLCJhIjoiY21sN3RucGZrMDBldjNoczh0eHpncHZ5cSJ9.UAsZLpLQMoEWuJx5pL5poQ';

// ─── Styles disponibles ────────────────────────────────────────────────────
const STYLES = {
    'mapbox://styles/mapbox/dark-v11':             { fog: true,  buildings: true,  terrain: true  },
    'mapbox://styles/mapbox/satellite-streets-v12':{ fog: true,  buildings: true,  terrain: true  },
    'mapbox://styles/mapbox/outdoors-v12':         { fog: false, buildings: false, terrain: true  },
    'mapbox://styles/mapbox/streets-v12':          { fog: false, buildings: false, terrain: false },
};

// ─── Continents pour le panneau de navigation ──────────────────────────────
const CONTINENTS = [
    { name: 'Europe',    lat: 54,   lng: 15,   zoom: 3.2 },
    { name: 'Asie',      lat: 34,   lng: 100,  zoom: 2.8 },
    { name: 'Amériques', lat: 10,   lng: -80,  zoom: 2.3 },
    { name: 'Afrique',   lat: 0,    lng: 20,   zoom: 3.0 },
    { name: 'Océanie',   lat: -25,  lng: 135,  zoom: 3.2 },
];

let currentStyle = 'mapbox://styles/mapbox/dark-v11';

// ─── Init carte ─────────────────────────────────────────────────────────────
const map = new mapboxgl.Map({
    container: 'map',
    style: currentStyle,
    center: [20, 20],
    zoom: 1.8,
    pitch: 45,        // inclinaison pour voir la 3D
    bearing: -10,
    projection: 'globe',
    antialias: true,
    renderWorldCopies: false,
});

map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');

// ─── Au chargement / rechargement du style ─────────────────────────────────
map.on('style.load', () => {
    applyAtmosphere();
    applyTerrain();
    renderMarkers();
});

// Cache les marqueurs pendant le mouvement (évite le décalage dû à la projection globe)
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

    // Bâtiments 3D (visible à partir de zoom ~14, seulement sur satellite)
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

// ─── Terrain 3D (relief des montagnes etc.) ────────────────────────────────
function applyTerrain() {
    const cfg = STYLES[currentStyle] || {};
    if (!cfg.terrain) return;

    // Source DEM (Digital Elevation Model) de Mapbox
    if (!map.getSource('mapbox-dem')) {
        map.addSource('mapbox-dem', {
            type: 'raster-dem',
            url: 'mapbox://mapbox.mapbox-terrain-dem-v1',
            tileSize: 512,
            maxzoom: 14,
        });
    }

    map.setTerrain({ source: 'mapbox-dem', exaggeration: 1.5 });
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

        document.querySelectorAll('.style-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // On supprime les marqueurs avant car le canvas est réinitialisé
        markers.forEach(m => m.remove());
        markers = [];

        map.setStyle(newStyle);
        // style.load se déclenchera → applyAtmosphere() + applyTerrain() + renderMarkers()
    });
});

// ─── Panneau navigation gauche : continents + pays ─────────────────────────
(function buildNavPanel() {
    const contList = document.getElementById('nav-continents');
    const countryList = document.getElementById('nav-countries');

    if (!contList || !countryList) return;

    // Bouton "monde entier"
    document.getElementById('nav-world')?.addEventListener('click', () => {
        setActiveNav(null);
        map.flyTo({ center: [20, 20], zoom: 1.8, pitch: 45, bearing: -10, duration: 1800, essential: true });
    });

    // Boutons continents
    CONTINENTS.forEach(cont => {
        const btn = document.createElement('button');
        btn.className = 'nav-btn nav-continent-btn';
        btn.textContent = cont.name;
        btn.addEventListener('click', () => {
            setActiveNav(btn);
            activeCountryId = null;
            syncCountryFilter(null);
            renderMarkers();
            map.flyTo({ center: [cont.lng, cont.lat], zoom: cont.zoom, pitch: 45, duration: 1800, essential: true });
        });
        contList.appendChild(btn);
    });

    // Boutons pays (depuis la BDD, passés via PHP)
    countries.forEach(c => {
        const btn = document.createElement('button');
        btn.className = 'nav-btn nav-country-btn';
        btn.dataset.id = c.id;
        btn.dataset.lat = c.lat;
        btn.dataset.lng = c.lng;
        btn.textContent = c.name;
        btn.addEventListener('click', () => {
            setActiveNav(btn);
            activeCountryId = String(c.id);
            syncCountryFilter(c.id);
            renderMarkers();
            map.flyTo({ center: [parseFloat(c.lng), parseFloat(c.lat)], zoom: 5, pitch: 50, duration: 1800, essential: true });
        });
        countryList.appendChild(btn);
    });
})();

// Met en surbrillance le bouton actif dans le panneau
function setActiveNav(activeBtn) {
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    if (activeBtn) activeBtn.classList.add('active');
}

// Synchronise le <select> pays du header avec la nav gauche
function syncCountryFilter(countryId) {
    const sel = document.getElementById('country-filter');
    if (!sel) return;
    sel.value = countryId ? String(countryId) : '';
}

// ─── Filtre pays (select header) ──────────────────────────────────────────
const countryFilter = document.getElementById('country-filter');

if (countryFilter) {
    countryFilter.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        activeCountryId = this.value || null;
        renderMarkers();

        // Sync panneau gauche
        document.querySelectorAll('.nav-country-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.id === this.value);
        });

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
