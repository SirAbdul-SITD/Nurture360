<?php require_once('settings.php');
// if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject_id'])) {
//   $_SESSION['subject_id'] = intval($_POST['subject_id']); //store session
//   $_SESSION['class_id'] = intval($_POST['class_id']); //store session
//   $_SESSION['subject_title'] = $_POST['subject_title']; //store session
//   $_SESSION['class_title'] = $_POST['class_title']; //store session
//   $_SESSION['num_lessons'] = intval($_POST['num_lessons']); //store session
//   header("Location: lessons.php"); //redirect to avoid form resubmission
//   exit();
// }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Lessons - <?= htmlspecialchars($app_name ?? 'Nuture 360°') ?> | Learning made simple</title>
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
    table th,
    table td { text-align: left !important; }
  </style>
  <style>
    table th,
    table td {
      text-align: left !important;
    }
  </style>
</head>

<body class="header-fixed">
  <?php $btnColor = $_SESSION['button_color'] ?? ($theme_primary_color ?? '#28a745'); ?>
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
          <?php
          $sql = "SELECT sub.subject_id, sub.title AS subject_title, c.class_id, c.title AS class_title, 
                    COUNT(l.lesson_id) AS num_lessons
                    FROM subjects sub
                    LEFT JOIN classes c ON sub.class_id = c.class_id 
                    LEFT JOIN lessons l ON sub.subject_id = l.subject_id
                    GROUP BY sub.subject_id, sub.title, c.title ORDER BY sub.title ASC";

          $stmt = $pdo->prepare($sql);
          $stmt->execute();
          $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
          $num_subjects = count($subjects);
          ?>
          <div class="row col-12">
            <div class="col-10 py-5">
              <h4>All Subjects</h4>
              <p class="text-gray">Ordered by subjects | subjects: <?= $num_subjects ?></p>
            </div>
            <div class="col-2 py-5">
              <h6>Filter:</h6>
              <div class="showcase_content_area">
                <select class="custom-select" id="subjectFilter">
                  <option selected>-- Select Subject --</option>
                  <?php
                  $distinctSubjects = array_unique(array_column($subjects, 'subject_title'));
                  foreach ($distinctSubjects as $subjectTitle) { ?>
                    <option value="<?= $subjectTitle; ?>"><?= $subjectTitle; ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row" id="subjectGrid">
            <?php foreach ($subjects as $row) { ?>
              <!-- Lesson -->
              <div class="col-lg-4 col-md-6 col-sm-12 equel-grid subject-item"
                data-subject-title="<?= $row['subject_title']; ?>">
                <div class="grid">
                  <div class="grid-body">
                    <!-- Thumbnail -->
                    <div class="d-flex mt-3">
                      <!-- <img class="col-12" src="assets/images/imagjes.png" alt="" srcset="" /> -->
                    </div>


                    <!-- lesson topic -->
                    <a href="lesson.php">
                      <div class="d-flex mt-2 mb-4">
                        <h5 class="mb-0 text-success"><?= $row['subject_title']; ?></h5>
                      </div>
                    </a>

                    <!-- class and subject -->
                    <div class="d-flex justify-content-between py-2 mt-3">
                      <p class="text-gray">
                        <i class="mdi mdi-book-open-page-variant text-success b"></i>
                        <?php if ($row['num_lessons'] > 1) {
                          echo $row['num_lessons'] . ' Lessons';
                        } else {
                          echo $row['num_lessons'] . ' Lesson';
                        }
                        ; ?>
                      </p>
                      <p class="text-black">
                        <i class="mdi mdi-chart-donut text-success"></i>
                        Class: <?= $row['class_title']; ?>
                      </p>

                    </div>


                    <form action="lessons.php" method="POST">
                      <input type="hidden" name="subject_id" value="<?= $row['subject_id']; ?>">
                      <input type="hidden" name="class_id" value="<?= $row['class_id']; ?>">
                      <input type="hidden" name="subject_title" value="<?= $row['subject_title']; ?>">
                      <input type="hidden" name="class_title" value="<?= $row['class_title']; ?>">
                      <input type="hidden" name="num_lessons" value="<?= $row['num_lessons']; ?>">
                      <button type="submit" class="btn btn-success has-icon btn-block mt-0" style="
                        background-color: <?= htmlspecialchars($btnColor) ?>;
                        border-color:     <?= htmlspecialchars($btnColor) ?>;
                        color:            #fff;
                      ">
                        View Lesson
                        <i class="mdi mdi-arrow-right-thick m-0 pl-2"></i>
                      </button>
                    </form>
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