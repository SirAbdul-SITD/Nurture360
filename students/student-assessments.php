<?php require_once('settings.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['lesson_id'])) {
  $_SESSION['lesson_id'] = intval($_POST['lesson_id']); //store session
  if (isset($_POST['type_name'])) { $_SESSION['type_name'] = $_POST['type_name']; }
  if (isset($_POST['type_id']))   { $_SESSION['type_id']   = (int)$_POST['type_id']; }
  if (isset($_POST['test_id']))   { $_SESSION['test_id']   = (int)$_POST['test_id']; }

  header("Location: student-assessments.php"); //redirect to avoid form resubmission
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
  <title>Take Assessment - <?= htmlspecialchars($app_name ?? 'Nuture 360°') ?> | Learning made simple</title>
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
        <div class="viewport-header">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb has-arrow">
              <li class="breadcrumb-item">
                <a href="lesson.php"><?= $_SESSION['lesson_title'] ?></a>
              </li>
              <li class="breadcrumb-item">
                <a href="assessments.php">My Assessments</a>
              </li>
              <li class="breadcrumb-item" aria-current="page" style="color: <?= htmlspecialchars($btnColor) ?>;">Take
                Assessment</li>
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
                $lesson_id = $_SESSION['lesson_id'] ?? 0;
                $type_id = isset($_SESSION['type_id']) ? (int)$_SESSION['type_id'] : 0;
                $questions = [];

                // If a test_id is set, prioritize loading questions from test_questions
                $test_id = isset($_SESSION['test_id']) ? (int)$_SESSION['test_id'] : 0;
                if ($test_id > 0) {
                  $st = $pdo->prepare("SELECT id, question_text, question_type, options, correct_answer, marks FROM test_questions WHERE test_id = :tid ORDER BY id ASC");
                  $st->execute([':tid' => $test_id]);
                  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

                  // Normalize to existing render format
                  foreach ($rows as $r) {
                    $opts = [];
                    if (!empty($r['options'])) {
                      $decoded = json_decode($r['options'], true);
                      if (is_array($decoded)) { $opts = array_values($decoded); }
                    }
                    // Ensure at least 4 option placeholders for renderer
                    for ($i=0; $i<4; $i++) { if (!isset($opts[$i])) { $opts[$i] = ''; } }
                    $questions[] = [
                      'question_id' => (int)$r['id'],
                      'question'    => $r['question_text'],
                      'option1'     => $opts[0],
                      'option2'     => $opts[1],
                      'option3'     => $opts[2],
                      'option4'     => $opts[3],
                      'answer'      => $r['correct_answer'],
                      'question_type' => strtolower($r['question_type']),
                      'options_arr'   => $opts,
                    ];
                  }
                }

                // Fallback to legacy lesson/type-based questions if no test questions loaded
                if (empty($questions)) {
                  if ($type_id === 4) {
                    $sql1 = "SELECT * FROM questions WHERE lesson_id = :lesson_id AND type = 1 ORDER BY RAND() LIMIT 4";
                    $stmt1 = $pdo->prepare($sql1);
                    $stmt1->execute(['lesson_id' => $lesson_id]);
                    $questions_type1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

                    $sql2 = "SELECT * FROM questions WHERE lesson_id = :lesson_id AND type = 2 ORDER BY RAND() LIMIT 4";
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute(['lesson_id' => $lesson_id]);
                    $questions_type2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                    $sql3 = "SELECT * FROM questions WHERE lesson_id = :lesson_id AND type = 3 ORDER BY RAND() LIMIT 2";
                    $stmt3 = $pdo->prepare($sql3);
                    $stmt3->execute(['lesson_id' => $lesson_id]);
                    $questions_type3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

                    $questions = array_merge($questions_type1, $questions_type2, $questions_type3);
                  } else {
                    $sql = "SELECT * FROM questions WHERE lesson_id = :lesson_id AND type = :type_id ORDER BY RAND() LIMIT 5";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['lesson_id' => $lesson_id, 'type_id' => $type_id]);
                    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  }
                }
                ?>

                <div class="grid-body">
                  <div class="item-wrapper">
                    <div class="row">
                      <div class="col-md-12">

                        <?php
                        if (empty($questions)) {
                          echo "<p class='text-danger text-center'>No questions found for this assessment.</p>";
                        } else {

                          ?>
                          <form id="assessmentForm" method="POST">
                            <!-- Hidden inputs for type_id, lesson_id, and total questions -->
                            <input type="hidden" name="type_id" value="<?= $type_id; ?>">
                            <input type="hidden" name="lesson_id" value="<?= $lesson_id; ?>">
                            <input type="hidden" name="total_questions" value="<?= count($questions); ?>">

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
                                      <input type="hidden" name="questions[<?= $question['question_id']; ?>][question]"
                                        value="<?= htmlspecialchars($question['question']); ?>">
                                      <?php
                                        $qType = isset($question['question_type']) ? $question['question_type'] : 'multiple_choice';
                                        $optsArr = isset($question['options_arr']) ? $question['options_arr'] : [
                                          $question['option1'] ?? '', $question['option2'] ?? '', $question['option3'] ?? '', $question['option4'] ?? ''
                                        ];
                                        echo '<input type="hidden" name="questions['.$question['question_id'].'][question_type]" value="'.htmlspecialchars($qType).'">';

                                        if ($qType === 'multiple_choice') {
                                          // Map correct_answer to index if it's value-based
                                          $correct = (string)($question['answer'] ?? '');
                                          $correctIndex = null;
                                          if (is_numeric($correct)) { $correctIndex = (int)$correct; }
                                          else {
                                            foreach ($optsArr as $i => $opt) { if ((string)$opt === $correct) { $correctIndex = $i+1; break; } }
                                            if ($correctIndex === null) { $correctIndex = 1; }
                                          }
                                          echo '<input type="hidden" name="questions['.$question['question_id'].'][correct_answer]" value="'.htmlspecialchars($correctIndex).'">';

                                          // Render radios for options
                                          foreach ($optsArr as $i => $opt) {
                                            $val = $i+1;
                                            echo '<div class="radio">'
                                               . '<label class="radio-label mr-4">'
                                               . '<input name="questions['.$question['question_id'].'][selected_option]" type="radio" value="'.$val.'">'
                                               . htmlspecialchars($opt)
                                               . ' <i class="input-frame"></i>'
                                               . '</label>'
                                               . '</div>';
                                          }
                                        } elseif ($qType === 'true_false') {
                                          // Normalize correct answer to 'true'/'false'
                                          $correct = strtolower(trim((string)($question['answer'] ?? '')));
                                          if ($correct === '1') $correct = 'true';
                                          if ($correct === '0') $correct = 'false';
                                          if ($correct !== 'true' && $correct !== 'false') $correct = 'true';
                                          echo '<input type="hidden" name="questions['.$question['question_id'].'][correct_answer]" value="'.$correct.'">';
                                          echo '<div class="radio">'
                                             . '<label class="radio-label mr-4">'
                                             . '<input name="questions['.$question['question_id'].'][selected_option]" type="radio" value="true"> True <i class="input-frame"></i>'
                                             . '</label>'
                                             . '</div>';
                                          echo '<div class="radio">'
                                             . '<label class="radio-label mr-4">'
                                             . '<input name="questions['.$question['question_id'].'][selected_option]" type="radio" value="false"> False <i class="input-frame"></i>'
                                             . '</label>'
                                             . '</div>';
                                        } elseif ($qType === 'short_answer' || $qType === 'essay') {
                                          // Free text answer via textarea
                                          $correct = (string)($question['answer'] ?? '');
                                          echo '<input type="hidden" name="questions['.$question['question_id'].'][correct_answer]" value="'.htmlspecialchars($correct).'">';
                                          echo '<textarea class="form-control" name="questions['.$question['question_id'].'][selected_option]" rows="'.($qType==='essay'?6:3).'" placeholder="Type your answer here..."></textarea>';
                                        } else {
                                          // Default fallback: multiple choice radios
                                          $correct = (string)($question['answer'] ?? '');
                                          $correctIndex = is_numeric($correct) ? (int)$correct : 1;
                                          echo '<input type="hidden" name="questions['.$question['question_id'].'][correct_answer]" value="'.htmlspecialchars($correctIndex).'">';
                                          foreach ($optsArr as $i => $opt) {
                                            $val = $i+1;
                                            echo '<div class="radio">'
                                               . '<label class="radio-label mr-4">'
                                               . '<input name="questions['.$question['question_id'].'][selected_option]" type="radio" value="'.$val.'">'
                                               . htmlspecialchars($opt)
                                               . ' <i class="input-frame"></i>'
                                               . '</label>'
                                               . '</div>';
                                          }
                                        }
                                      ?>

                                    </div>
                                  </div>
                                </div>
                              <?php } ?>
                            </div>
                            <button type="submit" class="col-12 btn btn-md btn-success social-btn pr-3 view-btn" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">
                              Submit Assessment
                              <i class="mdi mdi-pen m-0 pl-2"></i>
                            </button>
                          </form>

                        <?php } ?>

                        <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel"
                          aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-8">
                              <div class="modal-header text-white" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">
                                <h5 class="modal-title" id="resultModalLabel">Assessment Result</h5>
                              </div>
                              <div class="modal-body text-center">
                                <!-- Result content will be dynamically inserted here -->
                                <p id="resultMessage" class="fs-5"></p>
                              </div>
                              <div class="modal-footer justify-content-center">
                                <a href="assessments.php" class="btn btn-success" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">Back to Assessments</a>
                                <button type="button" class="btn" data-bs-dismiss="modal" aria-label="Close"
                                  id="closeButton">Close</button>
                                <a id="viewResultButton" href="#" class="btn" style="vertical-align: middle;" style="
          background-color: <?= htmlspecialchars($btnColor) ?>;
          border-color:     <?= htmlspecialchars($btnColor) ?>;
          color:            #fff;
        ">View Results</a>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Bootstrap 5 JS -->
                        <script
                          src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                        <script>
                          // Initialize the modal
                          const resultModal = new bootstrap.Modal(document.getElementById('resultModal'), {
                            backdrop: 'static',
                            keyboard: false
                          });

                          document.getElementById('assessmentForm').addEventListener('submit', function (e) {
                            e.preventDefault(); // Prevent default form submission

                            const form = e.target;
                            const formData = new FormData(form);

                            // Send the form data via AJAX
                            fetch('process.php', {
                              method: 'POST',
                              body: formData
                            })
                              .then(response => response.json())
                              .then(data => {
                                const closeButton = document.getElementById('closeButton');
                                const viewResultButton = document.getElementById('viewResultButton');
                                const resultMessage = document.getElementById('resultMessage');

                                if (data.success) {
                                  // Success response
                                  resultMessage.innerText = `Assessment submitted successfully! You scored ${data.percentage}%.`;
                                  viewResultButton.href = `result.php?assessment_id=${data.assessment_id}`;
                                  viewResultButton.style.display = 'inline-block'; // Show "View Results" button
                                  closeButton.style.display = 'none'; // Hide the close button
                                } else {
                                  // Error response
                                  resultMessage.innerText = `Error: ${data.message}`;
                                  viewResultButton.style.display = 'none'; // Hide "View Results" button
                                  closeButton.style.display = 'inline-block'; // Show the close button
                                }

                                // Show the modal
                                resultModal.show();
                              })
                              .catch(error => {
                                console.error('Error:', error);
                                const closeButton = document.getElementById('closeButton');
                                const viewResultButton = document.getElementById('viewResultButton');
                                const resultMessage = document.getElementById('resultMessage');

                                // Handle unexpected errors
                                resultMessage.innerText = 'An error occurred while submitting the assessment. Please try again.';
                                viewResultButton.style.display = 'none'; // Hide "View Results" button
                                closeButton.style.display = 'inline-block'; // Show the close button

                                // Show the modal
                                resultModal.show();
                              });
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