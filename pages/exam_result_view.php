<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

function canManage(){ return isSuperAdmin() || (($_SESSION['role'] ?? '') === 'teacher'); }

$examId = (int)($_GET['exam_id'] ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);
if ($examId <= 0 || $studentId <= 0) { redirect('./exams.php'); }

// Load exam template
$examStmt = $pdo->prepare("SELECT e.*, c.class_name,c.class_code, s.title AS subject_name,s.subject_code
                     FROM exam_assessments e
                     LEFT JOIN classes c ON c.id=e.class_id
                     LEFT JOIN subjects s ON s.subject_id=e.subject_id
                     WHERE e.exam_assessment_id=? AND e.student_id=0");
$examStmt->execute([$examId]);
$exam = $examStmt->fetch(PDO::FETCH_ASSOC);
if (!$exam) { redirect('./exams.php'); }

// Load student user
$stu = null;
try {
  $s = $pdo->prepare('SELECT id, first_name, last_name, username FROM users WHERE id = ?');
  $s->execute([$studentId]);
  $stu = $s->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) { $stu = null; }

// Load student's exam assessment
$studentAssessment = null;
try {
  $sa = $pdo->prepare('SELECT * FROM exam_assessments WHERE student_id=? AND subject_id=? AND class_id=? LIMIT 1');
  $sa->execute([$studentId, (int)$exam['subject_id'], (int)$exam['class_id']]);
  $studentAssessment = $sa->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) { $studentAssessment = null; }

if (!$studentAssessment) { redirect('./exams.php'); }

// Load result summary
$result = null;
try {
  $sr = $pdo->prepare('SELECT * FROM exam_results WHERE exam_assessment_id = ? AND student_id = ? ORDER BY submitted_at DESC LIMIT 1');
  $sr->execute([$studentAssessment['exam_assessment_id'], $studentId]);
  $result = $sr->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) { $result = null; }

// Load questions
$qStmt = $pdo->prepare('SELECT * FROM exam_questions WHERE subject_id = ? ORDER BY question_id ASC');
$qStmt->execute([(int)$exam['subject_id']]);
$questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

// Load student answers
$answersByQ = [];
try {
  $a = $pdo->prepare('SELECT * FROM exam_assessments_data WHERE exam_assessment_id = ? AND student_id = ?');
  $a->execute([$studentAssessment['exam_assessment_id'], $studentId]);
  $ans = $a->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($ans as $row) { $answersByQ[(int)($row['question_id'] ?? 0)] = $row; }
} catch (Throwable $e) { $answersByQ = []; }

$page_title = 'Student Exam Result';
include '../components/header.php';
?>

<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Result: <?php echo htmlspecialchars($exam['subject_name'] ?? 'Exam'); ?></h1>
        <div class="muted">
          Class: <?php echo htmlspecialchars(($exam['class_name']??'-').' #'.($exam['class_code']??'-')); ?> ·
          Subject: <?php echo htmlspecialchars(($exam['subject_name']??'-').' #'.($exam['subject_code']??'-')); ?>
        </div>
      </div>
      <div class="header-actions">
        <a class="btn btn-secondary" href="./exam_submissions.php?exam_id=<?php echo (int)$examId; ?>"><i class="fas fa-arrow-left"></i> Back to Submissions</a>
      </div>
    </div>

    <div class="card-content">
      <!-- Student Info -->
      <div class="content-card">
        <div class="card-header"><h3>Student Information</h3></div>
        <div class="card-content">
          <div class="info-grid">
            <div class="info-item">
              <label>Student Name:</label>
              <span><?php echo htmlspecialchars(trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username'] ?? 'Student #'.(int)$studentId)); ?></span>
            </div>
            <div class="info-item">
              <label>Username:</label>
              <span><?php echo htmlspecialchars($stu['username'] ?? ''); ?></span>
            </div>
            <div class="info-item">
              <label>Exam Type:</label>
              <span><?php echo htmlspecialchars($exam['type']); ?></span>
            </div>
            <div class="info-item">
              <label>Submitted:</label>
              <span><?php echo htmlspecialchars($result['submitted_at'] ?? ''); ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Result Summary -->
      <?php if ($result): ?>
        <div class="content-card">
          <div class="card-header"><h3>Result Summary</h3></div>
          <div class="card-content">
            <div class="result-grid">
              <div class="result-item">
                <label>Total Marks:</label>
                <span class="result-value"><?php echo (float)($result['total_marks'] ?? 0); ?></span>
              </div>
              <div class="result-item">
                <label>Obtained Marks:</label>
                <span class="result-value"><?php echo (float)($result['obtained_marks'] ?? 0); ?></span>
              </div>
              <div class="result-item">
                <label>Percentage:</label>
                <span class="result-value"><?php echo htmlspecialchars($result['percentage'] ?? ''); ?>%</span>
              </div>
              <div class="result-item">
                <label>Grade:</label>
                <span class="result-value grade-<?php echo strtolower($result['grade'] ?? ''); ?>"><?php echo htmlspecialchars($result['grade'] ?? ''); ?></span>
              </div>
              <div class="result-item">
                <label>Grading Type:</label>
                <span class="result-value"><?php echo htmlspecialchars(ucfirst($result['grading_type'] ?? '')); ?></span>
              </div>
              <div class="result-item">
                <label>Status:</label>
                <span class="result-value status-<?php echo $result['status'] ?? ''; ?>"><?php echo htmlspecialchars(ucfirst($result['status'] ?? '')); ?></span>
              </div>
            </div>
            
            <?php if ($result['feedback']): ?>
              <div class="feedback-section">
                <h4>Teacher Feedback:</h4>
                <p><?php echo nl2br(htmlspecialchars($result['feedback'])); ?></p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Question Details -->
      <div class="content-card">
        <div class="card-header"><h3>Question Details</h3></div>
        <div class="card-content">
          <?php if (empty($questions)): ?>
            <div class="empty-state">No questions found for this exam.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Question</th>
                    <th>Your Answer</th>
                    <th>Correct Answer</th>
                    <th>Marks</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($questions as $i => $q): ?>
                    <?php
                      $qid = (int)$q['question_id'];
                      $ans = $answersByQ[$qid] ?? null;
                      $correctRaw = $ans['correct_answer'] ?? $q['answer'] ?? '';
                      $selectedRaw = $ans['student_answer'] ?? '';
                      $isCorrect = false;
                      
                      if ($ans) {
                        if ((int)($q['type'] ?? 1) === 1 && !empty($q['option1'])) {
                          // Multiple choice - compare option index
                          $isCorrect = ($selectedRaw === $correctRaw);
                        } else {
                          // Text answer - compare content
                          $isCorrect = strtolower(trim($selectedRaw)) === strtolower(trim($correctRaw));
                        }
                      }
                      
                      // Map display values for multiple choice
                      $displaySel = $selectedRaw;
                      $displayCorr = $correctRaw;
                      if ((int)($q['type'] ?? 1) === 1 && !empty($q['option1'])) {
                        $sidx = ctype_digit($selectedRaw) ? (int)$selectedRaw : null;
                        $cidx = (int)$correctRaw;
                        if ($sidx !== null && isset($q['option'.$sidx]) && $q['option'.$sidx] !== '') { 
                          $displaySel = $q['option'.$sidx]; 
                        }
                        if (isset($q['option'.$cidx]) && $q['option'.$cidx] !== '') { 
                          $displayCorr = $q['option'.$cidx]; 
                        }
                      }
                    ?>
                    <tr>
                      <td><?php echo $i + 1; ?></td>
                      <td><?php echo nl2br(htmlspecialchars($q['question'] ?? '')); ?></td>
                      <td><?php echo htmlspecialchars($displaySel ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($displayCorr ?? ''); ?></td>
                      <td><?php echo (int)($q['marks'] ?? 0); ?></td>
                      <td>
                        <?php if ($ans): ?>
                          <span class="badge <?php echo $isCorrect ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $isCorrect ? 'Correct' : 'Incorrect'; ?>
                          </span>
                        <?php else: ?>
                          <span class="badge">No Answer</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<style>
.info-grid, .result-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.info-item, .result-item {
  display: flex;
  flex-direction: column;
}

.info-item label, .result-item label {
  font-weight: 600;
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 5px;
}

.info-item span, .result-item span {
  font-size: 16px;
  color: #111827;
}

.result-value {
  font-weight: 600;
}

.grade-a { color: #059669; }
.grade-b { color: #d97706; }
.grade-c { color: #dc2626; }
.grade-d { color: #dc2626; }
.grade-f { color: #dc2626; }

.status-pending { color: #d97706; }
.status-graded { color: #059669; }
.status-published { color: #2563eb; }

.feedback-section {
  margin-top: 20px;
  padding: 15px;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
}

.feedback-section h4 {
  margin: 0 0 10px 0;
  color: #374151;
}

.feedback-section p {
  margin: 0;
  color: #6b7280;
  line-height: 1.5;
}
</style>

<?php include '../components/footer.php'; ?>
