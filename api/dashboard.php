<?php
require_once('settings.php');
// Fallback to config if needed
if (!isset($pdo) || !($pdo instanceof PDO)) {
  require_once __DIR__ . '/../config/config.php';
  $pdo = getDBConnection();
}

// Resolve student and class context
$student_id = (int)($_SESSION['user_id'] ?? 0);
$first_name = $_SESSION['first_name'] ?? 'Student';
$last_name = $_SESSION['last_name'] ?? '';
$class_id = $_SESSION['class_id'] ?? ($my_class ?? 0);
$class_title = $_SESSION['class_title'] ?? ($my_class_Title ?? '');

// Theme
$btnColor = $_SESSION['button_color'] ?? ($theme_primary_color ?? '#6c63ff');
$primary = htmlspecialchars($theme_primary_color ?? '#1f3aa6');
$accent = htmlspecialchars($theme_accent_color ?? '#6c63ff');

// Data containers
$subjects = [];
$teachers = [];
$resources = [];
$announcements = [];
$total_subjects = 0;
$total_assignments = 0;
$total_tests = 0;

try {
  if ($class_id) {
    // Subjects for class (Enrolled Courses)
    $st = $pdo->prepare("SELECT s.id, s.subject_name, s.subject_code FROM class_subjects cs JOIN subjects s ON s.id = cs.subject_id WHERE cs.class_id = ? ORDER BY s.subject_name");
    $st->execute([$class_id]);
    $subjects = $st->fetchAll(PDO::FETCH_ASSOC);

    // Teachers for this class (Course instructors)
    $st = $pdo->prepare("SELECT DISTINCT u.id, u.first_name, u.last_name, u.profile_image FROM teacher_assignments ta JOIN users u ON u.id = ta.teacher_id WHERE ta.class_id = ? AND COALESCE(ta.is_active,1)=1 ORDER BY u.first_name, u.last_name");
    $st->execute([$class_id]);
    $teachers = $st->fetchAll(PDO::FETCH_ASSOC);

    // Learning resources (Virtual class / Learning resources)
    $st = $pdo->prepare("SELECT id, title, resource_type, created_at FROM learning_resources WHERE class_id = ? ORDER BY created_at DESC LIMIT 6");
    $st->execute([$class_id]);
    $resources = $st->fetchAll(PDO::FETCH_ASSOC);

    // Announcements for the class and global
    $st = $pdo->prepare("SELECT id, title, content, created_at FROM announcements WHERE is_active=1 AND (target_audience='all' OR (target_audience='specific_class' AND target_class_id=?)) AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY created_at DESC LIMIT 6");
    $st->execute([$class_id]);
    $announcements = $st->fetchAll(PDO::FETCH_ASSOC);

    // Stats: totals within the class
    // Total subjects assigned to this class
    $st = $pdo->prepare("SELECT COUNT(*) FROM class_subjects WHERE class_id = ?");
    $st->execute([$class_id]);
    $total_subjects = (int)$st->fetchColumn();

    // Total active assignments in this class
    $st = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE class_id = ? AND COALESCE(is_active,1)=1");
    $st->execute([$class_id]);
    $total_assignments = (int)$st->fetchColumn();

    // Total active tests in this class
    $st = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE class_id = ? AND COALESCE(is_active,1)=1");
    $st->execute([$class_id]);
    $total_tests = (int)$st->fetchColumn();
  }
} catch (Throwable $e) {
  // Soft fail: keep empty lists
}

function initials($f, $l){
  $t = trim(($f.' '.$l));
  $parts = preg_split('/\s+/', $t);
  $i = '';
  foreach($parts as $p){ if($p !== '') { $i .= mb_strtoupper(mb_substr($p,0,1)); if(mb_strlen($i)===2) break; } }
  return $i ?: 'T';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Student Dashboard</title>
  <link rel="stylesheet" href="assets/vendors/iconfonts/mdi/css/materialdesignicons.css" />
  <link rel="stylesheet" href="assets/vendors/css/vendor.addons.css" />
  <link rel="stylesheet" href="assets/css/shared/style.css" />
  <link rel="stylesheet" href="assets/css/demo_1/style.css" />
  <style>
    :root { --primary: <?= $primary ?>; --accent: <?= $accent ?>; }
    html, body { height: 100%; }
    body { background:#f3f6fb; margin:0; min-height:100vh; }
    .dashboard-shell { width: 100%; max-width: none; margin: 0; padding: 24px; min-height: 100vh; height: 100vh; display: grid; grid-template-columns: 260px 1fr; gap: 20px; box-sizing: border-box; overflow: hidden; }
    .sidebar { background: var(--primary); border-radius: 18px; padding: 20px; color: #fff; position: sticky; top: 24px; height: calc(100vh - 48px); overflow: hidden; align-self: start; }
    .sidebar .logo { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
    .sidebar .menu a { display:flex; align-items:center; gap:10px; color:#cdd5ff; text-decoration:none; padding:10px 12px; border-radius:12px; margin:4px 0; }
    .sidebar .menu a.active, .sidebar .menu a:hover { background: var(--accent); color:#fff; }
    .content { height: calc(100vh - 48px); overflow: auto; scrollbar-width: none; -ms-overflow-style: none; }
    .content::-webkit-scrollbar { width: 0; height: 0; }
    .topbar { position: sticky; top: 0; z-index: 20; background:#fff; border-radius: 18px; padding: 14px 16px; display:flex; align-items:center; justify-content:space-between; box-shadow: 0 10px 30px rgba(31,58,166,0.08); margin-bottom: 16px; }
    .search { background:#f0f2f8; border-radius: 999px; padding: 8px 12px; display:flex; align-items:center; gap:8px; width: 340px; }
    .search input { border:0; background:transparent; outline:none; width:100%; }
    .user-mini { display:flex; align-items:center; gap:10px; }
    .avatar { width:40px; height:40px; border-radius:50%; background:#e7e9f5; display:flex; align-items:center; justify-content:center; font-weight:700; color:#334; overflow:hidden; }
    .hero { background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 18px; padding: 22px; color:#fff; display:grid; grid-template-columns: 1fr 160px; gap:16px; }
    .cards { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:16px; margin-top:16px; }
    .card { background:#fff; border-radius:16px; padding:18px; box-shadow:0 10px 30px rgba(20,28,60,0.06); }
    /* Ensure hero uses theme gradient even when it has the 'card' class */
    .card.hero { background: linear-gradient(135deg, var(--primary), var(--accent)) !important; color:#fff; }
    .sec { margin-top:20px; }
    .sec-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .grid-3 { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:14px; }
    .grid-2 { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:14px; }
    .course { background:#eef2ff; border-radius:14px; padding:14px; display:flex; align-items:center; justify-content:space-between; }
    .course .title { font-weight:700; color:#1f2b6c; }
    .pill { background:#fff; border:1px solid #dde3ff; border-radius:999px; padding:6px 12px; font-size:12px; color:#1f2b6c; }
    .inst { display:flex; align-items:center; gap:10px; }
    .inst .avatar { width:36px; height:36px; }
    .notice { display:flex; flex-direction:column; gap:10px; }
    .notice-item { background:#fff; border-radius:12px; padding:12px; border:1px solid #eef1fb; }
    .res-card { display:flex; align-items:center; justify-content:space-between; background:#fff; border-radius:12px; padding:12px; border:1px solid #eef1fb; }
    /* Mobile sidebar toggle button */
    .mobile-toggle { display:none; border:0; background:#f0f2f8; width:40px; height:40px; border-radius:10px; align-items:center; justify-content:center; color:#223; }
    .mobile-toggle .mdi { font-size:22px; }
    /* Sidebar backdrop */
    .sidebar-backdrop { position:fixed; inset:0; background:rgba(17,24,39,0.5); backdrop-filter:saturate(140%) blur(1px); display:none; z-index:55; }
    /* Bottom mobile nav */
    .bottom-nav { position: fixed; left: 0; right: 0; bottom: 0; height: 60px; background:#fff; border-top:1px solid #e7e9f5; display:none; z-index: 50; }
    .bottom-nav .wrap { max-width: 1100px; margin: 0 auto; height:100%; display:flex; align-items:center; }
    .bottom-nav a { flex:1; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; text-decoration:none; color:#556; font-size:12px; }
    .bottom-nav a .mdi { font-size:20px; }
    .bottom-nav a.active, .bottom-nav a:hover { color: var(--primary); }
    @media (max-width: 1024px) { 
      .dashboard-shell { grid-template-columns: 1fr; height: auto; min-height: 100vh; overflow: visible; }
      /* Off-canvas sidebar */
      .sidebar { position: fixed; top: 0; left: 0; bottom: 0; height: 100vh; width: 80vw; max-width: 320px; z-index: 60; transform: translateX(-100%); transition: transform .25s ease; border-radius: 0 18px 18px 0; }
      body.sidebar-open .sidebar { transform: translateX(0); }
      .sidebar-backdrop { display:none; }
      body.sidebar-open .sidebar-backdrop { display:block; }
      .mobile-toggle { display:inline-flex; }
      .content { height: auto; overflow: visible; padding-bottom: 76px; } /* space for bottom nav */
      .cards{ grid-template-columns:1fr !important; }
      .grid-3{ grid-template-columns:1fr !important; }
      .grid-2{ grid-template-columns:1fr !important; }
      .hero { grid-template-columns: 1fr; }
      .search { width: 100%; }
      .bottom-nav { display:block; }
    }
  </style>
</head>
<body>
  <div class="dashboard-shell">
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    <div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

    <main class="content">
      <?php include __DIR__ . '/components/header.php'; ?>

      <section class="hero card" style="color:#fff;">
        <div>
          <div style="opacity:.9; font-size:12px;">
            <?= htmlspecialchars(date('F j, Y')) ?>
          </div>
          <h2 style="margin:6px 0 8px; font-weight:800;">Welcome back, <?= htmlspecialchars($first_name) ?>!</h2>
          <div style="opacity:.9;">Always stay updated in your student portal</div>
        </div>
        <div style="display:flex; align-items:center; justify-content:center;">
          <i class="mdi mdi-school" style="font-size:60px; opacity:.9;"></i>
        </div>
      </section>

      <section class="cards">
        <div class="card">
          <div style="display:flex; gap:10px; align-items:center;">
            <i class="mdi mdi-book-open-variant" style="font-size:26px; color:var(--primary);"></i>
            <div>
              <div style="font-size:12px; opacity:.7;">Subjects</div>
              <div style="font-size:20px; font-weight:800;"><?= (int)$total_subjects ?></div>
              <div style="font-size:12px; opacity:.7;">Total Subjects in Class</div>
            </div>
          </div>
        </div>
        <div class="card">
          <div style="display:flex; gap:10px; align-items:center;">
            <i class="mdi mdi-file-document-edit" style="font-size:26px; color:var(--primary);"></i>
            <div>
              <div style="font-size:12px; opacity:.7;">Assignments</div>
              <div style="font-size:20px; font-weight:800;"><?= (int)$total_assignments ?></div>
              <div style="font-size:12px; opacity:.7;">Total Assignments</div>
            </div>
          </div>
        </div>
        <div class="card">
          <div style="display:flex; gap:10px; align-items:center;">
            <i class="mdi mdi-clipboard-text" style="font-size:26px; color:var(--primary);"></i>
            <div>
              <div style="font-size:12px; opacity:.7;">Tests</div>
              <div style="font-size:20px; font-weight:800;"><?= (int)$total_tests ?></div>
              <div style="font-size:12px; opacity:.7;">Total Tests</div>
            </div>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="grid-2" style="grid-template-columns: 2fr 1fr;">
          <div class="card" style="padding:0;">
            <div style="padding:18px;">
              <div class="sec-header">
                <h3 style="margin:0;">Class Subjects</h3>
                <a href="../pages/courses.php" style="text-decoration:none; color:var(--primary);">See all</a>
              </div>
            </div>
            <div class="grid-3" style="padding:0 18px 18px;">
              <?php if ($subjects): foreach ($subjects as $s): ?>
                <div class="course">
                  <div>
                    <div class="title"><?= htmlspecialchars($s['subject_name']) ?></div>
                    <div style="opacity:.7; font-size:12px;">Code: <?= htmlspecialchars($s['subject_code']) ?></div>
                  </div>
                  <a class="pill" href="lesson.php?subject_id=<?= (int)$s['id'] ?>">View</a>
                </div>
              <?php endforeach; else: ?>
                <div class="card" style="grid-column:1/-1;">No subjects found for your class.</div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card">
            <div class="sec-header">
              <h4 style="margin:0;">Class Teachers</h4>
              <span style="font-size:12px; opacity:.7;">Class: <?= htmlspecialchars($class_title) ?></span>
            </div>
            <div class="notice">
              <?php if ($teachers): foreach ($teachers as $t): $name = trim(($t['first_name'] ?? '').' '.($t['last_name'] ?? '')); ?>
                <div class="inst">
                  <div class="avatar" style="background:#dde2ff;">
                    <?php if (!empty($t['profile_image'])): ?>
                      <img src="<?= htmlspecialchars($t['profile_image']) ?>" alt="photo" style="width:100%; height:100%; object-fit:cover; border-radius:50%;" onerror="this.parentNode.textContent='<?= initials($t['first_name']??'', $t['last_name']??'') ?>'" />
                    <?php else: echo htmlspecialchars(initials($t['first_name']??'', $t['last_name']??'')); endif; ?>
                  </div>
                  <div>
                    <div style="font-weight:700; color:#1f2b6c;"><?= htmlspecialchars($name ?: 'Teacher') ?></div>
                    <div style="font-size:12px; opacity:.7;">Teacher</div>
                  </div>
                </div>
              <?php endforeach; else: ?>
                <div class="notice-item">No teachers assigned to this class.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="grid-2" style="grid-template-columns: 2fr 1fr;">
          <div class="card">
            <div class="sec-header">
              <h3 style="margin:0;">Learning resources</h3>
              <a href="../pages/resources.php" style="text-decoration:none; color:var(--primary);">See all</a>
            </div>
            <div class="grid-3">
              <?php if ($resources): foreach ($resources as $r): ?>
                <div class="res-card">
                  <div>
                    <div style="font-weight:700; color:#1f2b6c;"><?= htmlspecialchars($r['title']) ?></div>
                    <div style="font-size:12px; opacity:.7;">Type: <?= htmlspecialchars($r['resource_type']) ?></div>
                  </div>
                  <div class="pill"><?= htmlspecialchars(date('M j', strtotime($r['created_at'] ?? 'now'))) ?></div>
                </div>
              <?php endforeach; else: ?>
                <div class="card" style="grid-column:1/-1;">No learning resources uploaded yet.</div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card">
            <div class="sec-header">
              <h4 style="margin:0;">Daily notice</h4>
              <a href="../pages/announcements.php" style="text-decoration:none; color:var(--primary);">See all</a>
            </div>
            <div class="notice">
              <?php if ($announcements): foreach ($announcements as $a): ?>
                <div class="notice-item">
                  <div style="font-weight:700; color:#1f2b6c;"><?= htmlspecialchars($a['title']) ?></div>
                  <div style="font-size:12px; opacity:.8; margin-top:4px;"><?= nl2br(htmlspecialchars(mb_strimwidth($a['content'], 0, 140, '...'))) ?></div>
                  <div style="font-size:11px; opacity:.6; margin-top:6px;"><?= htmlspecialchars(date('M j, Y', strtotime($a['created_at'] ?? 'now'))) ?></div>
                </div>
              <?php endforeach; else: ?>
                <div class="notice-item">No announcements right now.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

    </main>
  </div>
  <!-- Mobile fixed bottom navigation -->
  <nav class="bottom-nav" aria-label="Primary">
    <div class="wrap">
      <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>" title="Dashboard">
        <i class="mdi mdi-view-dashboard"></i>
        <span>Home</span>
      </a>
      <a href="index.php" class="<?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>" title="Subjects">
        <i class="mdi mdi-book-open-page-variant"></i>
        <span>Subjects</span>
      </a>
      <a href="assignments.php" class="<?= basename($_SERVER['PHP_SELF'])==='assignments.php'?'active':'' ?>" title="Assignments">
        <i class="mdi mdi-file-document-edit"></i>
        <span>Tasks</span>
      </a>
      <a href="assessments.php" class="<?= basename($_SERVER['PHP_SELF'])==='assessments.php'?'active':'' ?>" title="Exams">
        <i class="mdi mdi-clipboard-text-outline"></i>
        <span>Exams</span>
      </a>
      <a href="resources.php" class="<?= basename($_SERVER['PHP_SELF'])==='resources.php'?'active':'' ?>" title="Resources">
        <i class="mdi mdi-folder-multiple"></i>
        <span>Resources</span>
      </a>
    </div>
  </nav>
  <script>
    // Close sidebar on ESC and when clicking any sidebar link (mobile)
    (function(){
      document.addEventListener('keyup', function(e){ if(e.key === 'Escape'){ document.body.classList.remove('sidebar-open'); }});
      var sidebar = document.querySelector('.sidebar');
      if(sidebar){
        sidebar.addEventListener('click', function(e){
          var a = e.target.closest('a');
          if(a){ document.body.classList.remove('sidebar-open'); }
        });
      }
    })();
  </script>
  <?php include __DIR__ . '/components/footer.php'; ?>
</body>
</html>
