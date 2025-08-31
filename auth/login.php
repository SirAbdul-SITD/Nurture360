<?php
require_once '../config/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    if (isTeacher()) {
        redirect('../pages/teacher-dashboard.php');
    } elseif (function_exists('isSupervisor') && isSupervisor()) {
        redirect('../supervisor/');
    } else {
        redirect('../dashboard/');
    }
}

$settings = getSystemSettings(false);
$login_app_name = trim((string)($settings['app_name'] ?? '')) ?: APP_NAME;
$login_app_logo = $settings['app_logo'] ?? '';
// Compute filesystem and URL paths for logo
$login_logo_url = !empty($login_app_logo) ? ('../uploads/system/' . rawurlencode(basename($login_app_logo))) : '';
// Brand display mode: 'both' | 'logo' | 'name'
$brand_display = $settings['brand_display'] ?? 'both';
$mode = in_array($brand_display, ['logo','name','both'], true) ? $brand_display : 'both';
$hasLogo = !empty($login_app_logo);
$showLogo = $hasLogo && ($mode === 'logo' || $mode === 'both');
$showName = ($mode === 'name' || $mode === 'both');
// Theme colors for gradient background on illustration section
$theme_primary = $settings['theme_primary_color'] ?? (defined('DEFAULT_PRIMARY_COLOR') ? DEFAULT_PRIMARY_COLOR : '#8b5cf6');
$theme_secondary = $settings['theme_secondary_color'] ?? (defined('DEFAULT_SECONDARY_COLOR') ? DEFAULT_SECONDARY_COLOR : '#290c5c');

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['last_login'] = date('Y-m-d H:i:s');

                // Log successful login
                $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'login', 'Successful login', ?, ?)");
                $log_stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

                // Update last login
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);

                // Notify superadmins when a teacher logs in (non-blocking on failure)
                if ($user['role'] === 'teacher') {
                    try {
                        // Get all superadmins
                        $saStmt = $pdo->query("SELECT id FROM users WHERE role='superadmin' AND COALESCE(is_active,1)=1");
                        $superadmins = $saStmt->fetchAll();
                        if ($superadmins) {
                            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                            $displayName = $fullName !== '' ? $fullName : ($user['username'] ?? 'A teacher');
                            $title = 'Teacher Login';
                            $message = $displayName . ' just logged in.';
                            $actionUrl = '../pages/teachers.php';
                            $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)");
                            foreach ($superadmins as $sa) {
                                $ins->execute([(int)$sa['id'], $title, $message, 'info', $actionUrl]);
                            }
                        }
                    } catch (Throwable $e) {
                        // swallow
                    }
                }

                if ($remember) {
                    // Set remember me cookie (30 days)
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    
                    // Store token in database (you might want to create a remember_tokens table)
                }

                if (isTeacher()) {
                    redirect('../pages/teacher-dashboard.php');
                } elseif (function_exists('isSupervisor') && isSupervisor()) {
                    redirect('../supervisor/');
                } else {
                    redirect('../dashboard/');
                }
            } else {
                $error_message = 'Invalid email or password.';
                
                // Log failed login attempt
                if ($user) {
                    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'login_failed', 'Invalid password', ?, ?)");
                    $log_stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                }
            }
        } catch (PDOException $e) {
            $error_message = 'System error. Please try again later.';
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
    <title>Login - <?php echo htmlspecialchars($login_app_name); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{ --theme-primary: <?php echo htmlspecialchars($theme_primary); ?>; --theme-secondary: <?php echo htmlspecialchars($theme_secondary); ?>; }
        /* Button uses theme gradient */
        .btn-primary{ background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%) !important; }
        .btn-primary:hover{ transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important; }
        /* Inputs focus accents use theme */
        .input-wrapper input:focus{ border-color: var(--theme-primary) !important; background:#ffffff; box-shadow: 0 0 0 3px rgba(0,0,0,0.06) !important; }
        .input-wrapper input:focus + .input-icon{ color: var(--theme-primary) !important; }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Section - Login Form -->
        <div class="login-form-section">
            <div class="login-card">
                <div class="login-header">
                    <h1>LOGIN</h1>
                    <p class="subtitle">Login to access your dashboard</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="email" id="email" name="email" required 
                                   placeholder="Username" 
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
                            <span class="btn-loader" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                                Signing in...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
  
        <!-- Right Section - Illustration -->
        <div class="illustration-section" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($theme_primary); ?> 0%, <?php echo htmlspecialchars($theme_secondary); ?> 100%);">
            <!-- Logo/App Name at top-right -->
            <div class="logo-section">
                <?php if ($showLogo): ?>
                    <img src="<?php echo $login_logo_url; ?>" alt="<?php echo htmlspecialchars($login_app_name); ?>" class="logo-image" onerror="this.style.display='none';">
                <?php endif; ?>
                <?php if ($showName): ?>
                    <div class="app-name"><?php echo htmlspecialchars($login_app_name); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="illustration-card">
                <!-- <div class="illustration-frame"> -->
                    <div class="illustration-image">
                    <!-- <i class="fas fa-user-graduate"></i> -->
                        <img src="../assets/img/2.png" alt="Student with tablet" class="student-image">
                    </div>
                <!-- </div> -->
                <div class="decoration-element">
                    <i class="fas fa-bolt"></i>
                </div>
            </div>
        </div>
        
    </div>
</div>
  

    <script src="../assets/js/main.js"></script>
    <script>
        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle');
            
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }

            // Form submission with loading state
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');
            const btnLoader = loginBtn.querySelector('.btn-loader');

            loginForm.addEventListener('submit', function() {
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline-flex';
                loginBtn.disabled = true;
            });
        });
    </script>
</body>
</html> 