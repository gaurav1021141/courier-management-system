<?php
include 'db_connect.php';
$status_arr = array(
    0 => "Item Accepted by Courier",
    1 => "Collected",
    2 => "Shipped",
    3 => "In-Transit",
    4 => "Arrived At Destination",
    5 => "Out for Delivery",
    6 => "Ready to Pickup",
    7 => "Delivered",
    8 => "Picked-up",
    9 => "Unsuccessful Delivery Attempt"
);

if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT * FROM parcels WHERE id = " . $_GET['id']);
    foreach ($qry->fetch_array() as $k => $val) {
        $$k = $val;
    }
}
?>

<style>
    #location-map {
        height: 300px;
        width: 100%;
        margin-top: 10px;
    }
</style>

<!-- Include Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container-fluid">
    <form action="" id="manage-parcel-status">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="form-group">
            <label for="status" class="control-label">Status</label>
            <select name="status" id="status" class="custom-select select2">
                <option value=""></option>
                <?php foreach ($status_arr as $k => $v): ?>
                    <option value="<?php echo $k ?>" <?php echo isset($status) && $status == $k ? "selected" : '' ?>><?php echo $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="location" class="control-label">Location (Address)</label>
            <input type="text" name="location" id="location" class="form-control" placeholder="Enter address (e.g., 123 Main St, Mumbai, India)" value="">
            <button type="button" id="get-location" class="btn btn-sm btn-primary mt-2">Use Current Location</button>
            <div id="location-map"></div>
        </div>
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Save Status</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Initialize Leaflet map
    var map = L.map('location-map').setView([19.0760, 72.8777], 10); // Default: Mumbai
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var marker = null;

    function updateMap(lat, lng, address) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker([lat, lng]).addTo(map)
            .bindPopup(address || 'Selected Location')
            .openPopup();
        map.setView([lat, lng], 15);
        $('#latitude').val(lat);
        $('#longitude').val(lng);
    }

    $('#location').on('input', function() {
        var address = $(this).val();
        if (address.length > 3) {
            $.ajax({
                url: 'https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(address) + '&format=json&limit=1',
                method: 'GET',
                success: function(data) {
                    if (data.length > 0) {
                        updateMap(data[0].lat, data[0].lon, address);
                    } else {
                        alert_toast('Address not found. Please refine your input.', 'warning');
                    }
                },
                error: function() {
                    alert_toast('Geocoding service unavailable. Try again later.', 'error');
                }
            });
        }
    });

    $('#get-location').click(function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                $('#location').val('Current Location (' + lat + ', ' + lng + ')');
                updateMap(lat, lng, 'Current Location');
                alert_toast('Location captured successfully', 'success');
            }, function(error) {
                alert_toast('Unable to get location: ' + error.message, 'error');
            });
        } else {
            alert_toast('Geolocation is not supported by your browser', 'error');
        }
    });

    $('#manage-parcel-status').submit(function(e) {
        e.preventDefault();
        start_load();

        if (!$('#latitude').val() || !$('#longitude').val()) {
            alert_toast('Please provide a valid location.', 'error');
            end_load();
            return;
        }

        $.ajax({
            url: 'ajax.php?action=update_parcel',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Status successfully updated", 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert_toast("An error occurred", 'error');
                    end_load();
                }
            },
            error: function() {
                alert_toast("An error occurred", 'error');
                end_load();
            }
        });
    });
});
</script>