<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();
    $role = $_GET['role'] ?? '';
    $allowed = ['teacher','supervisor','student','superadmin'];
    if (!in_array($role, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, email FROM users WHERE role = ? AND is_active = 1 ORDER BY first_name, last_name");
    $stmt->execute([$role]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'users' => $users]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
