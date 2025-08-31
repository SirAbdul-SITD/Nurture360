<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject_id'])) {
  $_SESSION['subject_id'] = intval($_POST['subject_id']); //store session
  $_SESSION['class_id'] = intval($_POST['class_id']); //store session
  $_SESSION['subject_title'] = $_POST['subject_title']; //store session
  $_SESSION['class_title'] = $_POST['class_title']; //store session
  $_SESSION['num_lessons'] = intval($_POST['num_lessons']); //store session
  $_SESSION['button_color'] = $_POST['button_color'];

  header("Location: lessons.php"); //redirect to avoid form resubmission
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
$btnColor = $theme_primary_color ?? '#6c757d';
$exams_type = 'Third Term';
?>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Lessons - Nuture 360&deg; | Learning made simple</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="assets/vendors/iconfonts/mdi/css/materialdesignicons.css" />
  <link rel="stylesheet" href="assets/vendors/css/vendor.addons.css" />
  <!-- endinject -->
  <!-- vendor css for this page -->
  <!-- End vendor css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="assets/css/shared/style.css" />
  <!-- endinject -->
  <!-- Layout style -->
  <link rel="stylesheet" href="assets/css/demo_1/style.css" />
  <!-- Layout style -->
  <link rel="shortcut icon" href="<?= htmlspecialchars($favicon_url) ?>" />
  

  <style>
    table th,
    table td { text-align: left !important; }
    a { text-decoration: none; }

    /* Dynamic theme bindings (from index.php) */
    :root {
      --theme-primary: <?= htmlspecialchars($theme_primary_color) ?>;
      --theme-secondary: <?= htmlspecialchars($theme_secondary_color) ?>;
      --theme-accent: <?= htmlspecialchars($theme_accent_color) ?>;
    }
    .text-primary, .text-success { color: var(--theme-primary) !important; }
    .text-accent { color: var(--theme-accent) !important; }
    .theme-text { color: var(--theme-primary) !important; }
    a { color: var(--theme-primary); }
    a:hover { color: var(--theme-accent); }
    .btn-primary, .btn-success { background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; color: #fff !important; }
    .btn-outline-primary { color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
    .btn-outline-primary:hover { background-color: var(--theme-primary) !important; color: #fff !important; }
    .badge-primary, .badge-success { background-color: var(--theme-primary) !important; }
    .card-title { color: var(--theme-primary) !important; }
    .card.border-primary { border-color: var(--theme-primary) !important; }
    .card .card-header { border-bottom-color: var(--theme-primary) !important; }
    .card.theme-accent-border { border-top: 3px solid var(--theme-accent); }
    .t-header .nav .nav-link.active,
    .t-header .nav .nav-link:focus,
    .t-header .nav .nav-link:hover { color: var(--theme-primary) !important; }
    .t-header .nav .nav-link.active { position: relative; }
    .t-header .nav .nav-link.active::after { content: ''; position: absolute; left: 0; right: 0; bottom: -8px; height: 3px; background: var(--theme-primary); border-radius: 2px; }
    .sidebar .navigation-menu li.active > a,
    .sidebar .navigation-menu li > a:hover { color: var(--theme-primary) !important; }
    .sidebar .navigation-menu li.active > a .link-icon { color: var(--theme-primary); }
    .sidebar .navigation-menu li.active,
    .sidebar .navigation-menu li.active > a { background: rgba(0,0,0,0.03); border-left: 3px solid var(--theme-primary); }
    h1, h2, h3, h4, h5, h6 { color: var(--theme-primary); }
    .page-title, .section-title { color: var(--theme-primary) !important; }
    .breadcrumb .active { color: var(--theme-primary) !important; }
    a.active, .link-active { color: var(--theme-primary) !important; }
    .nav-tabs .nav-link.active, .nav-pills .nav-link.active { color: #fff !important; background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
    .nav-tabs .nav-link:hover { border-color: var(--theme-primary) !important; }
    .pagination .page-item.active .page-link { background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; color: #fff !important; }
    .form-control:focus, .custom-select:focus { border-color: var(--theme-primary) !important; box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.03), 0 0 0 0.1rem var(--theme-primary) !important; }
    .custom-control-input:checked ~ .custom-control-label::before { color: #fff; border-color: var(--theme-primary); background-color: var(--theme-primary); }
  </style>
</head>

<body class="header-fixed">
  <!-- partial:../../partials/_header.html (shared from index.php) -->
  <nav class="t-header">
    <div class="t-header-brand-wrapper">
      <a href="index.php" class="d-flex align-items-center" style="text-decoration:none; gap: .5rem;">
        <?php if (!empty($show_logo)): ?>
          <img class="logo" src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($app_name) ?>" onerror="this.onerror=null;this.style.display='none';" />
        <?php endif; ?>
        <?php if (!empty($show_name)): ?>
          <span class="h4 mb-0" style="font-weight:700; font-size:1.81rem; color: <?= htmlspecialchars($theme_primary_color) ?>;">&lrm;<?= htmlspecialchars($app_name) ?></span>
        <?php endif; ?>
      </a>
    </div>
    <div class="t-header-content-wrapper">
      <div class="t-header-content">
        <button class="t-header-toggler t-header-mobile-toggler d-block d-lg-none">
          <i class="mdi mdi-menu"></i>
        </button>
        <form action="#" class="t-header-search-box">
          <div class="input-group h-2">
            <input type="text" class="form-control h-4" id="inlineFormInputGroup" placeholder="Search" autocomplete="off" />
            <button class="btn btn-success" type="submit">
              <i class="mdi mdi-arrow-right-thick"></i>
            </button>
          </div>
        </form>
        <ul class="nav ml-auto">
          <li class="nav-item dropdown">
            <a class="nav-link" href="#" id="notificationDropdown" data-toggle="dropdown" aria-expanded="false">
              <div class="btn action-btn btn-success btn-rounded component-flan">
                <i class="mdi mdi-bell-outline mdi-1x text-white"></i>
              </div>
            </a>
            <div class="dropdown-menu navbar-dropdown dropdown-menu-right" aria-labelledby="notificationDropdown">
              <div class="dropdown-header">
                <h6 class="dropdown-title">Notifications</h6>
                <p class="dropdown-title-text">You have 4 unread notification</p>
              </div>
              <div class="dropdown-body">
                <div class="dropdown-list">
                  <div class="icon-wrapper rounded-circle bg-inverse-success text-success"><i class="mdi mdi-cloud-upload"></i></div>
                  <div class="content-wrapper"><small class="name">Upload Completed</small><small class="content-text">3 Files uploaded successfully</small></div>
                </div>
                <div class="dropdown-list">
                  <div class="icon-wrapper rounded-circle bg-inverse-success text-success"><i class="mdi mdi-cloud-upload"></i></div>
                  <div class="content-wrapper"><small class="name">Upload Completed</small><small class="content-text">3 Files uploded successfully</small></div>
                </div>
                <div class="dropdown-list">
                  <div class="icon-wrapper rounded-circle bg-inverse-warning text-warning"><i class="mdi mdi-security"></i></div>
                  <div class="content-wrapper"><small class="name">Authentication Required</small><small class="content-text">Please verify your password to continue using cloud services</small></div>
                </div>
              </div>
              <div class="dropdown-footer"><a href="#">View All</a></div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- partial -->
  <div class="page-body">
    <!-- partial:../../partials/_sidebar.html (shared from index.php) -->
    <div class="sidebar">
      <div class="user-profile">
        <div class="display-avatar animated-avatar">
          <img class="profile-img img-lg rounded-circle" src="<?= htmlspecialchars($profile_image_url) ?>" alt="profile image" onerror="this.onerror=null;this.src='assets/images/profile/male/1.jpg';" />
        </div>
        <div class="info-wrapper">
          <p class="user-name"><?= $first_name ?> <?= $last_name ?></p>
          <h6 class="display-income"><?= $my_class_Title ?></h6>
        </div>
      </div>
      <ul class="navigation-menu">
        <li class="nav-category-divider">MAIN</li>
        <li>
          <a href="index.php">
            <span class="link-title">My Subjects</span>
            <i class="mdi mdi-gauge link-icon"></i>
          </a>
        </li>
        <li>
          <a href="#">
            <span class="link-title">Profile</span>
            <i class="mdi mdi-asterisk link-icon"></i>
          </a>
        </li>
        <li>
          <a href="#">
            <span class="link-title">Documentation</span>
            <i class="mdi mdi-asterisk link-icon"></i>
          </a>
        </li>
      </ul>
    </div>
    <!-- partial -->
    <div class="page-content-wrapper">
      <div class="page-content-wrapper-inner">
        <div class="content-viewport">
          <?php
          $subject_id = isset($_SESSION['subject_id']) ? (int)$_SESSION['subject_id'] : 0;

          $subject_title = isset($_SESSION['subject_title']) ?
            $_SESSION['subject_title'] : '';

          $class_title = isset($_SESSION['class_title']) ?
            $_SESSION['class_title'] : '';

          $num_lessons = isset($_SESSION['num_lessons']) ? (int)$_SESSION['num_lessons'] : 0; // fallback only

          $class_id = isset($_SESSION['class_id']) ? (int)$_SESSION['class_id'] : 0;

          // Guard: if required filters are missing, go back to subjects
          if ($subject_id === 0 || $class_id === 0) {
            header('Location: index.php');
            exit();
          }

          // Determine available timestamp column (parity with supervisor view)
          $colsStmt = $pdo->query("SHOW COLUMNS FROM lessons");
          $cols = $colsStmt ? $colsStmt->fetchAll() : [];
          $colNames = array_map(function($c){ return $c['Field'] ?? ''; }, $cols);
          $hasUpdatedOn = in_array('updated_on', $colNames, true);
          $hasCreatedAt = in_array('created_at', $colNames, true);
          $hasUpdatedWithSpace = in_array('updated_ on', $colNames, true); // legacy typo
          $updatedExpr = $hasUpdatedOn ? 'updated_on' : ($hasCreatedAt ? 'created_at' : ($hasUpdatedWithSpace ? '`updated_ on`' : 'NULL'));

          // Fetch lessons matching supervisor layout ordering and fields
          $sql = "SELECT 
                      lesson_id, lesson_number, title, description, vocabulary, content, thumbnail, video,
                      $updatedExpr AS updated_on,
                      subject_id, class_id
                    FROM lessons
                    WHERE subject_id = :sid AND class_id = :cid
                    ORDER BY lesson_number IS NULL, lesson_number, lesson_id DESC";

          $stmt = $pdo->prepare($sql);
          $stmt->execute([':sid' => $subject_id, ':cid' => $class_id]);

          // Live DB count to display accurate number of lessons
          $stmtCount = $pdo->prepare('SELECT COUNT(*) AS c FROM lessons WHERE subject_id = :sid AND class_id = :cid');
          $stmtCount->execute([':sid' => $subject_id, ':cid' => $class_id]);
          $rowCount = $stmtCount->fetch(PDO::FETCH_ASSOC);
          $num_lessons_db = isset($rowCount['c']) ? (int)$rowCount['c'] : 0;
          ?>

          <?php
          // Detect if tests table supports lesson-scoped tests
          $testsHasLessonId = false;
          try {
            $tcols = $pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tcols as $c) { if (($c['Field'] ?? '') === 'lesson_id') { $testsHasLessonId = true; break; } }
          } catch (Throwable $e) { $testsHasLessonId = false; }
          ?>


          <div class="row">
            <div class="col-12 py-5">
              <a href="index.php">
                <h4 style="color: <?= htmlspecialchars($btnColor) ?>;"><?= $subject_title ?></h4>
              </a>
              <p class="text-gray"><?= $class_title ?> | Lessons: <?= (int)$num_lessons_db ?></p>
            </div>
          </div>


          <div class="row" id="subjectGrid">
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
              <!-- Lesson Card (Supervisor-like UI) -->
              <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="profile-card" style="border:1px solid #e5e7eb; box-shadow:0 6px 16px rgba(0,0,0,.08); border-radius:12px; background:#fff; overflow:hidden;">
                  <!-- Top Thumbnail -->
                  <div class="lesson-thumb" style="width:100%; height:160px; border-radius:12px; overflow:hidden; background:#f3f4f6; display:block; position:relative;">
                    <?php
                      // Thumbnail logic: prioritize lesson thumbnail, fallback to YouTube thumbnail
                      $thumbnailSrc = null;
                      $thumbnailAlt = 'Lesson Thumbnail';
                      
                      if (!empty($row['thumbnail'])) {
                        // Lesson has custom thumbnail
                        $thumbnailSrc = (strpos($row['thumbnail'],'http')===0) ? $row['thumbnail'] : '../uploads/resources/'.basename($row['thumbnail']);
                        $thumbnailAlt = 'Lesson Thumbnail';
                      } elseif (!empty($row['video'])) {
                        // No thumbnail but has video - try to extract YouTube thumbnail
                        $videoUrl = trim($row['video']);
                        $youtubeId = null;
                        
                        // Extract YouTube video ID from various URL formats
                        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([\w-]{11})~i', $videoUrl, $matches)) {
                          $youtubeId = $matches[1];
                          $thumbnailSrc = "https://img.youtube.com/vi/{$youtubeId}/maxresdefault.jpg";
                          $thumbnailAlt = 'YouTube Video Thumbnail';
                        }
                      }
                    ?>
                    
                    <?php if ($thumbnailSrc): ?>
                      <img src="<?= htmlspecialchars($thumbnailSrc) ?>" 
                           alt="<?= htmlspecialchars($thumbnailAlt) ?>" 
                           style="width:100%; height:100%; object-fit:cover; display:block;"
                           onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                      <div style="width:100%; height:100%; display:none; align-items:center; justify-content:center; color:#9ca3af; font-weight:600; background:#f3f4f6;">No Image</div>
                    <?php else: ?>
                      <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-weight:600;">No Image</div>
                    <?php endif; ?>
                    <?php
                      // Inline compute to show overlay timer on thumbnail (lesson-scoped preferred)
                      $thumbLive = null; $thumbEndTs = null; $nowTsTmp = time();
                      try {
                        if ($testsHasLessonId) {
                          $sqlLive2 = "SELECT id, title, lesson_id, scheduled_date, start_time, end_time FROM tests
                                       WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1
                                         AND (lesson_id IS NULL OR lesson_id = :lid)
                                       ORDER BY (lesson_id IS NULL) ASC, scheduled_date DESC, start_time ASC
                                       LIMIT 5";
                          $stLive2 = $pdo->prepare($sqlLive2);
                          $stLive2->execute([':cid'=>$class_id, ':sid'=>$subject_id, ':lid'=>$row['lesson_id']]);
                          $cands2 = $stLive2->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($cands2 as $c2) {
                            $s2 = @strtotime(($c2['scheduled_date']??'').' '.($c2['start_time']??''));
                            $e2 = @strtotime(($c2['scheduled_date']??'').' '.($c2['end_time']??''));
                            if ($s2 && $e2 && $nowTsTmp >= $s2 && $nowTsTmp <= $e2) { $thumbLive = $c2; $thumbEndTs = $e2; break; }
                          }
                        } else {
                          $sqlLive2 = "SELECT id, title, scheduled_date, start_time, end_time FROM tests
                                       WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1
                                       ORDER BY scheduled_date DESC, start_time ASC
                                       LIMIT 5";
                          $stLive2 = $pdo->prepare($sqlLive2);
                          $stLive2->execute([':cid'=>$class_id, ':sid'=>$subject_id]);
                          $cands2 = $stLive2->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($cands2 as $c2) {
                            $s2 = @strtotime(($c2['scheduled_date']??'').' '.($c2['start_time']??''));
                            $e2 = @strtotime(($c2['scheduled_date']??'').' '.($c2['end_time']??''));
                            if ($s2 && $e2 && $nowTsTmp >= $s2 && $nowTsTmp <= $e2) { $thumbLive = $c2; $thumbEndTs = $e2; break; }
                          }
                        }
                      } catch (Throwable $e) { $thumbLive = null; $thumbEndTs = null; }
                    ?>
                    <?php if (!empty($thumbEndTs) && $nowTsTmp < $thumbEndTs): ?>
                      <div data-role="countdown" data-end="<?= (int)$thumbEndTs ?>" style="
                            position:absolute; top:8px; right:8px; z-index:2;
                            display:inline-flex; align-items:center; gap:6px;
                            padding:6px 10px; border-radius:999px; font-weight:600; font-size:12px;
                            background-color: rgba(231, 76, 60, 0.9); color:#fff;">
                        <i class="mdi mdi-timer-outline" style="font-size:16px; line-height:1;"></i>
                        <span class="cd-time">--:--:--</span>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Title & meta -->
                  <!-- Title & meta removed as requested -->
                 
                 
                    
                  <div style="margin-top:6px; padding:0 12px; display:none;"></div>
                  <h5 style="margin: 6px 0; padding:0 12px; "><?= htmlspecialchars($row['lesson_number'] ?? '') ?></h5>

                  <div class="profile-meta" style="padding:12px;">
                    <div style="font-size:0.92rem;color:#555;max-height:4.5em;overflow:hidden; white-space: normal; word-break: break-word; overflow-wrap: anywhere;">
                      <?= substr(strip_tags($row['description'] ?? ''), 0, 260) ?>
                    </div>
                   
                    <?php
                      // Compute LIVE test for this specific lesson card using PHP time
                      $cardLiveTest = null;
                      $nowTs = time();
                      try {
                        if ($testsHasLessonId) {
                          // Fetch a few candidates: prefer lesson-scoped first, then subject-wide
                          $sqlLive = "SELECT id, title, lesson_id, scheduled_date, start_time, end_time FROM tests
                                      WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1
                                        AND (lesson_id IS NULL OR lesson_id = :lid)
                                      ORDER BY (lesson_id IS NULL) ASC, scheduled_date DESC, start_time ASC
                                      LIMIT 5";
                          $stLive = $pdo->prepare($sqlLive);
                          $stLive->execute([':cid'=>$class_id, ':sid'=>$subject_id, ':lid'=>$row['lesson_id']]);
                          $cands = $stLive->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($cands as $cand) {
                            $sTs = @strtotime(($cand['scheduled_date']??'').' '.($cand['start_time']??''));
                            $eTs = @strtotime(($cand['scheduled_date']??'').' '.($cand['end_time']??''));
                            if ($sTs && $eTs && $nowTs >= $sTs && $nowTs <= $eTs) { $cardLiveTest = $cand; break; }
                          }
                        } else {
                          // Subject-wide only
                          $sqlLive = "SELECT id, title, scheduled_date, start_time, end_time FROM tests
                                      WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1
                                      ORDER BY scheduled_date DESC, start_time ASC
                                      LIMIT 5";
                          $stLive = $pdo->prepare($sqlLive);
                          $stLive->execute([':cid'=>$class_id, ':sid'=>$subject_id]);
                          $cands = $stLive->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($cands as $cand) {
                            $sTs = @strtotime(($cand['scheduled_date']??'').' '.($cand['start_time']??''));
                            $eTs = @strtotime(($cand['scheduled_date']??'').' '.($cand['end_time']??''));
                            if ($sTs && $eTs && $nowTs >= $sTs && $nowTs <= $eTs) { $cardLiveTest = $cand; break; }
                          }
                        }
                      } catch (Throwable $e) { $cardLiveTest = null; }

                      // 2-attempt policy for this specific live test (only for the exact test_id)
                      $lessonAttempts = 0; $lessonLatest = null;
                      if (!empty($cardLiveTest) && !empty($cardLiveTest['id'])) {
                        try {
                          $stA = $pdo->prepare("SELECT tr.id AS result_id, tr.submitted_at
                                                FROM test_results tr
                                                WHERE tr.student_id = :sid AND tr.test_id = :tid
                                                ORDER BY tr.submitted_at DESC
                                                LIMIT 2");
                          $stA->execute([':sid' => (int)($_SESSION['student_id'] ?? 0), ':tid' => (int)$cardLiveTest['id']]);
                          $rowsA = $stA->fetchAll(PDO::FETCH_ASSOC);
                          $lessonAttempts = count($rowsA);
                          $lessonLatest = $rowsA[0] ?? null;
                        } catch (Throwable $e) { $lessonAttempts = 0; $lessonLatest = null; }
                      }

                      // Countdown to test end if live
                      $cardEndTs = null;
                      if (!empty($cardLiveTest)) {
                        $cardEndTs = @strtotime(($cardLiveTest['scheduled_date']??'').' '.($cardLiveTest['end_time']??''));
                      }
                    ?>
                    
                    <div style="margin-top:10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                      <form id="form-<?= (int)$row['lesson_id']; ?>" action="lesson.php" method="POST" style="flex:1;">
                        <input type="hidden" name="subject_id" value="<?= (int)$row['subject_id']; ?>">
                        <input type="hidden" name="lesson_id" value="<?= (int)$row['lesson_id']; ?>">
                        <input type="hidden" name="class_id" value="<?= (int)$row['class_id']; ?>">
                        <input type="hidden" name="lesson_title" value="<?= htmlspecialchars($row['title'] ?? '') ; ?>">
                        <input type="hidden" name="subject_title" value="<?= htmlspecialchars($subject_title); ?>">
                        <input type="hidden" name="class_title" value="<?= htmlspecialchars($class_title); ?>">
                        <input type="hidden" name="content" value='<?= htmlspecialchars($row['content'] ?? "", ENT_QUOTES); ?>'>
                        <input type="hidden" name="video" value="<?= htmlspecialchars($row['video'] ?? ''); ?>">
                        <input type="hidden" name="thumbnail" value="<?= htmlspecialchars($row['thumbnail'] ?? ''); ?>">
                        <input type="hidden" name="button_color" value="<?= htmlspecialchars($btnColor); ?>">
                        <button type="submit" class="btn has-icon" style="
                              background-color: <?= htmlspecialchars($btnColor) ?>;
                              border-color:     <?= htmlspecialchars($btnColor) ?>;
                              color:            #fff; width:100%;">
                          View Details
                          <i class="mdi mdi-arrow-right-thick m-0 pl-2"></i>
                        </button>
                      </form>
                      <?php if (!empty($cardLiveTest) && !empty($cardLiveTest['id'])): ?>
                        <?php
                          $twoDays = 2 * 24 * 60 * 60;
                          $now = time();
                          if ($lessonAttempts >= 2) {
                            $lastAt = isset($lessonLatest['submitted_at']) ? strtotime($lessonLatest['submitted_at']) : 0;
                            if ($lastAt && ($now - $lastAt) <= $twoDays): ?>
                              <a href="result.php?assessment_id=<?= (int)$lessonLatest['result_id']; ?>" class="btn has-icon" style="background-color: <?= htmlspecialchars($btnColor) ?>; border-color: <?= htmlspecialchars($btnColor) ?>; color:#fff; flex:1; display:block; width:100%; white-space:nowrap;">
                                View Test Result
                                <i class="mdi mdi-eye m-0 pl-2"></i>
                              </a>
                            <?php endif; ?>
                        <?php } elseif ($lessonAttempts === 1) { ?>
                          <form action="student-assessments.php" method="POST" style="flex:1;">
                            <input type="hidden" name="test_id" value="<?= (int)$cardLiveTest['id']; ?>">
                            <input type="hidden" name="lesson_id" value="<?= (int)$row['lesson_id']; ?>">
                            <button type="submit" class="btn has-icon" style="background-color: <?= htmlspecialchars($btnColor) ?>; border-color: <?= htmlspecialchars($btnColor) ?>; color:#fff; width:100%; white-space:nowrap;">
                              Retake Test
                              <i class="mdi mdi-reload m-0 pl-2"></i>
                            </button>
                          </form>
                        <?php } else { ?>
                          <form action="student-assessments.php" method="POST" style="flex:1;">
                            <input type="hidden" name="test_id" value="<?= (int)$cardLiveTest['id']; ?>">
                            <input type="hidden" name="lesson_id" value="<?= (int)$row['lesson_id']; ?>">
                            <button type="submit" class="btn has-icon" style="background-color: <?= htmlspecialchars($btnColor) ?>; border-color: <?= htmlspecialchars($btnColor) ?>; color:#fff; width:100%; white-space:nowrap;">
                              Take Test
                              <i class="mdi mdi-play m-0 pl-2"></i>
                            </button>
                          </form>
                        <?php } ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>


          <?php
          // Check if student has already taken this exam
          $sql = "SELECT exam_assessment_id FROM exam_assessments 
        WHERE student_id = :student_id AND subject_id = :subject_id AND class_id = :class_id 
        LIMIT 1";
          $stmt = $pdo->prepare($sql);
          $stmt->execute([
            'student_id' => $_SESSION['student_id'],
            'subject_id' => $_SESSION['subject_id'],
            'class_id' => $_SESSION['class_id']
          ]);

          $existingAssessment = $stmt->fetch(PDO::FETCH_ASSOC);
          ?>

          <?php if ($existingAssessment): ?>
            <!-- Exam already taken - Show result button -->
            <a href="exam-result.php?assessment_id=<?= $existingAssessment['exam_assessment_id']; ?>"
              class="col-12 mt-5 center has-icon btn btn-lg"
              style="background-color: <?= htmlspecialchars($btnColor); ?>; border-color: <?= htmlspecialchars($btnColor); ?>; color: #fff;">
              View Exam Results
              <i class="mdi mdi-eye m-0 pl-2"></i>
            </a>
          <?php else: ?>
            <!-- Exam not taken yet - Show exam start form -->
            <form id="exm" action="i-exams.php" method="post">
              <input type="hidden" name="subject_id" value="<?= $_SESSION['subject_id']; ?>">
              <input type="hidden" name="class_id" value="<?= $_SESSION['class_id']; ?>">
              <input type="hidden" name="subject_title" value="<?= $subject_title; ?>">
              <input type="hidden" name="class_title" value="<?= $class_title; ?>">
              <input type="hidden" name="button_color" value="<?= htmlspecialchars($btnColor); ?>">

              <button class="col-12 mt-5 center has-icon btn btn-lg" type="button" data-toggle="modal"
                data-target="#exmModal"
                style="background-color: <?= htmlspecialchars($btnColor); ?>; border-color: <?= htmlspecialchars($btnColor); ?>; color: #fff;">
                Take Exam
                <i class="mdi mdi-pen m-0 pl-2"></i>
              </button>
            </form>
          <?php endif; ?>


          <!-- Exam Start Confirmation Modal -->
          <div class="modal fade" id="exmModal" tabindex="-1" aria-labelledby="exmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exmModalLabel">Confirm Start</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  Are you sure you want to start the Third Term Examination now?
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" form="exm" class="btn" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">Yes, Start Exam</button>
                </div>
              </div>
            </div>
          </div>



          <footer class="footer">
            <div class="row">
              <div class="col-sm-6 text-center text-sm-right order-sm-1">
                <ul class="text-gray">
                  <li><a href="#">Terms of use</a></li>
                  <li><a href="#">Privacy Policy</a></li>
                </ul>
              </div>
              <div class="col-sm-6 text-center text-sm-left mt-3 mt-sm-0">
                <small class="text-muted d-block">Copyright © 2025 <a href="#" target="_blank"><?= htmlspecialchars($app_name) ?></a>. All
                  rights reserved</small>
                <small class="text-gray mt-2">Handcrafted With <i class="mdi mdi-heart text-danger"></i></small>
              </div>
            </div>
          </footer>
        </div>
      </div>
    </div>



    <!-- content viewport ends -->
    <!-- partial:../../partials/_footer.html -->

    <!-- partial -->
  </div>
  <!-- page content ends -->
  </div>
  <!--page body ends -->
  <!-- SCRIPT LOADING START FORM HERE /////////////-->
  <!-- plugins:js -->
  <script src="assets/vendors/js/core.js"></script>
  <script src="assets/vendors/js/vendor.addons.js"></script>



  <!-- endinject -->
  <!-- Vendor Js For This Page Ends-->
  <!-- Vendor Js For This Page Ends-->
  <!-- build:js -->
  <script src="template.js"></script>

  <!-- endbuild -->
  <script>
    (function(){
      if (window.__lessonCountdownInit) return; // guard
      window.__lessonCountdownInit = true;

      function pad(n){ return (n<10?'0':'')+n; }
      function fmt(ms){
        if (ms <= 0) return '00:00:00';
        var total = Math.floor(ms/1000);
        var h = Math.floor(total/3600);
        var m = Math.floor((total%3600)/60);
        var s = total%60;
        return pad(h)+':'+pad(m)+':'+pad(s);
      }

      function tick(){
        var now = Date.now();
        var nodes = document.querySelectorAll('[data-role="countdown"]');
        nodes.forEach(function(node){
          var end = parseInt(node.getAttribute('data-end'), 10) * 1000; // php time() -> seconds
          var span = node.querySelector('.cd-time');
          if (!span || !end) return;
          var left = end - now;
          span.textContent = fmt(left);
          if (left <= 0) {
            // Freeze at zero and optionally style as ended
            node.style.opacity = '0.7';
            node.style.backgroundColor = 'rgba(108,117,125,0.12)';
            node.style.color = '#6c757d';
          }
        });
      }

      tick();
      setInterval(tick, 1000);
    })();
  </script>
</body>

</html>