<?php
require_once '../config/config.php';

if (!isLoggedIn()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$userId = getCurrentUserId();
$isAdmin = isSuperAdmin();
$isTeacherRole = isTeacher();
$isSupervisorRole = isSupervisor();

$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
if ($lessonId <= 0) { redirect('../dashboard/index.php'); }

// Fetch lesson with class and subject
$stmt = $pdo->prepare('SELECT l.*, c.class_name, c.class_code, c.grade_level, c.academic_year, s.subject_name, s.subject_code
                       FROM lessons l
                       LEFT JOIN classes c ON c.id = l.class_id
                       LEFT JOIN subjects s ON s.id = l.subject_id
                       WHERE l.lesson_id = ?');
$stmt->execute([$lessonId]);
$L = $stmt->fetch();
if (!$L) { redirect('../dashboard/index.php'); }

// Access control
$allowed = false;
if ($isAdmin) {
  $allowed = true;
} elseif ($isTeacherRole) {
  // Teacher must be assigned to this class+subject
  $chk = $pdo->prepare('SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1');
  $chk->execute([$userId, (int)$L['class_id'], (int)$L['subject_id']]);
  $allowed = (bool)$chk->fetchColumn();
} elseif ($isSupervisorRole) {
  // Supervisor must manage the class
  $chk = $pdo->prepare('SELECT 1 FROM supervisor_assignments WHERE supervisor_id=? AND class_id=? AND is_active=1');
  $chk->execute([$userId, (int)$L['class_id']]);
  $allowed = (bool)$chk->fetchColumn();
}
if (!$allowed) { redirect('../auth/login.php'); }

$page_title = 'Lesson Details';
$current_page = 'teacher_lessons';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars(($L['lesson_number']?($L['lesson_number'].' - '):'').$L['title']); ?></h1>
        <div class="muted">
          Class: <?php echo htmlspecialchars(($L['class_name']??'').' #'.($L['class_code']??'')); ?> ·
          Subject: <?php echo htmlspecialchars(($L['subject_name']??'').' #'.($L['subject_code']??'')); ?>
        </div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="content-card">
      <div class="card-content" style="display:grid; grid-template-columns: 1fr; gap: 16px;">
        <?php if (!empty($L['thumbnail'])): ?>
          <div class="lesson-detail-thumb" style="width:100%; height:250px; border-radius:8px; overflow:hidden; background:#f3f4f6;">
            <img src="<?php echo htmlspecialchars((strpos($L['thumbnail'],'http')===0)?$L['thumbnail']:'../uploads/resources/'.basename($L['thumbnail'])); ?>" alt="Thumbnail" style="width:100%; height:100%; object-fit:cover; display:block;" />
          </div>
        <?php endif; ?>

        <section>
          <h3><i class="fas fa-info-circle"></i> Description</h3>
          <div><?php echo $L['description'] ?? ''; ?></div>
        </section>

        <?php if (!empty($L['content'])): ?>
        <section>
          <h3><i class="fas fa-file-alt"></i> Content</h3>
          <div><?php echo nl2br(htmlspecialchars($L['content'])); ?></div>
        </section>
        <?php endif; ?>

        <?php if (!empty($L['video'])): ?>
        <section>
          <h3><i class="fas fa-video"></i> Video</h3>
          <div style="margin-top:6px;">
            <?php
            $video = trim((string)$L['video']);
            $ytId = null;
            if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([\w-]{11})~i', $video, $m)) {
              $ytId = $m[1];
            }
            if ($ytId): ?>
              <iframe width="560" height="315" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($ytId); ?>" title="Lesson video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen style="max-width:100%;"></iframe>
            <?php else: ?>
              <a class="btn btn-link" href="<?php echo htmlspecialchars($video); ?>" target="_blank">Open Video</a>
            <?php endif; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($L['vocabulary'])): ?>
        <section>
          <h3><i class="fas fa-book"></i> Vocabulary</h3>
          <?php
            $vocabHref = (strpos($L['vocabulary'],'http')===0)? $L['vocabulary'] : ('../uploads/resources/'.basename($L['vocabulary']));
            $fileName = basename(parse_url($vocabHref, PHP_URL_PATH) ?? $vocabHref);
          ?>
          <div class="teachers-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); max-width: 520px;">
            <div class="teacher-card" style="padding:12px 14px;">
              <div class="teacher-avatar"><i class="fas fa-file-alt"></i></div>
              <div class="teacher-name" style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;"><strong title="<?php echo htmlspecialchars($fileName); ?>"><?php echo htmlspecialchars($fileName); ?></strong></div>
              <div class="teacher-username">Vocabulary</div>
              <div class="teacher-card-actions action-buttons centered">
                <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($vocabHref); ?>" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($vocabHref); ?>" download title="Download"><i class="fas fa-download"></i></a>
              </div>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <?php if (!$isSupervisorRole): ?>
        <section>
          <div class="muted">
            <?php
            // Show timestamps if available (hidden for supervisors)
            $colsStmt = $pdo->query("SHOW COLUMNS FROM lessons");
            $cols = $colsStmt ? $colsStmt->fetchAll() : [];
            $colNames = array_map(fn($c) => $c['Field'] ?? '', $cols);
            $showCreated = in_array('created_at', $colNames, true);
            $showUpdated = in_array('updated_on', $colNames, true) || in_array('updated_ on', $colNames, true);
            if ($showCreated || $showUpdated):
              // Fetch minimal timestamps
              $tsStmt = $pdo->prepare('SELECT '.($showCreated?'created_at':'NULL as created_at').', '.($showUpdated?(in_array('updated_on', $colNames, true)?'updated_on':'`updated_ on`'):'NULL as updated_on').' FROM lessons WHERE lesson_id=?');
              $tsStmt->execute([$lessonId]);
              $ts = $tsStmt->fetch() ?: [];
            ?>
              <div>Created: <?php echo htmlspecialchars($ts['created_at'] ?? ''); ?> · Updated: <?php echo htmlspecialchars(($ts['updated_on'] ?? '') ?: ($ts['updated_ on'] ?? '')); ?></div>
            <?php endif; ?>
          </div>
        </section>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
