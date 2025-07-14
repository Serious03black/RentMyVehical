<?php
require 'db.php';
if (isset($_GET['vehicle_number'])) {
    $vehicle_number = mysqli_real_escape_string($conn, $_GET['vehicle_number']);
    $result = mysqli_query($conn, "SELECT image_blob FROM vehicles WHERE vehicle_number = '$vehicle_number'");
    if ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['image_blob'])) {
            // Content-Type ऑटो डिटेक्ट करें
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($row['image_blob']);
            header('Content-Type: ' . $mimeType);
            echo $row['image_blob'];
            exit;
        }
    }
}
// अगर image_blob नहीं है तो placeholder दिखाएं
readfile('Uploads/placeholder.jpg'); 