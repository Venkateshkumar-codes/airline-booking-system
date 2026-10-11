<?php
$host = "localhost";
$user = "root";
$pass = ""; // Change this if your new laptop has a DB password
$db   = "airline_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>