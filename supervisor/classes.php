<?php
require_once '../config/config.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'supervisor') {
    redirect('../auth/login.php');
}

$supervisor_id = getCurrentUserId();
$page_title = 'Assigned Classes';
$assigned_classes = [];

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT 
                               c.id AS class_id,
                               c.class_name,
                               c.class_code,
                               c.grade_level,
                               c.academic_year,
                               c.max_students,
                               c.is_active,
                               c.virtual_link
                           FROM supervisor_assignments sa
                           JOIN classes c ON sa.class_id = c.id
                           WHERE sa.supervisor_id = ? AND sa.is_active = 1
                           ORDER BY c.grade_level, c.class_name");
    $stmt->execute([$supervisor_id]);
    $assigned_classes = $stmt->fetchAll();

    // Build teacher counts per class (active assignments)
    $class_ids = array_map(fn($r) => (int)$r['class_id'], $assigned_classes);
    $classTeacherCounts = [];
    $classTeachers = [];
    $classStudentCounts = [];
    if (!empty($class_ids)) {
        $in = implode(',', array_fill(0, count($class_ids), '?'));
        $tc = $pdo->prepare("SELECT class_id, COUNT(*) AS cnt
                              FROM teacher_assignments
                              WHERE is_active=1 AND class_id IN ($in)
                              GROUP BY class_id");
        $tc->execute($class_ids);
        foreach ($tc->fetchAll() as $row) { $classTeacherCounts[(int)$row['class_id']] = (int)$row['cnt']; }

        // Fetch teacher names per class
        $tn = $pdo->prepare("SELECT ta.class_id, u.first_name, u.last_name
                              FROM teacher_assignments ta
                              JOIN users u ON u.id = ta.teacher_id
                              WHERE ta.is_active=1 AND ta.class_id IN ($in)");
        $tn->execute($class_ids);
        foreach ($tn->fetchAll() as $row) {
            $cid = (int)$row['class_id'];
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($name === '') { $name = 'Unknown Teacher'; }
            $classTeachers[$cid] = $classTeachers[$cid] ?? [];
            $classTeachers[$cid][] = $name;
        }

        // Count active students per class
        $sc = $pdo->prepare("SELECT class_id, COUNT(*) AS cnt
                              FROM student_enrollments
                              WHERE status='active' AND class_id IN ($in)
                              GROUP BY class_id");
        $sc->execute($class_ids);
        foreach ($sc->fetchAll() as $row) { $classStudentCounts[(int)$row['class_id']] = (int)$row['cnt']; }
    }
} catch (PDOException $e) {
    $error_message = 'Error loading classes: ' . $e->getMessage();
}

function virtualClassUrl($code) {
    return APP_URL . '/virtual/class/' . urlencode($code);
}

include '../components/header.php';
?>
<style>
/* Page-scoped: 3-column grid for supervisor classes (desktop), 2 on tablet, 1 on mobile */
.cards-grid.supervisor-classes-grid { grid-template-columns: repeat(3, 1fr); gap: var(--spacing-6); }
@media (max-width: 1024px) {
  .cards-grid.supervisor-classes-grid { grid-template-columns: repeat(2, 1fr); gap: var(--spacing-4); }
}
@media (max-width: 768px) {
  .cards-grid.supervisor-classes-grid { grid-template-columns: 1fr; gap: var(--spacing-5); }
}
/* Center alignment for card contents */
.teacher-card .teacher-card-body { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.teacher-card .info-row { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-align: center; }
.teacher-card .info-row .link-wrap { justify-content: center; }
.teacher-card .teacher-card-actions.action-buttons.centered { justify-content: center; }
.teacher-card .link { text-align: center; }
/* Keep academic year and teacher names on one line; truncate long teacher lists */
.teacher-card .info-row .teacher-list { flex: 1; min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; text-align: center; }
</style>
<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Classes</h1>
            <p>Your assigned classes.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <p class="no-data" style="color:#dc2626;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <?php if (empty($assigned_classes)): ?>
          <p class="no-data">No classes assigned.</p>
        <?php else: ?>
          <div id="classesGrid" class="cards-grid supervisor-classes-grid">
            <?php foreach ($assigned_classes as $c): ?>
              <?php $link = !empty($c['virtual_link']) ? $c['virtual_link'] : virtualClassUrl($c['class_code']); ?>
              <div class="teacher-card">
                <span class="teacher-status status-badge <?php echo !empty($c['is_active']) ? 'status-active' : 'status-inactive'; ?>">
                  <?php echo !empty($c['is_active']) ? 'Active' : 'Inactive'; ?>
                </span>
                <div class="teacher-avatar"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="teacher-name"><strong><?php echo htmlspecialchars($c['class_name']); ?></strong></div>
                <div class="teacher-username">#<?php echo htmlspecialchars($c['class_code']); ?></div>
                <div class="teacher-card-body">
                  <div class="info-row">
                    <i class="fas fa-qrcode"></i><span>Code: <strong><?php echo htmlspecialchars($c['class_code']); ?></strong></span>
                    <span style="opacity:.6;">•</span>
                    <i class="fas fa-layer-group"></i><span>Grade <?php echo (int)$c['grade_level']; ?></span>
                  </div>
                  <div class="info-row">
                    <i class="far fa-calendar-alt"></i><span><?php echo htmlspecialchars($c['academic_year']); ?></span>
                    <span style="opacity:.6;">•</span>
                    <i class="fas fa-chalkboard"></i>
                    <span class="teacher-list">
                      <?php 
                        $tlist = $classTeachers[(int)$c['class_id']] ?? [];
                        $tcount = (int)($classTeacherCounts[(int)$c['class_id']] ?? 0);
                        echo $tcount > 0 ? htmlspecialchars(implode(', ', array_slice($tlist, 0, 3))) . ($tcount > 3 ? ' +' . ($tcount-3) . ' more' : '') : 'No teachers';
                      ?>
                    </span>
                  </div>
                  <div class="info-row"><i class="fas fa-user-graduate"></i><span><?php echo (int)($classStudentCounts[(int)$c['class_id']] ?? 0); ?> students</span></div>
                  <div class="info-row">
                    <i class="fas fa-link"></i>
                    <div class="link-wrap" style="display:flex; gap:8px; align-items:center; flex:1; min-width:0;">
                      <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener" class="link" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;">
                        <?php echo htmlspecialchars($link); ?>
                      </a>
                      <div class="input-group" style="position:relative;">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($link); ?>" readonly style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;">
                        <!-- <button class="btn btn-secondary" onclick="copyText(this)" title="Copy link"><i class="fas fa-copy"></i></button> -->
                      </div>
                    </div>
                  </div>
                </div>
                <div class="teacher-card-actions action-buttons centered">
                  <a class="btn btn-sm btn-primary" href="./class_view.php?class_id=<?php echo (int)$c['class_id']; ?>" title="Go to Class"><i class="fas fa-door-open"></i> Go to Class</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
    </main>
</div>
<script>
// Ensure copy button works here as well
function copyText(btn){
  try {
    const input = btn.parentElement.querySelector('input');
    input.select();
    document.execCommand('copy');
    if (typeof showNotification==='function') showNotification('Copied to clipboard','success');
  } catch(e) { /* noop */ }
}
</script>
<?php include '../components/footer.php'; ?>
