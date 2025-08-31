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
    html, body { width:100%; height:100%; margin:0; }
    /* expand all main containers to full width and remove gutters */
    .container, .container-fluid, .content-wrapper, .page-content, .page-body-wrapper, .main-panel, .main-wrapper {
      max-width: 100% !important;
      width: 100% !important;
      padding-left: 0 !important;
      padding-right: 0 !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
    }
    /* lesson header block flush with edges */
    #supervisorStyleDetail { padding-left: 12px; padding-right: 12px; }
    @media (max-width: 768px) { #supervisorStyleDetail { padding-left: 8px; padding-right: 8px; } }
    .menu-options {
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
    }

    /* Ensure TinyMCE fits nicely */
    .tox-tinymce { width: 100% !important; }

    /* Mobile tab selector hidden on desktop */
    .mobile-tab-select { display: none; }

    @media (max-width: 768px) {
      /* Collapse to one column on mobile */
      .lesson-grid { grid-template-columns: 1fr !important; }
      .menu-options { display: none; }
      .mobile-tab-select { display: block; margin-bottom: 8px; }
    }


    table th,
    table td {
      text-align: left !important;
    }

    /* Dynamic theme bindings (shared with index.php) */
    :root {
      --theme-primary: <?= htmlspecialchars($theme_primary_color) ?>;
      --theme-secondary: <?= htmlspecialchars($theme_secondary_color) ?>;
      --theme-accent: <?= htmlspecialchars($theme_accent_color) ?>;
    }
    :root { --active-color: <?= htmlspecialchars($theme_primary_color) ?>; }
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

    #pdfViewer {
      width: 100%;
      overflow: auto;
      border: none;
    }

    .pdf-page {
      margin-bottom: 10px;
      width: 100%;
    }

    .menu-options .menu-item {
      cursor: pointer;
      padding: 10px 15px;
      font-size: 16px;
      color: #333;
      transition: border-left 0.3s, color 0.3s;
      border-left: 4px solid transparent;
      /* Default transparent border */
    }

    .menu-options .menu-item:hover {
      background-color: #f8f9fa;
      /* Light gray on hover */
      color: #000;
      /* Black text on hover */
    }

    .menu-options .menu-item.active {
      border-left: 4px solid var(--active-color);
      color: var(--active-color);
      font-weight: bold;
    }

    .menu-options hr {
      margin: 5px 0;
      border: 0;
      border-top: 1px solid #ddd;
    }

    @media (max-width: 768px) {
      .desktop {
        display: none;

      }
    }

    @media (min-width: 768px) {
      .mobile {
        display: none;
      }
    }
    /* Constrain detail page width */
    #supervisorStyleDetail {
      max-width: 980px;
      margin: 0 auto;
    }
    /* Constrain option screen/content further */
    .option-screen { max-width: 980px; }

    /* Avoid overlap with fixed header */
    .page-body { padding-top: 70px; }
    .mt-30 { margin-top: 30px; }
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
    <!-- partial:partials/_sidebar.html (shared from index.php) -->
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

  <?php
  // Helper: robust YouTube ID parser
  function parseYouTubeId($url)
  {
    $url = trim((string)$url);
    if ($url === '') return '';
    // youtu.be/VIDEOID
    if (preg_match('~youtu\.be/([\w-]{6,})~i', $url, $m)) return $m[1];
    // youtube.com/embed/VIDEOID
    if (preg_match('~youtube\.com/embed/([\w-]{6,})~i', $url, $m)) return $m[1];
    // youtube shorts
    if (preg_match('~youtube\.com/(?:shorts|live)/([\w-]{6,})~i', $url, $m)) return $m[1];
    if (preg_match('~m\.youtube\.com/(?:watch|shorts)/([\w-]{6,})~i', $url, $m)) return $m[1];
    // youtube.com/watch?v=VIDEOID
    $q = [];
    $qs = parse_url($url, PHP_URL_QUERY);
    if ($qs) parse_str($qs, $q);
    if (!empty($q['v'])) return $q['v'];
    // raw ID fallback
    if (preg_match('~^[\w-]{6,}$~', $url)) return $url;
    return '';
  }

  // Determine which sections have data (with safe fallbacks)
  $hasDescription = trim((string)($row['description'] ?? '')) !== '';
  $videoSource = ($row['video'] ?? '') !== '' ? $row['video'] : ($_SESSION['video'] ?? '');
  $videoIdComputed = parseYouTubeId($videoSource);
  $hasVideo = trim((string)$videoSource) !== '';
  // Content file now comes from lessons.content (PDF/DOC/DOCX uploaded by teacher)
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

  // Vocabulary is now rich text (HTML) entered by teacher
  $rawVocab = (string)($row['vocabulary'] ?? '');
  $hasVocab = trim(strip_tags($rawVocab)) !== '';
  $hasNotesSaved = trim((string)$content) !== '';

  // Optional debug: append ?debug=1 to URL to see what data is detected
  if (isset($_GET['debug'])) {
    echo '<div class="alert alert-warning" style="max-width:980px;margin:10px auto;">';
    echo '<strong>Debug:</strong> lesson_id=' . (int)$lesson_id;
    echo ' · hasDescription=' . ($hasDescription?'1':'0');
    echo ' · hasVideo=' . ($hasVideo?'1':'0');
    echo ' · videoSource=' . htmlspecialchars((string)$videoSource);
    echo ' · parsedYtId=' . htmlspecialchars((string)$videoIdComputed);
    echo ' · hasVocab=' . ($hasVocab?'1':'0');
    echo ' · rawVocab=' . htmlspecialchars((string)$rawVocab);
    echo '</div>';
  }

  // Assessments availability by type
  $types = [
    1 => 'Basic Assessments',
    2 => 'Intermediate Assessments',
    3 => 'Advanced Assessments',
    4 => 'General Assessments'
  ];
  $typesWithData = [];
  try {
    foreach ($types as $tid => $tname) {
      $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM assessments a WHERE a.lesson_id = :lesson_id AND a.type = :type_id AND a.student_id = :student_id');
      $stmtCount->execute([
        ':lesson_id' => (int)$lesson_id,
        ':type_id' => (int)$tid,
        ':student_id' => (int)$student_id,
      ]);
      if ((int)$stmtCount->fetchColumn() > 0) {
        $typesWithData[$tid] = $tname;
      }
    }
  } catch (Throwable $e) {
    // fail silently; show nothing if error
    $typesWithData = [];
  }
  $hasAssessments = count($typesWithData) > 0;
  ?>

  <!-- Supervisor-style lesson detail layout (parity with supervisor dashboard) -->
  <div class="col-12 pt-4 mt-30" id="supervisorStyleDetail">
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:12px;">
      <div>
        <h2 class="mb-1"><?= htmlspecialchars(($row['lesson_number']?($row['lesson_number'].' - '):'').($row['title']??'')); ?></h2>
        <div class="text-muted">
          Subject: <?= htmlspecialchars($subject_title); ?> · Class: <?= htmlspecialchars($class_title); ?>
        </div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="lessons.php"><i class="mdi mdi-arrow-left"></i> Back to Lessons</a>
        <a class="btn btn-light" href="index.php"><i class="mdi mdi-view-grid"></i> Subjects</a>
      </div>
    </div>

    <div class="mt-3" style="border:1px solid #e5e7eb; box-shadow:0 6px 16px rgba(0,0,0,.08); border-radius:12px; background:#fff; overflow:hidden; width:100%; max-width:100%;">
      <div class="p-3 lesson-grid" style="display:grid; grid-template-columns: 200px 1fr; gap: 0; align-items:start;">
      <script>
        // define early to ensure availability for inline onclick handlers
        window.showTab = function(targetId, el){
          try {
            document.querySelectorAll('.menu-options .menu-item').forEach(function (i){ i.classList.remove('active'); });
            if (el) el.classList.add('active');
            document.querySelectorAll('.content-section').forEach(function (s){ s.style.display = 'none'; });
            var sec = document.getElementById(targetId);
            if (sec) sec.style.display = 'block';
          } catch(e) { console.error(e); }
          return false;
        }
      </script>
      <!-- Left Tabs -->
      <div class="menu-options mt-2">
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

    <!-- Mobile dropdown selector -->
    <div class="mobile-tab-select">
      <div class="text-center text-muted" style="font-weight:600; text-align:center;">Click Below To Select 👇</div>
      <select id="tabSelect" class="form-control" style="max-width:100%;" onchange="showTab(this.value, null);">
        <option value="about">Lesson Objectives</option>
        <option value="video">Video</option>
        <option value="content">Book</option>
        <option value="notes">My Notes</option>
        <option value="vocabulary">Vocabulary</option>
        <option value="assessments">Assessments</option>
      </select>
    </div>

    <!-- Option screen -->
    <div class="option-screen mt-0 pt-3" style="padding-left:0;">
      <!-- About Section -->
      <div id="about" class="content-section mt-5 pt-5" style="display:block;">
        <div class="mb-5 col-12">
          <h3 class="mb-2 col-12">Lesson Objectives</h3>
          <h2 class="col-8"><strong><?= htmlspecialchars($lesson_title); ?>
              <?= htmlspecialchars($row['lesson_number']); ?> in <?= htmlspecialchars($subject_title); ?>
              <?= htmlspecialchars($class_title); ?>.</strong></h2>
        </div>
        <div class="col-12">
          <hr class="ml-0 col-10 mb-5">
        </div>
        <div class="col-12">
          <?php if ($hasDescription): ?>
            <div style="font-size: large;" class="col-10"><?= $row['description'] ?></div>
          <?php else: ?>
            <p class="text-muted col-10">No description available for this lesson.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Test Content (formerly Vocabulary) Section -->
      <div id="vocabulary" class="content-section mt-5 pt-5" style="display:none;">
        <h3 class="mb-3"><i class="fas fa-book"></i> Vocabulary</h3>
        <?php if ($hasVocab): ?>
          <div class="card p-3" style="border:1px solid #eee; border-radius:8px;">
            <div class="content-view" style="max-width: 980px; overflow-x: auto;">
              <?= $rawVocab ?>
            </div>
          </div>
        <?php else: ?>
          <p class="text-muted">No vocabulary available for this lesson.</p>
        <?php endif; ?>
      </div>

      <!-- Video Section -->
      <div id="video" class="content-section mt-5 pt-5" style="display:none;">
        <h3 class="mb-3"><i class="fas fa-video"></i> Video</h3>
        <div style="margin-top:6px;">
          <?php if ($videoIdComputed !== ''): ?>
            <iframe width="560" height="315" src="https://www.youtube.com/embed/<?= htmlspecialchars($videoIdComputed) ?>" title="Lesson video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen style="max-width:100%; border-radius:12px;"></iframe>
          <?php elseif ($hasVideo): ?>
            <a class="btn btn-link" href="<?= htmlspecialchars($videoSource) ?>" target="_blank">Open Video</a>
          <?php else: ?>
            <p class="text-muted">No video available for this lesson.</p>
          <?php endif; ?>
        </div>
      </div>


      <!-- Content Section (Book) -->
      <div id="content" class="content-section mt-5 pt-5" style="display:none;">
        <div class="d-flex justify-content-between  mt-3">
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
        <?php if ($hasContent): ?>
          <?php if ($isPdf): ?>
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

      <!-- Notes Section -->
      <div id="notes" class="content-section mt-5 pt-5" style="display:block;">
        <div class="mb-5 col-12">
          <h3 class="mb-2 col-12">My Notes</h3>
          <h2 class="col-8"><strong><?= htmlspecialchars($lesson_title); ?></strong></h2>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="col-12 mb-3">
          <button type="button" class="btn btn-primary mr-2" onclick="showNotes()">
            <i class="mdi mdi-notebook-outline mr-1"></i> Notes
          </button>
          <!-- <button type="button" class="btn btn-outline-success" onclick="showAssessments()">
            <i class="mdi mdi-clipboard-check-outline mr-1"></i> Assessments
          </button> -->
        </div>
        
        <div class="col-12">
          <hr class="ml-0 col-10 mb-5">
        </div>
        <div class="col-12">
          <div class="form-group">
            <label for="notes_content" class="sr-only">My Notes</label>
            <textarea class="form-control bg-light" id="notes_content" name="content" rows="10" style="display: none;"><?= htmlspecialchars($content ?? '') ?></textarea>
            <div id="quill_editor" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
          </div>
        </div>
        
        <!-- Quill.js Rich Text Editor (Self-hosted, no API key required) -->
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
        <script>
          // Initialize Quill editor
          var quill = new Quill('#quill_editor', {
            theme: 'snow',
            height: 400,
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
          
          // Auto-save functionality
          var autoSaveTimer;
          var lastSavedContent = '';
          var isSaving = false;
          
          // Get lesson_id from session or form
          var lessonId = <?= json_encode($_SESSION['lesson_id'] ?? 0) ?>;
          
          // Function to save notes
          function saveNotes(content) {
            if (isSaving || !lessonId) return;
            
            isSaving = true;
            updateSaveStatus('Saving...', 'warning');
            
            fetch('autosave_notes.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                lesson_id: lessonId,
                content: content
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                lastSavedContent = content;
                updateSaveStatus('Saved', 'success');
                setTimeout(() => {
                  updateSaveStatus('', '');
                }, 2000);
              } else {
                updateSaveStatus('Save failed: ' + (data.message || 'Unknown error'), 'danger');
              }
            })
            .catch(error => {
              console.error('Auto-save error:', error);
              updateSaveStatus('Save failed: Network error', 'danger');
            })
            .finally(() => {
              isSaving = false;
            });
          }
          
          // Function to update save status
          function updateSaveStatus(message, type) {
            var statusElement = document.getElementById('save-status');
            if (!statusElement) {
              statusElement = document.createElement('div');
              statusElement.id = 'save-status';
              statusElement.className = 'mt-2';
              document.querySelector('#quill_editor').parentNode.appendChild(statusElement);
            }
            
            if (message) {
              var alertClass = type ? 'alert alert-' + type : '';
              statusElement.innerHTML = '<div class="' + alertClass + '">' + message + '</div>';
            } else {
              statusElement.innerHTML = '';
            }
          }
          
          // Debounced auto-save on text change
          quill.on('text-change', function() {
            var content = quill.root.innerHTML;
            
            // Update hidden textarea for form submission
            var textarea = document.getElementById('notes_content');
            textarea.value = content;
            
            // Clear existing timer
            if (autoSaveTimer) {
              clearTimeout(autoSaveTimer);
            }
            
            // Set new timer for auto-save (2 seconds after user stops typing)
            autoSaveTimer = setTimeout(function() {
              if (content !== lastSavedContent && content.trim() !== '') {
                saveNotes(content);
              }
            }, 2000);
            
            // Show "Unsaved changes" indicator
            if (content !== lastSavedContent) {
              updateSaveStatus('Unsaved changes...', 'info');
            }
          });
          
          // Set initial content if it exists
          var textarea = document.getElementById('notes_content');
          if (textarea.value) {
            quill.root.innerHTML = textarea.value;
            lastSavedContent = textarea.value;
          }
          
          // Manual save button (optional)
          var saveButton = document.createElement('button');
          saveButton.type = 'button';
          saveButton.className = 'btn btn-primary btn-sm mt-2';
          saveButton.innerHTML = '<i class="mdi mdi-content-save"></i> Save Notes';
          saveButton.onclick = function() {
            var content = quill.root.innerHTML;
            if (content.trim() !== '') {
              saveNotes(content);
            }
          };
          document.querySelector('#quill_editor').parentNode.appendChild(saveButton);
          
          // Also sync on form submit to ensure latest content is captured
          document.addEventListener('DOMContentLoaded', function() {
            var forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
              form.addEventListener('submit', function() {
                textarea.value = quill.root.innerHTML;
              });
            });
          });
          
          // Auto-save on page unload (optional)
          window.addEventListener('beforeunload', function() {
            var content = quill.root.innerHTML;
            if (content !== lastSavedContent && content.trim() !== '') {
              // Note: This won't actually save due to page unload, but shows intent
              console.log('Page unload detected with unsaved changes');
            }
          });
        </script>
      </div>

      <!-- Assessments Section -->
      <div id="assessments" class="content-section mt-5 pt-5" style="display:none;">
        <?php if (isset($_SESSION["class_id"])) {
          // Context
          $class_title = isset($_SESSION['class_title']) ? $_SESSION['class_title'] : '';
          $class_id = isset($_SESSION['class_id']) ? (int)$_SESSION['class_id'] : 0;
          $lesson_id = isset($_SESSION['lesson_id']) ? (int)$_SESSION['lesson_id'] : 0;
          $subject_id_ctx = isset($_SESSION['subject_id']) ? (int)$_SESSION['subject_id'] : 0;

          // Detect if tests has lesson_id column
          $testsHasLessonId = false;
          try {
            $tcols = $pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tcols as $c) { if (($c['Field'] ?? '') === 'lesson_id') { $testsHasLessonId = true; break; } }
          } catch (Throwable $e) { $testsHasLessonId = false; }

          // Build query for available (not yet taken) upcoming/live tests for this student
          if ($testsHasLessonId && $lesson_id > 0) {
            $availSql = "SELECT t.id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks, t.duration_minutes
                         FROM tests t
                         WHERE t.class_id = :class_id AND t.subject_id = :subject_id AND COALESCE(t.is_active,1)=1 AND t.lesson_id = :lesson_id
                           AND t.id NOT IN (SELECT DISTINCT tr.test_id FROM test_results tr WHERE tr.student_id = :student_id)
                         ORDER BY t.scheduled_date ASC, t.start_time ASC";
            $availParams = [
              ':student_id' => $student_id,
              ':class_id' => $class_id,
              ':subject_id' => $subject_id_ctx,
              ':lesson_id' => $lesson_id,
            ];
          } else {
            $availSql = "SELECT t.id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks, t.duration_minutes
                         FROM tests t
                         WHERE t.class_id = :class_id AND t.subject_id = :subject_id AND COALESCE(t.is_active,1)=1
                           AND t.id NOT IN (SELECT DISTINCT tr.test_id FROM test_results tr WHERE tr.student_id = :student_id)
                         ORDER BY t.scheduled_date ASC, t.start_time ASC";
            $availParams = [
              ':student_id' => $student_id,
              ':class_id' => $class_id,
              ':subject_id' => $subject_id_ctx,
            ];
          }

          $availStmt = $pdo->prepare($availSql);
          $availStmt->execute($availParams);

          // Helper to compute status
          $computeStatus = function($scheduledDate, $startTime, $endTime) {
            try {
              $now = new DateTime();
              $testDate = new DateTime($scheduledDate . ' ' . ($startTime ?: '00:00:00'));
              $testEnd = new DateTime($scheduledDate . ' ' . ($endTime ?: '23:59:59'));
              if ($now < $testDate) return 'upcoming';
              if ($now >= $testDate && $now <= $testEnd) return 'live';
              return 'ended';
            } catch (Throwable $e) { return 'upcoming'; }
          };
          ?>

          <?php
            // Previous attempts (results) scoped to this lesson when possible
            try {
              if ($testsHasLessonId && $lesson_id > 0) {
                $prevSql = "SELECT tr.id as result_id, tr.percentage, tr.obtained_marks, tr.grade, tr.submitted_at,
                                   t.id as test_id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks, t.duration_minutes
                            FROM test_results tr
                            INNER JOIN tests t ON tr.test_id = t.id
                            WHERE tr.student_id = :student_id AND t.class_id = :class_id AND t.subject_id = :subject_id AND t.lesson_id = :lesson_id
                            ORDER BY tr.submitted_at DESC";
                $prevParams = [
                  ':student_id' => $student_id,
                  ':class_id' => $class_id,
                  ':subject_id' => $subject_id_ctx,
                  ':lesson_id' => $lesson_id,
                ];
              } else {
                $prevSql = "SELECT tr.id as result_id, tr.percentage, tr.obtained_marks, tr.grade, tr.submitted_at,
                                   t.id as test_id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks, t.duration_minutes
                            FROM test_results tr
                            INNER JOIN tests t ON tr.test_id = t.id
                            WHERE tr.student_id = :student_id AND t.class_id = :class_id AND t.subject_id = :subject_id
                            ORDER BY tr.submitted_at DESC";
                $prevParams = [
                  ':student_id' => $student_id,
                  ':class_id' => $class_id,
                  ':subject_id' => $subject_id_ctx,
                ];
              }

              $prevStmt = $pdo->prepare($prevSql);
              $prevStmt->execute($prevParams);
            } catch (Throwable $e) { $prevStmt = null; }

            // Build combined rows: available (live/upcoming) + previous
            $combined = [];
            while ($row = $availStmt->fetch(PDO::FETCH_ASSOC)) {
              $status = $computeStatus($row['scheduled_date'], $row['start_time'], $row['end_time']);
              if ($status === 'ended') { continue; }
              $row['__kind'] = 'available';
              $row['__status'] = $status; // live | upcoming
              $combined[] = $row;
            }
            if ($prevStmt) {
              while ($r = $prevStmt->fetch(PDO::FETCH_ASSOC)) {
                $r['__kind'] = 'previous';
                $r['__status'] = 'previous';
                $combined[] = $r;
              }
            }

            // Sort: live first, then upcoming by schedule asc, then previous by submitted_at desc
            usort($combined, function($a, $b){
              $rank = ['live'=>0, 'upcoming'=>1, 'previous'=>2];
              $ra = $rank[$a['__status']] ?? 3;
              $rb = $rank[$b['__status']] ?? 3;
              if ($ra !== $rb) return $ra - $rb;
              if ($a['__status'] === 'previous' && $b['__status'] === 'previous') {
                return strtotime($b['submitted_at'] ?? '1970-01-01') <=> strtotime($a['submitted_at'] ?? '1970-01-01');
              }
              $aKey = strtotime(($a['scheduled_date'] ?? date('Y-m-d')) . ' ' . ($a['start_time'] ?? '00:00:00'));
              $bKey = strtotime(($b['scheduled_date'] ?? date('Y-m-d')) . ' ' . ($b['start_time'] ?? '00:00:00'));
              return $aKey <=> $bKey;
            });
          ?>

          <div class="row mb-3">
            <div class="col-lg-12">
              <div class="grid">
                <span class="grid-header row m-0">
                  <div class="d-flex w-100 justify-content-between align-items-center">
                    <span>Tests</span>
                    <a href="assessments.php" class="btn btn-sm btn-outline-primary">View all assessments</a>
                  </div>
                </span>
                <div class="item-wrapper">
                  <div class="table-responsive text-left">
                    <?php if (!empty($combined)): ?>
                      <table class="table info-table text-left">
                        <thead>
                          <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Total Marks</th>
                            <th>Status</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($combined as $item): ?>
                            <tr>
                              <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                              <td><span class="badge badge-primary"><?= htmlspecialchars(ucfirst($item['test_type'])) ?></span></td>
                              <td>
                                <?php 
                                  $dateStr = $item['scheduled_date'] ?? ($item['submitted_at'] ?? '');
                                  if ($dateStr) {
                                    $dt = new DateTime($dateStr);
                                    echo $dt->format('M d, Y');
                                  }
                                  if (!empty($item['start_time']) && !empty($item['end_time'])) {
                                    echo '<br><small class="text-muted">' . htmlspecialchars($item['start_time'] . ' - ' . $item['end_time']) . '</small>';
                                  }
                                ?>
                              </td>
                              <td><?= !empty($item['duration_minutes']) ? (int)$item['duration_minutes'] . ' min' : '-' ?></td>
                              <td>
                                <?php if (isset($item['total_marks'])): ?>
                                  <?= (int)$item['total_marks'] ?>
                                <?php else: ?>-
                                <?php endif; ?>
                              </td>
                              <td>
                                <?php if ($item['__status'] === 'live'): ?>
                                  <span class="badge badge-success">Live Now</span>
                                <?php elseif ($item['__status'] === 'upcoming'): ?>
                                  <span class="badge badge-info">Upcoming</span>
                                <?php else: ?>
                                  <span class="badge badge-secondary">Completed</span>
                                <?php endif; ?>
                              </td>
                              <td class="actions">
                                <?php if ($item['__kind'] === 'available' && $item['__status'] === 'live'): ?>
                                  <form action="student-assessments.php" method="POST" class="mb-0">
                                    <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">
                                    <input type="hidden" name="type_name" value="Test">
                                    <input type="hidden" name="test_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success social-btn pr-3 view-btn" style="
                                      background-color: <?= htmlspecialchars($btnColor) ?>;
                                      border-color:     <?= htmlspecialchars($btnColor) ?>;
                                      color:            #fff;
                                    ">
                                      Take Test Now
                                      <i class="mdi mdi-pen m-0 pl-2"></i>
                                    </button>
                                  </form>
                                <?php elseif ($item['__kind'] === 'available' && $item['__status'] === 'upcoming'): ?>
                                  <button type="button" class="btn btn-sm btn-outline-info" disabled>
                                    <i class="mdi mdi-clock-outline m-0 pr-1"></i>
                                    Coming Soon
                                  </button>
                                <?php else: ?>
                                  <span class="text-muted">Score: <?= isset($item['percentage']) ? (float)$item['percentage'].'%' : '-' ?></span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    <?php else: ?>
                      <div class="text-center py-4">
                        <i class="mdi mdi-clipboard-text-outline" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="text-muted mt-3">No Tests to Show</h5>
                        <p class="text-muted">There are no upcoming/live tests or previous attempts yet.</p>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>

    <script>
      // Global helper to ensure tab switching works even if other JS fails
      function showTab(targetId, el){
        try {
          document.querySelectorAll('.menu-options .menu-item').forEach(function (i){ i.classList.remove('active'); });
          if (el) el.classList.add('active');
          document.querySelectorAll('.content-section').forEach(function (s){ s.style.display = 'none'; });
          var sec = document.getElementById(targetId);
          if (sec) sec.style.display = 'block';
          // sync mobile dropdown selection
          var sel = document.getElementById('tabSelect');
          if (sel && sel.value !== targetId) sel.value = targetId;
          // If switching to notes, focus/refresh TinyMCE so layout is correct when tab was hidden
          if (targetId === 'notes') {
            // ensure tinymce is initialized
            if (window.tinymce) {
              var ed = tinymce.get('notes_content');
              if (ed) {
                ed.focus();
                setTimeout(function(){ try { ed.resize(); } catch(e){} }, 0);
              } else if (typeof window.__initNotesTinyMCE === 'function') {
                window.__initNotesTinyMCE(function(){
                  var e2 = tinymce.get('notes_content');
                  if (e2) e2.focus();
                });
              }
            } else if (typeof window.__loadTinyMCE === 'function') {
              window.__loadTinyMCE(function(){
                if (typeof window.__initNotesTinyMCE === 'function') {
                  window.__initNotesTinyMCE();
                }
              });
            } else {
              // Fallback contenteditable focus
              var ce = document.getElementById('notes_content_ce');
              if (ce) ce.focus();
            }
          }
        } catch(e) { console.error(e); }
        return false;
      }
      // Enable tab behavior: clicking left menu shows corresponding section
      (function(){
        var useTabs = true;

        if (!useTabs) return;

        function activate(targetId, clickedEl){
          document.querySelectorAll('.menu-options .menu-item').forEach(function (el) {
            el.classList.remove('active');
          });
          if (clickedEl) clickedEl.classList.add('active');
          document.querySelectorAll('.content-section').forEach(function (section) {
            section.style.display = 'none';
          });
          var targetSection = document.getElementById(targetId);
          if (targetSection) targetSection.style.display = 'block';
          // sync mobile dropdown selection
          var sel = document.getElementById('tabSelect');
          if (sel && sel.value !== targetId) sel.value = targetId;
        }

        document.querySelectorAll('.menu-options .menu-item').forEach(function (item) {
          item.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            activate(targetId, this);
          });
        });

        // Activate first tab immediately and on DOM ready (covers both cases)
        (function initFirst(){
          var firstItem = document.querySelector('.menu-options .menu-item');
          if (firstItem) activate(firstItem.getAttribute('data-target'), firstItem);
        })();
        document.addEventListener('DOMContentLoaded', function() {
          var firstItem = document.querySelector('.menu-options .menu-item');
          if (firstItem) activate(firstItem.getAttribute('data-target'), firstItem);

          // Mobile dropdown handler
          var sel = document.getElementById('tabSelect');
          if (sel) {
            sel.addEventListener('change', function(){
              var opt = this.value;
              var item = document.querySelector('.menu-options .menu-item[data-target="' + opt + '"]');
              activate(opt, item);
              // Scroll the selected section into view on mobile
              var sec = document.getElementById(opt);
              if (sec) {
                setTimeout(function(){ sec.scrollIntoView({behavior:'smooth', block:'start'}); }, 50);
              }
            });
            // ensure dropdown reflects current active
            var active = document.querySelector('.menu-options .menu-item.active');
            if (active) sel.value = active.getAttribute('data-target');
          }
        });
      })();
    </script>
  </div>

  <!-- Place the following <script> and <textarea> tags your HTML's <body> -->
  <script>
    let timeoutId;

    // Loader with fallback CDN
    window.__loadTinyMCE = function(callback){
      function onReady(){ if (typeof callback === 'function') callback(); }
      if (window.tinymce) return onReady();
      // Prefer jsDelivr (no API key required); fallback to Tiny Cloud if needed
      var s1 = document.createElement('script');
      s1.src = 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js';
      s1.onload = onReady;
      s1.onerror = function(){
        var s2 = document.createElement('script');
        s2.src = 'https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js';
        s2.referrerPolicy = 'origin';
        s2.onload = onReady;
        document.head.appendChild(s2);
      };
      document.head.appendChild(s1);
    };

    // Init function for notes editor
    window.__initNotesTinyMCE = function(after){
      if (!window.tinymce) return;
      if (tinymce.get('notes_content')) { if (after) after(); return; }
      tinymce.init({
        selector: '#notes_content',
        menubar: 'file edit view insert format tools table help',
        toolbar_mode: 'wrap',
        height: 420,
        resize: true,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table emoticons paste help wordcount autoresize',
        toolbar: 'undo redo | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | removeformat | code fullscreen preview',
        branding: true,
        statusbar: true,
        autoresize_bottom_margin: 16,
        setup: function (editor) {
          editor.on('keyup change', function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(function () {
              autoSaveNote(editor.getContent());
            }, 2000);
          });
        },
        init_instance_callback: function(ed){ if (after) after(); }
      });
    };

    // Boot on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
      window.__loadTinyMCE(function(){
        window.__initNotesTinyMCE();
      });

      // Ensure initial content is saved if user doesn't type
      const initialContent = document.getElementById('notes_content')?.value || '';
      if (initialContent) {
        setTimeout(function(){ autoSaveNote(initialContent); }, 1000);
      }

      // If TinyMCE fails to load, create a basic contenteditable fallback with minimal toolbar
      setTimeout(function(){
        try {
          if (!(window.tinymce && tinymce.get('notes_content'))) {
            var ta = document.getElementById('notes_content');
            if (ta && ta.tagName === 'TEXTAREA') {
              // build toolbar
              var toolbar = document.createElement('div');
              toolbar.style.margin = '8px 0';
              toolbar.innerHTML = '\n                <button type="button" class="btn btn-sm btn-light" data-cmd="bold"><b>B</b></button>\n                <button type="button" class="btn btn-sm btn-light" data-cmd="italic"><i>I</i></button>\n                <button type="button" class="btn btn-sm btn-light" data-cmd="underline"><u>U</u></button>\n                <button type="button" class="btn btn-sm btn-light" data-cmd="insertUnorderedList">• List</button>\n                <button type="button" class="btn btn-sm btn-light" data-cmd="insertOrderedList">1. List</button>\n                <button type="button" class="btn btn-sm btn-light" data-cmd="removeFormat">Clear</button>\n              ';
              // contenteditable area
              var ce = document.createElement('div');
              ce.id = 'notes_content_ce';
              ce.className = 'form-control bg-light';
              ce.style.minHeight = '280px';
              ce.style.overflowY = 'auto';
              ce.contentEditable = 'true';
              ce.innerHTML = ta.value || '';

              // replace textarea visually but keep it to store value if needed
              ta.style.display = 'none';
              ta.parentNode.insertBefore(toolbar, ta);
              ta.parentNode.insertBefore(ce, ta);

              // toolbar actions
              toolbar.addEventListener('click', function(e){
                var btn = e.target.closest('button[data-cmd]');
                if (!btn) return;
                var cmd = btn.getAttribute('data-cmd');
                document.execCommand(cmd, false, null);
                ce.focus();
              });

              // autosave on input with debounce
              var ceDebounce;
              ce.addEventListener('input', function(){
                clearTimeout(ceDebounce);
                ceDebounce = setTimeout(function(){
                  ta.value = ce.innerHTML;
                  autoSaveNote(ta.value);
                }, 2000);
              });
            }
          }
        } catch(err) { console.error(err); }
      }, 2500);
    });

    function autoSaveNote(content) {
      const userId = <?= (int) $user_id ?>;
      const lessonId = <?= (int) $lesson_id ?>;

      fetch('autosave_notes.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          user_id: userId,
          lesson_id: lessonId,
          content: content
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.status !== 'success') {
          console.error('Save failed:', data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    }

  </script>
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
</body>

</html>