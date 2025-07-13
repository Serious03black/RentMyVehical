<?php
$host = "localhost";        // Database host
$username = "root";         // Database username
$password = "";             // Database password
$database = "vehicalmanagment"; // Your database name

$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
