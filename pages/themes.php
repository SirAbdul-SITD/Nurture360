<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

$errors = [];$success=null;
$action = $_POST['action'] ?? '';

function fetchThemes(PDO $pdo){
  $st=$pdo->query("SELECT id, theme_name, primary_color, secondary_color, accent_color, is_dark_mode, is_active, created_at FROM themes ORDER BY is_active DESC, theme_name ASC");
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

function settingsUpsert(PDO $pdo, $k, $v, $type='string', $public=1){
  $ins=$pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), setting_type=VALUES(setting_type), is_public=VALUES(is_public)");
  $ins->execute([$k,$v,$type,$public]);
}

if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')){ $errors[]='Invalid CSRF token.'; }
  if (!$errors){
    try{
      if ($action==='create'){
        $name = trim($_POST['theme_name'] ?? '');
        $p = trim($_POST['primary_color'] ?? '#2563eb');
        $s = trim($_POST['secondary_color'] ?? '#1e40af');
        $a = trim($_POST['accent_color'] ?? '#3b82f6');
        $dark = isset($_POST['is_dark_mode']) ? 1 : 0;
        if ($name===''){ throw new Exception('Theme name is required'); }
        $ins=$pdo->prepare("INSERT INTO themes (theme_name, primary_color, secondary_color, accent_color, is_dark_mode, is_active) VALUES (?,?,?,?,?,0)");
        $ins->execute([$name,$p,$s,$a,$dark]);
        $success='Theme created.';
        logAction(getCurrentUserId(),'create_theme','Created theme '.$name);
      } elseif ($action==='activate'){
        $id=(int)($_POST['id'] ?? 0);
        $st=$pdo->prepare("SELECT * FROM themes WHERE id=?"); $st->execute([$id]); $th=$st->fetch(); if(!$th) throw new Exception('Theme not found');
        $pdo->beginTransaction();
        $pdo->exec("UPDATE themes SET is_active=0");
        $up=$pdo->prepare("UPDATE themes SET is_active=1 WHERE id=?"); $up->execute([$id]);
        settingsUpsert($pdo,'theme_primary_color', $th['primary_color']);
        settingsUpsert($pdo,'theme_secondary_color', $th['secondary_color']);
        settingsUpsert($pdo,'theme_accent_color', $th['accent_color']);
        settingsUpsert($pdo,'theme_mode', $th['is_dark_mode']? 'dark':'light');
        $pdo->commit();
        $success='Theme activated.';
        logAction(getCurrentUserId(),'activate_theme','Activated theme '.$th['theme_name']);
      } elseif ($action==='delete'){
        $id=(int)($_POST['id'] ?? 0);
        $st=$pdo->prepare("SELECT is_active, theme_name FROM themes WHERE id=?"); $st->execute([$id]); $row=$st->fetch(); if(!$row) throw new Exception('Theme not found');
        if ((int)$row['is_active']===1) throw new Exception('Cannot delete the active theme. Activate another theme first.');
        $del=$pdo->prepare("DELETE FROM themes WHERE id=?"); $del->execute([$id]);
        $success='Theme deleted.';
        logAction(getCurrentUserId(),'delete_theme','Deleted theme '.$row['theme_name']);
      }
    } catch(Throwable $e){ $errors[]=$e->getMessage(); if($pdo->inTransaction()) $pdo->rollBack(); }
  }
}

$themes = fetchThemes($pdo);
$page_title = 'Themes & UI';
include '../components/header.php';
$csrf = generateCSRFToken();
?>
<style>
.theme-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
@media(max-width:1200px){.theme-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.theme-grid{grid-template-columns:1fr}}
.theme-card{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff}
.theme-preview{height:80px;display:flex}
.theme-swatch{flex:1}
.theme-body{padding:12px}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-active{background:#dcfce7;color:#166534}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Themes & UI</h1></div>

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

    <div class="card" style="margin-bottom:12px">
      <h3>Create Theme</h3>
      <form method="POST" class="form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label>Name</label><input type="text" name="theme_name" placeholder="e.g. School Blue" required></div>
        <div class="form-group"><label>Primary</label><input type="color" name="primary_color" value="#2563eb"></div>
        <div class="form-group"><label>Secondary</label><input type="color" name="secondary_color" value="#1e40af"></div>
        <div class="form-group"><label>Accent</label><input type="color" name="accent_color" value="#3b82f6"></div>
        <div class="form-group"><label>Dark Mode</label><input type="checkbox" name="is_dark_mode"></div>
        <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Add Theme</button>
      </form>
    </div>

    <div class="theme-grid">
      <?php if($themes): foreach($themes as $t): ?>
        <div class="theme-card">
          <div class="theme-preview">
            <div class="theme-swatch" style="background: <?php echo htmlspecialchars($t['primary_color']); ?>"></div>
            <div class="theme-swatch" style="background: <?php echo htmlspecialchars($t['secondary_color']); ?>"></div>
            <div class="theme-swatch" style="background: <?php echo htmlspecialchars($t['accent_color']); ?>"></div>
          </div>
          <div class="theme-body">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
              <div>
                <strong><?php echo htmlspecialchars($t['theme_name']); ?></strong>
                <div class="virtual-meta">Mode: <?php echo ((int)$t['is_dark_mode']===1?'Dark':'Light'); ?><?php if((int)$t['is_active']===1): ?> • <span class="badge badge-active">Active</span><?php endif; ?></div>
              </div>
              <div class="teacher-card-actions action-buttons">
                <?php if((int)$t['is_active']!==1): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                  <input type="hidden" name="action" value="activate">
                  <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                  <button class="btn btn-sm btn-primary" type="submit" title="Activate"><i class="fas fa-check"></i></button>
                </form>
                <?php endif; ?>
                <?php if((int)$t['is_active']!==1): ?>
                <form method="POST" onsubmit="return confirm('Delete this theme?')" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                  <button class="btn btn-sm btn-danger" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
              </div>
            </div>
            <div class="virtual-meta">Created: <?php echo htmlspecialchars($t['created_at']); ?></div>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="no-data"><i class="fas fa-palette"></i><p>No themes yet. Create one above.</p></div>
      <?php endif; ?>
    </div>

  </main>
</div>
<?php include '../components/footer.php'; ?>
