/**
 * Carte Mapbox 3D — globe, terrain, marqueurs typés, filtre pays, switcher de style.
 * Requiert : mapboxgl (CDN v3+), window.MAP_DATA { token, places, countries, placePath }
 */
(function () {
  'use strict';
  if (typeof mapboxgl === 'undefined' || !window.MAP_DATA) return;

  var d       = window.MAP_DATA;
  var places  = d.places   || [];
  var pathLieu = d.placePath || '/lieu';

  mapboxgl.accessToken = d.token;

  /* ── Styles disponibles ── */
  var STYLES = {
    streets:   'mapbox://styles/mapbox/streets-v12',
    outdoors:  'mapbox://styles/mapbox/outdoors-v12',
    satellite: 'mapbox://styles/mapbox/satellite-streets-v12',
    dark:      'mapbox://styles/mapbox/dark-v11',
  };
  var currentStyle = 'streets';

  /* ── Carte ── */
  var map = new mapboxgl.Map({
    container:  'map',
    style:      STYLES[currentStyle],
    center:     [12, 28],
    zoom:       2,
    pitch:      45,
    bearing:    -10,
    projection: 'globe',
  });
  map.addControl(new mapboxgl.NavigationControl(), 'top-right');
  map.addControl(new mapboxgl.ScaleControl({ unit: 'metric' }), 'bottom-left');

  /* ── Helpers ── */
  function escapeHtml(s) {
    if (s == null) return '';
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── Catégorie → type de pin ── */
  var CAT_TO_TYPE = {
    'Monument': 'monument', 'Musée': 'monument', 'Site naturel': 'monument',
    'Plage': 'monument', 'Parc': 'monument',
    'Restaurant': 'ville', 'Hôtel': 'ville', 'Bar': 'ville',
    'Marché': 'ville', 'Autre': 'ville',
  };

  var CAT_ICON = {
    'Monument': '🏛️', 'Musée': '🖼️', 'Plage': '🏖️', 'Parc': '🌿',
    'Site naturel': '🏔️', 'Restaurant': '🍽️', 'Hôtel': '🏨',
    'Bar': '🍹', 'Marché': '🛒', 'Autre': '📍',
  };

  /* ── Plages de zoom par type ── */
  var ZOOM_RANGE = {
    monument: { min: 3.5, max: 22 },
    ville:    { min: 1.5, max: 22 },
  };

  /* ── Création d'un élément pin ── */
  function createPinEl(place) {
    var cat  = place.categorie || '';
    var type = CAT_TO_TYPE[cat] || 'monument';
    var icon = CAT_ICON[cat] || '📍';
    var note = place.avg_rating
      ? '<span class="pin-note">' + parseFloat(place.avg_rating).toFixed(1) + '★</span>'
      : '';

    var el = document.createElement('div');
    el.className = 'abs-marker pin-' + type;
    el.innerHTML =
      '<div class="pin-inner">' +
        '<span class="pin-icon">' + icon + '</span>' +
        '<span class="pin-label">' + escapeHtml(place.name) + '</span>' +
        note +
      '</div>';
    return el;
  }

  /* ── Tableau de markers ── */
  var markers = [];

  function updateVisibility() {
    var z = map.getZoom();
    markers.forEach(function (m) {
      var range   = ZOOM_RANGE[m.type] || { min: 0, max: 22 };
      var visible = z >= range.min && z <= range.max;
      var el = m.marker.getElement();
      el.style.opacity       = visible ? '1' : '0';
      el.style.pointerEvents = visible ? 'auto' : 'none';
    });
  }

  function renderMarkers() {
    markers.forEach(function (m) { m.marker.remove(); });
    markers = [];

    places.forEach(function (place) {
      var lat = parseFloat(place.lat);
      var lng = parseFloat(place.lng);
      if (isNaN(lat) || isNaN(lng)) return;

      var cat    = place.categorie || '';
      var type   = CAT_TO_TYPE[cat] || 'monument';
      var idPays = place.id_pays == null ? '' : String(place.id_pays);
      var rating = place.avg_rating ? parseFloat(place.avg_rating).toFixed(1) + '/5' : 'Aucun avis';

      var el = createPinEl(place);

      var imgBlock = place.image_url
        ? '<p class="map-popup-img-wrap"><img class="map-popup-img" src="' + escapeHtml(place.image_url) + '" alt="" loading="lazy"></p>'
        : '';
      var inner =
        '<div class="map-popup-body">' +
          imgBlock +
          '<h3 class="map-popup-title">' + escapeHtml(place.name) + '</h3>' +
          '<p class="map-popup-line"><strong>Pays :</strong> ' + escapeHtml(place.country_name) + '</p>' +
          '<p class="map-popup-line"><strong>Catégorie :</strong> ' + escapeHtml(cat) + '</p>' +
          '<p class="map-popup-line"><strong>Note :</strong> ' + escapeHtml(rating) + '</p>' +
          '<a class="map-popup-cta" href="' + escapeHtml(pathLieu + '?id=' + encodeURIComponent(String(place.id_lieu))) + '">Voir les avis</a>' +
        '</div>';

      var popup  = new mapboxgl.Popup({ offset: 25 }).setHTML(inner);
      var marker = new mapboxgl.Marker({ element: el })
        .setLngLat([lng, lat])
        .setPopup(popup)
        .addTo(map);

      markers.push({ marker: marker, type: type, idPays: idPays, lng: lng, lat: lat });
    });

    updateVisibility();
  }

  /* ── Terrain + atmosphère + ciel ── */
  function applyGlobeExtras() {
    try {
      map.setFog({
        color:            'rgb(186, 210, 235)',
        'high-color':     'rgb(36, 92, 223)',
        'horizon-blend':  0.02,
        'space-color':    'rgb(11, 11, 25)',
        'star-intensity': 0.6,
      });
    } catch (e) {}

    try {
      if (!map.getSource('mapbox-dem')) {
        map.addSource('mapbox-dem', {
          type:     'raster-dem',
          url:      'mapbox://mapbox.mapbox-terrain-dem-v1',
          tileSize: 512,
          maxzoom:  14,
        });
      }
      map.setTerrain({ source: 'mapbox-dem', exaggeration: 1.5 });
    } catch (e) {}

    try {
      if (!map.getLayer('sky')) {
        map.addLayer({
          id:   'sky',
          type: 'sky',
          paint: {
            'sky-type':                       'atmosphere',
            'sky-atmosphere-sun':             [0.0, 90.0],
            'sky-atmosphere-sun-intensity':   15,
          },
        });
      }
    } catch (e) {}
  }

  map.on('style.load', function () {
    applyGlobeExtras();
    renderMarkers();
  });

  map.on('zoom', updateVisibility);

  /* ── Switcher de style ── */
  document.querySelectorAll('[data-style]').forEach(function (btn) {
    if (btn.getAttribute('data-style') === currentStyle) btn.classList.add('active');
    btn.addEventListener('click', function () {
      var s = btn.getAttribute('data-style');
      if (!STYLES[s] || s === currentStyle) return;
      currentStyle = s;
      document.querySelectorAll('[data-style]').forEach(function (b) {
        b.classList.toggle('active', b === btn);
      });
      map.setStyle(STYLES[s]);
    });
  });

  /* ── Filtre par pays ── */
  function getSelectedPays() {
    var sel = document.getElementById('map-country-filter');
    return sel && sel.value ? String(sel.value) : '';
  }

  function syncFilter() {
    var v = getSelectedPays();
    markers.forEach(function (m) {
      if (!v || m.idPays === v) {
        m.marker.addTo(map);
      } else {
        m.marker.remove();
      }
    });
    updateVisibility();

    var list = markers.filter(function (m) { return !v || m.idPays === v; });
    if (list.length === 0) return;
    if (list.length === 1) {
      map.flyTo({ center: [list[0].lng, list[0].lat], zoom: 7, duration: 900 });
      return;
    }
    var bounds = new mapboxgl.LngLatBounds();
    list.forEach(function (m) { bounds.extend([m.lng, m.lat]); });
    map.fitBounds(bounds, { padding: 80, maxZoom: 7, duration: 900 });
  }

  var filterSel = document.getElementById('map-country-filter');
  if (filterSel) {
    filterSel.addEventListener('change', syncFilter);
  }
}());
