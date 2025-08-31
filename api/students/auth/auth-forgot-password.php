<!DOCTYPE html>

<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="assets/"
  data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>Forgot Password | Nurture360°</title>

  <meta name="description" content="" />

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../favlogo.png" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet" />

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
      opacity: 0;
      /* Start with 0 opacity */
      transition: opacity 0.5s ease-in-out;/ color: #fff;
    }

    .popup.success {
      background-color: #4CAF50;
      color: #fff;
    }

    .popup.error {
      background-color: #F44336;
      color: white;
    }

    .popup.show {
      opacity: 1;
      /* Show with full opacity */
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
      <div class="authentication-inner py-4">
        <!-- Forgot Password -->
        <div class="card">
          <div class="card-body">
            <!-- Logo -->

            <h4 class="mb-2">Forgot Password? 🔒</h4>
            <p class="mb-4">Enter your email and we'll send you instructions to reset your password</p>
            <form id="formAuthentication" class="mb-3" method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email"
                  autofocus />
              </div>
              <button id="send-reset-link-button" class="btn btn-primary d-grid w-100">Send Reset Link</button>
            </form>
            <div class="text-center">
              <a href="index.php" class="d-flex align-items-center justify-content-center">
                <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                Back to login
              </a>
            </div>
          </div>
        </div>
        <!-- /Forgot Password -->
      </div>
    </div>
  </div>

  <!-- / Content -->
  <!-- Core JS -->
  <script src="assets/vendor/libs/jquery/jquery.js"></script> <!-- jQuery FIRST!! -->
  <script src="assets/vendor/libs/popper/popper.js"></script>
  <script src="assets/vendor/js/bootstrap.js"></script>
  <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="assets/vendor/js/menu.js"></script>

  <!-- Your Custom Script -->
  <script>
    $(document).ready(function () {
      $("#send-reset-link-button").click(function (e) {
        e.preventDefault(); // Prevent the default form submission

        var email = $("#email").val();

        $.ajax({
          type: "POST",
          url: "send_email.php",
          data: { email: email },
          dataType: "json",
          success: function (response) {
            if (response.success) {
              displayPopup(response.message, response.success);
            } else {
              displayPopup(response.message, false);
            }
          },
          error: function () {
            displayPopup("An error occurred while sending the email. Please contact the administrator.", false);
          }
        });
      });
    });

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
        popup.classList.add('show');
      }, 100);

      setTimeout(function () {
        popup.classList.remove('show');
        setTimeout(function () {
          popup.remove();
        }, 500);
      }, 5000);
    }
  </script>

  <!-- Other JS -->
  <script src="assets/js/main.js"></script>
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>