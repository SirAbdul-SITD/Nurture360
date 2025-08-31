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
$isRead = isset($_POST['is_read']) ? (int)$_POST['is_read'] : 1;
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid notification id']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = ?, read_at = CASE WHEN ?=1 THEN NOW() ELSE NULL END WHERE id = ?");
    $stmt->execute([$isRead ? 1 : 0, $isRead ? 1 : 0, $id]);
    logAction(getCurrentUserId(), 'toggle_notification', 'id=' . $id . ', is_read=' . $isRead);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update notification']);
}
