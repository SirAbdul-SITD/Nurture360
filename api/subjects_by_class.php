<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();
    $class_id = (int)($_GET['class_id'] ?? 0);
    if ($class_id <= 0) {
        echo json_encode([]);
        exit;
    }

    // Validate class exists and is active
    $chk = $pdo->prepare('SELECT id FROM classes WHERE id = ? AND COALESCE(is_active,1)=1');
    $chk->execute([$class_id]);
    if (!$chk->fetchColumn()) { echo json_encode([]); exit; }

    // Return subjects assigned to this class (new schema: subjects.subject_id, subjects.title)
    $stmt = $pdo->prepare(
        'SELECT s.subject_id AS id, s.title AS subject_name, s.subject_code
         FROM class_subjects cs
         JOIN subjects s ON s.subject_id = cs.subject_id
         WHERE cs.class_id = ? AND COALESCE(s.is_active,1)=1
         ORDER BY s.title'
    );
    $stmt->execute([$class_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
