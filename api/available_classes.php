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
    
    // Get available classes (not assigned to any teacher)
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.class_name,
            c.grade_level,
            c.section,
            c.capacity,
            COUNT(DISTINCT cs.student_id) as current_students
        FROM classes c
        LEFT JOIN class_students cs ON c.id = cs.class_id
        WHERE c.is_active = 1 
        AND c.id NOT IN (
            SELECT DISTINCT class_id 
            FROM teacher_class_assignments
        )
        GROUP BY c.id, c.class_name, c.grade_level, c.section, c.capacity
        ORDER BY c.grade_level, c.class_name
    ");
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 