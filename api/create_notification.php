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

$audience = $_POST['audience'] ?? 'all';
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');
$type = $_POST['type'] ?? 'info';
$action_url = trim($_POST['action_url'] ?? '');
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($title === '' || $message === '' || !in_array($type, ['info','success','warning','error'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Determine recipients
    $userIds = [];
    if ($audience === 'user') {
        if ($userId <= 0) { throw new Exception('Select a valid user'); }
        $userIds = [$userId];
    } else {
        $where = '';
        if ($audience === 'teachers') { $where = "WHERE role='teacher' AND is_active=1"; }
        elseif ($audience === 'students') { $where = "WHERE role='student' AND is_active=1"; }
        elseif ($audience === 'supervisors') { $where = "WHERE role='supervisor' AND is_active=1"; }
        else { $where = "WHERE is_active=1"; }
        $stmt = $pdo->query("SELECT id FROM users $where");
        $userIds = array_map(fn($r)=> (int)$r['id'], $stmt->fetchAll());
    }

    if (!$userIds) { throw new Exception('No recipients found'); }

    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)");
    foreach ($userIds as $uid) {
        $stmt->execute([$uid, $title, $message, $type, $action_url ?: null]);
    }

    logAction(getCurrentUserId(), 'create_notification', 'Created notification to '.count($userIds).' user(s)');
    $pdo->commit();
    echo json_encode(['success' => true, 'count' => count($userIds)]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create notifications']);
}
