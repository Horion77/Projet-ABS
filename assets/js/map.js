// map.js — Logique carte 3D Mapbox GL JS — Projet ABS

mapboxgl.accessToken = 'pk.eyJ1IjoiaG9yaW9uNzciLCJhIjoiY21sN3RucGZrMDBldjNoczh0eHpncHZ5cSJ9.UAsZLpLQMoEWuJx5pL5poQ';

// ── Styles disponibles ─────────────────────────────────────────────────────
const STYLES = {
    'mapbox://styles/mapbox/dark-v11':              { fog: true,  buildings: true,  terrain: true  },
    'mapbox://styles/mapbox/satellite-streets-v12': { fog: true,  buildings: true,  terrain: true  },
    'mapbox://styles/mapbox/outdoors-v12':          { fog: false, buildings: false, terrain: true  },
    'mapbox://styles/mapbox/streets-v12':           { fog: false, buildings: false, terrain: false },
};

// ── Seuils de zoom par type ────────────────────────────────────────────────
// pays    : zoom 0-5.5   → vue mondiale / continentale
// ville   : zoom 4-9     → vue régionale / nationale
// monument: zoom 7.5-22  → vue locale / ville
const ZOOM_RANGE = {
    pays:     { min: 0,   max: 5.5 },
    ville:    { min: 4,   max: 9   },
    monument: { min: 7.5, max: 22  },
};

// ── Continents ────────────────────────────────────────────────────────────
const CONTINENTS = [
    { name: 'Europe',    lat: 54,  lng: 15,   zoom: 3.2 },
    { name: 'Asie',      lat: 34,  lng: 100,  zoom: 2.8 },
    { name: 'Amériques', lat: 10,  lng: -80,  zoom: 2.3 },
    { name: 'Afrique',   lat: 0,   lng: 20,   zoom: 3.0 },
    { name: 'Océanie',   lat: -25, lng: 135,  zoom: 3.2 },
];

let currentStyle    = 'mapbox://styles/mapbox/dark-v11';
let activeCountryId = null;
let markers         = [];  // [{ marker, type }]

// ── Init carte ────────────────────────────────────────────────────────────
const map = new mapboxgl.Map({
    container: 'map',
    style:      currentStyle,
    center:     [20, 20],
    zoom:       1.8,
    pitch:      45,
    bearing:    -10,
    projection: 'globe',
    antialias:  true,
    renderWorldCopies: false,
});

map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');

// ── Style load ────────────────────────────────────────────────────────────
map.on('style.load', () => {
    applyAtmosphere();
    applyTerrain();
    renderMarkers();
});

map.on('movestart', () => document.getElementById('map').classList.add('map-moving'));
map.on('moveend',   () => {
    document.getElementById('map').classList.remove('map-moving');
    updateVisibility();
});
map.on('zoom', updateVisibility);

// ── Atmosphère ────────────────────────────────────────────────────────────
function applyAtmosphere() {
    const cfg = STYLES[currentStyle] || {};
    if (cfg.fog) {
        map.setFog({
            color:            'rgb(10, 10, 30)',
            'high-color':     'rgb(36, 92, 223)',
            'horizon-blend':  0.015,
            'space-color':    'rgb(4, 4, 18)',
            'star-intensity': 0.85,
        });
        if (!map.getLayer('sky')) {
            map.addLayer({ id: 'sky', type: 'sky', paint: {
                'sky-type': 'atmosphere',
                'sky-atmosphere-sun': [0, 90],
                'sky-atmosphere-sun-intensity': 5,
            }});
        }
    } else {
        map.setFog(null);
    }
    if (cfg.buildings && map.getSource('composite') && !map.getLayer('3d-buildings')) {
        map.addLayer({
            id: '3d-buildings', source: 'composite', 'source-layer': 'building',
            filter: ['==', 'extrude', 'true'], type: 'fill-extrusion', minzoom: 14,
            paint: {
                'fill-extrusion-color':   '#aaa',
                'fill-extrusion-height':  ['get', 'height'],
                'fill-extrusion-base':    ['get', 'min_height'],
                'fill-extrusion-opacity': 0.6,
            },
        });
    }
}

// ── Terrain 3D ────────────────────────────────────────────────────────────
function applyTerrain() {
    const cfg = STYLES[currentStyle] || {};
    if (!cfg.terrain) return;
    if (!map.getSource('mapbox-dem')) {
        map.addSource('mapbox-dem', {
            type: 'raster-dem',
            url:  'mapbox://mapbox.mapbox-terrain-dem-v1',
            tileSize: 512, maxzoom: 14,
        });
    }
    map.setTerrain({ source: 'mapbox-dem', exaggeration: 1.5 });
}

// ── Création d'un pin HTML selon le type ──────────────────────────────────
function createPinEl(place) {
    const el   = document.createElement('div');
    const type = place.type || 'monument';
    el.className = `abs-marker pin-${type}`;

    const note = place.avg_rating
        ? `<span class="pin-note">${place.avg_rating}★</span>`
        : '';

    if (type === 'pays') {
        el.innerHTML = `<div class="pin-inner">
          <span class="pin-icon">${escapeHtml(place.icon || '🌍')}</span>
          <span class="pin-label">${escapeHtml(place.name)}</span>
          ${note}
        </div>`;
    } else if (type === 'ville') {
        el.innerHTML = `<div class="pin-inner">
          <span class="pin-icon">${escapeHtml(place.icon || '🏙️')}</span>
          <span class="pin-label">${escapeHtml(place.name)}</span>
          ${note}
        </div>`;
    } else {
        el.innerHTML = `<div class="pin-inner">
          <span class="pin-icon">${escapeHtml(place.icon || '📍')}</span>
          ${note}
        </div>`;
    }

    return el;
}

// ── Rendu des marqueurs ────────────────────────────────────────────────────
function renderMarkers() {
    markers.forEach(({ marker }) => marker.remove());
    markers = [];

    const filtered = activeCountryId
        ? places.filter(p => String(p.country_id) === String(activeCountryId))
        : places;

    updateCount(filtered.length);

    filtered.forEach(place => {
        const el    = createPinEl(place);
        const popup = new mapboxgl.Popup({
            offset: 28, maxWidth: '280px', className: 'abs-popup',
        }).setHTML(buildPopup(place));

        const marker = new mapboxgl.Marker({ element: el, anchor: 'center' })
            .setLngLat([parseFloat(place.lng), parseFloat(place.lat)])
            .setPopup(popup)
            .addTo(map);

        markers.push({ marker, type: place.type || 'monument' });
    });

    updateVisibility();
}

// ── Popup ─────────────────────────────────────────────────────────────────
function buildPopup(place) {
    const ratingHTML = place.avg_rating
        ? `<div class="popup-rating">
              ${renderStars(place.avg_rating)}
              <span>${place.avg_rating}/5</span>
              <small>(${place.review_count} avis)</small>
           </div>`
        : `<div class="popup-rating no-rating">Aucun avis pour l'instant</div>`;

    const imgHTML = place.image_url
        ? `<img class="popup-image" src="${escapeHtml(place.image_url)}" alt="${escapeHtml(place.name)}">`
        : '';

    const typeLabel = { pays: '🌍 Pays', ville: '🏙️ Ville', monument: '🏛️ Monument' };

    return `
      <div class="popup-inner">
        ${imgHTML}
        <div class="popup-body">
          <div class="popup-type-badge">${typeLabel[place.type] || ''}</div>
          <h3 class="popup-title">${escapeHtml(place.name)}</h3>
          <p class="popup-country">${escapeHtml(place.country_name)}</p>
          ${ratingHTML}
          <a class="popup-link" href="place.php?id=${place.id}">Voir les avis →</a>
        </div>
      </div>`;
}

// ── Visibilité par zoom ────────────────────────────────────────────────────
function updateVisibility() {
    const z = map.getZoom();
    markers.forEach(({ marker, type }) => {
        const range   = ZOOM_RANGE[type] || { min: 0, max: 22 };
        const visible = z >= range.min && z <= range.max;
        const el      = marker.getElement();
        el.style.opacity       = visible ? '1' : '0';
        el.style.pointerEvents = visible ? 'auto' : 'none';
    });
}

// ── Style switcher ────────────────────────────────────────────────────────
document.querySelectorAll('.style-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const newStyle = this.dataset.style;
        if (newStyle === currentStyle) return;
        currentStyle = newStyle;
        document.querySelectorAll('.style-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        markers.forEach(({ marker }) => marker.remove());
        markers = [];
        map.setStyle(newStyle);
    });
});

// ── Panneau nav gauche ────────────────────────────────────────────────────
(function buildNavPanel() {
    const contList    = document.getElementById('nav-continents');
    const countryList = document.getElementById('nav-countries');
    if (!contList || !countryList) return;

    document.getElementById('nav-world')?.addEventListener('click', () => {
        setActiveNav(null);
        activeCountryId = null;
        syncCountryFilter(null);
        renderMarkers();
        map.flyTo({ center: [20, 20], zoom: 1.8, pitch: 45, bearing: -10, duration: 1800, essential: true });
    });

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

    countries.forEach(c => {
        const btn = document.createElement('button');
        btn.className   = 'nav-btn nav-country-btn';
        btn.dataset.id  = c.id;
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

function setActiveNav(btn) {
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
}

function syncCountryFilter(id) {
    const sel = document.getElementById('country-filter');
    if (sel) sel.value = id ? String(id) : '';
}

// ── Filtre pays (select) ──────────────────────────────────────────────────
const countryFilterEl = document.getElementById('country-filter');
if (countryFilterEl) {
    countryFilterEl.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        activeCountryId = this.value || null;
        renderMarkers();
        document.querySelectorAll('.nav-country-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.id === this.value);
        });
        if (activeCountryId && opt.dataset.lat && opt.dataset.lng) {
            map.flyTo({ center: [parseFloat(opt.dataset.lng), parseFloat(opt.dataset.lat)], zoom: 4.5, duration: 1800, essential: true });
        } else {
            map.flyTo({ center: [20, 20], zoom: 1.8, duration: 1800, essential: true });
        }
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────
function updateCount(n) {
    const el = document.getElementById('count-number');
    if (!el) return;
    el.textContent = n;
    const txt = el.nextSibling;
    if (txt) txt.textContent = ' lieu' + (n > 1 ? 'x' : '');
}

function renderStars(rating) {
    const full  = Math.floor(rating);
    const half  = rating % 1 >= 0.5 ? 1 : 0;
    const empty = 5 - full - half;
    return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
