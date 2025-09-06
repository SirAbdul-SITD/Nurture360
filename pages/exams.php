<?php
require_once '../config/config.php';
require_once '../components/modal.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$userId = getCurrentUserId();
$isAdmin = isSuperAdmin();
$isTeacherRole = isTeacher();

if (!$isAdmin && !$isTeacherRole) { redirect('../dashboard/index.php'); }

function tClasses(PDO $pdo, int $tid): array {
  $q=$pdo->prepare('SELECT DISTINCT c.id,c.class_name,c.class_code,c.grade_level,c.academic_year FROM teacher_assignments ta JOIN classes c ON c.id=ta.class_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 ORDER BY c.grade_level,c.class_name');
  $q->execute([$tid]);
  return $q->fetchAll();
}
function tSubjects(PDO $pdo, int $tid): array {
  $q=$pdo->prepare('SELECT DISTINCT s.subject_id AS id, s.title AS subject_name, s.subject_code FROM teacher_assignments ta JOIN subjects s ON s.subject_id=ta.subject_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 ORDER BY s.title');
  $q->execute([$tid]);
  return $q->fetchAll();
}

// Flash messages
$message = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
$csrf = generateCSRFToken();

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { throw new Exception('Invalid CSRF'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
      $classId = (int)($_POST['class_id'] ?? 0);
      $subjectId = (int)($_POST['subject_id'] ?? 0);
      $marking = ($_POST['marking'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
      $startAt = trim((string)($_POST['start_at'] ?? ''));
      $endAt = trim((string)($_POST['end_at'] ?? ''));
      $durationMin = max(0, (int)($_POST['duration_min'] ?? 0));
      $visibility = $_POST['visibility'] ?? 'immediate'; // immediate, 2h, 1d, at_close
      
      if ($classId<=0 || $subjectId<=0) { throw new Exception('Select class and subject'); }
      if (!$isAdmin && !isTeacher()) { throw new Exception('Not allowed'); }
      
      // Calculate duration from start and end time if both are provided
      if ($startAt && $endAt) {
        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);
        if ($startTs && $endTs && $endTs > $startTs) {
          $durationMin = round(($endTs - $startTs) / 60);
        } else {
          throw new Exception('End time must be after start time');
        }
      }
      
      if (!$isAdmin && $isTeacherRole) {
        $has = $pdo->prepare('SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1');
        $has->execute([$userId,$classId,$subjectId]);
        if (!$has->fetchColumn()) { throw new Exception('Not assigned to this class/subject'); }
      }
      
      // timespan stores start_at; status stores config with duration and end time, type stores marking
      $status = 'open;dur='.$durationMin.';end='.($endAt ? strtotime($endAt) : '').';rv='.$visibility;
      $ts = $startAt ? date('Y-m-d H:i:s', strtotime($startAt)) : date('Y-m-d H:i:s');
      $stmt = $pdo->prepare('INSERT INTO exam_assessments (student_id,subject_id,class_id,`type`,`status`,timespan) VALUES (0,?,?,?,?,?)');
      $stmt->execute([$subjectId,$classId,$marking,$status,$ts]);
      $_SESSION['flash_success'] = 'Exam created';
      header('Location: '.$_SERVER['REQUEST_URI']);
      exit;
    } elseif ($action === 'delete') {
      $examId = (int)($_POST['exam_assessment_id'] ?? 0);
      if ($examId<=0) { throw new Exception('Invalid exam'); }
      // Teachers can delete only their exams (no ownership column; restrict to assigned class/subject)
      $row = $pdo->prepare('SELECT subject_id,class_id FROM exam_assessments WHERE exam_assessment_id=? AND student_id=0');
      $row->execute([$examId]);
      $exam = $row->fetch();
      if (!$exam) { throw new Exception('Exam not found'); }
      if (!$isAdmin) {
        $has = $pdo->prepare('SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1');
        $has->execute([$userId,(int)$exam['class_id'],(int)$exam['subject_id']]);
        if (!$has->fetchColumn()) { throw new Exception('Not allowed'); }
      }
      $pdo->prepare('DELETE FROM exam_assessments_data WHERE exam_assessment_id=?')->execute([$examId]);
      $pdo->prepare('DELETE FROM exam_assessments WHERE exam_assessment_id=?')->execute([$examId]);
      $_SESSION['flash_success'] = 'Exam deleted';
      header('Location: '.$_SERVER['REQUEST_URI']);
      exit;
    }
  }
} catch (Throwable $e) {
  $_SESSION['flash_error'] = $e->getMessage();
  header('Location: '.$_SERVER['REQUEST_URI']);
  exit;
}

// Load lists
if ($isAdmin) {
  $classes = $pdo->query('SELECT id,class_name,class_code FROM classes ORDER BY grade_level,class_name')->fetchAll();
  $subjects = $pdo->query('SELECT subject_id AS id, title AS subject_name, subject_code FROM subjects ORDER BY title')->fetchAll();
} else {
  $classes = tClasses($pdo,$userId);
  $subjects = tSubjects($pdo,$userId);
}

// Exams created (templates: student_id=0)
$exams = $pdo->query("SELECT e.*, c.class_name,c.class_code, s.title AS subject_name, s.subject_code FROM exam_assessments e LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN subjects s ON s.subject_id=e.subject_id WHERE e.student_id=0 ORDER BY e.exam_assessment_id DESC")->fetchAll();

$page_title = 'Exams';
$current_page = 'exams';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Exam Assessments</h1>
        <div class="muted">Create and manage your exams</div>
      </div>
      <div>
        <button class="btn btn-primary" type="button" onclick="openAddExamModal()"><i class="fas fa-plus"></i> Add Exam</button>
      </div>
    </div>

    <?php
      // Build modal form content
      ob_start();
    ?>
      <form method="post" id="addExamForm" class="form" data-validate="true">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>"/>
        <input type="hidden" name="action" value="create"/>

        <div class="form-row">
          <div class="form-group">
            <label for="exam_class_id">Class *</label>
            <select id="exam_class_id" name="class_id" required>
              <option value="">Select class</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars(($c['class_name']??'').' #'.($c['class_code']??'')); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="exam_subject_id">Subject *</label>
            <select id="exam_subject_id" name="subject_id" required>
              <option value="">Select subject</option>
              <?php foreach ($subjects as $s): ?>
                <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars(($s['subject_name']??'').' #'.($s['subject_code']??'')); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="exam_marking">Marking Type</label>
            <select id="exam_marking" name="marking">
              <option value="auto">Auto-grading</option>
              <option value="manual">Manual</option>
            </select>
            <small class="form-help">Auto-grading scores MCQ/short answers; manual requires teacher review.</small>
          </div>
          <div class="form-group">
            <label for="exam_start_at">Start At</label>
            <input type="datetime-local" id="exam_start_at" name="start_at"/>
            <small class="form-help">If left empty, the exam starts immediately.</small>
          </div>
          <div class="form-group">
            <label for="exam_end_at">End At</label>
            <input type="datetime-local" id="exam_end_at" name="end_at"/>
            <small class="form-help">Set the exact end time for the exam.</small>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="exam_duration">Duration (minutes)</label>
            <input type="number" id="exam_duration" name="duration_min" min="0" placeholder="e.g., 60"/>
            <small class="form-help">Set 0 to allow unlimited time. Will be calculated from start/end if both are set.</small>
          </div>
          <div class="form-group">
            <label for="exam_visibility">Results Visibility</label>
            <select id="exam_visibility" name="visibility">
              <option value="immediate">Immediately</option>
              <option value="2h">After 2 hours</option>
              <option value="1d">After 1 day</option>
              <option value="1w">After 1 week</option>
              <option value="1m">After 1 month</option>
              <option value="at_close">When exam closes</option>
            </select>
          </div>
        </div>
      </form>
    <?php
      $formContent = ob_get_clean();
      renderFormModal('addExamModal', 'Create Exam', $formContent, 'Create', 'Cancel', [ 'size' => 'large', 'formId' => 'addExamForm' ]);
    ?>

    <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- New Exam form moved to modal; use the Add Exam button above -->

    <script>
      function openAddExamModal(){
        var form = document.getElementById('addExamForm');
        if (form && typeof form.reset === 'function') form.reset();
        if (typeof window.openModalAddExamModal === 'function') {
          window.openModalAddExamModal();
          return;
        }
        var m = document.getElementById('addExamModal');
        if (m) { m.classList.add('show'); document.body.classList.add('modal-open'); m.focus && m.focus(); }
        else { console.error('Add Exam modal element not found'); }
      }
      
      // Auto-calculate duration when both start and end times are set
      document.addEventListener('DOMContentLoaded', function() {
        const startAtInput = document.getElementById('exam_start_at');
        const endAtInput = document.getElementById('exam_end_at');
        const durationInput = document.getElementById('exam_duration');
        
        function calculateDuration() {
          if (startAtInput.value && endAtInput.value) {
            const startTime = new Date(startAtInput.value);
            const endTime = new Date(endAtInput.value);
            
            if (endTime > startTime) {
              const diffMs = endTime - startTime;
              const diffMinutes = Math.round(diffMs / (1000 * 60));
              durationInput.value = diffMinutes;
            }
          }
        }
        
        if (startAtInput && endAtInput && durationInput) {
          startAtInput.addEventListener('change', calculateDuration);
          endAtInput.addEventListener('change', calculateDuration);
        }
      });
    </script>

    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-list"></i> Exams</h3>
      </div>
      <div class="card-content">
        <?php if (!$exams): ?>
          <div class="empty-state">No exams yet.</div>
        <?php else: ?>
          <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            <?php foreach ($exams as $E): ?>
              <div class="profile-card">
                <div class="profile-text">
                  <div class="profile-title-row">
                    <div class="profile-title">Exam #<?php echo (int)$E['exam_assessment_id']; ?> · <?php echo htmlspecialchars($E['subject_name'] ?? ''); ?></div>
                  </div>
                  <div class="profile-subtitle">Class: <?php echo htmlspecialchars(($E['class_name']??'').' #'.($E['class_code']??'')); ?></div>
                  <div class="muted">Start: <?php echo htmlspecialchars($E['timespan']); ?> · Type: <?php echo htmlspecialchars($E['type']); ?> · Cfg: <?php echo htmlspecialchars($E['status']); ?></div>
                </div>
                                  <div class="teacher-card-actions action-buttons centered">
                    <a class="btn btn-sm" title="Manage Questions" href="./exams_questions.php?subject_id=<?php echo (int)$E['subject_id']; ?>"><i class="fas fa-question"></i></a>
                    <a class="btn btn-sm btn-secondary" title="Submissions" href="./exam_submissions.php?exam_id=<?php echo (int)$E['exam_assessment_id']; ?>"><i class="fas fa-users"></i></a>
                    <!-- <a class="btn btn-sm btn-secondary" title="Results" href="./exams_results.php?exam_id=<?php echo (int)$E['exam_assessment_id']; ?>"><i class="fas fa-chart-bar"></i></a> -->
                    <form method="post" onsubmit="return confirm('Delete this exam?');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>"/>
                      <input type="hidden" name="action" value="delete"/>
                      <input type="hidden" name="exam_assessment_id" value="<?php echo (int)$E['exam_assessment_id']; ?>"/>
                      <button class="btn btn-sm btn-error" type="submit"><i class="fas fa-trash"></i></button>
                    </form>
                  </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
