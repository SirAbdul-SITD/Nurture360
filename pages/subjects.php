<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$message = '';
$error = '';

$page_title = 'Subjects Management';

function generateUniqueSubjectCode(PDO $pdo, $length = 6) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i=0; $i<$length; $i++) { $code .= $chars[random_int(0, strlen($chars)-1)]; }
        $stmt = $pdo->prepare("SELECT 1 FROM subjects WHERE subject_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn();
    } while ($exists);
    return $code;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $name = trim($_POST['subject_name'] ?? '');
            $code = trim($_POST['subject_code'] ?? '');
            $credits = (int)($_POST['credits'] ?? 1);
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 1; // default active
            if ($name === '') throw new Exception('Subject name is required');
            if ($code === '') { $code = generateUniqueSubjectCode($pdo); }
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, description, credits, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $description, $credits, $is_active]);
            $message = 'Subject created successfully';
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid subject ID');
            $name = trim($_POST['subject_name'] ?? '');
            $code = trim($_POST['subject_code'] ?? '');
            $credits = (int)($_POST['credits'] ?? 1);
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            if ($name === '' || $code === '') throw new Exception('Name and code are required');
            $stmt = $pdo->prepare("UPDATE subjects SET subject_name=?, subject_code=?, description=?, credits=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $code, $description, $credits, $is_active, $id]);
            $message = 'Subject updated successfully';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid subject ID');
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id=?");
            $stmt->execute([$id]);
            $message = 'Subject deleted successfully';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

// Fetch subjects
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll();

include '../components/header.php';
?>
<style>
/* Page-scoped mobile adjustments: make subject cards edge-to-edge */
@media (max-width: 768px) {
  .no-pad-mobile { padding-left: 0 !important; padding-right: 0 !important; }
  .no-pad-mobile #subjectsGrid { gap: 0 !important; }
  .no-pad-mobile .teacher-card { border-radius: 0 !important; width: 100% !important; }
}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Subjects</h1>
    </div>

    <div class="card-header">
        <div class="search-input-wrapper" style="max-width: 420px; width: 100%;">
            <i class="fas fa-search"></i>
            <input id="subjectSearchInput" type="text" class="table-search-input" placeholder="Search subjects by name or code..." />
        </div>
        <div class="card-header--right">
            <button type="button" class="btn btn-primary" onclick="openCreateSubjectModal()">
                <i class="fas fa-plus"></i> Add New Subject
            </button>
        </div>
    </div>

    <div class="card-content no-pad-mobile">
        <?php if (!empty($subjects)): ?>
            <div id="subjectsGrid" class="cards-grid teachers-grid">
                <?php foreach ($subjects as $s): ?>
                    <?php
                        $code = (string)($s['subject_code'] ?? '');
                        $safeData = json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
                    ?>
                    <div class="teacher-card">
                        <div class="teacher-avatar">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="teacher-name"><strong><?php echo htmlspecialchars($s['subject_name']); ?></strong></div>
                        <div class="teacher-username">#<?php echo htmlspecialchars($code); ?></div>
                        <!-- Removed details body to keep only name, code, and actions -->
                        <div class="teacher-card-actions action-buttons centered">
                            <button class="btn btn-sm btn-primary" type="button" onclick='openEditSubjectModal(<?php echo (int)$s['id']; ?>, <?php echo $safeData; ?>)' title="Edit Subject"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-error" type="button" onclick="openDeleteSubjectModal(<?php echo (int)$s['id']; ?>)" title="Delete Subject"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-book"></i>
                <p>No subjects found. Add your first subject to get started.</p>
                <button type="button" class="btn btn-primary" onclick="openCreateSubjectModal()">Add Subject</button>
            </div>
        <?php endif; ?>
    </div>
  </main>
</div>

<?php include '../components/modal.php'; ?>

<?php
$createSubjectForm = '
<form id="createSubjectForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
  <input type="hidden" name="action" value="create">
  <div class="form-row">
    <div class="form-group">
      <label>Subject Name *</label>
      <input type="text" name="subject_name" required placeholder="e.g., Mathematics">
    </div>
    <div class="form-group">
      <label>Subject Code</label>
      <input type="text" name="subject_code" placeholder="Auto if blank">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Credits</label>
      <input type="number" name="credits" min="1" value="1">
    </div>
    <div class="form-group" style="align-self:end;">
      <label><input type="checkbox" name="is_active" checked> Active</label>
    </div>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description" placeholder="Optional notes..."></textarea>
  </div>
</form>';

renderFormModal('createSubjectModal', 'Create Subject', $createSubjectForm, 'Create', 'Cancel', [
  'size' => 'large',
  'formId' => 'createSubjectForm'
]);

$editSubjectForm = '
<form id="editSubjectForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" id="edit_subject_id">
  <div class="form-row">
    <div class="form-group">
      <label>Subject Name *</label>
      <input type="text" name="subject_name" id="edit_subject_name" required>
    </div>
    <div class="form-group">
      <label>Subject Code *</label>
      <input type="text" name="subject_code" id="edit_subject_code" required>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Credits</label>
      <input type="number" name="credits" id="edit_credits" min="1" value="1">
    </div>
    <div class="form-group" style="align-self:end;">
      <label><input type="checkbox" name="is_active" id="edit_is_active"> Active</label>
    </div>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description" id="edit_description"></textarea>
  </div>
</form>';

renderFormModal('editSubjectModal', 'Edit Subject', $editSubjectForm, 'Save', 'Cancel', [
  'size' => 'large',
  'formId' => 'editSubjectForm'
]);
?>

<!-- Delete Subject Confirmation Modal -->
<?php
renderConfirmModal('deleteSubjectModal', 'Delete Subject',
    'Are you sure you want to delete this subject? This action cannot be undone.',
    'Delete', 'Cancel', [
        'type' => 'warning',
        'onConfirm' => 'handleDeleteSubject'
    ]);
?>

<script>
function openCreateSubjectModal(){
  if (typeof window.openModalCreateSubjectModal==='function') { window.openModalCreateSubjectModal(); }
  else { const m=document.getElementById('createSubjectModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
function submitCreateSubject(){ const f=document.getElementById('createSubjectForm'); if (f) f.submit(); }

function openEditSubjectModal(id, data){
  document.getElementById('edit_subject_id').value = id;
  document.getElementById('edit_subject_name').value = data.subject_name || '';
  document.getElementById('edit_subject_code').value = data.subject_code || '';
  document.getElementById('edit_credits').value = data.credits || 1;
  document.getElementById('edit_description').value = data.description || '';
  document.getElementById('edit_is_active').checked = !!Number(data.is_active || 0);
  if (typeof window.openModalEditSubjectModal==='function') { window.openModalEditSubjectModal(); }
  else { const m=document.getElementById('editSubjectModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
function submitEditSubject(){ document.getElementById('editSubjectForm').submit(); }

// Delete subject via custom confirmation modal
let currentSubjectId = null;
function openDeleteSubjectModal(id){
  currentSubjectId = id;
  if (typeof window.openModalDeleteSubjectModal === 'function') {
    window.openModalDeleteSubjectModal();
  } else {
    const m = document.getElementById('deleteSubjectModal');
    if (m) { m.classList.add('show','active'); document.body.classList.add('modal-open'); }
  }
}

function handleDeleteSubject(){
  if (!currentSubjectId) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = `
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="${currentSubjectId}">
  `;
  document.body.appendChild(form);
  form.submit();
}

// Hook modal submit buttons
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    const createForm = document.getElementById('createSubjectForm');
    if (createForm) createForm.addEventListener('submit', function(e){ /* normal submit */ });
    const editForm = document.getElementById('editSubjectForm');
    if (editForm) editForm.addEventListener('submit', function(e){ /* normal submit */ });
  });
})();

// Subjects search filtering (matches teachers page behavior)
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('subjectSearchInput');
    var grid = document.getElementById('subjectsGrid');
    if (!input || !grid) return;
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.teacher-card'));
    function normalize(s){ return (s || '').toLowerCase(); }
    function filter() {
        var q = normalize(input.value);
        cards.forEach(function(card){
            var text = normalize(card.textContent);
            var match = !q || text.indexOf(q) !== -1;
            card.style.display = match ? '' : 'none';
        });
    }
    input.addEventListener('input', filter);
});

// Show toasts if any
<?php if ($message || $error): ?>
document.addEventListener('DOMContentLoaded', function(){
  <?php if ($message): ?> if (typeof showNotification==='function') showNotification(<?php echo json_encode($message); ?>,'success'); <?php endif; ?>
  <?php if ($error): ?> if (typeof showNotification==='function') showNotification(<?php echo json_encode($error); ?>,'error'); <?php endif; ?>
});
<?php endif; ?>
</script>

<?php include '../components/footer.php'; ?>
