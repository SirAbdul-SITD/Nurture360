<?php
require_once '../config/config.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'supervisor') {
    redirect('../auth/login.php');
}

$supervisor_id = getCurrentUserId();
$page_title = 'Change Password';
$errors = [];
$success = null;
$csrf = generateCSRFToken();

try {
    $pdo = getDBConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid CSRF token.';
        }

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $errors[] = 'All fields are required.';
        }

        if (strlen($new) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        }

        // Fetch current hash
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'supervisor' LIMIT 1");
        $stmt->execute([$supervisor_id]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (!$errors) {
            try {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$newHash, $supervisor_id]);
                logAction($supervisor_id, 'change_password', 'Supervisor changed password.');
                $success = 'Password changed successfully.';
            } catch (Throwable $e) {
                $errors[] = 'Failed to change password.';
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = 'Error: ' . $e->getMessage();
}

include '../components/header.php';
?>
<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header"><h1>Change Password</h1></div>

        <?php if ($success): ?>
          <script>
            (function(){
              const msg = <?php echo json_encode($success); ?>;
              (window.RindaApp && typeof window.RindaApp.showNotification === 'function')
                ? window.RindaApp.showNotification(msg, 'success')
                : (window.showNotification && window.showNotification(msg, 'success'));
            })();
          </script>
        <?php endif; ?>
        <?php if ($errors): ?>
          <script>
            (function(){
              const msg = <?php echo json_encode(implode("\n", $errors)); ?>;
              (window.RindaApp && typeof window.RindaApp.showNotification === 'function')
                ? window.RindaApp.showNotification(msg, 'error')
                : (window.showNotification && window.showNotification(msg, 'error'));
            })();
          </script>
        <?php endif; ?>

        <div class="card">
          <div class="card-content">
            <form method="POST" class="form">
              <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

              <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
              </div>

              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                <small>Minimum length: <?php echo PASSWORD_MIN_LENGTH; ?> characters.</small>
              </div>

              <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
              </div>

              <div style="margin-top:12px; display:flex; gap:8px;">
                <button class="btn btn-primary" type="submit"><i class="fas fa-key"></i> Update Password</button>
                <a class="btn btn-secondary" href="./profile.php"><i class="fas fa-user"></i> Back to Profile</a>
              </div>
            </form>
          </div>
        </div>
    </main>
</div>
<?php include '../components/footer.php'; ?>
