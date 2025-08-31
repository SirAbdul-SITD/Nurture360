<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['class_id'])) {
  $_SESSION['class_id'] =
    intval($_POST['class_id']); //store session
  $_SESSION['lesson_id'] = intval($_POST['lesson_id']); //store session
  $_SESSION['lesson_title'] = $_POST['lesson_title']; //store session
  unset($_SESSION['assessment_id']);
  unset($_SESSION['first_name']);
  unset($_SESSION['last_name']);
  header("Location: assessments.php"); //redirect to avoid form resubmission
  exit();

} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assessment_id'])) {
  $_SESSION['first_name'] = $_POST['first_name']; //store session
  $_SESSION['last_name'] = $_POST['last_name']; //store session
  $_SESSION['assessment_id'] = intval($_POST['assessment_id']); //store session
  unset($_SESSION['class_id']);
  unset($_SESSION['lesson_id']);
  header("Location: assessments.php"); //redirect to avoid form resubmission
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
$btnColor = $_SESSION['button_color'] ?? ($theme_primary_color ?? '#6c757d');
?>

<head>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Removed CDN jQuery/Popper/Bootstrap JS to avoid conflicts with template vendor scripts loaded at page end. -->

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Assessments - <?= htmlspecialchars($app_name ?? 'Nuture 360°') ?> | Learning made simple</title>
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
        <div class="viewport-header">
          <div class="row">
            <nav class="col-lg-10" aria-label="breadcrumb">
              <ol class="breadcrumb has-arrow">
                <li class="breadcrumb-item">
                  <a href="lesson.php"><?= $_SESSION['lesson_title'] ?></a>
                </li>
                <li class="breadcrumb-item" aria-current="page" style="color: <?= htmlspecialchars($btnColor) ?>;">
                  My Assessments
                </li>
              </ol>
            </nav>

          </div>
        </div>
        <div class="content-viewport">
          <?php
          // Fallbacks: prefer session class_id; else use active class from settings.php (stored into $_SESSION['class_id'] there)
          $class_id = $_SESSION['class_id'] ?? ($my_class ?? 0);
          $class_title = $_SESSION['class_title'] ?? ($my_class_Title ?? '');
          $lesson_id = isset($_SESSION['lesson_id']) ? (int)$_SESSION['lesson_id'] : 0;

          if (!$class_id) {
            echo '<div class="alert alert-warning">No class selected. Please go to <a href="index.php">My Subjects</a> and pick a subject.</div>';
          }
          if (!$lesson_id) {
          
          }
          
            $subject_id_ctx = isset($_SESSION['subject_id']) ? (int)$_SESSION['subject_id'] : 0;
            if (!$subject_id_ctx):
              echo '<div class="alert alert-info">No subject selected. Please navigate from your subject to view assessments.</div>';
            else:
              // Determine if tests has a lesson_id column and build query
              $testsHasLessonId = false;
              try {
                $tcols = $pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($tcols as $c) { if (($c['Field'] ?? '') === 'lesson_id') { $testsHasLessonId = true; break; } }
              } catch (Throwable $e) { $testsHasLessonId = false; }

              if ($testsHasLessonId && $lesson_id > 0) {
                $sql = "SELECT t.id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks,
                               tr.id AS result_id, tr.percentage
                        FROM tests t
                        LEFT JOIN test_results tr ON tr.test_id = t.id AND tr.student_id = :student_id
                        WHERE t.class_id = :class_id AND t.subject_id = :subject_id AND COALESCE(t.is_active,1)=1 AND t.lesson_id = :lesson_id
                        ORDER BY t.scheduled_date DESC, t.start_time DESC";
                $params = [
                  ':student_id' => $student_id,
                  ':class_id' => $class_id,
                  ':subject_id' => $subject_id_ctx,
                  ':lesson_id' => $lesson_id,
                ];
              } else {
                $sql = "SELECT t.id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks,
                               tr.id AS result_id, tr.percentage
                        FROM tests t
                        LEFT JOIN test_results tr ON tr.test_id = t.id AND tr.student_id = :student_id
                        WHERE t.class_id = :class_id AND t.subject_id = :subject_id AND COALESCE(t.is_active,1)=1
                        ORDER BY t.scheduled_date DESC, t.start_time DESC";
                $params = [
                  ':student_id' => $student_id,
                  ':class_id' => $class_id,
                  ':subject_id' => $subject_id_ctx,
                ];
              }

              $stmt = $pdo->prepare($sql);
              $stmt->execute($params);

              ?>
              <div class="row mb-2">
                <div class="col-lg-12">
                  <div class="grid">
                    <span class="grid-header row m-0">
                      <div class="d-flex w-100 justify-content-between align-items-center">
                        <div>
                          <ul class="nav nav-tabs" id="assessmentsTab" role="tablist">
                            <li class="nav-item">
                              <a class="nav-link active" id="upcoming-tab" data-toggle="tab" href="#upcomingPane" role="tab" aria-controls="upcoming" aria-selected="true">
                                <i class="mdi mdi-calendar-clock mr-1"></i> Upcoming
                              </a>
                            </li>
                            <li class="nav-item">
                              <a class="nav-link" id="live-tab" data-toggle="tab" href="#livePane" role="tab" aria-controls="live" aria-selected="false">
                                <i class="mdi mdi-account-clock mr-1"></i> Live Now
                              </a>
                            </li>
                            <li class="nav-item">
                              <a class="nav-link" id="previous-tab" data-toggle="tab" href="#previousPane" role="tab" aria-controls="previous" aria-selected="false">
                                <i class="mdi mdi-history mr-1"></i> Previous
                              </a>
                            </li>
                            <li class="nav-item">
                              <a class="nav-link" id="all-tab" data-toggle="tab" href="#allPane" role="tab" aria-controls="all" aria-selected="false">
                                <i class="mdi mdi-view-list mr-1"></i> All
                              </a>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </span>
                    <div class="grid-body">
                      <div class="item-wrapper">
                        <div class="tab-content pt-3">
                          <!-- Upcoming Assessments Tab -->
                          <div class="tab-pane fade show active" id="upcomingPane" role="tabpanel" aria-labelledby="upcoming-tab">
                            <?php
                            // Fetch upcoming tests and exams
                            $upcomingAssessments = [];
                            $now = new DateTime();
                            
                            // Get upcoming tests
                            try {
                            $testSql = "SELECT 
                                'Test' as assessment_type,
                                t.id as assessment_id,
                                t.title,
                                t.description,
                                t.test_type as type,
                                t.total_marks,
                                t.duration_minutes,
                                t.scheduled_date,
                                t.start_time,
                                t.end_time,
                                c.class_name,
                                s.subject_name,
                                CONCAT(u.first_name, ' ', u.last_name) as teacher_name
                              FROM tests t
                              JOIN classes c ON t.class_id = c.id
                              JOIN subjects s ON t.subject_id = s.id
                              LEFT JOIN users u ON t.teacher_id = u.id
                              LEFT JOIN test_results tr ON t.id = tr.test_id AND tr.student_id = ?
                              WHERE t.class_id = ? 
                              AND t.subject_id = ?
                              AND t.scheduled_date >= CURDATE()
                              AND (t.scheduled_date > CURDATE() OR t.end_time > CURTIME())
                              AND tr.id IS NULL
                              ORDER BY t.scheduled_date, t.start_time";
                            
                            $testStmt = $pdo->prepare($testSql);
                            $testStmt->execute([$student_id, $class_id, $subject_id_ctx]);
                            $upcomingTests = $testStmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Throwable $e) {
                              echo "<!-- Upcoming Tests query error: " . htmlspecialchars($e->getMessage()) . " -->\n";
                              $upcomingTests = [];
                            }
                            
                            // Get upcoming exams (exam_assessments)
                            try {
                            $examSql = "SELECT 
                                'Exam' as assessment_type,
                                ea.exam_assessment_id as assessment_id,
                                CONCAT(s.subject_name, ' - ', ea.type) as title,
                                NULL as description,
                                ea.type as type,
                                NULL as total_marks,
                                NULL as duration_minutes,
                                DATE(ea.timespan) as scheduled_date,
                                TIME(ea.timespan) as start_time,
                                NULL as end_time,
                                c.class_name,
                                s.subject_name,
                                NULL as teacher_name
                              FROM exam_assessments ea
                              JOIN classes c ON ea.class_id = c.id
                              JOIN subjects s ON ea.subject_id = s.id
                              LEFT JOIN exam_results er ON ea.exam_assessment_id = er.exam_assessment_id AND er.student_id = ?
                              WHERE ea.class_id = ? 
                              AND ea.subject_id = ?
                              AND DATE(ea.timespan) >= CURDATE()
                              AND (DATE(ea.timespan) > CURDATE() OR TIME(ea.timespan) > CURTIME())
                              AND er.id IS NULL
                              ORDER BY DATE(ea.timespan), TIME(ea.timespan)";
                            
                            $examStmt = $pdo->prepare($examSql);
                            $examStmt->execute([$student_id, $class_id, $subject_id_ctx]);
                            $upcomingExams = $examStmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Throwable $e) {
                              echo "<!-- Upcoming Exams query error: " . htmlspecialchars($e->getMessage()) . " -->\n";
                              $upcomingExams = [];
                            }
                            
                            $upcomingAssessments = array_merge($upcomingTests, $upcomingExams);
                            
                            // Sort by date and time
                            usort($upcomingAssessments, function($a, $b) {
                              $dateA = new DateTime($a['scheduled_date'] . ' ' . $a['start_time']);
                              $dateB = new DateTime($b['scheduled_date'] . ' ' . $b['start_time']);
                              return $dateA <=> $dateB;
                            });
                            ?>
                            
                            <?php if (!empty($upcomingAssessments)): ?>
                              <div class="table-responsive">
                                <table class="table table-hover">
                                  <thead class="thead-light">
                                    <tr>
                                      <th>Assessment</th>
                                      <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                      <th>Subject</th>
                                      <th>Assessment Type</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($upcomingAssessments as $assessment): 
                                      $startDateTime = new DateTime($assessment['scheduled_date'] . ' ' . ($assessment['start_time'] ?? ''));
                                      $endTime = !empty($assessment['end_time']) ? (new DateTime($assessment['scheduled_date'] . ' ' . $assessment['end_time']))->format('g:i A') : '';
                                    ?>
                                      <tr>
                                        <td><strong><?= htmlspecialchars($assessment['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($assessment['type']) ?></td>
                                        <td><?= htmlspecialchars($startDateTime->format('M j, Y')) ?></td>
                                        <td>
                                          <?= htmlspecialchars($startDateTime->format('g:i A')) ?>
                                          <?php if ($endTime): ?> - <?= htmlspecialchars($endTime) ?><?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($assessment['subject_name']) ?></td>
                                        <td><?= htmlspecialchars($assessment['assessment_type']) ?></td>
                                      </tr>
                                    <?php endforeach; ?>
                                  </tbody>
                                </table>
                              </div>
                            <?php else: ?>
                              <div class="text-center py-5">
                                <i class="mdi mdi-calendar-remove-outline" style="font-size: 4rem; color: #e0e0e0;"></i>
                                <h4 class="mt-3 text-muted">No Upcoming Assessments</h4>
                                <p class="text-muted">You don't have any upcoming tests or exams scheduled.</p>
                              </div>
                            <?php endif; ?>
                          </div>
                          <!-- Live Assessments Tab -->
                          <div class="tab-pane fade" id="livePane" role="tabpanel" aria-labelledby="live-tab">
                            <?php
                            // Fetch live tests and exams
                            $liveAssessments = [];
                            // Live tests
                            try {
                            $liveTestSql = "SELECT 'Test' as assessment_type, t.id as assessment_id, t.title, t.test_type as type,
                                                    t.total_marks, t.duration_minutes, t.scheduled_date, t.start_time, t.end_time,
                                                    c.class_name, s.subject_name, CONCAT(u.first_name,' ',u.last_name) as teacher_name
                                             FROM tests t
                                             JOIN classes c ON t.class_id=c.id
                                             JOIN subjects s ON t.subject_id=s.id
                                             LEFT JOIN users u ON t.teacher_id=u.id
                                             LEFT JOIN test_results tr ON t.id=tr.test_id AND tr.student_id=?
                                             WHERE t.class_id=? AND t.subject_id=?
                                               AND t.scheduled_date=CURDATE() AND t.start_time<=CURTIME() AND t.end_time>=CURTIME()
                                               AND tr.id IS NULL
                                             ORDER BY t.end_time";
                            $liveTestStmt = $pdo->prepare($liveTestSql);
                            $liveTestStmt->execute([$student_id, $class_id, $subject_id_ctx]);
                            $liveTests = $liveTestStmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Throwable $e) {
                              echo "<!-- Live Tests query error: " . htmlspecialchars($e->getMessage()) . " -->\n";
                              $liveTests = [];
                            }

                            // Live exams (exam_assessments)
                            try {
                            $liveExamSql = "SELECT 'Exam' as assessment_type,
                                                    ea.exam_assessment_id as assessment_id,
                                                    CONCAT(s.subject_name, ' - ', ea.type) as title,
                                                    ea.type as type,
                                                    NULL as total_marks,
                                                    NULL as duration_minutes,
                                                    DATE(ea.timespan) as scheduled_date,
                                                    TIME(ea.timespan) as start_time,
                                                    NULL as end_time,
                                                    c.class_name, s.subject_name, NULL as teacher_name
                                             FROM exam_assessments ea
                                             JOIN classes c ON ea.class_id=c.id
                                             JOIN subjects s ON ea.subject_id=s.id
                                             LEFT JOIN exam_results er ON ea.exam_assessment_id=er.exam_assessment_id AND er.student_id=?
                                             WHERE ea.class_id=? AND ea.subject_id=?
                                               AND DATE(ea.timespan)=CURDATE() AND TIME(ea.timespan) <= CURTIME()
                                               AND er.id IS NULL
                                             ORDER BY TIME(ea.timespan)";
                            $liveExamStmt = $pdo->prepare($liveExamSql);
                            $liveExamStmt->execute([$student_id, $class_id, $subject_id_ctx]);
                            $liveExams = $liveExamStmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Throwable $e) {
                              echo "<!-- Live Exams query error: " . htmlspecialchars($e->getMessage()) . " -->\n";
                              $liveExams = [];
                            }

                            $liveAssessments = array_merge($liveTests, $liveExams);
                            usort($liveAssessments, function($a,$b){
                              $aEnd = strtotime($a['scheduled_date'].' '.$a['end_time']);
                              $bEnd = strtotime($b['scheduled_date'].' '.$b['end_time']);
                              return $aEnd <=> $bEnd;
                            });
                            ?>
                            <?php if (!empty($liveAssessments)): ?>
                              <div class="alert alert-warning"><i class="mdi mdi-alert"></i> Live assessments are in progress. You can start only these.</div>
                              <div class="table-responsive">
                                <table class="table table-hover">
                                  <thead class="thead-light">
                                    <tr>
                                      <th>Assessment</th>
                                <th>Type</th>
                                      <th>Date</th>
                                      <th>Time</th>
                                      <th>Subject</th>
                                      <th>Assessment Type</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                                    <?php foreach ($liveAssessments as $assessment): $isExam = $assessment['assessment_type']==='Exam'; ?>
                                      <tr>
                                        <td><strong><?= htmlspecialchars($assessment['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($assessment['type']) ?></td>
                                        <td><?= htmlspecialchars(date('M j, Y', strtotime($assessment['scheduled_date']))) ?></td>
                                        <td>
                                          <?php if (!empty($assessment['end_time'])): ?>
                                            <?= htmlspecialchars(date('g:i A', strtotime($assessment['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($assessment['end_time']))) ?>
                                    <?php else: ?>
                                            <?= htmlspecialchars(date('g:i A', strtotime($assessment['start_time']))) ?>
                                          <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($assessment['subject_name']) ?></td>
                                        <td><?= htmlspecialchars($assessment['assessment_type']) ?></td>
                                        <td>
                                          <?php 
                                            $now = new DateTime();
                                            $ended = false;
                                            if (!empty($assessment['end_time'])) {
                                              $rowEnd = new DateTime($assessment['scheduled_date'].' '.$assessment['end_time']);
                                              $ended = ($now > $rowEnd);
                                            }
                                          ?>
                                          <?php if ($ended): ?>
                                            <span class="text-muted"><?= $isExam ? 'Exam ended' : 'Test ended' ?></span>
                                          <?php else: ?>
                                            <form action="student-assessments.php" method="POST" class="mb-0">
                                              <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">
                                              <input type="hidden" name="type_name" value="<?= $isExam ? 'Exam':'Test' ?>">
                                              <input type="hidden" name="test_id" value="<?= (int)$assessment['assessment_id'] ?>">
                                              <button type="submit" class="btn btn-sm btn-warning"><i class="mdi mdi-pen"></i> Start</button>
                                      </form>
                                    <?php endif; ?>
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
                                <p class="text-muted">There are no tests or exams live right now.</p>
                              </div>
                          <?php endif; ?>
                          </div>

                          <!-- All Assessments Tab -->
                          <div class="tab-pane fade" id="allPane" role="tabpanel" aria-labelledby="all-tab">
                            <?php
                            $allAssessments = [];
                            try {
                              // Tests with optional result
                              $allTestSql = "SELECT 
                                  'Test' as assessment_type,
                                  t.id as assessment_id,
                                  t.title,
                                  t.test_type as type,
                                  t.total_marks,
                                  t.duration_minutes,
                                  t.scheduled_date,
                                  t.start_time,
                                  t.end_time,
                                  c.class_name,
                                  s.subject_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                                  tr.id as result_id,
                                  tr.obtained_marks,
                                  tr.percentage,
                                  tr.grade,
                                  tr.submitted_at
                                FROM tests t
                                JOIN classes c ON t.class_id = c.id
                                JOIN subjects s ON t.subject_id = s.id
                                LEFT JOIN users u ON t.teacher_id = u.id
                                LEFT JOIN test_results tr ON t.id = tr.test_id AND tr.student_id = ?
                                WHERE t.class_id = ? AND t.subject_id = ?";
                              $allTestStmt = $pdo->prepare($allTestSql);
                              $allTestStmt->execute([$student_id, $class_id, $subject_id_ctx]);
                              $allTests = $allTestStmt->fetchAll(PDO::FETCH_ASSOC);

                              // Exams with optional result (join via exam_assessments -> exam_results)
                              $allExamSql = "SELECT 
                                  'Exam' AS assessment_type,
                                  ea.exam_assessment_id AS assessment_id,
                                  CONCAT(s.subject_name, ' - ', ea.type) AS title,
                                  ea.type AS type,
                                  er.total_marks,
                                  NULL AS duration_minutes,
                                  DATE(ea.timespan) AS scheduled_date,
                                  '' AS start_time,
                                  '' AS end_time,
                                  c.class_name,
                                  s.subject_name,
                                  NULL AS teacher_name,
                                  er.id AS result_id,
                                  er.obtained_marks,
                                  er.percentage,
                                  er.grade,
                                  er.submitted_at
                                FROM exam_assessments ea
                                LEFT JOIN exam_results er ON er.exam_assessment_id = ea.exam_assessment_id AND er.student_id = ?
                                LEFT JOIN subjects s ON ea.subject_id = s.id
                                LEFT JOIN classes c ON ea.class_id = c.id
                                WHERE ea.class_id = ? AND ea.subject_id = ?";
                              $allExamStmt = $pdo->prepare($allExamSql);
                              $allExamStmt->execute([$student_id, $class_id, $subject_id_ctx]);
                              $allExams = $allExamStmt->fetchAll(PDO::FETCH_ASSOC);

                              $allAssessments = array_merge($allTests, $allExams);
                            } catch (Throwable $e) {
                              // Fallback: if exam tables don't exist, just show tests
                              if (empty($allAssessments)) {
                                $allAssessments = $allTests ?? [];
                              }
                            }

                            // Determine status for each item
                            $nowTs = time();
                            foreach ($allAssessments as &$a) {
                              $startTs = strtotime($a['scheduled_date'] . ' ' . ($a['start_time'] ?: '00:00:00'));
                              $endTs = strtotime($a['scheduled_date'] . ' ' . ($a['end_time'] ?: '23:59:59'));
                              if (!empty($a['result_id'])) {
                                $a['status'] = 'completed';
                              } elseif ($startTs <= $nowTs && $endTs >= $nowTs) {
                                $a['status'] = 'live';
                              } elseif ($startTs > $nowTs) {
                                $a['status'] = 'upcoming';
                              } else {
                                $a['status'] = 'missed';
                              }
                            }
                            unset($a);

                            // Sort by date/time ascending
                            usort($allAssessments, function($x,$y){
                              $dx = strtotime($x['scheduled_date'] . ' ' . $x['start_time']);
                              $dy = strtotime($y['scheduled_date'] . ' ' . $y['start_time']);
                              return $dx <=> $dy;
                            });
                            ?>

                            <?php if (!empty($allAssessments)): ?>
                              <div class="table-responsive">
                                <table class="table table-hover">
                                  <thead class="thead-light">
                                    <tr>
                                      <th>Assessment</th>
                                      <th>Type</th>
                                      <th>Date</th>
                                      <th>Status</th>
                                      <th>Marks</th>
                                      <th>Total</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($allAssessments as $a): 
                                      $isExam = $a['assessment_type'] === 'Exam';
                                      $statusBadge = [
                                        'completed' => 'success',
                                        'live' => 'warning',
                                        'upcoming' => 'info',
                                        'missed' => 'secondary'
                                      ][$a['status']] ?? 'secondary';
                                    ?>
                                      <tr>
                                        <td>
                                          <strong><?= htmlspecialchars($a['title']) ?></strong>
                                          <br><small class="text-muted"><?= htmlspecialchars($a['subject_name']) ?> · <?= htmlspecialchars($a['class_name']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($a['type']) ?></td>
                                        <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($a['scheduled_date'].' '.$a['start_time']))) ?></td>
                                        <td><?= htmlspecialchars($a['status']) ?></td>
                                        <td>
                                          <?php if (!empty($a['result_id'])): ?>
                                            <?= (int)$a['obtained_marks'] ?><?php if (isset($a['percentage'])): ?> (<?= number_format((float)$a['percentage'],1) ?>%)<?php endif; ?>
                                          <?php else: ?>
                                            <span class="text-muted">-</span>
                                          <?php endif; ?>
                                        </td>
                                        <td>
                                          <?php if (isset($a['total_marks'])): ?>
                                            <?= (int)$a['total_marks'] ?>
                                          <?php else: ?>
                                            <span class="text-muted">-</span>
                                          <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                  </tbody>
                                </table>
                              </div>
                            <?php else: ?>
                              <div class="text-center py-5">
                                <i class="mdi mdi-view-list" style="font-size: 4rem; color: #e0e0e0;"></i>
                                <h4 class="mt-3 text-muted">No Assessments</h4>
                                <p class="text-muted">There are no tests or exams for this subject yet.</p>
                              </div>
                            <?php endif; ?>
                          </div>

                          <div class="tab-pane fade" id="previousPane" role="tabpanel" aria-labelledby="previous-tab">
                            <?php
                              // Fetch all previous tests and exams for the current student
                              $previousAssessments = [];
                              
                              // Get completed tests with results (all subjects in this class)
                              try {
                                $testSql = "SELECT 
                                  'Test' AS assessment_type,
                                  t.id AS assessment_id,
                                  t.title,
                                  t.test_type,
                                  t.scheduled_date,
                                  t.start_time,
                                  t.end_time,
                                  t.total_marks,
                                  tr.obtained_marks,
                                  tr.percentage,
                                  tr.grade,
                                  tr.graded_at,
                                  tr.submitted_at,
                                  s.subject_name,
                                  c.class_name
                                FROM test_results tr
                                INNER JOIN tests t ON tr.test_id = t.id
                                LEFT JOIN subjects s ON t.subject_id = s.id
                                LEFT JOIN classes c ON t.class_id = c.id
                                WHERE tr.student_id = :student_id
                                  AND t.class_id = :class_id
                                ORDER BY tr.submitted_at DESC";
                                $testStmt = $pdo->prepare($testSql);
                                $testStmt->execute([
                                  ':student_id' => $student_id,
                                  ':class_id'   => $class_id,
                                ]);
                                while ($row = $testStmt->fetch(PDO::FETCH_ASSOC)) {
                                  $previousAssessments[] = $row;
                                }
                              } catch (Throwable $e) {
                                if (!empty($_GET['debug'])) {
                                  echo "<!-- Debug: Test query error: " . htmlspecialchars($e->getMessage()) . " -->\n";
                                }
                              }
                              
                              // Get completed exams with results (prefer exam_results). Fall back to exam_assessments only if needed.
                              $examRowsAdded = 0;
                              try {
                                $examSql = "SELECT 
                                  'Exam' AS assessment_type,
                                  ea.exam_assessment_id AS assessment_id,
                                  CONCAT(s.subject_name, ' - ', ea.type) AS title,
                                  ea.type AS test_type,
                                  DATE(ea.timespan) AS scheduled_date,
                                  '' AS start_time,
                                  '' AS end_time,
                                  er.total_marks,
                                  er.obtained_marks,
                                  er.percentage,
                                  er.grade,
                                  er.status AS grading_status,
                                  er.submitted_at,
                                  s.subject_name,
                                  c.class_name
                                FROM exam_results er
                                INNER JOIN exam_assessments ea ON er.exam_assessment_id = ea.exam_assessment_id
                                LEFT JOIN subjects s ON ea.subject_id = s.id
                                LEFT JOIN classes c ON ea.class_id = c.id
                                WHERE er.student_id = :student_id
                                  AND ea.class_id = :class_id
                                ORDER BY er.submitted_at DESC";
                                $examStmt = $pdo->prepare($examSql);
                                $examStmt->execute([
                                  ':student_id' => $student_id,
                                  ':class_id'   => $class_id,
                                ]);
                                while ($row = $examStmt->fetch(PDO::FETCH_ASSOC)) {
                                  $previousAssessments[] = $row;
                                  $examRowsAdded++;
                                }
                              } catch (Throwable $e) {
                                // If exam_results is missing or errors, we will fall back below
                                if (!empty($_GET['debug'])) {
                                  echo "<!-- Debug: Exam results query error: " . htmlspecialchars($e->getMessage()) . " -->\n";
                                }
                              }
                              
                              // Fallback: use exam_assessments (basic info only) if no exam rows were added
                              if ($examRowsAdded === 0) {
                                try {
                                  $fallbackExamSql = "SELECT 
                                    'Exam' AS assessment_type,
                                    ea.exam_assessment_id AS assessment_id,
                                    CONCAT(s.subject_name, ' - ', ea.type) AS title,
                                    ea.type AS test_type,
                                    ea.timespan AS submitted_at,
                                    ea.timespan AS scheduled_date,
                                    '' AS start_time,
                                    '' AS end_time,
                                    0 AS total_marks,
                                    0 AS obtained_marks,
                                    NULL AS percentage,
                                    '' AS grade,
                                    s.subject_name,
                                    c.class_name
                                  FROM exam_assessments ea
                                  LEFT JOIN subjects s ON ea.subject_id = s.id
                                  LEFT JOIN classes c ON ea.class_id = c.id
                                  WHERE ea.student_id = :student_id
                                    AND ea.class_id = :class_id
                                  ORDER BY ea.timespan DESC";
                                  $fallbackExamStmt = $pdo->prepare($fallbackExamSql);
                                  $fallbackExamStmt->execute([
                                    ':student_id' => $student_id,
                                    ':class_id'   => $class_id,
                                  ]);
                                  while ($row = $fallbackExamStmt->fetch(PDO::FETCH_ASSOC)) {
                                    $previousAssessments[] = $row;
                                  }
                                } catch (Throwable $e) {
                                  if (!empty($_GET['debug'])) {
                                    echo "<!-- Debug: Exam fallback query error: " . htmlspecialchars($e->getMessage()) . " -->\n";
                                  }
                                }
                              }
                              
                              // Sort all assessments by submission/date (most recent first)
                              usort($previousAssessments, function($a, $b) {
                                $dateA = isset($a['submitted_at']) ? strtotime($a['submitted_at']) : 0;
                                $dateB = isset($b['submitted_at']) ? strtotime($b['submitted_at']) : 0;
                                return $dateB <=> $dateA;
                              });
                            ?>
                            
                            <?php if (!empty($previousAssessments)): ?>
                              <div class="row mb-3">
                                <div class="col-12">
                                  <div class="alert alert-info">
                                    <i class="mdi mdi-information-outline"></i>
                                    Showing all completed assessments for <?= htmlspecialchars($class_title) ?> across all subjects
                                  </div>
                                </div>
                              </div>
                              
                              <table class="table info-table text-left">
                                <thead>
                                  <tr>
                                    <th>Assessment</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Marks</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($previousAssessments as $assessment): ?>
                                    <tr>
                                      <td>
                                        <strong><?= htmlspecialchars($assessment['title']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($assessment['class_name']) ?></small>
                                      </td>
                                      <td>
                                        <?= htmlspecialchars($assessment['assessment_type']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(ucfirst($assessment['test_type'])) ?></small>
                                      </td>
                                      <td><?= htmlspecialchars($assessment['subject_name']) ?></td>
                                      <td>
                                        <?php 
                                          $date = new DateTime($assessment['scheduled_date']);
                                          echo $date->format('M d, Y');
                                          if (!empty($assessment['start_time']) && !empty($assessment['end_time'])) {
                                            echo '<br><small class="text-muted">' . $assessment['start_time'] . ' - ' . $assessment['end_time'] . '</small>';
                                          }
                                        ?>
                                      </td>
                                      <td>
                                        <?php
                                          $status = null;
                                          if ($assessment['assessment_type'] === 'Exam') {
                                            $raw = strtolower((string)($assessment['grading_status'] ?? ''));
                                            if ($raw === 'graded' || $raw === 'published') {
                                              $status = 'graded';
                                            } elseif ($raw === 'pending' || $raw === '') {
                                              $status = 'inreview';
                                            } else {
                                              $status = $raw; // fallback to whatever value is stored
                                            }
                                          } else {
                                            $status = !empty($assessment['graded_at']) ? 'graded' : 'inreview';
                                          }
                                          if (!$status && $assessment['percentage'] !== null) { $status = 'graded'; }
                                          if (!$status) { $status = 'inreview'; }
                                        ?>
                                        <?= htmlspecialchars($status) ?>
                                      </td>
                                      <td>
                                        <?php if ($assessment['obtained_marks'] !== null): ?>
                                          <?= (int)$assessment['obtained_marks'] ?><?php if ($assessment['percentage'] !== null): ?> (<?= number_format((float)$assessment['percentage'],1) ?>%)<?php endif; ?>
                                        <?php else: ?>
                                          <span class="text-muted">-</span>
                                        <?php endif; ?>
                                      </td>
                                      <td>
                                        <?php if ($assessment['total_marks'] !== null): ?>
                                          <?= (int)$assessment['total_marks'] ?>
                                        <?php else: ?>
                                          <span class="text-muted">-</span>
                                        <?php endif; ?>
                                      </td>
                                      <td>
                                        <?php if ($assessment['grade']): ?>
                                          <?= htmlspecialchars($assessment['grade']) ?>
                                        <?php else: ?>
                                          <span class="text-muted">-</span>
                                        <?php endif; ?>
                                          </td>
                                        </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            <?php else: ?>
                              <div class="text-center py-4">
                                <i class="mdi mdi-clipboard-text-outline" style="font-size: 3rem; color: #ccc;"></i>
                                <h5 class="text-muted mt-3">No Previous Assessments</h5>
                                <p class="text-muted">You haven't completed any tests or exams yet.</p>
                                <a href="#testsPane" class="btn btn-primary" data-toggle="tab">Take Your First Test</a>
                              </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['debug'])): ?>
                              <pre class="small text-muted" style="white-space:pre-wrap;">Debug Previous:
context = {class_id: <?= (int)$class_id ?>, subject_id: <?= (int)$subject_id_ctx ?>, student_id: <?= (int)$student_id ?>}
total_assessments = <?= count($previousAssessments) ?>
tests_found = <?= count(array_filter($previousAssessments, function($a) { return $a['assessment_type'] === 'Test'; })) ?>
exams_found = <?= count(array_filter($previousAssessments, function($a) { return $a['assessment_type'] === 'Exam'; })) ?>

Test Query Debug:
SQL: SELECT 'Test' as assessment_type, t.id as assessment_id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks, tr.obtained_marks, tr.percentage, tr.grade, tr.submitted_at, s.subject_name, c.class_name FROM test_results tr INNER JOIN tests t ON tr.test_id = t.id LEFT JOIN subjects s ON t.subject_id = s.id LEFT JOIN classes c ON t.class_id = c.id WHERE tr.student_id = :student_id AND t.class_id = :class_id ORDER BY tr.submitted_at DESC

Parameters: student_id=<?= (int)$student_id ?>, class_id=<?= (int)$class_id ?>

Raw test_results data:
<?php
try {
  $debugStmt = $pdo->prepare("SELECT tr.*, t.title, t.class_id, t.subject_id FROM test_results tr INNER JOIN tests t ON tr.test_id = t.id WHERE tr.student_id = ?");
  $debugStmt->execute([$student_id]);
  $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
  echo htmlspecialchars(json_encode($debugResults, JSON_PRETTY_PRINT));
} catch (Throwable $e) {
  echo "Error: " . htmlspecialchars($e->getMessage());
}
?></pre>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <?php
            endif;
          ?>

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
            <small class="text-muted d-block">Copyright © 2025 <a href="#" target="_blank">Nurture360°</a>. All
              rights reserved</small>
            <small class="text-gray mt-2">Handcrafted With <i class="mdi mdi-heart text-danger"></i></small>
          </div>
        </div>
      </footer>

      <!-- partial -->
    </div>
    <!-- page content ends -->
  </div>



  <!-- Modal
  <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="responseModalLabel">Response</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
         Response content will be loaded here 
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div> -->

  <!-- JavaScript to handle form submission and display modal -->
  <!-- <script>
    $(document).ready(function () {
      $('.view-btn').on('click', function () {
        var $form = $(this).closest('.assessment-form');
        var formData = $form.serialize();
        $.ajax({
          url: $form.attr('action'),
          type: 'POST',
          data: formData,
          success: function (response) {
            $('#responseModal .modal-body').html(response);
            $('#responseModal').modal('show');
          },
          error: function () {
            $('#responseModal .modal-body').html('An error occurred. Please try again.');
            $('#responseModal').modal('show');
          }
        });
      });
    });
  </script> -->

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('input[name="assessment_id"]').forEach(function (input) {
        var sIdInput = document.createElement('input');
        sIdInput.type = 'hidden';
        sIdInput.name = 'assessment_ids';
        sIdInput.value = input.value;
        input.closest('form').appendChild(sIdInput);
      });
    });
  </script>

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
    // Fallback loader: ensure jQuery, Popper, and Bootstrap 4 tabs are available
    (function() {
      function loadScript(src, cb){ var s=document.createElement('script'); s.src=src; s.onload=cb; s.async=false; document.head.appendChild(s); }
      function ensureBootstrapTabs(cb){
        if (typeof window.jQuery === 'function' && window.jQuery.fn && typeof window.jQuery.fn.tab === 'function') { cb(); return; }
        // Ensure jQuery
        if (typeof window.jQuery !== 'function') {
          return loadScript('https://code.jquery.com/jquery-3.5.1.min.js', function(){ ensureBootstrapTabs(cb); });
        }
        // Ensure Popper (for BS4)
        if (typeof window.Popper === 'undefined') {
          return loadScript('https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js', function(){ ensureBootstrapTabs(cb); });
        }
        // Ensure Bootstrap 4 JS
        loadScript('https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', function(){ cb(); });
      }
      window.__initTabs = function(){
        if (typeof window.jQuery === 'function' && window.jQuery.fn && typeof window.jQuery.fn.tab === 'function') {
          var $ = window.jQuery;
          var $tabs = $('#assessmentsTab a[data-toggle="tab"]');
          $tabs.on('click', function(e){ e.preventDefault(); $(this).tab('show'); });
          var hash = window.location.hash;
          if (hash && $('#assessmentsTab a[href="'+hash+'"]').length) { $('#assessmentsTab a[href="'+hash+'"]').tab('show'); }
          $tabs.on('shown.bs.tab', function(e){ var href = e.target.getAttribute('href'); if (href) { history.replaceState(null, null, href); } });
        }
      };
      ensureBootstrapTabs(function(){ if (document.readyState !== 'loading') { window.__initTabs(); } else { document.addEventListener('DOMContentLoaded', window.__initTabs); } });
    })();
  </script>
  <script>
    // Ensure Bootstrap 4 tabs initialize and respond to clicks/hash
    (function() {
      function ready(fn){ if (document.readyState!=="loading") { fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
      ready(function(){
        if (typeof $ === 'function' && typeof $.fn.tab === 'function') {
          var $tabs = $('#assessmentsTab a[data-toggle="tab"]');
          // click -> show
          $tabs.on('click', function(e){ e.preventDefault(); $(this).tab('show'); });
          // show from URL hash
          var hash = window.location.hash;
          if (hash && $('#assessmentsTab a[href="'+hash+'"]').length) {
            $('#assessmentsTab a[href="'+hash+'"]').tab('show');
          }
          // persist hash
          $tabs.on('shown.bs.tab', function(e){ var href = e.target.getAttribute('href'); if (href) { history.replaceState(null, null, href); } });
        }
      });
    })();
  </script>
</body>

</html>
