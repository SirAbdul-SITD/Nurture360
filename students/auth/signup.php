<?php
require("config.php");

if (isset($_SESSION['email'])) {
  // User is already logged in, redirect to the home page
  header('Location: ../index.php');
  exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get form data
  $firstName = $_POST['firstName'];
  $lastName = $_POST['lastName'];
  $email = $_POST['email'];
  $class_id = $_POST['class_id'];
  $phoneNumber = $_POST['phoneNumber'];
  $password = $_POST['password'];

  // Check if user already exists based on email
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $existingUser = $stmt->fetch();

  // Validate and sanitize the form data
  $email = filter_var($email, FILTER_VALIDATE_EMAIL);
  $password = htmlspecialchars($password);

  // Create an array to store the response data
  $response = array();

  if (!$email) {
    // Invalid email format
    $response['success'] = false;
    $response['message'] = 'Invalid email format. Please correct and try again';

  } elseif ($existingUser) {
    $response['success'] = false;
    $response['message'] = 'A user with this email already exists. Please proceed to log in...';
  } else {
    // Hash the password (use appropriate password hashing method)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into the database
    $insertStmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, class_id, password) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->execute([$firstName, $lastName, $email, $phoneNumber, $class_id, $hashedPassword]);

    // Get the last inserted ID and assign it to session
    $lastInsertedId = $pdo->lastInsertId();
    $_SESSION['student_id'] = $lastInsertedId;
    $response['success'] = true;
    $response['email'] = $email;
    $response['first_name'] = $firstName;
    $response['message'] = 'Account creation was successful. You will now be redirected to your dashboard...';
    $_SESSION['email'] = $email;
    $_SESSION['class_id'] = $class_id;
  }

  // Send the response as JSON
  header('Content-Type: application/json');
  echo json_encode($response);
  exit();
}

?>

<!DOCTYPE html>

<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="assets/"
  data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>Register | Nurture360°</title>

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


    body {
      margin: 0;
      overflow-x: hidden;
      overflow-y: auto;
    }


    .background {
      position: absolute;
      width: 100%;
      height: 100%;
      overflow: hidden;
      pointer-events: none;
    }

    .icon {
      position: absolute;
      width: 30px;
      height: 30px;
      background-size: contain;
      animation: fall var(--duration, 5s) linear infinite, rotate 10s linear infinite;
    }

    input::placeholder {
      font-size: small;
    }

    input {
      font-size: small;
    }

    @keyframes fall {
      0% {
        transform: translateY(-30px);
      }

      100% {
        transform: translateY(calc(100vh + 30px));
      }
    }

    @keyframes rotate {
      0% {
        transform: translateY(-30px) rotate(0deg);
      }

      100% {
        transform: translateY(calc(100vh + 30px)) rotate(360deg);
      }
    }
  </style>

</head>

<body>
  <!-- Content -->

  <div class="background"></div>
  <div class="content">

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <!-- Register Card -->
          <div class="card" style="max-width: 500rem;"> <!-- Increased width -->
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center">
                <a href="index.php" class="app-brand-link gap-2">
                  <h3>Nurture360°</h3>
                </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-2">Adventure starts here 🚀</h4>
              <p class="mb-4">Make your literacy journey easy and fun!</p>

              <form id="formAuthentication" class="mb-3" action="index.html" method="POST">
                <div class="mb-1">
                  <div class="row">
                    <div class="col-md-6 mb-3"> <!-- First name input -->
                      <label for="firstName" class="form-label">First Name</label>
                      <input type="text" class="form-control" id="firstName" name="firstName"
                        placeholder="Enter your first name" autofocus />
                    </div>
                    <div class="col-md-6 mb-3"> <!-- Last name input -->
                      <label for="lastName" class="form-label">Last Name</label>
                      <input type="text" class="form-control" id="lastName" name="lastName"
                        placeholder="Enter your last name" />
                    </div>
                  </div>
                </div>
                <div class="mb-1">
                  <div class="row">
                    <div class="col-md-6 mb-3"> <!-- First name input -->
                      <label for="email" class="form-label">Email</label>
                      <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email" />
                    </div>
                    <div class="col-md-6 mb-3"> <!-- Last name input -->
                      <label for="phoneNumber" class="form-label">Phone Number</label>
                      <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber"
                        placeholder="Enter your phone number" />
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="class">Select Class</label>
                  <div class="input-group">
                    <select name="class_id" class="form-control" id="">
                      <option value="" selected disabled>Select Class</option>
                      <option value="1">Grade 1</option>
                      <option value="2">Grade 2</option>
                      <option value="3">Grade 3</option>
                      <option value="4">Grade 4</option>
                      <option value="5">Grade 5</option>
                      <option value="7">Jss 1</option>
                      <option value="8">Jss 2</option>
                      <option value="9">Jss 3</option>

                    </select>
                  </div>
                </div>

                <div class="mb-1 form-password-toggle">
                  <label class="form-label" for="password">Set Password</label>
                  <div class="input-group input-group-merge">
                    <input type="password" id="password" class="form-control" name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms" />
                    <label class="form-check-label" for="terms-conditions">
                      I agree to
                      <a href="javascript:void(0);" style="color: #14a39a">privacy policy & terms</a>
                    </label>
                  </div>
                </div>
                <button class="btn btn-warning d-grid w-100">Sign up</button>
              </form>

              <p class="text-center">
                <span>Already have an account?</span>
                <a href="index.php">
                  <span style="color: #14a39a">Sign in instead</span>
                </a>
              </p>
            </div>
          </div>
          <!-- Register Card -->
        </div>
      </div>
    </div>

  </div>



  <!-- / Content -->



  <!-- Core JS -->
  <!-- build:js assets/vendor/js/core.js -->
  <script src="assets/vendor/libs/jquery/jquery.js"></script>
  <script src="assets/vendor/libs/popper/popper.js"></script>
  <script src="assets/vendor/js/bootstrap.js"></script>
  <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

  <script src="assets/vendor/js/menu.js"></script>
  <!-- endbuild -->

  <!-- Vendors JS -->

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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



    $(document).ready(function () {
      $('#formAuthentication').submit(function (event) {
        event.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
          type: 'POST',
          url: '',
          data: formData,
          dataType: 'json',
          beforeSend: function () {
            $("#modelLoading").modal('show');
          },
          success: function (response) {
            var email = response.email;
            var first_name = response.first_name;

            displayPopup(response.message, response.success);

            // Redirect after 1 second
            setTimeout(function () {
              window.location.href = '../index.php';
            }, 1000);
          },
          error: function (xhr) {
            console.log(xhr);
            displayPopup('An error occurred.', false);
          }
        });
      });
    });

  </script>

  <!-- Main JS -->
  <script src="assets/js/main.js"></script>

  <!-- Page JS -->

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>