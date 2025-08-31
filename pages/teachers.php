<?php
require_once '../config/config.php';

// Check if user is logged in and is SuperAdmin
if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../auth/login.php');
}

$page_title = 'Teachers Management';

// Include components
require_once '../components/table.php';
require_once '../components/modal.php';
require_once '../components/teacher-detail.php';

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    if (validateCSRFToken($_POST['csrf_token'])) {
                        $username = sanitizeInput($_POST['username']);
                        $email = sanitizeInput($_POST['email']);
                        $first_name = sanitizeInput($_POST['first_name']);
                        $last_name = sanitizeInput($_POST['last_name']);
                        $phone = sanitizeInput($_POST['phone']);
                        $password = $_POST['password'];
                        $profile_image_url = null;
                        $uploadAttempted = false;
                        
                        // Check if username or email already exists
                        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                        $check_stmt->execute([$username, $email]);
                        
                        if ($check_stmt->fetch()) {
                            $error = 'Username or email already exists.';
                        } else {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            // Handle profile image upload if provided
                            $uploadAttempted = false;
                            if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                                $uploadAttempted = true;
                                $allowed_exts = ['jpg','jpeg','png','gif','webp'];
                                $max_size = 2 * 1024 * 1024; // 2MB
                                $fileErr = $_FILES['profile_image']['error'];
                                $tmp_name = $_FILES['profile_image']['tmp_name'];
                                $orig_name = $_FILES['profile_image']['name'];
                                $size = (int)$_FILES['profile_image']['size'];
                                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                                if ($fileErr === UPLOAD_ERR_OK) {
                                    // Optional MIME check
                                    if (function_exists('finfo_open')) {
                                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                        $mime = $finfo ? finfo_file($finfo, $tmp_name) : '';
                                        if ($finfo) finfo_close($finfo);
                                        $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
                                        if ($mime && !in_array($mime, $allowed_mimes, true)) {
                                            $error = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
                                        }
                                    }
                                    if (!$error && ($size > $max_size)) {
                                        $error = 'Image too large. Max 2MB.';
                                    }
                                    if (!$error && !in_array($ext, $allowed_exts, true)) {
                                        $error = 'Invalid file extension. Allowed: ' . implode(', ', $allowed_exts);
                                    }
                                    if (!$error) {
                                        $uploadsDirFs = dirname(__DIR__) . '/uploads/teachers';
                                        if (!is_dir($uploadsDirFs)) {
                                            @mkdir($uploadsDirFs, 0755, true);
                                        }
                                        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                                        $unique = bin2hex(random_bytes(6));
                                        $filename = $safeBase . '_' . $unique . '.' . $ext;
                                        $destFs = $uploadsDirFs . '/' . $filename;
                                        if (@move_uploaded_file($tmp_name, $destFs)) {
                                            $appBaseUrl = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                            if ($appBaseUrl === '' || $appBaseUrl === '.') { $appBaseUrl = ''; }
                                            $profile_image_url = $appBaseUrl . '/uploads/teachers/' . $filename;
                                        } else {
                                            $error = 'Failed to save uploaded image (permission or path issue).';
                                        }
                                    }
                                } else {
                                    // Map common PHP upload errors
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

                            // If upload was attempted but failed, do not proceed
                            if ($uploadAttempted && !$profile_image_url && $error) {
                                // Keep $action as list to render page and show error
                                $action = 'list';
                            } else {
                                if ($profile_image_url) {
                                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, phone, profile_image, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'teacher')");
                                    $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $phone, $profile_image_url]);
                                } else {
                                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role) VALUES (?, ?, ?, ?, ?, ?, 'teacher')");
                                    $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $phone]);
                                }
                                $message = 'Teacher added successfully!';
                                $action = 'list';
                            }
                        }
                    }
                    break;
                    
                case 'edit':
                    if (validateCSRFToken($_POST['csrf_token'])) {
                        $id = (int)$_POST['id'];
                        $first_name = sanitizeInput($_POST['first_name']);
                        $last_name = sanitizeInput($_POST['last_name']);
                        $phone = sanitizeInput($_POST['phone']);
                        $email = sanitizeInput($_POST['email']);
                        $profile_image_url = null;
                        $uploadAttempted = false;

                        // Handle profile image upload if provided
                        if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                            $uploadAttempted = true;
                            $allowed_exts = ['jpg','jpeg','png','gif','webp'];
                            $max_size = 2 * 1024 * 1024; // 2MB
                            $fileErr = $_FILES['profile_image']['error'];
                            $tmp_name = $_FILES['profile_image']['tmp_name'];
                            $orig_name = $_FILES['profile_image']['name'];
                            $size = (int)$_FILES['profile_image']['size'];
                            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                            if ($fileErr === UPLOAD_ERR_OK) {
                                // Optional MIME check
                                if (function_exists('finfo_open')) {
                                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                    $mime = $finfo ? finfo_file($finfo, $tmp_name) : '';
                                    if ($finfo) finfo_close($finfo);
                                    $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
                                    if ($mime && !in_array($mime, $allowed_mimes, true)) {
                                        $error = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
                                    }
                                }
                                if (!$error && ($size > $max_size)) {
                                    $error = 'Image too large. Max 2MB.';
                                }
                                if (!$error && !in_array($ext, $allowed_exts, true)) {
                                    $error = 'Invalid file extension. Allowed: ' . implode(', ', $allowed_exts);
                                }
                                if (!$error) {
                                    $uploadsDirFs = dirname(__DIR__) . '/uploads/teachers';
                                    if (!is_dir($uploadsDirFs)) {
                                        @mkdir($uploadsDirFs, 0755, true);
                                    }
                                    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                                    $unique = bin2hex(random_bytes(6));
                                    $filename = $safeBase . '_' . $unique . '.' . $ext;
                                    $destFs = $uploadsDirFs . '/' . $filename;
                                    if (@move_uploaded_file($tmp_name, $destFs)) {
                                        $appBaseUrl = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                        if ($appBaseUrl === '' || $appBaseUrl === '.') { $appBaseUrl = ''; }
                                        $profile_image_url = $appBaseUrl . '/uploads/teachers/' . $filename;
                                    } else {
                                        $error = 'Failed to save uploaded image (permission or path issue).';
                                    }
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

                        // If upload was attempted but failed, do not proceed
                        if ($uploadAttempted && !$profile_image_url && $error) {
                            $action = 'list';
                        } else {
                            if ($profile_image_url) {
                                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, email = ?, profile_image = ? WHERE id = ? AND role = 'teacher'");
                                $stmt->execute([$first_name, $last_name, $phone, $email, $profile_image_url, $id]);
                            } else {
                                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ? AND role = 'teacher'");
                                $stmt->execute([$first_name, $last_name, $phone, $email, $id]);
                            }
                            $message = 'Teacher updated successfully!';
                            $action = 'list';
                        }
                    }
                    break;
                    
                case 'delete':
                    if (validateCSRFToken($_POST['csrf_token'])) {
                        $id = (int)$_POST['id'];
                        
                        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'teacher'");
                        $stmt->execute([$id]);
                        
                        $message = 'Teacher deactivated successfully!';
                        $action = 'list';
                    }
                    break;
            }
        }
    }
    
    // Get teachers list
    $teachers = [];
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone, profile_image, is_active, email_verified, created_at FROM users WHERE role = 'teacher' ORDER BY first_name, last_name");
        $stmt->execute();
        $teachers = $stmt->fetchAll();
    }
    
    // Get teacher for editing
    $edit_teacher = null;
    if ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$id]);
        $edit_teacher = $stmt->fetch();
        
        if (!$edit_teacher) {
            $error = 'Teacher not found.';
            $action = 'list';
        }
    }
    
    // Get teacher for viewing
    $view_teacher = null;
    if ($action === 'view' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone, profile_image, is_active, email_verified, created_at FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$id]);
        $view_teacher = $stmt->fetch();
        
        if (!$view_teacher) {
            $error = 'Teacher not found.';
            $action = 'list';
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Include header
include '../components/header.php';
?>

<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Teachers</h1>
        </div>
        
        <?php // Inline banners removed in favor of toast notifications ?>
        
        <?php if ($action === 'list'): ?>
            <!-- Teachers List -->
            <div class="card-header">
                <div class="search-input-wrapper" style="max-width: 420px; width: 100%;">
                    <i class="fas fa-search"></i>
                    <input id="teacherSearchInput" type="text" class="table-search-input" placeholder="Search teachers by name, username, email or phone..." />
                </div>
                <div class="card-header--right">
                    <button type="button" class="btn btn-primary" onclick="openAddTeacherModal()">
                        <i class="fas fa-plus"></i> Add New Teacher
                    </button>
                </div>
            </div>

            <div class="card-content">
                    <?php if (!empty($teachers)): ?>
                        <div id="teachersGrid" class="cards-grid teachers-grid">
                            <?php foreach ($teachers as $t): ?>
                                <?php
                                    $fullName = trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
                                    $statusClass = !empty($t['is_active']) ? 'status-active' : 'status-inactive';
                                    $statusText = !empty($t['is_active']) ? 'Active' : 'Inactive';
                                    $created = !empty($t['created_at']) ? date('M j, Y', strtotime($t['created_at'])) : '';
                                ?>
                                <div class="teacher-card">
                                    <span class="teacher-status status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    <div class="teacher-avatar">
                                        <?php if (!empty($t['profile_image'])): ?>
                                            <img class="teacher-avatar-img" src="<?php echo htmlspecialchars($t['profile_image']); ?>" alt="<?php echo htmlspecialchars($fullName ?: $t['username']); ?> avatar" />
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="teacher-name"><strong><?php echo htmlspecialchars($fullName ?: $t['username']); ?></strong></div>
                                    <div class="teacher-username">@<?php echo htmlspecialchars($t['username']); ?></div>
                                    <div class="teacher-card-body centered">
                                        <div class="info-row"><i class="fas fa-envelope"></i><span><?php echo htmlspecialchars($t['email']); ?></span></div>
                                        <div class="info-row"><i class="fas fa-phone"></i><span><?php echo htmlspecialchars($t['phone'] ?? '-'); ?></span></div>
                                        <div class="info-row"><i class="fas fa-calendar"></i><span><?php echo htmlspecialchars($created); ?></span></div>
                                    </div>
                                    <div class="teacher-card-actions action-buttons centered">
                                        <a class="btn btn-sm btn-outline" href="?action=view&id=<?php echo (int)$t['id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                        <button class="btn btn-sm btn-outline" type="button" onclick="openEditTeacherModal(<?php echo (int)$t['id']; ?>)" title="Edit Teacher"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-error" type="button" onclick="openDeleteTeacherModal(<?php echo (int)$t['id']; ?>)" title="Deactivate Teacher"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-users"></i>
                            <p>No teachers found. Add your first teacher to get started.</p>
                            <button type="button" class="btn btn-primary" onclick="openModalAddTeacherModal()">Add Teacher</button>
                        </div>
                    <?php endif; ?>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var input = document.getElementById('teacherSearchInput');
                    var grid = document.getElementById('teachersGrid');
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
                </script>

<?php if ($message || $error): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    <?php if ($message): ?>
    window.RindaApp && window.RindaApp.showNotification(<?php echo json_encode($message); ?>, 'success');
    <?php endif; ?>
    <?php if ($error): ?>
    window.RindaApp && window.RindaApp.showNotification(<?php echo json_encode($error); ?>, 'error');
    <?php endif; ?>
});
</script>
<?php endif; ?>
            
        <?php elseif ($action === 'view' && $view_teacher): ?>
            <!-- Teacher Detail View -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-circle"></i> Teacher Details</h3>
                    <div class="header-actions">
                    <a href="?action=list" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                        <button type="button" class="btn btn-primary" onclick="openEditTeacherModal(<?php echo $view_teacher['id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
                
                <div class="card-content">
                    <?php renderTeacherDetail($view_teacher); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Add Teacher Modal -->
<?php
$addTeacherForm = '
<form id="addTeacherForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" required 
                                       placeholder="Enter username" maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required 
                                       placeholder="Enter email address">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required 
                                       placeholder="Enter first name" maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required 
                                       placeholder="Enter last name" maxlength="50">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       placeholder="Enter phone number">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required 
                                       placeholder="Enter password" minlength="8" data-strength="true">
                                <div class="password-strength">
                                    <div class="password-strength-bar"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="profile_image">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                            <small class="form-help">Accepted: JPG, PNG, GIF, WEBP. Max 2MB.</small>
                        </div>
</form>';

renderFormModal('addTeacherModal', 'Add New Teacher', $addTeacherForm, 'Add Teacher', 'Cancel', [
    'size' => 'large',
    'onSubmit' => 'handleAddTeacher',
    'formId' => 'addTeacherForm'
]);
?>

<!-- Edit Teacher Modal -->
<?php
$editTeacherForm = '
<form id="editTeacherForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" id="edit_teacher_id">
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
                        
                        <div class="form-row">
                            <div class="form-group">
            <label for="edit_username">Username</label>
            <input type="text" id="edit_username" disabled class="form-input-disabled">
                                <small class="form-help">Username cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
            <label for="edit_email">Email Address *</label>
            <input type="email" id="edit_email" name="email" required 
                                       placeholder="Enter email address">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
            <label for="edit_first_name">First Name *</label>
            <input type="text" id="edit_first_name" name="first_name" required 
                                       placeholder="Enter first name" maxlength="50">
                            </div>
                            
                            <div class="form-group">
            <label for="edit_last_name">Last Name *</label>
            <input type="text" id="edit_last_name" name="last_name" required 
                                       placeholder="Enter last name" maxlength="50">
                            </div>
                        </div>
                        
                        <div class="form-group">
        <label for="edit_phone">Phone Number</label>
        <input type="tel" id="edit_phone" name="phone" 
                                   placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label for="edit_profile_image">Profile Image</label>
                            <input type="file" id="edit_profile_image" name="profile_image" accept="image/*">
                            <small class="form-help">Upload to replace current image. Max 2MB.</small>
                        </div>
</form>';

renderFormModal('editTeacherModal', 'Edit Teacher', $editTeacherForm, 'Update Teacher', 'Cancel', [
    'size' => 'large',
    'onSubmit' => 'handleEditTeacher',
    'formId' => 'editTeacherForm'
]);
?>

<!-- Delete Teacher Confirmation Modal -->
<?php
renderConfirmModal('deleteTeacherModal', 'Deactivate Teacher', 
    'Are you sure you want to deactivate this teacher? This action can be undone later.', 
    'Deactivate', 'Cancel', [
        'type' => 'warning',
        'onConfirm' => 'handleDeleteTeacher'
    ]);
?>

<!-- Assign Class Modal -->
<?php
$assignClassForm = '
<form id="assignClassForm" class="form">
    <input type="hidden" name="teacher_id" id="assign_class_teacher_id">
    
    <div class="form-group">
        <label for="class_id">Select Class *</label>
        <select id="class_id" name="class_id" required>
            <option value="">Loading classes...</option>
        </select>
        <small class="form-help">Choose a class to assign to this teacher</small>
                        </div>
</form>';

renderFormModal('assignClassModal', 'Assign Class to Teacher', $assignClassForm, 'Assign Class', 'Cancel', [
    'size' => 'medium',
    'onSubmit' => 'handleAssignClass'
]);
?>

<!-- Assign Subject Modal -->
<?php
$assignSubjectForm = '
<form id="assignSubjectForm" class="form">
    <input type="hidden" name="teacher_id" id="assign_subject_teacher_id">
    
    <div class="form-group">
        <label for="subject_id">Select Subject *</label>
        <select id="subject_id" name="subject_id" required>
            <option value="">Loading subjects...</option>
        </select>
        <small class="form-help">Choose a subject to assign to this teacher</small>
                </div>
</form>';

renderFormModal('assignSubjectModal', 'Assign Subject to Teacher', $assignSubjectForm, 'Assign Subject', 'Cancel', [
    'size' => 'medium',
    'onSubmit' => 'handleAssignSubject'
]);
?>

<script>
// Global variables
let currentTeacherId = null;
let teachersData = <?php echo json_encode($teachers); ?>;
let currentViewTeacher = <?php echo json_encode($view_teacher ?? null); ?>;

// Modal functions
function openAddTeacherModal() {
    var form = document.getElementById('addTeacherForm');
    if (form) form.reset();
    if (typeof window.openModalAddTeacherModal === 'function') {
        window.openModalAddTeacherModal();
        return;
    }
    // Fallback: directly show modal if generated function not present
    var modal = document.getElementById('addTeacherModal');
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        modal.focus();
    } else {
        console.error('Add Teacher modal element not found');
    }
}

function openEditTeacherModal(teacherId) {
    let teacher = Array.isArray(teachersData) ? teachersData.find(t => t.id == teacherId) : null;
    if (!teacher && currentViewTeacher && currentViewTeacher.id == teacherId) {
        teacher = currentViewTeacher;
    }
    if (teacher) {
        document.getElementById('edit_teacher_id').value = teacher.id;
        document.getElementById('edit_username').value = teacher.username;
        document.getElementById('edit_email').value = teacher.email;
        document.getElementById('edit_first_name').value = teacher.first_name;
        document.getElementById('edit_last_name').value = teacher.last_name;
        document.getElementById('edit_phone').value = teacher.phone || '';
        if (typeof window.openModalEditTeacherModal === 'function') {
            window.openModalEditTeacherModal();
        } else {
            const modal = document.getElementById('editTeacherModal');
            if (modal) {
                modal.classList.add('show');
                document.body.classList.add('modal-open');
                modal.focus();
            }
        }
    } else {
        console.error('Teacher not found for edit:', teacherId);
    }
}

function openDeleteTeacherModal(teacherId) {
    currentTeacherId = teacherId;
    if (typeof window.openModalDeleteTeacherModal === 'function') {
        window.openModalDeleteTeacherModal();
        return;
    }
    // Fallback if generated function missing
    var modal = document.getElementById('deleteTeacherModal');
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        modal.focus();
    } else {
        console.error('Delete Teacher modal element not found');
    }
}

function openAssignClassModal(teacherId) {
    currentTeacherId = teacherId;
    document.getElementById('assign_class_teacher_id').value = teacherId;
    loadAvailableClasses();
    openModalAssignClassModal();
}

function openAssignSubjectModal(teacherId) {
    currentTeacherId = teacherId;
    document.getElementById('assign_subject_teacher_id').value = teacherId;
    loadAvailableSubjects();
    openModalAssignSubjectModal();
}

// Form handlers
function handleAddTeacher() {
    const form = document.getElementById('addTeacherForm');
    if (form.checkValidity()) {
        form.submit();
    } else {
        form.reportValidity();
    }
}

function handleEditTeacher() {
    const form = document.getElementById('editTeacherForm');
    if (form.checkValidity()) {
        form.submit();
    } else {
        form.reportValidity();
    }
}

function handleDeleteTeacher() {
        const form = document.createElement('form');
        form.method = 'POST';
        // Hidden inputs without using template literals
        const inAction = document.createElement('input');
        inAction.type = 'hidden';
        inAction.name = 'action';
        inAction.value = 'delete';

        const inId = document.createElement('input');
        inId.type = 'hidden';
        inId.name = 'id';
        inId.value = String(currentTeacherId);

        const inCsrf = document.createElement('input');
        inCsrf.type = 'hidden';
        inCsrf.name = 'csrf_token';
        inCsrf.value = '<?php echo generateCSRFToken(); ?>';

        form.appendChild(inAction);
        form.appendChild(inId);
        form.appendChild(inCsrf);

        document.body.appendChild(form);
        form.submit();
}

// API functions
function loadAvailableClasses() {
    fetch('../api/available_classes.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('class_id');
            select.innerHTML = '<option value="">Select a class</option>';
            
            if (data.success && data.classes) {
                data.classes.forEach(cls => {
                    const option = document.createElement('option');
                    option.value = cls.id;
                    option.textContent = (cls.class_name + ' - Grade ' + cls.grade_level + ' (' + cls.current_students + '/' + cls.capacity + ' students)');
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            document.getElementById('class_id').innerHTML = '<option value="">Error loading classes</option>';
        });
}

function loadAvailableSubjects() {
    fetch('../api/available_subjects.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('subject_id');
            select.innerHTML = '<option value="">Select a subject</option>';
            
            if (data.success && data.subjects) {
                data.subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id;
                    option.textContent = (subject.subject_name + ' (' + subject.subject_code + ') - Grade ' + subject.grade_level);
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading subjects:', error);
            document.getElementById('subject_id').innerHTML = '<option value="">Error loading subjects</option>';
        });
}

function handleAssignClass() {
    const form = document.getElementById('assignClassForm');
    const formData = new FormData(form);
    
    fetch('../api/assign_class.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.RindaApp && window.RindaApp.showNotification('Class assigned successfully!', 'success');
            closeModalAssignClassModal();
            // Refresh teacher details if on detail page
            if (typeof loadTeacherAssignments === 'function') {
                loadTeacherAssignments(currentTeacherId);
            }
        } else {
            window.RindaApp && window.RindaApp.showNotification(data.error || 'Error assigning class', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.RindaApp && window.RindaApp.showNotification('Error assigning class', 'error');
    });
}

function handleAssignSubject() {
    const form = document.getElementById('assignSubjectForm');
    const formData = new FormData(form);
    
    fetch('../api/assign_subject.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.RindaApp && window.RindaApp.showNotification('Subject assigned successfully!', 'success');
            closeModalAssignSubjectModal();
            // Refresh teacher details if on detail page
            if (typeof loadTeacherAssignments === 'function') {
                loadTeacherAssignments(currentTeacherId);
            }
        } else {
            window.RindaApp && window.RindaApp.showNotification(data.error || 'Error assigning subject', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.RindaApp && window.RindaApp.showNotification(data.error || 'Error assigning subject', 'error');
    });
}

// Use global toast API from main.js via window.RindaApp.showNotification

// Assignment removal functions
function removeClassAssignment(teacherId, classId) {
    if (confirm('Are you sure you want to remove this class assignment?')) {
        const formData = new FormData();
        formData.append('teacher_id', teacherId);
        formData.append('class_id', classId);
        
        fetch('../api/remove_class_assignment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Class assignment removed successfully!', 'success');
                loadTeacherAssignments(teacherId);
            } else {
                showNotification(data.error || 'Error removing assignment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error removing assignment', 'error');
        });
    }
}

function removeSubjectAssignment(teacherId, subjectId) {
    if (confirm('Are you sure you want to remove this subject assignment?')) {
        const formData = new FormData();
        formData.append('teacher_id', teacherId);
        formData.append('subject_id', subjectId);
        
        fetch('../api/remove_subject_assignment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Subject assignment removed successfully!', 'success');
                loadTeacherAssignments(teacherId);
            } else {
                showNotification(data.error || 'Error removing assignment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error removing assignment', 'error');
        });
    }
}

// Initialize password strength indicator
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = this.parentNode.querySelector('.password-strength-bar');
            
            if (!strengthBar) return;
            
            let strength = 0;
            let className = 'password-strength-weak';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    className = 'password-strength-weak';
                    break;
                case 2:
                    className = 'password-strength-medium';
                    break;
                case 3:
                    className = 'password-strength-strong';
                    break;
                case 4:
                case 5:
                    className = 'password-strength-very-strong';
                    break;
            }
            
            strengthBar.className = 'password-strength-bar ' + className;
        });
    }
});
</script>

<script>
// Global safeguard: close modal when any element with data-dismiss="modal" is clicked
document.addEventListener('click', function(e) {
    var trigger = e.target.closest('[data-dismiss="modal"]');
    if (!trigger) return;
    var modal = trigger.closest('.modal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
});
</script>

<?php include '../components/footer.php'; ?>