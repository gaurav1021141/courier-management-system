<?php if(!isset($conn)){ include 'db_connect.php'; } ?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        textarea {
            resize: none;
        }
        .map-container {
            height: 400px;
            width: 100%;
            margin-top: 20px;
        }
        #msg {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form action="" id="manage-branch">
                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                <div class="row">
                    <div class="col-md-12">
                        <div id="msg" class=""></div>

                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="street" class="control-label">Street/Building</label>
                                <textarea name="street" id="street" cols="30" rows="2" class="form-control" required><?php echo isset($street) ? $street : '' ?></textarea>
                            </div>
                            <div class="col-sm-6 form-group">
                                <label for="city" class="control-label">City</label>
                                <textarea name="city" id="city" cols="30" rows="2" class="form-control" required><?php echo isset($city) ? $city : '' ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="state" class="control-label">State</label>
                                <textarea name="state" id="state" cols="30" rows="2" class="form-control" required><?php echo isset($state) ? $state : '' ?></textarea>
                            </div>
                            <div class="col-sm-6 form-group">
                                <label for="zip_code" class="control-label">Zip Code/Postal Code</label>
                                <textarea name="zip_code" id="zip_code" cols="30" rows="2" class="form-control" required><?php echo isset($zip_code) ? $zip_code : '' ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="country" class="control-label">Country</label>
                                <textarea name="country" id="country" cols="30" rows="2" class="form-control" required><?php echo isset($country) ? $country : '' ?></textarea>
                            </div>
                            <div class="col-sm-6 form-group">
                                <label for="contact" class="control-label">Contact #</label>
                                <textarea name="contact" id="contact" cols="30" rows="2" class="form-control" required><?php echo isset($contact) ? $contact : '' ?></textarea>
                            </div>
                        </div>

                        <!-- Map Container -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="map-container" id="map"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer border-top border-info">
            <div class="d-flex w-100 justify-content-center align-items-center">
                <button class="btn btn-flat bg-gradient-primary mx-2" form="manage-branch">Save</button>
                <a class="btn btn-flat bg-gradient-secondary mx-2" href="./index.php?page=branch_list">Cancel</a>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize the map
    let map = L.map('map').setView([0, 0], 2);
    let marker;

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Function to update map based on form input
    async function updateMap() {
        const street = document.getElementById('street').value;
        const city = document.getElementById('city').value;
        const state = document.getElementById('state').value;
        const zip_code = document.getElementById('zip_code').value;
        const country = document.getElementById('country').value;

        if (street && city && state && zip_code && country) {
            const fullAddress = `${street}, ${city}, ${state} ${zip_code}, ${country}`;

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fullAddress)}&format=json&limit=1`);
                const data = await response.json();

                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);

                    map.setView([lat, lon], 15);

                    if (marker) {
                        map.removeLayer(marker);
                    }

                    marker = L.marker([lat, lon]).addTo(map);
                    marker.bindPopup(`
                        <h3>Branch Location</h3>
                        <p>${fullAddress}</p>
                    `).openPopup();
                }
            } catch (error) {
                console.error('Error updating map:', error);
            }
        }
    }

    // Add event listeners to update map when inputs change
    ['street', 'city', 'state', 'zip_code', 'country'].forEach(id => {
        document.getElementById(id).addEventListener('change', updateMap);
    });

    // Form submission
    $('#manage-branch').submit(function(e){
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_branch',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            success: function(resp){
                if(resp == 1){
                    alert_toast('Data successfully saved', "success");
                    setTimeout(function(){
                        location.href = 'index.php?page=branch_list';
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                alert_toast('Error saving data', "error");
                end_load();
            }
        });
    });

    function displayImgCover(input, _this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#cover').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Initial map update if editing existing branch
    $(document).ready(function() {
        updateMap();
    });
</script>
</body>
</html>