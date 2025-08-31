<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();
$csrf = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid notification id']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$id]);
    logAction(getCurrentUserId(), 'delete_notification', 'id=' . $id);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete notification']);
}
