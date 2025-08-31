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

    $class_id = (int)($_POST['class_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);

    if ($class_id <= 0 || $subject_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'class_id and subject_id are required']);
        exit;
    }

    // Validate class
    $chk = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND is_active = 1");
    $chk->execute([$class_id]);
    if (!$chk->fetchColumn()) { http_response_code(404); echo json_encode(['error' => 'Class not found']); exit; }

    // Validate subject
    $chk = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND is_active = 1");
    $chk->execute([$subject_id]);
    if (!$chk->fetchColumn()) { http_response_code(404); echo json_encode(['error' => 'Subject not found']); exit; }

    // Prevent duplicate assignment
    $dup = $pdo->prepare('SELECT id FROM class_subjects WHERE class_id = ? AND subject_id = ?');
    $dup->execute([$class_id, $subject_id]);
    if ($dup->fetchColumn()) { http_response_code(409); echo json_encode(['error' => 'Subject already assigned to this class']); exit; }

    // Insert
    $ins = $pdo->prepare('INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)');
    $ins->execute([$class_id, $subject_id]);

    // Log
    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'assign_class_subject', 'Assigned subject to class', ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null; $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $log->execute([$_SESSION['user_id'] ?? null, $ip, $ua]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
