<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSupervisor()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$supervisorId = getCurrentUserId();
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
if ($classId <= 0 || $subjectId <= 0) { redirect('./classes.php'); }

// ensure supervisor manages this class
$chk = $pdo->prepare("SELECT 1 FROM supervisor_assignments WHERE supervisor_id=? AND class_id=? AND is_active=1");
$chk->execute([$supervisorId, $classId]);
if (!$chk->fetchColumn()) { redirect('./classes.php'); }

// fetch class and subject names
$cls = $pdo->prepare("SELECT class_name, grade_level, class_code, academic_year FROM classes WHERE id=?");
$cls->execute([$classId]);
$class = $cls->fetch() ?: [];

$sub = $pdo->prepare("SELECT subject_name, subject_code FROM subjects WHERE id=?");
$sub->execute([$subjectId]);
$subject = $sub->fetch() ?: [];

// Determine which timestamp column exists to avoid SQL errors
$colsStmt = $pdo->query("SHOW COLUMNS FROM lessons");
$cols = $colsStmt ? $colsStmt->fetchAll() : [];
$colNames = array_map(fn($c) => $c['Field'] ?? '', $cols);
$hasUpdatedOn = in_array('updated_on', $colNames, true);
$hasCreatedAt = in_array('created_at', $colNames, true);
$hasUpdatedWithSpace = in_array('updated_ on', $colNames, true); // legacy typo
$updatedExpr = $hasUpdatedOn ? 'updated_on' : ($hasCreatedAt ? 'created_at' : ($hasUpdatedWithSpace ? '`updated_ on`' : 'NULL'));

// fetch lessons using the detected timestamp column
$sql = "SELECT 
          lesson_id, lesson_number, title, description, vocabulary, content, thumbnail, video,
          $updatedExpr AS updated_on
        FROM lessons 
        WHERE class_id=? AND subject_id=? 
        ORDER BY lesson_number IS NULL, lesson_number, lesson_id DESC";
$lst = $pdo->prepare($sql);
$lst->execute([$classId, $subjectId]);
$lessons = $lst->fetchAll();

$page_title = 'Lessons';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Lessons · <?php echo htmlspecialchars($subject['subject_name'] ?? 'Subject'); ?></h1>
        <div class="muted">Class: <?php echo htmlspecialchars($class['class_name'] ?? ''); ?> · Code: <?php echo htmlspecialchars($class['class_code'] ?? ''); ?></div>
      </div>
      <div>
        <a class="btn btn-secondary" href="./class_view.php?class_id=<?php echo (int)$classId; ?>"><i class="fas fa-arrow-left"></i> Back to Class</a>
      </div>
    </div>

    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-book-open"></i> Lessons (<?php echo count($lessons); ?>)</h3>
      </div>
      <div class="card-content">
        <?php if (!$lessons): ?>
          <div class="empty-state">No lessons added for this subject in this class.</div>
        <?php else: ?>
          <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            <?php foreach ($lessons as $L): ?>
              <div class="profile-card">
                <!-- Top Thumbnail -->
                <div class="lesson-thumb" style="width:100%; height:160px; border-radius:12px; overflow:hidden; background:#f3f4f6; display:block;">
                  <?php if (!empty($L['thumbnail'])): ?>
                    <img src="<?php echo htmlspecialchars((strpos($L['thumbnail'],'http')===0)?$L['thumbnail']:'../uploads/resources/'.basename($L['thumbnail'])); ?>" alt="Thumbnail" style="width:100%; height:100%; object-fit:cover; display:block;"/>
                  <?php else: ?>
                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-weight:600;">No Image</div>
                  <?php endif; ?>
                </div>

                <!-- Title & meta -->
                <div class="profile-text" style="margin-top:10px;">
                  <div class="profile-title-row">
                    <div class="profile-title" style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">
                      <?php echo htmlspecialchars(($L['lesson_number']?($L['lesson_number'].' - '):'') . $L['title']); ?>
                    </div>
                  </div>
                  <div class="profile-subtitle">Updated: <?php echo htmlspecialchars($L['updated_on'] ?? ''); ?></div>
                </div>
                <div class="profile-meta" style="padding:12px;">
                  <div style="font-size:0.92rem;color:#555;max-height:4.5em;overflow:hidden; white-space: normal; word-break: break-word; overflow-wrap: anywhere;">
                    <?php echo substr(strip_tags($L['description'] ?? ''), 0, 260); ?>
                  </div>
                  <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                    <?php if (!empty($L['vocabulary'])): ?>
                      <a class="badge" href="<?php echo htmlspecialchars((strpos($L['vocabulary'],'http')===0)?$L['vocabulary']:'../uploads/resources/'.basename($L['vocabulary'])); ?>" target="_blank"><i class="fas fa-file-alt"></i> Vocabulary</a>
                    <?php endif; ?>
                    <?php if (!empty($L['video'])): ?>
                      <a class="badge" href="<?php echo htmlspecialchars($L['video']); ?>" target="_blank"><i class="fas fa-video"></i> Video</a>
                    <?php endif; ?>
                  </div>
                  <div style="margin-top:10px;">
                    <a class="btn btn-primary" href="../pages/lesson_detail.php?lesson_id=<?php echo (int)$L['lesson_id']; ?>">
                      <i class="fas fa-eye"></i> View Details
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
