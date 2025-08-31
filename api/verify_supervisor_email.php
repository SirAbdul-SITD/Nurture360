<?php
require_once '../config/config.php';

// Ensure JSON response
header('Content-Type: application/json');

// Authz: SuperAdmin only
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

    // Support JSON body
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $supervisor_id = 0;
    if (is_array($data) && isset($data['supervisor_id'])) {
        $supervisor_id = (int)$data['supervisor_id'];
    } elseif (isset($_POST['supervisor_id'])) {
        $supervisor_id = (int)$_POST['supervisor_id'];
    }

    if ($supervisor_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Supervisor ID is required']);
        exit;
    }

    // Ensure supervisor exists
    $check = $pdo->prepare("SELECT id, email_verified FROM users WHERE id = ? AND role = 'supervisor'");
    $check->execute([$supervisor_id]);
    $supervisor = $check->fetch(PDO::FETCH_ASSOC);
    if (!$supervisor) {
        http_response_code(404);
        echo json_encode(['error' => 'Supervisor not found']);
        exit;
    }

    if (!empty($supervisor['email_verified'])) {
        echo json_encode(['success' => true, 'message' => 'Email already verified']);
        exit;
    }

    // Update flag
    $upd = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ? AND role = 'supervisor'");
    $upd->execute([$supervisor_id]);

    // Optional: log action
    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'verify_email', 'Marked supervisor email verified', ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $log->execute([$_SESSION['user_id'] ?? null, $ip, $ua]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
