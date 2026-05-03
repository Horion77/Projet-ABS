/**
 * Carte Mapbox — marqueurs, popups, filtre par pays.
 * Requiert : mapboxgl (CDN), window.MAP_DATA { token, places, placePath }
 */
(function () {
  'use strict';
  if (typeof mapboxgl === 'undefined' || !window.MAP_DATA) {
    return;
  }

  var d = window.MAP_DATA;
  var places = d.places || [];
  var placeFile = d.placePath || 'place.php';

  mapboxgl.accessToken = d.token;

  var map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [0, 20],
    zoom: 2
  });
  map.addControl(new mapboxgl.NavigationControl());

  function escapeHtml(s) {
    if (s == null) {
      return '';
    }
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  var items = [];

  for (var i = 0; i < places.length; i += 1) {
    var place = places[i];
    var lat = place.lat;
    var lng = place.lng;
    if (lat == null || lng == null) {
      continue;
    }
    var lngN = parseFloat(lng, 10);
    var latN = parseFloat(lat, 10);
    if (isNaN(lngN) || isNaN(latN)) {
      continue;
    }

    var noteText = place.avg_rating ? String(place.avg_rating) + '/5' : 'Aucun avis';
    var idPays = place.id_pays == null ? '' : String(place.id_pays);
    var idLieu = place.id_lieu;
    var link = placeFile + '?id=' + encodeURIComponent(String(idLieu));
    var imgUrl = place.image_url ? String(place.image_url).trim() : '';
    var imgBlock =
      imgUrl !== ''
        ? '<p class="map-popup-img-wrap"><img class="map-popup-img" src="' +
          escapeHtml(imgUrl) +
          '" alt="" width="160" height="100" loading="lazy"></p>'
        : '';
    var inner =
      '<div class="map-popup-body">' +
      imgBlock +
      '<h3 class="map-popup-title">' + escapeHtml(place.name) + '</h3>' +
      '<p class="map-popup-line"><strong>Pays :</strong> ' + escapeHtml(place.country_name) + '</p>' +
      '<p class="map-popup-line"><strong>Note :</strong> ' + escapeHtml(noteText) + '</p>' +
      '<a class="map-popup-cta" href="' + escapeHtml(link) + '">Voir les avis</a>' +
      '</div>';
    var popup = new mapboxgl.Popup({ offset: 25 }).setHTML(inner);
    var marker = new mapboxgl.Marker({ color: '#0d5c63' })
      .setLngLat([lngN, latN])
      .setPopup(popup);
    items.push({ marker: marker, idPays: idPays, lng: lngN, lat: latN });
  }

  function getFilterValue() {
    var s = document.getElementById('map-country-filter');
    return s && s.value ? String(s.value) : '';
  }

  function visibleList() {
    var v = getFilterValue();
    if (!v) {
      return items;
    }
    return items.filter(function (it) {
      return it.idPays === v;
    });
  }

  function syncMarkers() {
    var v = getFilterValue();
    for (var a = 0; a < items.length; a += 1) {
      items[a].marker.remove();
    }
    for (var b = 0; b < items.length; b += 1) {
      if (!v || items[b].idPays === v) {
        items[b].marker.addTo(map);
      }
    }
  }

  function refit() {
    var list = visibleList();
    if (list.length === 0) {
      return;
    }
    var v = getFilterValue();
    if (v) {
      if (list.length === 1) {
        map.flyTo({ center: [list[0].lng, list[0].lat], zoom: 6, duration: 500 });
        return;
      }
      var bounds = new mapboxgl.LngLatBounds();
      for (var k = 0; k < list.length; k += 1) {
        bounds.extend([list[k].lng, list[k].lat]);
      }
      map.fitBounds(bounds, { padding: 50, maxZoom: 8 });
    }
  }

  for (var j = 0; j < items.length; j += 1) {
    items[j].marker.addTo(map);
  }

  map.on('load', function () {
    if (items.length === 0) {
      return;
    }
    if (items.length === 1) {
      map.flyTo({ center: [items[0].lng, items[0].lat], zoom: 6, duration: 0 });
      return;
    }
    var all = new mapboxgl.LngLatBounds();
    for (var m = 0; m < items.length; m += 1) {
      all.extend([items[m].lng, items[m].lat]);
    }
    try {
      map.fitBounds(all, { padding: 32, maxZoom: 3, duration: 500 });
    } catch (e) { /* solde */ }
  });

  var sel = document.getElementById('map-country-filter');
  if (sel) {
    sel.addEventListener('change', function () {
      syncMarkers();
      var v = getFilterValue();
      if (v) {
        refit();
      } else {
        if (items.length === 0) {
          map.flyTo({ center: [0, 20], zoom: 2, duration: 500 });
        } else if (items.length === 1) {
          map.flyTo({ center: [items[0].lng, items[0].lat], zoom: 6, duration: 500 });
        } else {
          var b = new mapboxgl.LngLatBounds();
          for (var p = 0; p < items.length; p += 1) {
            b.extend([items[p].lng, items[p].lat]);
          }
          try {
            map.fitBounds(b, { padding: 32, maxZoom: 3, duration: 500 });
          } catch (e) { /* ignore */ }
        }
      }
    });
  }
}());
