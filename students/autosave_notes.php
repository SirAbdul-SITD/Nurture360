<?php
require_once 'settings.php'; // adjust as needed

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$lesson_id = (int) ($data['lesson_id'] ?? 0);
$content = trim($data['content'] ?? '');

// Make sure we have the student_id from settings.php
if (!isset($student_id) || !$student_id) {
    echo json_encode(['status' => 'error', 'message' => 'Student not authenticated']);
    exit;
}

if (!$lesson_id) {
    echo json_encode(['status' => 'error', 'message' => 'Lesson ID is required']);
    exit;
}

try {
    // First check if a note already exists for this student and lesson
    $checkStmt = $pdo->prepare("SELECT id FROM notes WHERE user_id = :user_id AND lesson_id = :lesson_id LIMIT 1");
    $checkStmt->execute([
        ':user_id' => $student_id,
        ':lesson_id' => $lesson_id
    ]);
    
    $existingNote = $checkStmt->fetch();
    
    if ($existingNote) {
        // Update existing note
        $stmt = $pdo->prepare("
            UPDATE notes 
            SET content = :content, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $existingNote['id'],
            ':content' => $content
        ]);
    } else {
        // Insert new note - generate ID manually
        $idStmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM notes");
        $nextId = $idStmt->fetch(PDO::FETCH_ASSOC)['next_id'];
        
        $stmt = $pdo->prepare("
            INSERT INTO notes (id, user_id, lesson_id, content, updated_at)
            VALUES (:id, :user_id, :lesson_id, :content, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':id' => $nextId,
            ':user_id' => $student_id,
            ':lesson_id' => $lesson_id,
            ':content' => $content
        ]);
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Note saved successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'General error: ' . $e->getMessage()]);
}
