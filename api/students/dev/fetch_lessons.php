<?php
// Include the database connection
require_once '../settings.php'; // Ensure this file sets up a $pdo variable for the PDO connection

try {
    // Get class_id from the request
    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

    if ($class_id <= 0) {
        throw new Exception('Invalid class_id provided.');
    }

    // Fetch lessons for the given class_id and join the subjects table to get the title
    $sql = "SELECT l.lesson_id, l.lesson_number, s.title 
            FROM lessons l
            INNER JOIN subjects s ON l.subject_id = s.subject_id
            WHERE l.class_id = :class_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();

    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($lessons);
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch lessons: ' . $e->getMessage()]);
}
?>