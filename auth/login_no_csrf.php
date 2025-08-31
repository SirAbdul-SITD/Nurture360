<?php
require_once '../config/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    if (isTeacher()) {
        redirect('../pages/teacher-dashboard.php');
    } else {
        redirect('../dashboard/');
    }
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    echo "<p>Debug: Email received: '" . $email . "'</p>";
    echo "<p>Debug: Password received: '" . $password . "'</p>";
    echo "<p>Debug: Password length: " . strlen($password) . "</p>";

    try {
        $pdo = getDBConnection();
        echo "<p>Debug: Database connection successful</p>";
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        echo "<p>Debug: User query executed</p>";
        
        if ($user) {
            echo "<p>Debug: User found</p>";
            echo "<p>Debug: User data: " . print_r($user, true) . "</p>";
            
            $password_check = password_verify($password, $user['password_hash']);
            echo "<p>Debug: Password verification result: " . ($password_check ? 'TRUE' : 'FALSE') . "</p>";
            
            if ($password_check) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['last_login'] = date('Y-m-d H:i:s');

                $success_message = 'Login successful! Redirecting...';
                echo "<p>Debug: Session variables set</p>";
                
                // Redirect after a short delay
                if (isTeacher()) {
                    header("refresh:3;url=../pages/teacher-dashboard.php");
                } else {
                    header("refresh:3;url=../dashboard/");
                }
                
            } else {
                $error_message = 'Invalid email or password.';
                echo "<p>Debug: Password verification failed</p>";
            }
        } else {
            $error_message = 'Invalid email or password.';
            echo "<p>Debug: User not found</p>";
        }
    } catch (PDOException $e) {
        $error_message = 'System error. Please try again later.';
        echo "<p>Debug: Database error: " . $e->getMessage() . "</p>";
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login No CSRF - Rinda School Management System</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px;">
    <h1>Login Test (No CSRF)</h1>
    
    <?php if ($error_message): ?>
        <div style="background: #fee; color: #c33; padding: 10px; border: 1px solid #fcc; margin: 10px 0;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div style="background: #efe; color: #363; padding: 10px; border: 1px solid #cfc; margin: 10px 0;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="border: 1px solid #ccc; padding: 20px; border-radius: 5px;">
        <div style="margin-bottom: 15px;">
            <label for="email" style="display: block; margin-bottom: 5px;">Email:</label>
            <input type="email" id="email" name="email" required 
                   value="admin@rinda.edu" 
                   style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="password" style="display: block; margin-bottom: 5px;">Password:</label>
            <input type="password" id="password" name="password" required 
                   value="admin123"
                   style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px;">
        </div>
        
        <div>
            <button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;">
                Login
            </button>
        </div>
    </form>
    
    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <h3>Test Credentials:</h3>
        <p><strong>Email:</strong> admin@rinda.edu</p>
        <p><strong>Password:</strong> admin123</p>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="login.php">Back to Full Login Page</a>
    </div>
</body>
</html> 