<?php include 'db_connect.php'; ?>
<style>
  #map {
    height: 500px;
    width: 100%;
    margin-top: 20px;
  }
  .input-group {
    max-width: 500px;
    margin: 0 auto;
  }
  .btn {
    margin-left: 5px;
  }
  .leaflet-tooltip {
    font-size: 10px;
    padding: 1px 4px;
    background-color: rgba(255, 255, 255, 0.7);
    border: 1px solid #333;
    border-radius: 3px;
    white-space: nowrap;
    z-index: 400; /* Below markers */
  }
  /* Tooltip text colors matching marker colors */
  .tooltip-0 { color: #00FF00; } /* Green - Item Accepted */
  .tooltip-1 { color: #0000FF; } /* Blue - Collected */
  .tooltip-2 { color: #FFFF00; } /* Yellow - Shipped */
  .tooltip-3 { color: #FFA500; } /* Orange - In-Transit */
  .tooltip-4 { color: #800080; } /* Violet - Arrived */
  .tooltip-5 { color: #00FFFF; } /* Cyan - Out for Delivery */
  .tooltip-6 { color: #FF69B4; } /* Pink - Ready to Pickup */
  .tooltip-7 { color: #FFD700; } /* Gold - Delivered */
  .tooltip-8 { color: #808080; } /* Grey - Picked-up */
  .tooltip-9 { color: #000000; } /* Black - Unsuccessful */
</style>
<!-- Include Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="col-lg-12">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <div class="d-flex w-100 px-1 py-2 justify-content-center align-items-center">
        <label for="ref_no">Enter Tracking Number</label>
        <div class="input-group col-sm-5">
          <input type="search" id="ref_no" class="form-control form-control-sm" placeholder="Type the tracking number here">
          <div class="input-group-append">
            <button type="button" id="track-btn" class="btn btn-sm btn-primary btn-gradient-primary">
              <i class="fa fa-search"></i> Track
            </button>
            <button type="button" id="stop-poll-btn" class="btn btn-sm btn-secondary">Stop</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div id="map"></div>
    </div>
  </div>
</div>

<script>
  // Initialize Leaflet map
  var map = L.map('map').setView([19.0760, 72.8777], 10);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
  }).addTo(map);

  // Define icons for each status with larger size
  var icons = {
    0: L.icon({ // Item Accepted by Courier
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
      iconSize: [30, 48], // Increased size
      iconAnchor: [15, 48], // Adjusted anchor
      popupAnchor: [0, -40]
    }),
    1: L.icon({ // Collected
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    2: L.icon({ // Shipped
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    3: L.icon({ // In-Transit (static)
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    4: L.icon({ // Arrived At Destination
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    5: L.icon({ // Out for Delivery
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-cyan.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    6: L.icon({ // Ready to Pickup
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-pink.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    7: L.icon({ // Delivered
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    8: L.icon({ // Picked-up
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    }),
    9: L.icon({ // Unsuccessful Delivery Attempt
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-black.png',
      iconSize: [30, 48],
      iconAnchor: [15, 48],
      popupAnchor: [0, -40]
    })
  };

  let pollInterval;
  let transitMarker = null;
  let transitPath = null;

  function track_now() {
    start_load();
    var tracking_num = $('#ref_no').val();
    if (tracking_num == '') {
      clearMap();
      end_load();
      return;
    }

    $.ajax({
      url: 'ajax.php?action=get_parcel_history',
      method: 'POST',
      data: { ref_no: tracking_num },
      error: function(err) {
        console.log(err);
        alert_toast("An error occurred: " + err.status + " " + err.statusText, 'error');
        end_load();
      },
      success: function(resp) {
        try {
          if (typeof resp === 'string') {
            resp = JSON.parse(resp);
          }
          if (Array.isArray(resp) && resp.length > 0) {
            clearMap();

            var bounds = [];
            var polylinePoints = [];
            var latestStatus = resp[resp.length - 1].status;

            // Process history entries
            resp.forEach(function(item, index) {
              if (item.latitude && item.longitude) {
                var latlng = [parseFloat(item.latitude), parseFloat(item.longitude)];
                var isCollectionPoint = index === 0;
                var statusIndex = isCollectionPoint ? 0 : parseInt(item.status_index, 10);
                var markerIcon = isCollectionPoint ? icons[0] : (icons[statusIndex] || icons[1]); // Fallback to blue
                var popupContent = `<b>${isCollectionPoint ? 'Collection Point' : item.status}</b><br>${item.date_created}`;

                // Add marker
                var marker = L.marker(latlng, { icon: markerIcon })
                  .addTo(map)
                  .bindPopup(popupContent)
                  .bindTooltip(isCollectionPoint ? 'Collection Point' : item.status, {
                    permanent: true,
                    direction: 'top',
                    className: 'leaflet-tooltip tooltip-' + (isCollectionPoint ? 0 : statusIndex),
                    offset: [0, -20], // Move tooltip above marker
                    zIndexOffset: -100 // Tooltip behind marker
                  });

                bounds.push(latlng);
                polylinePoints.push(latlng);
              }
            });

            // Draw route
            if (polylinePoints.length > 1) {
              L.polyline(polylinePoints, {
                color: '#ff4500',
                weight: 5,
                opacity: 0.8,
                dashArray: '10, 5'
              }).addTo(map);
            }

            // Real-time tracking for In-Transit
            if (latestStatus === 'In-Transit' && polylinePoints.length >= 2) {
              var startPoint = polylinePoints[polylinePoints.length - 2];
              var endPoint = polylinePoints[polylinePoints.length - 1];
              simulateTransit(startPoint, endPoint);
            }

            // Adjust map view
            if (bounds.length > 0) {
              map.fitBounds(bounds, { padding: [50, 50] });
            } else {
              alert_toast('No valid location data found.', 'warning');
            }
          } else {
            alert_toast('No history found for this tracking number.', 'error');
            clearMap();
          }
        } catch (e) {
          console.error('Error parsing response:', e);
          alert_toast('Invalid data format: ' + e.message, 'error');
        }
      },
      complete: function() {
        end_load();
      }
    });
  }

  function clearMap() {
    map.eachLayer(function(layer) {
      if (layer instanceof L.Marker || layer instanceof L.Polyline || layer instanceof L.CircleMarker) {
        map.removeLayer(layer);
      }
    });
    if (transitMarker) {
      map.removeLayer(transitMarker);
      transitMarker = null;
    }
    if (transitPath) {
      map.removeLayer(transitPath);
      transitPath = null;
    }
  }

  function simulateTransit(start, end) {
    var steps = 100;
    var step = 0;
    var latDiff = (end[0] - start[0]) / steps;
    var lngDiff = (end[1] - start[1]) / steps;

    if (transitMarker) {
      map.removeLayer(transitMarker);
    }

    function updateTransit() {
      if (step <= steps) {
        var currentLat = start[0] + (latDiff * step);
        var currentLng = start[1] + (lngDiff * step);
        transitMarker = L.circleMarker([currentLat, currentLng], {
          radius: 8, // Small round marker
          fillColor: '#4B0082', // Dark purple
          fillOpacity: 0.8,
          color: '#000',
          weight: 1
        })
          .addTo(map)
          .bindPopup('<b>In-Transit (Live)</b>'); // Popup on click, no tooltip

        // Update path
        if (transitPath) {
          map.removeLayer(transitPath);
        }
        transitPath = L.polyline([start, [currentLat, currentLng]], {
          color: '#ff4500',
          weight: 5,
          opacity: 0.8,
          dashArray: '10, 5'
        }).addTo(map);

        step++;
        setTimeout(updateTransit, 200);
      }
    }

    updateTransit();
  }

  function startPolling() {
    track_now();
    pollInterval = setInterval(track_now, 5000);
  }

  function stopPolling() {
    clearInterval(pollInterval);
    clearMap();
    alert_toast('Real-time tracking stopped.', 'info');
  }

  $('#track-btn').click(function() {
    clearInterval(pollInterval);
    startPolling();
  });

  $('#stop-poll-btn').click(function() {
    stopPolling();
  });

  $('#ref_no').on('search', function() {
    clearInterval(pollInterval);
    startPolling();
  });

  $(document).ready(function() {
    map.invalidateSize();
  });
</script>