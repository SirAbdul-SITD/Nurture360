<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isTeacher()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$teacherId = getCurrentUserId();
$classId = (int)($_GET['id'] ?? 0);
if ($classId <= 0) { redirect('./my_classes.php'); }

$error = '';
$class = null;
$teacherSubjects = [];
$students = [];

try {
    // Verify this class is assigned to the current teacher
    $chk = $pdo->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND COALESCE(is_active,1)=1");
    $chk->execute([$teacherId, $classId]);
    if (!$chk->fetchColumn()) {
        throw new Exception('You are not assigned to this class.');
    }

    // Get class details
    $cStmt = $pdo->prepare("SELECT id, class_name, class_code, grade_level, academic_year, description, max_students FROM classes WHERE id=?");
    $cStmt->execute([$classId]);
    $class = $cStmt->fetch();
    if (!$class) { throw new Exception('Class not found'); }

    // Get subjects this teacher teaches in this class (updated schema)
    $sStmt = $pdo->prepare("SELECT s.subject_id AS id, s.title AS subject_name, s.subject_code
                            FROM teacher_assignments ta
                            JOIN subjects s ON s.subject_id = ta.subject_id
                            WHERE ta.teacher_id=? AND ta.class_id=? AND COALESCE(ta.is_active,1)=1
                            ORDER BY s.title");
    $sStmt->execute([$teacherId, $classId]);
    $teacherSubjects = $sStmt->fetchAll();

    // Get enrolled students for this class
    $stStmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.username, u.email, u.profile_image
                              FROM student_enrollments se
                              JOIN users u ON u.id = se.student_id
                              WHERE se.class_id=?
                              ORDER BY u.first_name, u.last_name");
    $stStmt->execute([$classId]);
    $students = $stStmt->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$page_title = 'Class Details';
$current_page = 'my_classes';
include '../components/header.php';
?>
<style>
.class-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; }
.meta { color:#6b7280; }
.subject-badges { display:flex; gap:6px; flex-wrap:wrap; }
.subject-badges .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; background:#dbeafe; color:#1e40af; }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:10px; border:1px solid var(--border-color); text-align:left; }
.table th { background: var(--card-bg); }
.student-avatar { width:32px; height:32px; border-radius:50%; object-fit:cover; }
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Class Details</h1></div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
      <div class="card">
        <div class="card-header class-header">
          <div>
            <h2 style="margin:0;"><?php echo htmlspecialchars($class['class_name']); ?></h2>
            <div class="meta">Code: <?php echo htmlspecialchars($class['class_code']); ?> • Grade: G<?php echo (int)$class['grade_level']; ?> • Year: <?php echo htmlspecialchars($class['academic_year']); ?></div>
          </div>
          <div class="subject-badges">
            <?php if ($teacherSubjects): foreach ($teacherSubjects as $s): ?>
              <span class="badge"><?php echo htmlspecialchars($s['subject_name']); ?></span>
            <?php endforeach; else: ?>
              <span class="meta">No subjects assigned.</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (!empty($class['description'])): ?>
          <div class="card-content">
            <p><?php echo nl2br(htmlspecialchars($class['description'])); ?></p>
          </div>
        <?php endif; ?>
      </div>

      <div class="card" style="margin-top:12px;">
        <div class="card-header"><h3 style="margin:0;">Enrolled Students (<?php echo count($students); ?>)</h3></div>
        <div class="card-content">
          <?php if ($students): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>Username</th>
                  <th>Email</th>
                </tr>
              </thead>
              <tbody>
                <?php $i=1; foreach ($students as $stu): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td>
                      <?php
                        $pi = $stu['profile_image'] ?? '';
                        $url = '';
                        if (!empty($pi)) {
                          if (preg_match('/^https?:\/\//i', $pi)) { $url = $pi; }
                          else if (strpos($pi,'/') !== false) {
                            $p = ltrim($pi,'/'); if (strpos($p,'rinda/')===0) { $p = substr($p, strlen('rinda/')); }
                            $url = (strpos($p,'uploads/')===0) ? ('../'.$p) : ('../uploads/users/'.rawurlencode(basename($p)));
                          } else { $url = '../uploads/users/'.rawurlencode($pi); }
                        }
                      ?>
                      <img src="<?php echo htmlspecialchars($url); ?>" alt="" class="student-avatar" onerror="this.style.display='none';">
                      <?php echo htmlspecialchars(trim(($stu['first_name']??'').' '.($stu['last_name']??''))); ?>
                    </td>
                    <td><?php echo htmlspecialchars($stu['username']??''); ?></td>
                    <td><?php echo htmlspecialchars($stu['email']??''); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <div class="no-data" style="display:flex; align-items:center; justify-content:center; min-height:30vh; color:#6b7280;">
              <i class="fas fa-user-graduate" style="font-size:36px; margin-right:8px; color:#9ca3af;"></i>
              <span>No students enrolled yet.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </main>
</div>
<?php include '../components/footer.php'; ?>
