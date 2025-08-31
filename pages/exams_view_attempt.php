<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$userId = getCurrentUserId();
$isAdmin = function_exists('isSuperAdmin') ? isSuperAdmin() : false;
$isTeacherRole = function_exists('isTeacher') ? isTeacher() : false;

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($examId <= 0 || $studentId <= 0) { redirect('./exams.php'); }

// Load exam template. If a student-specific ID was passed, resolve to template by class/subject/timespan
$E = null; $templateId = $examId;
try {
  $examStmt = $pdo->prepare('SELECT e.*, c.class_name,c.class_code, s.subject_name,s.subject_code FROM exam_assessments e LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN subjects s ON s.id=e.subject_id WHERE e.exam_assessment_id=? AND e.student_id=0');
  $examStmt->execute([$examId]);
  $E = $examStmt->fetch(PDO::FETCH_ASSOC) ?: null;
  if (!$E) {
    // examId could be student-specific; fetch it and map to template
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

// Permission: admin or teacher assigned to class+subject
if (!$isAdmin) {
  if (!$isTeacherRole) { redirect('../dashboard/index.php'); }
  $has = $pdo->prepare('SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1');
  $has->execute([$userId,(int)$E['class_id'],(int)$E['subject_id']]);
  if (!$has->fetchColumn()) { redirect('../dashboard/index.php'); }
}

// Load student info
$uStmt = $pdo->prepare('SELECT id, first_name, last_name, username, email FROM users WHERE id=?');
$uStmt->execute([$studentId]);
$U = $uStmt->fetch();
if (!$U) { redirect('./exams_results.php?exam_id='.$examId); }

// Determine all relevant assessment IDs for this student (template + per-student matching class/subject/timespan)
$ids = [$templateId];
try {
  $ast = $pdo->prepare('SELECT exam_assessment_id FROM exam_assessments WHERE class_id=? AND subject_id=? AND student_id=? AND timespan=?');
  $ast->execute([(int)$E['class_id'], (int)$E['subject_id'], (int)$studentId, (string)$E['timespan']]);
  while ($r = $ast->fetch(PDO::FETCH_ASSOC)) { $ids[] = (int)$r['exam_assessment_id']; }
} catch (Throwable $e) {}
$ids = array_values(array_unique(array_filter($ids)));

// Fetch answers joined with questions across all relevant assessment IDs
$rows = [];
if (!empty($ids)) {
  $ph = implode(',', array_fill(0, count($ids), '?'));
  $sql = 'SELECT 
            d.question_id,
            COALESCE(q.question, d.question) AS question_text,
            d.correct_answer,
            d.student_answer,
            q.marks,
            q.type,
            q.option1, q.option2, q.option3, q.option4, q.option5,
            q.answer AS reference_answer
          FROM exam_assessments_data d
          LEFT JOIN exam_questions q ON q.question_id = d.question_id
          WHERE d.exam_assessment_id IN ('.$ph.') AND d.student_id=?
          ORDER BY d.question_id';
  $params = $ids; $params[] = $studentId;
  $dStmt = $pdo->prepare($sql);
  $dStmt->execute($params);
  $rows = $dStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Helpers replicated from students/exam-result.php
if (!function_exists('n360_text_similarity')) {
  function n360_text_similarity(string $a, string $b): float {
    $normalize = function(string $s): string {
      $s = strtolower($s);
      $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
      $s = preg_replace('/\s+/', ' ', trim($s));
      return $s;
    };
    $a = $normalize($a); $b = $normalize($b);
    if ($a === '' || $b === '') return 0.0;
    $aw = array_values(array_filter(explode(' ', $a)));
    $bw = array_values(array_filter(explode(' ', $b)));
    $setA = array_unique($aw); $setB = array_unique($bw);
    $intersect = array_intersect($setA, $setB);
    $union = array_unique(array_merge($setA, $setB));
    $jaccard = count($union) ? (count($intersect) / count($union)) : 0.0;
    $maxLen = max(strlen($a), strlen($b));
    if ($maxLen === 0) return $jaccard;
    $lev = levenshtein($a, $b);
    $levRatio = 1.0 - ($lev / $maxLen);
    return max(0.0, min(1.0, 0.6*$jaccard + 0.4*$levRatio));
  }
}
if (!function_exists('n360_best_similarity')) {
  function n360_best_similarity(string $text, array $refs): float {
    $best = 0.0; foreach ($refs as $r) { $score = n360_text_similarity($text, $r); if ($score > $best) $best = $score; } return $best;
  }
}
if (!function_exists('n360_split_variants')) {
  function n360_split_variants(string $s): array {
    $parts = preg_split('/,|\/|;|\bor\b/i', $s); $out = [];
    foreach ($parts as $p) { $p = trim($p); if ($p !== '') $out[] = $p; }
    return $out ?: [trim($s)];
  }
}

$totalMarks = 0.0; $earned = 0.0; $items = [];
foreach ($rows as $r) {
  $m = (float)($r['marks'] ?? 0);
  $totalMarks += $m;
  $studentRaw = (string)($r['student_answer'] ?? '');
  $correctRaw = (string)($r['correct_answer'] ?? '');
  $refBase = $correctRaw !== '' ? $correctRaw : (string)($r['reference_answer'] ?? '');

  $opts = [
    1 => (string)($r['option1'] ?? ''),
    2 => (string)($r['option2'] ?? ''),
    3 => (string)($r['option3'] ?? ''),
    4 => (string)($r['option4'] ?? ''),
    5 => (string)($r['option5'] ?? ''),
  ];
  $hasOptions = ($opts[1] !== '' || $opts[2] !== '' || $opts[3] !== '' || $opts[4] !== '' || $opts[5] !== '');
  $isMCQ = $hasOptions && $correctRaw !== '' && ctype_digit((string)$correctRaw);

  $displaySel = $studentRaw; $displayCorr = $correctRaw;
  if ($isMCQ) {
    $sidx = ctype_digit($studentRaw) ? (int)$studentRaw : null;
    $cidx = (int)$correctRaw;
    if ($sidx !== null && isset($opts[$sidx]) && $opts[$sidx] !== '') { $displaySel = $opts[$sidx]; }
    if (isset($opts[$cidx]) && $opts[$cidx] !== '') { $displayCorr = $opts[$cidx]; }
  }

  $isCorrect = false; $isPartial = false; $similarity = null;
  if ($isMCQ) {
    $isCorrect = ($studentRaw !== '' && (int)$studentRaw === (int)$correctRaw);
  } else {
    if ($refBase !== '') {
      $variants = n360_split_variants($refBase);
      $similarity = n360_best_similarity($studentRaw, $variants);
      if ($similarity >= 0.8) { $isCorrect = true; }
      elseif ($similarity >= 0.5) { $isPartial = true; }
    }
  }

  if (strtolower((string)($E['type'] ?? 'auto')) === 'auto') {
    if ($isCorrect) { $earned += $m; }
    // Optionally partial credit: if desired uncomment next line
    // elseif ($isPartial) { $earned += $m * 0.5; }
  }

  $items[] = [
    'qid' => (int)$r['question_id'],
    'question' => (string)($r['question_text'] ?? ''),
    'student' => $displaySel,
    'correct' => $displayCorr,
    'marks' => $m,
    'is_correct' => $isCorrect,
    'is_partial' => $isPartial,
    'similarity' => $similarity,
    'is_mcq' => $isMCQ,
    'ref_base' => $refBase,
  ];
}

$page_title = 'View Attempt';
$current_page = 'exams';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Attempt · Exam #<?php echo (int)$E['exam_assessment_id']; ?> · <?php echo htmlspecialchars($E['subject_name'] ?? ''); ?></h1>
        <div class="muted">Class: <?php echo htmlspecialchars(($E['class_name']??'').' #'.($E['class_code']??'')); ?> · Student: <?php $nm = trim(($U['first_name']??'').' '.($U['last_name']??'')); if($nm===''){ $nm = $U['username']??('Student #'.$U['id']); } echo htmlspecialchars($nm); ?></div>
      </div>
      <div>
        <a class="btn btn-secondary" href="./exams_results.php?exam_id=<?php echo (int)$templateId; ?>"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="content-card">
      <div class="card-header">
        <h3>Answers (<?php echo count($items); ?>)</h3>
        <?php if (strtolower((string)($E['type'] ?? 'auto')) === 'auto'): ?>
          <div class="muted">Score: <?php echo $earned; ?> / <?php echo $totalMarks; ?></div>
        <?php else: ?>
          <div class="muted">Manual grading</div>
        <?php endif; ?>
      </div>
      <div class="card-content">
        <?php if (isset($_GET['debug'])): ?>
          <div class="alert info" style="white-space:pre-wrap;">
            <strong>Debug</strong>
            <?php echo htmlspecialchars(json_encode([
              'template_id' => $templateId,
              'queried_ids' => $ids,
              'student_id' => $studentId,
              'rows' => count($rows),
            ], JSON_PRETTY_PRINT)); ?>
          </div>
        <?php endif; ?>
        <?php if (!$items): ?>
          <div class="empty-state">No answers recorded for this attempt.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Question</th>
                  <th>Student Answer</th>
                  <th>Correct Answer</th>
                  <th>Marks</th>
                  <?php if (strtolower((string)($E['type'] ?? 'auto')) === 'auto'): ?><th>Status</th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($items as $idx=>$it): ?>
                <tr>
                  <td><?php echo $idx+1; ?></td>
                  <td><?php echo nl2br(htmlspecialchars($it['question'])); ?></td>
                  <td><?php echo nl2br(htmlspecialchars($it['student'])); ?></td>
                  <td><?php echo nl2br(htmlspecialchars($it['correct'])); ?></td>
                  <td><?php echo $it['marks']; ?></td>
                  <?php if (strtolower((string)($E['type'] ?? 'auto')) === 'auto'): ?>
                    <td>
                      <?php if ($it['is_mcq']): ?>
                        <span class="badge <?php echo $it['is_correct'] ? 'success' : 'error'; ?>"><?php echo $it['is_correct'] ? 'Correct' : 'Incorrect'; ?></span>
                      <?php else: ?>
                        <?php if ($it['is_correct']): ?>
                          <span class="badge success">Correct</span>
                        <?php elseif ($it['is_partial']): ?>
                          <span class="badge warning">Partially Correct<?php echo $it['similarity']!==null ? ' ('.htmlspecialchars(number_format($it['similarity']*100,0)).'%)' : ''; ?></span>
                        <?php else: ?>
                          <span class="badge error">Incorrect</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
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
