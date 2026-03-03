<?php
/* ================================================================
   logout.php
   Place this file in: C:\xampp\htdocs\soe\main\logout.php
   ================================================================ */

session_start();
session_unset();
session_destroy();

header('Location: ../main/login.php');
exit;