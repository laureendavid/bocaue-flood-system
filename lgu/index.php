<?php
session_start();

// Block access if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../main/login.php');
    exit;
}

// Block wrong role
if ($_SESSION['role'] !== 'LGU') {
    header('Location: ../main/login.php');
    exit;
}

// Redirect to actual dashboard
header('Location: ./main.php');
exit;