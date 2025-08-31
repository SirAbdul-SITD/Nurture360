<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject_id'])) {
  $_SESSION['subject_id'] = intval($_POST['subject_id']); // store session
  $_SESSION['class_id'] = intval($_POST['class_id']); // store session
  $_SESSION['subject_title'] = $_POST['subject_title']; // store session
  $_SESSION['class_title'] = $_POST['class_title']; // store session
  $_SESSION['num_lessons'] = intval($_POST['num_lessons']); // store session
  header("Location: lessons.php"); // redirect to lessons listing for the chosen subject/class
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

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
    table td {
      text-align: left !important;
    }
    /* Dynamic theme bindings */
    :root {
      --theme-primary: <?= htmlspecialchars($theme_primary_color) ?>;
      --theme-secondary: <?= htmlspecialchars($theme_secondary_color) ?>;
      --theme-accent: <?= htmlspecialchars($theme_accent_color) ?>;
    }
    /* Text helpers */
    .text-primary, .text-success { color: var(--theme-primary) !important; }
    .text-accent { color: var(--theme-accent) !important; }
    .theme-text { color: var(--theme-primary) !important; }
    /* Links */
    a { color: var(--theme-primary); }
    a:hover { color: var(--theme-accent); }
    /* Buttons */
    .btn-primary, .btn-success {
      background-color: var(--theme-primary) !important;
      border-color: var(--theme-primary) !important;
      color: #fff !important;
    }
    .btn-outline-primary {
      color: var(--theme-primary) !important;
      border-color: var(--theme-primary) !important;
    }
    .btn-outline-primary:hover { background-color: var(--theme-primary) !important; color: #fff !important; }
    /* Badges/labels */
    .badge-primary, .badge-success { background-color: var(--theme-primary) !important; }
    /* Cards */
    .card-title { color: var(--theme-primary) !important; }
    .card.border-primary { border-color: var(--theme-primary) !important; }
    .card .card-header { border-bottom-color: var(--theme-primary) !important; }
    .card.theme-accent-border { border-top: 3px solid var(--theme-accent); }

    /* Navbar active link */
    .t-header .nav .nav-link.active,
    .t-header .nav .nav-link:focus,
    .t-header .nav .nav-link:hover {
      color: var(--theme-primary) !important;
    }
    /* Optional underline/border for active top nav items */
    .t-header .nav .nav-link.active {
      position: relative;
    }
    .t-header .nav .nav-link.active::after {
      content: '';
      position: absolute; left: 0; right: 0; bottom: -8px; height: 3px;
      background: var(--theme-primary);
      border-radius: 2px;
    }

    /* Sidebar active item */
    .sidebar .navigation-menu li.active > a,
    .sidebar .navigation-menu li > a:hover {
      color: var(--theme-primary) !important;
    }
    .sidebar .navigation-menu li.active > a .link-icon {
      color: var(--theme-primary);
    }
    .sidebar .navigation-menu li.active,
    .sidebar .navigation-menu li.active > a {
      background: rgba(0,0,0,0.03);
      border-left: 3px solid var(--theme-primary);
    }

    /* Page text and headings */
    h1, h2, h3, h4, h5, h6 { color: var(--theme-primary); }
    .page-title, .section-title { color: var(--theme-primary) !important; }
    .breadcrumb .active { color: var(--theme-primary) !important; }
    a.active, .link-active { color: var(--theme-primary) !important; }

    /* Tabs & Pills active */
    .nav-tabs .nav-link.active, .nav-pills .nav-link.active {
      color: #fff !important;
      background-color: var(--theme-primary) !important;
      border-color: var(--theme-primary) !important;
    }
    .nav-tabs .nav-link:hover { border-color: var(--theme-primary) !important; }

    /* Pagination active */
    .pagination .page-item.active .page-link {
      background-color: var(--theme-primary) !important;
      border-color: var(--theme-primary) !important;
      color: #fff !important;
    }

    /* Form focus states */
    .form-control:focus, .custom-select:focus {
      border-color: var(--theme-primary) !important;
      box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.03), 0 0 0 0.1rem var(--theme-primary) !important;
    }
    .custom-control-input:checked ~ .custom-control-label::before {
      color: #fff; border-color: var(--theme-primary); background-color: var(--theme-primary);
    }
  </style>
</head>

<body class="header-fixed">
  <!-- partial:../../partials/_header.html -->
  <nav class="t-header">
    <div class="t-header-brand-wrapper">
      <a href="index.php" class="d-flex align-items-center" style="text-decoration:none; gap: .5rem;">
        <?php if (!empty($show_logo)): ?>
          <img class="logo" src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($app_name) ?>" onerror="this.onerror=null;this.style.display='none';" />
        <?php endif; ?>
        <?php if (!empty($show_name)): ?>
          <span class="h4 mb-0" style="font-weight:700; font-size:1.81rem; color: <?= htmlspecialchars($theme_primary_color) ?>;">&lrm;<?= htmlspecialchars($app_name) ?></span>
        <?php endif; ?>
        <!-- <img class="logo-mini" src="assets/images/logo_mini.svg" alt="" /> -->
      </a>
    </div>
    <div class="t-header-content-wrapper">
      <div class="t-header-content">
        <button class="t-header-toggler t-header-mobile-toggler d-block d-lg-none">
          <i class="mdi mdi-menu"></i>
        </button>
        <form action="#" class="t-header-search-box">
          <div class="input-group h-2">
            <input type="text" class="form-control h-4" id="inlineFormInputGroup" placeholder="Search"
              autocomplete="off" />
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
                <p class="dropdown-title-text">
                  You have 4 unread notification
                </p>
              </div>
              <div class="dropdown-body">
                <div class="dropdown-list">
                  <div class="icon-wrapper rounded-circle bg-inverse-success text-success">
                    <i class="mdi mdi-cloud-upload"></i>
                  </div>
                  <div class="content-wrapper">
                    <small class="name">Upload Completed</small>
                    <small class="content-text">3 Files uploaded successfully</small>
                  </div>
                </div>
                <div class="dropdown-list">
                  <div class="icon-wrapper rounded-circle bg-inverse-success text-success">
                    <i class="mdi mdi-cloud-upload"></i>
                  </div>
                  <div class="content-wrapper">
                    <small class="name">Upload Completed</small>
                    <small class="content-text">3 Files uploded successfully</small>
                  </div>
                </div>
                <div class="dropdown-list">
                  <div class="icon-wrapper rounded-circle bg-inverse-warning text-warning">
                    <i class="mdi mdi-security"></i>
                  </div>
                  <div class="content-wrapper">
                    <small class="name">Authentication Required</small>
                    <small class="content-text">Please verify your password to continue using cloud
                      services</small>
                  </div>
                </div>
              </div>
              <div class="dropdown-footer">
                <a href="#">View All</a>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- partial -->
  <div class="page-body">
    <!-- partial:partials/_sidebar.html -->
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
          $subjects = [];
          $num_subjects = 0;
          $query_error = null;

          if (empty($my_class)) {
            $query_error = 'No class is associated with your account yet.';
          } else {
            $sql = "
                  SELECT 
                    s.id   AS subject_id,
                    s.subject_name AS subject_title,
                    c.id   AS class_id,
                    c.class_name AS class_title,
                    (SELECT COUNT(*) FROM lessons l WHERE l.subject_id = cs.subject_id AND l.class_id = cs.class_id) AS num_lessons
                  FROM class_subjects cs
                  JOIN subjects s ON cs.subject_id = s.id
                  JOIN classes c  ON cs.class_id = c.id
                  WHERE cs.class_id = ?
                  ORDER BY s.subject_name ASC
                ";
            try {
              $stmt = $pdo->prepare($sql);
              $stmt->execute([$my_class]);
              $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
              $num_subjects = count($subjects);
            } catch (Throwable $e) {
              $query_error = 'Failed to load subjects for class ID ' . htmlspecialchars((string)$my_class) . '.';
            }
          }
          ?>
          <div class="row col-12 align-items-end">
            <div class="col-md-8 py-5">
              <h4>Subjects</h4>
              <p class="text-gray">Ordered by subjects | subjects: <?= (int)$num_subjects ?></p>
            </div>
            <div class="col-md-4 py-5">
              <form method="post" class="form-inline text-right">
                <label for="class_id" class="mr-2">Class</label>
                <select name="class_id" id="class_id" class="form-control" onchange="this.form.submit()">
                  <?php foreach (($available_classes ?? []) as $cls): ?>
                    <option value="<?= (int)$cls['class_id'] ?>" <?= ((int)$cls['class_id'] === (int)$my_class ? 'selected' : '') ?>>
                      <?= htmlspecialchars($cls['class_name'] ?? ('Class ' . (int)$cls['class_id'])) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>
          </div>
          <?php if ($query_error): ?>
            <div class="alert alert-warning" role="alert">
              <?= htmlspecialchars($query_error) ?>
            </div>
          <?php endif; ?>

          <div class="row" id="subjectGrid">
            <?php if ($num_subjects === 0 && !$query_error): ?>
              <div class="col-12">
                <div class="alert alert-info" role="alert">
                  No subjects found for your class (ID: <?= htmlspecialchars((string)$my_class) ?>). Please contact your school administrator.
                </div>
              </div>
            <?php endif; ?>
            <?php foreach ($subjects as $row) { ?>
              <!-- Lesson -->
              <div class="col-lg-4 col-md-6 col-sm-12 equel-grid subject-item"
                data-subject-title="<?= $row['subject_title']; ?>">
                <div class="grid">
                  <div class="grid-body">
                    <!-- Thumbnail / Banner with optional countdown overlay -->
                    <div class="mt-3" style="width:100%; height:140px; border-radius:12px; overflow:hidden; background:#f3f4f6; position:relative;">
                      <!-- No subject image available; using neutral banner -->
                      <?php
                        // Compute LIVE exam window for this subject card using exam template (student_id=0)
                        $examEndTs = null;
                        try {
                          $stTpl = $pdo->prepare('SELECT status, timespan FROM exam_assessments WHERE student_id=0 AND class_id=? AND subject_id=? ORDER BY exam_assessment_id DESC LIMIT 1');
                          $stTpl->execute([(int)$row['class_id'], (int)$row['subject_id']]);
                          $tpl = $stTpl->fetch(PDO::FETCH_ASSOC);
                          if ($tpl) {
                            $statusStr = (string)($tpl['status'] ?? '');
                            $durMin = 30; // fallback to 30 mins if not specified
                            $endTs = null; // end timestamp from status
                            
                            // Extract duration and end time from status string
                            if (preg_match('/dur=([0-9]+)/', $statusStr, $m)) { $durMin = (int)$m[1]; }
                            if (preg_match('/end=([0-9]+)/', $statusStr, $m)) { $endTs = (int)$m[1]; }
                            
                            $startAt = $tpl['timespan'] ? strtotime($tpl['timespan']) : time();
                            
                            // Use explicit end time if available, otherwise calculate from duration
                            if ($endTs && $endTs > time()) {
                              $examEndTs = $endTs;
                            } elseif ($durMin > 0 && $startAt > 0) {
                              $examEndTs = $startAt + ($durMin * 60);
                            }
                          }
                        } catch (Throwable $e) { $examEndTs = null; }

                        // Compute LIVE subject-level test window (from tests table)
                        $testEndTs = null;
                        try {
                          $testsHasLessonId = false;
                          try {
                            $tcols = $pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($tcols as $c) { if (($c['Field'] ?? '') === 'lesson_id') { $testsHasLessonId = true; break; } }
                          } catch (Throwable $e) { $testsHasLessonId = false; }

                          if ($testsHasLessonId) {
                            $sqlT = "SELECT id, scheduled_date, start_time, end_time\n                                     FROM tests\n                                     WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1\n                                       AND lesson_id IS NULL\n                                     ORDER BY scheduled_date DESC, start_time ASC\n                                     LIMIT 5";
                          } else {
                            $sqlT = "SELECT id, scheduled_date, start_time, end_time\n                                     FROM tests\n                                     WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1\n                                     ORDER BY scheduled_date DESC, start_time ASC\n                                     LIMIT 5";
                          }
                          $stT = $pdo->prepare($sqlT);
                          $stT->execute([':cid'=>(int)$row['class_id'], ':sid'=>(int)$row['subject_id']]);
                          $cands = $stT->fetchAll(PDO::FETCH_ASSOC);
                          $nowTs = time();
                          foreach ($cands as $cand) {
                            $sTs = @strtotime((string)($cand['scheduled_date']??'').' '.(string)($cand['start_time']??''));
                            $eTs = @strtotime((string)($cand['scheduled_date']??'').' '.(string)($cand['end_time']??''));
                            if ($sTs && $eTs && $nowTs >= $sTs && $nowTs <= $eTs) { $testEndTs = $eTs; break; }
                          }
                        } catch (Throwable $e) { $testEndTs = null; }

                        // Decide which countdown to show: nearest future end between exam and test
                        $overlayEnd = null;
                        $examStatus = 'none'; // none, upcoming, active, ended
                        
                        if (!empty($examEndTs)) {
                          $now = time();
                          if ($now < $examEndTs) {
                            if ($startAt && $now >= $startAt) {
                              $overlayEnd = $examEndTs;
                              $examStatus = 'active';
                            } else {
                              $examStatus = 'upcoming';
                            }
                          } else {
                            $examStatus = 'ended';
                          }
                        }
                        
                        if (!empty($testEndTs) && time() < $testEndTs) {
                          $overlayEnd = isset($overlayEnd) ? min($overlayEnd, $testEndTs) : $testEndTs;
                        }
                      ?>
                      <?php if ($examStatus === 'active' && !empty($overlayEnd)): ?>
                        <div data-role="countdown" data-end="<?= (int)$overlayEnd ?>" title="Ends in"
                             style="position:absolute; inset:0; z-index:2; display:flex; align-items:center; justify-content:center; background: rgba(0, 0, 0, 0.06); pointer-events:none;">
                          <span class="cd-time" style="font-weight:800; font-size:44px; color: var(--theme-primary); text-shadow: 0 3px 8px rgba(0,0,0,0.20);">--:--:--</span>
                        </div>
                      <?php elseif ($examStatus === 'upcoming'): ?>
                        <div style="position:absolute; inset:0; z-index:2; display:flex; align-items:center; justify-content:center; background: rgba(0, 0, 0, 0.06); pointer-events:none;">
                          <span style="font-weight:800; font-size:24px; color: var(--theme-primary); text-shadow: 0 3px 8px rgba(0,0,0,0.20);">Upcoming</span>
                        </div>
                      <?php elseif ($examStatus === 'ended'): ?>
                        <div style="position:absolute; inset:0; z-index:1; display:flex; align-items:center; justify-content:center; color: var(--theme-primary); opacity: .28; pointer-events:none;">
                          <i class="mdi mdi-book-open-outline" style="font-size:42px;"></i>
                        </div>
                      <?php else: ?>
                        <!-- Placeholder icon when no exam is available -->
                        <div style="position:absolute; inset:0; z-index:1; display:flex; align-items:center; justify-content:center; color: var(--theme-primary); opacity: .28; pointer-events:none;">
                          <i class="mdi mdi-book-open-outline" style="font-size:42px;"></i>
                        </div>
                      <?php endif; ?>
                      <?php
                        // Debug footprint to verify timing calculations (will not affect UI)
                        $dbgNow = time();
                        echo "\n<!-- countdown-debug subject_id=".(int)$row['subject_id']." class_id=".(int)$row['class_id']
                          ." nowTs=".$dbgNow
                          ." examEndTs=".((int)($examEndTs??0))
                          ." testEndTs=".((int)($testEndTs??0))
                          ." overlayEnd=".((int)($overlayEnd??0))
                          ." examStatus=".($examStatus??'none')." -->\n";
                      ?>
                    </div>


                    <!-- lesson topic -->
                    <a href="#" onclick="event.preventDefault(); this.closest('.grid-body').querySelector('form').submit();">
                      <div class="d-flex mt-2 mb-4">
                        <h5 class="mb-0 text-success"><?= htmlspecialchars($row['subject_title']); ?></h5>
                      </div>
                    </a>

                    <!-- class and subject -->
                    <div class="d-flex justify-content-between py-2 mt-3">
                      <p class="text-gray">
                        <i class="mdi mdi-book-open-page-variant text-success b"></i>
                        <?php if ((int)$row['num_lessons'] > 1) {
                          echo (int)$row['num_lessons'] . ' Lessons';
                        } else {
                          echo (int)$row['num_lessons'] . ' Lesson';
                        }
                        ; ?>
                      </p>
                      <p class="text-black">
                        <i class="mdi mdi-chart-donut text-success"></i>
                        Class: <?= $row['class_title']; ?>
                      </p>

                    </div>


                    <div class="subject-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <form action="lessons.php" method="POST" style="flex:1;">
                      <input type="hidden" name="subject_id" value="<?= (int)$row['subject_id']; ?>">
                      <input type="hidden" name="class_id" value="<?= (int)$row['class_id']; ?>">
                      <input type="hidden" name="subject_title" value="<?= htmlspecialchars($row['subject_title']); ?>">
                      <input type="hidden" name="class_title" value="<?= htmlspecialchars($row['class_title']); ?>">
                      <input type="hidden" name="num_lessons" value="<?= (int)$row['num_lessons']; ?>">

                      <?php
                      $subject = $row['subject_title'];
                      $colors = [
                        'Civic Education' => '#1E3A8A',
                        'Cultural and Creative Arts' => '#6F42C1',
                        'History' => '#C99A3B',
                        'Home Economics' => '#28A745',
                        'Security Education' => '#8B0000',
                        'Social Studies' => '#117864',
                        'Agric' => '#2F4F2F',
                      ];
                      $btnColor = $colors[$subject] ?? '#6c757d';
                      ?>
                      <!-- send the button color along -->
                      <input type="hidden" name="button_color" value="<?= htmlspecialchars($theme_primary_color); ?>">

                      <button type="submit" class="btn has-icon mt-0" style="
                              background-color: var(--theme-primary);
                              border-color:     var(--theme-primary);
                              color:            #fff; width:100%;
                            ">
                        ViewLessons
                        <i class="mdi mdi-arrow-right-thick m-0 pl-2"></i>
                      </button>
                    </form>

                    <?php
                      // Exam button logic within the subject card
                      $subjectIdCard = (int)($row['subject_id'] ?? 0);
                      $classIdCard   = (int)($row['class_id'] ?? 0);
                      $studentIdCard = (int)($_SESSION['student_id'] ?? 0);
                      $exams_type    = 'Third Term';

                      // Check if an exam template is assigned for this subject/class and if it's currently open
                      $examTemplateExists = false;
                      $examIsOpen = false;
                      try {
                        $stTpl = $pdo->prepare("SELECT status, timespan FROM exam_assessments WHERE student_id=0 AND class_id=? AND subject_id=? ORDER BY exam_assessment_id DESC LIMIT 1");
                        $stTpl->execute([$classIdCard, $subjectIdCard]);
                        $examTemplate = $stTpl->fetch(PDO::FETCH_ASSOC);
                        $examTemplateExists = (bool)$examTemplate;
                        
                        // Check if exam is currently open (within time window)
                        if ($examTemplateExists && !empty($examTemplate['status']) && !empty($examTemplate['timespan'])) {
                          $cfg = ['dur' => 0];
                          foreach (explode(';', (string)$examTemplate['status']) as $part) {
                            if (strpos($part,'=')!==false) { 
                              [$k,$v] = explode('=', $part, 2); 
                              $cfg[$k] = $v; 
                            }
                          }
                          $startAt = strtotime($examTemplate['timespan']);
                          $durationMin = (int)($cfg['dur'] ?? 0);
                          $endAt = $durationMin > 0 ? ($startAt + $durationMin*60) : null;
                          $now = time();
                          $examIsOpen = $now >= $startAt && ($endAt === null || $now <= $endAt);
                        }
                      } catch (Throwable $e) {
                        $examTemplateExists = false;
                        $examIsOpen = false;
                      }

                      // Check if this student already has a submitted assessment for this subject/class
                      $existingAssessment = null;
                      try {
                        // Check for any existing assessment regardless of type (auto, manual, etc.)
                        $stAss = $pdo->prepare("SELECT exam_assessment_id FROM exam_assessments WHERE student_id=? AND class_id=? AND subject_id=? ORDER BY exam_assessment_id DESC LIMIT 1");
                        $stAss->execute([$studentIdCard, $classIdCard, $subjectIdCard]);
                        $existingAssessment = $stAss->fetch(PDO::FETCH_ASSOC);
                      } catch (Throwable $e) {
                        $existingAssessment = null;
                      }
                    ?>

                    <?php if (!empty($existingAssessment['exam_assessment_id'])): ?>
                      <a href="exam-result.php?assessment_id=<?= (int)$existingAssessment['exam_assessment_id']; ?>"
                         class="btn has-icon mt-0"
                         style="background-color: var(--theme-primary); border-color: var(--theme-primary); color: #fff; flex:1; display:block; width:100%;">
                         <i class="mdi mdi-eye m-0 pl-2"></i>
                        View Results
                       
                      </a>
                    <?php elseif ($examTemplateExists && $examIsOpen): ?>
                      <!-- Exam is available and currently open - show Take Exam button -->
                      <form action="i-exams.php" method="post" style="flex:1;">
                        <input type="hidden" name="subject_id" value="<?= (int)$row['subject_id']; ?>">
                        <input type="hidden" name="class_id" value="<?= (int)$row['class_id']; ?>">
                        <input type="hidden" name="subject_title" value="<?= htmlspecialchars($row['subject_title']); ?>">
                        <input type="hidden" name="class_title" value="<?= htmlspecialchars($row['class_title']); ?>">
                        <input type="hidden" name="button_color" value="<?= htmlspecialchars($theme_primary_color); ?>">
                        <button type="submit" class="btn has-icon mt-0" style="
                                background-color: var(--theme-primary);
                                border-color:     var(--theme-primary);
                                color:            #fff; width:100%;">
                          Take Exam
                          <i class="mdi mdi-pen m-0 pl-2"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                    <!-- Note: When exam time has ended, only View Lessons button is shown -->

                    <?php
                      // Subject-scoped Test/Quiz logic with 2-attempt policy
                      $subjectLiveTest = null;    // currently live test (subject scope)
                      $attempts = 0;              // number of attempts taken by student for latest subject test
                      $latestResult = null;       // latest test_result row
                      try {
                        // Detect if tests.lesson_id exists to distinguish lesson vs subject scope
                        $testsHasLessonId = false;
                        try {
                          $tcols = $pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($tcols as $c) { if (($c['Field'] ?? '') === 'lesson_id') { $testsHasLessonId = true; break; } }
                        } catch (Throwable $e) { $testsHasLessonId = false; }

                        $nowTs = time();
                        // 1) Find a currently live subject-scoped test (if any)
                        if ($testsHasLessonId) {
                          $sqlT = "SELECT id, title, scheduled_date, start_time, end_time
                                   FROM tests
                                   WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1
                                     AND lesson_id IS NULL
                                   ORDER BY scheduled_date DESC, start_time ASC
                                   LIMIT 5";
                        } else {
                          $sqlT = "SELECT id, title, scheduled_date, start_time, end_time
                                   FROM tests
                                   WHERE class_id=:cid AND subject_id=:sid AND COALESCE(is_active,1)=1
                                   ORDER BY scheduled_date DESC, start_time ASC
                                   LIMIT 5";
                        }
                        $stT = $pdo->prepare($sqlT);
                        $stT->execute([':cid'=>$classIdCard, ':sid'=>$subjectIdCard]);
                        $cands = $stT->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($cands as $cand) {
                          $sTs = @strtotime(($cand['scheduled_date']??'').' '.($cand['start_time']??''));
                          $eTs = @strtotime(($cand['scheduled_date']??'').' '.($cand['end_time']??''));
                          if ($sTs && $eTs && $nowTs >= $sTs && $nowTs <= $eTs) { $subjectLiveTest = $cand; break; }
                        }

                        // 2) Determine attempts and latest result for subject-scoped tests in this class/subject
                        if ($testsHasLessonId) {
                          $sqlR = "SELECT tr.id AS result_id, tr.test_id, tr.submitted_at
                                   FROM test_results tr
                                   JOIN tests t ON t.id = tr.test_id
                                   WHERE tr.student_id = :sid AND t.class_id = :cid AND t.subject_id = :subid
                                     AND t.lesson_id IS NULL
                                   ORDER BY tr.submitted_at DESC
                                   LIMIT 2";
                        } else {
                          $sqlR = "SELECT tr.id AS result_id, tr.test_id, tr.submitted_at
                                   FROM test_results tr
                                   JOIN tests t ON t.id = tr.test_id
                                   WHERE tr.student_id = :sid AND t.class_id = :cid AND t.subject_id = :subid
                                   ORDER BY tr.submitted_at DESC
                                   LIMIT 2";
                        }
                        $stR = $pdo->prepare($sqlR);
                        $stR->execute([':sid'=>$studentIdCard, ':cid'=>$classIdCard, ':subid'=>$subjectIdCard]);
                        $rowsR = $stR->fetchAll(PDO::FETCH_ASSOC);
                        $attempts = count($rowsR);
                        $latestResult = $rowsR[0] ?? null;
                      } catch (Throwable $e) {
                        $subjectLiveTest = null; $attempts = 0; $latestResult = null;
                      }
                    ?>

                    <?php
                      // Render subject test action per 2-attempt policy
                      $twoDays = 2 * 24 * 60 * 60;
                      $now = time();
                      if ($attempts >= 2) {
                        // After 2 attempts: show View Result for 2 days, then hide
                        $lastAt = isset($latestResult['submitted_at']) ? strtotime($latestResult['submitted_at']) : 0;
                        if ($lastAt && ($now - $lastAt) <= $twoDays): ?>
                          <a href="result.php?assessment_id=<?= (int)$latestResult['result_id']; ?>" class="btn has-icon mt-0" style="background-color: var(--theme-primary); border-color: var(--theme-primary); color:#fff; flex:1; display:block; width:100%;">
                            View Test Result
                            <i class="mdi mdi-eye m-0 pl-2"></i>
                          </a>
                        <?php endif;
                      } elseif ($attempts === 1) {
                        // One attempt: allow a retake only if a test is currently live
                        if (!empty($subjectLiveTest['id'])): ?>
                          <form action="student-assessments.php" method="POST" style="flex:1;">
                            <input type="hidden" name="test_id" value="<?= (int)$subjectLiveTest['id']; ?>">
                            <input type="hidden" name="lesson_id" value="">
                            <button type="submit" class="btn has-icon mt-0" style="background-color: var(--theme-primary); border-color: var(--theme-primary); color:#fff; width:100%;">
                              Retake Test
                              <i class="mdi mdi-reload m-0 pl-2"></i>
                            </button>
                          </form>
                        <?php endif;
                      } else {
                        // Zero attempts: allow take only if currently live
                        if (!empty($subjectLiveTest['id'])): ?>
                          <form action="student-assessments.php" method="POST" style="flex:1;">
                            <input type="hidden" name="test_id" value="<?= (int)$subjectLiveTest['id']; ?>">
                            <input type="hidden" name="lesson_id" value="">
                            <button type="submit" class="btn has-icon mt-0" style="background-color: var(--theme-primary); border-color: var(--theme-primary); color:#fff; width:100%;">
                              Take Test
                              <i class="mdi mdi-play m-0 pl-2"></i>
                            </button>
                          </form>
                        <?php endif;
                      }
                    ?>

                    </div>

                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>

        <script>
          document.getElementById('subjectFilter').addEventListener('change', function () {
            var selectedSubjectTitle = this.value;
            var subjectItems = document.querySelectorAll('.subject-item');

            subjectItems.forEach(function (item) {
              if (selectedSubjectTitle === '-- Select Subject --' || item.getAttribute('data-subject-title') === selectedSubjectTitle) {
                item.style.display = 'block';
              } else {
                item.style.display = 'none';
              }
            });
          });
        </script>
      </div>
      <!-- content viewport ends -->
      <!-- partial:../../partials/_footer.html -->
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
      if (window.__subjectCountdownInit) return; // guard
      window.__subjectCountdownInit = true;
      function pad(n){ return (n<10?'0':'')+n; }
      function fmt(ms){ if (ms<=0) return '00:00:00'; var t=Math.floor(ms/1000),h=Math.floor(t/3600),m=Math.floor((t%3600)/60),s=t%60; return pad(h)+":"+pad(m)+":"+pad(s); }
      function tick(){
        var now=Date.now();
        document.querySelectorAll('[data-role="countdown"]').forEach(function(node){
          var end = parseInt(node.getAttribute('data-end'),10)*1000; if(!end) return;
          var left=end-now; var span=node.querySelector('.cd-time'); if(span) span.textContent=fmt(left);
          if(left<=0){ node.style.opacity='0.7'; node.style.backgroundColor='rgba(108,117,125,0.12)'; node.style.color='#6c757d'; }
        });
      }
      tick(); setInterval(tick,1000);
    })();
  </script>
</body>

</html>