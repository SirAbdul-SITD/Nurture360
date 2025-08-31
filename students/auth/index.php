<?php
require("config.php");


// Redirect to dashboard only when full student session is present
if (isset($_SESSION['email']) && isset($_SESSION['student_id'])) {
  header('Location: ../index.php');
  exit();
} else {
  // Check if the form is submitted via AJAX
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Retrieve form data
    $identifier = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $response = ['success' => false, 'message' => ''];

    try {
      if ($identifier === '' || $password === '') {
        throw new Exception('Email/Username and password are required.');
      }

      // Determine whether identifier is email or username
      if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'student'");
        $stmt->execute([$identifier]);
      } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'student'");
        $stmt->execute([$identifier]);
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user && password_verify($password, $user['password_hash'])) {
        // Authentication successful
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['student_id'] = $user['id'];

        // Optional: derive class_id from active enrollment
        try {
          $stmtC = $pdo->prepare("SELECT class_id FROM student_enrollments WHERE student_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
          $stmtC->execute([$_SESSION['student_id']]);
          $rowC = $stmtC->fetch(PDO::FETCH_ASSOC);
          if ($rowC && isset($rowC['class_id'])) {
            $_SESSION['class_id'] = (int)$rowC['class_id'];
          }
        } catch (Throwable $e) { /* ignore */ }

        $response['success'] = true;
        $response['message'] = 'Authentication successful. Redirecting to your dashboard...';
      } else {
        $response['success'] = false;
        $response['message'] = 'Sorry, the credentials are incorrect. Please check and try again';
      }
    } catch (Throwable $e) {
      $response['success'] = false;
      $response['message'] = 'Login failed: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
  }
}

?>
<!DOCTYPE html>

<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="assets/"
  data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>Login | Nurture360°</title>

  <meta name="description" content="" />

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../favlogo.png" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet" />

  <!-- Icons. Uncomment required icon fonts -->
  <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="assets/css/demo.css" />

  <!-- Vendors CSS -->
  <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

  <!-- Page CSS -->
  <!-- Page -->
  <link rel="stylesheet" href="assets/vendor/css/pages/page-auth.css" />
  <!-- Helpers -->
  <script src="assets/vendor/js/helpers.js"></script>

  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="assets/js/config.js"></script>
  <style>
    .popup {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 10px 20px;
      border-radius: 5px;
      font-size: 14px;
      z-index: 9999;
      display: flex;
      align-items: center;
      background-color: rgba(0, 10, 5, 0.8);
      /* Background color with opacity */
      color: #fff;
    }

    .popup.success {
      background-color: #4CAF50;
      color: #fff;
    }

    .popup.error {
      background-color: #F44336;
      color: white;
    }

    .popup i {
      margin-right: 5px;
    }
  </style>
</head>

<body>
  <!-- Content -->

  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <!-- Register -->
        <div class="card">
          <div class="card-body">

            <!-- /Logo -->
            <div class="app-brand justify-content-center">
              <a href="index.php" class="app-brand-link gap-2">
                <h3>Nurture360°</h3>
              </a>
            </div>
            <p class="mb-4">Kindly log in to your account to proceed with the adventure.</p>

            <form id="signin-form" class="mb-3" method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">Email or Username</label>
                <input type="text" class="form-control" id="email" name="email"
                  placeholder="Enter your email or username" autofocus />
              </div>
              <div class="mb-3 form-password-toggle">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="password">Password</label>
                  <a href="forgot_password.php">
                    <small style="color: #14a39a">Forgot Password?</small>
                  </a>
                </div>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control" name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    aria-describedby="password" />
                  <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember-me" />
                  <label class="form-check-label" for="remember-me"> Remember Me </label>
                </div>
              </div>
              <div class="mb-3">
                <button class="btn btn-warning d-grid w-100" id="login_button" type="button">Sign in</button>
              </div>
            </form>
            <center>
              <p class="text-center">
                <a href="signup.php">
                  <span style="color: #14a39a">Sign Up</span>
                </a>
              </p>
            </center>

          </div>
        </div>
        <p class="text-center">
          <span>Don't have a password yet?</span>
          <a href="otp_login.php">
            <span style="color: #14a39a">Login with OTP</span>
          </a>
        </p>
        <!-- /Login -->
      </div>
    </div>
  </div>

  <!-- / Content -->

  <!-- Your HTML code -->
  <!-- Your HTML code -->

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/main.js"></script>

  <script>
    // Function to display a popup message
    function displayPopup(message, success) {
      var popup = document.createElement('div');
      popup.className = 'popup ' + (success ? 'success' : 'error');

      var iconClass = success ? 'fa fa-check-circle' : 'fa fa-times-circle';
      var icon = document.createElement('i');
      icon.className = iconClass;
      popup.appendChild(icon);

      var text = document.createElement('span');
      text.textContent = message;
      popup.appendChild(text);

      document.body.appendChild(popup);

      setTimeout(function () {
        popup.remove();
      }, 5000);
    }


    document.getElementById("login_button").addEventListener("click", function () {
      $.ajax({
        type: 'POST',
        url: '',
        data: $('#signin-form').serialize(),
        dataType: 'json',
        beforeSend: function () {
          $("#modelLoading").modal('show');
        },
        success: function (response) {
          // Assuming the response contains a "message" field
          displayPopup(response.message, response.success);
          if (response.success) {
            setTimeout(function () {
              window.location.href = '../index.php';
            }, 1000);
          }
        },
        error: function (xhr) {
          let msg = 'An error occurred.';
          try {
            if (xhr.responseJSON && typeof xhr.responseJSON.message === 'string') {
              msg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
              // Attempt to parse JSON, fallback to raw text
              try {
                const parsed = JSON.parse(xhr.responseText);
                if (parsed && parsed.message) msg = parsed.message;
                else msg = xhr.responseText;
              } catch (_) {
                msg = xhr.responseText;
              }
            }
          } catch (e) { /* ignore */ }
          displayPopup(msg, false);
          if (window.console) {
            console.error('Login AJAX error', { status: xhr.status, statusText: xhr.statusText, response: xhr.responseText });
          }
        }
      });
    });
  </script>



  <!-- Core JS -->
  <!-- build:js assets/vendor/js/core.js -->
  <script src="assets/vendor/libs/jquery/jquery.js"></script>
  <script src="assets/vendor/libs/popper/popper.js"></script>
  <script src="assets/vendor/js/bootstrap.js"></script>
  <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

  <script src="assets/vendor/js/menu.js"></script>
  <!-- endbuild -->

  <!-- Vendors JS -->

  <!-- Main JS -->
  <script src="assets/js/main.js"></script>

  <!-- Page JS -->

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>