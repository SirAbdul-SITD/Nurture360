<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isTeacher()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$teacherId = getCurrentUserId();

// Fetch classes and subjects assigned to this teacher
$classesStmt = $pdo->prepare("SELECT DISTINCT c.id, c.class_name, c.class_code, c.grade_level
                              FROM teacher_assignments ta
                              JOIN classes c ON c.id = ta.class_id
                              WHERE ta.teacher_id = ? AND COALESCE(ta.is_active,1)=1 AND COALESCE(c.is_active,1)=1
                              ORDER BY c.grade_level, c.class_name");
$classesStmt->execute([$teacherId]);
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

$subjectsByClass = [];
if ($classes) {
    $subStmt = $pdo->prepare("SELECT DISTINCT ta.class_id, s.id AS subject_id, s.subject_name, s.subject_code
                               FROM teacher_assignments ta
                               JOIN subjects s ON s.id = ta.subject_id
                               WHERE ta.teacher_id = ? AND ta.class_id = ? AND COALESCE(ta.is_active,1)=1 AND COALESCE(s.is_active,1)=1
                               ORDER BY s.subject_name");
    foreach ($classes as $c) {
        $subStmt->execute([$teacherId, (int)$c['id']]);
        $subjectsByClass[$c['id']] = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Filters
$classId = (int)($_GET['class_id'] ?? ($_POST['class_id'] ?? 0));
$subjectId = (int)($_GET['subject_id'] ?? ($_POST['subject_id'] ?? 0));
$date = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));

// Validate selected class/subject are assigned to this teacher
function teacherHasAssignment(PDO $pdo, int $teacherId, int $classId, int $subjectId): bool {
    if ($classId <= 0 || $subjectId <= 0) return false;
    $s = $pdo->prepare("SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1 LIMIT 1");
    $s->execute([$teacherId, $classId, $subjectId]);
    return (bool)$s->fetchColumn();
}

$errors = [];
$success = null;
$csrf = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    if ($classId <= 0) { $errors[] = 'Please select a class.'; }
    if ($subjectId <= 0) { $errors[] = 'Please select a subject.'; }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $errors[] = 'Invalid date.'; }

    if (!$errors && !teacherHasAssignment($pdo, $teacherId, $classId, $subjectId)) {
        $errors[] = 'You are not assigned to this class/subject.';
    }

    $statuses = $_POST['status'] ?? [];
    $remarksMap = $_POST['remarks'] ?? [];

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Get enrolled students for safety and to map valid IDs
            $stuStmt = $pdo->prepare("SELECT u.id FROM student_enrollments se JOIN users u ON u.id=se.student_id WHERE se.class_id=? AND u.role='student' AND COALESCE(u.is_active,1)=1");
            $stuStmt->execute([$classId]);
            $validStudents = array_map(fn($r) => (int)$r['id'], $stuStmt->fetchAll(PDO::FETCH_ASSOC));
            $validSet = array_fill_keys($validStudents, true);

            // Prepare queries
            $findStmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id=? AND class_id=? AND subject_id=? AND date=? LIMIT 1");
            $insStmt = $pdo->prepare("INSERT INTO attendance (student_id, class_id, subject_id, date, status, remarks, recorded_by) VALUES (?,?,?,?,?,?,?)");
            $updStmt = $pdo->prepare("UPDATE attendance SET status=?, remarks=?, recorded_by=?, created_at=created_at WHERE id=?");

            $countSaved = 0;
            foreach ($statuses as $studentIdStr => $status) {
                $studentId = (int)$studentIdStr;
                if (!isset($validSet[$studentId])) continue; // skip invalid
                if (!in_array($status, ['present','absent','late','excused'], true)) continue;
                $rem = trim($remarksMap[$studentIdStr] ?? '');

                $findStmt->execute([$studentId, $classId, $subjectId, $date]);
                $existingId = (int)($findStmt->fetchColumn() ?: 0);
                if ($existingId > 0) {
                    $updStmt->execute([$status, $rem, $teacherId, $existingId]);
                } else {
                    $insStmt->execute([$studentId, $classId, $subjectId, $date, $status, $rem, $teacherId]);
                }
                $countSaved++;
            }

            $pdo->commit();
            $success = $countSaved . ' attendance record(s) saved.';
            logAction($teacherId, 'attendance_save', 'Saved teacher attendance for class '.$classId.' subject '.$subjectId.' date '.$date);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to save attendance.';
        }
    }
}

// Fetch students for chosen class
$students = [];
if ($classId > 0) {
    $stuStmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.username
                              FROM student_enrollments se
                              JOIN users u ON u.id = se.student_id
                              WHERE se.class_id = ? AND u.role='student' AND COALESCE(u.is_active,1)=1
                              ORDER BY u.first_name, u.last_name");
    $stuStmt->execute([$classId]);
    $students = $stuStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch existing attendance for date/class/subject
$existing = [];
if ($classId > 0 && $subjectId > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $attStmt = $pdo->prepare("SELECT student_id, status, remarks FROM attendance WHERE class_id=? AND subject_id=? AND date=?");
    $attStmt->execute([$classId, $subjectId, $date]);
    while ($r = $attStmt->fetch(PDO::FETCH_ASSOC)) {
        $existing[(int)$r['student_id']] = $r;
    }
}

$page_title = 'Attendance';
$current_page = 'teacher_attendance';

// Print mode: render minimal printable table and exit
$printMode = isset($_GET['print']) && $_GET['print'] === '1' && $classId>0 && $subjectId>0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
if ($printMode) {
    // Ensure teacher has rights
    if (!teacherHasAssignment($pdo, $teacherId, $classId, $subjectId)) { redirect('./teacher_attendance.php'); }

    // Fetch names for headings
    $cn = $pdo->prepare("SELECT class_name, class_code, grade_level FROM classes WHERE id=?");
    $cn->execute([$classId]);
    $cinfo = $cn->fetch();
    $sn = $pdo->prepare("SELECT subject_name, subject_code FROM subjects WHERE id=?");
    $sn->execute([$subjectId]);
    $sinfo = $sn->fetch();

    // Build printable HTML
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Attendance Print - <?php echo htmlspecialchars($date); ?></title>
      <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;color:#111827;margin:24px}
        h1{font-size:20px;margin:0 0 8px}
        .meta{margin-bottom:16px;color:#374151}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #e5e7eb;padding:8px;text-align:left;font-size:14px}
        th{background:#f9fafb}
        .badge{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #e5e7eb}
      </style>
    </head>
    <body>
      <h1>Attendance</h1>
      <div class="meta">
        <div><strong>Date:</strong> <?php echo htmlspecialchars($date); ?></div>
        <div><strong>Class:</strong> <?php echo htmlspecialchars(($cinfo['class_name']??'-')." (#".($cinfo['class_code']??'').") Grade ".(int)($cinfo['grade_level']??0)); ?></div>
        <div><strong>Subject:</strong> <?php echo htmlspecialchars(($sinfo['subject_name']??'-')." (#".($sinfo['subject_code']??'').")"); ?></div>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th>Student</th>
            <th style="width:140px">Status</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; foreach ($students as $stu): $sid=(int)$stu['id']; $name = trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username']??''); $cur = $existing[$sid]['status'] ?? 'present'; $rem = $existing[$sid]['remarks'] ?? ''; ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($name); ?></td>
              <td><?php echo htmlspecialchars(ucfirst($cur)); ?></td>
              <td><?php echo htmlspecialchars($rem); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <script>window.print();</script>
    </body>
    </html><?php
    exit;
}

include '../components/header.php';
?>

<style>
.attendance-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:1024px){.attendance-grid{grid-template-columns:1fr}}
.badge-status{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid var(--border-color)}
.badge-present{background:#dcfce7;color:#166534}
.badge-absent{background:#fee2e2;color:#991b1b}
.badge-late{background:#fef9c3;color:#92400e}
.badge-excused{background:#e0e7ff;color:#3730a3}
</style>

<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Attendance</h1></div>

    <?php if ($success): ?>
      <script>
        (function(){
          const msg = <?php echo json_encode($success); ?>;
          (window.RindaApp && typeof window.RindaApp.showNotification === 'function')
            ? window.RindaApp.showNotification(msg, 'success')
            : (window.showNotification && window.showNotification(msg, 'success'));
        })();
      </script>
    <?php endif; ?>
    <?php if ($errors): ?>
      <script>
        (function(){
          const msg = <?php echo json_encode(implode("\n", $errors)); ?>;
          (window.RindaApp && typeof window.RindaApp.showNotification === 'function')
            ? window.RindaApp.showNotification(msg, 'error')
            : (window.showNotification && window.showNotification(msg, 'error'));
        })();
      </script>
    <?php endif; ?>

    <div class="card">
      <div class="card-content">
        <form method="GET" class="form" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
          <div class="form-group">
            <label>Class</label>
            <select name="class_id" onchange="this.form.submit()" required>
              <option value="0">Select class</option>
              <?php foreach ($classes as $c): $id=(int)$c['id']; $label=$c['class_name'].' (G'.(int)($c['grade_level']??0).') #'.($c['class_code']??''); ?>
                <option value="<?php echo $id; ?>" <?php echo $classId===$id?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Subject</label>
            <select name="subject_id" onchange="this.form.submit()" required>
              <option value="0">Select subject</option>
              <?php if ($classId>0): foreach (($subjectsByClass[$classId] ?? []) as $s): $sid=(int)$s['subject_id']; $slabel=($s['subject_name']??'-').' #'.($s['subject_code']??''); ?>
                <option value="<?php echo $sid; ?>" <?php echo $subjectId===$sid?'selected':''; ?>><?php echo htmlspecialchars($slabel); ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date</label>
            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" onchange="this.form.submit()" required>
          </div>
        </form>
      </div>
    </div>

    <?php if ($classId>0 && $subjectId>0): ?>
    <div class="card" style="margin-top:12px;">
      <div class="card-content">
        <form method="POST" class="form">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
          <input type="hidden" name="class_id" value="<?php echo (int)$classId; ?>">
          <input type="hidden" name="subject_id" value="<?php echo (int)$subjectId; ?>">
          <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">

          <?php if (!$students): ?>
            <div class="no-data"><i class="fas fa-user-slash"></i><p>No enrolled students found for this class.</p></div>
          <?php else: ?>
            <div style="display:flex;gap:8px;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap">
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="button" data-mark="present" class="btn btn-secondary js-mark-all"><i class="fas fa-user-check"></i> Mark all Present</button>
                <button type="button" data-mark="absent" class="btn btn-secondary js-mark-all"><i class="fas fa-user-times"></i> Mark all Absent</button>
                <button type="button" data-mark="late" class="btn btn-secondary js-mark-all"><i class="fas fa-clock"></i> Mark all Late</button>
                <button type="button" data-mark="excused" class="btn btn-secondary js-mark-all"><i class="fas fa-passport"></i> Mark all Excused</button>
                <?php $printUrl = './teacher_attendance.php?class_id='.(int)$classId.'&subject_id='.(int)$subjectId.'&date='.rawurlencode($date).'&print=1'; ?>
                <a href="<?php echo $printUrl; ?>" target="_blank" rel="noopener" class="btn btn-light"><i class="fas fa-print"></i> Print</a>
                <a href="./teacher_attendance_summary.php?class_id=<?php echo (int)$classId; ?>&subject_id=<?php echo (int)$subjectId; ?>" class="btn btn-light"><i class="fas fa-table"></i> Summary/Export</a>
              </div>
              <div style="opacity:.7;font-size:12px;">Tip: Save before printing to include latest changes</div>
            </div>
            <div class="attendance-grid">
              <?php foreach ($students as $stu): $sid=(int)$stu['id']; $name = trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username']??''); $cur = $existing[$sid]['status'] ?? 'present'; $rem = $existing[$sid]['remarks'] ?? ''; ?>
                <div class="teacher-card">
                  <div class="teacher-name"><strong><?php echo htmlspecialchars($name); ?></strong></div>
                  <div class="virtual-meta">ID: <?php echo $sid; ?></div>
                  <div class="form-group" style="margin-top:8px;">
                    <label>Status</label>
                    <select name="status[<?php echo $sid; ?>]">
                      <option value="present" <?php echo $cur==='present'?'selected':''; ?>>Present</option>
                      <option value="absent" <?php echo $cur==='absent'?'selected':''; ?>>Absent</option>
                      <option value="late" <?php echo $cur==='late'?'selected':''; ?>>Late</option>
                      <option value="excused" <?php echo $cur==='excused'?'selected':''; ?>>Excused</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks[<?php echo $sid; ?>]" value="<?php echo htmlspecialchars($rem); ?>" placeholder="Optional">
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Attendance</button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

<?php include '../components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.js-mark-all').forEach(function(btn){
    btn.addEventListener('click', function(){
      const val = this.getAttribute('data-mark');
      if (!val) return;
      const sels = Array.from(document.querySelectorAll('select[name^="status["]'));
      const n = sels.length;
      if (!n) return;
      const label = val.charAt(0).toUpperCase()+val.slice(1);
      if (!confirm(`Set ${n} student(s) to ${label}?`)) return;
      sels.forEach(function(sel){ sel.value = val; });
    });
  });
});
</script>
