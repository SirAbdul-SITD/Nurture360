<?php
require_once '../config/config.php';
// Allow logged-in users; enforce role/ownership after fetching assignment
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

$assignmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($assignmentId <= 0) { redirect('./assignments.php'); }

// Fetch assignment with relations
$stmt = $pdo->prepare(
  "SELECT a.*, 
          c.class_name, c.class_code, c.grade_level, c.academic_year,
          s.subject_name, s.subject_code,
          t.first_name AS t_first, t.last_name AS t_last, t.username AS t_user
   FROM assignments a
   LEFT JOIN classes c ON c.id = a.class_id
   LEFT JOIN subjects s ON s.id = a.subject_id
   LEFT JOIN users t ON t.id = a.teacher_id
   WHERE a.id = ?"
);
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();
if (!$assignment) { redirect('./assignments.php'); }

// Authorization: SuperAdmin can view; Teacher can view only own assignment
if (isSuperAdmin()) {
    // ok
} elseif (isTeacher()) {
    if ((int)($assignment['teacher_id'] ?? 0) !== (int)getCurrentUserId()) {
        redirect('./assignments.php');
    }
} else {
    redirect('./assignments.php');
}

// Compute status
$dueTs = @strtotime(($assignment['due_date'] ?? '') . ' ' . ($assignment['due_time'] ?? ''));
$isClosed = ($dueTs !== false) ? ($dueTs < time()) : false;
$statusText = $isClosed ? 'Closed' : 'Open';
$statusClass = $isClosed ? 'badge-danger' : 'badge-success';
$marksVal = (int)($assignment['total_marks'] ?? 0);

// Fetch submissions with students and graders
$sub = $pdo->prepare(
  "SELECT asb.*, 
          stu.first_name AS s_first, stu.last_name AS s_last, stu.username AS s_user, stu.email AS s_email,
          gr.first_name AS g_first, gr.last_name AS g_last, gr.username AS g_user
     FROM assignment_submissions asb
     LEFT JOIN users stu ON stu.id = asb.student_id
     LEFT JOIN users gr  ON gr.id  = asb.graded_by
    WHERE asb.assignment_id = ?
    ORDER BY asb.submitted_at DESC"
);
$sub->execute([$assignmentId]);
$submissions = $sub->fetchAll();

// Page title for header.php
$page_title = 'Assignment — ' . ($assignment['title'] ?? '');

include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
        <div class="muted">
          Class: <?php echo htmlspecialchars(($assignment['class_name'] ?? '-') . ' (' . ($assignment['class_code'] ?? '-') . ')'); ?> ·
          Subject: <?php echo htmlspecialchars(($assignment['subject_name'] ?? '-') . (!empty($assignment['subject_code']) ? ' (' . $assignment['subject_code'] . ')' : '')); ?> ·
          Teacher: <?php 
            $tn = trim(($assignment['t_first'] ?? '') . ' ' . ($assignment['t_last'] ?? ''));
            echo htmlspecialchars($tn !== '' ? $tn : ($assignment['t_user'] ?? ''));
          ?> ·
          Due: <?php echo htmlspecialchars(($assignment['due_date'] ?? '') . ' ' . ($assignment['due_time'] ?? '')); ?> ·
          <span class="status-badge" style="background:#eff6ff;color:#1e3a8a;border:1px solid #bfdbfe;">Marks: <?php echo (int)($assignment['total_marks'] ?? 0); ?></span>
        </div>
      </div>
      <div class="header-actions">
        <a class="btn btn-primary" href="./assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
        <span class="status-badge <?php echo $isClosed ? 'status-inactive' : 'status-active'; ?>" style="margin-left:8px;">
          <?php echo htmlspecialchars($statusText); ?>
        </span>
      </div>
    </div>
    <!-- Questions (Description) card -->
    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-question-circle"></i> Questions</h3>
      </div>
      <div class="card-content">
        <?php if (!empty($assignment['description'])): ?>
          <?php 
            $desc = (string)$assignment['description'];
            $lines = preg_split("/(\r\n|\r|\n)/", $desc);
            $items = array_values(array_filter(array_map('trim', $lines), function($v){ return $v !== ''; }));
          ?>
          <?php if (count($items) > 0): ?>
            <div class="question-list">
              <?php foreach ($items as $q): ?>
                <div class="question-line"><strong><?php echo htmlspecialchars($q); ?></strong></div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-state">No questions provided</div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-state">No questions provided</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Submissions card -->
    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-user-graduate"></i> Submissions (<?php echo count($submissions); ?>)</h3>
      </div>
      <div class="card-content">
        <?php if (!empty($submissions)): ?>
          <div style="overflow:auto;">
            <table class="submissions-table" style="width:100%; border-collapse: collapse;">
              <thead>
                <tr>
                  <th style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:left;">Student</th>
                  <th style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:left;">Submitted</th>
                  <th style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:left;">File</th>
                  <th style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:left;">Marks</th>
                  <th style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:left;">Grade</th>
                  <th style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:left;">Graded By</th>
                  <th style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:left;">Feedback</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($submissions as $row): 
                $student = trim(($row['s_first'] ?? '') . ' ' . ($row['s_last'] ?? ''));
                if ($student === '') { $student = (string)($row['s_user'] ?? ''); }
                $grader = trim(($row['g_first'] ?? '') . ' ' . ($row['g_last'] ?? ''));
                if ($grader === '') { $grader = (string)($row['g_user'] ?? ''); }
                $obt = is_null($row['obtained_marks']) ? '-' : (int)$row['obtained_marks'];
                $tot = is_null($row['total_marks']) ? $marksVal : (int)$row['total_marks'];
                $pct = (is_numeric($obt) && is_numeric($tot) && $tot > 0) ? number_format(($obt/$tot)*100, 1) . '%' : '-';
                $grade = $row['grade'] ?? '';
                $file = $row['file_path'] ?? '';
              ?>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid #e5e7eb;">&nbsp;<?php echo htmlspecialchars($student); ?></td>
                  <td style="padding:10px; border-bottom:1px solid #e5e7eb;">&nbsp;<?php echo htmlspecialchars($row['submitted_at'] ?? ''); ?></td>
                  <td style="padding:10px; border-bottom:1px solid #e5e7eb;" class="submission-file">
                    <?php if ($file): ?>
                      <a href="<?php echo htmlspecialchars($file); ?>" target="_blank"><i class="fas fa-paperclip"></i> View</a>
                    <?php else: ?>
                      <span class="muted">No file</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:10px; border-bottom:1px solid #e5e7eb;">&nbsp;<?php echo ($obt === '-') ? '-' : ($obt . ' / ' . $tot . ' (' . $pct . ')'); ?></td>
                  <td style="padding:10px; border-bottom:1px solid #e5e7eb;">&nbsp;<?php echo htmlspecialchars($grade ?: '-'); ?></td>
                  <td style="padding:10px; border-bottom:1px solid #e5e7eb;">&nbsp;<?php echo htmlspecialchars($grader ?: '-'); ?></td>
                  <td style="padding:10px; border-bottom:1px solid #e5e7eb;">&nbsp;<?php echo htmlspecialchars($row['feedback'] ?? '-'); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">No submissions yet</div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
