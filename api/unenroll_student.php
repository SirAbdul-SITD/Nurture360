<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isSuperAdmin()) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }

$pdo = getDBConnection();
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$reason = trim($_POST['reason'] ?? '');

if ($student_id <= 0 || $class_id <= 0) {
  http_response_code(400);
  echo json_encode(['error'=>'Missing required fields']);
  exit;
}

try {
  if (!$pdo->inTransaction()) $pdo->beginTransaction();

  // Validate active enrollment
  $st = $pdo->prepare("SELECT id FROM student_enrollments WHERE student_id=? AND class_id=? AND status='active' LIMIT 1");
  $st->execute([$student_id, $class_id]);
  $enrollId = (int)($st->fetchColumn() ?: 0);
  if ($enrollId <= 0) { throw new Exception('Active enrollment not found'); }

  // Set inactive
  $upd = $pdo->prepare("UPDATE student_enrollments SET status='inactive' WHERE id=?");
  $upd->execute([$enrollId]);

  // Log
  $desc = 'Unenrolled student ' . $student_id . ' from class ' . $class_id . ($reason !== '' ? (' Reason: ' . $reason) : '');
  logAction(getCurrentUserId(), 'unenroll_student', $desc);

  // Notifications (best-effort)
  try {
    // Fetch details
    $q = $pdo->prepare("SELECT c.class_name, c.academic_year, u.username, u.first_name, u.last_name
                        FROM classes c CROSS JOIN users u
                        WHERE c.id=? AND u.id=?");
    $q->execute([$class_id, $student_id]);
    $row = $q->fetch(PDO::FETCH_ASSOC) ?: [];
    $sName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ('@' . ($row['username'] ?? 'student'));
    $title = 'Student unenrolled from class';
    $baseMsg = sprintf('%s has been unenrolled from %s · AY %s',
      $sName,
      $row['class_name'] ?? ('Class #' . $class_id),
      $row['academic_year'] ?? ''
    );
    $msg = $baseMsg . ($reason !== '' ? (' · Reason: ' . $reason) : '');
    $actionUrl = '../pages/class_details.php?id=' . (int)$class_id;

    $ins = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)');

    // Notify student
    $ins->execute([$student_id, $title, $msg, 'warning', $actionUrl]);

    // Notify superadmins
    $sa = $pdo->query("SELECT id FROM users WHERE role='superadmin' AND is_active=1");
    foreach ($sa->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $ins->execute([(int)$r['id'], $title, $msg, 'warning', $actionUrl]);
    }

    // Notify teachers assigned to this class
    $tq = $pdo->prepare("SELECT DISTINCT teacher_id FROM teacher_assignments WHERE class_id = ? AND is_active = 1");
    $tq->execute([$class_id]);
    foreach ($tq->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $ins->execute([(int)$r['teacher_id'], $title, $msg, 'warning', $actionUrl]);
    }
  } catch (Throwable $e) { /* ignore */ }

  $pdo->commit();
  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(['error'=>$e->getMessage()]);
}
