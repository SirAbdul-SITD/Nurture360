<?php
require_once '../config/config.php';
header('Content-Type: application/json');
if (!isLoggedIn() || !isSuperAdmin()) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }

$pdo = getDBConnection();
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$from_class_id = isset($_POST['from_class_id']) ? (int)$_POST['from_class_id'] : 0;
$to_class_id = isset($_POST['to_class_id']) ? (int)$_POST['to_class_id'] : 0;
$academic_year = trim($_POST['academic_year'] ?? '');

if ($student_id <= 0 || $from_class_id <= 0 || $to_class_id <= 0 || $from_class_id === $to_class_id || $academic_year === '') {
  http_response_code(400);
  echo json_encode(['error'=>'Missing or invalid fields']);
  exit;
}

try {
  $pdo->beginTransaction();

  // Validate student
  $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND is_active = 1");
  $stmt->execute([$student_id]);
  if (!$stmt->fetch()) { throw new Exception('Invalid student'); }

  // Validate classes
  $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND is_active = 1");
  $stmt->execute([$from_class_id]);
  if (!$stmt->fetch()) { throw new Exception('Invalid source class'); }
  $stmt->execute([$to_class_id]);
  if (!$stmt->fetch()) { throw new Exception('Invalid target class'); }

  // Ensure there is an active enrollment in from_class
  $stmt = $pdo->prepare("SELECT id FROM student_enrollments WHERE student_id = ? AND class_id = ? AND status = 'active'");
  $stmt->execute([$student_id, $from_class_id]);
  $enrollment = $stmt->fetchColumn();
  if (!$enrollment) { throw new Exception('Student not actively enrolled in source class'); }

  // Deactivate current enrollment
  $stmt = $pdo->prepare("UPDATE student_enrollments SET status = 'inactive' WHERE id = ?");
  $stmt->execute([$enrollment]);

  // Prevent duplicate active enrollment in target
  $stmt = $pdo->prepare("SELECT id FROM student_enrollments WHERE student_id = ? AND class_id = ? AND status = 'active'");
  $stmt->execute([$student_id, $to_class_id]);
  if ($stmt->fetch()) { throw new Exception('Student already active in target class'); }

  // Enroll into target class
  $stmt = $pdo->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year, enrollment_date, status) VALUES (?,?,?,?, 'active')");
  $stmt->execute([$student_id, $to_class_id, $academic_year, date('Y-m-d')]);

  $pdo->commit();

  logAction(getCurrentUserId(), 'promote_student', 'Promoted student '.$student_id.' from class '.$from_class_id.' to '.$to_class_id.' AY '.$academic_year);

  // Notifications: inform superadmins and teachers of involved classes
  try {
    // Fetch student and classes labels
    $sInfo = $pdo->prepare("SELECT first_name,last_name,username FROM users WHERE id=?");
    $sInfo->execute([$student_id]);
    $sRow = $sInfo->fetch();
    $studentName = trim(($sRow['first_name']??'').' '.($sRow['last_name']??''));
    if ($studentName==='') { $studentName = (string)($sRow['username']??'Student'); }

    $cInfo = $pdo->prepare("SELECT id,class_name,class_code FROM classes WHERE id IN (?,?)");
    $cInfo->execute([$from_class_id,$to_class_id]);
    $map = [];
    foreach ($cInfo->fetchAll() as $r) { $map[(int)$r['id']] = ($r['class_name']??'-').' (#'.($r['class_code']??'-').')'; }
    $fromLabel = $map[$from_class_id] ?? ('Class #'.$from_class_id);
    $toLabel   = $map[$to_class_id]   ?? ('Class #'.$to_class_id);

    $titleN = 'Student Promoted';
    $msgN = $studentName.' promoted from '.$fromLabel.' to '.$toLabel.' (AY '.$academic_year.')';
    $actionUrl = './class_detail.php?id='.$to_class_id;

    // Superadmins
    $sa = $pdo->query("SELECT id FROM users WHERE role='superadmin' AND COALESCE(is_active,1)=1")->fetchAll();
    if ($sa) {
      $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)");
      foreach ($sa as $row) { $ins->execute([(int)$row['id'], $titleN, $msgN, 'info', $actionUrl]); }
    }

    // Teachers of source and target classes
    $tStmt = $pdo->prepare("SELECT DISTINCT teacher_id FROM teacher_assignments WHERE class_id IN (?,?) AND COALESCE(is_active,1)=1");
    $tStmt->execute([$from_class_id,$to_class_id]);
    $teachers = $tStmt->fetchAll();
    if ($teachers) {
      $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)");
      foreach ($teachers as $row) { $ins->execute([(int)$row['teacher_id'], $titleN, $msgN, 'info', $actionUrl]); }
    }
  } catch (Throwable $e) { /* swallow notification errors */ }

  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(['error'=>$e->getMessage()]);
}
