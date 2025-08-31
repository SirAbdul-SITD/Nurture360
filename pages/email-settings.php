<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

// Load current email settings
$existing = [];
try {
  $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN (?,?,?,?,?,?,?,?)");
  $stmt->execute([
    'smtp_host','smtp_port','smtp_username','smtp_password',
    'smtp_encryption','from_email','from_name','email_enabled'
  ]);
  while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $existing[$row['setting_key']] = $row['setting_value']; }
} catch (Throwable $e) {}

$errors = [];$success = null;
$action = $_POST['action'] ?? 'save';

function upsert_setting(PDO $pdo, $k, $v, $type='string', $public=0){
  $ins = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), setting_type=VALUES(setting_type), is_public=VALUES(is_public)");
  $ins->execute([$k, $v, $type, $public]);
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF token.'; }
  if (!$errors) {
    try {
      if ($action === 'save') {
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = (string)intval($_POST['smtp_port'] ?? 0);
        $smtp_username = trim($_POST['smtp_username'] ?? '');
        $smtp_password = trim($_POST['smtp_password'] ?? '');
        $smtp_encryption = trim($_POST['smtp_encryption'] ?? '');
        $from_email = trim($_POST['from_email'] ?? '');
        $from_name = trim($_POST['from_name'] ?? '');
        $email_enabled = isset($_POST['email_enabled']) ? '1' : '0';

        // Basic validation
        if ($from_email !== '' && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
          throw new Exception('From Email is not a valid email address.');
        }

        $pdo->beginTransaction();
        upsert_setting($pdo,'smtp_host',$smtp_host);
        upsert_setting($pdo,'smtp_port',$smtp_port,'number');
        upsert_setting($pdo,'smtp_username',$smtp_username);
        upsert_setting($pdo,'smtp_password',$smtp_password);
        upsert_setting($pdo,'smtp_encryption',$smtp_encryption);
        upsert_setting($pdo,'from_email',$from_email);
        upsert_setting($pdo,'from_name',$from_name);
        upsert_setting($pdo,'email_enabled',$email_enabled,'boolean');
        $pdo->commit();
        $success = 'Email settings saved successfully.';
        logAction(getCurrentUserId(),'update_email_settings','Updated SMTP settings');
        $existing = array_merge($existing, [
          'smtp_host'=>$smtp_host,
          'smtp_port'=>$smtp_port,
          'smtp_username'=>$smtp_username,
          'smtp_password'=>$smtp_password,
          'smtp_encryption'=>$smtp_encryption,
          'from_email'=>$from_email,
          'from_name'=>$from_name,
          'email_enabled'=>$email_enabled,
        ]);
      }
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = 'Failed to save email settings: '.$e->getMessage();
    }
  }
}

$page_title = 'Email Settings';
include '../components/header.php';
$csrf = generateCSRFToken();
?>
<style>
  .settings-layout{display:grid;gap:16px}
  .settings-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  @media(max-width:900px){.form-grid{grid-template-columns:1fr}}
  .card{padding:16px}
  .card h3{margin:0 0 4px 0}
  .card p.section-desc{margin:0 0 12px 0;color:var(--text-muted,#9ca3af);font-size:0.95rem}
  .form-group small{display:block;margin-top:6px;color:var(--text-muted,#9ca3af)}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header settings-header"><h1>Email Settings</h1></div>

    <?php // Use toast notifications ?>
    <?php if($success || $errors): ?>
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          <?php if($success): ?>
          if (window.RindaApp && typeof window.RindaApp.showNotification === 'function') {
            window.RindaApp.showNotification(<?php echo json_encode($success); ?>,'success');
          }
          <?php endif; ?>
          <?php if($errors): ?>
          if (window.RindaApp && typeof window.RindaApp.showNotification === 'function') {
            window.RindaApp.showNotification(<?php echo json_encode(implode("\n", $errors)); ?>,'error');
          }
          <?php endif; ?>
        });
      </script>
    <?php endif; ?>

    <div class="settings-layout">
      <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="action" value="save">
        <div class="card">
          <h3>Delivery</h3>
          <p class="section-desc">Toggle sending emails and configure your SMTP server details.</p>
          <div class="form-group">
            <label>Enable Email</label>
            <label style="display:inline-flex;align-items:center;gap:8px">
              <input type="checkbox" name="email_enabled" <?php echo (isset($existing['email_enabled']) && $existing['email_enabled']==='1')?'checked':''; ?>> Enable SMTP
            </label>
            <small>Disable if you don't want the system to send emails.</small>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label>SMTP Host</label>
              <input type="text" name="smtp_host" placeholder="smtp.yourdomain.com" value="<?php echo htmlspecialchars($existing['smtp_host'] ?? ''); ?>">
              <small>Your mail server address.</small>
            </div>
            <div class="form-group">
              <label>SMTP Port</label>
              <input type="number" name="smtp_port" placeholder="587" value="<?php echo htmlspecialchars($existing['smtp_port'] ?? ''); ?>">
              <small>Common ports: 587 (TLS), 465 (SSL).</small>
            </div>
            <div class="form-group">
              <label>Encryption</label>
              <select name="smtp_encryption">
                <?php $enc = $existing['smtp_encryption'] ?? ''; ?>
                <option value="" <?php echo $enc===''?'selected':''; ?>>None</option>
                <option value="tls" <?php echo strtolower($enc)==='tls'?'selected':''; ?>>TLS</option>
                <option value="ssl" <?php echo strtolower($enc)==='ssl'?'selected':''; ?>>SSL</option>
              </select>
              <small>Choose the encryption method supported by your server.</small>
            </div>
            <div class="form-group">
              <label>Username</label>
              <input type="text" name="smtp_username" placeholder="user@yourdomain.com" value="<?php echo htmlspecialchars($existing['smtp_username'] ?? ''); ?>">
              <small>Often your full email address.</small>
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="smtp_password" placeholder="••••••••" value="<?php echo htmlspecialchars($existing['smtp_password'] ?? ''); ?>">
              <small>App password or account password depending on your provider.</small>
            </div>
          </div>
        </div>

        <div class="card">
          <h3>Sender Identity</h3>
          <p class="section-desc">Set the default sender address and name for outgoing emails.</p>
          <div class="form-grid">
            <div class="form-group">
              <label>From Email</label>
              <input type="email" name="from_email" placeholder="no-reply@yourdomain.com" value="<?php echo htmlspecialchars($existing['from_email'] ?? ''); ?>">
              <small>Must be a verified/allowed address on your SMTP provider.</small>
            </div>
            <div class="form-group">
              <label>From Name</label>
              <input type="text" name="from_name" placeholder="Your App Name" value="<?php echo htmlspecialchars($existing['from_name'] ?? ''); ?>">
              <small>Appears as the sender name in recipients' inboxes.</small>
            </div>
          </div>
        </div>

        <div style="margin-top:12px">
          <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Email Settings</button>
          <a class="btn btn-secondary" href="./settings.php"><i class="fas fa-cog"></i> System Settings</a>
        </div>
      </form>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
