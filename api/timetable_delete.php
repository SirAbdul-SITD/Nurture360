<?php
// api/timetable_delete.php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Invalid method'); }
    $pdo = getDBConnection();

    $type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : 'class'; // class | exam
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { throw new Exception('id is required'); }

    if ($type === 'class') {
        $stmt = $pdo->prepare("DELETE FROM timetable WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
        $stmt->execute([$id]);
    }
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
