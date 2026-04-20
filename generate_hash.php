<?php
// Change this to any password you want
$password = "admin123";

// Generate bcrypt hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Display results
echo "<h2>Password Hash Generator</h2>";
echo "Plain Password: " . $password . "<br><br>";
echo "Hashed Password:<br>";
echo "<textarea rows='3' cols='80'>" . $hash . "</textarea>";
?>