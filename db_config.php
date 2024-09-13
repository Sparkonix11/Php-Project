<?php
// Database configuration
$servername = "feenix-mariadb.swin.edu.au"; 
$username = "s105101199"; 
$password = "190901"; 
$dbname = "s105101199_db"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, 3306); // 3306 is the default MySQL port

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
