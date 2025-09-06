<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$userId = getCurrentUserId();
$isAdmin = isSuperAdmin();
$isTeacherRole = isTeacher();

if (!$isAdmin && !$isTeacherRole) { redirect('../dashboard/index.php'); }

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if ($examId <= 0) { redirect('./exams.php'); }

// Load exam template
$examStmt = $pdo->prepare('SELECT e.*, c.class_name, c.class_code, s.subject_name, s.subject_code FROM exam_assessments e LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN subjects s ON s.id=e.subject_id WHERE e.exam_assessment_id=? AND e.student_id=0');
$examStmt->execute([$examId]);
$exam = $examStmt->fetch(PDO::FETCH_ASSOC);
if (!$exam) { redirect('./exams.php'); }

// Permission check for teachers
if (!$isAdmin && $isTeacherRole) {
  $hasAssignment = $pdo->prepare('SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1 LIMIT 1');
  $hasAssignment->execute([$userId, (int)$exam['class_id'], (int)$exam['subject_id']]);
  if (!$hasAssignment->fetchColumn()) { redirect('../dashboard/index.php'); }
}

// Handle manual grading
$message = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
$csrf = generateCSRFToken();

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { throw new Exception('Invalid CSRF'); }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'grade_manual') {
      $studentId = (int)($_POST['student_id'] ?? 0);
      $obtainedMarks = (float)($_POST['obtained_marks'] ?? 0);
      $totalMarks = (float)($_POST['total_marks'] ?? 0);
      $feedback = trim((string)($_POST['feedback'] ?? ''));
      
      if ($studentId <= 0 || $totalMarks <= 0) { throw new Exception('Invalid data'); }
      if ($obtainedMarks > $totalMarks) { throw new Exception('Obtained marks cannot exceed total marks'); }
      
      // Calculate percentage and grade
      $percentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0;
      $grade = '';
      if ($percentage >= 70) $grade = 'A';
      elseif ($percentage >= 60) $grade = 'B';
      elseif ($percentage >= 50) $grade = 'C';
      elseif ($percentage >= 45) $grade = 'D';
      else $grade = 'F';
      
      // Update exam result
      $updateStmt = $pdo->prepare('UPDATE exam_results SET obtained_marks=?, percentage=?, grade=?, grading_type=?, graded_by=?, graded_at=NOW(), status=? WHERE exam_assessment_id IN (SELECT exam_assessment_id FROM exam_assessments WHERE student_id=? AND subject_id=? AND class_id=?)');
      $updateStmt->execute([$obtainedMarks, $percentage, $grade, 'manual', $userId, 'graded', $studentId, (int)$exam['subject_id'], (int)$exam['class_id']]);
      
      $_SESSION['flash_success'] = 'Exam graded successfully';
      header('Location: '.$_SERVER['REQUEST_URI']);
      exit;
    }
  }
} catch (Throwable $e) {
  $_SESSION['flash_error'] = $e->getMessage();
  header('Location: '.$_SERVER['REQUEST_URI']);
  exit;
}

// Fetch all student submissions for this exam
$submissions = [];
try {
  // Get all student assessments that match this exam template (same subject and class)
  // Note: Students create their own assessments, so we look for matching subject/class
  $subStmt = $pdo->prepare('
    SELECT 
      e.exam_assessment_id,
      e.student_id,
      e.timespan,
      er.obtained_marks,
      er.total_marks,
      er.percentage,
      er.grade,
      er.grading_type,
      er.status,
      er.submitted_at,
      er.graded_at,
      er.feedback,
      u.first_name,
      u.last_name,
      u.username
    FROM exam_assessments e
    LEFT JOIN exam_results er ON er.exam_assessment_id = e.exam_assessment_id
    LEFT JOIN users u ON u.id = e.student_id
    WHERE e.student_id > 0 
    AND e.subject_id = ? 
    AND e.class_id = ? 
    ORDER BY e.exam_assessment_id DESC
  ');
  $subStmt->execute([(int)$exam['subject_id'], (int)$exam['class_id']]);
  $submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Debug: Log the query and results
  error_log("Exam submissions query executed for subject_id: " . (int)$exam['subject_id'] . ", class_id: " . (int)$exam['class_id']);
  error_log("Found " . count($submissions) . " submissions");
  
} catch (Throwable $e) {
  error_log("Error fetching exam submissions: " . $e->getMessage());
  $submissions = [];
}

$page_title = 'Exam Submissions';
$current_page = 'exams';
include '../components/header.php';
?>

<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Exam Submissions</h1>
        <div class="muted">
          <?php echo htmlspecialchars($exam['subject_name'] ?? ''); ?> · 
          Class: <?php echo htmlspecialchars(($exam['class_name']??'').' #'.($exam['class_code']??'')); ?> · 
          Type: <?php echo htmlspecialchars($exam['type']); ?>
        </div>
      </div>
      <div class="header-actions">
        <a class="btn btn-secondary" href="./exams.php"><i class="fas fa-arrow-left"></i> Back to Exams</a>
      </div>
    </div>

    <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="content-card">
      <div class="card-header">
        <h3>Student Submissions (<?php echo count($submissions); ?>)</h3>
      </div>
      <div class="card-content">
        <!-- Debug Information -->
        <div class="debug-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
          <strong>Debug Info:</strong><br>
          Exam ID: <?php echo (int)$examId; ?><br>
          Subject ID: <?php echo (int)$exam['subject_id']; ?><br>
          Class ID: <?php echo (int)$exam['class_id']; ?><br>
          Timespan: <?php echo htmlspecialchars($exam['timespan']); ?><br>
          Exam Type: <?php echo htmlspecialchars($exam['type']); ?><br>
          Submissions Found: <?php echo count($submissions); ?><br>
          <br>
          <strong>Query Details:</strong><br>
          Looking for student assessments with:<br>
          - subject_id = <?php echo (int)$exam['subject_id']; ?><br>
          - class_id = <?php echo (int)$exam['class_id']; ?><br>
          <br>
          <strong>Raw Data Check:</strong><br>
          <?php
          try {
            $rawCheck = $pdo->prepare('SELECT COUNT(*) as total FROM exam_assessments WHERE student_id > 0 AND subject_id = ? AND class_id = ?');
            $rawCheck->execute([(int)$exam['subject_id'], (int)$exam['class_id']]);
            $totalRaw = $rawCheck->fetchColumn();
            echo "Total student assessments in database: " . $totalRaw . "<br>";
            
            if ($totalRaw > 0) {
              $sampleCheck = $pdo->prepare('SELECT student_id, type, status, timespan FROM exam_assessments WHERE student_id > 0 AND subject_id = ? AND class_id = ? LIMIT 3');
              $sampleCheck->execute([(int)$exam['subject_id'], (int)$exam['class_id']]);
              $samples = $sampleCheck->fetchAll(PDO::FETCH_ASSOC);
              echo "Sample assessments:<br>";
              foreach ($samples as $sample) {
                echo "- Student ID: " . $sample['student_id'] . ", Type: " . $sample['type'] . ", Status: " . $sample['status'] . "<br>";
              }
            }
          } catch (Exception $e) {
            echo "Error checking raw data: " . htmlspecialchars($e->getMessage());
          }
          ?>
        </div>
        
        <?php if (empty($submissions)): ?>
          <div class="empty-state">
            <p>No submissions yet.</p>
            <p><small>This could mean:</small></p>
            <ul style="text-align: left; display: inline-block;">
              <li>No students have taken this exam yet</li>
              <!-- <li>Students are taking exams but results aren't being saved</li>
              <li>Database query issue</li> -->
            </ul>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>Submitted</th>
                  <th>Total Marks</th>
                  <th>Obtained</th>
                  <th>Percentage</th>
                  <th>Grade</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($submissions as $i => $sub): ?>
                  <?php 
                    $name = trim(($sub['first_name']??'').' '.($sub['last_name']??'')); 
                    if ($name === '') $name = $sub['username'] ?? ('Student #'.(int)$sub['student_id']); 
                  ?>
                  <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($name); ?></td>
                    <td><?php echo htmlspecialchars($sub['submitted_at'] ?? ''); ?></td>
                    <td><?php echo (float)($sub['total_marks'] ?? 0); ?></td>
                    <td>
                      <?php if ($sub['grading_type'] === 'manual' && $sub['status'] === 'pending'): ?>
                        <span class="muted">Pending</span>
                      <?php else: ?>
                        <?php echo (float)($sub['obtained_marks'] ?? 0); ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($sub['grading_type'] === 'manual' && $sub['status'] === 'pending'): ?>
                        <span class="muted">-</span>
                      <?php else: ?>
                        <?php echo htmlspecialchars($sub['percentage'] ?? ''); ?>%
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($sub['grading_type'] === 'manual' && $sub['status'] === 'pending'): ?>
                        <span class="muted">-</span>
                      <?php else: ?>
                        <span class="badge badge-<?php echo ($sub['grade'] ?? '') === 'A' ? 'success' : (($sub['grade'] ?? '') === 'F' ? 'danger' : 'warning'); ?>">
                          <?php echo htmlspecialchars($sub['grade'] ?? ''); ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($sub['grading_type'] === 'auto'): ?>
                        <span class="badge badge-success">Auto-graded</span>
                      <?php elseif ($sub['status'] === 'pending'): ?>
                        <span class="badge badge-warning">Pending</span>
                      <?php elseif ($sub['status'] === 'graded'): ?>
                        <span class="badge badge-info">Manually Graded</span>
                      <?php else: ?>
                        <span class="badge"><?php echo htmlspecialchars($sub['status'] ?? ''); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <a class="btn btn-sm btn-secondary" href="./exam_result_view.php?exam_id=<?php echo (int)$examId; ?>&student_id=<?php echo (int)$sub['student_id']; ?>" title="View Details">
                          <i class="fas fa-eye"></i>
                        </a>
                        
                        <?php if ($exam['type'] === 'manual' && $sub['status'] === 'pending'): ?>
                          <button class="btn btn-sm btn-primary" onclick="openGradeModal(<?php echo (int)$sub['student_id']; ?>, '<?php echo htmlspecialchars($name); ?>', <?php echo (float)($sub['total_marks'] ?? 0); ?>)" title="Grade Exam">
                            <i class="fas fa-edit"></i>
                          </button>
                        <?php endif; ?>
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

<!-- Manual Grading Modal -->
<div class="modal" id="gradeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Grade Exam</h5>
        <button type="button" class="close" onclick="closeGradeModal()">&times;</button>
      </div>
      <form method="post" id="gradeForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>"/>
        <input type="hidden" name="action" value="grade_manual"/>
        <input type="hidden" name="student_id" id="gradeStudentId"/>
        <input type="hidden" name="total_marks" id="gradeTotalMarks"/>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Student</label>
            <input type="text" id="gradeStudentName" class="form-control" readonly/>
          </div>
          
          <div class="form-group">
            <label for="gradeObtainedMarks">Obtained Marks *</label>
            <input type="number" id="gradeObtainedMarks" name="obtained_marks" class="form-control" step="0.01" min="0" required/>
            <small class="form-text">Maximum: <span id="gradeMaxMarks"></span></small>
          </div>
          
          <div class="form-group">
            <label for="gradeFeedback">Feedback (Optional)</label>
            <textarea id="gradeFeedback" name="feedback" class="form-control" rows="3"></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeGradeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Grade</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openGradeModal(studentId, studentName, totalMarks) {
  document.getElementById('gradeStudentId').value = studentId;
  document.getElementById('gradeStudentName').value = studentName;
  document.getElementById('gradeTotalMarks').value = totalMarks;
  document.getElementById('gradeMaxMarks').textContent = totalMarks;
  document.getElementById('gradeObtainedMarks').max = totalMarks;
  document.getElementById('gradeObtainedMarks').value = '';
  document.getElementById('gradeFeedback').value = '';
  
  document.getElementById('gradeModal').style.display = 'block';
  document.getElementById('gradeModal').classList.add('show');
}

function closeGradeModal() {
  document.getElementById('gradeModal').style.display = 'none';
  document.getElementById('gradeModal').classList.remove('show');
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('gradeModal');
  if (event.target === modal) {
    closeGradeModal();
  }
}
</script>

<style>
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
}

.modal.show {
  display: block;
}

.modal-dialog {
  margin: 50px auto;
  max-width: 500px;
}

.modal-content {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
}

.close:hover {
  color: #374151;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #374151;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-text {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}
</style>

<?php include '../components/footer.php'; ?>
