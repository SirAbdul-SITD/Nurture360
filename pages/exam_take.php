<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isStudent()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$studentId = getCurrentUserId();

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0; // references template exam in exam_assessments where student_id=0
if ($examId <= 0) { redirect('../dashboard/index.php'); }

// Load exam template
$exam = $pdo->prepare('SELECT e.*, c.class_name, s.subject_name FROM exam_assessments e LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN subjects s ON s.id=e.subject_id WHERE e.exam_assessment_id=? AND e.student_id=0');
$exam->execute([$examId]);
$E = $exam->fetch();
if (!$E) { redirect('../dashboard/index.php'); }

// One attempt enforcement: if any data exists for this exam and student, block retake
$hasAttemptStmt = $pdo->prepare('SELECT 1 FROM exam_assessments_data WHERE exam_assessment_id=? AND student_id=? LIMIT 1');
$hasAttemptStmt->execute([$examId,$studentId]);
$hasAttempt = (bool)$hasAttemptStmt->fetchColumn();

// Parse config
$cfg = [ 'dur' => 0, 'rv' => 'immediate' ];
if (!empty($E['status'])) {
  foreach (explode(';', (string)$E['status']) as $part) {
    if (strpos($part,'=')!==false) { [$k,$v] = explode('=', $part, 2); $cfg[$k] = $v; }
  }
}
$durationMin = (int)($cfg['dur'] ?? 0);
$rv = (string)($cfg['rv'] ?? 'immediate');
$startAt = strtotime($E['timespan']);
$endAt = $durationMin>0 ? ($startAt + $durationMin*60) : null;
$now = time();
$open = $now >= $startAt && ($endAt===null || $now <= $endAt);

$message = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
$csrf = generateCSRFToken();

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { throw new Exception('Invalid CSRF'); }
    if ($hasAttempt) { throw new Exception('You have already attempted this exam.'); }
    if (!$open) { throw new Exception('Exam is not open.'); }

    // Fetch questions for subject
    $qStmt = $pdo->prepare('SELECT * FROM exam_questions WHERE subject_id=? ORDER BY question_id');
    $qStmt->execute([(int)$E['subject_id']]);
    $questions = $qStmt->fetchAll();
    if (!$questions) { throw new Exception('No questions available for this subject.'); }

    // Start transaction
    $pdo->beginTransaction();

      try {
        // Insert exam assessment for this student
        $insAssessment = $pdo->prepare('INSERT INTO exam_assessments (student_id, subject_id, class_id, type, status, timespan) VALUES (?, ?, ?, ?, ?, ?)');
        $insAssessment->execute([$studentId, (int)$E['subject_id'], (int)$E['class_id'], $E['type'], $E['status'], $E['timespan']]);
        $studentAssessmentId = $pdo->lastInsertId();
        
        // Debug logging
        error_log("Created student assessment ID: " . $studentAssessmentId . " for student: " . $studentId . " in exam: " . $examId);

      // Insert question responses
      $ins = $pdo->prepare('INSERT INTO exam_assessments_data (exam_assessment_id, student_id, question_id, question, correct_answer, student_answer) VALUES (?, ?, ?, ?, ?, ?)');
      
      $totalMarks = 0;
      $obtainedMarks = 0;
      $autoGradable = true;

      foreach ($questions as $Q) {
        $qid = (int)$Q['question_id'];
        $field = 'q_'.$qid;
        $ans = isset($_POST[$field]) ? trim((string)$_POST[$field]) : null;
        $marks = (float)($Q['marks'] ?? 0);
        
        $totalMarks += $marks;
        
        // Auto-grade if possible
        if ($E['type'] === 'auto') {
          $isCorrect = false;
          $questionType = (int)($Q['type'] ?? 1);
          
          if ($questionType === 1 && !empty($Q['option1'])) {
            // Multiple choice - compare option index
            $isCorrect = ($ans === $Q['answer']);
          } else {
            // Text answer - compare content (case-insensitive)
            $isCorrect = strtolower(trim($ans ?? '')) === strtolower(trim($Q['answer'] ?? ''));
          }
          
          if ($isCorrect) {
            $obtainedMarks += $marks;
          }
        } else {
          // Manual grading - mark as not auto-gradable
          $autoGradable = false;
        }
        
        $ins->execute([$studentAssessmentId, $studentId, $qid, (string)$Q['question'], (string)$Q['answer'], $ans]);
      }

      // Calculate percentage and grade
      $percentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0;
      $grade = '';
      if ($percentage >= 70) $grade = 'A';
      elseif ($percentage >= 60) $grade = 'B';
      elseif ($percentage >= 50) $grade = 'C';
      elseif ($percentage >= 45) $grade = 'D';
      else $grade = 'F';

      // Insert result into exam_results table
      $insResult = $pdo->prepare('INSERT INTO exam_results (exam_assessment_id, student_id, subject_id, class_id, obtained_marks, total_marks, percentage, grade, grading_type, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
      $gradingType = $E['type'] === 'auto' ? 'auto' : 'manual';
      $status = $E['type'] === 'auto' ? 'graded' : 'pending';
      
      $insResult->execute([
        $studentAssessmentId,
        $studentId,
        (int)$E['subject_id'],
        (int)$E['class_id'],
        $obtainedMarks,
        $totalMarks,
        $percentage,
        $grade,
        $gradingType,
        $status
      ]);
      
      // Debug logging
      error_log("Inserted exam result - Assessment ID: " . $studentAssessmentId . ", Student: " . $studentId . ", Marks: " . $obtainedMarks . "/" . $totalMarks . ", Grade: " . $grade . ", Status: " . $status);

      $pdo->commit();
      $_SESSION['flash_success'] = 'Exam submitted successfully!';
      header('Location: '.$_SERVER['REQUEST_URI']);
      exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      throw $e;
    }
  }
} catch (Throwable $e) {
  $_SESSION['flash_error'] = $e->getMessage();
  header('Location: '.$_SERVER['REQUEST_URI']);
  exit;
}

// If attempted, load result from exam_results table
$showResults = false; $score = 0.0; $total = 0.0; $details = []; $resultData = null;
if ($hasAttempt) {
  // Get student's assessment ID
  $studentAssessmentStmt = $pdo->prepare('SELECT exam_assessment_id FROM exam_assessments WHERE student_id=? AND subject_id=? AND class_id=? AND timespan=? LIMIT 1');
  $studentAssessmentStmt->execute([$studentId, (int)$E['subject_id'], (int)$E['class_id'], $E['timespan']]);
  $studentAssessment = $studentAssessmentStmt->fetch();
  
  if ($studentAssessment) {
    $studentAssessmentId = (int)$studentAssessment['exam_assessment_id'];
    
    // Load result from exam_results table
    $resultStmt = $pdo->prepare('SELECT * FROM exam_results WHERE exam_assessment_id=? AND student_id=? LIMIT 1');
    $resultStmt->execute([$studentAssessmentId, $studentId]);
    $resultData = $resultStmt->fetch();
    
    if ($resultData) {
      $score = (float)($resultData['obtained_marks'] ?? 0);
      $total = (float)($resultData['total_marks'] ?? 0);
      
      // Visibility policy
      $showResults = ($rv==='immediate') || 
                     ($rv==='2h' && $now >= $startAt + 7200) || 
                     ($rv==='1d' && $now >= $startAt + 86400) || 
                     ($rv==='at_close' && ($endAt!==null && $now>$endAt));
      
      // For auto-graded exams, always show results if visibility allows
      if ($showResults && $E['type'] === 'auto') {
        // Load question details for display
        $dStmt = $pdo->prepare('SELECT d.*, q.marks FROM exam_assessments_data d LEFT JOIN exam_questions q ON q.question_id=d.question_id WHERE d.exam_assessment_id=? AND d.student_id=?');
        $dStmt->execute([$studentAssessmentId, $studentId]);
        while ($row = $dStmt->fetch()) {
          $m = (float)($row['marks'] ?? 0);
          $isCorrect = strtolower(trim((string)$row['student_answer'])) === strtolower(trim((string)$row['correct_answer']));
          $details[] = $row + ['marks'=>$m, 'correct'=>$isCorrect];
        }
      }
    }
  }
}

$page_title = 'Take Exam';
$current_page = 'exams';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Exam: <?php echo htmlspecialchars($E['subject_name'] ?? 'Subject'); ?></h1>
        <div class="muted">Starts: <?php echo htmlspecialchars($E['timespan']); ?><?php if ($durationMin>0): ?> · Duration: <?php echo (int)$durationMin; ?> min<?php endif; ?></div>
      </div>
    </div>

    <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if ($hasAttempt): ?>
      <div class="content-card">
        <div class="card-header"><h3>Your Attempt</h3></div>
        <div class="card-content">
          <p>You have already submitted this exam.</p>
          
          <?php if ($showResults && $resultData): ?>
            <?php if ($E['type'] === 'auto'): ?>
              <div class="result-summary">
                <h4>Your Results:</h4>
                <p><strong>Score:</strong> <?php echo $score; ?> / <?php echo $total; ?> (<?php echo htmlspecialchars($resultData['percentage']); ?>%)</p>
                <p><strong>Grade:</strong> <?php echo htmlspecialchars($resultData['grade']); ?></p>
                <p><strong>Status:</strong> <span class="badge badge-success">Graded</span></p>
              </div>
              
              <?php if (!empty($details)): ?>
                <div class="question-details" style="margin-top: 20px;">
                  <h5>Question Details:</h5>
                  <div class="table-responsive">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Question</th>
                          <th>Your Answer</th>
                          <th>Correct Answer</th>
                          <th>Marks</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($details as $detail): ?>
                          <tr>
                            <td><?php echo nl2br(htmlspecialchars($detail['question'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($detail['student_answer'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($detail['correct_answer'] ?? ''); ?></td>
                            <td><?php echo (int)($detail['marks'] ?? 0); ?></td>
                            <td>
                              <span class="badge <?php echo $detail['correct'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $detail['correct'] ? 'Correct' : 'Incorrect'; ?>
                              </span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div class="result-summary">
                <h4>Exam Status:</h4>
                <p><strong>Status:</strong> <span class="badge badge-warning">Pending Manual Grading</span></p>
                <p>Your exam has been submitted and is awaiting manual grading by your teacher. Results will be available once grading is complete.</p>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <p>Results will be available later based on the exam's visibility settings.</p>
            <?php if ($rv === '2h'): ?>
              <p><em>Results will be visible 2 hours after the exam starts.</em></p>
            <?php elseif ($rv === '1d'): ?>
              <p><em>Results will be visible 1 day after the exam starts.</em></p>
            <?php elseif ($rv === 'at_close'): ?>
              <p><em>Results will be visible after the exam closes.</em></p>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <?php if (!$open): ?>
        <div class="content-card"><div class="card-content">This exam is not currently open.</div></div>
      <?php else: ?>
        <?php
          $qStmt = $pdo->prepare('SELECT * FROM exam_questions WHERE subject_id=? ORDER BY question_id');
          $qStmt->execute([(int)$E['subject_id']]);
          $questions = $qStmt->fetchAll();
        ?>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>"/>
          <div class="content-card">
            <div class="card-header"><h3>Questions (<?php echo count($questions); ?>)</h3></div>
            <div class="card-content">
              <?php if (!$questions): ?>
                <div class="empty-state">No questions to display.</div>
              <?php else: ?>
                <?php foreach ($questions as $idx=>$Q): ?>
                  <div class="form-group" style="margin-bottom:14px;">
                    <label><strong>Q<?php echo $idx+1; ?>.</strong> <?php echo nl2br(htmlspecialchars($Q['question'])); ?></label>
                    <?php
                      $qid = (int)$Q['question_id'];
                      $name = 'q_'.$qid;
                      $type = (int)$Q['type'];
                      $options = [];
                      for ($i=1;$i<=5;$i++) { $opt=$Q['option'.$i]; if ($opt!==null && $opt!=='') { $options[] = ['key'=>(string)$i, 'text'=>$opt]; } }
                    ?>
                    <?php if ($type === 1 && $options): ?>
                      <?php foreach ($options as $opt): ?>
                        <div><label><input type="radio" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars($opt['key']); ?>"> <?php echo htmlspecialchars($opt['text']); ?></label></div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <textarea name="<?php echo $name; ?>" rows="3" style="width:100%;"></textarea>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="action-buttons" style="margin-top:10px;">
            <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Submit</button>
          </div>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</div>
<?php include '../components/footer.php'; ?>
