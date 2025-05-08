<?php
session_start();
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/courier-management-system/php_errors.log');

class Action {
    private $db;

    public function __construct() {
        ob_start();
        include 'db_connect.php';
        $this->db = $conn;
    }

    function __destruct() {
        $this->db->close();
        ob_end_flush();
    }

    function login(){
        extract($_POST);
        $qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM users where email = '".$email."' and password = '".md5($password)."'  ");
        if($qry->num_rows > 0){
            foreach ($qry->fetch_array() as $key => $value) {
                if($key != 'password' && !is_numeric($key))
                    $_SESSION['login_'.$key] = $value;
            }
            return 1;
        }else{
            return 2;
        }
    }

    function logout(){
        session_destroy();
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        header("location:login.php");
    }

    function login2(){
        extract($_POST);
        $qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM students where student_code = '".$student_code."' ");
        if($qry->num_rows > 0){
            foreach ($qry->fetch_array() as $key => $value) {
                if($key != 'password' && !is_numeric($key))
                    $_SESSION['rs_'.$key] = $value;
            }
            return 1;
        }else{
            return 3;
        }
    }

    function save_user(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
                if(empty($data)){
                    $data .= " $k='$v' ";
                }else{
                    $data .= ", $k='$v' ";
                }
            }
        }
        if(!empty($password)){
            $data .= ", password=md5('$password') ";
        }
        $check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
        if($check > 0){
            return 2;
            exit;
        }
        if(empty($id)){
            $save = $this->db->query("INSERT INTO users set $data");
        }else{
            $save = $this->db->query("UPDATE users set $data where id = $id");
        }
        if($save){
            return 1;
        }
    }

    function signup(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
                if($k =='password'){
                    if(empty($v))
                        continue;
                    $v = md5($v);
                }
                if(empty($data)){
                    $data .= " $k='$v' ";
                }else{
                    $data .= ", $k='$v' ";
                }
            }
        }
        $check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
        if($check > 0){
            return 2;
            exit;
        }
        if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
            $fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $move = move_uploaded_file($_FILES['img']['tmp_name'],'../assets/uploads/'. $fname);
            $data .= ", avatar = '$fname' ";
        }
        if(empty($id)){
            $save = $this->db->query("INSERT INTO users set $data");
        }else{
            $save = $this->db->query("UPDATE users set $data where id = $id");
        }
        if($save){
            if(empty($id))
                $id = $this->db->insert_id;
            foreach ($_POST as $key => $value) {
                if(!in_array($key, array('id','cpass','password')) && !is_numeric($key))
                    $_SESSION['login_'.$key] = $value;
            }
            $_SESSION['login_id'] = $id;
            return 1;
        }
    }

    function update_user(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','cpass','table')) && !is_numeric($k)){
                if($k =='password')
                    $v = md5($v);
                if(empty($data)){
                    $data .= " $k='$v' ";
                }else{
                    $data .= ", $k='$v' ";
                }
            }
        }
        if($_FILES['img']['tmp_name'] != ''){
            $fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
            $data .= ", avatar = '$fname' ";
        }
        $check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
        if($check > 0){
            return 2;
            exit;
        }
        if(empty($id)){
            $save = $this->db->query("INSERT INTO users set $data");
        }else{
            $save = $this->db->query("UPDATE users set $data where id = $id");
        }
        if($save){
            foreach ($_POST as $key => $value) {
                if($key != 'password' && !is_numeric($key))
                    $_SESSION['login_'.$key] = $value;
            }
            if($_FILES['img']['tmp_name'] != '')
                $_SESSION['login_avatar'] = $fname;
            return 1;
        }
    }

    function delete_user(){
        extract($_POST);
        $delete = $this->db->query("DELETE FROM users where id = ".$id);
        if($delete)
            return 1;
    }

    function save_system_settings(){
        extract($_POST);
        $data = '';
        foreach($_POST as $k => $v){
            if(!is_numeric($k)){
                if(empty($data)){
                    $data .= " $k='$v' ";
                }else{
                    $data .= ", $k='$v' ";
                }
            }
        }
        if($_FILES['cover']['tmp_name'] != ''){
            $fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
            $move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
            $data .= ", cover_img = '$fname' ";
        }
        $chk = $this->db->query("SELECT * FROM system_settings");
        if($chk->num_rows > 0){
            $save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']);
        }else{
            $save = $this->db->query("INSERT INTO system_settings set $data");
        }
        if($save){
            foreach($_POST as $k => $v){
                if(!is_numeric($k)){
                    $_SESSION['system'][$k] = $v;
                }
            }
            if($_FILES['cover']['tmp_name'] != ''){
                $_SESSION['system']['cover_img'] = $fname;
            }
            return 1;
        }
    }

    function save_image(){
        extract($_FILES['file']);
        if(!empty($tmp_name)){
            $fname = strtotime(date("Y-m-d H:i"))."_".(str_replace(" ","-",$name));
            $move = move_uploaded_file($tmp_name,'../assets/uploads/'. $fname);
            $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
            $hostName = $_SERVER['HTTP_HOST'];
            $path =explode('/',$_SERVER['PHP_SELF']);
            $currentPath = '/'.$path[1]; 
            if($move){
                return $protocol.'://'.$hostName.$currentPath.'/assets/uploads/'.$fname;
            }
        }
    }

    function save_branch(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id')) && !is_numeric($k)){
                if(empty($data)){
                    $data .= " $k='$v' ";
                }else{
                    $data .= ", $k='$v' ";
                }
            }
        }
        if(empty($id)){
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $i = 0;
            while($i == 0){
                $bcode = substr(str_shuffle($chars), 0, 15);
                $chk = $this->db->query("SELECT * FROM branches where branch_code = '$bcode'")->num_rows;
                if($chk <= 0){
                    $i = 1;
                }
            }
            $data .= ", branch_code='$bcode' ";
            $save = $this->db->query("INSERT INTO branches set $data");
        }else{
            $save = $this->db->query("UPDATE branches set $data where id = $id");
        }
        if($save){
            return 1;
        }
    }

    function delete_branch(){
        extract($_POST);
        $delete = $this->db->query("DELETE FROM branches where id = $id");
        if($delete){
            return 1;
        }
    }

    function save_parcel(){
        extract($_POST);
        foreach($price as $k => $v){
            $data = "";
            foreach($_POST as $key => $val){
                if(!in_array($key, array('id','weight','height','width','length','price')) && !is_numeric($key)){
                    if(empty($data)){
                        $data .= " $key='$val' ";
                    }else{
                        $data .= ", $key='$val' ";
                    }
                }
            }
            if(!isset($type)){
                $data .= ", type='2' ";
            }
            $data .= ", height='{$height[$k]}' ";
            $data .= ", width='{$width[$k]}' ";
            $data .= ", length='{$length[$k]}' ";
            $data .= ", weight='{$weight[$k]}' ";
            $price[$k] = str_replace(',', '', $price[$k]);
            $data .= ", price='{$price[$k]}' ";
            if(empty($id)){
                $i = 0;
                while($i == 0){
                    $ref = sprintf("%'012d",mt_rand(0, 999999999999));
                    $chk = $this->db->query("SELECT * FROM parcels where reference_number = '$ref'")->num_rows;
                    if($chk <= 0){
                        $i = 1;
                    }
                }
                $data .= ", reference_number='$ref' ";
                if($save[] = $this->db->query("INSERT INTO parcels set $data"))
                    $ids[]= $this->db->insert_id;
            }else{
                if($save[] = $this->db->query("UPDATE parcels set $data where id = $id"))
                    $ids[] = $id;
            }
        }
        if(isset($save) && isset($ids)){
            return 1;
        }
    }

    function delete_parcel(){
        extract($_POST);
        $delete = $this->db->query("DELETE FROM parcels where id = $id");
        if($delete){
            return 1;
        }
    }

    function update_parcel(){
        extract($_POST);
        // Update parcel status
        $update = $this->db->prepare("UPDATE parcels SET status = ? WHERE id = ?");
        $update->bind_param("ii", $status, $id);
        $update->execute();

        // Insert into parcel_tracks with coordinates
        $latitude = isset($latitude) && !empty($latitude) ? $latitude : null;
        $longitude = isset($longitude) && !empty($longitude) ? $longitude : null;
        $insert = $this->db->prepare("INSERT INTO parcel_tracks (parcel_id, status, latitude, longitude, date_created) VALUES (?, ?, ?, ?, NOW())");
        $insert->bind_param("iidd", $id, $status, $latitude, $longitude);
        $insert->execute();

        if ($update && $insert->affected_rows > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    function get_parcel_history(){
        $data = array();

        // Check if ref_no is provided in POST
        if (!isset($_POST['ref_no']) || empty($_POST['ref_no'])) {
            error_log("get_parcel_history: No ref_no provided in POST data");
            return json_encode($data);
        }

        $ref_no = $this->db->real_escape_string($_POST['ref_no']);
        error_log("get_parcel_history called with ref_no: $ref_no");

        // Get parcel details
        $parcel_query = $this->db->prepare("SELECT id, sender_address, from_branch_id, date_created FROM parcels WHERE reference_number = ?");
        if (!$parcel_query) {
            error_log("Parcel query prepare failed: " . $this->db->error);
            return json_encode(['error' => 'Database error']);
        }
        $parcel_query->bind_param("s", $ref_no);
        $parcel_query->execute();
        $parcel_result = $parcel_query->get_result();

        if ($parcel_result->num_rows <= 0) {
            error_log("No parcel found for ref_no: $ref_no");
            return json_encode($data);
        }

        $parcel = $parcel_result->fetch_assoc();
        $parcel_id = $parcel['id'];
        error_log("Parcel found: ID $parcel_id");

        // Get collection point coordinates
        $latitude = null;
        $longitude = null;
        $address = '';

        // Try branch address first
        if (!empty($parcel['from_branch_id'])) {
            $branch_query = $this->db->prepare("SELECT CONCAT(street, ', ', city, ', ', state, ', ', zip_code, ', ', country) AS address FROM branches WHERE id = ?");
            if (!$branch_query) {
                error_log("Branch query prepare failed: " . $this->db->error);
            } else {
                $branch_query->bind_param("i", $parcel['from_branch_id']);
                $branch_query->execute();
                $branch_result = $branch_query->get_result();
                $branch = $branch_result->fetch_assoc();
                if ($branch) {
                    $address = $branch['address'];
                    error_log("Branch address: $address");
                }
            }
        }

        // Fallback to sender address
        if (empty($address)) {
            $address = $parcel['sender_address'];
            error_log("Using sender address: $address");
        }

        // Geocode address
        if ($address) {
            $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
            error_log("Geocoding URL: $url");
            $geocode = @file_get_contents($url);
            if ($geocode === false) {
                error_log("Geocoding failed for address: $address");
                // Fallback to default coordinates
                $latitude = 19.0760;
                $longitude = 72.8777;
                error_log("Using default coordinates for collection point");
            } else {
                $geo_data = json_decode($geocode, true);
                if (!empty($geo_data)) {
                    $latitude = $geo_data[0]['lat'];
                    $longitude = $geo_data[0]['lon'];
                    error_log("Geocoded coordinates: lat=$latitude, lon=$longitude");
                } else {
                    error_log("No geocoding results for address: $address");
                }
            }
        }

        // Add collection point
        if ($latitude !== null && $longitude !== null) {
            $data[] = array(
                'status' => 'Item Accepted by Courier',
                'status_index' => 0,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'date_created' => date("M d, Y h:i A", strtotime($parcel['date_created']))
            );
        }

        // Get history from parcel_tracks
        $history_query = $this->db->prepare("SELECT status, latitude, longitude, date_created FROM parcel_tracks WHERE parcel_id = ? ORDER BY date_created ASC");
        if (!$history_query) {
            error_log("History query prepare failed: " . $this->db->error);
            return json_encode(['error' => 'Database error']);
        }
        $history_query->bind_param("i", $parcel_id);
        $history_query->execute();
        $history_result = $history_query->get_result();

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

        while($row = $history_result->fetch_assoc()){
            // Skip entries without valid coordinates
            if ($row['latitude'] === null || $row['longitude'] === null) {
                error_log("Skipping history entry with null coordinates for parcel_id: $parcel_id, status: " . $row['status']);
                continue;
            }
            $status_index = $row['status'];
            $status_text = isset($status_arr[$status_index]) ? $status_arr[$status_index] : "Unknown";
            $data[] = array(
                'status' => $status_text,
                'status_index' => $status_index,
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'date_created' => date("M d, Y h:i A", strtotime($row['date_created']))
            );
        }

        error_log("Returning data: " . json_encode($data));
        return json_encode($data);
    }

    function get_report(){
        extract($_POST);
        $data = array();
        $get = $this->db->query("SELECT * FROM parcels where date(date_created) BETWEEN '$date_from' and '$date_to' ".($status != 'all' ? " and status = $status " : "")." order by unix_timestamp(date_created) asc");
        $status_arr = array("Item Accepted by Courier","Collected","Shipped","In-Transit","Arrived At Destination","Out for Delivery","Ready to Pickup","Delivered","Picked-up","Unsuccessful Delivery Attempt");
        while($row=$get->fetch_assoc()){
            $row['sender_name'] = ucwords($row['sender_name']);
            $row['recipient_name'] = ucwords($row['recipient_name']);
            $row['date_created'] = date("M d, Y",strtotime($row['date_created']));
            $row['status'] = $status_arr[$row['status']];
            $row['price'] = number_format($row['price'],2);
            $data[] = $row;
        }
        return json_encode($data);
    }
}
?>