<?php
require_once '../config/config.php';

// Check if user is logged in and is SuperAdmin
if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Get available subjects (not assigned to any teacher)
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.subject_name,
            s.subject_code,
            s.grade_level,
            s.description,
            s.credits
        FROM subjects s
        WHERE s.is_active = 1 
        AND s.id NOT IN (
            SELECT DISTINCT subject_id 
            FROM teacher_subject_assignments
        )
        ORDER BY s.grade_level, s.subject_name
    ");
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 