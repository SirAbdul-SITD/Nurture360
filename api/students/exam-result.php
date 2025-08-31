<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assessment_id'])) {
  $_SESSION['assessment_id'] = intval($_POST['assessment_id']); //store session
  // $_SESSION['type_name'] = $_POST['type_name']; //store session
  // $_SESSION['type_id'] = $_POST['type_id']; //store session

  // $_SESSION['assessment_id'] = intval($_POST['assessment_id']); //store session

  header("Location: result.php"); //redirect to avoid form resubmission
  exit();
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['assessment_id'])) {
  $_SESSION['assessment_id'] = intval($_GET['assessment_id']); //store session
  // $_SESSION['type_name'] = $_POST['type_name']; //store session
  // $_SESSION['type_id'] = $_POST['type_id']; //store session

  // $_SESSION['assessment_id'] = intval($_POST['assessment_id']); //store session

  header("Location: exam-result.php"); //redirect to avoid form resubmission
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
  <title>Exam Result - <?= htmlspecialchars($app_name ?? 'Nuture 360°') ?> | Learning made simple</title>
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
            <button class="btn btn-success" type="submit" style="
              background-color: <?= htmlspecialchars($btnColor) ?>;
              border-color:     <?= htmlspecialchars($btnColor) ?>;
              color:            #fff;
            ">
              <i class="mdi mdi-arrow-right-thick"></i>
            </button>
          </div>
        </form>
        <ul class="nav ml-auto">
          <li class="nav-item dropdown">
            <a class="nav-link" href="#" id="notificationDropdown" data-toggle="dropdown" aria-expanded="false">
              <div class="btn action-btn btn-success btn-rounded component-flan" style="
                background-color: <?= htmlspecialchars($btnColor) ?>;
                border-color:     <?= htmlspecialchars($btnColor) ?>;
                color:            #fff;
              ">
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

        <div class="content-viewport">
          <div class="row">

            <div class="col-lg-12">
              <div class="grid">
                <p class="grid-header"><?= $_SESSION['class_title'] ?> - <?= $_SESSION['subject_title'] ?> -
                  Third Term Examination Result
                </p>
                <?php

                $assessment_id = $_SESSION['assessment_id'];
                // $lesson_id = $_SESSION['lesson_id'];
                

                // Fetch assessment details
                $sql = "SELECT a.subject_id, a.type, a.status, q.*, ad.correct_answer, ad.student_answer
                  FROM exam_assessments_data ad
                  JOIN exam_questions q ON ad.question_id = q.question_id
                  JOIN exam_assessments a ON ad.exam_assessment_id = a.exam_assessment_id
                  WHERE ad.exam_assessment_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$assessment_id]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                ?>

                <div class="grid-body">
                  <div class="item-wrapper">
                    <div class="row">
                      <div class="col-md-12">
                        <!-- <form action="ass.php" method="POST"> -->
                        <div class="showcase_row_area">
                          <?php if (empty($results)): ?>
                            <div class="alert alert-warning">No result found to display.</div>
                          <?php else: ?>
                            <?php
                              // summary from parent assessment (stored per row via JOIN)
                              $examType = (string)($results[0]['type'] ?? 'Exam');
                              $percentage = isset($results[0]['status']) ? (float)$results[0]['status'] : null;

                              // helper: compute similarity between two texts (0..1)
                              if (!function_exists('n360_text_similarity')) {
                                function n360_text_similarity(string $a, string $b): float {
                                  $normalize = function(string $s): string {
                                    $s = strtolower($s);
                                    // remove punctuation
                                    $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
                                    // collapse whitespace
                                    $s = preg_replace('/\s+/', ' ', trim($s));
                                    return $s;
                                  };
                                  $a = $normalize($a);
                                  $b = $normalize($b);
                                  if ($a === '' || $b === '') return 0.0;
                                  // Jaccard over word sets + scaled levenshtein ratio
                                  $aw = array_values(array_filter(explode(' ', $a)));
                                  $bw = array_values(array_filter(explode(' ', $b)));
                                  $setA = array_unique($aw);
                                  $setB = array_unique($bw);
                                  $intersect = array_intersect($setA, $setB);
                                  $union = array_unique(array_merge($setA, $setB));
                                  $jaccard = count($union) ? (count($intersect) / count($union)) : 0.0;
                                  $maxLen = max(strlen($a), strlen($b));
                                  if ($maxLen === 0) return $jaccard; // both empty
                                  $lev = levenshtein($a, $b);
                                  $levRatio = 1.0 - ($lev / $maxLen);
                                  // weighted blend
                                  return max(0.0, min(1.0, 0.6*$jaccard + 0.4*$levRatio));
                                }
                              }
                              if (!function_exists('n360_best_similarity')) {
                                // Compare text against multiple reference variants, return best score
                                function n360_best_similarity(string $text, array $refs): float {
                                  $best = 0.0;
                                  foreach ($refs as $r) {
                                    $score = n360_text_similarity($text, $r);
                                    if ($score > $best) $best = $score;
                                  }
                                  return $best;
                                }
                              }
                              if (!function_exists('n360_split_variants')) {
                                // Split acceptable answers: by comma, slash, semicolon, or the word 'or'
                                function n360_split_variants(string $s): array {
                                  $parts = preg_split('/,|\/|;|\bor\b/i', $s);
                                  $out = [];
                                  foreach ($parts as $p) {
                                    $p = trim($p);
                                    if ($p !== '') $out[] = $p;
                                  }
                                  // if nothing split, keep whole string
                                  return $out ?: [trim($s)];
                                }
                              }
                            ?>
                            <div class="mb-4">
                              <h4 class="mb-2">Result Summary</h4>
                              <p><strong>Exam:</strong> <?= htmlspecialchars($examType); ?></p>
                              <?php if ($percentage !== null): ?>
                                <p class="mb-0"><strong>Score:</strong> <?= htmlspecialchars(number_format($percentage, 2)); ?>%</p>
                              <?php endif; ?>
                            </div>

                            <?php foreach ($results as $index => $row):
                              $qText = (string)($row['question'] ?? '');
                              $opts = [
                                1 => (string)($row['option1'] ?? ''),
                                2 => (string)($row['option2'] ?? ''),
                                3 => (string)($row['option3'] ?? ''),
                                4 => (string)($row['option4'] ?? ''),
                                5 => (string)($row['option5'] ?? ''),
                              ];
                              $studentRaw = (string)($row['student_answer'] ?? '');
                              $correctRaw = (string)($row['correct_answer'] ?? '');
                              $hasOptions = ($opts[1] !== '' || $opts[2] !== '' || $opts[3] !== '' || $opts[4] !== '' || $opts[5] !== '');
                              $isMCQ = $hasOptions && $correctRaw !== '' && ctype_digit((string)$correctRaw);

                              // Map display values
                              $displaySel = $studentRaw;
                              $displayCorr = $correctRaw;
                              if ($isMCQ) {
                                $sidx = ctype_digit($studentRaw) ? (int)$studentRaw : null;
                                $cidx = (int)$correctRaw;
                                if ($sidx !== null && isset($opts[$sidx]) && $opts[$sidx] !== '') { $displaySel = $opts[$sidx]; }
                                if (isset($opts[$cidx]) && $opts[$cidx] !== '') { $displayCorr = $opts[$cidx]; }
                              }
                              // Determine correctness/similarity
                              $isCorrect = false;
                              $isPartial = false;
                              $similarity = null;
                              if ($isMCQ) {
                                $isCorrect = ($studentRaw !== '' && (int)$studentRaw === (int)$correctRaw);
                              } else {
                                // Essay/short: pick reference
                                $refBase = $correctRaw !== '' ? $correctRaw : ((string)($row['answer'] ?? ''));
                                if ($refBase !== '') {
                                  $variants = n360_split_variants($refBase);
                                  $similarity = n360_best_similarity($studentRaw, $variants);
                                  // thresholds
                                  if ($similarity >= 0.8) {
                                    $isCorrect = true;
                                  } elseif ($similarity >= 0.5) {
                                    $isPartial = true;
                                  }
                                }
                              }
                            ?>
                              <div class="card mb-3">
                                <div class="card-body">
                                  <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-2">Q<?= $index + 1 ?>. <?= htmlspecialchars($qText) ?></h6>
                                    <?php if ($isMCQ): ?>
                                      <span class="badge <?= $isCorrect ? 'bg-success' : 'bg-danger' ?>"><?= $isCorrect ? 'Correct' : 'Incorrect' ?></span>
                                    <?php else: ?>
                                      <?php if ($isCorrect): ?>
                                        <span class="badge bg-success">Correct</span>
                                      <?php elseif ($isPartial): ?>
                                        <span class="badge bg-warning">Partially Correct<?= $similarity !== null ? ' ('.htmlspecialchars(number_format($similarity*100,0)).'%)' : '' ?></span>
                                      <?php else: ?>
                                        <span class="badge bg-danger">Incorrect</span>
                                      <?php endif; ?>
                                    <?php endif; ?>
                                  </div>
                                  <?php if ($studentRaw !== ''): ?>
                                    <p class="mb-1"><strong>Your answer:</strong> <?= htmlspecialchars($displaySel) ?></p>
                                  <?php else: ?>
                                    <p class="mb-1"><span class="badge bg-warning">Not Answered</span></p>
                                  <?php endif; ?>
                                  <?php if ($isMCQ): ?>
                                    <p class="mb-0"><strong>Correct answer:</strong> <?= htmlspecialchars($displayCorr) ?></p>
                                  <?php else: ?>
                                    <?php $refBase = $correctRaw !== '' ? $correctRaw : ((string)($row['answer'] ?? '')); ?>
                                    <?php if ($refBase !== ''): ?>
                                      <p class="mb-0"><strong>Reference answer:</strong> <?= htmlspecialchars($refBase) ?></p>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </div>
                              </div>
                            <?php endforeach; ?>
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