<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject_id'])) {
    $_SESSION['subject_id'] = intval($_POST['subject_id']); //store session
    $_SESSION['class_id'] = $_POST['class_id']; //store session
    $_SESSION['subject_title'] = $_POST['subject_title']; //store session
    $_SESSION['class_title'] = $_POST['class_title']; //store session
    $_SESSION['button_color'] = $_POST['button_color']; //store session

    // $_SESSION['assessment_id'] = intval($_POST['assessment_id']); //store session

    header("Location: i-exams.php"); //redirect to avoid form resubmission
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php
$btnColor = $_SESSION['button_color'] ?? '#6c757d';
// Determine exam duration and start time from admin exam config
$classIdCtx = (int)($_SESSION['class_id'] ?? 0);
$subjectIdCtx = (int)($_SESSION['subject_id'] ?? 0);
$remaining_seconds = 0;
$unlimited_time = false;
try {
    // Pull latest exam template for this class/subject (student_id=0)
    $qExam = $pdo->prepare('SELECT exam_assessment_id, status, timespan FROM exam_assessments WHERE student_id=0 AND class_id=? AND subject_id=? ORDER BY exam_assessment_id DESC LIMIT 1');
    $qExam->execute([$classIdCtx, $subjectIdCtx]);
    $examCfg = $qExam->fetch(PDO::FETCH_ASSOC);
    $durationMin = 30; // fallback
    $startAt = time();
    if ($examCfg) {
        $statusStr = (string)($examCfg['status'] ?? '');
        if (preg_match('/dur=([0-9]+)/', $statusStr, $m)) {
            $durationMin = (int)$m[1];
        }
        $tsStr = (string)($examCfg['timespan'] ?? '');
        $startAt = $tsStr ? strtotime($tsStr) : time();
    }
    if ($durationMin <= 0) {
        $unlimited_time = true;
        $remaining_seconds = 0;
    } else {
        $nowTs = time();
        $elapsed = max(0, $nowTs - (int)$startAt);
        $remaining_seconds = max(0, ($durationMin * 60) - $elapsed);
    }
} catch (Throwable $e) {
    // fallback to 30 minutes if config is missing
    $remaining_seconds = 30 * 60;
}
?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lessons - Nuture 360&deg; | Learning made simple</title>
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
    <link rel="shortcut icon" href="<?= htmlspecialchars($favicon_url) ?>" />
    <style>
      table th,
      table td { text-align: left !important; }
      /* Dynamic theme bindings */
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
                        <a class="nav-link" href="#" id="notificationDropdown" data-toggle="dropdown"
                            aria-expanded="false">
                            <div class="btn action-btn btn-success btn-rounded component-flan" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">
                                <i class="mdi mdi-bell-outline mdi-1x text-white"></i>
                            </div>
                        </a>
                        <div class="dropdown-menu navbar-dropdown dropdown-menu-right"
                            aria-labelledby="notificationDropdown">
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
                    <div class="row">

                        <div class="col-lg-12">
                            <div class="grid">
                                <p class="grid-header"><?= $_SESSION['class_title'] ?> -
                                    <?= $_SESSION['subject_title'] ?> -
                                    <?= $exams_type ?> Examination
                                </p>
                                <div id="examTimerBar" class="alert alert-info d-flex justify-content-between align-items-center" role="alert" style="position: sticky; top: 0; z-index: 1020; <?php echo $unlimited_time ? 'display:none;' : '';?>">
                                    <div>
                                        <i class="mdi mdi-timer"></i>
                                        Time Remaining: <strong id="timeRemaining">--:--</strong>
                                    </div>
                                    <div class="small text-muted">Auto-submit when time ends</div>
                                </div>
                                <?php
                                $class_id = $_SESSION['class_id'];
                                $subject_id = $_SESSION['subject_id'];


                              try {
    $questions       = [];
    $subject_id      =  $_SESSION['subject_id'];
    $totalNeeded     = 50;

    // 1) Fetch up to 20 of type 1
    $limit1 = 20;
    $sql    = "SELECT * FROM exam_questions 
               WHERE subject_id = :subject_id AND type = 1 
               ORDER BY RAND() 
               LIMIT {$limit1}";
    $stmt   = $pdo->prepare($sql);
    $stmt->execute(['subject_id' => $subject_id]);
    $type1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $questions = $type1;
    $count1    = count($type1);

    // 2) If type1 < 20, borrow the shortfall from type2
    $needFrom2For1 = max(0, $limit1 - $count1);
    $borrow2Ids    = [];
    if ($needFrom2For1 > 0) {
        $sql2 = "SELECT * FROM exam_questions 
                 WHERE subject_id = :subject_id AND type = 2 
                 ORDER BY RAND() 
                 LIMIT {$needFrom2For1}";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute(['subject_id' => $subject_id]);
        $type2_for1 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $questions   = array_merge($questions, $type2_for1);
        $count1     += count($type2_for1);
        // remember which IDs we’ve already used from type2
        $borrow2Ids  = array_column($type2_for1, 'question_id');
    }

    // 3) Now fetch up to 20 of type2 *excluding* anything we already borrowed
    $limit2 = 20;
    $in2    = $borrow2Ids
            ? "AND question_id NOT IN (" . implode(',', $borrow2Ids) . ")"
            : "";

    $sql3 = "SELECT * FROM exam_questions
             WHERE subject_id = :subject_id 
               AND type = 2
               {$in2}
             ORDER BY RAND()
             LIMIT {$limit2}";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute(['subject_id' => $subject_id]);
    $type2_primary = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    $questions = array_merge($questions, $type2_primary);
    $count2    = count($type2_primary);

    // 4) If type2_primary < 20, borrow the shortfall from type3
    $needFrom3For2 = max(0, $limit2 - $count2);
    $borrow3Ids    = [];
    if ($needFrom3For2 > 0) {
        $sql4 = "SELECT * FROM exam_questions
                 WHERE subject_id = :subject_id 
                   AND type = 3
                 ORDER BY RAND()
                 LIMIT {$needFrom3For2}";
        $stmt4 = $pdo->prepare($sql4);
        $stmt4->execute(['subject_id' => $subject_id]);
        $type3_for2 = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        $questions   = array_merge($questions, $type3_for2);
        $count2     += count($type3_for2);
        // remember which IDs we’ve already used from type3
        $borrow3Ids  = array_column($type3_for2, 'question_id');
    }

    // 5) Now fetch up to 10 of type3 *excluding* anything we already borrowed
    $limit3 = 10;
    $in3    = $borrow3Ids
            ? "AND question_id NOT IN (" . implode(',', $borrow3Ids) . ")"
            : "";

    $sql5 = "SELECT * FROM exam_questions
             WHERE subject_id = :subject_id 
               AND type = 3
               {$in3}
             ORDER BY RAND()
             LIMIT {$limit3}";
    $stmt5 = $pdo->prepare($sql5);
    $stmt5->execute(['subject_id' => $subject_id]);
    $type3_primary = $stmt5->fetchAll(PDO::FETCH_ASSOC);

    $questions = array_merge($questions, $type3_primary);

    // 6) Final fallback: if we still don’t have 50, grab ANY random questions
    $currentCount = count($questions);
    if ($currentCount < $totalNeeded) {
        $remaining = $totalNeeded - $currentCount;
        // exclude all IDs already in $questions
        $usedIds   = array_column($questions, 'question_id');
        $inAll     = $usedIds
                   ? "AND question_id NOT IN (" . implode(',', $usedIds) . ")"
                   : "";

        $sql6 = "SELECT * FROM exam_questions
                 WHERE subject_id = :subject_id
                 {$inAll}
                 ORDER BY RAND()
                 LIMIT {$remaining}";
        $stmt6 = $pdo->prepare($sql6);
        $stmt6->execute(['subject_id' => $subject_id]);
        $extras = $stmt6->fetchAll(PDO::FETCH_ASSOC);

        $questions = array_merge($questions, $extras);
    }

    // De-duplicate by question_id to avoid duplicates from merges/borrows
    $uniqueById = [];
    foreach ($questions as $q) {
        $qid = $q['question_id'] ?? null;
        if ($qid === null) { continue; }
        $uniqueById[$qid] = $q; // last write wins; order not critical before shuffle
    }
    $questions = array_values($uniqueById);

    // Shuffle the final set so they’re in random order
    shuffle($questions);

    // $questions now contains up to 50 records
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}



                                // if ($type_id == 4) {
                                
                                // Fetch 4 random questions of type=1
                                // $sql1 = "SELECT * FROM exam_questions WHERE subject_id = :subject_id AND type = 1 ORDER BY RAND() LIMIT 4";
                                // $stmt1 = $pdo->prepare($sql1);
                                // $stmt1->execute(['subject_id' => $subject_id]);
                                // $questions_type1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
                                
                                // // Fetch 4 random questions of type=2
                                // $sql2 = "SELECT * FROM exam_questions WHERE subject_id = :subject_id AND type = 2 ORDER BY RAND() LIMIT 4";
                                // $stmt2 = $pdo->prepare($sql2);
                                // $stmt2->execute(['subject_id' => $subject_id]);
                                // $questions_type2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                                
                                // // Fetch 2 random questions of type=3
                                // $sql3 = "SELECT * FROM exam_questions WHERE subject_id = :subject_id AND type = 3 ORDER BY RAND() LIMIT 2";
                                // $stmt3 = $pdo->prepare($sql3);
                                // $stmt3->execute(['subject_id' => $subject_id]);
                                // $questions_type3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Merge all questions into a single array
                                // $questions = array_merge(
                                //     $questions_type1,
                                //     $questions_type2
                                //     // $questions_type3
                                // );
                                
                                // } else {
                                //     // Fetch all questions of the specified type
                                //     $sql = "SELECT * FROM exam_questions WHERE subject_id = :subject_id AND type = :type_id ORDER BY RAND() LIMIT 5";
                                //     $stmt = $pdo->prepare($sql);
                                //     $stmt->execute(['subject_id' => $subject_id, 'type_id' => $type_id]);
                                //     $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                // }
                                
                                ?>

                                <div class="grid-body">
                                    <div class="item-wrapper">
                                        <div class="row">
                                            <div class="col-md-12">
                                                
                                                
                                                <div id="loadingOverlay" style="
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.8);
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
  color: #333;
  pointer-events: all;
">
  Processing, please wait...
</div>


                                                <?php
                                                if (empty($questions)) {
                                                    echo "<p class='text-danger text-center'>No questions found for this subject.</p>";
                                                } else {

                                                    ?>
                                                    <form id="assessmentForm" method="POST">
                                                        <!-- Hidden inputs for type_id, lesson_id, and total questions -->
                                                        <input type="hidden" name="subject_id" value="<?= $subject_id; ?>">
                                                        <input type="hidden" name="class_id" value="<?= $class_id; ?>">
                                                        <input type="hidden" name="total_questions"
                                                            value="<?= count($questions); ?>">

                                                        <div class="showcase_row_area">
                                                            <?php foreach ($questions as $index => $question) { ?>
                                                                <div class="row col-12">
                                                                    <div class="col-md-1"><?= $index + 1; ?>.</div>
                                                                    <div class="col-md-11 mb-5">
                                                                        <div class="mb-2">
                                                                            <p><?= $question['question']; ?></p>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <!-- Hidden inputs to submit question details -->
                                                                            <input type="hidden"
                                                                                name="questions[<?= $question['question_id']; ?>][question]"
                                                                                value="<?= htmlspecialchars($question['question']); ?>">
                                                                            <input type="hidden"
                                                                                name="questions[<?= $question['question_id']; ?>][qtype]"
                                                                                value="<?= isset($question['type']) ? (int)$question['type'] : 0; ?>">

                                                                            <?php
                                                                              $opt1 = trim((string)($question['option1'] ?? ''));
                                                                              $opt2 = trim((string)($question['option2'] ?? ''));
                                                                              $opt3 = trim((string)($question['option3'] ?? ''));
                                                                              $opt4 = trim((string)($question['option4'] ?? ''));
                                                                              $opt5 = trim((string)($question['option5'] ?? ''));
                                                                              $hasOptions = ($opt1 !== '') || ($opt2 !== '') || ($opt3 !== '') || ($opt4 !== '') || ($opt5 !== '');
                                                                              $isEssay = ((int)($question['type'] ?? 0) === 3) || !$hasOptions;
                                                                            ?>

                                                                            <?php if ($isEssay): ?>
                                                                              <!-- Essay / open-ended answer -->
                                                                              <textarea class="form-control" rows="4" placeholder="Type your answer here"
                                                                                name="questions[<?= $question['question_id']; ?>][text_answer]"></textarea>
                                                                              <!-- keep correct_answer empty to avoid auto-grading -->
                                                                              <input type="hidden" name="questions[<?= $question['question_id']; ?>][correct_answer]" value="">
                                                                            <?php else: ?>
                                                                              <!-- MCQ options (up to 5) -->
                                                                              <input type="hidden"
                                                                                name="questions[<?= $question['question_id']; ?>][correct_answer]"
                                                                                value="<?= htmlspecialchars($question['answer']); ?>">

                                                                              <?php if ($opt1 !== ''): ?>
                                                                              <div class="radio">
                                                                                <label class="radio-label mr-4">
                                                                                  <input name="questions[<?= $question['question_id']; ?>][selected_option]" type="radio" value="1" class="text-success">
                                                                                  <?= htmlspecialchars($opt1); ?> <i class="input-frame"></i>
                                                                                </label>
                                                                              </div>
                                                                              <?php endif; ?>

                                                                              <?php if ($opt2 !== ''): ?>
                                                                              <div class="radio">
                                                                                <label class="radio-label mr-4">
                                                                                  <input name="questions[<?= $question['question_id']; ?>][selected_option]" type="radio" value="2">
                                                                                  <?= htmlspecialchars($opt2); ?> <i class="input-frame"></i>
                                                                                </label>
                                                                              </div>
                                                                              <?php endif; ?>

                                                                              <?php if ($opt3 !== ''): ?>
                                                                              <div class="radio">
                                                                                <label class="radio-label mr-4">
                                                                                  <input name="questions[<?= $question['question_id']; ?>][selected_option]" type="radio" value="3">
                                                                                  <?= htmlspecialchars($opt3); ?> <i class="input-frame"></i>
                                                                                </label>
                                                                              </div>
                                                                              <?php endif; ?>

                                                                              <?php if ($opt4 !== ''): ?>
                                                                              <div class="radio">
                                                                                <label class="radio-label mr-4">
                                                                                  <input name="questions[<?= $question['question_id']; ?>][selected_option]" type="radio" value="4">
                                                                                  <?= htmlspecialchars($opt4); ?> <i class="input-frame"></i>
                                                                                </label>
                                                                              </div>
                                                                              <?php endif; ?>

                                                                              <?php if ($opt5 !== ''): ?>
                                                                              <div class="radio">
                                                                                <label class="radio-label mr-4">
                                                                                  <input name="questions[<?= $question['question_id']; ?>][selected_option]" type="radio" value="5">
                                                                                  <?= htmlspecialchars($opt5); ?> <i class="input-frame"></i>
                                                                                </label>
                                                                              </div>
                                                                              <?php endif; ?>
                                                                            <?php endif; ?>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                       <button type="button"
    class="col-12 btn btn-md btn-success social-btn pr-3 view-btn"
    id="confirmSubmitBtn"
    style="
      background-color: <?= htmlspecialchars($btnColor) ?>;
      border-color:     <?= htmlspecialchars($btnColor) ?>;
      color:            #fff;
    ">
    Submit Assessment
    <i class="mdi mdi-pen m-0 pl-2"></i>
</button>

                                                    </form>

                                                <?php } ?>

                                                <div class="modal fade" id="resultModal" tabindex="-1"
                                                    aria-labelledby="resultModalLabel" aria-hidden="true"
                                                    data-bs-backdrop="static" data-bs-keyboard="false">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content rounded-8">
                                                            <div class="modal-header text-white" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">
                                                                <h5 class="modal-title" id="resultModalLabel">Assessment
                                                                    Result</h5>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <!-- Result content will be dynamically inserted here -->
                                                                <p id="resultMessage" class="fs-5"></p>
                                                            </div>
                                                            <div class="modal-footer justify-content-center">
                                                                <a href="lessons.php" class="btn btn-success" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">Back to Subjects</a>
                                                                <button type="button" class="btn"
                                                                    data-bs-dismiss="modal" aria-label="Close"
                                                                    id="closeButton">Close</button>
                                                                <a id="viewResultButton" href="#" class="btn"
                                                                    style="vertical-align: middle;" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">View Results</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                
                                                <div class="modal fade" id="confirmSubmitModal" tabindex="-1" role="dialog" aria-labelledby="confirmSubmitLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="confirmSubmitLabel">Confirm Submission</h5>
        
      </div>
      <div class="modal-body">
        Are you sure you want to submit your assessment? <br>
        <strong>Once you submit, you will not be able to make changes.</strong>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmFinalSubmit">Yes, Submit</button>
      </div>
    </div>
  </div>
</div>


                                                <script>
                                                    // Defer Bootstrap modal init until vendor scripts are loaded
                                                    window.addEventListener('load', function(){
                                                      try {
                                                        if (window.$ && typeof $('#resultModal').modal === 'function') {
                                                          $('#resultModal').modal({backdrop:'static', keyboard:false, show:false});
                                                        }
                                                      } catch(e) { /* ignore */ }
                                                    });
                                                    
// Countdown Timer setup
document.addEventListener('DOMContentLoaded', function(){
  let remainingSeconds = <?php echo (int)$remaining_seconds; ?>;
  const unlimited = <?php echo $unlimited_time ? 'true' : 'false'; ?>;
  const timeEl = document.getElementById('timeRemaining');
  const loadingOverlay = document.getElementById('loadingOverlay');

  function formatTime(total) {
    const m = Math.floor(total / 60);
    const s = total % 60;
    return `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
  }
  function updateDisplay() {
    if (timeEl) timeEl.textContent = formatTime(remainingSeconds);
  }
  function autoSubmitOnTimeout() {
    try { if (loadingOverlay) loadingOverlay.style.display = 'flex'; } catch(e) {}
    submitAssessment(true);
  }
  if (!unlimited) {
    updateDisplay();
    let countdownId = null;
    if (remainingSeconds > 0) {
      countdownId = setInterval(() => {
        remainingSeconds--;
        if (remainingSeconds <= 0) {
          remainingSeconds = 0;
          updateDisplay();
          clearInterval(countdownId);
          autoSubmitOnTimeout();
        } else {
          updateDisplay();
        }
      }, 1000);
    } else {
      // Already timed out on page load
      autoSubmitOnTimeout();
    }
  }
});

// Submission logic reusable for manual and auto
function submitAssessment(isAuto) {
  const form = document.getElementById('assessmentForm');
  if (!form) { window.location.href = 'index.php'; return; }
  const formData = new FormData(form);
  loadingOverlay.style.display = 'flex';
  fetch('process-exams.php', {
      method: 'POST',
      body: formData
  })
  .then(r => r.json())
  .then(data => {
      if (isAuto) {
          // After auto submit, navigate home regardless
          window.location.href = 'index.php';
          return;
      }
      const closeButton = document.getElementById('closeButton');
      const viewResultButton = document.getElementById('viewResultButton');
      const resultMessage = document.getElementById('resultMessage');
      if (data.success) {
          resultMessage.innerText = `Assessment submitted successfully! You scored ${data.percentage}%.`;
          viewResultButton.href = `exam-result.php?assessment_id=${data.assessment_id}`;
          viewResultButton.style.display = 'inline-block';
          closeButton.style.display = 'none';
      } else {
          resultMessage.innerText = `Error: ${data.message}`;
          viewResultButton.style.display = 'none';
          closeButton.style.display = 'inline-block';
      }
      $('#resultModal').modal('show');
  })
  .catch(err => {
      console.error('Error:', err);
      if (isAuto) {
          window.location.href = 'index.php';
          return;
      }
      const closeButton = document.getElementById('closeButton');
      const viewResultButton = document.getElementById('viewResultButton');
      const resultMessage = document.getElementById('resultMessage');
      resultMessage.innerText = 'An error occurred while submitting the assessment. Please try again.';
      viewResultButton.style.display = 'none';
      closeButton.style.display = 'inline-block';
      $('#resultModal').modal('show');
  })
  .finally(() => {
      loadingOverlay.style.display = 'none';
  });
}

// Manual submit flow
document.getElementById('confirmSubmitBtn').addEventListener('click', function () {
    $('#confirmSubmitModal').modal('show');
});

document.getElementById('confirmFinalSubmit').addEventListener('click', function () {
    $('#confirmSubmitModal').modal('hide');
    submitAssessment(false);
});


                                                </script>
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
                        <small class="text-muted d-block">Copyright © 2025 <a href="http://www.strad.africa"
                                target="_blank">Nuture
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
    <script>
  document.addEventListener('contextmenu', function (e) {
    e.preventDefault();
   
  });
</script>

    <!-- endbuild -->
</body>

</html>