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
            height: 200px;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        #price-table, #rates-table {
            margin-top: 15px;
            width: 100%;
            border-collapse: collapse;
        }
        #price-table th, #price-table td, #rates-table th, #rates-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        #price-table th, #rates-table th {
            background-color: #f2f2f2;
        }
        #price-table input, #rates-table input {
            width: 80px;
            text-align: right;
        }
    </style>
</head>
<body>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form action="" id="manage-parcel">
                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                <div id="msg" class=""></div>
                <div class="row">
                    <div class="col-md-6">
                        <b>Sender Information</b>
                        <div class="form-group">
                            <label for="sender_name" class="control-label">Name</label>
                            <input type="text" name="sender_name" id="sender_name" class="form-control form-control-sm" value="<?php echo isset($sender_name) ? $sender_name : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="sender_address" class="control-label">Address</label>
                            <input type="text" name="sender_address" id="sender_address" class="form-control form-control-sm" value="<?php echo isset($sender_address) ? $sender_address : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="sender_contact" class="control-label">Contact #</label>
                            <input type="text" name="sender_contact" id="sender_contact" class="form-control form-control-sm" value="<?php echo isset($sender_contact) ? $sender_contact : '' ?>" required>
                        </div>
                        <div class="map-container" id="sender_map"></div>
                    </div>
                    <div class="col-md-6">
                        <b>Recipient Information</b>
                        <div class="form-group">
                            <label for="recipient_name" class="control-label">Name</label>
                            <input type="text" name="recipient_name" id="recipient_name" class="form-control form-control-sm" value="<?php echo isset($recipient_name) ? $recipient_name : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="recipient_address" class="control-label">Address</label>
                            <input type="text" name="recipient_address" id="recipient_address" class="form-control form-control-sm" value="<?php echo isset($recipient_address) ? $recipient_address : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="recipient_contact" class="control-label">Contact #</label>
                            <input type="text" name="recipient_contact" id="recipient_contact" class="form-control form-control-sm" value="<?php echo isset($recipient_contact) ? $recipient_contact : '' ?>" required>
                        </div>
                        <div class="map-container" id="recipient_map"></div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dtype">Type</label>
                            <input type="checkbox" name="type" id="dtype" <?php echo isset($type) && $type == 1 ? 'checked' : '' ?> data-bootstrap-switch data-toggle="toggle" data-on="Deliver" data-off="Pickup" class="switch-toggle status_chk" data-size="xs" data-offstyle="info" data-width="5rem" value="1">
                            <small>Deliver = Deliver to Recipient Address</small>
                            <small>, Pickup = Pickup to nearest Branch</small>
                        </div>
                    </div>
                    <div class="col-md-6" id="" <?php echo isset($type) && $type == 1 ? 'style="display: none"' : '' ?>>
                        <?php if($_SESSION['login_branch_id'] <= 0): ?>
                            <div class="form-group" id="fbi-field">
                                <label for="from_branch_id" class="control-label">Branch Processed</label>
                                <select name="from_branch_id" id="from_branch_id" class="form-control select2" required="">
                                    <option value=""></option>
                                    <?php 
                                        $branches = $conn->query("SELECT *,concat(street,', ',city,', ',state,', ',zip_code,', ',country) as address FROM branches");
                                        while($row = $branches->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $row['id'] ?>" <?php echo isset($from_branch_id) && $from_branch_id == $row['id'] ? "selected":'' ?>><?php echo $row['branch_code']. ' | '.(ucwords($row['address'])) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="from_branch_id" value="<?php echo $_SESSION['login_branch_id'] ?>">
                        <?php endif; ?>  
                        <div class="form-group" id="tbi-field">
                            <label for="to_branch_id" class="control-label">Pickup Branch</label>
                            <select name="to_branch_id" id="to_branch_id" class="form-control select2">
                                <option value=""></option>
                                <?php 
                                    $branches = $conn->query("SELECT *,concat(street,', ',city,', ',state,', ',zip_code,', ',country) as address FROM branches");
                                    while($row = $branches->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id'] ?>" <?php echo isset($to_branch_id) && $to_branch_id == $row['id'] ? "selected":'' ?>><?php echo $row['branch_code']. ' | '.(ucwords($row['address'])) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <b>Parcel Information</b>
                <table class="table table-bordered" id="parcel-items">
                    <thead>
                        <tr>
                            <th>Weight (in kgs)</th>
                            <th>Height (in cms)</th>
                            <th>Length (in cms)</th>
                            <th>Width (in cms)</th>
                            <th>Price (in ₹)</th>
                            <?php if(!isset($id)): ?>
                            <th></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="number" name='weight[]' class="form-control" oninput="calculatePrice(this)" value="<?php echo isset($weight) ? $weight :'' ?>" required></td>
                            <td><input type="number" name='height[]' class="form-control" oninput="calculatePrice(this)" value="<?php echo isset($height) ? $height :'' ?>" required></td>
                            <td><input type="number" name='length[]' class="form-control" oninput="calculatePrice(this)" value="<?php echo isset($length) ? $length :'' ?>" required></td>
                            <td><input type="number" name='width[]' class="form-control" oninput="calculatePrice(this)" value="<?php echo isset($width) ? $width :'' ?>" required></td>
                            <td><input type="number" class="text-right number" name='price[]' value="<?php echo isset($price) ? $price : '0' ?>" step="0.01"></td>
                            <?php if(!isset($id)): ?>
                            <td><button class="btn btn-sm btn-danger" type="button" onclick="$(this).closest('tr').remove() && updatePriceTable() && calc()"><i class="fa fa-times"></i></button></td>
                            <?php endif; ?>
                        </tr>
                    </tbody>
                    <?php if(!isset($id)): ?>
                    <tfoot>
                        <th colspan="4" class="text-right">Total</th>
                        <th class="text-right" id="tAmount">0.00</th>
                        <th></th>
                    </tfoot>
                    <?php endif; ?>
                </table>
                <?php if(!isset($id)): ?>
                <div class="row">
                    <div class="col-md-12 d-flex justify-content-end">
                        <button class="btn btn-sm btn-primary bg-gradient-primary" type="button" id="new_parcel"><i class="fa fa-item"></i> Add Item</button>
                    </div>
                </div>
                <!-- Parcel Price Table -->
                <div class="row">
                    <div class="col-md-12">
                        <h4>Parcel Prices</h4>
                        <table id="price-table">
                            <thead>
                                <tr>
                                    <th>Weight (kgs)</th>
                                    <th>Height (cms)</th>
                                    <th>Length (cms)</th>
                                    <th>Width (cms)</th>
                                    <th>Price (in ₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamically populated -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Pricing Rates Table -->
                <div class="row">
                    <div class="col-md-12">
                        <h4>Pricing Rates (Editable)</h4>
                        <table id="rates-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Rate (in ₹ per unit)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Weight (per kg)</td>
                                    <td><input type="number" id="rate-weight" value="10" step="0.01" oninput="updateAllPrices()"></td>
                                </tr>
                                <tr>
                                    <td>Height (per cm)</td>
                                    <td><input type="number" id="rate-height" value="5" step="0.01" oninput="updateAllPrices()"></td>
                                </tr>
                                <tr>
                                    <td>Length (per cm)</td>
                                    <td><input type="number" id="rate-length" value="5" step="0.01" oninput="updateAllPrices()"></td>
                                </tr>
                                <tr>
                                    <td>Width (per cm)</td>
                                    <td><input type="number" id="rate-width" value="5" step="0.01" oninput="updateAllPrices()"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-footer border-top border-info">
            <div class="d-flex w-100 justify-content-center align-items-center">
                <button class="btn btn-flat bg-gradient-primary mx-2" form="manage-parcel">Save</button>
                <a class="btn btn-flat bg-gradient-secondary mx-2" href="./index.php?page=parcel_list">Cancel</a>
            </div>
        </div>
    </div>
</div>
<div id="ptr_clone" class="d-none">
    <table>
        <tr>
            <td><input type="number" name='weight[]' class="form-control" oninput="calculatePrice(this)" required></td>
            <td><input type="number" name='height[]' class="form-control" oninput="calculatePrice(this)" required></td>
            <td><input type="number" name='length[]' class="form-control" oninput="calculatePrice(this)" required></td>
            <td><input type="number" name='width[]' class="form-control" oninput="calculatePrice(this)" required></td>
            <td><input type="number" class="text-right number" name='price[]' value="0" step="0.01"></td>
            <td><button class="btn btn-sm btn-danger" type="button" onclick="$(this).closest('tr').remove() && updatePriceTable() && calc()"><i class="fa fa-times"></i></button></td>
        </tr>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize maps
    let senderMap = L.map('sender_map').setView([0, 0], 2);
    let recipientMap = L.map('recipient_map').setView([0, 0], 2);
    let senderMarker, recipientMarker;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(senderMap);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(recipientMap);

    async function updateSenderMap() {
        const address = document.getElementById('sender_address').value;
        if (address) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1`);
                const data = await response.json();
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    senderMap.setView([lat, lon], 15);
                    if (senderMarker) senderMap.removeLayer(senderMarker);
                    senderMarker = L.marker([lat, lon]).addTo(senderMap);
                    senderMarker.bindPopup(`Sender: ${address}`).openPopup();
                }
            } catch (error) {
                console.error('Error updating sender map:', error);
            }
        }
    }

    async function updateRecipientMap() {
        const address = document.getElementById('recipient_address').value;
        if (address) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1`);
                const data = await response.json();
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    recipientMap.setView([lat, lon], 15);
                    if (recipientMarker) recipientMap.removeLayer(recipientMarker);
                    recipientMarker = L.marker([lat, lon]).addTo(recipientMap);
                    recipientMarker.bindPopup(`Recipient: ${address}`).openPopup();
                }
            } catch (error) {
                console.error('Error updating recipient map:', error);
            }
        }
    }

    document.getElementById('sender_address').addEventListener('change', updateSenderMap);
    document.getElementById('recipient_address').addEventListener('change', updateRecipientMap);

    // Price calculation based on rates
    function calculatePrice(input) {
        const row = $(input).closest('tr');
        const weight = parseFloat(row.find('[name="weight[]"]').val()) || 0;
        const height = parseFloat(row.find('[name="height[]"]').val()) || 0;
        const length = parseFloat(row.find('[name="length[]"]').val()) || 0;
        const width = parseFloat(row.find('[name="width[]"]').val()) || 0;
        const rateWeight = parseFloat($('#rate-weight').val()) || 0;
        const rateHeight = parseFloat($('#rate-height').val()) || 0;
        const rateLength = parseFloat($('#rate-length').val()) || 0;
        const rateWidth = parseFloat($('#rate-width').val()) || 0;
        let price = (weight * rateWeight) + (height * rateHeight) + (length * rateLength) + (width * rateWidth);
        row.find('[name="price[]"]').val(price.toFixed(2));
        updatePriceTable();
        calc();
    }

    function updateAllPrices() {
        $('#parcel-items tbody tr').each(function() {
            const row = $(this);
            const weight = parseFloat(row.find('[name="weight[]"]').val()) || 0;
            const height = parseFloat(row.find('[name="height[]"]').val()) || 0;
            const length = parseFloat(row.find('[name="length[]"]').val()) || 0;
            const width = parseFloat(row.find('[name="width[]"]').val()) || 0;
            const rateWeight = parseFloat($('#rate-weight').val()) || 0;
            const rateHeight = parseFloat($('#rate-height').val()) || 0;
            const rateLength = parseFloat($('#rate-length').val()) || 0;
            const rateWidth = parseFloat($('#rate-width').val()) || 0;
            let price = (weight * rateWeight) + (height * rateHeight) + (length * rateLength) + (width * rateWidth);
            row.find('[name="price[]"]').val(price.toFixed(2));
        });
        updatePriceTable();
        calc();
    }

    function updatePriceTable() {
        const tbody = $('#price-table tbody');
        tbody.empty();
        $('#parcel-items tbody tr').each(function(index) {
            const weight = $(this).find('[name="weight[]"]').val() || '0';
            const height = $(this).find('[name="height[]"]').val() || '0';
            const length = $(this).find('[name="length[]"]').val() || '0';
            const width = $(this).find('[name="width[]"]').val() || '0';
            const price = $(this).find('[name="price[]"]').val() || '0';
            const row = `
                <tr>
                    <td>${weight}</td>
                    <td>${height}</td>
                    <td>${length}</td>
                    <td>${width}</td>
                    <td><input type="number" class="edit-price" data-row="${index}" value="${price}" step="0.01"></td>
                </tr>
            `;
            tbody.append(row);
        });
        $('.edit-price').on('change', function() {
            const rowIndex = $(this).data('row');
            const newPrice = $(this).val();
            $('#parcel-items tbody tr').eq(rowIndex).find('[name="price[]"]').val(parseFloat(newPrice).toFixed(2));
            calc();
        });
    }

    $('#dtype').change(function(){
        if($(this).prop('checked') == true){
            $('#tbi-field').hide();
        } else {
            $('#tbi-field').show();
        }
    });

    $('#new_parcel').click(function(){
        var tr = $('#ptr_clone tr').clone();
        $('#parcel-items tbody').append(tr);
        updatePriceTable();
        calc();
    });

    $('#manage-parcel').submit(function(e){
        e.preventDefault();
        start_load();
        if($('#parcel-items tbody tr').length <= 0){
            alert_toast("Please add at least 1 parcel information.","error");
            end_load();
            return false;
        }
        $.ajax({
            url: 'ajax.php?action=save_parcel',
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
                        location.href = 'index.php?page=parcel_list';
                    }, 2000);
                } else {
                    alert_toast('Failed to save parcel: ' + resp, "error");
                    end_load();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert_toast('Error saving parcel: ' + error, "error");
                end_load();
            }
        });
    });

    function displayImgCover(input,_this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#cover').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function calc(){
        var total = 0;
        $('#parcel-items [name="price[]"]').each(function(){
            var p = $(this).val().replace(/,/g, '');
            p = p > 0 ? p : 0;
            total = parseFloat(p) + parseFloat(total);
        });
        if($('#tAmount').length > 0){
            $('#tAmount').text(parseFloat(total).toLocaleString('en-US', {style:'decimal', maximumFractionDigits:2, minimumFractionDigits:2}));
        }
    }

    // Initial map update, price calc, and table update on page load
    $(document).ready(function() {
        updateSenderMap();
        updateRecipientMap();
        calculatePrice($('#parcel-items tbody tr:first input')[0]); // Initial calc for existing row
        updatePriceTable();
        calc();
    });
</script>
</body>
</html>