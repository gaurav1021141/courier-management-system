<?php
$address = "Mumbai, India";
$url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
$geocode = @file_get_contents($url);
if ($geocode === false) {
    echo "Geocoding failed";
} else {
    $data = json_decode($geocode, true);
    print_r($data);
}
?>