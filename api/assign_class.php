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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $teacher_id = $_POST['teacher_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    
    if (!$teacher_id || !$class_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Teacher ID and Class ID are required']);
        exit;
    }
    
    // Check if assignment already exists
    $check_stmt = $pdo->prepare("SELECT id FROM teacher_class_assignments WHERE teacher_id = ? AND class_id = ?");
    $check_stmt->execute([$teacher_id, $class_id]);
    
    if ($check_stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Teacher is already assigned to this class']);
        exit;
    }
    
    // Check if class is already assigned to another teacher
    $check_class_stmt = $pdo->prepare("SELECT teacher_id FROM teacher_class_assignments WHERE class_id = ?");
    $check_class_stmt->execute([$class_id]);
    $existing_teacher = $check_class_stmt->fetch();
    
    if ($existing_teacher) {
        http_response_code(409);
        echo json_encode(['error' => 'This class is already assigned to another teacher']);
        exit;
    }
    
    // Create assignment
    $stmt = $pdo->prepare("INSERT INTO teacher_class_assignments (teacher_id, class_id, assigned_at) VALUES (?, ?, NOW())");
    $stmt->execute([$teacher_id, $class_id]);
    
    // Log the action
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, additional_data) VALUES (?, 'class_assigned', 'Assigned class to teacher', ?)");
    $log_stmt->execute([$_SESSION['user_id'], json_encode(['teacher_id' => $teacher_id, 'class_id' => $class_id])]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Class assigned successfully',
        'assignment_id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 