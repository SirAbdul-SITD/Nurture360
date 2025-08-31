<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

// Load current settings
$existing = [];
try {
  $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
  while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $existing[$row['setting_key']] = $row['setting_value']; }
} catch (Throwable $e) {}

$errors = [];$success = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF token.'; }
  if (!$errors) {
    $removeLogo = isset($_POST['remove_logo']);
    $removeFavicon = isset($_POST['remove_favicon']);
    // Validate brand display
    $postedBrand = $_POST['brand_display'] ?? ($existing['brand_display'] ?? 'both');
    $brandDisplay = in_array($postedBrand, ['logo','name','both'], true) ? $postedBrand : 'both';

    $pairs = [
      'app_name' => trim($_POST['app_name'] ?? APP_NAME),
      'app_timezone' => trim($_POST['app_timezone'] ?? APP_TIMEZONE),
      'app_currency' => trim($_POST['app_currency'] ?? APP_CURRENCY),
      'app_language' => trim($_POST['app_language'] ?? APP_LANGUAGE),
      'app_description' => trim($_POST['app_description'] ?? ($existing['app_description'] ?? '')),
      'app_address' => trim($_POST['app_address'] ?? ($existing['app_address'] ?? '')),
      'app_phone' => trim($_POST['app_phone'] ?? ($existing['app_phone'] ?? '')),
      'app_email' => trim($_POST['app_email'] ?? ($existing['app_email'] ?? '')),
      'brand_display' => $brandDisplay,
    ];
    try {
      $pdo->beginTransaction();
      $ins = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), setting_type=VALUES(setting_type), is_public=VALUES(is_public)");
      foreach($pairs as $k=>$v){
        $type = 'string'; $pub = in_array($k, ['app_name','app_email','theme_primary_color','theme_mode']) ? 1 : 0;
        $ins->execute([$k, $v, $type, $pub]);
      }

      // Handle Branding uploads (logo & favicon)
      $uploadBase = dirname(__DIR__).'/uploads/system';
      if (!is_dir($uploadBase)) { @mkdir($uploadBase, 0775, true); }

      // Handle removals first
      if ($removeLogo && !empty($existing['app_logo'])) {
        $oldLogo = $uploadBase.'/'.basename($existing['app_logo']);
        if (is_file($oldLogo)) { @unlink($oldLogo); }
        $ins->execute(['app_logo', '', 'string', 1]);
        $existing['app_logo'] = '';
      }
      if ($removeFavicon && !empty($existing['app_favicon'])) {
        $oldFav = $uploadBase.'/'.basename($existing['app_favicon']);
        if (is_file($oldFav)) { @unlink($oldFav); }
        $ins->execute(['app_favicon', '', 'string', 1]);
        $existing['app_favicon'] = '';
      }

      // Helper to save an uploaded file
      $saveUpload = function($field, $prefix, $allowedExts, $allowedMimes) use ($uploadBase) {
        if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'])) return null;
        if (!is_uploaded_file($_FILES[$field]['tmp_name'])) return null;
        $name = $_FILES[$field]['name'];
        $tmp  = $_FILES[$field]['tmp_name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) return null;
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? @finfo_file($finfo, $tmp) : null;
        if ($finfo) @finfo_close($finfo);
        if ($allowedMimes && $mime && !in_array($mime, $allowedMimes, true)) return null;
        $new = $prefix.'_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        if (@move_uploaded_file($tmp, $uploadBase.'/'.$new)) {
          return $new; // store filename only
        }
        return null;
      };

      $logoFile = $saveUpload('app_logo', 'logo', ['png','jpg','jpeg','svg'], ['image/png','image/jpeg','image/svg+xml']);
      if ($logoFile) { $ins->execute(['app_logo', $logoFile, 'string', 1]); $existing['app_logo'] = $logoFile; }

      $faviconFile = $saveUpload('app_favicon', 'favicon', ['ico','png'], ['image/x-icon','image/vnd.microsoft.icon','image/png']);
      if ($faviconFile) { $ins->execute(['app_favicon', $faviconFile, 'string', 1]); $existing['app_favicon'] = $faviconFile; }

      $pdo->commit();
      $success = 'Settings saved successfully.';
      logAction(getCurrentUserId(), 'update_settings', 'System settings updated');
      // Refresh existing for display
      $existing = array_merge($existing, $pairs);
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = 'Failed to save settings: '.$e->getMessage();
    }
  }
}

$page_title = 'System Settings';
include '../components/header.php';
$csrf = generateCSRFToken();
$appName = $existing['app_name'] ?? APP_NAME;
// Theme and email settings are handled in dedicated pages
?>
<style>
.settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;width:100%}
@media(max-width:1024px){.settings-grid{grid-template-columns:1fr}}
.card{background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:16px}
.alert{padding:10px 12px;border-radius:8px;margin-bottom:12px}
.alert-success{background:#dcfce7;color:#166534}
.alert-danger{background:#fee2e2;color:#991b1b}
.main-content.full-width{max-width:none;width:calc(100vw - var(--sidebar-width));margin-left:var(--sidebar-width)}
.settings-form{max-width:100% !important;width:100% !important}
.settings-grid>.card{width:100%}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content full-width">
    <div class="page-header"><h1>System Settings</h1></div>

    <?php // Use global toast notifications instead of inline alerts ?>
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

    <form method="POST" class="form settings-form" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
      <div class="settings-grid">
        <div class="card">
          <h3>Application</h3>
          <div class="form-group"><label>App Name</label><input type="text" name="app_name" value="<?php echo htmlspecialchars($appName); ?>" required></div>
          <div class="form-group"><label>Timezone</label><input type="text" name="app_timezone" value="<?php echo htmlspecialchars($existing['app_timezone'] ?? APP_TIMEZONE); ?>" placeholder="Africa/Lagos"></div>
          <div class="form-group"><label>Currency</label><input type="text" name="app_currency" value="<?php echo htmlspecialchars($existing['app_currency'] ?? APP_CURRENCY); ?>" placeholder="NGN"></div>
          <div class="form-group"><label>Language</label><input type="text" name="app_language" value="<?php echo htmlspecialchars($existing['app_language'] ?? APP_LANGUAGE); ?>" placeholder="en"></div>
          <div class="form-group"><label>Description</label><textarea name="app_description" rows="3" placeholder="Comprehensive school management system for modern ..."><?php echo htmlspecialchars($existing['app_description'] ?? ''); ?></textarea></div>
          <div class="form-group"><label>Address</label><input type="text" name="app_address" value="<?php echo htmlspecialchars($existing['app_address'] ?? ''); ?>" placeholder="123 Rinda Street, Education City, USA"></div>
          <div class="form-group"><label>Phone</label><input type="tel" name="app_phone" value="<?php echo htmlspecialchars($existing['app_phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000"></div>
          <div class="form-group"><label>Email</label><input type="email" name="app_email" value="<?php echo htmlspecialchars($existing['app_email'] ?? ''); ?>" placeholder="support@example.com"></div>
          <div class="form-group">
            <label>Header Brand Display</label>
            <?php $brand = htmlspecialchars($existing['brand_display'] ?? 'both'); ?>
            <select name="brand_display">
              <option value="both" <?php echo ($brand==='both')?'selected':''; ?>>Logo and Name</option>
              <option value="logo" <?php echo ($brand==='logo')?'selected':''; ?>>Logo only</option>
              <option value="name" <?php echo ($brand==='name')?'selected':''; ?>>Name only</option>
            </select>
            <small>When a logo is shown, the generic school icon is hidden automatically.</small>
          </div>
        </div>

        <div class="card">
          <h3>Branding</h3>
          <div class="form-group">
            <label>Logo (PNG, JPG, SVG)</label>
            <input type="file" name="app_logo" accept="image/png,image/jpeg,image/svg+xml">
            <?php if (!empty($existing['app_logo'])): $logoName = htmlspecialchars($existing['app_logo']); ?>
              <div style="margin-top:8px;display:flex;gap:12px;align-items:center">
                <img src="<?php echo '../uploads/system/'.rawurlencode($logoName); ?>" alt="Current Logo" style="height:40px;border:1px solid var(--border-color);border-radius:6px;padding:4px;background:var(--card-bg)">
                <small>Current: <?php echo $logoName; ?></small>
                <button type="submit" name="remove_logo" value="1" class="btn btn-danger" onclick="return confirm('Remove current logo?')">
                  <i class="fas fa-trash"></i> Remove Logo
                </button>
              </div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Favicon (ICO or PNG)</label>
            <input type="file" name="app_favicon" accept="image/x-icon,image/png">
            <?php if (!empty($existing['app_favicon'])): $favName = htmlspecialchars($existing['app_favicon']); ?>
              <div style="margin-top:8px;display:flex;gap:12px;align-items:center">
                <img src="<?php echo '../uploads/system/'.rawurlencode($favName); ?>" alt="Current Favicon" style="height:20px;width:20px;border:1px solid var(--border-color);border-radius:4px;background:var(--card-bg)">
                <small>Current: <?php echo $favName; ?></small>
                <button type="submit" name="remove_favicon" value="1" class="btn btn-danger" onclick="return confirm('Remove current favicon?')">
                  <i class="fas fa-trash"></i> Remove Favicon
                </button>
              </div>
            <?php endif; ?>
          </div>
          <small>Tip: Uploading a new file will replace the currently used asset.</small>
        </div>
      </div>

      <div style="margin-top:12px">
        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Settings</button>
      </div>
    </form>
  </main>
</div>
<?php include '../components/footer.php'; ?>
