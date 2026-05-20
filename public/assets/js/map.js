// map.js — Carte 3D Mapbox GL JS — Projet ABS (MVC)
// Requiert : window.MAP_DATA { token, places, countries, placePath }

(function () {
  'use strict';
  if (typeof mapboxgl === 'undefined' || !window.MAP_DATA) return;

  var d           = window.MAP_DATA;
  var places      = d.places     || [];
  var countries   = d.countries  || [];
  var categories  = d.categories || [];
  var pathLieu    = d.placePath  || '/lieu';
  var pathPays    = d.paysPath   || '/pays';
  var pathCreer   = d.creerPath  || '/lieu/creer';
  var isLogged    = d.isLoggedIn === true;
  // Set des id_lieu où l'utilisateur connecté a déjà laissé un avis : utilisé
  // par le filtre « Mes avis ». Set pour des lookups O(1) au lieu de O(n).
  var mesAvisSet  = new Set((d.myReviewedLieux || []).map(function (x) { return parseInt(x, 10); }));
  // Mode courant du filtre avis : 'tous' (par défaut) | 'avecAvis' | 'mesAvis'
  var avisFilter  = 'tous';

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
  // Les monuments commencent à zoom 9 : en dessous c'est le clustering qui prend le relais.
  var ZOOM_RANGE = {
    pays:     { min: 0, max: 5.5 },
    ville:    { min: 4, max: 9   },
    monument: { min: 9, max: 22  },
  };

  // ── Continents ──────────────────────────────────────────────────────────
  var CONTINENTS = [
    { name: 'Europe',    lat: 54,  lng: 15,   zoom: 3.2 },
    { name: 'Asie',      lat: 34,  lng: 100,  zoom: 2.8 },
    { name: 'Amériques', lat: 10,  lng: -80,  zoom: 2.3 },
    { name: 'Afrique',   lat: 0,   lng: 20,   zoom: 3.0 },
    { name: 'Oceanie',   lat: -25, lng: 135,  zoom: 3.2 },
  ];

  var activeCountryId   = null;
  var markers           = [];   // [{ marker, type }]
  var hoveredCountryId  = null; // ISO3 du pays sous le curseur
  var selectedCountryId = null; // ISO3 du pays sélectionné

  // ── URL state : lit ?lng=&lat=&zoom= au chargement pour réouvrir la même vue
  function lireEtatUrl() {
    var p    = new URLSearchParams(window.location.search);
    var lng  = parseFloat(p.get('lng'));
    var lat  = parseFloat(p.get('lat'));
    var zoom = parseFloat(p.get('zoom'));
    if (isNaN(lng) || isNaN(lat) || isNaN(zoom)) return null;
    return { lng: lng, lat: lat, zoom: zoom };
  }
  var etatUrl = lireEtatUrl();

  // Centre par défaut : lat 30 (au lieu de 20) → le globe est visuellement plus haut
  var CENTRE_DEFAUT = [20, 30];
  var ZOOM_DEFAUT   = 1.8;

  // ── Init carte ──────────────────────────────────────────────────────────
  var map = new mapboxgl.Map({
    container:         'map',
    style:             STYLES[currentStyleKey].url,
    center:            etatUrl ? [etatUrl.lng, etatUrl.lat] : CENTRE_DEFAUT,
    zoom:              etatUrl ? etatUrl.zoom              : ZOOM_DEFAUT,
    pitch:             45,
    bearing:           -10,
    projection:        'globe',
    antialias:         true,
    renderWorldCopies: false,
  });

  map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
  map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');

  map.on('style.load', function () {
    applyAtmosphere();
    applyTerrain();
    addCountryLayer();
    addMonumentClusters();
    renderMarkers();
    // Couche régions chargée seulement au premier zoom suffisant (fichier ~37 MB)
    map.once('zoom', tryLoadRegions);
    tryLoadRegions();
  });

  function tryLoadRegions() {
    if (map.getZoom() >= 3.5 && !map.getSource('regions-source')) {
      addRegionsLayer();
    } else if (map.getZoom() < 3.5) {
      map.once('zoom', tryLoadRegions);
    }
  }

  map.on('movestart', function () { document.getElementById('map').classList.add('map-moving'); });
  map.on('moveend',   function () { document.getElementById('map').classList.remove('map-moving'); updateVisibility(); ecrireEtatUrl(); });
  map.on('zoom', updateVisibility);

  // ── URL state : écrit lng/lat/zoom dans l'URL (debounced, replaceState) ─
  var urlTimer = null;
  function ecrireEtatUrl() {
    clearTimeout(urlTimer);
    urlTimer = setTimeout(function () {
      var c = map.getCenter();
      var p = new URLSearchParams(window.location.search);
      p.set('lng',  c.lng.toFixed(4));
      p.set('lat',  c.lat.toFixed(4));
      p.set('zoom', map.getZoom().toFixed(2));
      history.replaceState(null, '', '?' + p.toString());
    }, 350);
  }

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

  // ── Couche régions (GeoJSON Natural Earth) ───────────────────────────────
  // Visible seulement à partir du zoom 4, juste pour distinction visuelle
  var hoveredRegionId = null;

  function addRegionsLayer() {
    var regionsUrl = (d.regionsPath || '');
    if (!regionsUrl) return;

    if (!map.getSource('regions-source')) {
      map.addSource('regions-source', {
        type:      'geojson',
        data:      regionsUrl,
        promoteId: 'ne_id', // identifiant unique dans Natural Earth
      });
    }

    var sous = map.getLayer('country-label') ? 'country-label' : undefined;

    // Fill léger — visible à partir du zoom 4
    if (!map.getLayer('regions-fill')) {
      map.addLayer({
        id:     'regions-fill',
        type:   'fill',
        source: 'regions-source',
        minzoom: 4,
        paint: {
          'fill-color':   '#a0c4ff',
          'fill-opacity': ['case',
            ['boolean', ['feature-state', 'hover'], false], 0.18,
            0,
          ],
        },
      }, sous);
    }

    // Contour des régions — visible à partir du zoom 4
    if (!map.getLayer('regions-outline')) {
      map.addLayer({
        id:     'regions-outline',
        type:   'line',
        source: 'regions-source',
        minzoom: 4,
        paint: {
          'line-color':   '#7eb3ff',
          'line-width':   0.6,
          'line-opacity': 0.35,
        },
      });
    }

    // Hover : highlight de la région survolée
    map.on('mousemove', 'regions-fill', function (e) {
      if (!e.features.length) return;
      var id = e.features[0].id;
      if (hoveredRegionId !== null && hoveredRegionId !== id) {
        map.setFeatureState({ source: 'regions-source', id: hoveredRegionId }, { hover: false });
      }
      hoveredRegionId = id;
      map.setFeatureState({ source: 'regions-source', id: id }, { hover: true });
    });

    map.on('mouseleave', 'regions-fill', function () {
      if (hoveredRegionId !== null) {
        map.setFeatureState({ source: 'regions-source', id: hoveredRegionId }, { hover: false });
      }
      hoveredRegionId = null;
    });

    // Clic région : zoom sur la zone, sans repasser au clic pays
    map.on('click', 'regions-fill', function (e) {
      if (!e.features.length) return;
      e.preventDefault();
      var bounds = getFeatureBounds(e.features[0]);
      if (!bounds) return;
      map.fitBounds(bounds, { padding: 60, pitch: 50, duration: 1500, maxZoom: 9, essential: true });
    });
  }

  // Calcule la bounding box d'une feature Polygon ou MultiPolygon
  function getFeatureBounds(feature) {
    if (!feature || !feature.geometry) return null;
    var bounds = new mapboxgl.LngLatBounds();
    (function extend(c) {
      if (typeof c[0] === 'number') bounds.extend(c);
      else c.forEach(extend);
    })(feature.geometry.coordinates);
    return bounds;
  }

  // ── Couche pays : hover + sélection ─────────────────────────────────────
  function addCountryLayer() {
    // Tileset officiel Mapbox (gratuit) avec toutes les frontières pays
    if (!map.getSource('pays-source')) {
      map.addSource('pays-source', {
        type:      'vector',
        url:       'mapbox://mapbox.country-boundaries-v1',
        promoteId: { country_boundaries: 'iso_3166_1_alpha_3' },
      });
    }

    // Inséré sous les labels pays pour pas les cacher
    var sous = map.getLayer('country-label') ? 'country-label' : undefined;

    if (!map.getLayer('pays-fill')) {
      map.addLayer({
        id:             'pays-fill',
        type:           'fill',
        source:         'pays-source',
        'source-layer': 'country_boundaries',
        filter:         ['match', ['get', 'worldview'], ['all', 'US'], true, false],
        paint: {
          'fill-color':   '#6490ff',
          'fill-opacity': ['case',
            ['boolean', ['feature-state', 'selected'], false], 0.30,
            ['boolean', ['feature-state', 'hover'],    false], 0.12,
            0,
          ],
        },
      }, sous);
    }

    // Contour uniquement sur le pays sélectionné
    if (!map.getLayer('pays-outline')) {
      map.addLayer({
        id:             'pays-outline',
        type:           'line',
        source:         'pays-source',
        'source-layer': 'country_boundaries',
        filter:         ['match', ['get', 'worldview'], ['all', 'US'], true, false],
        paint: {
          'line-color':   '#7eb3ff',
          'line-width':   ['case', ['boolean', ['feature-state', 'selected'], false], 2, 0],
          'line-opacity': 0.9,
        },
      });
    }

    // Hover : highlight léger + mini-card avec stats du pays
    var hoverCard = document.getElementById('pays-hover-card');

    map.on('mousemove', 'pays-fill', function (e) {
      if (!e.features.length) return;
      var id = e.features[0].id;

      // Le pays sous le curseur a changé : on met à jour feature-state + contenu card
      if (hoveredCountryId !== id) {
        if (hoveredCountryId) {
          map.setFeatureState(
            { source: 'pays-source', sourceLayer: 'country_boundaries', id: hoveredCountryId },
            { hover: false }
          );
        }
        hoveredCountryId = id;
        map.setFeatureState(
          { source: 'pays-source', sourceLayer: 'country_boundaries', id: id },
          { hover: true }
        );
        map.getCanvas().style.cursor = 'pointer';

        // Reconstruction du contenu de la card seulement quand le pays change
        if (hoverCard) {
          var paysDB = trouverPaysDB(String(id));
          if (paysDB) {
            var nb       = parseInt(paysDB.places_count || 0, 10);
            var note     = paysDB.avg_rating;
            var statsTxt = nb + ' lieu' + (nb > 1 ? 'x' : '');
            if (note) statsTxt += ' · ★ ' + note + '/5';
            hoverCard.innerHTML =
              '<h5>' + escapeHtml(paysDB.nom) + '</h5>' +
              '<div class="phc-stats">' + statsTxt + '</div>';
            hoverCard.hidden = false;
          } else {
            hoverCard.hidden = true;
          }
        }
      }

      // Suivi de la souris : ça reste léger (juste 2 styles modifiés)
      if (hoverCard && !hoverCard.hidden) {
        hoverCard.style.left = (e.point.x + 16) + 'px';
        hoverCard.style.top  = (e.point.y + 16) + 'px';
      }
    });

    map.on('mouseleave', 'pays-fill', function () {
      if (hoveredCountryId) {
        map.setFeatureState(
          { source: 'pays-source', sourceLayer: 'country_boundaries', id: hoveredCountryId },
          { hover: false }
        );
      }
      hoveredCountryId = null;
      map.getCanvas().style.cursor = '';
      if (hoverCard) hoverCard.hidden = true;
    });

    // Clic : sélection du pays + zoom + filtre marqueurs
    map.on('click', 'pays-fill', function (e) {
      if (e.defaultPrevented) return; // Une région a déjà géré le clic
      if (!e.features.length) return;
      var f    = e.features[0];
      var iso3 = String(f.id);

      // Efface l'ancienne sélection visuelle
      if (selectedCountryId) {
        map.setFeatureState(
          { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
          { selected: false }
        );
      }

      // Reclic sur le même pays = on désélectionne
      if (selectedCountryId === iso3) {
        selectedCountryId = null;
        deselectionnerPays();
        return;
      }

      selectedCountryId = iso3;
      map.setFeatureState(
        { source: 'pays-source', sourceLayer: 'country_boundaries', id: iso3 },
        { selected: true }
      );

      var paysDB = trouverPaysDB(iso3);
      if (paysDB) {
        // Pays dans notre BDD : popup d'aperçu + filtre marqueurs + zoom
        activeCountryId = String(paysDB.id_pays);
        syncCountryFilter(paysDB.id_pays);
        renderMarkers();

        // Popup ancrée au centre du pays (reste visible après le flyTo)
        new mapboxgl.Popup({ offset: 14, maxWidth: '280px', className: 'abs-popup' })
          .setLngLat([parseFloat(paysDB.lng), parseFloat(paysDB.lat)])
          .setHTML(buildPaysPopup(paysDB))
          .addTo(map);

        map.flyTo({
          center: [parseFloat(paysDB.lng), parseFloat(paysDB.lat)],
          zoom: 5, pitch: 50, duration: 1800, essential: true,
        });
      } else {
        // Pays hors BDD : zoom simple sans filtre
        map.flyTo({
          center: [e.lngLat.lng, e.lngLat.lat],
          zoom: 5, pitch: 45, duration: 1600, essential: true,
        });
      }
    });
  }

  // ── Clustering des pins monuments ────────────────────────────────────────
  // En dézoom on regroupe les monuments dans des clusters circle pour éviter
  // l'empilement visuel. Au-dessus du clusterMaxZoom les pins DOM individuels
  // prennent le relais (via ZOOM_RANGE.monument).
  // Applique le filtre par type d'avis (tous / avec avis / mes avis).
  // Centralisé ici pour que renderMarkers() ET buildMonumentFeatures() restent
  // synchronisés (sinon le cluster afficherait des pins que le DOM cache).
  function appliquerFiltreAvis(liste) {
    if (avisFilter === 'avecAvis') {
      return liste.filter(function (p) {
        return parseInt(p.review_count || 0, 10) > 0;
      });
    }
    if (avisFilter === 'mesAvis') {
      return liste.filter(function (p) {
        return mesAvisSet.has(parseInt(p.id_lieu, 10));
      });
    }
    return liste;
  }

  // Construit la collection GeoJSON des monuments depuis le tableau `places`.
  // Extrait dans sa propre fonction pour être appelée à la fois au chargement
  // (par addMonumentClusters) et après chaque ajout d'un nouveau lieu par un user.
  function buildMonumentFeatures() {
    return appliquerFiltreAvis(places)
      .filter(function (p) { return (p.type || 'monument') === 'monument'; })
      .map(function (p) {
        var lat = parseFloat(p.lat);
        var lng = parseFloat(p.lng);
        if (isNaN(lat) || isNaN(lng)) return null;
        return {
          type: 'Feature',
          properties: {
            id_lieu: p.id_lieu,
            name:    p.name,
            rating:  p.avg_rating || 0,
          },
          geometry: { type: 'Point', coordinates: [lng, lat] },
        };
      })
      .filter(function (f) { return f !== null; });
  }

  // Rafraîchit la source GeoJSON existante (= relance le clustering avec les
  // nouveaux pins). Appelée après création d'un lieu par un utilisateur.
  function refreshMonumentSource() {
    var src = map.getSource('monuments-source');
    if (!src) return;
    src.setData({ type: 'FeatureCollection', features: buildMonumentFeatures() });
  }

  function addMonumentClusters() {
    if (map.getSource('monuments-source')) return;

    map.addSource('monuments-source', {
      type:           'geojson',
      data:           { type: 'FeatureCollection', features: buildMonumentFeatures() },
      cluster:        true,
      clusterMaxZoom: 9,   // au-delà : on bascule sur les pins DOM monuments
      clusterRadius:  50,
    });

    // Cercle du cluster (taille + couleur selon nombre de points)
    map.addLayer({
      id:     'clusters-monuments',
      type:   'circle',
      source: 'monuments-source',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color':        ['step', ['get', 'point_count'], '#b56a1a', 10, '#d4842a', 30, '#e8a347'],
        'circle-radius':       ['step', ['get', 'point_count'], 16, 10, 22, 30, 28],
        'circle-stroke-width': 2,
        'circle-stroke-color': 'rgba(255,255,255,0.6)',
        'circle-opacity':      0.92,
      },
    });

    // Compteur au centre du cluster
    map.addLayer({
      id:     'clusters-count',
      type:   'symbol',
      source: 'monuments-source',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '{point_count_abbreviated}',
        'text-size':  13,
        'text-font':  ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
      },
      paint: { 'text-color': '#fff' },
    });

    // Pin "monument isolé" (pas dans un cluster) — visible en vue ville/région
    // C'est là qu'avant on n'avait rien : seul le cluster réagissait au clic.
    map.addLayer({
      id:     'unclustered-monuments',
      type:   'circle',
      source: 'monuments-source',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-color':        '#d4842a',
        'circle-radius':       8,
        'circle-stroke-width': 2,
        'circle-stroke-color': 'rgba(255,255,255,0.7)',
        'circle-opacity':      0.95,
      },
    });

    // Clic sur un cluster → zoom à l'intérieur (Mapbox calcule le zoom pour l'éclater)
    map.on('click', 'clusters-monuments', function (e) {
      var f = map.queryRenderedFeatures(e.point, { layers: ['clusters-monuments'] })[0];
      if (!f) return;
      map.getSource('monuments-source').getClusterExpansionZoom(f.properties.cluster_id, function (err, z) {
        if (err) return;
        map.easeTo({ center: f.geometry.coordinates, zoom: z, duration: 800 });
      });
    });

    // Clic sur un monument isolé → popup avec photo + bouton "Voir les avis"
    // (avant : redirection directe, ce qui sautait l'aperçu)
    map.on('click', 'unclustered-monuments', function (e) {
      if (!e.features.length) return;
      var idLieu = e.features[0].properties.id_lieu;
      var coords = e.features[0].geometry.coordinates.slice();
      var place  = trouverLieuParId(idLieu);
      if (!place) return;
      new mapboxgl.Popup({ offset: 14, maxWidth: '280px', className: 'abs-popup' })
        .setLngLat(coords)
        .setHTML(buildPopup(place))
        .addTo(map);
    });

    // Curseur pointer au survol
    map.on('mouseenter', 'clusters-monuments',     function () { map.getCanvas().style.cursor = 'pointer'; });
    map.on('mouseleave', 'clusters-monuments',     function () { map.getCanvas().style.cursor = ''; });
    map.on('mouseenter', 'unclustered-monuments',  function () { map.getCanvas().style.cursor = 'pointer'; });
    map.on('mouseleave', 'unclustered-monuments',  function () { map.getCanvas().style.cursor = ''; });
  }

  // ── Helpers pays ─────────────────────────────────────────────────────────
  function trouverPaysDB(iso3) {
    return countries.find(function (c) {
      return (c.code_iso || '').toUpperCase() === iso3.toUpperCase();
    }) || null;
  }

  // Recherche d'un lieu par son id (utilisé par le clic sur la couche Mapbox
  // 'unclustered-monuments' qui n'a accès qu'à l'id_lieu en propriété).
  function trouverLieuParId(idLieu) {
    return places.find(function (p) {
      return String(p.id_lieu) === String(idLieu);
    }) || null;
  }

  // Popup spécifique au clic sur un pays (zone colorée).
  // Pas de photo (la table pays n'en a pas), juste les stats agrégées.
  function buildPaysPopup(paysDB) {
    var nb     = parseInt(paysDB.places_count || 0, 10);
    var rating = paysDB.avg_rating ? parseFloat(paysDB.avg_rating) : null;

    var ratingHTML = rating
      ? '<div class="popup-rating">' + renderStars(rating) +
        ' <span>' + rating.toFixed(1) + '/5</span></div>'
      : '<div class="popup-rating no-rating">Aucun avis pour l\'instant</div>';

    var lieuxTxt = nb + ' lieu' + (nb > 1 ? 'x' : '') + ' référencé' + (nb > 1 ? 's' : '');
    var link     = pathPays + '?id=' + encodeURIComponent(String(paysDB.id_pays));

    return '<div class="popup-inner">' +
      '<div class="popup-body">' +
        '<div class="popup-type-badge">Pays</div>' +
        '<h3 class="popup-title">' + escapeHtml(paysDB.nom) + '</h3>' +
        '<p class="popup-country">' + escapeHtml(lieuxTxt) + '</p>' +
        ratingHTML +
        '<a class="popup-link" href="' + escapeHtml(link) + '">Voir les avis →</a>' +
      '</div>' +
    '</div>';
  }

  function deselectionnerPays() {
    if (selectedCountryId) {
      try {
        map.setFeatureState(
          { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
          { selected: false }
        );
      } catch (e) {}
    }
    selectedCountryId = null;
    activeCountryId   = null;
    syncCountryFilter(null);
    renderMarkers();
    map.flyTo({ center: CENTRE_DEFAUT, zoom: ZOOM_DEFAUT, pitch: 45, bearing: -10, duration: 1800, essential: true });
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

    if (type === 'pays' || type === 'ville') {
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

    // On combine les deux filtres : pays (activeCountryId) puis type d'avis.
    var filtered = activeCountryId
      ? places.filter(function (p) { return String(p.id_pays) === String(activeCountryId); })
      : places;
    filtered = appliquerFiltreAvis(filtered);

    updateCount(filtered.length);

    filtered.forEach(function (place) {
      var lat = parseFloat(place.lat);
      var lng = parseFloat(place.lng);
      if (isNaN(lat) || isNaN(lng)) return;

      var type   = place.type || 'monument';
      var el     = createPinEl(place);
      var marker = new mapboxgl.Marker({ element: el, anchor: 'center' })
        .setLngLat([lng, lat])
        .addTo(map);

      // Tous les types (pays / ville / monument) : popup avec photo + "Voir les avis"
      // Le bouton dans la popup redirige vers la fiche détaillée.
      var popup = new mapboxgl.Popup({ offset: 28, maxWidth: '280px', className: 'abs-popup' })
        .setHTML(buildPopup(place));
      marker.setPopup(popup);

      markers.push({ marker: marker, type: type });
    });

    updateVisibility();
  }

  // ── Popup ────────────────────────────────────────────────────────────────
  function buildPopup(place) {
    var rating = place.avg_rating ? parseFloat(place.avg_rating) : null;
    var ratingHTML = rating
      ? '<div class="popup-rating">' + renderStars(rating) +
        ' <span>' + rating.toFixed(1) + '/5</span>' +
        ' <small>(' + (place.review_count || 0) + ' avis)</small></div>'
      : '<div class="popup-rating no-rating">Aucun avis pour l\'instant</div>';

    var imgHTML = place.image_url
      ? '<img class="popup-image" src="' + escapeHtml(place.image_url) + '" alt="' + escapeHtml(place.name) + '">'
      : '';

    var typeLabel = { pays: 'Pays', ville: 'Ville', monument: 'Monument' };
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

      // Reset sélection pays avant rechargement du style
      hoveredCountryId  = null;
      selectedCountryId = null;

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
        deselectionnerPays();
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
      btn.className   = 'nav-btn nav-country-btn';
      btn.dataset.id  = c.id_pays;
      btn.textContent = c.nom;
      btn.addEventListener('click', function () {
        setActiveNav(btn);
        activeCountryId = String(c.id_pays);
        syncCountryFilter(c.id_pays);
        renderMarkers();
        // Sélection visuelle sur la carte si l'ISO est dispo
        if (c.code_iso && map.getLayer('pays-fill')) {
          if (selectedCountryId) {
            map.setFeatureState(
              { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
              { selected: false }
            );
          }
          selectedCountryId = c.code_iso.toUpperCase();
          map.setFeatureState(
            { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
            { selected: true }
          );
        }
        var lat = parseFloat(c.lat || 0);
        var lng = parseFloat(c.lng || 0);
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

  // ── Filtre pays (select du haut) ─────────────────────────────────────────
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
        map.flyTo({ center: CENTRE_DEFAUT, zoom: ZOOM_DEFAUT, duration: 1800, essential: true });
      }
    });
  }

  // Filtre par type d'avis : on change le mode global et on rafraîchit
  // à la fois les markers DOM et la source GeoJSON du clustering.
  var avisFilterEl = document.getElementById('avis-filter');
  if (avisFilterEl) {
    avisFilterEl.addEventListener('change', function () {
      avisFilter = avisFilterEl.value || 'tous';
      refreshMonumentSource();
      renderMarkers();
    });
  }

  // ══════════════════════════════════════════════════════════════════════════
  // ── Barre de recherche d'adresse (composant Mapbox Search Box) ────────────
  // ══════════════════════════════════════════════════════════════════════════
  // Le token est déjà passé en attribut HTML (access-token="…") pour éviter
  // que la lib envoie ses premières requêtes sans token. Ici on attend que le
  // composant soit défini par le navigateur, puis on le branche à la carte
  // et on écoute l'évènement de sélection pour faire un flyTo.
  function brancherRecherche() {
    var box = document.getElementById('recherche-lieu');
    if (!box) return;
    box.mapboxgl = mapboxgl;
    try { box.bindMap(map); } catch (e) { /* la carte se branche au prochain tick */ }
    box.addEventListener('retrieve', function (ev) {
      var f = ev.detail && ev.detail.features && ev.detail.features[0];
      if (!f) return;
      map.flyTo({ center: f.geometry.coordinates, zoom: 14, essential: true });
    });
  }
  if (window.customElements && customElements.whenDefined) {
    customElements.whenDefined('mapbox-search-box').then(brancherRecherche);
  } else {
    var sjs = document.getElementById('search-js');
    if (sjs) sjs.addEventListener('load', brancherRecherche);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // ── Mode « Ajouter un lieu » ──────────────────────────────────────────────
  // ══════════════════════════════════════════════════════════════════════════
  // Flux : clic bouton → mode actif → clic carte → marker temp draggable +
  // popup formulaire pré-rempli par reverse-geocoding → submit → POST /lieu/creer
  // → ajout du pin permanent à la liste places + re-rendu.

  var addMode    = false;   // état global du mode ajout
  var markerTemp = null;    // marker jaune temporaire (un seul à la fois)
  var popupTemp  = null;    // popup du formulaire

  var btnAjouter = document.getElementById('btn-ajouter-lieu');
  var hintBar    = document.getElementById('add-mode-hint');
  var hintCancel = document.getElementById('add-mode-cancel');

  function activerAddMode() {
    if (!isLogged) return;
    addMode = true;
    document.body.classList.add('map-add-mode');
    if (hintBar) hintBar.hidden = false;
    if (btnAjouter) btnAjouter.classList.add('actif');
  }

  function desactiverAddMode() {
    addMode = false;
    document.body.classList.remove('map-add-mode');
    if (hintBar) hintBar.hidden = true;
    if (btnAjouter) btnAjouter.classList.remove('actif');
  }

  function nettoyerMarkerTemp() {
    if (popupTemp) { popupTemp.remove(); popupTemp = null; }
    if (markerTemp) { markerTemp.remove(); markerTemp = null; }
  }

  if (btnAjouter) {
    btnAjouter.addEventListener('click', function () {
      if (addMode) {
        desactiverAddMode();
        nettoyerMarkerTemp();
      } else {
        activerAddMode();
      }
    });
  }
  if (hintCancel) {
    hintCancel.addEventListener('click', function () {
      desactiverAddMode();
      nettoyerMarkerTemp();
    });
  }

  // Clic sur la carte en mode ajout : on pose un marker temp draggable.
  map.on('click', function (e) {
    if (!addMode) return;
    e.preventDefault();
    nettoyerMarkerTemp();
    poserMarkerTemp(e.lngLat.lng, e.lngLat.lat);
    desactiverAddMode(); // le mode se désactive après le 1er clic (1 lieu à la fois)
  });

  function poserMarkerTemp(lng, lat) {
    // Élément DOM du marker (jaune, animé pour signaler qu'il est ajustable)
    var el = document.createElement('div');
    el.className = 'marker-temp';
    el.innerHTML = '<span class="marker-temp-pin">📍</span>';

    markerTemp = new mapboxgl.Marker({ element: el, draggable: true })
      .setLngLat([lng, lat])
      .addTo(map);

    // Ouvre la popup du formulaire avec un état de chargement le temps du geocoding inverse.
    ouvrirPopupForm(lng, lat, null);
    reverseGeocode(lng, lat).then(function (info) {
      if (!markerTemp) return; // user a annulé entre-temps
      remplirInfosFormulaire(info);
    }).catch(function () {
      // Échec geocoding : on laisse les champs vides, l'utilisateur peut quand même
      // remplir manuellement (mais le backend exigera ville_nom + pays_nom).
    });

    // Quand l'user déplace le marker, on met à jour les coords + re-geocode.
    markerTemp.on('dragend', function () {
      var p = markerTemp.getLngLat();
      mettreAJourCoords(p.lng, p.lat);
      reverseGeocode(p.lng, p.lat).then(remplirInfosFormulaire).catch(function () {});
    });
  }

  // ── Reverse-geocoding (Mapbox Geocoding API v5) ─────────────────────────
  function reverseGeocode(lng, lat) {
    var url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/'
            + lng + ',' + lat + '.json'
            + '?access_token=' + d.token
            + '&types=address,place,locality,country&language=fr&limit=1';
    return fetch(url).then(function (r) { return r.json(); }).then(function (data) {
      var f = data.features && data.features[0];
      if (!f) return { adresse: '', ville: '', pays: '', code_iso: '' };

      var adresse  = f.place_name || '';
      var ville    = '';
      var pays     = '';
      var codeIso  = '';

      // Mapbox renvoie le contexte hiérarchique : on prend le 1er match par type.
      (f.context || []).forEach(function (c) {
        if (!c.id) return;
        if (c.id.indexOf('place.')   === 0 && !ville) ville = c.text || '';
        if (c.id.indexOf('locality.')=== 0 && !ville) ville = c.text || '';
        if (c.id.indexOf('country.') === 0) {
          pays = c.text || '';
          codeIso = (c.short_code || '').toLowerCase();
        }
      });
      // Si le feature lui-même est un pays
      if (f.id && f.id.indexOf('country.') === 0) {
        pays = f.text || pays;
        codeIso = (f.properties && f.properties.short_code || '').toLowerCase() || codeIso;
      }
      return { adresse: adresse, ville: ville, pays: pays, code_iso: codeIso };
    });
  }

  // ── Popup formulaire de création ────────────────────────────────────────
  function ouvrirPopupForm(lng, lat, info) {
    var optionsCat = categories.map(function (c) {
      return '<option value="' + c.id_categorie + '">' + escapeHtml(c.libelle) + '</option>';
    }).join('');

    var html =
      '<form class="form-creer-lieu" id="form-creer-lieu" enctype="multipart/form-data">' +
        '<h3>Ajouter ce lieu</h3>' +
        '<label>Nom *<input name="nom" required maxlength="150" autocomplete="off"></label>' +
        '<label>Catégorie *' +
          '<select name="id_categorie" required>' +
            '<option value="">— choisir —</option>' + optionsCat +
          '</select>' +
        '</label>' +
        '<label>Description<textarea name="description" maxlength="2000" rows="3"></textarea></label>' +
        '<label>Photo (optionnelle)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>' +
        '<div class="loc-preview">' +
          '<div><strong>Adresse :</strong> <span data-adresse>' + (info ? escapeHtml(info.adresse) : '…') + '</span></div>' +
          '<div><strong>Ville :</strong> <span data-ville>' + (info ? escapeHtml(info.ville) : '…') + '</span> · ' +
               '<strong>Pays :</strong> <span data-pays>'  + (info ? escapeHtml(info.pays) : '…')  + '</span></div>' +
        '</div>' +
        '<div class="form-error" hidden></div>' +
        '<input type="hidden" name="latitude"  value="' + lat + '">' +
        '<input type="hidden" name="longitude" value="' + lng + '">' +
        '<input type="hidden" name="adresse"   value="' + (info ? escapeHtml(info.adresse) : '') + '">' +
        '<input type="hidden" name="ville_nom" value="' + (info ? escapeHtml(info.ville) : '') + '">' +
        '<input type="hidden" name="pays_nom"  value="' + (info ? escapeHtml(info.pays) : '') + '">' +
        '<input type="hidden" name="pays_code_iso" value="' + (info ? escapeHtml(info.code_iso) : '') + '">' +
        '<div class="form-actions">' +
          '<button type="button" id="btn-annuler-creation">Annuler</button>' +
          '<button type="submit">Créer le lieu</button>' +
        '</div>' +
      '</form>';

    popupTemp = new mapboxgl.Popup({ offset: 28, maxWidth: '320px', className: 'abs-popup', closeOnClick: false })
      .setLngLat([lng, lat])
      .setHTML(html)
      .addTo(map);

    // Branche les handlers une fois la popup dans le DOM.
    setTimeout(function () {
      var form = document.getElementById('form-creer-lieu');
      var btnAnnuler = document.getElementById('btn-annuler-creation');
      if (form) form.addEventListener('submit', soumettreFormulaire);
      if (btnAnnuler) btnAnnuler.addEventListener('click', function () {
        nettoyerMarkerTemp();
      });
    }, 0);
  }

  function remplirInfosFormulaire(info) {
    if (!popupTemp || !info) return;
    var el = popupTemp.getElement();
    if (!el) return;
    var setText = function (sel, txt) {
      var e = el.querySelector(sel);
      if (e) e.textContent = txt || '—';
    };
    var setHidden = function (name, val) {
      var e = el.querySelector('[name="' + name + '"]');
      if (e) e.value = val || '';
    };
    setText('[data-adresse]', info.adresse);
    setText('[data-ville]',   info.ville);
    setText('[data-pays]',    info.pays);
    setHidden('adresse',       info.adresse);
    setHidden('ville_nom',     info.ville);
    setHidden('pays_nom',      info.pays);
    setHidden('pays_code_iso', info.code_iso);
  }

  function mettreAJourCoords(lng, lat) {
    if (!popupTemp) return;
    var el = popupTemp.getElement();
    if (!el) return;
    var latIn = el.querySelector('[name="latitude"]');
    var lngIn = el.querySelector('[name="longitude"]');
    if (latIn) latIn.value = lat;
    if (lngIn) lngIn.value = lng;
    popupTemp.setLngLat([lng, lat]);
  }

  function soumettreFormulaire(ev) {
    ev.preventDefault();
    var form = ev.target;
    var errBox = form.querySelector('.form-error');
    var btnSubmit = form.querySelector('button[type="submit"]');
    errBox.hidden = true;
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Envoi…';

    fetch(pathCreer, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
    })
    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
    .then(function (res) {
      if (!res.ok || !res.body.success) {
        errBox.textContent = res.body.erreur || 'Erreur inconnue.';
        errBox.hidden = false;
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Créer le lieu';
        return;
      }
      // Succès : on ajoute le lieu à la liste, on rafraîchit le cluster Mapbox
      // (sinon le nouveau pin ne se regroupe pas avec les autres dans la même
      // zone et le clic sur la couche unclustered-monuments ne le trouve pas),
      // puis on re-rend les markers DOM.
      var nouveau = res.body.lieu;
      places.push(nouveau);
      nettoyerMarkerTemp();
      refreshMonumentSource();
      if (typeof renderMarkers === 'function') renderMarkers();
      // Petit zoom sur le nouveau lieu pour confirmer visuellement à l'utilisateur.
      map.flyTo({ center: [parseFloat(nouveau.lng), parseFloat(nouveau.lat)], zoom: 15, essential: true });
    })
    .catch(function () {
      errBox.textContent = 'Connexion au serveur impossible.';
      errBox.hidden = false;
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Créer le lieu';
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
