<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject_id'])) {
  $_SESSION['class_id'] = intval($_POST['class_id']); //store session
  $_SESSION['subject_id'] = intval($_POST['subject_id']); //store session
  $_SESSION['subject_title'] = $_POST['subject_title']; //store session
  $_SESSION['class_title'] = $_POST['class_title']; //store session
  $_SESSION['lesson_id'] = intval($_POST['lesson_id']); //store session
  $_SESSION['lesson_title'] = $_POST['lesson_title']; //store session
  $_SESSION['content'] = $_POST['content']; //store session
  $_SESSION['video'] = $_POST['video']; //store session
  $_SESSION['thumbnail'] = $_POST['thumbnail']; //store session
  $_SESSION['button_color'] = $_POST['button_color'];
  header("Location: lesson.php"); //redirect to avoid form resubmission
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
$btnColor = $_SESSION['button_color'] ?? '#6c757d';
?>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Lesson - Nuture 360&deg; | Learning made simple</title>
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

  <!-- ...existing code... -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>

  <style>
    :root {
      --theme-primary: <?= htmlspecialchars($theme_primary_color) ?>;
      --theme-secondary: <?= htmlspecialchars($theme_secondary_color) ?>;
      --theme-accent: <?= htmlspecialchars($theme_accent_color) ?>;
      --active-color: <?= htmlspecialchars($theme_primary_color) ?>;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9fa;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .lesson-container {
      max-width: 100%;
      margin: 70px auto;
      padding: 20px;
    }

    .lesson-header {
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e0e0e0;
    }

    .lesson-title {
      font-size: 28px;
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 5px;
    }

    .lesson-subtitle {
      color: #7f8c8d;
      font-size: 16px;
      margin-bottom: 20px;
    }

    .lesson-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 30px;
    }

    .menu-options {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      padding: 20px 0;
      height: fit-content;
    }

    .menu-item {
      padding: 14px 25px;
      font-size: 16px;
      color: #555;
      cursor: pointer;
      transition: all 0.2s ease;
      border-left: 4px solid transparent;
      margin-bottom: 5px;
    }

    .menu-item:hover {
      background-color: #f8f9fa;
      color: #2c3e50;
    }

    .menu-item.active {
      border-left: 4px solid var(--active-color);
      color: var(--active-color);
      font-weight: 600;
      background-color: rgba(0, 0, 0, 0.02);
    }

    .menu-item hr {
      margin: 8px 0;
      border: none;
      border-top: 1px solid #eee;
    }

    .content-section {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      padding: 30px;
      display: none;
    }

    #about {
      display: block;
    }

    .section-title {
      font-size: 22px;
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }

    .content-text {
      font-size: 16px;
      line-height: 1.6;
      color: #444;
    }

    .content-text h2 {
      font-size: 24px;
      color: #2c3e50;
      margin-top: 30px;
      margin-bottom: 15px;
    }

    .content-text h3 {
      font-size: 20px;
      color: #2c3e50;
      margin-top: 25px;
      margin-bottom: 12px;
    }

    .content-text ul {
      padding-left: 20px;
      margin-bottom: 20px;
    }

    .content-text li {
      margin-bottom: 8px;
    }

    .mobile-tab-select {
      display: none;
    }

    @media (max-width: 968px) {
      .lesson-grid {
        grid-template-columns: 1fr;
      }

      .menu-options {
        display: none;
      }

      .mobile-tab-select {
        display: block;
        margin-bottom: 20px;
      }

      .mobile-tab-select select {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #ddd;
        background-color: white;
        font-size: 16px;
      }
    }

    @media (max-width: 768px) {
      .lesson-container {
        padding: 15px;
      }

      .content-section {
        padding: 20px;
      }

      .lesson-title {
        font-size: 24px;
      }
    }

    /* Header styling */
    .t-header {
      background: white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 0 20px;
      height: 70px;
    }

    .t-header-brand-wrapper {
      display: flex;
      align-items: center;
    }

    .logo {
      height: 40px;
    }

    /* Button styling */
    .btn-primary {
      background-color: var(--theme-primary);
      border-color: var(--theme-primary);
    }

    .btn-primary:hover {
      background-color: var(--theme-accent);
      border-color: var(--theme-accent);
    }

    /* Header icons use theme color */
    .t-header .t-header-toggler i,
    .t-header .t-header-search-box i,
    .t-header .nav .nav-link i,
    .t-header .nav-tabs .nav-link i {
      color: var(--theme-primary) !important;
    }

    /* Notification action button themed */
    .t-header .action-btn.theme-outline {
      background: transparent;
      border: 1px solid var(--theme-primary);
      color: var(--theme-primary);
    }
    .t-header .action-btn.theme-outline i {
      color: var(--theme-primary) !important;
    }

    /* Search submit button: rounded theme background, white icon */
    .t-header .t-header-search-box .search-submit {
      background-color: var(--theme-primary) !important;
      border-color: var(--theme-primary) !important;
      color: #fff !important;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .t-header .t-header-search-box .search-submit i {
      color: #fff !important;
    }

    /* Sidebar should remain visible like other pages */
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
            <button class="btn btn-primary search-submit" type="submit">
              <i class="mdi mdi-arrow-right-thick"></i>
            </button>
          </div>
        </form>
        <ul class="nav ml-auto">
          <li class="nav-item dropdown">
            <a class="nav-link" href="#" id="notificationDropdown" data-toggle="dropdown" aria-expanded="false">
              <div class="btn action-btn theme-outline btn-rounded component-flan">
                <i class="mdi mdi-bell-outline mdi-1x"></i>
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

    <!-- partial -->
    <div class="page-content-wrapper">
      <div class="page-content-wrapper-inner">
        <div class="content-viewport">
  <?php
  $subject_id = isset($_SESSION['subject_id']) ? $_SESSION['subject_id'] : 0;
  $class_id = isset($_SESSION['class_id']) ? $_SESSION['class_id'] : 0;
  $subject_title = isset($_SESSION['subject_title']) ? $_SESSION['subject_title'] : '';
  $class_title = isset($_SESSION['class_title']) ? $_SESSION['class_title'] : '';
  $lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : (isset($_SESSION['lesson_id']) ? (int)$_SESSION['lesson_id'] : 0);
  $lesson_title = isset($_SESSION['lesson_title']) ? $_SESSION['lesson_title'] : '';

  $sql = "SELECT * FROM lessons WHERE lesson_id = :id ORDER BY lesson_id ASC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id' => (int)$lesson_id]);

  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    die('No data found for the given lesson ID.');
  }

  $stmt = $pdo->prepare("SELECT content FROM notes WHERE user_id = :user_id AND lesson_id = :lesson_id");
  $stmt->execute(['user_id' => $student_id, 'lesson_id' => $lesson_id]);
  $content = $stmt->fetchColumn() ?: '';

  ?>

  <div class="lesson-container">
    <div class="lesson-header">
      <h1 class="lesson-title"><?= htmlspecialchars(($row['lesson_number']?($row['lesson_number'].' '):'').($row['title']??'')); ?></h1>
      <div class="lesson-subtitle">
        Subject: <?= htmlspecialchars($subject_title); ?> · Class: <?= htmlspecialchars($class_title); ?>
      </div>
      <div class="d-flex" style="gap:10px; flex-wrap:wrap;">
        <a class="btn btn-primary" href="lessons.php"><i class="mdi mdi-arrow-left"></i> Back to Lessons</a>
        <a class="btn btn-outline-primary" href="index.php"><i class="mdi mdi-arrow-left-bold"></i> Back to Subjects</a>
      </div>
    </div>

    <div class="mobile-tab-select">
      <select id="tabSelect" class="form-control" onchange="showTab(this.value, null);">
        <option value="about" selected>Lesson Objectives</option>
        <option value="video">Video</option>
        <option value="content">Book</option>
        <option value="notes">My Notes</option>
        <option value="vocabulary">Vocabulary</option>
        <option value="assessments">Assessments</option>
      </select>
    </div>

    <div class="lesson-grid">
      <!-- Left Menu -->
      <div class="menu-options">
        <p class="menu-item active" role="button" tabindex="0" data-target="about" onclick="return showTab('about', this)">Lesson Objectives</p>
        <hr>
        <p class="menu-item" role="button" tabindex="0" data-target="video" onclick="return showTab('video', this)">Video</p>
        <hr>
        <p class="menu-item" role="button" tabindex="0" data-target="content" onclick="return showTab('content', this)">Book</p>
        <hr>
        <p class="menu-item" role="button" tabindex="0" data-target="notes" onclick="return showTab('notes', this)">My Notes</p>
        <hr>
        <p class="menu-item" role="button" tabindex="0" data-target="vocabulary" onclick="return showTab('vocabulary', this)">Vocabulary</p>
        <hr>
        <p class="menu-item" role="button" tabindex="0" data-target="assessments" onclick="return showTab('assessments', this)">Assessments</p>
      </div>

      <!-- Content Area -->
      <div class="content-area">
        <!-- About Section -->
        <div id="about" class="content-section">
          <h2 class="section-title">Lesson Objectives</h2>
          <div class="content-text">
            <?php
              // Show the lesson description/objectives from DB if available
              $description = (string)($row['description'] ?? '');
              if (trim(strip_tags($description)) === '') {
                echo '<p class="text-muted">No objectives provided for this lesson.</p>';
              } else {
                echo $description; // description may contain HTML
              }
            ?>
          </div>
        </div>

        <!-- Video Section -->
        <div id="video" class="content-section">
          <h2 class="section-title">Video</h2>
          <div class="content-text">
            <?php
            function parseYouTubeId($url) {
              $url = trim((string)$url);
              if ($url === '') return '';
              if (preg_match('~youtu\.be/([\w-]{6,})~i', $url, $m)) return $m[1];
              if (preg_match('~youtube\.com/embed/([\w-]{6,})~i', $url, $m)) return $m[1];
              if (preg_match('~youtube\.com/(?:shorts|live)/([\w-]{6,})~i', $url, $m)) return $m[1];
              if (preg_match('~m\.youtube\.com/(?:watch|shorts)/([\w-]{6,})~i', $url, $m)) return $m[1];
              $q = [];
              $qs = parse_url($url, PHP_URL_QUERY);
              if ($qs) parse_str($qs, $q);
              if (!empty($q['v'])) return $q['v'];
              if (preg_match('~^[\w-]{6,}$~', $url)) return $url;
              return '';
            }

            $videoSource = ($row['video'] ?? '') !== '' ? $row['video'] : ($_SESSION['video'] ?? '');
            $videoIdComputed = parseYouTubeId($videoSource);
            $hasVideo = trim((string)$videoSource) !== '';
            ?>
            
            <?php if ($videoIdComputed !== ''): ?>
              <iframe width="100%" height="400" src="https://www.youtube.com/embed/<?= htmlspecialchars($videoIdComputed) ?>" title="Lesson video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen style="border-radius:12px;"></iframe>
            <?php elseif ($hasVideo): ?>
              <a class="btn btn-primary" href="<?= htmlspecialchars($videoSource) ?>" target="_blank">Open Video</a>
            <?php else: ?>
              <p class="text-muted">No video available for this lesson.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Content Section (Book) -->
        <div id="content" class="content-section">
          <h2 class="section-title">Book</h2>
          <div class="content-text">
            <?php
            $contentPathDb = (string)($row['content'] ?? '');
            $contentSource = '';
            $contentExt = '';
            $hasContent = false;
            
            if ($contentPathDb !== '') {
              $contentSource = (strpos($contentPathDb, 'http') === 0)
                ? $contentPathDb
                : ('../uploads/resources/' . basename($contentPathDb));
              $contentExt = strtolower(pathinfo(parse_url($contentSource, PHP_URL_PATH) ?: $contentSource, PATHINFO_EXTENSION));
              $hasContent = true;
            }
            $isPdf = ($contentExt === 'pdf');
            $isDoc = ($contentExt === 'doc' || $contentExt === 'docx');
            ?>
            
            <?php if ($hasContent): ?>
              <?php if ($isPdf): ?>
                <div class="d-flex justify-content-between mt-3">
                  <h4></h4>
                  <button id="fullscreenBtn" class="btn btn-success mb-2" style="
                  background-color: <?= htmlspecialchars($btnColor) ?>;
                  border-color:     <?= htmlspecialchars($btnColor) ?>;
                  color:            #fff;
                ">Fullscreen
                    <i class="mdi mdi-fullscreen text-white"></i>
                  </button>
                </div>
                <div class="d-flex py-2">
                  <div id="pdfViewer" class="w-100"></div>
                </div>
                <script>
                  var url = '<?= htmlspecialchars($contentSource) ?>';
                  var pdfjsLib = window['pdfjs-dist/build/pdf'];
                  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';
                  var loadingTask = pdfjsLib.getDocument(url);
                  loadingTask.promise.then(function (pdf) {
                    var pdfViewer = document.getElementById('pdfViewer');
                    var scale = window.innerWidth < 768 ? 0.75 : 1.5; // Adjust scale for mobile devices
                    for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                      pdf.getPage(pageNum).then(function (page) {
                        var viewport = page.getViewport({ scale: scale });
                        var canvas = document.createElement('canvas');
                        canvas.className = 'pdf-page';
                        var context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        var renderContext = { canvasContext: context, viewport: viewport };
                        page.render(renderContext);
                        pdfViewer.appendChild(canvas);
                      });
                    }
                  });
                  document.getElementById('fullscreenBtn').addEventListener('click', function () {
                    var pdfViewer = document.getElementById('pdfViewer');
                    if (pdfViewer.requestFullscreen) {
                      pdfViewer.requestFullscreen();
                    } else if (pdfViewer.mozRequestFullScreen) { pdfViewer.mozRequestFullScreen(); }
                    else if (pdfViewer.webkitRequestFullscreen) { pdfViewer.webkitRequestFullscreen(); }
                    else if (pdfViewer.msRequestFullscreen) { pdfViewer.msRequestFullscreen(); }
                  });
                </script>
              <?php elseif ($isDoc): ?>
                <div class="alert alert-info">This document type cannot be previewed inline. Use the buttons below to open or download.</div>
                <div class="d-flex gap-2" style="gap:8px;">
                  <a class="btn btn-secondary" href="<?= htmlspecialchars($contentSource) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> Open</a>
                  <a class="btn btn-primary" href="<?= htmlspecialchars($contentSource) ?>" download><i class="fas fa-download"></i> Download</a>
                </div>
              <?php else: ?>
                <div class="alert alert-secondary">Unsupported content type. You can still download the file below.</div>
                <div class="d-flex gap-2" style="gap:8px;">
                  <a class="btn btn-primary" href="<?= htmlspecialchars($contentSource) ?>" download><i class="fas fa-download"></i> Download</a>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <p class="text-muted">No book content available for this lesson.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Notes Section -->
        <div id="notes" class="content-section">
          <h2 class="section-title">My Notes</h2>
          <div class="content-text">
            <div class="form-group">
              <textarea class="form-control bg-light" id="notes_content" name="content" rows="10" style="display: none;"><?= htmlspecialchars($content ?? '') ?></textarea>
              <div id="quill_editor" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
            </div>
          </div>
        </div>

        <!-- Vocabulary Section -->
        <div id="vocabulary" class="content-section">
          <h2 class="section-title">Vocabulary</h2>
          <div class="content-text">
            <?php
            $rawVocab = (string)($row['vocabulary'] ?? '');
            $hasVocab = trim(strip_tags($rawVocab)) !== '';
            ?>
            
            <?php if ($hasVocab): ?>
              <div class="card p-3" style="border:1px solid #eee; border-radius:8px;">
                <div class="content-view" style="overflow-x: auto;">
                  <?= $rawVocab ?>
                </div>
              </div>
            <?php else: ?>
              <p class="text-muted">No vocabulary available for this lesson.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Assessments Section -->
        <div id="assessments" class="content-section">
          <h2 class="section-title">Assessments</h2>
          <div class="content-text">
            <?php
            // Context from session
            $class_id_ctx   = isset($_SESSION['class_id']) ? (int)$_SESSION['class_id'] : 0;
            $subject_id_ctx = isset($_SESSION['subject_id']) ? (int)$_SESSION['subject_id'] : 0;
            $lesson_id_ctx  = isset($_SESSION['lesson_id']) ? (int)$_SESSION['lesson_id'] : 0;

            if ($class_id_ctx && $subject_id_ctx) :
              // Detect if tests table has lesson_id to filter by lesson
              $testsHasLessonId = false;
              try {
                $tcols = $pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($tcols as $c) { if (($c['Field'] ?? '') === 'lesson_id') { $testsHasLessonId = true; break; } }
              } catch (Throwable $e) { $testsHasLessonId = false; }

              // Build base WHERE and params
              $where = "WHERE t.class_id = :class_id AND t.subject_id = :subject_id";
              $paramsBase = [':class_id' => $class_id_ctx, ':subject_id' => $subject_id_ctx];
              if ($testsHasLessonId && $lesson_id_ctx > 0) {
                $where .= " AND t.lesson_id = :lesson_id";
                $paramsBase[':lesson_id'] = $lesson_id_ctx;
              }

              // Live (today and within time window) not yet attempted by this student
              try {
                $liveSql = "SELECT 'Test' as assessment_type,
                                   t.id as assessment_id,
                                   t.title,
                                   t.test_type as type,
                                   t.total_marks,
                                   t.duration_minutes,
                                   t.scheduled_date,
                                   t.start_time,
                                   t.end_time,
                                   s.subject_name,
                                   tr.id AS result_id
                            FROM tests t
                            JOIN subjects s ON t.subject_id = s.id
                            LEFT JOIN test_results tr ON t.id = tr.test_id AND tr.student_id = :student_id
                            $where
                              AND t.scheduled_date = CURDATE()
                              AND t.start_time <= CURTIME() AND t.end_time >= CURTIME()
                              AND COALESCE(t.is_active,1)=1
                              AND tr.id IS NULL
                            ORDER BY t.end_time";
                $liveStmt = $pdo->prepare($liveSql);
                $paramsLive = array_merge([':student_id' => $student_id], $paramsBase);
                $liveStmt->execute($paramsLive);
                $liveAssessments = $liveStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
              } catch (Throwable $e) {
                echo "<!-- Live assessments query error: " . htmlspecialchars($e->getMessage()) . " -->"; $liveAssessments = [];
              }

              // All (show all tests; include whether attempted)
              try {
                $allSql = "SELECT 'Test' as assessment_type,
                                  t.id as assessment_id,
                                  t.title,
                                  t.test_type as type,
                                  t.total_marks,
                                  t.duration_minutes,
                                  t.scheduled_date,
                                  t.start_time,
                                  t.end_time,
                                  s.subject_name,
                                  tr.id AS result_id,
                                  tr.obtained_marks,
                                  tr.total_marks AS result_total_marks,
                                  tr.percentage
                           FROM tests t
                           JOIN subjects s ON t.subject_id = s.id
                           LEFT JOIN test_results tr ON t.id = tr.test_id AND tr.student_id = :student_id
                           $where
                           AND COALESCE(t.is_active,1)=1
                           ORDER BY t.scheduled_date DESC, t.start_time DESC";
                $allStmt = $pdo->prepare($allSql);
                $paramsAll = array_merge([':student_id' => $student_id], $paramsBase);
                $allStmt->execute($paramsAll);
                $allAssessments = $allStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
              } catch (Throwable $e) {
                echo "<!-- All assessments query error: " . htmlspecialchars($e->getMessage()) . " -->"; $allAssessments = [];
              }
            ?>

            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#assessLive" role="tab" aria-selected="true">
                  <i class="mdi mdi-account-clock mr-1"></i> Live Now
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#assessAll" role="tab" aria-selected="false">
                  <i class="mdi mdi-view-list mr-1"></i> All
                </a>
              </li>
            </ul>
            <div class="tab-content pt-3">
              <div class="tab-pane fade show active" id="assessLive" role="tabpanel">
                <?php if (!empty($liveAssessments)): ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead class="thead-light">
                        <tr>
                          <th>Assessment</th>
                          <th>Type</th>
                          <th>Date</th>
                          <th>Time</th>
                          <th>Subject</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($liveAssessments as $a): ?>
                          <tr>
                            <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                            <td><?= htmlspecialchars($a['type']) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y', strtotime($a['scheduled_date']))) ?></td>
                            <td><?= htmlspecialchars(date('g:i A', strtotime($a['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($a['end_time']))) ?></td>
                            <td><?= htmlspecialchars($a['subject_name']) ?></td>
                            <td>
                              <form action="student-assessments.php" method="POST" class="mb-0">
                                <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id_ctx ?>">
                                <input type="hidden" name="type_name" value="Test">
                                <input type="hidden" name="test_id" value="<?= (int)$a['assessment_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-warning"><i class="mdi mdi-pen"></i> Start</button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-center py-5">
                    <i class="mdi mdi-timer-off" style="font-size: 4rem; color: #e0e0e0;"></i>
                    <h4 class="mt-3 text-muted">No Live Assessments</h4>
                    <p class="text-muted">There are no tests live right now for this lesson.</p>
                  </div>
                <?php endif; ?>
              </div>

              <div class="tab-pane fade" id="assessAll" role="tabpanel">
                <?php if (!empty($allAssessments)): ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead class="thead-light">
                        <tr>
                          <th>Assessment</th>
                          <th>Type</th>
                          <th>Date</th>
                          <th>Time</th>
                          <th>Subject</th>
                          <th>Score</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($allAssessments as $a):
                          $isToday   = (date('Y-m-d') === (string)$a['scheduled_date']);
                          $now       = strtotime('now');
                          $startTs   = strtotime($a['scheduled_date'].' '.$a['start_time']);
                          $endTs     = strtotime($a['scheduled_date'].' '.$a['end_time']);
                          $isLive    = $isToday && $now >= $startTs && $now <= $endTs;
                          $attempted = !empty($a['result_id']);
                        ?>
                          <tr>
                            <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                            <td><?= htmlspecialchars($a['type']) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y', strtotime($a['scheduled_date']))) ?></td>
                            <td><?= htmlspecialchars(date('g:i A', strtotime($a['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($a['end_time']))) ?></td>
                            <td><?= htmlspecialchars($a['subject_name']) ?></td>
                            <td>
                              <?php if ($attempted): ?>
                                <?= (int)($a['obtained_marks'] ?? 0) ?> / <?= (int)($a['result_total_marks'] ?? $a['total_marks']) ?><?= isset($a['percentage']) && $a['percentage'] !== null ? ' ('.(float)$a['percentage'].'%)' : '' ?>
                              <?php else: ?>
                                -
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ($attempted): ?>
                                <span class="badge badge-success">Completed<?= isset($a['percentage']) && $a['percentage'] !== null ? ' - '.(float)$a['percentage'].'%' : '' ?></span>
                              <?php elseif ($isLive): ?>
                                <span class="badge badge-warning">Live</span>
                              <?php elseif (strtotime($a['scheduled_date'].' '.$a['start_time']) > time()): ?>
                                <span class="badge badge-info">Upcoming</span>
                              <?php else: ?>
                                <span class="badge badge-secondary">Ended</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-center py-5">
                    <i class="mdi mdi-calendar-remove-outline" style="font-size: 4rem; color: #e0e0e0;"></i>
                    <h4 class="mt-3 text-muted">No Assessments Found</h4>
                    <p class="text-muted">No tests are configured for this subject/class<?= $testsHasLessonId ? ' and lesson' : '' ?>.</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <?php else: ?>
              <p class="text-muted">No class or subject selected. Please navigate from your subject to view assessments.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
        </div>
      </div>
    </div>

  <script>
    // Tab navigation functionality
    function showTab(targetId, el) {
      // Hide all content sections
      document.querySelectorAll('.content-section').forEach(function(section) {
        section.style.display = 'none';
      });
      
      // Show the selected section
      const targetSection = document.getElementById(targetId);
      if (targetSection) {
        targetSection.style.display = 'block';
      }
      
      // Update menu item active states
      document.querySelectorAll('.menu-item').forEach(function(item) {
        item.classList.remove('active');
      });
      
      if (el) {
        el.classList.add('active');
      } else {
        // Find the menu item that corresponds to the selected tab
        const correspondingMenuItem = document.querySelector(`.menu-item[data-target="${targetId}"]`);
        if (correspondingMenuItem) {
          correspondingMenuItem.classList.add('active');
        }
      }
      
      // Sync mobile dropdown
      const tabSelect = document.getElementById('tabSelect');
      if (tabSelect) {
        tabSelect.value = targetId;
      }
      
      return false;
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
      // Prefer Video tab if a valid YouTube/video source exists, else About
      var hasVideo = <?php echo json_encode(!empty($videoIdComputed) || (!empty($videoSource))); ?>;
      if (hasVideo && document.getElementById('video')) {
        showTab('video', document.querySelector('.menu-item[data-target="video"]'));
      } else {
        showTab('about', document.querySelector('.menu-item.active'));
      }
      
      // Initialize Quill editor for notes
      if (typeof Quill !== 'undefined') {
        var quill = new Quill('#quill_editor', {
          theme: 'snow',
          modules: {
            toolbar: [
              [{ 'header': [1, 2, 3, false] }],
              ['bold', 'italic', 'underline', 'strike'],
              [{ 'color': [] }, { 'background': [] }],
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              [{ 'indent': '-1'}, { 'indent': '+1' }],
              [{ 'align': [] }],
              ['link', 'image'],
              ['clean']
            ]
          },
          placeholder: 'Start writing your notes here...'
        });
        
        // Set initial content if it exists
        const textarea = document.getElementById('notes_content');
        if (textarea && textarea.value) {
          quill.root.innerHTML = textarea.value;
        }
        
        // Update textarea when editor content changes
        quill.on('text-change', function() {
          textarea.value = quill.root.innerHTML;
        });
      }
    });
  </script>

  <!-- Quill.js Rich Text Editor -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <!-- plugins:js -->
  <script src="assets/vendors/js/core.js"></script>
  <script src="assets/vendors/js/vendor.addons.js"></script>
  <!-- endinject -->
  <!-- build:js -->
  <script src="template.js"></script>
  <!-- endbuild -->
</body>
</html>