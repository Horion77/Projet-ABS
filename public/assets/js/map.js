// map.js — Carte 3D Mapbox GL JS — Projet ABS (MVC)
//
// Ce fichier gère TOUT ce qui se passe sur la carte côté navigateur :
//   - Initialisation du globe 3D Mapbox
//   - Affichage des lieux avec clustering (regroupement en bulles)
//   - Détection du clic sur les pays (frontières réelles via tileset Mapbox)
//   - Panneau de navigation gauche (continents, pays, filtres)
//   - Mode "Ajouter un lieu" (géocodage inverse + formulaire AJAX)
//   - Synchronisation de la vue dans l'URL (?lng=&lat=&zoom=)
//
// Toutes les données viennent de window.MAP_DATA, injecté par carte/index.php
// (le PHP encode le tableau $mapData en JSON directement dans la page).
//
// Requiert : window.MAP_DATA { token, places, countries, placePath }

// IIFE : tout le code est encapsulé pour éviter de polluer le scope global.
// var (ES5) utilisé partout : compatibilité avec les navigateurs anciens et
// cohérence avec l'API Mapbox GL JS qui est elle-même en ES5 compilé.
(function () {
  'use strict';
  // Sortie silencieuse si Mapbox n'est pas chargé ou si le serveur n'a pas injecté
  // window.MAP_DATA (token, lieux, pays…). Cela permet d'inclure ce script sur
  // toutes les pages sans erreur JS sur celles qui n'ont pas la carte.
  if (typeof mapboxgl === 'undefined' || !window.MAP_DATA) return;

  // --- DONNÉES (viennent toutes de PHP via window.MAP_DATA) ---
  var d           = window.MAP_DATA;
  var places      = d.places     || [];   // tous les lieux géolocalisés (lat, lng, note...)
  var countries   = d.countries  || [];   // pays avec stats (nb lieux, note moyenne, centre)
  var categories  = d.categories || [];   // catégories pour les chips de filtre
  var paysNoms    = d.paysNoms   || {};   // table ISO3 → nom FR (ex: 'FRA' → 'France')
  var pathLieu    = d.placePath  || '/lieu';     // URL de la fiche lieu
  var pathPays    = d.paysPath   || '/pays';     // URL de la page pays
  var pathCreer   = d.creerPath  || '/lieu/creer'; // endpoint POST pour créer un lieu
  var isLogged    = d.isLoggedIn === true;        // true si l'utilisateur est connecté

  // Set (pas un tableau) pour les lookups O(1) — on teste si un id_lieu est dedans
  // en O(1) au lieu de O(n) avec .indexOf()
  var mesAvisSet  = new Set((d.myReviewedLieux || []).map(function (x) { return parseInt(x, 10); }));

  // Filtres actifs — modifiés par l'interface, relus par buildLieuxFeatures()
  var avisFilter     = 'tous';    // 'tous' | 'avecAvis' | 'mesAvis'
  var categoryFilter = new Set(); // libellés de catégories actives (vide = tout afficher)

  // Le token Mapbox est obligatoire pour accéder aux tuiles et tilesets
  mapboxgl.accessToken = d.token;

  // ── Styles disponibles ──────────────────────────────────────────────────
  // Chaque style est un fond de carte Mapbox différent.
  // 'standard' = nouveau style Mapbox 3D avec éclairage dynamique (jour/nuit),
  //   bâtiments, arbres, monuments inclus. On met fog/buildings à false car
  //   le style les gère lui-même (inutile de les rajouter en double).
  // isStandard : flag pour savoir si on peut utiliser setConfigProperty (lightPreset)
  var STYLES = {
    standard:  { url: 'mapbox://styles/mapbox/standard',              fog: false, buildings: false, terrain: true,  isStandard: true },
    dark:      { url: 'mapbox://styles/mapbox/dark-v11',              fog: true,  buildings: true,  terrain: true  },
    satellite: { url: 'mapbox://styles/mapbox/satellite-streets-v12', fog: true,  buildings: true,  terrain: true  },
    outdoors:  { url: 'mapbox://styles/mapbox/outdoors-v12',          fog: false, buildings: false, terrain: true  },
    streets:   { url: 'mapbox://styles/mapbox/streets-v12',           fog: false, buildings: false, terrain: false },
  };
  var currentStyleKey    = 'standard'; // style actif au démarrage
  // Préréglage lumière du style Standard : 'dawn' | 'day' | 'dusk' | 'night'
  // Changeable via les boutons Aube/Jour/Crépuscule/Nuit dans la barre de contrôle
  var currentLightPreset = 'day';

  // Zoom de clustering GL : au-delà de 14, Mapbox affiche les pins individuels.
  var CLUSTER_MAX_ZOOM = 14;

  // ── Icônes SVG par catégorie ─────────────────────────────────────────────
  // stroke="currentColor" → hérite de la couleur CSS du parent (.abs-pin--*)
  var _s = function (d) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"'
         + ' stroke="currentColor" stroke-width="1.5" stroke-linecap="round"'
         + ' stroke-linejoin="round">' + d + '</svg>';
  };
  // Jeu d'icônes Lucide (https://lucide.dev) — propres, cohérentes, lisibles en petit.
  var CAT_ICONS = {
    // image/cadre → galerie / musée
    'Musée':        _s('<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="1.6"/><path d="m21 15-3.5-3.5a2 2 0 0 0-2.8 0L6 21"/>'),
    // utensils → restaurant
    'Restaurant':   _s('<path d="M3 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2z"/>'),
    // umbrella → plage
    'Plage':        _s('<path d="M22 12a10 10 0 0 0-20 0Z"/><path d="M12 12v8a2 2 0 0 0 4 0"/><path d="M12 2v1"/>'),
    // landmark → monument
    'Monument':     _s('<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>'),
    // tree → parc
    'Parc':         _s('<path d="M8 19a4 4 0 0 1-2.24-7.32A3.5 3.5 0 0 1 9 6.03V6a3 3 0 1 1 6 0v.03a3.5 3.5 0 0 1 3.24 5.65A4 4 0 0 1 16 19Z"/><path d="M12 19v3"/>'),
    // bed → hôtel
    'Hôtel':        _s('<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>'),
    // wine → bar
    'Bar':          _s('<path d="M8 22h8"/><path d="M7 10h10"/><path d="M12 15v7"/><path d="M12 15a5 5 0 0 0 5-5c0-2-.5-4-2-8H9c-1.5 4-2 6-2 8a5 5 0 0 0 5 5Z"/>'),
    // shopping-bag → marché
    'Marché':       _s('<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>'),
    // mountain → site naturel
    'Site naturel': _s('<path d="m8 3 4 8 5-5 5 15H2L8 3z"/>'),
    // map-pin → autre
    'Autre':        _s('<path d="M20 10c0 5-8 12-8 12s-8-7-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>'),
    // globe → pays
    '_pays':        _s('<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>'),
    // building → ville
    '_ville':       _s('<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M9 6h.01M15 6h.01M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/>'),
  };

  // ── Continents ──────────────────────────────────────────────────────────
  var CONTINENTS = [
    { name: 'Europe',    lat: 54,  lng: 15,   zoom: 3.2 },
    { name: 'Asie',      lat: 34,  lng: 100,  zoom: 2.8 },
    { name: 'Amériques', lat: 10,  lng: -80,  zoom: 2.3 },
    { name: 'Afrique',   lat: 0,   lng: 20,   zoom: 3.0 },
    { name: 'Oceanie',   lat: -25, lng: 135,  zoom: 3.2 },
  ];

  // --- ÉTAT GLOBAL DE LA CARTE ---
  var activeCountryId   = null;  // id_pays en base du pays sélectionné (filtre les lieux)
  var leafMarkers       = {};    // { id_lieu: mapboxgl.Marker } — markers DOM des lieux individuels
  var hoveredCountryId  = null;  // code ISO3 du pays sous le curseur (pour le hover)
  var selectedCountryId = null;  // code ISO3 du pays sélectionné (contour bleu)
  var survolMarker      = false; // true quand la souris est sur un pin → bloque la hover card pays

  // ── URL state : lit ?lng=&lat=&zoom= pour réouvrir la même vue au rechargement
  // Si l'URL contient des coordonnées valides, on repart de cette vue
  // (utile pour partager un lien ou recharger sans perdre sa position)
  function lireEtatUrl() {
    var p    = new URLSearchParams(window.location.search);
    var lng  = parseFloat(p.get('lng'));
    var lat  = parseFloat(p.get('lat'));
    var zoom = parseFloat(p.get('zoom'));
    // isNaN = true si la conversion a échoué (param absent ou invalide)
    if (isNaN(lng) || isNaN(lat) || isNaN(zoom)) return null;
    return { lng: lng, lat: lat, zoom: zoom };
  }
  var etatUrl = lireEtatUrl(); // null si l'URL n'a pas de coordonnées

  // Vue par défaut au chargement : globe entier, légèrement incliné
  // [longitude, latitude] — attention, Mapbox met toujours lng avant lat
  var CENTRE_DEFAUT = [20, 30];
  var ZOOM_DEFAUT   = 1.8; // zoom 1.8 = on voit tout le globe

  // ── Initialisation du globe 3D ────────────────────────────────────────
  // new mapboxgl.Map() crée le canvas WebGL dans la <div id="map">.
  // Le secret du globe c'est projection:'globe' — sans ça, carte plate classique.
  //
  //   container: 'map'          → id de la <div> qui accueille le canvas WebGL
  //   style: url                → thème visuel (couleurs, routes, labels...). On peut
  //                               le changer à chaud via map.setStyle() sans rechargement
  //   center: [lng, lat]        → ATTENTION : longitude d'abord, latitude ensuite !
  //                               Convention GeoJSON/Mapbox — l'inverse de Google Maps
  //   zoom: 1.8                 → 0 = planète entière, 22 = détail d'une rue. 1.8 = globe entier
  //   pitch: 45                 → inclinaison en degrés. 0 = vue du dessus, 45 = isométrique
  //   bearing: -10              → rotation. 0 = nord en haut. -10 = légère impression de profondeur
  //   projection: 'globe'       → active le rendu globe 3D (Mapbox GL JS v2.9+)
  //   antialias: true           → lissage WebGL des bords, plus joli mais légèrement plus lent
  //   renderWorldCopies: false  → sans ça la carte se répète à l'infini sur les côtés
  var map = new mapboxgl.Map({
    container:         'map',
    style:             STYLES[currentStyleKey].url,
    center:            etatUrl ? [etatUrl.lng, etatUrl.lat] : CENTRE_DEFAUT,
    zoom:              etatUrl ? etatUrl.zoom              : ZOOM_DEFAUT,
    pitch:             45,
    bearing:           -10,
    projection:        'globe',     // ← active le rendu globe 3D
    antialias:         true,
    renderWorldCopies: false,
  });

  map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
  map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');

  // Handler clic pays enregistré une seule fois (ne dépend pas du style)
  initPaysClic();

  // 'style.load' se déclenche à chaque fois qu'un style est chargé
  // (au démarrage ET quand on change de style via le switcher)
  // On ne peut ajouter des sources/couches QU'après cet événement
  map.on('style.load', function () {
    applyAtmosphere();          // brouillard + étoiles + ciel (si style dark/satellite)
    applyLightPreset();         // éclairage dynamique jour/nuit (style Standard uniquement)
    applyTerrain();             // relief 3D (DEM = Digital Elevation Model)
    addCountryLayer();          // couche de remplissage/contour des pays (tileset Mapbox)
    addLieuxClusters();         // source GeoJSON de nos lieux + clustering

    // regions.json fait 36 Mo → on ne le charge pas immédiatement
    // on attend que l'utilisateur zoome assez (>= 3.5) pour que ça serve vraiment
    map.once('zoom', tryLoadRegions);
    tryLoadRegions();
  });

  // 'sourcedata' se déclenche chaque fois que les tuiles d'une source sont chargées/mises à jour
  // On l'utilise pour créer/supprimer les markers DOM des lieux individuels
  map.on('sourcedata', function (e) {
    if (e.sourceId === 'lieux-source' && e.isSourceLoaded) {
      updateLeafMarkers(); // recréé les pins DOM pour ce qui est visible
    }
  });

  function tryLoadRegions() {
    if (map.getZoom() >= 3.5 && !map.getSource('regions-source')) {
      addRegionsLayer();
    } else if (map.getZoom() < 3.5) {
      map.once('zoom', tryLoadRegions);
    }
  }

  map.on('movestart', function () { document.getElementById('map').classList.add('map-moving'); });
  map.on('moveend',   function () { document.getElementById('map').classList.remove('map-moving'); updateLeafMarkers(); ecrireEtatUrl(); });

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

  // ── Éclairage dynamique (style Standard) ─────────────────────────────────
  // Le style "standard" expose une config 'lightPreset' (dawn/day/dusk/night)
  // qui change l'ambiance lumineuse + le ciel. Ignoré par les styles classiques.
  function applyLightPreset() {
    var cfg = STYLES[currentStyleKey] || {};
    if (!cfg.isStandard) return;
    try {
      map.setConfigProperty('basemap', 'lightPreset', currentLightPreset);
    } catch (e) {
      // Le style n'est pas encore prêt : on réessaie quand la carte est stabilisée.
      map.once('idle', function () {
        try { map.setConfigProperty('basemap', 'lightPreset', currentLightPreset); } catch (_) {}
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

    // Clic région : zoom sur la zone.
    // On n'intervient qu'après qu'un pays soit sélectionné (phase 2).
    // Sans ça, le handler regions-fill intercepte les clics avant pays-fill
    // et empêche la détection du pays.
    map.on('click', 'regions-fill', function (e) {
      if (!e.features.length) return;
      if (!selectedCountryId) return;   // laisse pays-fill gérer le clic
      // On ne recadre sur une région QUE depuis une vue large (pays).
      // Si on explore déjà une ville (zoom >= 5.5), on ne touche pas la caméra :
      // sinon un clic qui rate un cluster recadre toute la région → dezoom.
      if (map.getZoom() >= 5.5) return;
      e.preventDefault();
      var bounds = getFeatureBounds(e.features[0]);
      if (!bounds) return;
      map.fitBounds(bounds, { padding: 60, pitch: 50, duration: 1200, maxZoom: 9, essential: true });
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
  // On utilise le tileset vectoriel officiel 'mapbox.country-boundaries-v1' :
  // les vraies frontières géographiques de tous les pays, fournies par Mapbox.
  // On ne stocke PAS les frontières dans notre BDD — ça ferait des Go de données.
  //
  // Flux quand on clique sur la France :
  //   1. map.on('click') → queryRenderedFeatures() interroge les tuiles visibles
  //   2. On récupère f.id = 'FRA' (code ISO3 fourni par le tileset)
  //   3. trouverPaysDB('FRA') cherche ce code dans notre tableau countries[] (depuis MySQL)
  //   4. Trouvé → on filtre les lieux + panneau avec les stats de la BDD
  //   5. Pas trouvé → panneau vide "Aucun avis pour ce pays"
  //
  // La tolérance variable (tol) résout les petits pays insulaires (Japon, Grèce...)
  // qui font quelques pixels à faible zoom. On élargit la zone de détection selon le zoom.
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
        filter:         ['any',
                          ['==', ['get', 'worldview'], 'all'],
                          ['in', 'US', ['get', 'worldview']]],
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
        filter:         ['any',
                          ['==', ['get', 'worldview'], 'all'],
                          ['in', 'US', ['get', 'worldview']]],
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
      if (survolMarker) { if (hoverCard) hoverCard.hidden = true; return; } // souris sur un pin
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

  }

  // ── Clic pays (handler générique avec tolérance de bounding-box) ─────────
  // Remplace map.on('click','pays-fill') pour résoudre le problème des nations
  // insulaires (Japon, Grèce, Indonésie…) dont les frontières font quelques pixels
  // à faible zoom et sont impossibles à viser précisément.
  // La tolérance diminue au fur et à mesure qu'on zoome (plus de précision = moins
  // de risque de sélectionner le pays voisin).
  function initPaysClic() {
    map.on('click', function (e) {
      if (addMode) return;
      if (e.defaultPrevented) return; // une région a déjà capturé ce clic
      if (!map.getLayer('pays-fill')) return; // style pas encore chargé

      // Ignore les clics qui partent d'un pin de lieu (DOM marker) ou de sa popup :
      // Mapbox gère déjà l'ouverture/le contenu du popup pour ces clics.
      var tgt = e.originalEvent && e.originalEvent.target;
      if (tgt && tgt.closest && tgt.closest('.abs-marker, .mapboxgl-popup')) return;

      // Ignore aussi les clics sur une bulle de cluster GL (géré par son propre
      // handler qui zoome) — sinon on ouvrirait le panneau pays par-dessus.
      if (map.getLayer('clusters-lieux')) {
        var clusterHit = map.queryRenderedFeatures(e.point, { layers: ['clusters-lieux'] });
        if (clusterHit.length) return;
      }

      var zoom = map.getZoom();
      var tol  = zoom < 3 ? 10 : zoom < 5 ? 6 : zoom < 7 ? 4 : 3;
      var bbox = [[e.point.x - tol, e.point.y - tol],
                  [e.point.x + tol, e.point.y + tol]];
      var features = map.queryRenderedFeatures(bbox, { layers: ['pays-fill'] });
      if (!features.length) return;

      var f    = features[0];
      var iso3 = String(f.id);
      if (!iso3 || iso3 === 'undefined' || iso3 === 'null') return;

      // Clic sur le pays déjà sélectionné → ne rien faire (évite qu'un clic
      // accidentel près d'un pin désélectionne le pays). La désélection passe
      // par le bouton ✕ du panneau ou « Monde entier ».
      if (selectedCountryId === iso3) return;

      // Efface l'ancienne sélection visuelle
      if (selectedCountryId) {
        map.setFeatureState(
          { source: 'pays-source', sourceLayer: 'country_boundaries', id: selectedCountryId },
          { selected: false }
        );
      }

      selectedCountryId = iso3;
      map.setFeatureState(
        { source: 'pays-source', sourceLayer: 'country_boundaries', id: iso3 },
        { selected: true }
      );

      // On ne fait un flyTo QUE depuis une vue large (globe / continent).
      // Si on explore déjà le pays (zoom >= 4.5), on ne touche pas la caméra :
      // ça évite le dezoom intempestif quand un clic rate un pin et tombe sur
      // le pays en dessous.
      var dejaProche = map.getZoom() >= 4.5;

      var paysDB = trouverPaysDB(iso3);
      if (paysDB) {
        // Pays dans notre BDD : filtre marqueurs + zoom + panneau
        activeCountryId = String(paysDB.id_pays);
        syncCountryFilter(paysDB.id_pays);
        refreshLieuxSource();
        ouvrirPanneauPays(iso3, paysDB);
        if (!dejaProche) {
          map.flyTo({
            center: [parseFloat(paysDB.lng), parseFloat(paysDB.lat)],
            zoom: 5, pitch: 50, duration: 1800, essential: true,
          });
        }
      } else {
        // Pays hors BDD : état vide dans le panneau + zoom simple
        ouvrirPanneauPays(iso3, null);
        if (!dejaProche) {
          map.flyTo({
            center: [e.lngLat.lng, e.lngLat.lat],
            zoom: 5, pitch: 45, duration: 1600, essential: true,
          });
        }
      }
    });
  }

  // ── Panneau latéral pays ─────────────────────────────────────────────────
  // Remplace le popup flottant : slide depuis la droite, ne couvre pas le globe.

  function ouvrirPanneauPays(iso3, paysDB) {
    var panel   = document.getElementById('pays-panel');
    var content = document.getElementById('pays-panel-content');
    if (!panel || !content) return;

    var nomPays = paysDB ? escapeHtml(paysDB.nom)
                         : escapeHtml(paysNoms[iso3] || iso3);

    var html = '';

    if (paysDB) {
      // ── Pays avec données ────────────────────────────────────────────────
      var nb     = parseInt(paysDB.places_count || 0, 10);
      var note   = paysDB.avg_rating ? parseFloat(paysDB.avg_rating) : null;
      var link   = pathPays + '?id=' + encodeURIComponent(String(paysDB.id_pays));

      // Image représentative : premier lieu du pays qui en a une
      var placeImg = places.find(function (p) {
        return String(p.id_pays) === String(paysDB.id_pays) && p.image_url;
      });
      if (placeImg) {
        html += '<img class="pp-img" src="' + escapeHtml(placeImg.image_url)
              + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">';
      }

      // Header : nom + stats
      var statsHTML = '';
      if (note) {
        statsHTML += '<span class="pp-rating">'
          + renderStars(note)
          + ' <span class="pp-rating-num">' + note.toFixed(1) + '</span></span>';
      }
      statsHTML += '<span>' + nb + ' lieu' + (nb > 1 ? 'x' : '') + '</span>';

      html += '<div class="pp-header">'
            + '<div class="pp-nom">' + nomPays + '</div>'
            + '<div class="pp-stats">' + statsHTML + '</div>'
            + '</div>';

      // Derniers avis
      var avis = paysDB.derniers_avis || [];
      if (avis.length > 0) {
        html += '<h4 class="pp-section-title">Derniers avis</h4>';
        avis.forEach(function (a) {
          var stars = '';
          for (var i = 1; i <= 5; i++) {
            stars += i <= parseInt(a.note, 10) ? '★' : '☆';
          }
          var desc = a.description
            ? escapeHtml(String(a.description).substring(0, 120)) + (a.description.length > 120 ? '…' : '')
            : '';
          html += '<div class="pp-avis-card">'
                + '<div class="pp-avis-lieu">' + escapeHtml(a.lieu_nom || '') + '</div>'
                + '<div class="pp-avis-meta">'
                +   '<span class="pp-avis-note">' + stars + '</span>'
                +   '<span>· ' + escapeHtml((a.prenom || '') + ' ' + (a.nom_user || '')) + '</span>'
                + '</div>'
                + (desc ? '<div class="pp-avis-desc">' + desc + '</div>' : '')
                + '</div>';
        });
      }

      html += '<a class="pp-cta" href="' + escapeHtml(link) + '">Voir tous les avis →</a>';

    } else {
      // ── Pays sans données ────────────────────────────────────────────────
      html += '<div class="pp-header"><div class="pp-nom">' + nomPays + '</div></div>'
            + '<div class="pp-empty">'
            + '<div class="pp-empty-icon">🌍</div>'
            + '<p>Aucun avis pour ce pays.</p>'
            + '<p>Sois le premier à explorer !</p>'
            + '</div>';
    }

    content.innerHTML = html;
    panel.hidden = false;
    document.body.classList.add('pays-panel-open');
  }

  function fermerPanneauPays() {
    var panel = document.getElementById('pays-panel');
    if (panel) panel.hidden = true;
    document.body.classList.remove('pays-panel-open');
  }

  // Bouton fermer du panneau
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('pays-panel-close');
    if (btn) {
      btn.addEventListener('click', function () {
        fermerPanneauPays();
        deselectionnerPays();
      });
    }
  });

  // ── Clustering natif unifié ───────────────────────────────────────────────
  //
  // Architecture du clustering en 2 niveaux :
  //
  // NIVEAU 1 — Bulles (clusters) : dessinées par des couches GL (performant, GPU)
  //   → couche 'clusters-lieux' (circle) + 'clusters-lieux-count' (symbol/texte)
  //   → filtrage : ['has', 'point_count'] (seuls les groupes ont cette propriété)
  //
  // NIVEAU 2 — Pins individuels (feuilles) : markers HTML créés par updateLeafMarkers()
  //   → créés UNIQUEMENT pour les points visibles à l'écran (viewport)
  //   → supprimés dès qu'ils sortent du cadre (sinon on aurait des centaines de <div>)
  //   → filtrage : ['!', ['has', 'point_count']] (tout ce qui n'est pas un cluster)
  //
  // Quand on change un filtre → refreshLieuxSource() → setData() → Mapbox re-clusterise seul

  // Applique le filtre par type d'avis sur une liste de lieux.
  // 'avecAvis' = seulement les lieux qui ont au moins 1 avis
  // 'mesAvis' = seulement les lieux où l'utilisateur connecté a posté
  function appliquerFiltreAvis(liste) {
    if (avisFilter === 'avecAvis') {
      return liste.filter(function (p) { return parseInt(p.review_count || 0, 10) > 0; });
    }
    if (avisFilter === 'mesAvis') {
      return liste.filter(function (p) { return mesAvisSet.has(parseInt(p.id_lieu, 10)); });
    }
    return liste;
  }

  // Construit la FeatureCollection GeoJSON à injecter dans la source Mapbox.
  // Applique tous les filtres actifs : pays sélectionné + type d'avis + catégorie.
  // Chaque Feature = { type:'Feature', properties:{id_lieu}, geometry:{type:'Point', coordinates:[lng,lat]} }
  // Note : coordonnées en [longitude, latitude] — convention GeoJSON/Mapbox (lng avant lat)
  function buildLieuxFeatures() {
    var base = activeCountryId
      ? places.filter(function (p) { return String(p.id_pays) === String(activeCountryId); })
      : places;
    return appliquerFiltreAvis(base)
      .filter(function (p) { return p.type !== 'pays'; })
      // Filtre par catégorie (vide = tout afficher)
      .filter(function (p) {
        return categoryFilter.size === 0 || categoryFilter.has(p.categorie);
      })
      .map(function (p) {
        var lat = parseFloat(p.lat);
        var lng = parseFloat(p.lng);
        if (isNaN(lat) || isNaN(lng)) return null;
        return {
          type: 'Feature',
          properties: { id_lieu: p.id_lieu },
          geometry:   { type: 'Point', coordinates: [lng, lat] },
        };
      })
      .filter(Boolean);
  }

  // Reconstruit les données et les injecte dans la source Mapbox.
  // Mapbox recalcule le clustering automatiquement après setData().
  // À appeler après chaque changement de filtre (pays, catégorie, type d'avis).
  function refreshLieuxSource() {
    var feats = buildLieuxFeatures();
    updateCount(feats.length);
    var src = map.getSource('lieux-source');
    if (src) src.setData({ type: 'FeatureCollection', features: feats });
  }

  // ── Clustering : 2 niveaux d'affichage ───────────────────────────────
  // NIVEAU 1 — Bulles GL : dessinées par le GPU via des couches Mapbox.
  //   → Très performant, même avec des centaines de points.
  //   → Filtre : ['has', 'point_count'] — seuls les groupes ont cette propriété.
  //
  // NIVEAU 2 — Markers DOM : éléments HTML créés par updateLeafMarkers().
  //   → Créés uniquement pour ce qui est visible à l'écran (sinon des centaines de <div>).
  //   → Filtre : ['!', ['has', 'point_count']] — tout ce qui n'est PAS un cluster.
  //
  // Passage niveau 1 → 2 à clusterMaxZoom (14) :
  //   zoom < 14  → Mapbox regroupe → bulles GL
  //   zoom >= 14 → Mapbox arrête → updateLeafMarkers() crée les markers HTML individuels
  //
  // Changement de filtre → refreshLieuxSource() → setData() → Mapbox recalcule seul.
  // Crée la source clusterisée + les couches GL de clusters. Appelée au style.load.
  function addLieuxClusters() {
    if (map.getSource('lieux-source')) return;

    var feats = buildLieuxFeatures();
    updateCount(feats.length);

    map.addSource('lieux-source', {
      type:          'geojson',
      data:          { type: 'FeatureCollection', features: feats },
      cluster:       true,
      clusterMaxZoom: 14,   // au-delà : pins DOM individuels (updateLeafMarkers)
      clusterRadius:  40,
    });

    // Bulle cluster (couleur + taille selon le nombre de points groupés)
    map.addLayer({
      id:     'clusters-lieux',
      type:   'circle',
      source: 'lieux-source',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color':        ['step', ['get', 'point_count'],
                                  '#818cf8', 5, '#6366f1', 20, '#4f46e5'],
        'circle-radius':       ['step', ['get', 'point_count'],
                                  18, 5, 24, 20, 30],
        'circle-stroke-width': 2,
        'circle-stroke-color': 'rgba(255,255,255,0.65)',
        'circle-opacity':      0.92,
      },
    });

    // Compteur au centre du cluster
    map.addLayer({
      id:     'clusters-lieux-count',
      type:   'symbol',
      source: 'lieux-source',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '{point_count_abbreviated}',
        'text-size':  13,
        'text-font':  ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
      },
      paint: { 'text-color': '#fff' },
    });

    // Clic sur un cluster → zoom pour l'éclater.
    // On pousse le zoom au-delà du simple "expansion zoom" (qui sépare tout juste
    // les points) pour arriver directement à un niveau où on voit les détails.
    map.on('click', 'clusters-lieux', function (e) {
      var f = map.queryRenderedFeatures(e.point, { layers: ['clusters-lieux'] })[0];
      if (!f) return;
      map.getSource('lieux-source').getClusterExpansionZoom(f.properties.cluster_id, function (err, z) {
        if (err) return;
        var cible = Math.min(Math.max(z + 2, map.getZoom() + 2.5), 16);
        map.easeTo({ center: f.geometry.coordinates, zoom: cible, duration: 700, essential: true });
      });
    });

    map.on('mouseenter', 'clusters-lieux', function () { map.getCanvas().style.cursor = 'pointer'; });
    map.on('mouseleave', 'clusters-lieux', function () { map.getCanvas().style.cursor = ''; });
  }

  // Crée / supprime les DOM markers pour les points non-clusterisés dans le viewport.
  // Appelée sur moveend + sourcedata. Keyed par id_lieu pour éviter les doublons.
  function updateLeafMarkers() {
    if (!map.getSource('lieux-source')) return;

    var features = map.querySourceFeatures('lieux-source', {
      filter: ['!', ['has', 'point_count']],
    });

    // Dédupliquer (un point peut apparaître dans plusieurs tuiles adjacentes)
    var seen    = {};
    var unique  = [];
    features.forEach(function (f) {
      var id = String(f.properties.id_lieu);
      if (!seen[id]) { seen[id] = true; unique.push(f); }
    });

    // Créer les markers manquants
    var inView = {};
    unique.forEach(function (f) {
      var id    = String(f.properties.id_lieu);
      inView[id] = true;
      if (!leafMarkers[id]) {
        var place = trouverLieuParId(id);
        if (!place) return;
        var el     = createPinEl(place);
        var marker = new mapboxgl.Marker({ element: el, anchor: 'center' })
          .setLngLat(f.geometry.coordinates.slice())
          .addTo(map);
        var popup = new mapboxgl.Popup({ offset: 28, maxWidth: '280px', className: 'abs-popup' })
          .setHTML(buildPopup(place));
        marker.setPopup(popup);
        leafMarkers[id] = marker;
      }
    });

    // Supprimer les markers qui ne sont plus dans le viewport
    Object.keys(leafMarkers).forEach(function (id) {
      if (!inView[id]) {
        leafMarkers[id].remove();
        delete leafMarkers[id];
      }
    });
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

  // Vide la sélection pays (feature-state, panel, filtre) sans changer le zoom.
  // Le zoom retour au globe est géré en dehors (bouton "Monde entier" uniquement).
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
    fermerPanneauPays();
    syncCountryFilter(null);
    refreshLieuxSource();
  }

  // ── Création d'un pin HTML ───────────────────────────────────────────────
  // Chaque lieu individuel (non clusterisé) a un marker DOM custom.
  // Structure du pin : cercle blanc + icône SVG selon la catégorie + badge note coloré
  //   + label "TripAdvisor style" (nom en gras + sous-titre catégorie à droite)
  // Le badge est coloré par palier : ★ vert (≥4.5), ambre (≥3), rouge (<3)
  function createPinEl(place) {
    var el   = document.createElement('div');
    var type = place.type || 'monument';
    el.className = 'abs-marker abs-pin--' + type;

    // Icône : pays/ville → générique ; monument → catégorie du lieu
    var iconKey = type === 'pays'  ? '_pays'
                : type === 'ville' ? '_ville'
                : (place.categorie || 'Autre');
    var icon = CAT_ICONS[iconKey] || CAT_ICONS['Autre'];

    // Badge note coloré par palier (vert→ambre→rouge) — seulement si note existante
    var rating = place.avg_rating ? parseFloat(place.avg_rating) : null;
    var badge = '';
    if (rating != null && !isNaN(rating)) {
      var tier = rating >= 4.5 ? 'exc' : rating >= 4 ? 'good' : rating >= 3 ? 'avg' : 'low';
      badge = '<div class="abs-pin-badge abs-pin-badge--' + tier + '">' + rating.toFixed(1) + '</div>';
    }

    // Label façon TripAdvisor : nom (gras) + sous-titre catégorie, à droite du pin
    var nom       = escapeHtml(place.name || '');
    var sousTitre = type === 'pays'  ? 'Pays'
                  : type === 'ville' ? 'Ville'
                  : escapeHtml(place.categorie || '');
    var label = '<div class="abs-pin-label">'
              + '<span class="abs-pin-name">' + nom + '</span>'
              + (sousTitre ? '<span class="abs-pin-cat">' + sousTitre + '</span>' : '')
              + '</div>';

    el.innerHTML = '<div class="abs-pin-circle">' + icon + '</div>' + badge + label;

    // IMPORTANT : on NE bloque PAS la propagation du click ici.
    // Mapbox Marker.setPopup() ouvre la popup via map.on('click') en testant
    // e.originalEvent.target — couper la propagation tuerait l'ouverture du popup.
    // Le clic pays est filtré côté handler (closest('.abs-marker')).
    //
    // Pour la hover card pays : un simple flag survolMarker suffit. Le handler
    // pays-fill mousemove sort tôt quand la souris est sur un pin.
    el.addEventListener('mouseenter', function () {
      survolMarker = true;
      var card = document.getElementById('pays-hover-card');
      if (card) card.hidden = true;
    });
    el.addEventListener('mouseleave', function () { survolMarker = false; });

    return el;
  }

  // renderMarkers() supprimée — remplacée par refreshLieuxSource() + updateLeafMarkers().

  // ── Popup ────────────────────────────────────────────────────────────────
  function buildPopup(place) {
    var rating = place.avg_rating ? parseFloat(place.avg_rating) : null;
    var ratingHTML = rating
      ? '<div class="popup-rating">' + renderStars(rating) +
        ' <span>' + rating.toFixed(1) + '/5</span>' +
        ' <small>(' + (place.review_count || 0) + ' avis)</small></div>'
      : '<div class="popup-rating no-rating">Aucun avis pour l\'instant</div>';

    var imgHTML = place.image_url
      ? '<img class="popup-image" src="' + escapeHtml(place.image_url) + '" alt="' + escapeHtml(place.name) + '" loading="lazy" onerror="this.style.display=\'none\'">'
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

  // updateVisibility() supprimée — le clustering GL gère nativement la densité par zoom.

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

      // Supprime les DOM markers individuels avant rechargement du style
      Object.values(leafMarkers).forEach(function (m) { m.remove(); });
      leafMarkers = {};
      map.setStyle(STYLES[key].url);
    });
  });

  // ── Préréglage lumière (jour / nuit) du style Standard ───────────────────
  document.querySelectorAll('.light-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var key = btn.dataset.light;
      if (!key) return;
      currentLightPreset = key;
      document.querySelectorAll('.light-btn').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');

      // Les presets ne marchent que sur le style Standard : on bascule dessus si besoin.
      if (!STYLES[currentStyleKey].isStandard) {
        currentStyleKey = 'standard';
        document.querySelectorAll('.style-btn').forEach(function (b) {
          b.classList.toggle('active', b.dataset.style === 'standard');
        });
        Object.values(leafMarkers).forEach(function (m) { m.remove(); });
        leafMarkers = {};
        map.setStyle(STYLES.standard.url); // applyLightPreset rappelé au style.load
      } else {
        applyLightPreset();
      }
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
        // Seul endroit où on repart au globe — les autres désélections gardent le zoom courant
        map.flyTo({ center: CENTRE_DEFAUT, zoom: ZOOM_DEFAUT, pitch: 45, bearing: -10, duration: 1800, essential: true });
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
        refreshLieuxSource();
        map.flyTo({ center: [cont.lng, cont.lat], zoom: cont.zoom, pitch: 45, duration: 1800, essential: true });
      });
      contList.appendChild(btn);
    });

    // On a beaucoup de pays : on n'affiche dans la nav que les plus actifs
    // (les plus de lieux référencés). Le filtre déroulant du haut garde la liste
    // complète pour ceux qui cherchent un pays précis.
    var paysPopulaires = countries.slice().sort(function (a, b) {
      return (parseInt(b.places_count || 0, 10)) - (parseInt(a.places_count || 0, 10));
    }).slice(0, 10);

    paysPopulaires.forEach(function (c) {
      var btn = document.createElement('button');
      btn.className   = 'nav-btn nav-country-btn';
      btn.dataset.id  = c.id_pays;
      var nb = parseInt(c.places_count || 0, 10);
      btn.innerHTML = escapeHtml(c.nom) + '<span class="nav-count">' + nb + '</span>';
      btn.addEventListener('click', function () {
        setActiveNav(btn);
        activeCountryId = String(c.id_pays);
        syncCountryFilter(c.id_pays);
        refreshLieuxSource();
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
          ouvrirPanneauPays(selectedCountryId, c);
        }
        var lat = parseFloat(c.lat || 0);
        var lng = parseFloat(c.lng || 0);
        map.flyTo({ center: [lng, lat], zoom: 5, pitch: 50, duration: 1800, essential: true });
      });

      // Mini-fiche au survol : aperçu note + image, posée à droite du panneau
      btn.addEventListener('mouseenter', function () { apercuPaysNav(c, btn); });
      btn.addEventListener('mouseleave', masquerApercuNav);

      countryList.appendChild(btn);
    });

    // ── Filtre par type de lieu (chips à bascule, multi-sélection) ──────────
    var catList = document.getElementById('nav-categories');
    if (catList) {
      categories.forEach(function (cat) {
        var libelle = cat.libelle || cat.nom || '';
        if (!libelle) return;
        var btn = document.createElement('button');
        btn.className   = 'nav-btn nav-cat-btn';
        btn.dataset.cat = libelle;
        var icon = CAT_ICONS[libelle] || CAT_ICONS['Autre'];
        btn.innerHTML = '<span class="nav-cat-ic">' + icon + '</span>' + escapeHtml(libelle);
        btn.addEventListener('click', function () {
          if (categoryFilter.has(libelle)) {
            categoryFilter.delete(libelle);
            btn.classList.remove('active');
          } else {
            categoryFilter.add(libelle);
            btn.classList.add('active');
          }
          refreshLieuxSource();
        });
        catList.appendChild(btn);
      });
    }
  }());

  function setActiveNav(btn) {
    // On ne touche pas aux chips catégories (.nav-cat-btn) : leur état actif
    // est géré indépendamment (multi-sélection du filtre par type).
    document.querySelectorAll('.nav-btn:not(.nav-cat-btn)').forEach(function (b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');
  }

  function syncCountryFilter(id) {
    var sel = document.getElementById('country-filter');
    if (sel) sel.value = id ? String(id) : '';
  }

  // ── Panneau gauche : repli (desktop) / tiroir (mobile) ───────────────────
  (function navDrawer() {
    var openBtn  = document.getElementById('nav-open-btn');
    var closeBtn = document.getElementById('nav-close-btn');
    var backdrop = document.getElementById('nav-backdrop');
    var panel    = document.getElementById('nav-panel');
    if (!panel) return;

    function ouvrir() {
      document.body.classList.add('map-nav-open');
      if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    }
    function fermer() {
      document.body.classList.remove('map-nav-open');
      if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    }

    // État initial : ouvert sur grand écran, replié sinon.
    if (window.innerWidth > 900) ouvrir(); else fermer();

    if (openBtn)  openBtn.addEventListener('click', ouvrir);
    if (closeBtn) closeBtn.addEventListener('click', fermer);
    if (backdrop) backdrop.addEventListener('click', fermer);

    // Sur mobile, sélectionner un continent / pays referme le tiroir
    // pour laisser voir la carte (les chips catégories, eux, ne ferment pas).
    panel.addEventListener('click', function (e) {
      if (window.innerWidth > 900) return;
      var t = e.target.closest('.nav-continent-btn, .nav-country-btn, .nav-world-btn');
      if (t) fermer();
    });
  }());

  // ── Sections repliables du panneau (clic sur le titre) ───────────────────
  (function navAccordion() {
    document.querySelectorAll('#nav-panel .nav-label').forEach(function (label) {
      label.setAttribute('role', 'button');
      label.setAttribute('tabindex', '0');
      label.addEventListener('click', function () {
        var section = label.closest('.nav-section');
        if (section) section.classList.toggle('collapsed');
      });
    });
  }());

  // ── Mini-fiche pays au survol dans la nav (réutilise #pays-hover-card) ────
  function apercuPaysNav(c, btn) {
    var card = document.getElementById('pays-hover-card');
    var wrap = document.getElementById('map-wrapper');
    if (!card || !wrap) return;

    var nb   = parseInt(c.places_count || 0, 10);
    var note = c.avg_rating ? parseFloat(c.avg_rating) : null;
    var img  = places.find(function (p) {
      return String(p.id_pays) === String(c.id_pays) && p.image_url;
    });

    var stats = nb + ' lieu' + (nb > 1 ? 'x' : '');
    if (note) stats += ' · ★ ' + note.toFixed(1) + '/5';

    card.innerHTML =
      (img ? '<img class="phc-img" src="' + escapeHtml(img.image_url) + '" alt="" onerror="this.style.display=\'none\'">' : '') +
      '<h5>' + escapeHtml(c.nom) + '</h5>' +
      '<div class="phc-stats">' + stats + '</div>';

    // Position : à droite du bouton, en coordonnées relatives au conteneur carte
    var b = btn.getBoundingClientRect();
    var w = wrap.getBoundingClientRect();
    card.style.left = (b.right - w.left + 10) + 'px';
    card.style.top  = (b.top - w.top) + 'px';
    card.hidden = false;
  }

  function masquerApercuNav() {
    var card = document.getElementById('pays-hover-card');
    if (card) card.hidden = true;
  }

  // ── Filtre pays (select du haut) ─────────────────────────────────────────
  var filterEl = document.getElementById('country-filter');
  if (filterEl) {
    filterEl.addEventListener('change', function () {
      var opt = filterEl.options[filterEl.selectedIndex];
      activeCountryId = filterEl.value || null;
      refreshLieuxSource();
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
      refreshLieuxSource();
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
  //
  // Flux complet :
  //   1. Clic bouton "+ Ajouter un lieu" → activeAddMode (curseur change)
  //   2. Clic sur la carte → marker temporaire jaune posé (draggable)
  //   3. Appel API géocodage inverse (Mapbox) → récupère ville + pays depuis les coords
  //   4. Popup avec formulaire pré-rempli (nom, catégorie, description, photo)
  //   5. Submit → fetch POST /lieu/creer (AJAX, pas de rechargement)
  //   6. PHP valide, insère en base, renvoie le lieu en JSON
  //   7. JS ajoute le lieu à places[] + refreshLieuxSource() → pin visible immédiatement

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

  // ── AJAX : pourquoi et comment ───────────────────────────────────────
  // Sans AJAX, un <form action="..."> classique rechargerait toute la page.
  //   → la carte disparaît, la position est perdue, mauvaise UX.
  //
  // Avec fetch (AJAX) on envoie les données en arrière-plan :
  //   → le serveur répond en JSON (pas en HTML), on met à jour juste le pin. Rien ne recharge.
  //
  // Décryptage :
  //   ev.preventDefault()          → annule le rechargement par défaut du form HTML
  //   fetch(url, {method:'POST'})  → requête HTTP asynchrone — n'attend pas, continue à tourner
  //   new FormData(form)           → emballe tous les champs + le fichier photo automatiquement
  //   credentials: 'same-origin'   → envoie le cookie de session (OBLIGATOIRE pour que PHP sache qui on est)
  //   .then(r => r.json())         → quand la réponse arrive (asynchrone), on parse le JSON
  //   places.push(nouveau)         → ajoute le lieu au tableau JS en mémoire
  //   refreshLieuxSource()         → Mapbox reçoit le nouveau GeoJSON et intègre le pin dans les clusters
  //   .catch(...)                  → réseau coupé → on affiche l'erreur sans crasher
  //
  // "Asynchrone" = fetch() ne bloque pas. La carte reste interactive pendant l'envoi.
  // Les .then() s'exécutent QUAND la réponse arrive, pas immédiatement.
  function soumettreFormulaire(ev) {
    ev.preventDefault();           // empêche le rechargement de page (comportement par défaut du form)
    var form = ev.target;
    var errBox = form.querySelector('.form-error');
    var btnSubmit = form.querySelector('button[type="submit"]');
    errBox.hidden = true;
    btnSubmit.disabled = true;     // désactive le bouton pour éviter le double-submit
    btnSubmit.textContent = 'Envoi…';

    // ↓ C'est ici que se passe l'AJAX : on envoie les données sans recharger la page
    fetch(pathCreer, {
      method: 'POST',
      body: new FormData(form),    // FormData emballe tout : champs texte + fichier photo
      credentials: 'same-origin', // envoie le cookie de session (sinon PHP ne sait pas qui on est)
    })
    // on chaîne les .then() : chaque étape reçoit le résultat de la précédente
    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
    // r.ok = true si HTTP 200-299, false si 400/500. On vérifie les deux (HTTP + logique PHP)
    .then(function (res) {
      if (!res.ok || !res.body.success) {
        // PHP a renvoyé une erreur → on l'affiche dans le form, on ne ferme rien
        errBox.textContent = res.body.erreur || 'Erreur inconnue.';
        errBox.hidden = false;
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Créer le lieu';
        return;
      }
      // ✅ Succès : PHP a inséré le lieu en base et renvoyé l'objet JSON complet
      var nouveau = res.body.lieu;  // { id_lieu, name, lat, lng, categorie, ... }
      places.push(nouveau);         // on l'ajoute au tableau JS en mémoire (pas de rechargement)
      nettoyerMarkerTemp();
      refreshLieuxSource();         // Mapbox reçoit le nouveau GeoJSON et place le pin dans les clusters
      // flyTo = animation de zoom sur le nouveau lieu pour confirmer visuellement
      map.flyTo({ center: [parseFloat(nouveau.lng), parseFloat(nouveau.lat)], zoom: 15, essential: true });
    })
    .catch(function () {
      // .catch = réseau coupé ou serveur planté → on n'a jamais eu de réponse JSON
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
