<?php
// Include the database settings
require 'settings.php';

try {

    // Query to select unique titles from the subject table
    $query = "SELECT DISTINCT title FROM subjects";
    $stmt = $pdo->query($query);

    // Fetch and display the results
    $titles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($titles as $title) {
        echo htmlspecialchars($title) . "<br>";
    }
} catch (PDOException $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage());
}
