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
    $subject_id = $_POST['subject_id'] ?? null;
    
    if (!$teacher_id || !$subject_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Teacher ID and Subject ID are required']);
        exit;
    }
    
    // Check if assignment exists
    $check_stmt = $pdo->prepare("SELECT id FROM teacher_subject_assignments WHERE teacher_id = ? AND subject_id = ?");
    $check_stmt->execute([$teacher_id, $subject_id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Assignment not found']);
        exit;
    }
    
    // Remove assignment
    $stmt = $pdo->prepare("DELETE FROM teacher_subject_assignments WHERE teacher_id = ? AND subject_id = ?");
    $stmt->execute([$teacher_id, $subject_id]);
    
    // Log the action
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, additional_data) VALUES (?, 'subject_unassigned', 'Removed subject assignment from teacher', ?)");
    $log_stmt->execute([$_SESSION['user_id'], json_encode(['teacher_id' => $teacher_id, 'subject_id' => $subject_id])]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Subject assignment removed successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 