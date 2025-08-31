<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isTeacher()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$teacherId = getCurrentUserId();

// Load teacher classes
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

// Inputs
$classId = (int)($_GET['class_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? 0);
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$export = ($_GET['export'] ?? '') === 'csv';
$printMode = ($_GET['print'] ?? '') === '1';

function teacherHasAssignment(PDO $pdo, int $teacherId, int $classId, int $subjectId): bool {
    if ($classId <= 0 || $subjectId <= 0) return false;
    $s = $pdo->prepare("SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1 LIMIT 1");
    $s->execute([$teacherId, $classId, $subjectId]);
    return (bool)$s->fetchColumn();
}

$valid = ($classId>0 && $subjectId>0 && preg_match('/^\d{4}-\d{2}-\d{2}$/',$start) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$end) && $start <= $end && teacherHasAssignment($pdo,$teacherId,$classId,$subjectId));

// Fetch meta
$classInfo = null; $subjectInfo = null;
if ($classId>0) {
    $cs = $pdo->prepare("SELECT class_name, class_code, grade_level FROM classes WHERE id=?");
    $cs->execute([$classId]);
    $classInfo = $cs->fetch(PDO::FETCH_ASSOC) ?: [];
}
if ($subjectId>0) {
    $ss = $pdo->prepare("SELECT subject_name, subject_code FROM subjects WHERE id=?");
    $ss->execute([$subjectId]);
    $subjectInfo = $ss->fetch(PDO::FETCH_ASSOC) ?: [];
}

$students = [];
$records = [];
$statusCounts = ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0];
$perStudentCounts = [];

if ($valid) {
    // Students in class
    $stuStmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.username
                              FROM student_enrollments se
                              JOIN users u ON u.id = se.student_id
                              WHERE se.class_id = ? AND u.role='student' AND COALESCE(u.is_active,1)=1
                              ORDER BY u.first_name, u.last_name");
    $stuStmt->execute([$classId]);
    $students = $stuStmt->fetchAll(PDO::FETCH_ASSOC);

    // Attendance in range
    $att = $pdo->prepare("SELECT student_id, date, status, remarks
                          FROM attendance
                          WHERE class_id=? AND subject_id=? AND date BETWEEN ? AND ?
                          ORDER BY date ASC, student_id ASC");
    $att->execute([$classId, $subjectId, $start, $end]);
    while ($r = $att->fetch(PDO::FETCH_ASSOC)) {
        $sid = (int)$r['student_id'];
        $d = $r['date'];
        $records[$sid][$d] = ['status'=>$r['status'], 'remarks'=>$r['remarks']];
        if (isset($statusCounts[$r['status']])) $statusCounts[$r['status']]++;
        if (!isset($perStudentCounts[$sid])) $perStudentCounts[$sid] = ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0];
        if (isset($perStudentCounts[$sid][$r['status']])) $perStudentCounts[$sid][$r['status']]++;
    }
}

// CSV export
if ($export && $valid) {
    $filename = 'attendance_'.($classInfo['class_code']??'class').'_' . ($subjectInfo['subject_code']??'subject') . '_' . $start . '_to_' . $end . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    // Header
    fputcsv($out, ['Class', ($classInfo['class_name']??'').' (#'.($classInfo['class_code']??'').') Grade '.(int)($classInfo['grade_level']??0)]);
    fputcsv($out, ['Subject', ($subjectInfo['subject_name']??'').' (#'.($subjectInfo['subject_code']??'').')']);
    fputcsv($out, ['Range', $start.' to '.$end]);
    fputcsv($out, []);

    // Dates list
    $dates = [];
    for ($d = strtotime($start); $d <= strtotime($end); $d = strtotime('+1 day', $d)) {
        $dates[] = date('Y-m-d', $d);
    }
    $header = ['Student ID','Student Name'];
    foreach ($dates as $d) { $header[] = $d; }
    $header[] = 'Present';
    $header[] = 'Absent';
    $header[] = 'Late';
    $header[] = 'Excused';
    fputcsv($out, $header);

    foreach ($students as $stu) {
        $sid = (int)$stu['id'];
        $name = trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username']??'');
        $row = [$sid, $name];
        foreach ($dates as $d) {
            $st = $records[$sid][$d]['status'] ?? '';
            $row[] = $st;
        }
        $pc = $perStudentCounts[$sid] ?? ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0];
        $row[] = $pc['present'];
        $row[] = $pc['absent'];
        $row[] = $pc['late'];
        $row[] = $pc['excused'];
        fputcsv($out, $row);
    }

    fclose($out);
    exit;
}

$page_title = 'Attendance Summary';
$current_page = 'teacher_attendance_summary';

if ($printMode && $valid) {
    // Minimal print view
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Attendance Summary Print</title>
      <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;color:#111827;margin:24px}
        h1{font-size:20px;margin:0 0 8px}
        .meta{margin-bottom:16px;color:#374151}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #e5e7eb;padding:6px 8px;text-align:left;font-size:12px}
        th{background:#f9fafb}
        .totals{margin-top:12px}
      </style>
    </head>
    <body>
      <h1>Attendance Summary</h1>
      <div class="meta">
        <div><strong>Range:</strong> <?php echo htmlspecialchars($start.' to '.$end); ?></div>
        <div><strong>Class:</strong> <?php echo htmlspecialchars(($classInfo['class_name']??'-')." (#".($classInfo['class_code']??'').") Grade ".(int)($classInfo['grade_level']??0)); ?></div>
        <div><strong>Subject:</strong> <?php echo htmlspecialchars(($subjectInfo['subject_name']??'-')." (#".($subjectInfo['subject_code']??'').")"); ?></div>
      </div>
      <?php
        $dates = [];
        for ($d = strtotime($start); $d <= strtotime($end); $d = strtotime('+1 day', $d)) { $dates[] = date('Y-m-d', $d); }
      ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <?php foreach ($dates as $d): ?>
              <th><?php echo htmlspecialchars($d); ?></th>
            <?php endforeach; ?>
            <th>Present</th><th>Absent</th><th>Late</th><th>Excused</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; foreach ($students as $stu): $sid=(int)$stu['id']; $name = trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username']??''); $pc = $perStudentCounts[$sid] ?? ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0]; ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($name); ?></td>
              <?php foreach ($dates as $d): $st = $records[$sid][$d]['status'] ?? ''; ?>
                <td><?php echo htmlspecialchars($st); ?></td>
              <?php endforeach; ?>
              <td><?php echo (int)$pc['present']; ?></td>
              <td><?php echo (int)$pc['absent']; ?></td>
              <td><?php echo (int)$pc['late']; ?></td>
              <td><?php echo (int)$pc['excused']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="totals">
        <strong>Totals:</strong>
        Present: <?php echo (int)$statusCounts['present']; ?>,
        Absent: <?php echo (int)$statusCounts['absent']; ?>,
        Late: <?php echo (int)$statusCounts['late']; ?>,
        Excused: <?php echo (int)$statusCounts['excused']; ?>
      </div>
      <script>window.print();</script>
    </body>
    </html><?php
    exit;
}

include '../components/header.php';
?>

<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Attendance Summary</h1></div>

    <div class="card">
      <div class="card-content">
        <form method="GET" class="form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <div class="form-group">
            <label>Class</label>
            <select name="class_id" required onchange="this.form.submit()">
              <option value="0">Select class</option>
              <?php foreach ($classes as $c): $id=(int)$c['id']; $label=$c['class_name'].' (G'.(int)($c['grade_level']??0).') #'.($c['class_code']??''); ?>
                <option value="<?php echo $id; ?>" <?php echo $classId===$id?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Subject</label>
            <select name="subject_id" required onchange="this.form.submit()">
              <option value="0">Select subject</option>
              <?php if ($classId>0): foreach (($subjectsByClass[$classId] ?? []) as $s): $sid=(int)$s['subject_id']; $slabel=($s['subject_name']??'-').' #'.($s['subject_code']??''); ?>
                <option value="<?php echo $sid; ?>" <?php echo $subjectId===$sid?'selected':''; ?>><?php echo htmlspecialchars($slabel); ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Start</label>
            <input type="date" name="start" value="<?php echo htmlspecialchars($start); ?>" required>
          </div>
          <div class="form-group">
            <label>End</label>
            <input type="date" name="end" value="<?php echo htmlspecialchars($end); ?>" required>
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
          </div>
          <?php if ($valid): ?>
          <div class="form-group" style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-light" target="_blank" rel="noopener" href="<?php echo './teacher_attendance_summary.php?class_id='.(int)$classId.'&subject_id='.(int)$subjectId.'&start='.rawurlencode($start).'&end='.rawurlencode($end).'&export=csv'; ?>"><i class="fas fa-file-csv"></i> Export CSV</a>
            <a class="btn btn-light" target="_blank" rel="noopener" href="<?php echo './teacher_attendance_summary.php?class_id='.(int)$classId.'&subject_id='.(int)$subjectId.'&start='.rawurlencode($start).'&end='.rawurlencode($end).'&print=1'; ?>"><i class="fas fa-print"></i> Print</a>
          </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <?php if ($valid): ?>
    <div class="card" style="margin-top:12px;overflow:auto">
      <div class="card-content">
        <?php
          $dates = [];
          for ($d = strtotime($start); $d <= strtotime($end); $d = strtotime('+1 day', $d)) { $dates[] = date('Y-m-d', $d); }
        ?>
        <div style="margin-bottom:8px;opacity:.75">
          <strong>Totals:</strong>
          Present: <?php echo (int)$statusCounts['present']; ?> ·
          Absent: <?php echo (int)$statusCounts['absent']; ?> ·
          Late: <?php echo (int)$statusCounts['late']; ?> ·
          Excused: <?php echo (int)$statusCounts['excused']; ?>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <?php foreach ($dates as $d): ?>
                <th><?php echo htmlspecialchars(substr($d,5)); ?></th>
              <?php endforeach; ?>
              <th>P</th><th>A</th><th>L</th><th>E</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; foreach ($students as $stu): $sid=(int)$stu['id']; $name = trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username']??''); $pc = $perStudentCounts[$sid] ?? ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0]; ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($name); ?></td>
                <?php foreach ($dates as $d): $st = $records[$sid][$d]['status'] ?? ''; ?>
                  <td><?php echo $st ? strtoupper($st[0]) : ''; ?></td>
                <?php endforeach; ?>
                <td><?php echo (int)$pc['present']; ?></td>
                <td><?php echo (int)$pc['absent']; ?></td>
                <td><?php echo (int)$pc['late']; ?></td>
                <td><?php echo (int)$pc['excused']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php elseif ($classId>0 && $subjectId>0): ?>
      <div class="card" style="margin-top:12px">
        <div class="card-content">
          <div class="no-data"><i class="fas fa-info-circle"></i><p>Please provide a valid date range and ensure you are assigned to this class and subject.</p></div>
        </div>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php include '../components/footer.php'; ?>
