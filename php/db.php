<?php
$conn = mysqli_connect("localhost", "root", "", "jobportal");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
