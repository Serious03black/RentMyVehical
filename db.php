<?php
$host = "sql307.infinityfree.com";        // Database host
$username = "if0_39470731";         // Database username
$password = "pAS021203";    // Database password
$database = "if0_39470731_rentmyvehicle"; // Your database name

$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
