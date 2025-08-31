<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$userId = getCurrentUserId();
$isAdmin = isSuperAdmin();
$isTeacherRole = isTeacher();

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if ($examId <= 0) { redirect('./exams.php'); }

// Load exam template. If student-specific ID was passed, map to template by class/subject/timespan
$E = null; $templateId = $examId;
try {
  $examStmt = $pdo->prepare('SELECT e.*, c.class_name,c.class_code, s.subject_name,s.subject_code FROM exam_assessments e LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN subjects s ON s.id=e.subject_id WHERE e.exam_assessment_id=? AND e.student_id=0');
  $examStmt->execute([$examId]);
  $E = $examStmt->fetch(PDO::FETCH_ASSOC) ?: null;
  if (!$E) {
    // Maybe a student-specific id was provided. Fetch it, then resolve template
    $one = $pdo->prepare('SELECT * FROM exam_assessments WHERE exam_assessment_id=?');
    $one->execute([$examId]);
    $row = $one->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      $resTpl = $pdo->prepare('SELECT e.*, c.class_name,c.class_code, s.subject_name,s.subject_code FROM exam_assessments e LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN subjects s ON s.id=e.subject_id WHERE e.student_id=0 AND e.class_id=? AND e.subject_id=? AND e.timespan=?');
      $resTpl->execute([(int)$row['class_id'], (int)$row['subject_id'], (string)$row['timespan']]);
      $E = $resTpl->fetch(PDO::FETCH_ASSOC) ?: null;
      if ($E) { $templateId = (int)$E['exam_assessment_id']; }
    }
  } else {
    $templateId = (int)$E['exam_assessment_id'];
  }
} catch (Throwable $e) { $E = null; }
if (!$E) { redirect('./exams.php'); }

// Permission: teacher assigned or admin
if (!$isAdmin) {
  if (!$isTeacherRole) { redirect('../dashboard/index.php'); }
  $has = $pdo->prepare('SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1');
  $has->execute([$userId,(int)$E['class_id'],(int)$E['subject_id']]);
  if (!$has->fetchColumn()) { redirect('../dashboard/index.php'); }
}

// Parse config
$cfg = []; if (!empty($E['status'])) { foreach (explode(';',(string)$E['status']) as $p){ if (strpos($p,'=')!==false){[$k,$v]=explode('=',$p,2); $cfg[$k]=$v; } }}
$durationMin = (int)($cfg['dur'] ?? 0);
$rv = (string)($cfg['rv'] ?? 'immediate');

// Manual grading is now handled through the exam_results table

$message = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
$csrf = generateCSRFToken();

// Manual grading is now handled through the exam_submissions.php page

// Fetch students who have taken this exam with their results
$students = [];
try {
  $stuStmt = $pdo->prepare('
    SELECT DISTINCT 
      e.student_id, 
      u.first_name, 
      u.last_name, 
      u.username,
      er.obtained_marks,
      er.total_marks,
      er.percentage,
      er.grade,
      er.grading_type,
      er.status,
      er.submitted_at,
      er.graded_at
    FROM exam_assessments e 
    LEFT JOIN users u ON u.id=e.student_id 
    LEFT JOIN exam_results er ON er.exam_assessment_id = e.exam_assessment_id
    WHERE e.student_id>0 
    AND e.subject_id=? 
    AND e.class_id=? 
    ORDER BY u.first_name, u.last_name
  ');
  $stuStmt->execute([(int)$E['subject_id'], (int)$E['class_id']]);
  $students = $stuStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { $students = []; }

// Question marks are now stored in the exam_results table

// Optional debug
$debugInfo = null; $recent = [];
if (isset($_GET['debug'])) {
  try { $dbn = $pdo->query('SELECT DATABASE()')->fetchColumn(); } catch (Throwable $e) { $dbn = 'n/a'; }
  $debugInfo = [
    'db' => $dbn,
    'exam_id_param' => $examId,
    'template_id' => $templateId,
    'class_id' => (int)$E['class_id'],
    'subject_id' => (int)$E['subject_id'],
    'timespan' => (string)$E['timespan'],
    'all_assess_ids' => $allAssessIds,
    'students_count' => count($students),
  ];
  try {
    $ph = implode(',', array_fill(0, count($allAssessIds), '?'));
    $rs = $pdo->prepare('SELECT exam_assessment_id, student_id, question_id, createdate FROM exam_assessments_data WHERE exam_assessment_id IN ('.$ph.') ORDER BY createdate DESC LIMIT 10');
    $rs->execute($allAssessIds);
    $recent = $rs->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) { $recent = []; }
  // Per-assessment row counts
  try {
    $cnts = [];
    foreach ($allAssessIds as $aid) {
      $cst = $pdo->prepare('SELECT COUNT(*) FROM exam_assessments_data WHERE exam_assessment_id=?');
      $cst->execute([(int)$aid]);
      $cnts[(int)$aid] = (int)$cst->fetchColumn();
    }
    $debugInfo['rows_per_assessment'] = $cnts;
  } catch (Throwable $e) {}
}

$page_title = 'Exam Results';
$current_page = 'exams';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Results · Exam #<?php echo (int)$E['exam_assessment_id']; ?> · <?php echo htmlspecialchars($E['subject_name'] ?? ''); ?></h1>
        <div class="muted">Class: <?php echo htmlspecialchars(($E['class_name']??'').' #'.($E['class_code']??'')); ?> · Type: <?php echo htmlspecialchars($E['type']); ?> · Start: <?php echo htmlspecialchars($E['timespan']); ?></div>
      </div>
      <div>
        <a class="btn btn-secondary" href="./exams.php"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>

    <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="content-card">
      <div class="card-header"><h3>Submissions (<?php echo count($students); ?>)</h3></div>
      <div class="card-content">
        <?php if (isset($debugInfo)): ?>
          <div class="alert info" style="white-space:pre-wrap;">
            <strong>Debug</strong> <?php echo htmlspecialchars(json_encode($debugInfo, JSON_PRETTY_PRINT)); ?>
            <?php if (!empty($recent)): ?>
            <div style="margin-top:6px"><em>Recent exam_assessments_data sample:</em></div>
            <pre style="background:#f6f8fa; padding:6px; border-radius:6px; max-height:220px; overflow:auto;"><?php echo htmlspecialchars(json_encode($recent, JSON_PRETTY_PRINT)); ?></pre>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if (!$students): ?>
          <div class="empty-state">No submissions yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Obtained Marks</th>
                  <th>Total Marks</th>
                  <th>Percentage</th>
                  <th>Grade</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($students as $S): ?>
                <?php
                  $sid = (int)$S['student_id'];
                  $name = trim(($S['first_name']??'').' '.($S['last_name']??'')); 
                  if ($name === '') { $name = $S['username'] ?? ('Student #'.$sid); }
                  
                  // Get result data from the new exam_results table
                  $obtainedMarks = (float)($S['obtained_marks'] ?? 0);
                  $totalMarks = (float)($S['total_marks'] ?? 0);
                  $percentage = $S['percentage'] ?? 0;
                  $grade = $S['grade'] ?? '';
                  $gradingType = $S['grading_type'] ?? 'auto';
                  $status = $S['status'] ?? 'pending';
                  $submittedAt = $S['submitted_at'] ?? '';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($name); ?></td>
                  <td>
                    <?php if ($gradingType === 'auto' && $status === 'graded'): ?>
                      <?php echo $obtainedMarks; ?>
                    <?php else: ?>
                      <span class="muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo $totalMarks; ?></td>
                  <td>
                    <?php if ($gradingType === 'auto' && $status === 'graded'): ?>
                      <?php echo $percentage; ?>%
                    <?php else: ?>
                      <span class="muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($gradingType === 'auto' && $status === 'graded'): ?>
                      <span class="badge badge-<?php echo $grade === 'A' ? 'success' : ($grade === 'F' ? 'danger' : 'warning'); ?>">
                        <?php echo htmlspecialchars($grade); ?>
                      </span>
                    <?php else: ?>
                      <span class="muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($gradingType === 'auto'): ?>
                      <span class="badge badge-success">Auto-graded</span>
                    <?php elseif ($status === 'pending'): ?>
                      <span class="badge badge-warning">Pending</span>
                    <?php elseif ($status === 'graded'): ?>
                      <span class="badge badge-info">Manually Graded</span>
                    <?php else: ?>
                      <span class="badge"><?php echo htmlspecialchars($status); ?></span>
                    <?php endif; ?>
                  </td>
                                      <td>
                      <div class="action-buttons">
                        <a class="btn btn-sm btn-secondary" href="./exam_result_view.php?exam_id=<?php echo (int)$examId; ?>&student_id=<?php echo $sid; ?>" title="View Details">
                          <i class="fas fa-eye"></i>
                        </a>
                      </div>
                    </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
