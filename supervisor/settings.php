<?php
require_once '../config/config.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'supervisor') {
    redirect('../auth/login.php');
}

$supervisor_id = getCurrentUserId();
$page_title = 'My Settings';
$errors = [];
$success = null;
$csrf = generateCSRFToken();

try {
    $pdo = getDBConnection();

    // Load current settings
    $stmt = $pdo->prepare("SELECT two_factor_enabled FROM users WHERE id = ? AND role='supervisor' LIMIT 1");
    $stmt->execute([$supervisor_id]);
    $user = $stmt->fetch();
    if (!$user) { redirect('../auth/logout.php'); }

    // Fetch email notification preference
    $permStmt = $pdo->prepare("SELECT permission_value FROM user_permissions WHERE user_id = ? AND permission_name = 'email_notifications' LIMIT 1");
    $permStmt->execute([$supervisor_id]);
    $perm = $permStmt->fetch();
    $emailNotifications = $perm ? (bool)$perm['permission_value'] : false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid CSRF token.';
        }
        $twoFactor = isset($_POST['two_factor_enabled']) ? 1 : 0;
        $emailNotif = isset($_POST['email_notifications']) ? 1 : 0;

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $upd = $pdo->prepare("UPDATE users SET two_factor_enabled = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$twoFactor, $supervisor_id]);

                // Upsert email_notifications permission
                $upsert = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_name, permission_value)
                                         VALUES (?, 'email_notifications', ?)
                                         ON DUPLICATE KEY UPDATE permission_value = VALUES(permission_value)");
                $upsert->execute([$supervisor_id, $emailNotif]);

                $pdo->commit();
                logAction($supervisor_id, 'update_settings', 'Supervisor updated settings.');
                $success = 'Settings updated successfully.';
                // refresh
                $stmt->execute([$supervisor_id]);
                $user = $stmt->fetch();
                $permStmt->execute([$supervisor_id]);
                $perm = $permStmt->fetch();
                $emailNotifications = $perm ? (bool)$perm['permission_value'] : false;
            } catch (Throwable $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to update settings.';
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
        <div class="page-header"><h1>My Settings</h1></div>

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
                <label>
                  <input type="checkbox" name="two_factor_enabled" value="1" <?php echo ((int)$user['two_factor_enabled']===1)?'checked':''; ?>>
                  Enable Two-Factor Authentication
                </label>
                <small>Requires OTP at login for added security.</small>
              </div>

              <div class="form-group">
                <label>
                  <input type="checkbox" name="email_notifications" value="1" <?php echo $emailNotifications?'checked':''; ?>>
                  Enable Email Notifications
                </label>
                <small>Receive important updates via email (assignments, tests, announcements).</small>
              </div>

              <div style="margin-top:12px; display:flex; gap:8px;">
                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Settings</button>
                <a class="btn btn-secondary" href="./profile.php"><i class="fas fa-user"></i> Profile</a>
              </div>
            </form>
          </div>
        </div>
    </main>
</div>
<?php include '../components/footer.php'; ?>
