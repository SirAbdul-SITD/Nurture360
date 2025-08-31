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
    $class_subject_id = (int)($_POST['class_subject_id'] ?? 0);
    if ($class_subject_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'class_subject_id is required']);
        exit;
    }

    $chk = $pdo->prepare('SELECT id FROM class_subjects WHERE id = ?');
    $chk->execute([$class_subject_id]);
    if (!$chk->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['error' => 'Class subject link not found']);
        exit;
    }

    $del = $pdo->prepare('DELETE FROM class_subjects WHERE id = ?');
    $del->execute([$class_subject_id]);

    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'remove_class_subject', 'Removed subject from class', ?, ?)");
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
