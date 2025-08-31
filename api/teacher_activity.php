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
    
    $teacher_id = $_GET['teacher_id'] ?? null;
    
    if (!$teacher_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Teacher ID is required']);
        exit;
    }
    
    // Get recent activity
    $activity_stmt = $pdo->prepare("
        SELECT 
            sl.id,
            sl.action,
            sl.description,
            sl.created_at
        FROM system_logs sl
        WHERE sl.user_id = ?
        ORDER BY sl.created_at DESC
        LIMIT 20
    ");
    $activity_stmt->execute([$teacher_id]);
    $activities = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format activities with better descriptions
    $formatted_activities = [];
    foreach ($activities as $activity) {
        $description = $activity['description'];
        
        // If no description, create one based on action
        if (empty($description)) {
            switch ($activity['action']) {
                case 'login':
                    $description = 'Logged into the system';
                    break;
                case 'logout':
                    $description = 'Logged out of the system';
                    break;
                case 'grade_update':
                    $description = 'Updated student grades';
                    break;
                case 'attendance':
                    $description = 'Updated attendance records';
                    break;
                case 'test_created':
                    $description = 'Created a new test';
                    break;
                case 'announcement':
                    $description = 'Posted an announcement';
                    break;
                default:
                    $description = 'Performed an action';
                    break;
            }
        }
        
        $formatted_activities[] = [
            'id' => $activity['id'],
            'action' => $activity['action'],
            'description' => $description,
            'created_at' => $activity['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $formatted_activities
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 