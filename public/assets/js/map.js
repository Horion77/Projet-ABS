// map.js — Carte 3D Mapbox GL JS — Projet ABS (MVC)
// Requiert : window.MAP_DATA { token, places, countries, placePath }

(function () {
  'use strict';
  if (typeof mapboxgl === 'undefined' || !window.MAP_DATA) return;

  var d        = window.MAP_DATA;
  var places   = d.places   || [];
  var countries = d.countries || [];
  var pathLieu = d.placePath || '/lieu';

  mapboxgl.accessToken = d.token;

  // ── Styles disponibles ──────────────────────────────────────────────────
  var STYLES = {
    dark:      { url: 'mapbox://styles/mapbox/dark-v11',              fog: true,  buildings: true,  terrain: true  },
    satellite: { url: 'mapbox://styles/mapbox/satellite-streets-v12', fog: true,  buildings: true,  terrain: true  },
    outdoors:  { url: 'mapbox://styles/mapbox/outdoors-v12',          fog: false, buildings: false, terrain: true  },
    streets:   { url: 'mapbox://styles/mapbox/streets-v12',           fog: false, buildings: false, terrain: false },
  };
  var currentStyleKey = 'dark';

  // ── Seuils de zoom par type ─────────────────────────────────────────────
  var ZOOM_RANGE = {
    pays:     { min: 0,   max: 5.5 },
    ville:    { min: 4,   max: 9   },
    monument: { min: 7.5, max: 22  },
  };

  // ── Continents ──────────────────────────────────────────────────────────
  var CONTINENTS = [
    { name: 'Europe',    lat: 54,  lng: 15,   zoom: 3.2 },
    { name: 'Asie',      lat: 34,  lng: 100,  zoom: 2.8 },
    { name: 'Amériques', lat: 10,  lng: -80,  zoom: 2.3 },
    { name: 'Afrique',   lat: 0,   lng: 20,   zoom: 3.0 },
    { name: 'Océanie',   lat: -25, lng: 135,  zoom: 3.2 },
  ];

  var activeCountryId = null;
  var markers         = [];  // [{ marker, type }]

  // ── Init carte ──────────────────────────────────────────────────────────
  var map = new mapboxgl.Map({
    container:         'map',
    style:             STYLES[currentStyleKey].url,
    center:            [20, 20],
    zoom:              1.8,
    pitch:             45,
    bearing:           -10,
    projection:        'globe',
    antialias:         true,
    renderWorldCopies: false,
  });

  map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
  map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');

  map.on('style.load', function () { applyAtmosphere(); applyTerrain(); renderMarkers(); });
  map.on('movestart', function () { document.getElementById('map').classList.add('map-moving'); });
  map.on('moveend',   function () { document.getElementById('map').classList.remove('map-moving'); updateVisibility(); });
  map.on('zoom', updateVisibility);

  // ── Atmosphère ──────────────────────────────────────────────────────────
  function applyAtmosphere() {
    var cfg = STYLES[currentStyleKey] || {};
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
          'sky-type':                     'atmosphere',
          'sky-atmosphere-sun':           [0, 90],
          'sky-atmosphere-sun-intensity': 5,
        }});
      }
    } else {
      try { map.setFog(null); } catch (e) {}
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

  // ── Terrain 3D ──────────────────────────────────────────────────────────
  function applyTerrain() {
    var cfg = STYLES[currentStyleKey] || {};
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

  // ── Création d'un pin HTML ───────────────────────────────────────────────
  function createPinEl(place) {
    var el   = document.createElement('div');
    var type = place.type || 'monument';
    el.className = 'abs-marker pin-' + type;

    var note = place.avg_rating
      ? '<span class="pin-note">' + parseFloat(place.avg_rating).toFixed(1) + '★</span>'
      : '';

    var icon = escapeHtml(place.icon || '📍');

    if (type === 'pays') {
      el.innerHTML = '<div class="pin-inner">' +
        '<span class="pin-icon">' + icon + '</span>' +
        '<span class="pin-label">' + escapeHtml(place.name) + '</span>' +
        note + '</div>';
    } else if (type === 'ville') {
      el.innerHTML = '<div class="pin-inner">' +
        '<span class="pin-icon">' + icon + '</span>' +
        '<span class="pin-label">' + escapeHtml(place.name) + '</span>' +
        note + '</div>';
    } else {
      el.innerHTML = '<div class="pin-inner">' +
        '<span class="pin-icon">' + icon + '</span>' +
        note + '</div>';
    }
    return el;
  }

  // ── Rendu des marqueurs ──────────────────────────────────────────────────
  function renderMarkers() {
    markers.forEach(function (m) { m.marker.remove(); });
    markers = [];

    var filtered = activeCountryId
      ? places.filter(function (p) { return String(p.id_pays) === String(activeCountryId); })
      : places;

    updateCount(filtered.length);

    filtered.forEach(function (place) {
      var lat = parseFloat(place.lat);
      var lng = parseFloat(place.lng);
      if (isNaN(lat) || isNaN(lng)) return;

      var el     = createPinEl(place);
      var popup  = new mapboxgl.Popup({ offset: 28, maxWidth: '280px', className: 'abs-popup' })
        .setHTML(buildPopup(place));
      var marker = new mapboxgl.Marker({ element: el, anchor: 'center' })
        .setLngLat([lng, lat])
        .setPopup(popup)
        .addTo(map);

      markers.push({ marker: marker, type: place.type || 'monument' });
    });

    updateVisibility();
  }

  // ── Popup ────────────────────────────────────────────────────────────────
  function buildPopup(place) {
    var rating = place.avg_rating ? parseFloat(place.avg_rating) : null;
    var ratingHTML = rating
      ? '<div class="popup-rating">' +
          renderStars(rating) +
          ' <span>' + rating.toFixed(1) + '/5</span>' +
          ' <small>(' + (place.review_count || 0) + ' avis)</small>' +
        '</div>'
      : '<div class="popup-rating no-rating">Aucun avis pour l\'instant</div>';

    var imgHTML = place.image_url
      ? '<img class="popup-image" src="' + escapeHtml(place.image_url) + '" alt="' + escapeHtml(place.name) + '">'
      : '';

    var typeLabel = { pays: '🌍 Pays', ville: '🏙️ Ville', monument: '🏛️ Monument' };
    var link = pathLieu + '?id=' + encodeURIComponent(String(place.id_lieu));

    return '<div class="popup-inner">' +
      imgHTML +
      '<div class="popup-body">' +
        '<div class="popup-type-badge">' + (typeLabel[place.type] || '') + '</div>' +
        '<h3 class="popup-title">' + escapeHtml(place.name) + '</h3>' +
        '<p class="popup-country">' + escapeHtml(place.country_name) + '</p>' +
        ratingHTML +
        '<a class="popup-link" href="' + escapeHtml(link) + '">Voir les avis →</a>' +
      '</div>' +
    '</div>';
  }

  // ── Visibilité par zoom ──────────────────────────────────────────────────
  function updateVisibility() {
    var z = map.getZoom();
    markers.forEach(function (m) {
      var range   = ZOOM_RANGE[m.type] || { min: 0, max: 22 };
      var visible = z >= range.min && z <= range.max;
      var el      = m.marker.getElement();
      el.style.opacity       = visible ? '1' : '0';
      el.style.pointerEvents = visible ? 'auto' : 'none';
    });
  }

  // ── Style switcher ───────────────────────────────────────────────────────
  document.querySelectorAll('.style-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var key = btn.dataset.style;
      if (!STYLES[key] || key === currentStyleKey) return;
      currentStyleKey = key;
      document.querySelectorAll('.style-btn').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      markers.forEach(function (m) { m.marker.remove(); });
      markers = [];
      map.setStyle(STYLES[key].url);
    });
  });

  // ── Panneau nav gauche ───────────────────────────────────────────────────
  (function buildNavPanel() {
    var contList    = document.getElementById('nav-continents');
    var countryList = document.getElementById('nav-countries');
    if (!contList || !countryList) return;

    var worldBtn = document.getElementById('nav-world');
    if (worldBtn) {
      worldBtn.addEventListener('click', function () {
        setActiveNav(null);
        activeCountryId = null;
        syncCountryFilter(null);
        renderMarkers();
        map.flyTo({ center: [20, 20], zoom: 1.8, pitch: 45, bearing: -10, duration: 1800, essential: true });
      });
    }

    CONTINENTS.forEach(function (cont) {
      var btn = document.createElement('button');
      btn.className   = 'nav-btn nav-continent-btn';
      btn.textContent = cont.name;
      btn.addEventListener('click', function () {
        setActiveNav(btn);
        activeCountryId = null;
        syncCountryFilter(null);
        renderMarkers();
        map.flyTo({ center: [cont.lng, cont.lat], zoom: cont.zoom, pitch: 45, duration: 1800, essential: true });
      });
      contList.appendChild(btn);
    });

    countries.forEach(function (c) {
      var btn = document.createElement('button');
      btn.className      = 'nav-btn nav-country-btn';
      btn.dataset.id     = c.id_pays;
      btn.dataset.lat    = c.lat  || 0;
      btn.dataset.lng    = c.lng  || 0;
      btn.textContent    = c.nom;
      btn.addEventListener('click', function () {
        setActiveNav(btn);
        activeCountryId = String(c.id_pays);
        syncCountryFilter(c.id_pays);
        renderMarkers();
        var lat = parseFloat(c.lat  || 0);
        var lng = parseFloat(c.lng  || 0);
        map.flyTo({ center: [lng, lat], zoom: 5, pitch: 50, duration: 1800, essential: true });
      });
      countryList.appendChild(btn);
    });
  }());

  function setActiveNav(btn) {
    document.querySelectorAll('.nav-btn').forEach(function (b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');
  }

  function syncCountryFilter(id) {
    var sel = document.getElementById('country-filter');
    if (sel) sel.value = id ? String(id) : '';
  }

  // ── Filtre pays (select) ─────────────────────────────────────────────────
  var filterEl = document.getElementById('country-filter');
  if (filterEl) {
    filterEl.addEventListener('change', function () {
      var opt = filterEl.options[filterEl.selectedIndex];
      activeCountryId = filterEl.value || null;
      renderMarkers();
      document.querySelectorAll('.nav-country-btn').forEach(function (b) {
        b.classList.toggle('active', b.dataset.id === filterEl.value);
      });
      if (activeCountryId && opt.dataset.lat && opt.dataset.lng) {
        map.flyTo({ center: [parseFloat(opt.dataset.lng), parseFloat(opt.dataset.lat)], zoom: 4.5, duration: 1800, essential: true });
      } else {
        map.flyTo({ center: [20, 20], zoom: 1.8, duration: 1800, essential: true });
      }
    });
  }

  // ── Helpers ──────────────────────────────────────────────────────────────
  function updateCount(n) {
    var el = document.getElementById('count-number');
    if (!el) return;
    el.textContent = n;
    var txt = el.nextSibling;
    if (txt) txt.textContent = ' lieu' + (n > 1 ? 'x' : '');
  }

  function renderStars(rating) {
    var full  = Math.floor(rating);
    var half  = (rating % 1 >= 0.5) ? 1 : 0;
    var empty = 5 - full - half;
    return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
}());
