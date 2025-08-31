<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$page_title = 'Notifications';
$current_page = 'notifications';
$csrf = generateCSRFToken();

// Filters
$type = $_GET['type'] ?? 'all'; // info, success, warning, error
$status = $_GET['status'] ?? 'all'; // read, unread, all
$q = trim($_GET['q'] ?? '');
$userFilter = (int)($_GET['user_id'] ?? 0);

// Load users for targeting single user (when creating) and filtering
$allUsers = [];
try {
    $st = $pdo->query("SELECT id, role, username, first_name, last_name FROM users WHERE is_active = 1 ORDER BY role, first_name, last_name");
    $allUsers = $st->fetchAll();
} catch (Throwable $e) {}

// Build notifications query (latest first)
$notifications = [];
try {
    $sql = "SELECT n.*, u.username, u.first_name, u.last_name, u.role
            FROM notifications n
            JOIN users u ON u.id = n.user_id
            WHERE 1=1";
    $params = [];
    if ($type !== 'all') { $sql .= " AND n.type = ?"; $params[] = $type; }
    if ($status === 'read') { $sql .= " AND n.is_read = 1"; }
    if ($status === 'unread') { $sql .= " AND n.is_read = 0"; }
    if ($q !== '') { $sql .= " AND (n.title LIKE ? OR n.message LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
    if ($userFilter > 0) { $sql .= " AND n.user_id = ?"; $params[] = $userFilter; }
    $sql .= " ORDER BY n.created_at DESC LIMIT 200";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $notifications = $st->fetchAll();
} catch (Throwable $e) {}

include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Notifications</h1>
      <div class="header-actions">
        <button class="btn btn-primary" type="button" data-open-modal="createNotification"><i class="fas fa-plus"></i> Create</button>
      </div>
    </div>

    <div class="content-card compact">
      <div class="card-content">
        <form class="form" method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <div class="form-group">
            <label>Type</label>
            <select name="type">
              <?php foreach (['all','info','success','warning','error'] as $opt): ?>
                <option value="<?php echo $opt; ?>" <?php echo $type===$opt?'selected':''; ?>><?php echo ucfirst($opt); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <?php foreach (['all','unread','read'] as $opt): ?>
                <option value="<?php echo $opt; ?>" <?php echo $status===$opt?'selected':''; ?>><?php echo ucfirst($opt); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>User</label>
            <select name="user_id">
              <option value="0">All users</option>
              <?php foreach ($allUsers as $u): $nm = trim(($u['first_name']??'').' '.($u['last_name']??'')) ?: $u['username']; ?>
                <option value="<?php echo (int)$u['id']; ?>" <?php echo $userFilter===(int)$u['id']?'selected':''; ?>><?php echo htmlspecialchars("{$nm} (@{$u['username']}) · {$u['role']}"); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="min-width:240px;flex:1">
            <label>Search</label>
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search title or message">
          </div>
          <div class="form-group">
            <button class="btn btn-outline" type="submit"><i class="fas fa-search"></i> Filter</button>
          </div>
        </form>
      </div>
    </div>

    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-bell"></i> Recent Notifications (<?php echo count($notifications); ?>)</h3>
      </div>
      <div class="card-content">
        <?php if (!$notifications): ?>
          <div class="empty-state">No notifications found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Title</th>
                  <th>Recipient</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $i=1; foreach ($notifications as $n): $nm = trim(($n['first_name']??'').' '.($n['last_name']??'')) ?: ($n['username']??''); ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td>
                      <div style="font-weight:600;"><?php echo htmlspecialchars($n['title']); ?></div>
                      <div class="muted" style="max-width:520px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($n['message']); ?></div>
                      <?php if (!empty($n['action_url'])): ?><div><a href="<?php echo htmlspecialchars($n['action_url']); ?>" target="_blank">Open link</a></div><?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($nm . ' · ' . ($n['role'] ?? '')); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars($n['type']); ?></span></td>
                    <td><?php echo $n['is_read'] ? 'Read' : 'Unread'; ?></td>
                    <td><?php echo htmlspecialchars($n['created_at']); ?></td>
                    <td style="white-space:nowrap">
                      <button class="btn btn-sm btn-outline js-toggle-read" data-id="<?php echo (int)$n['id']; ?>" data-read="<?php echo $n['is_read']?1:0; ?>"><?php echo $n['is_read']? 'Mark Unread' : 'Mark Read'; ?></button>
                      <button class="btn btn-sm btn-danger js-delete" data-id="<?php echo (int)$n['id']; ?>">Delete</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php
// Build Create Notification modal
ob_start();
?>
  <form id="createNotificationForm" class="form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <div class="form-group">
      <label>Audience</label>
      <select name="audience" id="audience" required>
        <option value="all">All users</option>
        <option value="teachers">Teachers</option>
        <option value="students">Students</option>
        <option value="supervisors">Supervisors</option>
        <option value="user">Specific user</option>
      </select>
    </div>
    <div class="form-group" id="userSelectGroup" style="display:none">
      <label>User</label>
      <select name="user_id">
        <option value="">Select user</option>
        <?php foreach ($allUsers as $u): $nm = trim(($u['first_name']??'').' '.($u['last_name']??'')) ?: $u['username']; ?>
          <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars("{$nm} (@{$u['username']}) · {$u['role']}"); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Type</label>
      <select name="type" required>
        <?php foreach (['info','success','warning','error'] as $t): ?>
          <option value="<?php echo $t; ?>"><?php echo ucfirst($t); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Title</label>
      <input type="text" name="title" required maxlength="255">
    </div>
    <div class="form-group">
      <label>Message</label>
      <textarea name="message" rows="4" required></textarea>
    </div>
    <div class="form-group">
      <label>Action URL (optional)</label>
      <input type="url" name="action_url" placeholder="https://...">
    </div>
  </form>
<?php
$createForm = ob_get_clean();
include_once '../components/modal.php';
renderFormModal('createNotification', 'Create Notification', $createForm, 'Create', 'Cancel', [ 'size' => 'large' ]);
?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const aud = document.getElementById('audience');
  const userGrp = document.getElementById('userSelectGroup');
  function syncAudience(){ userGrp.style.display = (aud && aud.value === 'user') ? '' : 'none'; }
  if (aud) { aud.addEventListener('change', syncAudience); syncAudience(); }

  const submitForm = (form, url, onOk) => {
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;
      try {
        const res = await fetch(url, { method:'POST', body: new FormData(form), credentials:'same-origin' });
        const data = await res.json().catch(()=>({}));
        if (!res.ok || data.error) throw new Error(data.error || 'Request failed');
        onOk && onOk(data);
      } catch (err) {
        (window.showNotification||console.error)('Failed: '+err.message, 'error');
      } finally { if (btn) btn.disabled = false; }
    });
  };
  submitForm(document.getElementById('createNotificationForm'), '../api/create_notification.php', function(){
    if (typeof window.closeModalCreateNotification==='function') window.closeModalCreateNotification();
    (window.showNotification||console.log)('Notification created','success');
    setTimeout(()=>window.location.reload(), 500);
  });

  // Toggle read / delete
  document.addEventListener('click', async function(e){
    const tgl = e.target.closest('.js-toggle-read');
    if (tgl){
      const id = tgl.getAttribute('data-id');
      const isRead = tgl.getAttribute('data-read') === '1';
      const form = new FormData();
      form.append('csrf_token','<?php echo htmlspecialchars($csrf); ?>');
      form.append('id', id);
      form.append('is_read', isRead ? '0' : '1');
      try {
        const res = await fetch('../api/toggle_notification_read.php', { method:'POST', body: form, credentials:'same-origin' });
        const data = await res.json().catch(()=>({}));
        if (!res.ok || data.error) throw new Error(data.error || 'Request failed');
      } catch(err){ (window.showNotification||console.error)('Failed: '+err.message,'error'); return; }
      window.location.reload();
      return;
    }
    const del = e.target.closest('.js-delete');
    if (del){
      if (!confirm('Delete this notification?')) return;
      const id = del.getAttribute('data-id');
      const form = new FormData();
      form.append('csrf_token','<?php echo htmlspecialchars($csrf); ?>');
      form.append('id', id);
      try {
        const res = await fetch('../api/delete_notification.php', { method:'POST', body: form, credentials:'same-origin' });
        const data = await res.json().catch(()=>({}));
        if (!res.ok || data.error) throw new Error(data.error || 'Request failed');
      } catch(err){ (window.showNotification||console.error)('Failed: '+err.message,'error'); return; }
      window.location.reload();
    }
  });
});
</script>
<?php include '../components/footer.php'; ?>
