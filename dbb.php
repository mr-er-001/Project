<?php
$host = "localhost";
$user = "root";   // default XAMPP MySQL user
$pass = "";       // default is empty in XAMPP
$db   = "db_books"; // <-- your actual database name

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
?>
  