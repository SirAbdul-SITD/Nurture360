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

    // Get assignment details before deletion for notifications
    $chk = $pdo->prepare('SELECT ta.id, ta.class_id, ta.teacher_id, ta.subject_id, c.class_name, c.academic_year,
                                 u.username AS teacher_username, u.first_name AS tfn, u.last_name AS tln,
                                 s.subject_name, s.subject_code
                          FROM teacher_assignments ta
                          JOIN classes c ON c.id = ta.class_id
                          JOIN users u ON u.id = ta.teacher_id
                          JOIN subjects s ON s.id = ta.subject_id
                          WHERE ta.id = ?');
    $chk->execute([$assignment_id]);
    $assignment = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$assignment) {
        http_response_code(404);
        echo json_encode(['error' => 'Assignment not found']);
        exit;
    }

    $del = $pdo->prepare('DELETE FROM teacher_assignments WHERE id = ?');
    $del->execute([$assignment_id]);

    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'remove_teacher_assignment', 'Removed teacher assignment from class', ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null; $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $log->execute([$_SESSION['user_id'] ?? null, $ip, $ua]);

    // Create notifications (best-effort; errors won't block response)
    try {
        $title = 'Teacher unassigned from class';
        $tName = trim(($assignment['tfn'] ?? '') . ' ' . ($assignment['tln'] ?? ''));
        $sub = $assignment['subject_name'] . (!empty($assignment['subject_code']) ? ' (' . $assignment['subject_code'] . ')' : '');
        $msg = sprintf('%s has been removed from %s for %s · AY %s',
            $tName ?: ('@' . ($assignment['teacher_username'] ?? 'teacher')),
            $sub,
            $assignment['class_name'] ?? 'class',
            $assignment['academic_year'] ?? ''
        );
        $actionUrl = '../pages/class_details.php?id=' . (int)$assignment['class_id'];

        // Notify the teacher
        $ins = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, ?, ?)');
        $ins->execute([(int)$assignment['teacher_id'], $title, $msg, 'warning', $actionUrl]);

        // Notify superadmins
        $sa = $pdo->query("SELECT id FROM users WHERE role='superadmin' AND is_active=1");
        foreach ($sa->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ins->execute([(int)$row['id'], $title, $msg, 'warning', $actionUrl]);
        }
    } catch (Throwable $e) {
        // swallow notification errors
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
