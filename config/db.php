<?php
/* ================================================================
   db.php — Database Connection
   Place this file in: C:\xampp\htdocs\soe\config\db.php
   ================================================================ */

$host   = 'localhost';
$db     = 'flood_information';          // your database name
$user   = 'root';         // default XAMPP username
$pass   = 'password';             // default XAMPP password (empty)

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');