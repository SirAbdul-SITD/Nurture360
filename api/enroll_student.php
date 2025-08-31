<?php
require_once '../config/config.php';
header('Content-Type: application/json');
if (!isLoggedIn() || !isSuperAdmin()) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }

$pdo = getDBConnection();
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$academic_year = trim($_POST['academic_year'] ?? '');

if ($student_id <= 0 || $class_id <= 0 || $academic_year === '') {
  http_response_code(400);
  echo json_encode(['error'=>'Missing required fields']);
  exit;
}

try {
  if (!$pdo->inTransaction()) $pdo->beginTransaction();

  // Validate student exists and is active
  $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND is_active = 1");
  $stmt->execute([$student_id]);
  if (!$stmt->fetch()) { throw new Exception('Invalid student'); }

  // Validate class exists and is active
  $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND is_active = 1");
  $stmt->execute([$class_id]);
  if (!$stmt->fetch()) { throw new Exception('Invalid class'); }

  // Enforce single active class: if student already has any active enrollment (in any class), block
  $stmt = $pdo->prepare("SELECT class_id FROM student_enrollments WHERE student_id = ? AND status = 'active' LIMIT 1");
  $stmt->execute([$student_id]);
  $existingActive = $stmt->fetchColumn();
  if ($existingActive) {
    if ((int)$existingActive === $class_id) {
      throw new Exception('Student already enrolled in this class');
    } else {
      throw new Exception('Student already active in another class. Use Promote to move the student.');
    }
  }

  // Check if already enrolled active in target (redundant but safe)
  $stmt = $pdo->prepare("SELECT id FROM student_enrollments WHERE student_id = ? AND class_id = ? AND status = 'active'");
  $stmt->execute([$student_id, $class_id]);
  if ($stmt->fetch()) { throw new Exception('Student already enrolled in this class'); }

  // Enroll
  $stmt = $pdo->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year, enrollment_date, status) VALUES (?,?,?,?, 'active')");
  $stmt->execute([$student_id, $class_id, $academic_year, date('Y-m-d')]);

  // Log
  logAction(getCurrentUserId(), 'enroll_student', 'Enrolled student '.$student_id.' to class '.$class_id.' AY '.$academic_year);

  // Notifications (best-effort; do not block on failure)
  try {
    // Gather details
    $q = $pdo->prepare("SELECT c.class_name, c.academic_year, u.username, u.first_name, u.last_name
                        FROM classes c
                        CROSS JOIN users u
                        WHERE c.id = ? AND u.id = ?");
    $q->execute([$class_id, $student_id]);
    $row = $q->fetch(PDO::FETCH_ASSOC) ?: [];
    $sName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ('@' . ($row['username'] ?? 'student'));
    $title = 'Student enrolled in class';
    $msg = sprintf('%s has been enrolled in %s · AY %s',
      $sName,
      $row['class_name'] ?? ('Class #' . $class_id),
      $row['academic_year'] ?? $academic_year
    );
    $actionUrl = '../pages/class_details.php?id=' . (int)$class_id;

    // Notify superadmins
    $ins = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)');
    $sa = $pdo->query("SELECT id FROM users WHERE role='superadmin' AND is_active=1");
    foreach ($sa->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $ins->execute([(int)$r['id'], $title, $msg, 'info', $actionUrl]);
    }

    // Notify all active teachers assigned to this class
    $tq = $pdo->prepare("SELECT DISTINCT teacher_id FROM teacher_assignments WHERE class_id = ? AND is_active = 1");
    $tq->execute([$class_id]);
    foreach ($tq->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $ins->execute([(int)$r['teacher_id'], $title, $msg, 'info', $actionUrl]);
    }
  } catch (Throwable $e) { /* swallow */ }

  $pdo->commit();
  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(['error'=>$e->getMessage()]);
}
