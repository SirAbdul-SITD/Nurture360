<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isTeacher()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$userId = getCurrentUserId();

// Load current password hash
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$row = $stmt->fetch();
if (!$row) { redirect('../auth/logout.php'); }

$errors = [];
$success = null;
$csrf = generateCSRFToken();

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

    if (!$errors && !password_verify($current, $row['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }

    if (!$errors) {
        try {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$hash, $userId]);
            logAction($userId, 'change_password', 'Teacher changed password.');
            $success = 'Password changed successfully.';
        } catch (Throwable $e) {
            $errors[] = 'Failed to change password.';
        }
    }
}

$page_title = 'Change Password';
$current_page = 'teacher_change_password';
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
          <a class="btn btn-secondary" href="./teacher_profile.php"><i class="fas fa-user"></i> Back to Profile</a>
        </div>
      </form>
      </div>
    </div>
  </main>
</div>

<?php include '../components/footer.php'; ?>
