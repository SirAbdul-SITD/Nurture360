<?php
session_start(); // Start the session

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();



// Redirect the user to a login page or any other desired page
header("Location: ../auth/index.php");
exit();
?>
