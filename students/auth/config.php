<?php
// Use global app configuration and shared DB connection
require_once __DIR__ . '/../../config/config.php';

// Optional: local error display for auth pages
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Shared PDO from global config
$pdo = getDBConnection();

?>