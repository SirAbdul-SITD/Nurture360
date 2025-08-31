<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$message = '';
$error = '';

$page_title = 'Classes Management';

// Ensure classes.virtual_link column exists for customizable virtual links
function ensureVirtualLinkColumn(PDO $pdo){
    try {
        $q = $pdo->query("SHOW COLUMNS FROM classes LIKE 'virtual_link'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE classes ADD COLUMN virtual_link VARCHAR(500) NULL AFTER class_code");
        }
    } catch (Throwable $e) {
        // non-fatal; feature will fallback to generated link
    }
}
ensureVirtualLinkColumn($pdo);

function generateUniqueClassCode(PDO $pdo, $length = 8) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i=0; $i<$length; $i++) { $code .= $chars[random_int(0, strlen($chars)-1)]; }
        $stmt = $pdo->prepare("SELECT 1 FROM classes WHERE class_code = ?");
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
            $class_name = trim($_POST['class_name'] ?? '');
            $grade_level = (int)($_POST['grade_level'] ?? 0);
            $academic_year = trim($_POST['academic_year'] ?? '');
            $max_students = (int)($_POST['max_students'] ?? 30);
            $description = trim($_POST['description'] ?? '');
            if ($class_name === '' || $grade_level <= 0 || $academic_year === '') {
                throw new Exception('Please provide class name, grade level and academic year');
            }
            $class_code = generateUniqueClassCode($pdo);
            $stmt = $pdo->prepare("INSERT INTO classes (class_name, class_code, grade_level, academic_year, max_students, description, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$class_name, $class_code, $grade_level, $academic_year, $max_students, $description]);
            $message = 'Class created successfully';
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $class_name = trim($_POST['class_name'] ?? '');
            $grade_level = (int)($_POST['grade_level'] ?? 0);
            $academic_year = trim($_POST['academic_year'] ?? '');
            $max_students = (int)($_POST['max_students'] ?? 30);
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $virtual_link = trim($_POST['virtual_link'] ?? '');
            if ($id <= 0) throw new Exception('Invalid class ID');
            $stmt = $pdo->prepare("UPDATE classes SET class_name=?, grade_level=?, academic_year=?, max_students=?, description=?, is_active=?, virtual_link=? WHERE id=?");
            $stmt->execute([$class_name, $grade_level, $academic_year, $max_students, $description, $is_active, ($virtual_link !== '' ? $virtual_link : null), $id]);
            $message = 'Class updated successfully';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid class ID');
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id=?");
            $stmt->execute([$id]);
            $message = 'Class deleted successfully';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

// Fetch classes + assignment summaries
$stmt = $pdo->query("SELECT * FROM classes ORDER BY grade_level, class_name");
$classes = $stmt->fetchAll();

// Build maps for teacher count and supervisor existence (active ones)
$classTeacherCounts = [];
$classSupervisorExists = [];
try {
    $tc = $pdo->query("SELECT class_id, COUNT(*) AS cnt FROM teacher_assignments WHERE is_active=1 GROUP BY class_id")->fetchAll();
    foreach ($tc as $row) { $classTeacherCounts[(int)$row['class_id']] = (int)$row['cnt']; }
} catch (Throwable $e) {}
try {
    $sc = $pdo->query("SELECT class_id, COUNT(*) AS cnt FROM supervisor_assignments WHERE is_active=1 AND class_id IS NOT NULL GROUP BY class_id")->fetchAll();
    foreach ($sc as $row) { $classSupervisorExists[(int)$row['class_id']] = ((int)$row['cnt']) > 0; }
} catch (Throwable $e) {}

function virtualClassUrl($code) {
    return APP_URL . '/virtual/class/' . urlencode($code);
}

include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
<div class="page-header">
  <h1>Classes</h1>
  <button class="btn btn-primary" onclick="openCreateClassModal()"><i class="fas fa-plus"></i> New Class</button>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive classes-table">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Grade</th>
            <th>Year</th>
            <th>Max Students</th>
            <th>Status</th>
            <th>Virtual Link</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($classes as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars($c['class_name']); ?></td>
            <td><span class="badge"><?php echo htmlspecialchars($c['class_code']); ?></span></td>
            <td><?php echo (int)$c['grade_level']; ?></td>
            <td><?php echo htmlspecialchars($c['academic_year']); ?></td>
            <td><?php echo (int)$c['max_students']; ?></td>
            <td><?php echo !empty($c['is_active']) ? '<span class="status-badge status-active">Active</span>' : '<span class="status-badge status-inactive">Inactive</span>'; ?></td>
            <td>
              <?php $link = !empty($c['virtual_link']) ? $c['virtual_link'] : virtualClassUrl($c['class_code']); ?>
              <div class="input-group">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($link); ?>" readonly>
                <button class="btn btn-secondary" onclick="copyText(this)" title="Copy link"><i class="fas fa-copy"></i></button>
              </div>
            </td>
            <td>
              <div class="btn-group">
                <a class="btn btn-sm" href="class_details.php?id=<?php echo (int)$c['id']; ?>" title="View class details"><i class="fas fa-eye"></i></a>
                <button class="btn btn-sm btn-primary hide-on-mobile" onclick="openEditClassModal(<?php echo (int)$c['id']; ?>, <?php echo htmlspecialchars(json_encode($c)); ?>)"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-error hide-on-mobile" onclick="confirmDeleteClass(<?php echo (int)$c['id']; ?>)"><i class="fas fa-trash"></i></button>
                <?php $tcount = $classTeacherCounts[(int)$c['id']] ?? 0; ?>
                <button class="btn btn-sm <?php echo $tcount>0 ? 'btn-primary' : ''; ?>" onclick="openAssignTeacherModal(<?php echo (int)$c['id']; ?>)"><i class="fas fa-chalkboard-teacher"></i> <?php echo $tcount>0 ? 'Manage Teachers' : 'Add Teacher'; ?></button>
                <?php $hasSup = $classSupervisorExists[(int)$c['id']] ?? false; ?>
                <button class="btn btn-sm <?php echo $hasSup ? 'btn-primary' : ''; ?>" onclick="openAssignSupervisorModal(<?php echo (int)$c['id']; ?>)"><i class="fas fa-user-shield"></i> <?php echo $hasSup ? 'Change Supervisor' : 'Add Supervisor'; ?></button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <!-- Mobile cards fallback -->
    <div class="mobile-cards classes-mobile-cards">
      <?php foreach ($classes as $c): ?>
        <div class="mobile-card">
          <div class="mobile-card-header">
            <div class="title"><?php echo htmlspecialchars($c['class_name']); ?></div>
            <span class="badge"><?php echo htmlspecialchars($c['class_code']); ?></span>
          </div>
          <div class="mobile-card-body">
            <div class="row"><span>Grade:</span><strong><?php echo (int)$c['grade_level']; ?></strong></div>
            <div class="row"><span>Year:</span><strong><?php echo htmlspecialchars($c['academic_year']); ?></strong></div>
            <div class="row"><span>Max Students:</span><strong><?php echo (int)$c['max_students']; ?></strong></div>
            <div class="row"><span>Status:</span>
              <?php echo !empty($c['is_active']) ? '<span class="status-badge status-active">Active</span>' : '<span class="status-badge status-inactive">Inactive</span>'; ?>
            </div>
            <div class="row"><span>Link:</span>
              <?php $link = !empty($c['virtual_link']) ? $c['virtual_link'] : virtualClassUrl($c['class_code']); ?>
              <div class="input-group">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($link); ?>" readonly>
                <button class="btn btn-secondary" onclick="copyText(this)" title="Copy link"><i class="fas fa-copy"></i></button>
              </div>
            </div>
          </div>
          <div class="mobile-card-actions btn-group">
            <a class="btn btn-sm" href="class_details.php?id=<?php echo (int)$c['id']; ?>"><i class="fas fa-eye"></i> View</a>
            <?php $tcount = $classTeacherCounts[(int)$c['id']] ?? 0; ?>
            <?php $hasSup = $classSupervisorExists[(int)$c['id']] ?? false; ?>
            <button class="btn btn-sm btn-primary hide-on-mobile" onclick="openEditClassModal(<?php echo (int)$c['id']; ?>, <?php echo htmlspecialchars(json_encode($c)); ?>)"><i class="fas fa-edit"></i> Edit</button>
            <button class="btn btn-sm btn-error hide-on-mobile" onclick="confirmDeleteClass(<?php echo (int)$c['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
            <button class="btn btn-sm <?php echo $tcount>0 ? 'btn-primary' : ''; ?>" onclick="openAssignTeacherModal(<?php echo (int)$c['id']; ?>)"><i class="fas fa-chalkboard-teacher"></i> <?php echo $tcount>0 ? 'Manage Teachers' : 'Add Teacher'; ?></button>
            <button class="btn btn-sm <?php echo $hasSup ? 'btn-primary' : ''; ?>" onclick="openAssignSupervisorModal(<?php echo (int)$c['id']; ?>)"><i class="fas fa-user-shield"></i> <?php echo $hasSup ? 'Change Supervisor' : 'Add Supervisor'; ?></button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

  </main>
</div>

<?php include '../components/modal.php'; ?>

<?php
$createClassForm = '
<form id="createClassForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
  <input type="hidden" name="action" value="create">
  <div class="form-row">
    <div class="form-group">
      <label>Class Name *</label>
      <input type="text" name="class_name" required placeholder="e.g., Grade 6 A">
    </div>
    <div class="form-group">
      <label>Grade Level *</label>
      <input type="number" name="grade_level" min="1" required placeholder="e.g., 6">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Academic Year *</label>
      <input type="text" name="academic_year" placeholder="2025/2026" required>
    </div>
    <div class="form-group">
      <label>Max Students *</label>
      <input type="number" name="max_students" min="1" value="30" required>
    </div>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description" placeholder="Optional notes..."></textarea>
  </div>
</form>';

renderFormModal('createClassModal', 'Create Class', $createClassForm, 'Create', 'Cancel', [
  'size' => 'large',
  'formId' => 'createClassForm'
]);

$editClassForm = '
<form id="editClassForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" id="edit_class_id">
  <div class="form-row">
    <div class="form-group">
      <label>Class Name *</label>
      <input type="text" name="class_name" id="edit_class_name" required>
    </div>
    <div class="form-group">
      <label>Grade Level *</label>
      <input type="number" name="grade_level" id="edit_grade_level" min="1" required>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Academic Year *</label>
      <input type="text" name="academic_year" id="edit_academic_year" required>
    </div>
    <div class="form-group">
      <label>Max Students *</label>
      <input type="number" name="max_students" id="edit_max_students" min="1" required>
    </div>
  </div>
  <div class="form-group">
    <label>Virtual Class Link (optional)</label>
    <input type="url" name="virtual_link" id="edit_virtual_link" class="virtual-link-input" placeholder="https://...">
    <small class="text-muted">Leave blank to use default: ' . APP_URL . '/virtual/class/{class_code}</small>
  </div>
  <div class="form-row">
    <div class="form-group" style="align-self: end;">
      <label><input type="checkbox" name="is_active" id="edit_is_active"> Active</label>
    </div>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description" id="edit_description"></textarea>
  </div>
</form>';

renderFormModal('editClassModal', 'Edit Class', $editClassForm, 'Save', 'Cancel', [
  'size' => 'large',
  'formId' => 'editClassForm'
]);

$assignTeacherForm = '
<form id="assignTeacherForm" class="form">
  <input type="hidden" name="class_id" id="assign_teacher_class_id">
  <div class="form-row">
    <div class="form-group">
      <label>Teachers *</label>
      <select id="teacher_select" multiple size="6" required></select>
      <small class="text-muted">Hold Ctrl/Cmd to select multiple teachers</small>
    </div>
    <div class="form-group">
      <label>Subject *</label>
      <select name="subject_id" id="subject_select" required></select>
    </div>
  </div>
  <div class="form-group">
    <label>Academic Year *</label>
    <input type="text" name="academic_year" id="assign_teacher_year" placeholder="2025/2026" required>
  </div>
</form>';

renderFormModal('assignTeacherModal', 'Assign Teacher', $assignTeacherForm, 'Assign', 'Cancel', [
  'size' => 'medium',
  'formId' => 'assignTeacherForm'
]);

$assignSupervisorForm = '
<form id="assignSupervisorForm" class="form">
  <input type="hidden" name="class_id" id="assign_supervisor_class_id">
  <div class="form-row">
    <div class="form-group">
      <label>Supervisor *</label>
      <select name="supervisor_id" id="supervisor_select" required></select>
    </div>
    <div class="form-group">
      <label>Academic Year *</label>
      <input type="text" name="academic_year" id="assign_supervisor_year" placeholder="2025/2026" required>
    </div>
  </div>
</form>';

renderFormModal('assignSupervisorModal', 'Assign Supervisor', $assignSupervisorForm, 'Assign', 'Cancel', [
  'size' => 'medium',
  'formId' => 'assignSupervisorForm'
]);
?>

<script>
function copyText(btn){
  const input = btn.parentElement.querySelector('input');
  input.select();
  document.execCommand('copy');
  if (typeof showNotification==='function') showNotification('Copied to clipboard','success');
}

function openCreateClassModal(){
  if (typeof window.openModalCreateClassModal==='function') { window.openModalCreateClassModal(); }
  else { const m=document.getElementById('createClassModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
function submitCreateClass(){ const f=document.getElementById('createClassForm'); if (f) f.submit(); }

function openEditClassModal(id, data){
  document.getElementById('edit_class_id').value = id;
  document.getElementById('edit_class_name').value = data.class_name || '';
  document.getElementById('edit_grade_level').value = data.grade_level || '';
  document.getElementById('edit_academic_year').value = data.academic_year || '';
  document.getElementById('edit_max_students').value = data.max_students || 30;
  document.getElementById('edit_description').value = data.description || '';
  document.getElementById('edit_is_active').checked = !!Number(data.is_active || 0);
  var vInput = document.getElementById('edit_virtual_link');
  if (vInput) {
    const defaultLink = <?php echo json_encode(APP_URL); ?> + '/virtual/class/' + encodeURIComponent(data.class_code || '');
    vInput.value = data.virtual_link || defaultLink;
  }
  if (typeof window.openModalEditClassModal==='function') { window.openModalEditClassModal(); }
  else { const m=document.getElementById('editClassModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
function submitEditClass(){ document.getElementById('editClassForm').submit(); }

function confirmDeleteClass(id){
  if (!confirm('Delete this class?')) return;
  const form = document.createElement('form'); form.method='POST';
  form.innerHTML = `
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="${id}">
  `; document.body.appendChild(form); form.submit();
}

async function fetchOptions(url, selectEl, labelFn){
  selectEl.innerHTML = '<option value="">Loading...</option>';
  try {
    const res = await fetch(url); const data = await res.json();
    let options = '<option value="">Select...</option>';
    if (data && data.success) {
      const list = data.users || data.subjects || [];
      options += list.map(item => `<option value="${item.id}">${labelFn(item)}</option>`).join('');
    }
    selectEl.innerHTML = options;
  } catch(e){ console.error(e); selectEl.innerHTML = '<option value="">Error</option>'; }
}

function openAssignTeacherModal(classId){
  document.getElementById('assign_teacher_class_id').value = classId;
  if (typeof window.openModalAssignTeacherModal==='function') { window.openModalAssignTeacherModal(); }
  else { const m=document.getElementById('assignTeacherModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
  fetchOptions('../api/list_users.php?role=teacher', document.getElementById('teacher_select'), u => `${u.first_name} ${u.last_name} (@${u.username})`);
  fetchOptions('../api/available_subjects.php', document.getElementById('subject_select'), s => `${s.subject_name} (${s.subject_code})`);
}

function openAssignSupervisorModal(classId){
  document.getElementById('assign_supervisor_class_id').value = classId;
  if (typeof window.openModalAssignSupervisorModal==='function') { window.openModalAssignSupervisorModal(); }
  else { const m=document.getElementById('assignSupervisorModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
  fetchOptions('../api/list_users.php?role=supervisor', document.getElementById('supervisor_select'), u => `${u.first_name} ${u.last_name} (@${u.username})`);
}

async function submitAssignTeacher(){
  const classId = document.getElementById('assign_teacher_class_id').value;
  const teacherSelect = document.getElementById('teacher_select');
  const subjectId = document.getElementById('subject_select').value;
  const year = document.getElementById('assign_teacher_year').value;
  const selected = Array.from(teacherSelect.selectedOptions).map(o => o.value).filter(Boolean);
  if (selected.length === 0) { if (typeof showNotification==='function') showNotification('Select at least one teacher','warning'); return; }
  let successCount = 0, failCount = 0;
  for (const teacherId of selected) {
    const fd = new FormData();
    fd.append('class_id', classId);
    fd.append('teacher_id', teacherId);
    fd.append('subject_id', subjectId);
    fd.append('academic_year', year);
    try {
      const res = await fetch('../api/assign_teacher_to_class.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) { successCount++; }
      else { failCount++; }
    } catch(e){ failCount++; }
  }
  if (successCount) { if (typeof showNotification==='function') showNotification(successCount + ' teacher(s) assigned','success'); }
  if (failCount) { if (typeof showNotification==='function') showNotification(failCount + ' assignment(s) failed','warning'); }
  closeModalById('assignTeacherModal');
}

async function submitAssignSupervisor(){
  const form = document.getElementById('assignSupervisorForm');
  const fd = new FormData(form);
  try {
    const res = await fetch('../api/assign_supervisor_to_class.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { if (typeof showNotification==='function') showNotification('Supervisor assigned','success'); closeModalById('assignSupervisorModal'); }
    else { if (typeof showNotification==='function') showNotification(data.error||'Failed','error'); }
  } catch(e){ if (typeof showNotification==='function') showNotification('Server error','error'); }
}

function closeModalById(id){
  const helper = window['closeModal'+id.charAt(0).toUpperCase()+id.slice(1)];
  if (typeof helper==='function') { helper(); }
  else { const m=document.getElementById(id); if (m){ m.classList.remove('show','active'); document.body.classList.remove('modal-open'); } }
}

// Intercept form submits for AJAX-based modals
document.addEventListener('DOMContentLoaded', function(){
  var f1 = document.getElementById('assignTeacherForm');
  if (f1) f1.addEventListener('submit', function(e){ e.preventDefault(); submitAssignTeacher(); });
  var f2 = document.getElementById('assignSupervisorForm');
  if (f2) f2.addEventListener('submit', function(e){ e.preventDefault(); submitAssignSupervisor(); });
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
