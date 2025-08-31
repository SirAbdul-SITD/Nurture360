<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDBConnection();
    $assignment_id = (int)($_POST['assignment_id'] ?? 0);
    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'assignment_id is required']);
        exit;
    }

    $chk = $pdo->prepare('SELECT id FROM supervisor_assignments WHERE id = ?');
    $chk->execute([$assignment_id]);
    if (!$chk->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['error' => 'Assignment not found']);
        exit;
    }

    $del = $pdo->prepare('DELETE FROM supervisor_assignments WHERE id = ?');
    $del->execute([$assignment_id]);

    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'remove_supervisor_assignment', 'Removed supervisor assignment from class', ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null; $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $log->execute([$_SESSION['user_id'] ?? null, $ip, $ua]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
