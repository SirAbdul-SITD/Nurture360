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

    $supervisor_id = (int)($_POST['supervisor_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $academic_year = trim($_POST['academic_year'] ?? '');

    if ($supervisor_id <= 0 || $class_id <= 0 || $academic_year === '') {
        http_response_code(400);
        echo json_encode(['error' => 'supervisor_id, class_id and academic_year are required']);
        exit;
    }

    // Validate entities
    $chk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'supervisor' AND is_active = 1");
    $chk->execute([$supervisor_id]);
    if (!$chk->fetchColumn()) { http_response_code(404); echo json_encode(['error' => 'Supervisor not found']); exit; }

    $chk = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND is_active = 1");
    $chk->execute([$class_id]);
    if (!$chk->fetchColumn()) { http_response_code(404); echo json_encode(['error' => 'Class not found']); exit; }

    // Prevent duplicate assignment for same supervisor/class/year
    $dup = $pdo->prepare("SELECT id FROM supervisor_assignments WHERE supervisor_id=? AND class_id=? AND academic_year=?");
    $dup->execute([$supervisor_id, $class_id, $academic_year]);
    if ($dup->fetchColumn()) { http_response_code(409); echo json_encode(['error' => 'Assignment already exists']); exit; }

    $ins = $pdo->prepare("INSERT INTO supervisor_assignments (supervisor_id, class_id, academic_year, is_active) VALUES (?, ?, ?, 1)");
    $ins->execute([$supervisor_id, $class_id, $academic_year]);

    // Log
    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'assign_supervisor', 'Assigned supervisor to class', ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null; $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $log->execute([$_SESSION['user_id'] ?? null, $ip, $ua]);

    echo json_encode(['success' => true, 'assignment_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
