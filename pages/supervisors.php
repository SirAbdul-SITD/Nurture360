<?php
require_once '../config/config.php';

// Auth check
if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../auth/login.php');
}

$page_title = 'Supervisors Management';

// Include components
require_once '../components/table.php';
require_once '../components/modal.php';
require_once '../components/supervisor-detail.php';

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

try {
    $pdo = getDBConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!validateCSRFToken($_POST['csrf_token'])) break;
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $password = $_POST['password'] ?? '';

                if (!$username || !$email || !$first_name || !$last_name || !$password) {
                    $error = 'All required fields must be filled.';
                    break;
                }

                // Unique checks
                $check_stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
                $check_stmt->execute([$username, $email]);
                if ($check_stmt->fetch()) { $error = 'Username or email already exists.'; break; }

                // Optional profile image upload
                $profile_image_url = null;
                $uploadAttempted = false;
                if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadAttempted = true;
                    $allowed_exts = ['jpg','jpeg','png','gif','webp'];
                    $max_size = 2 * 1024 * 1024;
                    $fileErr = $_FILES['profile_image']['error'];
                    $tmp_name = $_FILES['profile_image']['tmp_name'];
                    $orig_name = $_FILES['profile_image']['name'];
                    $size = (int)$_FILES['profile_image']['size'];
                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                    if ($fileErr === UPLOAD_ERR_OK) {
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = $finfo ? finfo_file($finfo, $tmp_name) : '';
                            if ($finfo) finfo_close($finfo);
                            $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
                            if ($mime && !in_array($mime, $allowed_mimes, true)) {
                                $error = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
                            }
                        }
                        if (!$error && ($size > $max_size)) { $error = 'Image too large. Max 2MB.'; }
                        if (!$error && !in_array($ext, $allowed_exts, true)) { $error = 'Invalid file extension.'; }
                        if (!$error) {
                            $uploadsDirFs = dirname(__DIR__) . '/uploads/teachers'; // reuse same uploads folder for style consistency
                            if (!is_dir($uploadsDirFs)) { @mkdir($uploadsDirFs, 0755, true); }
                            $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                            $unique = bin2hex(random_bytes(6));
                            $filename = $safeBase . '_' . $unique . '.' . $ext;
                            $destFs = $uploadsDirFs . '/' . $filename;
                            if (@move_uploaded_file($tmp_name, $destFs)) {
                                $appBaseUrl = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                if ($appBaseUrl === '' || $appBaseUrl === '.') { $appBaseUrl = ''; }
                                $profile_image_url = $appBaseUrl . '/uploads/teachers/' . $filename;
                            } else { $error = 'Failed to save uploaded image.'; }
                        }
                    } else {
                        $phpErrs = [
                            UPLOAD_ERR_INI_SIZE => 'Uploaded file exceeds server limit (upload_max_filesize).',
                            UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds form limit.',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                            UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on server.',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                        ];
                        $error = $phpErrs[$fileErr] ?? 'Unknown upload error.';
                    }
                }

                if ($uploadAttempted && !$profile_image_url && $error) { $action = 'list'; break; }

                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, phone, profile_image, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'supervisor')");
                $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $phone, $profile_image_url]);
                $message = 'Supervisor added successfully!';
                $action = 'list';
                break;

            case 'edit':
                if (!validateCSRFToken($_POST['csrf_token'])) break;
                $id = (int)($_POST['id'] ?? 0);
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $phone = sanitizeInput($_POST['phone'] ?? '');

                $profile_image_url = null;
                $uploadAttempted = false;
                if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadAttempted = true;
                    $allowed_exts = ['jpg','jpeg','png','gif','webp'];
                    $max_size = 2 * 1024 * 1024;
                    $fileErr = $_FILES['profile_image']['error'];
                    $tmp_name = $_FILES['profile_image']['tmp_name'];
                    $orig_name = $_FILES['profile_image']['name'];
                    $size = (int)$_FILES['profile_image']['size'];
                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                    if ($fileErr === UPLOAD_ERR_OK) {
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = $finfo ? finfo_file($finfo, $tmp_name) : '';
                            if ($finfo) finfo_close($finfo);
                            $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
                            if ($mime && !in_array($mime, $allowed_mimes, true)) { $error = 'Invalid image type.'; }
                        }
                        if (!$error && ($size > $max_size)) { $error = 'Image too large. Max 2MB.'; }
                        if (!$error && !in_array($ext, $allowed_exts, true)) { $error = 'Invalid file extension.'; }
                        if (!$error) {
                            $uploadsDirFs = dirname(__DIR__) . '/uploads/teachers';
                            if (!is_dir($uploadsDirFs)) { @mkdir($uploadsDirFs, 0755, true); }
                            $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                            $unique = bin2hex(random_bytes(6));
                            $filename = $safeBase . '_' . $unique . '.' . $ext;
                            $destFs = $uploadsDirFs . '/' . $filename;
                            if (@move_uploaded_file($tmp_name, $destFs)) {
                                $appBaseUrl = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                if ($appBaseUrl === '' || $appBaseUrl === '.') { $appBaseUrl = ''; }
                                $profile_image_url = $appBaseUrl . '/uploads/teachers/' . $filename;
                            } else { $error = 'Failed to save uploaded image.'; }
                        }
                    } else {
                        $phpErrs = [
                            UPLOAD_ERR_INI_SIZE => 'Uploaded file exceeds server limit (upload_max_filesize).',
                            UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds form limit.',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                            UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on server.',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                        ];
                        $error = $phpErrs[$fileErr] ?? 'Unknown upload error.';
                    }
                }

                if (!$id) { $error = 'Invalid supervisor ID.'; break; }

                if ($profile_image_url) {
                    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, profile_image=? WHERE id=? AND role='supervisor'");
                    $stmt->execute([$first_name, $last_name, $email, $phone, $profile_image_url, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=? AND role='supervisor'");
                    $stmt->execute([$first_name, $last_name, $email, $phone, $id]);
                }
                $message = 'Supervisor updated successfully!';
                $action = 'list';
                break;

            case 'delete':
                if (!validateCSRFToken($_POST['csrf_token'])) break;
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'supervisor'");
                    $stmt->execute([$id]);
                    $message = 'Supervisor deactivated successfully!';
                    $action = 'list';
                }
                break;
        }
    }

    // Fetch list
    $supervisors = [];
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone, profile_image, is_active, created_at FROM users WHERE role = 'supervisor' ORDER BY first_name, last_name");
        $stmt->execute();
        $supervisors = $stmt->fetchAll();
    }

    // View single
    $view_supervisor = null;
    $supervisor_assignments = [];
    if ($action === 'view' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone, profile_image, is_active, created_at FROM users WHERE id = ? AND role = 'supervisor'");
        $stmt->execute([$id]);
        $view_supervisor = $stmt->fetch();
        if ($view_supervisor) {
            // Fetch assigned classes for this supervisor
            $as = $pdo->prepare(
                "SELECT sa.id, sa.class_id, sa.academic_year, sa.is_active, c.class_name, c.class_code, c.grade_level, c.is_active AS class_active
                 FROM supervisor_assignments sa
                 INNER JOIN classes c ON c.id = sa.class_id
                 WHERE sa.supervisor_id = ?
                 ORDER BY sa.academic_year DESC, c.grade_level, c.class_name"
            );
            $as->execute([$id]);
            $supervisor_assignments = $as->fetchAll();
        } else {
            $error = 'Supervisor not found.'; $action = 'list';
        }
    }

} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include '../components/header.php';
?>

<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1>Supervisors</h1>
        </div>

        <?php if ($action === 'list'): ?>
            <div class="card-header">
                <div class="search-input-wrapper" style="max-width: 420px; width: 100%;">
                    <i class="fas fa-search"></i>
                    <input id="supervisorSearchInput" type="text" class="table-search-input" placeholder="Search supervisors by name, username, email or phone..." />
                </div>
                <div class="card-header--right">
                    <button type="button" class="btn btn-primary" onclick="openAddSupervisorModal()">
                        <i class="fas fa-plus"></i> Add New Supervisor
                    </button>
                </div>
            </div>

            <div class="card-content">
                <?php if (!empty($supervisors)): ?>
                <div id="supervisorsGrid" class="cards-grid teachers-grid">
                    <?php foreach ($supervisors as $s): ?>
                        <?php $fullName = trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?>
                        <div class="teacher-card">
                            <span class="teacher-status status-badge <?php echo !empty($s['is_active']) ? 'status-active' : 'status-inactive'; ?>"><?php echo !empty($s['is_active']) ? 'Active' : 'Inactive'; ?></span>
                            <div class="teacher-avatar">
                                <?php if (!empty($s['profile_image'])): ?>
                                    <img class="teacher-avatar-img" src="<?php echo htmlspecialchars($s['profile_image']); ?>" alt="<?php echo htmlspecialchars($fullName ?: $s['username']); ?> avatar" />
                                <?php else: ?>
                                    <i class="fas fa-user-shield"></i>
                                <?php endif; ?>
                            </div>
                            <div class="teacher-name"><strong><?php echo htmlspecialchars($fullName ?: $s['username']); ?></strong></div>
                            <div class="teacher-username">@<?php echo htmlspecialchars($s['username']); ?></div>
                            <div class="teacher-card-body centered">
                                <div class="info-row"><i class="fas fa-envelope"></i><span><?php echo htmlspecialchars($s['email']); ?></span></div>
                                <div class="info-row"><i class="fas fa-phone"></i><span><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></span></div>
                                <div class="info-row"><i class="fas fa-calendar"></i><span><?php echo !empty($s['created_at']) ? htmlspecialchars(date('M j, Y', strtotime($s['created_at']))) : ''; ?></span></div>
                            </div>
                            <div class="teacher-card-actions action-buttons centered">
                                <a class="btn btn-sm btn-outline" href="?action=view&id=<?php echo (int)$s['id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-outline" type="button" onclick="openEditSupervisorModal(<?php echo (int)$s['id']; ?>)" title="Edit Supervisor"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-error" type="button" onclick="openDeleteSupervisorModal(<?php echo (int)$s['id']; ?>)" title="Deactivate Supervisor"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-user-shield"></i>
                    <p>No supervisors found. Add your first supervisor to get started.</p>
                    <button type="button" class="btn btn-primary" onclick="openAddSupervisorModal()">Add Supervisor</button>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($message || $error): ?>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                <?php if ($message): ?>
                showNotification(<?php echo json_encode($message); ?>, 'success');
                <?php endif; ?>
                <?php if ($error): ?>
                showNotification(<?php echo json_encode($error); ?>, 'error');
                <?php endif; ?>
            });
            </script>
            <?php endif; ?>

        <?php elseif ($action === 'view' && $view_supervisor): ?>
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-shield"></i> Supervisor Details</h3>
                    <div class="header-actions">
                        <a href="?action=list" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to List</a>
                        <button type="button" class="btn btn-primary" onclick="openEditSupervisorModal(<?php echo (int)$view_supervisor['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                    </div>
                </div>
                <div class="card-content">
                    <?php renderSupervisorDetail($view_supervisor, [ 'showAssignments' => true, 'assignments' => $supervisor_assignments ]); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
// Add Supervisor Modal
$addSupervisorForm = '
<form id="addSupervisorForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">

    <div class="form-row">
        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required placeholder="Enter username" maxlength="50">
        </div>
        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" required placeholder="Enter email address">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="first_name">First Name *</label>
            <input type="text" id="first_name" name="first_name" required placeholder="Enter first name" maxlength="50">
        </div>
        <div class="form-group">
            <label for="last_name">Last Name *</label>
            <input type="text" id="last_name" name="last_name" required placeholder="Enter last name" maxlength="50">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="Enter phone number">
        </div>
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required placeholder="Enter password" minlength="8" data-strength="true">
            <div class="password-strength"><div class="password-strength-bar"></div></div>
        </div>
    </div>

    <div class="form-group">
        <label for="profile_image">Profile Image</label>
        <input type="file" id="profile_image" name="profile_image" accept="image/*">
        <small class="form-help">Accepted: JPG, PNG, GIF, WEBP. Max 2MB.</small>
    </div>
</form>';

renderFormModal('addSupervisorModal', 'Add New Supervisor', $addSupervisorForm, 'Add Supervisor', 'Cancel', [
    'size' => 'large',
    'onSubmit' => 'handleAddSupervisor',
    'formId' => 'addSupervisorForm'
]);

// Edit Supervisor Modal
$editSupervisorForm = '
<form id="editSupervisorForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" id="edit_supervisor_id">
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">

    <div class="form-row">
        <div class="form-group">
            <label for="edit_username">Username</label>
            <input type="text" id="edit_username" disabled class="form-input-disabled">
            <small class="form-help">Username cannot be changed</small>
        </div>
        <div class="form-group">
            <label for="edit_email">Email Address *</label>
            <input type="email" id="edit_email" name="email" required placeholder="Enter email address">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="edit_first_name">First Name *</label>
            <input type="text" id="edit_first_name" name="first_name" required placeholder="Enter first name" maxlength="50">
        </div>
        <div class="form-group">
            <label for="edit_last_name">Last Name *</label>
            <input type="text" id="edit_last_name" name="last_name" required placeholder="Enter last name" maxlength="50">
        </div>
    </div>

    <div class="form-group">
        <label for="edit_phone">Phone Number</label>
        <input type="tel" id="edit_phone" name="phone" placeholder="Enter phone number">
    </div>

    <div class="form-group">
        <label for="edit_profile_image">Profile Image</label>
        <input type="file" id="edit_profile_image" name="profile_image" accept="image/*">
        <small class="form-help">Upload to replace current image. Max 2MB.</small>
    </div>
</form>';

renderFormModal('editSupervisorModal', 'Edit Supervisor', $editSupervisorForm, 'Update Supervisor', 'Cancel', [
    'size' => 'large',
    'onSubmit' => 'handleEditSupervisor',
    'formId' => 'editSupervisorForm'
]);

// Delete Supervisor Modal
renderConfirmModal('deleteSupervisorModal', 'Deactivate Supervisor',
    'Are you sure you want to deactivate this supervisor? This action can be undone later.',
    'Deactivate', 'Cancel', [
        'type' => 'warning',
        'onConfirm' => 'handleDeleteSupervisor'
    ]);
?>

<script>
let supervisorsData = <?php echo json_encode($supervisors ?? []); ?>;
let currentViewSupervisor = <?php echo json_encode($view_supervisor ?? null); ?>;
let currentSupervisorId = null;

function openAddSupervisorModal() {
  const form = document.getElementById('addSupervisorForm');
  if (form) form.reset();
  if (typeof window.openModalAddSupervisorModal === 'function') { window.openModalAddSupervisorModal(); return; }
  const modal = document.getElementById('addSupervisorModal');
  if (modal) { modal.classList.add('show'); document.body.classList.add('modal-open'); modal.focus(); }
}

function openEditSupervisorModal(id) {
  let s = Array.isArray(supervisorsData) ? supervisorsData.find(x => x.id == id) : null;
  if (!s && currentViewSupervisor && currentViewSupervisor.id == id) { s = currentViewSupervisor; }
  if (!s) { console.error('Supervisor not found', id); return; }
  document.getElementById('edit_supervisor_id').value = s.id;
  document.getElementById('edit_username').value = s.username;
  document.getElementById('edit_email').value = s.email;
  document.getElementById('edit_first_name').value = s.first_name;
  document.getElementById('edit_last_name').value = s.last_name;
  document.getElementById('edit_phone').value = s.phone || '';
  if (typeof window.openModalEditSupervisorModal === 'function') { window.openModalEditSupervisorModal(); }
  else {
    const modal = document.getElementById('editSupervisorModal');
    if (modal) { modal.classList.add('show'); document.body.classList.add('modal-open'); modal.focus(); }
  }
}

function openDeleteSupervisorModal(id) {
  currentSupervisorId = id;
  if (typeof window.openModalDeleteSupervisorModal === 'function') { window.openModalDeleteSupervisorModal(); return; }
  const modal = document.getElementById('deleteSupervisorModal');
  if (modal) { modal.classList.add('show'); document.body.classList.add('modal-open'); modal.focus(); }
}

function handleAddSupervisor() {
  const form = document.getElementById('addSupervisorForm');
  if (form.checkValidity()) form.submit(); else form.reportValidity();
}
function handleEditSupervisor() {
  const form = document.getElementById('editSupervisorForm');
  if (form.checkValidity()) form.submit(); else form.reportValidity();
}
function handleDeleteSupervisor() {
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                   '<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">' +
                   '<input type="hidden" name="id" value="' + String(currentSupervisorId || '') + '">';
  document.body.appendChild(form);
  form.submit();
}

// Simple search filter
(function(){
  const input = document.getElementById('supervisorSearchInput');
  const grid = document.getElementById('supervisorsGrid');
  if (!input || !grid) return;
  const cards = Array.prototype.slice.call(grid.querySelectorAll('.teacher-card'));
  function normalize(s){ return (s || '').toLowerCase(); }
  function filter(){
    const q = normalize(input.value);
    cards.forEach(function(card){
      const text = normalize(card.textContent);
      const match = !q || text.indexOf(q) !== -1;
      card.style.display = match ? '' : 'none';
    });
  }
  input.addEventListener('input', filter);
})();
</script>
<?php include '../components/footer.php'; ?>
