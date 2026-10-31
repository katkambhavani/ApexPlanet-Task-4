
<?php
// BookNest — MySQL Database Connection

$host = "localhost";
$username = "root";
$password = "";
$database = "booknest_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log("BookNest database connection failed: " . $conn->connect_error);
    exit("Unable to connect to the database. Please check the database configuration.");
}

$conn->set_charset("utf8mb4");
?>
