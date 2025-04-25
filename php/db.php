<?php
$servername = "localhost";
$username = "root"; // Your database username
$password = ""; // Your database password (replace with actual if different)
$dbname = "jobportal"; // Make sure this matches your actual DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("[DB DEBUG] Connection failed: " . $conn->connect_error);
} else {
    echo "[DB DEBUG] Connected successfully\n";
}
?>
