<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

// Predefined permission set (keys should be stable identifiers)
$available_permissions = [
  'manage_users' => 'Create, edit and deactivate users',
  'manage_classes' => 'Create and manage classes',
  'manage_subjects' => 'Create and manage subjects',
  'manage_assignments' => 'Create and grade assignments',
  'manage_tests' => 'Create and grade tests/quizzes',
  'manage_resources' => 'Upload and manage learning resources',
  'send_messages' => 'Send messages and announcements',
  'view_reports' => 'View reports and analytics',
  'view_logs' => 'View system logs',
  'manage_settings' => 'Update system settings',
  'manage_themes' => 'Manage themes & UI',
  'manage_access_control' => 'Manage access control',
];

// Fetch users for selection
function fetchUsers(PDO $pdo) {
  $st = $pdo->query("SELECT id, first_name, last_name, role FROM users ORDER BY role ASC, first_name ASC, last_name ASC");
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

function fetchUserPermissions(PDO $pdo, int $userId) {
  $st = $pdo->prepare("SELECT permission_name, permission_value FROM user_permissions WHERE user_id = ?");
  $st->execute([$userId]);
  $perms = [];
  foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) { $perms[$row['permission_name']] = (int)$row['permission_value']; }
  return $perms;
}

$errors = [];$success = null;
$selected_user_id = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
  $selected_user_id = (int)($_POST['user_id'] ?? 0);
  if ($selected_user_id <= 0) { $errors[] = 'Please select a user.'; }

  if (!$errors) {
    try {
      $pdo->beginTransaction();
      // Remove only the permissions we manage here
      $placeholders = rtrim(str_repeat('?,', count($available_permissions)), ',');
      $del = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ? AND permission_name IN ($placeholders)");
      $del->execute(array_merge([$selected_user_id], array_keys($available_permissions)));

      // Insert selected permissions with value=1
      $selected = array_map('strval', array_keys($_POST['perm'] ?? []));
      if (!empty($selected)) {
        $ins = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_name, permission_value) VALUES (?,?,1)");
        foreach ($selected as $p) {
          if (isset($available_permissions[$p])) {
            $ins->execute([$selected_user_id, $p]);
          }
        }
      }
      $pdo->commit();
      $success = 'Permissions updated successfully.';
      logAction(getCurrentUserId(), 'update_permissions', 'Updated permissions for user ID '.$selected_user_id);
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = 'Failed to update permissions: '.$e->getMessage();
    }
  }
}

$users = fetchUsers($pdo);
$user_perms = $selected_user_id ? fetchUserPermissions($pdo, $selected_user_id) : [];

$page_title = 'Access Control';
include '../components/header.php';
$csrf = generateCSRFToken();
?>
<style>
  .ac-header{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
  .perm-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  @media(max-width:900px){.perm-grid{grid-template-columns:1fr}}
  .perm-item{display:flex;gap:10px;align-items:flex-start;padding:10px;border:1px solid var(--border-color);border-radius:10px}
  .perm-item label{font-weight:600}
  .perm-item small{display:block;color:var(--text-muted,#9ca3af);font-weight:400}
  .card{padding:16px}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header ac-header">
      <h1>Access Control</h1>
      <form method="GET" class="form" style="display:flex;gap:8px;align-items:flex-end">
        <div class="form-group">
          <label>Select User</label>
          <select name="user_id" onchange="this.form.submit()">
            <option value="">-- Choose user --</option>
            <?php foreach($users as $u): $uid=(int)$u['id']; $name=htmlspecialchars($u['first_name'].' '.$u['last_name']); $role=htmlspecialchars($u['role']); ?>
              <option value="<?php echo $uid; ?>" <?php echo ($uid===$selected_user_id?'selected':''); ?>><?php echo $name; ?> (<?php echo $role; ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>

    <?php if($success || $errors): ?>
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          <?php if($success): ?>
          if (window.RindaApp && typeof window.RindaApp.showNotification === 'function') { window.RindaApp.showNotification(<?php echo json_encode($success); ?>,'success'); }
          <?php endif; ?>
          <?php if($errors): ?>
          if (window.RindaApp && typeof window.RindaApp.showNotification === 'function') { window.RindaApp.showNotification(<?php echo json_encode(implode("\n", $errors)); ?>,'error'); }
          <?php endif; ?>
        });
      </script>
    <?php endif; ?>

    <div class="card">
      <h3>User Permissions</h3>
      <?php if($selected_user_id): ?>
      <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="user_id" value="<?php echo (int)$selected_user_id; ?>">
        <div class="perm-grid">
          <?php foreach($available_permissions as $key=>$desc): $checked = !empty($user_perms[$key]); ?>
          <div class="perm-item">
            <input type="checkbox" id="perm_<?php echo htmlspecialchars($key); ?>" name="perm[<?php echo htmlspecialchars($key); ?>]" <?php echo $checked?'checked':''; ?>>
            <div>
              <label for="perm_<?php echo htmlspecialchars($key); ?>"><?php echo ucwords(str_replace('_',' ', htmlspecialchars($key))); ?></label>
              <small><?php echo htmlspecialchars($desc); ?></small>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:12px">
          <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Permissions</button>
        </div>
      </form>
      <?php else: ?>
        <div class="no-data"><i class="fas fa-user-shield"></i><p>Select a user to manage permissions.</p></div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
