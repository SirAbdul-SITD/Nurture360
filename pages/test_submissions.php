<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

$testId = (int)($_GET['id'] ?? 0);
if ($testId <= 0) { redirect('./tests.php'); }

function canManage(){ return isSuperAdmin() || (($_SESSION['role'] ?? '') === 'teacher'); }

// Load test header details
$st = $pdo->prepare("SELECT t.*, c.class_name,c.class_code, s.title AS subject_name,s.subject_code FROM tests t LEFT JOIN classes c ON c.id=t.class_id LEFT JOIN subjects s ON s.subject_id=t.subject_id WHERE t.id=?");
$st->execute([$testId]);
$test = $st->fetch(PDO::FETCH_ASSOC);
if (!$test) { redirect('./tests.php'); }

// Fetch submissions
try {
  $subStmt = $pdo->prepare("SELECT tr.*, u.first_name, u.last_name, u.username FROM test_results tr LEFT JOIN users u ON u.id = tr.student_id WHERE tr.test_id = ? ORDER BY tr.submitted_at DESC");
  $subStmt->execute([$testId]);
  $submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $submissions = [];
}

// Also fetch a simple count to cross-check
try {
  $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM test_results WHERE test_id = ?");
  $cntStmt->execute([$testId]);
  $submissionCount = (int)$cntStmt->fetchColumn();
} catch (Throwable $e) {
  $submissionCount = null;
}

$page_title = 'Test Submissions';
// Optional debug helpers
$debugData = null; $recentRows = [];
if (isset($_GET['debug'])) {
  try { $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn(); } catch (Throwable $e) { $dbName = 'n/a'; }
  $debugData = [
    'db' => $dbName,
    'test_id' => $testId,
    'submissionCount' => $submissionCount,
    'fetchedRows' => is_array($submissions)?count($submissions):0,
  ];
  // Global recent sample (first 5) to confirm visibility
  try {
    $rs = $pdo->query("SELECT id, test_id, student_id, obtained_marks, total_marks, percentage, submitted_at FROM test_results ORDER BY submitted_at DESC LIMIT 5");
    $recentRows = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
  } catch (Throwable $e) { $recentRows = []; }
}
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Submissions: <?php echo htmlspecialchars($test['title'] ?? 'Test'); ?></h1>
        <div class="muted">Class: <?php echo htmlspecialchars(($test['class_name']??'-').' #'.($test['class_code']??'-')); ?> · Subject: <?php echo htmlspecialchars(($test['subject_name']??'-').' #'.($test['subject_code']??'-')); ?></div>
      </div>
      <div class="header-actions">
        <a class="btn btn-secondary" href="./test_detail.php?id=<?php echo (int)$testId; ?>"><i class="fas fa-arrow-left"></i> Back to Test</a>
      </div>
    </div>

    <div class="card-content">
      <?php if(isset($_GET['debug'])): ?>
        <div class="alert alert-info">
          <div><strong>Debug</strong></div>
          <div>db: <?php echo htmlspecialchars((string)($debugData['db']??'n/a')); ?></div>
          <div>test_id: <?php echo (int)$testId; ?></div>
          <div>submissionCount (COUNT(*)): <?php echo ($submissionCount===null?'n/a':(int)$submissionCount); ?></div>
          <div>submissions fetched (rows): <?php echo is_array($submissions)?count($submissions):0; ?></div>
          <?php if(is_array($submissions) && count($submissions)>0): ?>
            <div>first row student_id: <?php echo (int)($submissions[0]['student_id']??0); ?>, id: <?php echo (int)($submissions[0]['id']??0); ?></div>
          <?php endif; ?>
          <?php if(!empty($recentRows)): ?>
            <div style="margin-top:8px"><em>Recent test_results sample (top 5):</em></div>
            <pre style="white-space:pre-wrap; max-height:200px; overflow:auto; background:#f9fafb; padding:8px; border-radius:6px;"><?php echo htmlspecialchars(json_encode($recentRows, JSON_PRETTY_PRINT)); ?></pre>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Obtained</th>
              <th>Total</th>
              <th>%</th>
              <th>Grade</th>
              <th>Submitted At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($submissions) && ($submissionCount ?? 0) > 0):
              // Fallback: re-fetch without join and display minimal info
              try {
                $fallback = $pdo->prepare('SELECT id, test_id, student_id, obtained_marks, total_marks, percentage, grade, submitted_at FROM test_results WHERE test_id = ? ORDER BY submitted_at DESC');
                $fallback->execute([$testId]);
                $submissions = $fallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
              } catch (Throwable $e) { $submissions = []; }
            endif; ?>
            <?php if(!empty($submissions)): foreach($submissions as $i=>$r): $name = trim(($r['first_name']??'').' '.($r['last_name']??'')); if($name==='') $name = $r['username']??('Student #'.(int)$r['student_id']); ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><?php echo htmlspecialchars($name); ?></td>
              <td><?php echo (int)$r['obtained_marks']; ?></td>
              <td><?php echo (int)$r['total_marks']; ?></td>
              <td><?php echo htmlspecialchars(number_format((float)$r['percentage'],2)); ?></td>
              <td><?php echo htmlspecialchars($r['grade']??''); ?></td>
              <td><?php echo htmlspecialchars($r['submitted_at']??''); ?></td>
              <td>
                <?php if(canManage()): ?>
                  <a class="btn btn-sm btn-secondary" href="./test_result_view.php?test_id=<?php echo (int)$testId; ?>&student_id=<?php echo (int)$r['student_id']; ?>">View</a>
                <?php else: ?>
                  <span class="muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8" class="muted">No submissions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
