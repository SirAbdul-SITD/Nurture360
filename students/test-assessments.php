<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['class_id'])) {
  $_SESSION['class_id'] = intval($_POST['class_id']); //store session
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
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

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
            <input type="text" class="form-control h-4" id="inlineFormInputGroup" placeholder="Search" autocomplete="off" />
            <button class="btn btn-success" type="submit" style="
              background-color: <?= htmlspecialchars($btnColor) ?>;
              border-color: <?= htmlspecialchars($btnColor) ?>;
              color: #fff;
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
                border-color: <?= htmlspecialchars($btnColor) ?>;
                color: #fff;
              ">
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
                  <div class="icon-wrapper rounded-circle bg-inverse-success text-success">
                    <i class="mdi mdi-cloud-upload"></i>
                  </div>
                  <div class="content-wrapper">
                    <small class="name">Upload Completed</small>
                    <small class="content-text">3 Files uploaded successfully</small>
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
            // Handle case where no lesson is selected
          }
          
          $subject_id_ctx = isset($_SESSION['subject_id']) ? (int)$_SESSION['subject_id'] : 0;
          if (!$subject_id_ctx) {
            echo '<div class="alert alert-info">No subject selected. Please navigate from your subject to view assessments.</div>';
          } else {
            // Determine if tests has a lesson_id column and build query
            $testsHasLessonId = false;
            try {
              $tcols = $pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($tcols as $c) { 
                if (($c['Field'] ?? '') === 'lesson_id') { 
                  $testsHasLessonId = true; 
                  break; 
                } 
              }
            } catch (Throwable $e) { 
              $testsHasLessonId = false; 
            }
          ?>
            <div class="row mb-2">
              <div class="col-lg-12">
                <div class="grid">
                  <span class="grid-header row m-0">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                      <div>
                        <ul class="nav nav-tabs" role="tablist" style="border:0;">
                          <li class="nav-item">
                            <a class="nav-link active" id="tests-tab" data-toggle="tab" href="#testsPane" role="tab">Tests</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link" id="exams-tab" data-toggle="tab" href="#examsPane" role="tab">Exams</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link" id="previous-tab" data-toggle="tab" href="#previousPane" role="tab">Previous Tests & Exams</a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </span>
                  <div class="grid-body">
                    <div class="item-wrapper">
                      <div class="tab-content pt-3">
                        <!-- Tests Tab - Show only upcoming and live tests -->
                        <div class="tab-pane fade show active" id="testsPane" role="tabpanel" aria-labelledby="tests-tab">
                          <?php
                            // Tests: Show only upcoming and live tests (not attempted ones)
                            if ($testsHasLessonId && $lesson_id > 0) {
                              $sql = "SELECT t.id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks,
                                     t.description, t.duration_minutes
                                     FROM tests t
                                     WHERE t.class_id = :class_id AND t.subject_id = :subject_id AND COALESCE(t.is_active,1)=1 
                                     AND t.lesson_id = :lesson_id
                                     AND t.id NOT IN (
                                       SELECT DISTINCT tr.test_id FROM test_results tr WHERE tr.student_id = :student_id
                                     )
                                     ORDER BY t.scheduled_date ASC, t.start_time ASC";
                              $params = [
                                ':student_id' => $student_id,
                                ':class_id' => $class_id,
                                ':subject_id' => $subject_id_ctx,
                                ':lesson_id' => $lesson_id,
                              ];
                            } else {
                              $sql = "SELECT t.id, t.title, t.test_type, t.scheduled_date, t.start_time, t.end_time, t.total_marks,
                                     t.description, t.duration_minutes
                                     FROM tests t
                                     WHERE t.class_id = :class_id AND t.subject_id = :subject_id AND COALESCE(t.is_active,1)=1
                                     AND t.id NOT IN (
                                       SELECT DISTINCT tr.test_id FROM test_results tr WHERE tr.student_id = :student_id
                                     )
                                     ORDER BY t.scheduled_date ASC, t.start_time ASC";
                              $params = [
                                ':student_id' => $student_id,
                                ':class_id' => $class_id,
                                ':subject_id' => $subject_id_ctx,
                              ];
                            }

                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            
                            // Function to determine test status
                            function getTestStatus($scheduledDate, $startTime, $endTime) {
                              $now = new DateTime();
                              $testDate = new DateTime($scheduledDate . ' ' . $startTime);
                              $testEnd = new DateTime($scheduledDate . ' ' . $endTime);
                              
                              if ($now < $testDate) {
                                return ['status' => 'upcoming', 'badge' => 'info', 'text' => 'Upcoming'];
                              } elseif ($now >= $testDate && $now <= $testEnd) {
                                return ['status' => 'live', 'badge' => 'success', 'text' => 'Live Now'];
                              } else {
                                return ['status' => 'ended', 'badge' => 'secondary', 'text' => 'Ended'];
                              }
                            }
                          ?>
                          <?php if ($stmt->rowCount() > 0): ?>
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
                                <?php while ($row = $stmt->fetch()) { 
                                  $testStatus = getTestStatus($row['scheduled_date'], $row['start_time'], $row['end_time']);
                                  // Only show upcoming and live tests
                                  if ($testStatus['status'] === 'ended') continue;
                                ?>
                                  <tr>
                                    <td>
                                      <strong><?= htmlspecialchars($row['title']) ?></strong>
                                      <?php if ($row['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($row['description']) ?></small>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <span class="badge badge-primary"><?= htmlspecialchars(ucfirst($row['test_type'])) ?></span>
                                    </td>
                                    <td>
                                      <?php 
                                        $date = new DateTime($row['scheduled_date']);
                                        echo $date->format('M d, Y');
                                        if ($row['start_time'] && $row['end_time']) {
                                          echo '<br><small class="text-muted">' . $row['start_time'] . ' - ' . $row['end_time'] . '</small>';
                                        }
                                      ?>
                                    </td>
                                    <td>
                                      <?php if ($row['duration_minutes']): ?>
                                        <span class="badge badge-light"><?= (int)$row['duration_minutes'] ?> min</span>
                                      <?php else: ?>
                                        <span class="text-muted">-</span>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <span class="font-weight-bold"><?= (int)$row['total_marks'] ?> marks</span>
                                    </td>
                                    <td>
                                      <span class="badge badge-<?= $testStatus['badge'] ?>">
                                        <?= $testStatus['text'] ?>
                                      </span>
                                    </td>
                                    <td class="actions">
                                      <?php if ($testStatus['status'] === 'live'): ?>
                                        <form action="test.php" method="POST">
                                          <input type="hidden" name="test_id" value="<?= (int)$row['id'] ?>">
                                          <button type="submit" class="btn btn-sm btn-success social-btn pr-3 view-btn" style="
                                            background-color: <?= htmlspecialchars($btnColor) ?>;
                                            border-color: <?= htmlspecialchars($btnColor) ?>;
                                            color: #fff;
                                          ">
                                            Take Test Now
                                            <i class="mdi mdi-pen m-0 pl-2"></i>
                                          </button>
                                        </form>
                                      <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-info" disabled>
                                          <i class="mdi mdi-clock-outline m-0 pr-1"></i>
                                          Coming Soon
                                        </button>
                                      <?php endif; ?>
                                    </td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          <?php else: ?>
                            <div class="text-center py-4">
                              <i class="mdi mdi-clipboard-text-outline" style="font-size: 3rem; color: #ccc;"></i>
                              <h5 class="text-muted mt-3">No Upcoming Tests</h5>
                              <p class="text-muted">There are no upcoming or live tests for this subject at the moment.</p>
                              <a href="#previousPane" class="btn btn-primary" data-toggle="tab">View Previous Tests</a>
                            </div>
                          <?php endif; ?>
                        </div>

                        <!-- Exams Tab - Show only upcoming and live exams -->
                        <div class="tab-pane fade" id="examsPane" role="tabpanel" aria-labelledby="exams-tab">
                          <?php
                            // Exams: Show only upcoming and live exams (not attempted ones)
                            $examTemplates = [];
                            try {
                              $examSql = "SELECT 
                                ea.exam_assessment_id,
                                ea.type,
                                ea.timespan,
                                ea.duration_minutes,
                                ea.total_marks,
                                ea.description,
                                s.subject_name
                              FROM exam_assessments ea
                              LEFT JOIN subjects s ON ea.subject_id = s.id
                              WHERE ea.student_id = 0 
                              AND ea.class_id = :class_id 
                              AND ea.subject_id = :subject_id
                              AND ea.exam_assessment_id NOT IN (
                                SELECT DISTINCT ea2.exam_assessment_id 
                                FROM exam_assessments ea2 
                                WHERE ea2.student_id = :student_id 
                                AND ea2.class_id = :class_id 
                                AND ea2.subject_id = :subject_id
                              )
                              ORDER BY ea.timespan ASC";
                              
                              $examStmt = $pdo->prepare($examSql);
                              $examStmt->execute([
                                ':student_id' => $student_id,
                                ':class_id' => $class_id,
                                ':subject_id' => $subject_id_ctx
                              ]);
                              $examTemplates = $examStmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Throwable $e) { 
                              $examTemplates = []; 
                            }

                            // Function to determine exam status
                            function getExamStatus($timespan) {
                              $now = time();
                              $examTime = strtotime($timespan);
                              
                              if ($examTime === false) return ['status' => 'scheduled', 'badge' => 'info', 'text' => 'Scheduled'];
                              
                              // Assume 2 hour window for live exams
                              $examEnd = $examTime + 7200; // 2 hours
                              
                              if ($now < $examTime) {
                                return ['status' => 'upcoming', 'badge' => 'info', 'text' => 'Upcoming'];
                              } elseif ($now >= $examTime && $now <= $examEnd) {
                                return ['status' => 'live', 'badge' => 'success', 'text' => 'Live Now'];
                              } else {
                                return ['status' => 'ended', 'badge' => 'secondary', 'text' => 'Ended'];
                              }
                            }
                          ?>
                          <?php if (!empty($examTemplates)): ?>
                            <table class="table info-table text-left">
                              <thead>
                                <tr>
                                  <th>Exam</th>
                                  <th>Type</th>
                                  <th>Date & Time</th>
                                  <th>Duration</th>
                                  <th>Total Marks</th>
                                  <th>Status</th>
                                  <th>Action</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($examTemplates as $exam): 
                                  $examStatus = getExamStatus($exam['timespan']);
                                  // Only show upcoming and live exams
                                  if ($examStatus['status'] === 'ended') continue;
                                ?>
                                  <tr>
                                    <td>
                                      <strong><?= htmlspecialchars($exam['subject_name'] ?? 'Subject Exam') ?></strong>
                                      <?php if ($exam['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($exam['description']) ?></small>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <span class="badge badge-success"><?= htmlspecialchars(ucfirst($exam['type'] ?? 'Exam')) ?></span>
                                    </td>
                                    <td>
                                      <?php 
                                        $examDate = new DateTime($exam['timespan']);
                                        echo $examDate->format('M d, Y H:i');
                                      ?>
                                    </td>
                                    <td>
                                      <?php if ($exam['duration_minutes']): ?>
                                        <span class="badge badge-light"><?= (int)$exam['duration_minutes'] ?> min</span>
                                      <?php else: ?>
                                        <span class="text-muted">-</span>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <?php if ($exam['total_marks']): ?>
                                        <span class="font-weight-bold"><?= (int)$exam['total_marks'] ?> marks</span>
                                      <?php else: ?>
                                        <span class="text-muted">-</span>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <span class="badge badge-<?= $examStatus['badge'] ?>">
                                        <?= $examStatus['text'] ?>
                                      </span>
                                    </td>
                                    <td class="actions">
                                      <?php if ($examStatus['status'] === 'live'): ?>
                                        <a href="i-exams.php" class="btn btn-sm btn-success social-btn pr-3 view-btn" style="
                                          background-color: <?= htmlspecialchars($btnColor) ?>;
                                          border-color: <?= htmlspecialchars($btnColor) ?>;
                                          color: #fff;
                                        ">
                                          Take Exam Now
                                          <i class="mdi mdi-pen m-0 pl-2"></i>
                                        </a>
                                      <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-info" disabled>
                                          <i class="mdi mdi-clock-outline m-0 pr-1"></i>
                                          Coming Soon
                                        </button>
                                      <?php endif; ?>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          <?php else: ?>
                            <div class="text-center py-4">
                              <i class="mdi mdi-clipboard-text-outline" style="font-size: 3rem; color: #ccc;"></i>
                              <h5 class="text-muted mt-3">No Upcoming Exams</h5>
                              <p class="text-muted">There are no upcoming or live exams for this subject at the moment.</p>
                              <a href="#previousPane" class="btn btn-primary" data-toggle="tab">View Previous Exams</a>
                            </div>
                          <?php endif; ?>
                        </div>

                        <!-- Previous Tests & Exams Tab - Show all completed assessments -->
                        <div class="tab-pane fade" id="previousPane" role="tabpanel" aria-labelledby="previous-tab">
                          <?php
                            // Fetch all previous tests and exams for the current student
                            $previousAssessments = [];
                            
                            // Get completed tests with results - show ALL tests for the class regardless of subject
                            try {
                              $testSql = "SELECT 
                                'Test' as assessment_type,
                                t.id as assessment_id,
                                t.title,
                                t.test_type,
                                t.scheduled_date,
                                t.start_time,
                                t.end_time,
                                t.total_marks,
                                tr.obtained_marks,
                                tr.percentage,
                                tr.grade,
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
                                ':class_id' => $class_id
                              ]);
                              
                              while ($row = $testStmt->fetch(PDO::FETCH_ASSOC)) {
                                $previousAssessments[] = $row;
                              }
                              
                            } catch (Throwable $e) {
                              // Log error for debugging
                            }
                            
                            // Get completed exams with results - show ALL exams for the class regardless of subject
                            try {
                              $examSql = "SELECT 
                                'Exam' as assessment_type,
                                ea.exam_assessment_id as assessment_id,
                                ea.subject_id,
                                CONCAT(s.subject_name, ' - ', ea.type) as title,
                                ea.type as test_type,
                                ea.timespan as scheduled_date,
                                '' as start_time,
                                '' as end_time,
                                0 as total_marks,
                                0 as obtained_marks,
                                NULL as percentage,
                                '' as grade,
                                ea.timespan as submitted_at,
                                s.subject_name,
                                c.class_name
                              FROM exam_assessments ea
                              LEFT JOIN subjects s ON ea.subject_id = s.id
                              LEFT JOIN classes c ON ea.class_id = c.id
                              WHERE ea.student_id = :student_id 
                              AND ea.class_id = :class_id
                              ORDER BY ea.timespan DESC";
                              
                              $examStmt = $pdo->prepare($examSql);
                              $examStmt->execute([
                                ':student_id' => $student_id,
                                ':class_id' => $class_id
                              ]);
                              
                              while ($row = $examStmt->fetch(PDO::FETCH_ASSOC)) {
                                // For exams, we'll show basic info without complex score calculation
                                // The exam-result.php page will handle the detailed score calculation
                                $row['obtained_marks'] = 0;
                                $row['total_marks'] = 0;
                                $row['percentage'] = null;
                                $row['grade'] = '';
                                $previousAssessments[] = $row;
                              }
                              
                            } catch (Throwable $e) {
                              // Log error for debugging
                            }
                            
                            // Sort all assessments by date (most recent first)
                            usort($previousAssessments, function($a, $b) {
                              $dateA = strtotime($a['submitted_at']);
                              $dateB = strtotime($b['submitted_at']);
                              return $dateB - $dateA;
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
                                  <th>Score</th>
                                  <th>Grade</th>
                                  <th>Action</th>
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
                                      <span class="badge badge-<?= $assessment['assessment_type'] === 'Test' ? 'primary' : 'success' ?>">
                                        <?= htmlspecialchars($assessment['assessment_type']) ?>
                                      </span>
                                      <br><small class="text-muted"><?= htmlspecialchars(ucfirst($assessment['test_type'])) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($assessment['subject_name']) ?></td>
                                    <td>
                                      <?php 
                                        $date = new DateTime($assessment['scheduled_date']);
                                        echo $date->format('M d, Y');
                                        if ($assessment['start_time'] && $assessment['end_time']) {
                                          echo '<br><small class="text-muted">' . $assessment['start_time'] . ' - ' . $assessment['end_time'] . '</small>';
                                        }
                                      ?>
                                    </td>
                                    <td>
                                      <?php if ($assessment['percentage'] !== null): ?>
                                        <div class="d-flex align-items-center">
                                          <div class="progress mr-2" style="width: 60px; height: 8px;">
                                            <div class="progress-bar bg-<?= $assessment['percentage'] >= 70 ? 'success' : ($assessment['percentage'] >= 50 ? 'warning' : 'danger') ?>" 
                                                 style="width: <?= $assessment['percentage'] ?>%"></div>
                                          </div>
                                          <span class="font-weight-bold"><?= number_format($assessment['percentage'], 1) ?>%</span>
                                        </div>
                                        <small class="text-muted">
                                          <?= $assessment['obtained_marks'] ?>/<?= $assessment['total_marks'] ?> marks
                                        </small>
                                      <?php else: ?>
                                        <span class="text-muted">-</span>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <?php if ($assessment['grade']): ?>
                                        <span class="badge badge-<?= $assessment['grade'] === 'A' ? 'success' : ($assessment['grade'] === 'B' ? 'info' : ($assessment['grade'] === 'C' ? 'warning' : 'danger')) ?>">
                                          <?= htmlspecialchars($assessment['grade']) ?>
                                        </span>
                                      <?php else: ?>
                                        <span class="text-muted">-</span>
                                      <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                      <?php if ($assessment['assessment_type'] === 'Test'): ?>
                                        <form action="result.php" method="POST">
                                          <input type="hidden" name="assessment_id" value="<?= (int)$assessment['assessment_id'] ?>">
                                          <button type="submit" class="btn btn-sm btn-rounded btn-success social-btn pr-3 view-btn" style="
                                            background-color: <?= htmlspecialchars($btnColor) ?>;
                                            border-color: <?= htmlspecialchars($btnColor) ?>;
                                            color: #fff;
                                          ">
                                            View Result
                                            <i class="mdi mdi-arrow-right-thick m-0 pl-2"></i>
                                          </button>
                                        </form>
                                      <?php else: ?>
                                        <form action="exam-result.php" method="POST">
                                          <input type="hidden" name="assessment_id" value="<?= (int)$assessment['assessment_id'] ?>">
                                          <button type="submit" class="btn btn-sm btn-rounded btn-success social-btn pr-3 view-btn" style="
                                            background-color: <?= htmlspecialchars($btnColor) ?>;
                                            border-color: <?= htmlspecialchars($btnColor) ?>;
                                            color: #fff;
                                          ">
                                            View Result
                                            <i class="mdi mdi-arrow-right-thick m-0 pl-2"></i>
                                          </button>
                                        </form>
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
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php
          } 
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
            <small class="text-muted d-block">Copyright © 2025 <a href="#" target="_blank">Nurture360°</a>. All rights reserved</small>
            <small class="text-gray mt-2">Handcrafted With <i class="mdi mdi-heart text-danger"></i></small>
          </div>
        </div>
      </footer>
      <!-- partial -->
    </div>
    <!-- page content ends -->
  </div>

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
  <!-- build:js -->
</body>
</html>
