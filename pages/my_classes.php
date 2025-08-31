<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isTeacher()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$teacherId = getCurrentUserId();
$message = '';
$error = '';

try {
    // Classes assigned to this teacher with student counts
    $stmt = $pdo->prepare(
        "SELECT c.id, c.class_name, c.class_code, c.grade_level, c.academic_year,
                COUNT(DISTINCT se.student_id) AS student_count
         FROM teacher_assignments ta
         JOIN classes c ON c.id = ta.class_id
         LEFT JOIN student_enrollments se ON se.class_id = c.id
         WHERE ta.teacher_id = ? AND COALESCE(ta.is_active,1)=1 AND COALESCE(c.is_active,1)=1
         GROUP BY c.id, c.class_name, c.class_code, c.grade_level, c.academic_year
         ORDER BY c.grade_level, c.class_name"
    );
    $stmt->execute([$teacherId]);
    $classes = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
    $classes = [];
}

$page_title = 'My Classes';
$current_page = 'my_classes';
include '../components/header.php';
?>
<style>
#classGrid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:16px; }
@media(max-width:1024px){ #classGrid{ grid-template-columns:repeat(2, minmax(0,1fr)); } }
@media(max-width:640px){ #classGrid{ grid-template-columns:1fr; } }
.class-card { border:1px solid var(--border-color); border-radius:10px; padding:16px; display:flex; flex-direction:column; gap:8px; }
.class-card .meta { color:#6b7280; font-size: 14px; }
.class-card .actions { display:flex; gap:8px; margin-top:8px; }
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>My Classes</h1>
      <p>These are the classes assigned to you.</p>
    </div>

    <div class="card-content">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div id="classGrid">
        <?php if ($classes): foreach ($classes as $c): ?>
          <div class="class-card">
            <div style="display:flex; align-items:center; gap:8px;">
              <i class="fas fa-school"></i>
              <strong><?php echo htmlspecialchars($c['class_name']); ?></strong>
            </div>
            <div class="meta">Code: <?php echo htmlspecialchars($c['class_code']); ?> • Grade: G<?php echo (int)$c['grade_level']; ?> • Year: <?php echo htmlspecialchars($c['academic_year']); ?></div>
            <div class="meta">Students: <?php echo (int)$c['student_count']; ?></div>
            <div class="actions">
              <a class="btn btn-sm btn-primary" href="./class_detail.php?id=<?php echo (int)$c['id']; ?>">
                <i class="fas fa-users"></i> View Class
              </a>
            </div>
          </div>
        <?php endforeach; else: ?>
          <div class="no-data" style="grid-column:1 / -1; display:flex; align-items:center; justify-content:center; min-height:45vh; flex-direction:column; color:#6b7280; text-align:center;">
            <i class="fas fa-users" style="font-size:48px; margin-bottom:8px; color:#9ca3af;"></i>
            <p style="font-size:18px; margin:0;">No assigned classes yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
