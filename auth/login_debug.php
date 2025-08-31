<?php
require_once '../config/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('../dashboard/');
}

$error_message = '';
$success_message = '';
$debug_info = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_info .= "<h3>Form Submitted</h3>";
    $debug_info .= "<p>POST data received:</p>";
    $debug_info .= "<pre>" . print_r($_POST, true) . "</pre>";
    
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
        $debug_info .= "<p style='color: red;'>CSRF token validation failed</p>";
    } else {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        $debug_info .= "<p>Email: " . htmlspecialchars($email) . "</p>";
        $debug_info .= "<p>Password length: " . strlen($password) . "</p>";
        $debug_info .= "<p>Password (first 3 chars): " . substr($password, 0, 3) . "...</p>";

        try {
            $pdo = getDBConnection();
            $debug_info .= "<p style='color: green;'>Database connection successful</p>";
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $debug_info .= "<p>User query executed</p>";
            if ($user) {
                $debug_info .= "<p style='color: green;'>User found in database</p>";
                $debug_info .= "<p>User data:</p>";
                $debug_info .= "<pre>" . print_r($user, true) . "</pre>";
                
                $debug_info .= "<p>Password hash from DB: " . $user['password_hash'] . "</p>";
                $debug_info .= "<p>Testing password verification...</p>";
                
                if (password_verify($password, $user['password_hash'])) {
                    $debug_info .= "<p style='color: green;'>Password verification successful!</p>";
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['last_login'] = date('Y-m-d H:i:s');

                    $debug_info .= "<p style='color: green;'>Session variables set</p>";
                    $debug_info .= "<p>Redirecting to dashboard...</p>";
                    
                    // Don't redirect in debug mode, just show success
                    $success_message = 'Login successful! Would redirect to dashboard.';
                    
                } else {
                    $error_message = 'Invalid email or password.';
                    $debug_info .= "<p style='color: red;'>Password verification failed!</p>";
                    $debug_info .= "<p>Input password: " . htmlspecialchars($password) . "</p>";
                    $debug_info .= "<p>Stored hash: " . $user['password_hash'] . "</p>";
                }
            } else {
                $error_message = 'Invalid email or password.';
                $debug_info .= "<p style='color: red;'>No user found with email: " . htmlspecialchars($email) . "</p>";
            }
        } catch (PDOException $e) {
            $error_message = 'System error. Please try again later.';
            $debug_info .= "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug - Rinda School Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Section - Login Form -->
        <div class="login-form-section">
            <div class="login-card">
                <div class="login-header">
                    <h1>LOGIN DEBUG</h1>
                    <p class="subtitle">Debug version to troubleshoot login issues</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="email" id="email" name="email" required 
                                   placeholder="Email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   autocomplete="email">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Password"
                                   autocomplete="current-password">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                            <span class="btn-text">Login Now</span>
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <h4>Debug Information:</h4>
                    <?php echo $debug_info; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Section - Illustration -->
        <div class="illustration-section">
            <div class="logo-section">
                <div class="app-name"><?php echo APP_NAME; ?></div>
            </div>
            
            <div class="illustration-card">
                <div class="illustration-image">
                    <img src="../assets/img/2.png" alt="Student with tablet" class="student-image">
                </div>
                <div class="decoration-element">
                    <i class="fas fa-bolt"></i>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html> 