<?php require_once('settings.php');
// Normalize parameters: accept assessment_id and student_id via POST or GET then redirect (PRG pattern)
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['assessment_id']) || isset($_POST['student_id']))) {
  if (isset($_POST['assessment_id'])) { $_SESSION['assessment_id'] = intval($_POST['assessment_id']); }
  if (isset($_POST['student_id']))    { $_SESSION['student_id']    = intval($_POST['student_id']); }
  header("Location: result.php");
  exit();
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['assessment_id']) || isset($_GET['student_id']))) {
  if (isset($_GET['assessment_id'])) { $_SESSION['assessment_id'] = intval($_GET['assessment_id']); }
  if (isset($_GET['student_id']))    { $_SESSION['student_id']    = intval($_GET['student_id']); }
  header("Location: result.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php
$btnColor = $_SESSION['button_color'] ?? ($theme_primary_color ?? '#6c757d');
?>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Assessment Result - <?= htmlspecialchars($app_name ?? 'Nuture 360°') ?> | Learning made simple</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="assets/vendors/iconfonts/mdi/css/materialdesignicons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.addons.css">
  <!-- endinject -->
  <!-- vendor css for this page -->
  <!-- End vendor css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="assets/css/shared/style.css">
  <!-- endinject -->
  <!-- Layout style -->
  <link rel="stylesheet" href="assets/css/demo_1/style.css">
  <!-- Layout style -->
  <link rel="shortcut icon" href="<?= htmlspecialchars($favicon_url ?? 'assets/images/favicon.ico') ?>" />
  <style>
    :root {
      --theme-primary: <?= htmlspecialchars($theme_primary_color ?? '#28a745') ?>;
      --theme-secondary: <?= htmlspecialchars($theme_secondary_color ?? '#6c757d') ?>;
      --theme-accent: <?= htmlspecialchars($theme_accent_color ?? '#17a2b8') ?>;
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
          <span class="h4 mb-0" style="font-weight:700; font-size:1.81rem; color: <?= htmlspecialchars($theme_primary_color ?? '#28a745') ?>;">&lrm;<?= htmlspecialchars($app_name ?? '') ?></span>
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
          <img class="profile-img img-lg rounded-circle" src="<?= htmlspecialchars($profile_image_url ?? 'assets/images/profile/male/1.jpg') ?>" alt="profile image" onerror="this.onerror=null;this.src='assets/images/profile/male/1.jpg';" />
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
        <div class="viewport-header">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb has-arrow">
              <li class="breadcrumb-item">
                <a href="lesson.php"><?= $_SESSION['lesson_title'] ?></a>
              </li>
              <li class="breadcrumb-item">
                <a href="assessments.php">My Assessments</a>
              </li>
              <li class="breadcrumb-item  text-success" aria-current="page">Assessment Result</li>
            </ol>
          </nav>
        </div>
        <div class="content-viewport">
          <div class="row">

            <div class="col-lg-12">
              <div class="grid">
                <p class="grid-header"><?= $_SESSION['class_title'] ?> - <?= $_SESSION['subject_title'] ?> -
                  <?= $_SESSION['lesson_title'] ?> - <?= $_SESSION['type_name'] ?>
                </p>
                <?php

                $assessment_id = intval($_SESSION['assessment_id'] ?? 0);
                $student_id = intval($_SESSION['student_id'] ?? 0);

                // Fetch test result summary (new flow)
                $summary = null;
                $answers = [];
                if ($assessment_id > 0 && $student_id > 0) {
                  $sql = "SELECT tr.id, tr.test_id, tr.student_id, tr.obtained_marks, tr.total_marks, tr.percentage,
                                 t.title, t.test_type, t.class_id, t.subject_id, t.total_marks AS configured_total
                          FROM test_results tr
                          JOIN tests t ON tr.test_id = t.id
                          WHERE tr.id = :rid AND tr.student_id = :sid";
                  $stmt = $pdo->prepare($sql);
                  $stmt->execute([':rid' => $assessment_id, ':sid' => $student_id]);
                  $summary = $stmt->fetch(PDO::FETCH_ASSOC);

                  // Load per-question answers if summary exists
                  if ($summary) {
                    $qa = $pdo->prepare("SELECT ta.id, ta.question_id, ta.question_type, ta.selected_answer, ta.correct_answer, ta.is_correct,
                                                 tq.question_text, tq.question_type AS q_type, tq.options AS q_options
                                          FROM test_answers ta
                                          JOIN test_questions tq ON ta.question_id = tq.id
                                          WHERE ta.test_result_id = :rid AND ta.test_id = :tid
                                          ORDER BY ta.id ASC");
                    $qa->execute([':rid' => $assessment_id, ':tid' => $summary['test_id']]);
                    $answers = $qa->fetchAll(PDO::FETCH_ASSOC);
                  }
                }
                ?>

                <div class="grid-body">
                  <div class="item-wrapper">
                    <div class="row">
                      <div class="col-md-12">
                        <!-- <form action="ass.php" method="POST"> -->
                        <div class="showcase_row_area">
                          <?php if ($summary): ?>
                            <div class="mb-4">
                              <h4 class="mb-2">Result Summary</h4>
                              <p><strong>Test:</strong> <?= htmlspecialchars($summary['title']); ?> (<?= htmlspecialchars($summary['test_type']); ?>)</p>
                              <p><strong>Score:</strong> <?= intval($summary['obtained_marks']); ?> / <?= intval($summary['total_marks']); ?> (<?= htmlspecialchars($summary['percentage']); ?>%)</p>
                              <p class="mb-0"><strong>Actual mark:</strong> <?= intval($summary['obtained_marks']); ?></p>
                            </div>

                            <?php if (!empty($answers)): ?>
                              <div class="mb-4">
                                <h5 class="mb-3">Per-question breakdown</h5>
                                <?php foreach ($answers as $i => $row):
                                  $qType = strtolower($row['question_type'] ?: $row['q_type']);
                                  $qText = $row['question_text'];
                                  $sel  = (string)$row['selected_answer'];
                                  $corr = (string)$row['correct_answer'];
                                  $isCorrect = (int)$row['is_correct'] === 1;
                                  $opts = [];
                                  if (!empty($row['q_options'])) {
                                    $decoded = json_decode($row['q_options'], true);
                                    if (is_array($decoded)) { $opts = array_values($decoded); }
                                  }

                                  // Presentable values
                                  $displaySel = $sel;
                                  $displayCorr = $corr;
                                  if ($qType === 'multiple_choice') {
                                    // If numeric index, map to option text (1-based)
                                    if (ctype_digit($sel)) {
                                      $idx = max(1, (int)$sel); $displaySel = $opts[$idx-1] ?? $sel;
                                    }
                                    if (ctype_digit($corr)) {
                                      $idx = max(1, (int)$corr); $displayCorr = $opts[$idx-1] ?? $corr;
                                    }
                                  } elseif ($qType === 'true_false') {
                                    $displaySel = (strtolower($sel) === 'true' || $sel === '1') ? 'True' : ((strtolower($sel) === 'false' || $sel === '0') ? 'False' : $sel);
                                    $displayCorr = (strtolower($corr) === 'true' || $corr === '1') ? 'True' : ((strtolower($corr) === 'false' || $corr === '0') ? 'False' : $corr);
                                  }
                                  ?>
                                  <div class="card mb-3">
                                    <div class="card-body">
                                      <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-2">Q<?= $i+1 ?>. <?= htmlspecialchars($qText) ?></h6>
                                        <span class="badge <?= $isCorrect ? 'bg-success' : 'bg-danger' ?>"><?= $isCorrect ? 'Correct' : 'Incorrect' ?></span>
                                      </div>
                                      <p class="mb-1"><strong>Your answer:</strong> <?= htmlspecialchars($displaySel) ?></p>
                                      <p class="mb-0"><strong>Correct answer:</strong> <?= htmlspecialchars($displayCorr) ?></p>
                                    </div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                          <?php else: ?>
                            <div class="alert alert-warning">No result found to display.</div>
                          <?php endif; ?>


                        </div>
                        <a href="lessons.php">
                          <button type="submit" class="col-12 btn btn-md btn-success social-btn pr-3 view-btn" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">
                            <i class="mdi mdi-undo-variant"></i> Back to Lessons
                          </button></a>
                        <!-- </form> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
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
            <small class="text-muted d-block">Copyright © 2025 <a href="http://www.strad.africa" target="_blank">Nuture
                360&deg;</a>. All rights reserved</small>
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
</body>

</html>