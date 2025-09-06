<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

function canManage(){ return isSuperAdmin() || (($_SESSION['role'] ?? '') === 'teacher'); }

$testId = (int)($_GET['test_id'] ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);
if ($testId <= 0 || $studentId <= 0) { redirect('./tests.php'); }

// Load test
$st = $pdo->prepare("SELECT t.*, c.class_name,c.class_code, s.title AS subject_name,s.subject_code, u.first_name,u.last_name,u.username
                     FROM tests t
                     LEFT JOIN classes c ON c.id=t.class_id
                     LEFT JOIN subjects s ON s.subject_id=t.subject_id
                     LEFT JOIN users u ON u.id=t.teacher_id
                     WHERE t.id=?");
$st->execute([$testId]);
$test = $st->fetch(PDO::FETCH_ASSOC);
if (!$test) { redirect('./tests.php'); }

// Load student user (may be missing)
$stu = null;
try {
  $s = $pdo->prepare('SELECT id, first_name, last_name, username FROM users WHERE id = ?');
  $s->execute([$studentId]);
  $stu = $s->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) { $stu = null; }

// Load result summary (if exists)
$res = null;
try {
  $sr = $pdo->prepare('SELECT * FROM test_results WHERE test_id = ? AND student_id = ? ORDER BY submitted_at DESC LIMIT 1');
  $sr->execute([$testId, $studentId]);
  $res = $sr->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) { $res = null; }

$resultId = (int)($res['id'] ?? 0);

// Load questions
$qStmt = $pdo->prepare('SELECT * FROM test_questions WHERE test_id = ? ORDER BY id ASC');
$qStmt->execute([$testId]);
$questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

// Load answers. Prefer linking by test_result_id if available; fallback to test_id+student_id
$answersByQ = [];
try {
  if ($resultId > 0) {
    $a = $pdo->prepare('SELECT * FROM test_answers WHERE test_result_id = ?');
    $a->execute([$resultId]);
  } else {
    $a = $pdo->prepare('SELECT * FROM test_answers WHERE test_id = ? AND student_id = ?');
    $a->execute([$testId, $studentId]);
  }
  $ans = $a->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($ans as $row) { $answersByQ[(int)($row['question_id'] ?? 0)] = $row; }
} catch (Throwable $e) { $answersByQ = []; }

$page_title = 'Student Test Result';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Result: <?php echo htmlspecialchars($test['title'] ?? 'Test'); ?></h1>
        <div class="muted">
          Class: <?php echo htmlspecialchars(($test['class_name']??'-').' #'.($test['class_code']??'-')); ?> ·
          Subject: <?php echo htmlspecialchars(($test['subject_name']??'-').' #'.($test['subject_code']??'-')); ?>
        </div>
      </div>
      <div class="header-actions">
        <a class="btn btn-secondary" href="./test_submissions.php?id=<?php echo (int)$testId; ?>"><i class="fas fa-arrow-left"></i> Back to Submissions</a>
      </div>
    </div>

    <div class="card-content">
      <div class="summary" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <?php $studentName = $stu ? trim(($stu['first_name']??'').' '.($stu['last_name']??'')) : ''; if ($studentName==='') { $studentName = $stu['username'] ?? ('Student #'.$studentId); } ?>
        <div class="badge">Student: <strong><?php echo htmlspecialchars($studentName); ?></strong></div>
        <?php if ($res): ?>
          <div class="badge">Obtained: <strong><?php echo (int)$res['obtained_marks']; ?></strong> / <?php echo (int)$res['total_marks']; ?></div>
          <div class="badge">%: <strong><?php echo htmlspecialchars(number_format((float)$res['percentage'],2)); ?></strong></div>
          <div class="badge">Grade: <strong><?php echo htmlspecialchars($res['grade'] ?? '-'); ?></strong></div>
          <div class="badge">Submitted: <strong><?php echo htmlspecialchars($res['submitted_at'] ?? '-'); ?></strong></div>
        <?php else: ?>
          <div class="badge">No aggregated result found; showing per-question answers if available.</div>
        <?php endif; ?>
      </div>

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
            <?php if ($questions): foreach ($questions as $i=>$q):
              $qid = (int)$q['id'];
              $ans = $answersByQ[$qid] ?? null;
              $qType = $q['question_type'] ?? '';
              $correctRaw = $ans['correct_answer'] ?? $q['correct_answer'] ?? '';
              $selectedRaw = $ans['selected_answer'] ?? '';
              $isCorrect = (int)($ans['is_correct'] ?? 0) === 1;
              // Normalize multiple answers for display
              $correctDisp = $correctRaw;
              $selectedDisp = $selectedRaw;
              if ($qType === 'objective_multiple') {
                $cr = json_decode((string)$correctRaw, true); if (is_array($cr)) { $correctDisp = implode(', ', $cr); }
                $sr = json_decode((string)$selectedRaw, true); if (is_array($sr)) { $selectedDisp = implode(', ', $sr); }
              }
            ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><?php echo nl2br(htmlspecialchars($q['question_text'] ?? '')); ?></td>
              <td><?php echo htmlspecialchars($selectedDisp ?? ''); ?></td>
              <td><?php echo htmlspecialchars($correctDisp ?? ''); ?></td>
              <td><?php echo (int)($q['marks'] ?? 0); ?></td>
              <td>
                <?php if ($ans): ?>
                  <span class="badge <?php echo $isCorrect ? 'badge-success' : 'badge-danger'; ?>"><?php echo $isCorrect ? 'Correct' : 'Incorrect'; ?></span>
                <?php else: ?>
                  <span class="badge">No Answer</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="muted">No questions found for this test.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
