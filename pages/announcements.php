<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

$page_title = 'Announcements';
$current_page = 'announcements';

$message = '';
$error = '';

// Helpers
function canManageAnnouncements(): bool {
    return isSuperAdmin() || isTeacher();
}

function getAudienceLabel(string $aud): string {
    switch ($aud) {
        case 'teachers': return 'Teachers';
        case 'students': return 'Students';
        case 'supervisors': return 'Supervisors';
        case 'specific_class': return 'Specific Class';
        default: return 'All';
    }
}

function getAvailableClassesForUser(PDO $pdo): array {
    if (isSuperAdmin()) {
        $s = $pdo->prepare("SELECT id,class_name,class_code,grade_level FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name");
        $s->execute();
        return $s->fetchAll();
    }
    if (isTeacher()) {
        $s = $pdo->prepare("SELECT DISTINCT c.id,c.class_name,c.class_code,c.grade_level
                             FROM teacher_assignments ta
                             JOIN classes c ON c.id=ta.class_id
                             WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(c.is_active,1)=1
                             ORDER BY c.grade_level,c.class_name");
        $s->execute([getCurrentUserId()]);
        return $s->fetchAll();
    }
    return [];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) throw new Exception('Invalid CSRF token');
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            if (!canManageAnnouncements()) throw new Exception('Not authorized');
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $target = $_POST['target_audience'] ?? 'all';
            $classId = isset($_POST['target_class_id']) ? (int)$_POST['target_class_id'] : null;
            $isActive = isset($_POST['is_active']) ? 1 : 1;
            $expiresAt = trim($_POST['expires_at'] ?? '');
            if ($title === '' || $content === '') throw new Exception('Title and content are required');
            if ($target === 'specific_class') {
                if (!$classId || $classId <= 0) throw new Exception('Select a class for specific class audience');
                if (isTeacher()) {
                    $chk = $pdo->prepare("SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND COALESCE(is_active,1)=1 LIMIT 1");
                    $chk->execute([getCurrentUserId(), $classId]);
                    if (!$chk->fetchColumn()) throw new Exception('You are not assigned to this class');
                }
            } else {
                $classId = null;
            }
            $authorId = getCurrentUserId();
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, author_id, target_audience, target_class_id, is_active, expires_at) VALUES (?,?,?,?,?,?,NULLIF(?,''))");
            $stmt->execute([$title, $content, $authorId, $target, $classId, $isActive, $expiresAt]);
            $message = 'Announcement created';
        } elseif ($action === 'update') {
            if (!canManageAnnouncements()) throw new Exception('Not authorized');
            $id = (int)($_POST['id'] ?? 0); if ($id <= 0) throw new Exception('Bad ID');
            // Ownership: teachers can only edit their own
            if (isTeacher()) {
                $own = $pdo->prepare('SELECT COUNT(*) FROM announcements WHERE id=? AND author_id=?');
                $own->execute([$id, getCurrentUserId()]);
                if (!$own->fetchColumn()) throw new Exception('Not authorized to edit this announcement');
            }
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $target = $_POST['target_audience'] ?? 'all';
            $classId = isset($_POST['target_class_id']) ? (int)$_POST['target_class_id'] : null;
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $expiresAt = trim($_POST['expires_at'] ?? '');
            if ($title === '' || $content === '') throw new Exception('Title and content are required');
            if ($target === 'specific_class') {
                if (!$classId || $classId <= 0) throw new Exception('Select a class for specific class audience');
                if (isTeacher()) {
                    $chk = $pdo->prepare("SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND COALESCE(is_active,1)=1 LIMIT 1");
                    $chk->execute([getCurrentUserId(), $classId]);
                    if (!$chk->fetchColumn()) throw new Exception('You are not assigned to this class');
                }
            } else {
                $classId = null;
            }
            $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=?, target_audience=?, target_class_id=?, is_active=?, expires_at=NULLIF(?, '') WHERE id=?");
            $stmt->execute([$title, $content, $target, $classId, $isActive, $expiresAt, $id]);
            $message = 'Announcement updated';
        } elseif ($action === 'delete') {
            if (!canManageAnnouncements()) throw new Exception('Not authorized');
            $id = (int)($_POST['id'] ?? 0); if ($id <= 0) throw new Exception('Bad ID');
            if (isTeacher()) {
                $del = $pdo->prepare('DELETE FROM announcements WHERE id=? AND author_id=?');
                $del->execute([$id, getCurrentUserId()]);
                if ($del->rowCount() === 0) throw new Exception('Not authorized to delete this announcement');
            } else {
                $del = $pdo->prepare('DELETE FROM announcements WHERE id=?');
                $del->execute([$id]);
            }
            $message = 'Announcement deleted';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

// Load classes for targeting in forms
$classes = getAvailableClassesForUser($pdo);

// Fetch announcements list
$announcements = [];
if (isSuperAdmin()) {
    $q = "SELECT a.*, u.first_name, u.last_name, u.username, c.class_name, c.class_code, c.grade_level
          FROM announcements a
          LEFT JOIN users u ON u.id=a.author_id
          LEFT JOIN classes c ON c.id=a.target_class_id
          ORDER BY a.created_at DESC";
    $announcements = $pdo->query($q)->fetchAll();
} elseif (isTeacher()) {
    // Teacher: only see active announcements targeted to 'teachers' or 'all'
    $stmt = $pdo->prepare("SELECT a.*, u.first_name, u.last_name, u.username, c.class_name, c.class_code, c.grade_level
                           FROM announcements a
                           LEFT JOIN users u ON u.id=a.author_id
                           LEFT JOIN classes c ON c.id=a.target_class_id
                           WHERE a.is_active=1 AND a.target_audience IN ('teachers','all')
                           ORDER BY a.created_at DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} elseif (isSupervisor()) {
    // Supervisor: active announcements for 'supervisors' or 'all', plus specific_class for their assigned classes
    $supId = getCurrentUserId();
    $clsStmt = $pdo->prepare("SELECT class_id FROM supervisor_assignments WHERE supervisor_id=? AND COALESCE(is_active,1)=1");
    $clsStmt->execute([$supId]);
    $classIds = array_map('intval', array_column($clsStmt->fetchAll(), 'class_id'));
    $params = [];
    $extra = '';
    if (!empty($classIds)) {
        $placeholders = implode(',', array_fill(0, count($classIds), '?'));
        $extra = " OR (a.target_audience='specific_class' AND a.target_class_id IN (".$placeholders."))";
        $params = $classIds;
    }
    $sql = "SELECT a.*, u.first_name, u.last_name, u.username, c.class_name, c.class_code, c.grade_level
            FROM announcements a
            LEFT JOIN users u ON u.id=a.author_id
            LEFT JOIN classes c ON c.id=a.target_class_id
            WHERE a.is_active=1 AND (
                  a.target_audience IN ('supervisors','all')".$extra.
            ")
            ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $announcements = $stmt->fetchAll();
} else {
    // Students and others: only active announcements for them / for their class
    // If student, filter class announcements to their enrolled class (most recent active enrollment)
    $params = [];
    $extraClassFilter = '';
    if (isStudent()) {
        $st = $pdo->prepare("SELECT class_id FROM student_enrollments WHERE student_id=? AND status='active' ORDER BY enrollment_date DESC LIMIT 1");
        $st->execute([getCurrentUserId()]);
        $classId = (int)($st->fetchColumn() ?: 0);
        if ($classId > 0) {
            $extraClassFilter = " OR (a.target_audience='specific_class' AND a.target_class_id=?)";
            $params[] = $classId;
        }
    }
    $sql = "SELECT a.*, u.first_name, u.last_name, u.username, c.class_name, c.class_code, c.grade_level
            FROM announcements a
            LEFT JOIN users u ON u.id=a.author_id
            LEFT JOIN classes c ON c.id=a.target_class_id
            WHERE a.is_active=1 AND (
                  a.target_audience IN ('all','teachers','students','supervisors')" .
            $extraClassFilter .
            ")
            ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $announcements = $stmt->fetchAll();
}

include '../components/header.php';
?>
<style>
#annGrid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px; }
@media (max-width: 900px){ #annGrid { grid-template-columns: 1fr; } }
.ann-card { padding-bottom: 26px; }
.ann-card .teacher-avatar{background:#fff7ed;color:#9a3412}
.ann-meta{font-size:12px;color:#6b7280;margin-top:6px}
.ann-content{font-size:13px;color:#374151;margin-top:8px; white-space: pre-line;}
.ann-top{display:flex;justify-content:space-between;align-items:center;margin-top:6px}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-info{background:#e0f2fe;color:#075985}
.badge-warn{background:#fef3c7;color:#92400e}
.badge-danger{background:#fee2e2;color:#991b1b}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Announcements</h1>
      <div class="header-actions">
        <?php if (canManageAnnouncements()): ?>
        <button type="button" class="btn btn-primary" onclick="openCreateAnnouncementModal()"><i class="fas fa-plus"></i> New Announcement</button>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-header">
      <div class="search-input-wrapper" style="max-width:420px;width:100%"><i class="fas fa-search"></i>
        <input id="annSearch" type="text" class="table-search-input" placeholder="Search announcements..." />
      </div>
    </div>
    <div class="card-content">
      <?php if (!empty($announcements)): ?>
      <div id="annGrid" class="cards-grid">
        <?php foreach ($announcements as $a): 
            $author = trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? '')); if ($author==='') $author = (string)($a['username'] ?? '');
            $aud = (string)($a['target_audience'] ?? 'all');
            $audLabel = getAudienceLabel($aud);
            $classText = '';
            if ($aud === 'specific_class') {
                $classText = ($a['class_name'] ?? '-') . ' (' . ($a['class_code'] ?? '-') . ')';
            }
            $isActive = (int)($a['is_active'] ?? 1) === 1;
            $exp = $a['expires_at'] ?? '';
            $expired = $exp !== '' && strtotime($exp) < strtotime(date('Y-m-d'));
            $badgeClass = $isActive ? ($expired ? 'badge-warn' : 'badge-info') : 'badge-danger';
            $safe = json_encode($a, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
        ?>
        <div class="teacher-card ann-card">
          <div class="teacher-avatar"><i class="fas fa-bullhorn"></i></div>
          <div class="teacher-name"><strong><?php echo htmlspecialchars($a['title']); ?></strong></div>
          <div class="ann-top">
            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($audLabel . ($classText? (' • '.$classText):'')); ?></span>
            <span class="badge"><?php echo date('M j, Y', strtotime($a['created_at'] ?? date('Y-m-d'))); ?></span>
          </div>
          <div class="ann-meta">by <?php echo htmlspecialchars($author); ?><?php if(!$isActive): ?> • <strong>Inactive</strong><?php endif; ?><?php if($exp!==''): ?> • Expires: <?php echo htmlspecialchars($exp); ?><?php endif; ?></div>
          <div class="ann-content"><?php echo nl2br(htmlspecialchars($a['content'])); ?></div>
          <?php if (canManageAnnouncements() && (isSuperAdmin() || (int)$a['author_id'] === (int)getCurrentUserId())): ?>
          <div class="teacher-card-actions action-buttons centered">
            <button class="btn btn-sm btn-primary" type="button" onclick='openEditAnnouncementModal(<?php echo (int)$a['id']; ?>, <?php echo $safe; ?>)' title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-error" type="button" onclick="openDeleteAnnouncementModal(<?php echo (int)$a['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="no-data"><i class="fas fa-bullhorn"></i><p>No announcements yet.</p><?php if (canManageAnnouncements()): ?><button type="button" class="btn btn-primary" onclick="openCreateAnnouncementModal()">Create Announcement</button><?php endif; ?></div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
// Build class options
$opts = '';
foreach ($classes as $c) {
    $label = ($c['class_name'] ?? '-') . ' (G' . (int)($c['grade_level'] ?? 0) . ') #' . ($c['class_code'] ?? '');
    $opts .= '<option value="'.(int)$c['id'].'">'.htmlspecialchars($label).'</option>';
}

$createForm = '
<form id="createAnnouncementForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="create">
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" required></div>
    <div class="form-group"><label>Expires At</label><input type="date" name="expires_at" placeholder="YYYY-MM-DD"></div>
  </div>
  <div class="form-group"><label>Audience *</label>
    <select name="target_audience" id="create_target" required>
      <option value="all">All</option>
      <option value="teachers">Teachers</option>
      <option value="students">Students</option>
      <option value="supervisors">Supervisors</option>
      <option value="specific_class">Specific Class</option>
    </select>
  </div>
  <div class="form-group" id="create_class_wrap" style="display:none;"><label>Class *</label><select name="target_class_id" id="create_class"><option value="">Select class</option>'.$opts.'</select></div>
  <div class="form-group"><label>Content *</label><textarea name="content" rows="6" required placeholder="Write your announcement..."></textarea></div>
  <div class="form-group"><label><input type="checkbox" name="is_active" checked> Active</label></div>
</form>';
renderFormModal('createAnnouncementModal','Create Announcement',$createForm,'Create','Cancel',['size'=>'large','formId'=>'createAnnouncementForm']);

$editForm = '
<form id="editAnnouncementForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" id="edit_id">
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" id="edit_title" required></div>
    <div class="form-group"><label>Expires At</label><input type="date" name="expires_at" id="edit_expires" placeholder="YYYY-MM-DD"></div>
  </div>
  <div class="form-group"><label>Audience *</label>
    <select name="target_audience" id="edit_target" required>
      <option value="all">All</option>
      <option value="teachers">Teachers</option>
      <option value="students">Students</option>
      <option value="supervisors">Supervisors</option>
      <option value="specific_class">Specific Class</option>
    </select>
  </div>
  <div class="form-group" id="edit_class_wrap" style="display:none;"><label>Class *</label><select name="target_class_id" id="edit_class"><option value="">Select class</option>'.$opts.'</select></div>
  <div class="form-group"><label>Content *</label><textarea name="content" id="edit_content" rows="6" required></textarea></div>
  <div class="form-group"><label><input type="checkbox" name="is_active" id="edit_active"> Active</label></div>
</form>';
renderFormModal('editAnnouncementModal','Edit Announcement',$editForm,'Save','Cancel',['size'=>'large','formId'=>'editAnnouncementForm']);

renderConfirmModal('deleteAnnouncementModal','Delete Announcement','Are you sure you want to delete this announcement?','Delete','Cancel',['type'=>'warning','onConfirm'=>'handleDeleteAnnouncement']);
?>
<script>
function openCreateAnnouncementModal(){ if(typeof window.openModalCreateAnnouncementModal==='function'){window.openModalCreateAnnouncementModal();} else { var m=document.getElementById('createAnnouncementModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function submitCreateAnnouncement(){ var f=document.getElementById('createAnnouncementForm'); if(f) f.submit(); }
function openEditAnnouncementModal(id,data){
  document.getElementById('edit_id').value=id;
  document.getElementById('edit_title').value=data.title||'';
  document.getElementById('edit_expires').value=data.expires_at||'';
  document.getElementById('edit_target').value=data.target_audience||'all';
  document.getElementById('edit_class').value=data.target_class_id||'';
  document.getElementById('edit_content').value=data.content||'';
  document.getElementById('edit_active').checked=!!Number(data.is_active||0);
  syncAudienceVisibility('edit');
  if(typeof window.openModalEditAnnouncementModal==='function'){window.openModalEditAnnouncementModal();} else { var m=document.getElementById('editAnnouncementModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
function submitEditAnnouncement(){ var f=document.getElementById('editAnnouncementForm'); if(f) f.submit(); }
var currentAnnouncementId=null;
function openDeleteAnnouncementModal(id){ currentAnnouncementId=id; if(typeof window.openModalDeleteAnnouncementModal==='function'){window.openModalDeleteAnnouncementModal();} else { var m=document.getElementById('deleteAnnouncementModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function handleDeleteAnnouncement(){ if(!currentAnnouncementId) return; var form=document.createElement('form'); form.method='POST'; var t1=document.createElement('input'); t1.type='hidden'; t1.name='csrf_token'; t1.value='<?php echo generateCSRFToken(); ?>'; form.appendChild(t1); var t2=document.createElement('input'); t2.type='hidden'; t2.name='action'; t2.value='delete'; form.appendChild(t2); var t3=document.createElement('input'); t3.type='hidden'; t3.name='id'; t3.value=String(currentAnnouncementId); form.appendChild(t3); document.body.appendChild(form); form.submit(); }

// Audience toggles
function syncAudienceVisibility(prefix){
  var sel = document.getElementById(prefix+'_target');
  var wrap = document.getElementById(prefix+'_class_wrap');
  if(!sel||!wrap) return;
  if (sel.value === 'specific_class') { wrap.style.display='block'; }
  else { wrap.style.display='none'; var cls = document.getElementById(prefix+'_class'); if(cls) cls.value=''; }
}
document.addEventListener('DOMContentLoaded', function(){
  var csel = document.getElementById('create_target'); if(csel){ csel.addEventListener('change', function(){ syncAudienceVisibility('create'); }); syncAudienceVisibility('create'); }
  var esel = document.getElementById('edit_target'); if(esel){ esel.addEventListener('change', function(){ syncAudienceVisibility('edit'); }); }
  // search
  var input=document.getElementById('annSearch'); var grid=document.getElementById('annGrid'); if(input&&grid){ var cards=[].slice.call(grid.querySelectorAll('.teacher-card')); function norm(s){return (s||'').toLowerCase();} function f(){ var q=norm(input.value); cards.forEach(function(c){ var m=!q||norm(c.textContent).indexOf(q)!==-1; c.style.display=m?'':'none'; }); } input.addEventListener('input',f); }
});
</script>
<?php include '../components/footer.php'; ?>

