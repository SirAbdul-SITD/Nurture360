<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$role = $_SESSION['role'] ?? '';
$userId = getCurrentUserId();
$pdo = getDBConnection();

$testId = (int)($_GET['id'] ?? 0);
if ($testId <= 0) { redirect('./tests.php'); }

// Load test
$st = $pdo->prepare("SELECT t.*, c.class_name,c.class_code, s.subject_name,s.subject_code FROM tests t LEFT JOIN classes c ON c.id=t.class_id LEFT JOIN subjects s ON s.id=t.subject_id WHERE t.id=?");
$st->execute([$testId]);
$test = $st->fetch();
if (!$test) { redirect('./tests.php'); }

// Load questions
$qst = $pdo->prepare("SELECT * FROM test_questions WHERE test_id=? ORDER BY id ASC");
$qst->execute([$testId]);
$questions = $qst->fetchAll();

// Enforce schedule window
$scheduled_date = $test['scheduled_date'] ?? '';
$start_time = $test['start_time'] ?? '';
$end_time = $test['end_time'] ?? '';
$startTs = @strtotime($scheduled_date . ' ' . $start_time);
$endTs = @strtotime($scheduled_date . ' ' . $end_time);
$now = time();
$windowStatus = 'upcoming';
if ($startTs && $endTs) {
  if ($now < $startTs) $windowStatus = 'upcoming';
  elseif ($now > $endTs) $windowStatus = 'ended';
  else $windowStatus = 'live';
}

// Enforce one attempt per student
$resChk = $pdo->prepare("SELECT id, obtained_marks, total_marks, submitted_at FROM test_results WHERE test_id=? AND student_id=? LIMIT 1");
$resChk->execute([$testId, $userId]);
$existingResult = $resChk->fetch();

// Track duration per attempt via session
$sessionKey = 'test_start_' . $testId;
if (!isset($_SESSION[$sessionKey]) && $windowStatus==='live' && $role==='student' && !$existingResult) {
  $_SESSION[$sessionKey] = time();
}
$startedAt = $_SESSION[$sessionKey] ?? null;
$duration = (int)$test['duration_minutes'];
$deadlineTs = $startedAt ? ($startedAt + $duration*60) : null;
$timeLeft = $deadlineTs ? max(0, $deadlineTs - $now) : null;
$durationExpired = $deadlineTs && $now > $deadlineTs;

$message=''; $error='';

// Handle submission
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_test'])) {
  if (!validateCSRFToken($_POST['csrf_token']??'')) { $error='Invalid CSRF token'; }
  elseif ($role!=='student') { $error='Only students can submit tests.'; }
  elseif ($existingResult) { $error='You have already submitted this test.'; }
  elseif ($windowStatus!=='live') { $error='The test is not open for submission.'; }
  elseif (!$startedAt) { $error='No active attempt found.'; }
  else {
    // Auto grade supported types
    $answers = $_POST['answers'] ?? [];
    $obtained = 0; $total = 0;
    $insertAns = $pdo->prepare("INSERT INTO test_answers (test_id, question_id, student_id, answer_text, is_auto_graded, marks_awarded) VALUES (?,?,?,?,?,?)");
    foreach ($questions as $q) {
      $qid = (int)$q['id'];
      $qtype = $q['question_type'];
      $marks = (int)$q['marks'];
      $total += $marks;
      $rawAns = $answers[$qid] ?? null;
      $ans = '';
      if (is_array($rawAns)) {
        // For objective_multiple we get array of selections
        $ans = json_encode(array_values(array_filter(array_map('strval',$rawAns), fn($v)=>trim($v)!=='')) , JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
      } else {
        $ans = trim((string)$rawAns);
      }
      $awarded = 0; $auto = 0;
      if (in_array($qtype, ['objective_single','objective_multiple','true_false'])) {
        $auto = 1;
        // Normalize compare
        if ($qtype==='true_false') {
          $correct = trim((string)$q['correct_answer']);
          $map = ['true'=>'true','false'=>'false','1'=>'true','0'=>'false','yes'=>'true','no'=>'false'];
          $ans = strtolower($ans);
          $ans = $map[$ans] ?? $ans;
          $correct = strtolower($correct);
          $correct = $map[$correct] ?? $correct;
          if ($ans !== '' && $correct !== '' && strcasecmp($ans, $correct)===0) { $awarded = $marks; }
        } elseif ($qtype==='objective_single') {
          $correct = trim((string)$q['correct_answer']);
          if ($ans !== '' && $correct !== '' && strcmp($ans, $correct)===0) { $awarded = $marks; }
        } elseif ($qtype==='objective_multiple') {
          // Compare sets
          $correctArr = json_decode((string)$q['correct_answer'], true);
          $ansArr = [];
          if ($ans!=='') { $tmp=json_decode($ans,true); if(is_array($tmp)) { $ansArr=$tmp; } }
          if (!is_array($correctArr)) { $correctArr = []; }
          // Normalize trim
          $normalize = function($arr){ $out=[]; foreach($arr as $v){ $t=trim((string)$v); if($t!=='') $out[]=$t; } sort($out); return $out; };
          $a1 = $normalize($ansArr); $a2 = $normalize($correctArr);
          if ($a1 === $a2 && count($a2)>0) { $awarded = $marks; }
        }
      } else {
        // short_answer/essay require manual grading later
        $auto = 0;
      }
      $obtained += $awarded;
      $insertAns->execute([$testId, $qid, $userId, $ans, $auto, $awarded]);
    }
    $percentage = $total>0 ? round(($obtained/$total)*100,2) : 0.00;
    // Simple grade mapping
    $grade = ($percentage>=70?'A':($percentage>=60?'B':($percentage>=50?'C':($percentage>=45?'D':'F'))));
    $insRes = $pdo->prepare("INSERT INTO test_results (test_id, student_id, obtained_marks, total_marks, percentage, grade, graded_by, graded_at, feedback) VALUES (?,?,?,?,?,?,?,?,?)");
    $insRes->execute([$testId, $userId, $obtained, $total, $percentage, $grade, null, null, null]);
    unset($_SESSION[$sessionKey]);
    $message = 'Test submitted. Score: '.$obtained.' / '.$total.' ('.$percentage.'%)';
    // Reload existing result
    $resChk->execute([$testId, $userId]);
    $existingResult = $resChk->fetch();
  }
}

$page_title = 'Take Test';
include '../components/header.php';
?>
<style>
.timer {font-weight:600;color:#1f2937;background:#fff7ed;border:1px solid #fed7aa;padding:6px 10px;border-radius:8px}
.question {border:1px solid #e5e7eb;border-radius:10px;background:#fff;padding:12px;margin-bottom:12px}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars($test['title']); ?></h1>
        <div class="muted">Class: <?php echo htmlspecialchars(($test['class_name']??'-').' #'.($test['class_code']??'-')); ?> · Subject: <?php echo htmlspecialchars(($test['subject_name']??'-').' #'.($test['subject_code']??'-')); ?> · Schedule: <?php echo htmlspecialchars(($test['scheduled_date']??'').' '.($test['start_time']??'').' - '.($test['end_time']??'')); ?></div>
      </div>
      <div class="header-actions">
        <a class="btn btn-secondary" href="./tests.php"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>

    <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if($existingResult): ?>
      <div class="content-card"><div class="content-card-body">
        <p>You already submitted this test on <?php echo htmlspecialchars($existingResult['submitted_at']); ?>.</p>
        <p>Score: <?php echo (int)$existingResult['obtained_marks']; ?> / <?php echo (int)$existingResult['total_marks']; ?></p>
      </div></div>
    <?php elseif($role!=='student'): ?>
      <div class="alert alert-info">Only students can take tests. You can preview questions below.</div>
    <?php elseif($windowStatus==='upcoming'): ?>
      <div class="alert alert-warning">This test has not started yet.</div>
    <?php elseif($windowStatus==='ended'): ?>
      <div class="alert alert-error">This test has ended.</div>
    <?php else: ?>
      <div class="content-card"><div class="content-card-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <div><strong>Duration:</strong> <?php echo (int)$test['duration_minutes']; ?> minutes</div>
          <?php if($timeLeft!==null): ?><div class="timer">Time left: <span id="timeLeft" data-seconds="<?php echo (int)$timeLeft; ?>"></span></div><?php endif; ?>
        </div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
          <?php foreach($questions as $index=>$q): $qid=(int)$q['id']; $opts=$q['options']?json_decode($q['options'],true):null; ?>
            <div class="question">
              <div><strong>Q<?php echo $index+1; ?> (<?php echo (int)$q['marks']; ?> mark<?php echo $q['marks']>1?'s':''; ?>):</strong> <?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>
              <div style="margin-top:8px">
                <?php if($q['question_type']==='objective_single' && is_array($opts)): ?>
                  <?php foreach($opts as $opt): $optVal=htmlspecialchars($opt); ?>
                    <div><label><input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo $optVal; ?>"> <?php echo $optVal; ?></label></div>
                  <?php endforeach; ?>
                <?php elseif($q['question_type']==='objective_multiple' && is_array($opts)): ?>
                  <?php foreach($opts as $opt): $optVal=htmlspecialchars($opt); ?>
                    <div><label><input type="checkbox" name="answers[<?php echo $qid; ?>][]" value="<?php echo $optVal; ?>"> <?php echo $optVal; ?></label></div>
                  <?php endforeach; ?>
                <?php elseif($q['question_type']==='true_false'): ?>
                  <div><label><input type="radio" name="answers[<?php echo $qid; ?>]" value="true"> True</label></div>
                  <div><label><input type="radio" name="answers[<?php echo $qid; ?>]" value="false"> False</label></div>
                <?php elseif($q['question_type']==='short_answer'): ?>
                  <input type="text" name="answers[<?php echo $qid; ?>]" class="form-control" placeholder="Your answer">
                <?php elseif($q['question_type']==='essay' || $q['question_type']==='practical'): ?>
                  <textarea name="answers[<?php echo $qid; ?>]" rows="4" class="form-control" placeholder="Your essay..."></textarea>
                <?php else: ?>
                  <input type="text" name="answers[<?php echo $qid; ?>]" class="form-control">
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if($role==='student'): ?>
          <div style="display:flex;gap:8px;align-items:center">
            <button type="submit" name="submit_test" class="btn btn-primary">Submit</button>
            <span class="muted">Submitting after time is up will be auto-submitted with answers filled so far.</span>
          </div>
          <?php endif; ?>
        </form>
      </div></div>
    <?php endif; ?>
  </main>
</div>
<script>
(function(){
  var el=document.getElementById('timeLeft');
  if(!el) return;
  var s=parseInt(el.getAttribute('data-seconds')||'0',10);
  function fmt(n){return n<10?'0'+n:n}
  function tick(){
    if(s<=0){ el.textContent='00:00'; try{ document.querySelector('button[name="submit_test"]').click(); }catch(e){} return; }
    var m=Math.floor(s/60), ss=s%60; el.textContent=fmt(m)+':'+fmt(ss); s--; setTimeout(tick,1000);
  }
  tick();
})();
</script>
<?php include '../components/footer.php'; ?>
