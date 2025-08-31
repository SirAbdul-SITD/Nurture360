<?php
require_once '../config/config.php';
header('Content-Type: application/json');

try {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $pdo = getDBConnection();
    $userId = getCurrentUserId();

    $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;

    if ($sinceId > 0) {
        $stmt = $pdo->prepare("SELECT id, title, message, type, is_read, action_url, created_at FROM notifications WHERE user_id = ? AND id > ? ORDER BY id DESC LIMIT $limit");
        $stmt->execute([$userId, $sinceId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, title, message, type, is_read, action_url, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT $limit");
        $stmt->execute([$userId]);
    }
    $rows = $stmt->fetchAll();

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $countStmt->execute([$userId]);
    $unreadCount = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => $rows,
        'unread_count' => $unreadCount,
        'latest_id' => count($rows) ? (int)$rows[0]['id'] : $sinceId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch notifications']);
}
