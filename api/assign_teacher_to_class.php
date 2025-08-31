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

    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $academic_year = trim($_POST['academic_year'] ?? '');

    if ($teacher_id <= 0 || $class_id <= 0 || $subject_id <= 0 || $academic_year === '') {
        http_response_code(400);
        echo json_encode(['error' => 'teacher_id, class_id, subject_id and academic_year are required']);
        exit;
    }

    // Validate entities
    $chk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'teacher' AND is_active = 1");
    $chk->execute([$teacher_id]);
    if (!$chk->fetchColumn()) { http_response_code(404); echo json_encode(['error' => 'Teacher not found']); exit; }

    $chk = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND is_active = 1");
    $chk->execute([$class_id]);
    if (!$chk->fetchColumn()) { http_response_code(404); echo json_encode(['error' => 'Class not found']); exit; }

    $chk = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND is_active = 1");
    $chk->execute([$subject_id]);
    if (!$chk->fetchColumn()) { http_response_code(404); echo json_encode(['error' => 'Subject not found']); exit; }

    // Prevent duplicate assignment for same teacher/class/subject/year
    $dup = $pdo->prepare("SELECT id FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND academic_year=?");
    $dup->execute([$teacher_id, $class_id, $subject_id, $academic_year]);
    if ($dup->fetchColumn()) { http_response_code(409); echo json_encode(['error' => 'Assignment already exists']); exit; }

    $ins = $pdo->prepare("INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, academic_year, is_active) VALUES (?, ?, ?, ?, 1)");
    $ins->execute([$teacher_id, $class_id, $subject_id, $academic_year]);

    // Log
    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'assign_teacher', 'Assigned teacher to class', ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null; $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $log->execute([$_SESSION['user_id'] ?? null, $ip, $ua]);

    // Notifications: inform assigned teacher and superadmins
    try {
        // Fetch labels
        $tInfo = $pdo->prepare("SELECT first_name,last_name,username FROM users WHERE id=?");
        $tInfo->execute([$teacher_id]);
        $tRow = $tInfo->fetch();
        $tName = trim(($tRow['first_name']??'').' '.($tRow['last_name']??''));
        if ($tName==='') { $tName = (string)($tRow['username']??'Teacher'); }

        $cInfo = $pdo->prepare("SELECT class_name,class_code FROM classes WHERE id=?");
        $cInfo->execute([$class_id]);
        $cRow = $cInfo->fetch();
        $classLabel = (($cRow['class_name']??'-').' (#'.($cRow['class_code']??'-').')');

        $sInfo = $pdo->prepare("SELECT subject_name,subject_code FROM subjects WHERE id=?");
        $sInfo->execute([$subject_id]);
        $sRow = $sInfo->fetch();
        $subjectLabel = (($sRow['subject_name']??'-').' ('.($sRow['subject_code']??'-').')');

        $titleN = 'Class Assignment';
        $msgForTeacher = 'You were assigned to '.$classLabel.' — '.$subjectLabel.' (AY '.$academic_year.')';
        $msgForAdmin = $tName.' assigned to '.$classLabel.' — '.$subjectLabel.' (AY '.$academic_year.')';
        $actionUrl = '../pages/class_detail.php?id='.$class_id;

        // Assigned teacher
        $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)");
        $ins->execute([$teacher_id, $titleN, $msgForTeacher, 'success', $actionUrl]);

        // Superadmins
        $sa = $pdo->query("SELECT id FROM users WHERE role='superadmin' AND COALESCE(is_active,1)=1")->fetchAll();
        foreach ($sa as $row) {
            $ins->execute([(int)$row['id'], $titleN, $msgForAdmin, 'info', $actionUrl]);
        }
    } catch (Throwable $e) { /* swallow */ }

    echo json_encode(['success' => true, 'assignment_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
