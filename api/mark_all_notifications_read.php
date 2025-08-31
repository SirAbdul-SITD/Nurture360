<?php
require_once '../config/config.php';
header('Content-Type: application/json');

try {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $pdo = getDBConnection();
    $userId = getCurrentUserId();

    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $affected = $stmt->rowCount();

    echo json_encode(['success' => true, 'message' => 'Marked as read', 'updated' => $affected]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
}
