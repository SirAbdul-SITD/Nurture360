<?php
require_once '../config/config.php';

// Check auth
if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../auth/login.php');
}

$page_title = 'Students Management';

// Include components
require_once '../components/table.php';
require_once '../components/modal.php';

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

function ensureStudentProfilesSchema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_profiles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        gender ENUM('male','female','other') NULL,
        dob DATE NULL,
        admission_date DATE NULL,
        guardian_name VARCHAR(100) NULL,
        guardian_phone VARCHAR(20) NULL,
        previous_school VARCHAR(150) NULL,
        emergency_contact VARCHAR(100) NULL,
        medical_notes TEXT NULL,
        address_line VARCHAR(255) NULL,
        city VARCHAR(100) NULL,
        state VARCHAR(100) NULL,
        country VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function generateStudentId(PDO $pdo): string {
    $prefix = 'RDA';
    $year = date('Y');
    $like = $prefix . '/' . $year . '/%';
    // Caller is responsible for starting a transaction (this function must not nest transactions)
    $stmt = $pdo->prepare("SELECT username FROM users WHERE role='student' AND username LIKE ? ORDER BY username DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$like]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = 1;
    if ($row && isset($row['username'])) {
        if (preg_match('/^' . preg_quote($prefix,'/') . '\/' . $year . '\/(\\d{3})$/', $row['username'], $m)) {
            $next = (int)$m[1] + 1;
        }
    }
    $id = sprintf('%s/%s/%03d', $prefix, $year, $next);
    // We don't insert here; caller handles insert within its transaction
    return $id;
}

try {
    $pdo = getDBConnection();
    ensureStudentProfilesSchema($pdo);

    // Load active classes for dropdowns
    $classes = [];
    try {
        $stmtClasses = $pdo->query("SELECT id, class_name, grade_level, academic_year FROM classes WHERE is_active = 1 ORDER BY grade_level, class_name");
        $classes = $stmtClasses ? $stmtClasses->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {
        // If classes table not available for some reason, keep empty options
        $classes = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!validateCSRFToken($_POST['csrf_token'])) break;
                $email = sanitizeInput($_POST['email'] ?? '');
                $first_name = sanitizeInput($_POST['first_name']);
                $last_name = sanitizeInput($_POST['last_name']);
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $address_line = sanitizeInput($_POST['address_line'] ?? '');
                $city = sanitizeInput($_POST['city'] ?? '');
                $state = sanitizeInput($_POST['state'] ?? '');
                $country = sanitizeInput($_POST['country'] ?? '');
                $gender = sanitizeInput($_POST['gender'] ?? '');
                $dob = sanitizeInput($_POST['dob'] ?? '');
                $admission_date = sanitizeInput($_POST['admission_date'] ?? '');
                $guardian_name = sanitizeInput($_POST['guardian_name'] ?? '');
                $guardian_phone = sanitizeInput($_POST['guardian_phone'] ?? '');
                $previous_school = sanitizeInput($_POST['previous_school'] ?? '');
                $emergency_contact = sanitizeInput($_POST['emergency_contact'] ?? '');
                $medical_notes = sanitizeInput($_POST['medical_notes'] ?? '');
                $password = $_POST['password'];
                $selected_class_id = (int)($_POST['class_id'] ?? 0);

                // Basic validation
                if (empty($first_name) || empty($last_name) || empty($password)) {
                    $error = 'First name, last name and password are required.';
                    break;
                }

                // Optional profile image upload
                $profile_image_url = null;
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
                            $uploadsDirFs = dirname(__DIR__) . '/uploads/students';
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
                                $profile_image_url = $appBaseUrl . '/uploads/students/' . $filename;
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

                if ($uploadAttempted && !$profile_image_url && $error) {
                    $action = 'list';
                    break;
                }

                // Ensure email unique if provided
                if ($email) {
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check_stmt->execute([$email]);
                    if ($check_stmt->fetch()) {
                        $error = 'Email already exists.';
                        break;
                    }
                }

                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Create student with generated username (student ID)
                try {
                    // transaction for ID generation + inserts
                    if (!$pdo->inTransaction()) $pdo->beginTransaction();

                    $student_id_username = generateStudentId($pdo);

                    // Double-check username not taken
                    $check_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $check_user->execute([$student_id_username]);
                    if ($check_user->fetch()) {
                        // Rare collision: bump sequence by counting again
                        $suffix = 2;
                        do {
                            $candidate = $student_id_username;
                            if (preg_match('#^(RDA/\d{4}/)(\d{3})$#', $candidate, $m)) {
                                $n = (int)$m[2] + $suffix - 1;
                                $student_id_username = $m[1] . sprintf('%03d', $n);
                            } else {
                                $student_id_username .= '-' . $suffix;
                            }
                            $suffix++;
                            $check_user->execute([$student_id_username]);
                        } while ($check_user->fetch());
                    }

                    if ($profile_image_url) {
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, phone, address, profile_image, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student')");
                        $stmt->execute([$student_id_username, $email, $password_hash, $first_name, $last_name, $phone, $address_line, $profile_image_url]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'student')");
                        $stmt->execute([$student_id_username, $email, $password_hash, $first_name, $last_name, $phone, $address_line]);
                    }
                    $newUserId = (int)$pdo->lastInsertId();

                    // Insert profile
                    $stmtP = $pdo->prepare("INSERT INTO student_profiles (user_id, gender, dob, admission_date, guardian_name, guardian_phone, previous_school, emergency_contact, medical_notes, address_line, city, state, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtP->execute([$newUserId, $gender ?: null, $dob ?: null, $admission_date ?: null, $guardian_name ?: null, $guardian_phone ?: null, $previous_school ?: null, $emergency_contact ?: null, $medical_notes ?: null, $address_line ?: null, $city ?: null, $state ?: null, $country ?: null]);

                    // Optional: create enrollment if a class was selected
                    if ($selected_class_id > 0) {
                        $stmtC = $pdo->prepare("SELECT academic_year FROM classes WHERE id = ?");
                        $stmtC->execute([$selected_class_id]);
                        if ($rowC = $stmtC->fetch(PDO::FETCH_ASSOC)) {
                            $ay = $rowC['academic_year'];
                            // Deactivate any existing active enrollment just in case
                            $pdo->prepare("UPDATE student_enrollments SET status='inactive' WHERE student_id = ? AND status = 'active'")->execute([$newUserId]);
                            $stmtEn = $pdo->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year, enrollment_date, status) VALUES (?, ?, ?, ?, 'active')");
                            $stmtEn->execute([$newUserId, $selected_class_id, $ay, date('Y-m-d')]);
                        }
                    }

                    $pdo->commit();
                    $message = 'Student added successfully! Student ID: ' . $student_id_username;
                    $action = 'list';
                } catch (Exception $ex) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'Failed to add student: ' . $ex->getMessage();
                }
                break;
            case 'edit':
                if (!validateCSRFToken($_POST['csrf_token'])) break;
                $id = (int)($_POST['id'] ?? 0);
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $gender = sanitizeInput($_POST['gender'] ?? '');
                $dob = sanitizeInput($_POST['dob'] ?? '');
                $admission_date = sanitizeInput($_POST['admission_date'] ?? '');
                $guardian_name = sanitizeInput($_POST['guardian_name'] ?? '');
                $guardian_phone = sanitizeInput($_POST['guardian_phone'] ?? '');
                $previous_school = sanitizeInput($_POST['previous_school'] ?? '');
                $address_line = sanitizeInput($_POST['address_line'] ?? '');
                $city = sanitizeInput($_POST['city'] ?? '');
                $state = sanitizeInput($_POST['state'] ?? '');
                $country = sanitizeInput($_POST['country'] ?? '');
                $emergency_contact = sanitizeInput($_POST['emergency_contact'] ?? '');
                $medical_notes = sanitizeInput($_POST['medical_notes'] ?? '');
                $selected_class_id = (int)($_POST['class_id'] ?? 0);

                $profile_image_url = null;
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
                            $uploadsDirFs = dirname(__DIR__) . '/uploads/students';
                            if (!is_dir($uploadsDirFs)) { @mkdir($uploadsDirFs, 0755, true); }
                            $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                            $unique = bin2hex(random_bytes(6));
                            $filename = $safeBase . '_' . $unique . '.' . $ext;
                            $destFs = $uploadsDirFs . '/' . $filename;
                            if (@move_uploaded_file($tmp_name, $destFs)) {
                                $appBaseUrl = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                if ($appBaseUrl === '' || $appBaseUrl === '.') { $appBaseUrl = ''; }
                                $profile_image_url = $appBaseUrl . '/uploads/students/' . $filename;
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

                if (!$id) { $error = 'Invalid student ID.'; break; }

                try {
                    if (!$pdo->inTransaction()) $pdo->beginTransaction();
                    if ($profile_image_url) {
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, profile_image=? WHERE id=? AND role='student'");
                        $stmt->execute([$first_name, $last_name, $email, $phone, $address_line, $profile_image_url, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE id=? AND role='student'");
                        $stmt->execute([$first_name, $last_name, $email, $phone, $address_line, $id]);
                    }
                    // Upsert student_profiles
                    $stmtChk = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id=?");
                    $stmtChk->execute([$id]);
                    if ($stmtChk->fetch()) {
                        $stmtP = $pdo->prepare("UPDATE student_profiles SET gender=?, dob=?, admission_date=?, guardian_name=?, guardian_phone=?, previous_school=?, emergency_contact=?, medical_notes=?, address_line=?, city=?, state=?, country=? WHERE user_id=?");
                        $stmtP->execute([$gender ?: null, $dob ?: null, $admission_date ?: null, $guardian_name ?: null, $guardian_phone ?: null, $previous_school ?: null, $emergency_contact ?: null, $medical_notes ?: null, $address_line ?: null, $city ?: null, $state ?: null, $country ?: null, $id]);
                    } else {
                        $stmtP = $pdo->prepare("INSERT INTO student_profiles (user_id, gender, dob, admission_date, guardian_name, guardian_phone, previous_school, emergency_contact, medical_notes, address_line, city, state, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtP->execute([$id, $gender ?: null, $dob ?: null, $admission_date ?: null, $guardian_name ?: null, $guardian_phone ?: null, $previous_school ?: null, $emergency_contact ?: null, $medical_notes ?: null, $address_line ?: null, $city ?: null, $state ?: null, $country ?: null]);
                    }

                    // If a class was selected, update the student's active enrollment
                    if ($selected_class_id > 0) {
                        // Find current active class
                        $stmtCur = $pdo->prepare("SELECT class_id FROM student_enrollments WHERE student_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
                        $stmtCur->execute([$id]);
                        $cur = $stmtCur->fetch(PDO::FETCH_ASSOC);
                        $current_class_id = $cur ? (int)$cur['class_id'] : 0;
                        if ($current_class_id !== $selected_class_id) {
                            // Deactivate current
                            $pdo->prepare("UPDATE student_enrollments SET status='inactive' WHERE student_id = ? AND status='active'")->execute([$id]);
                            // Insert new active with AY from classes
                            $stmtC = $pdo->prepare("SELECT academic_year FROM classes WHERE id = ?");
                            $stmtC->execute([$selected_class_id]);
                            if ($rowC = $stmtC->fetch(PDO::FETCH_ASSOC)) {
                                $ay = $rowC['academic_year'];
                                $stmtEn = $pdo->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year, enrollment_date, status) VALUES (?, ?, ?, ?, 'active')");
                                $stmtEn->execute([$id, $selected_class_id, $ay, date('Y-m-d')]);
                            }
                        }
                    }
                    $pdo->commit();
                    $message = 'Student updated successfully!';
                    $action = 'list';
                } catch (Exception $ex) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'Failed to update student: ' . $ex->getMessage();
                }
                break;
            case 'delete':
                if (!validateCSRFToken($_POST['csrf_token'])) break;
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'student'");
                    $stmt->execute([$id]);
                    $message = 'Student withdrawn successfully!';
                    $action = 'list';
                }
                break;
        }
    }

    // Fetch students for list
    $students = [];
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT 
            u.id, u.username, u.email, u.first_name, u.last_name, u.phone, u.address, u.profile_image, u.created_at, u.is_active,
            sp.gender, sp.dob, sp.admission_date, sp.guardian_name, sp.guardian_phone, sp.previous_school, sp.emergency_contact, sp.medical_notes,
            sp.address_line, sp.city, sp.state, sp.country,
            (
                SELECT se.class_id FROM student_enrollments se 
                WHERE se.student_id = u.id AND se.status = 'active' 
                ORDER BY se.id DESC LIMIT 1
            ) AS current_class_id
        FROM users u 
        LEFT JOIN student_profiles sp ON sp.user_id = u.id 
        WHERE u.role='student' 
        ORDER BY u.first_name, u.last_name");
        $students = $stmt->fetchAll();
    }

    // Student for view (detail page)
    $view_student = null;
    if ($action === 'view' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.phone, u.address, u.profile_image, u.is_active, u.created_at, sp.* FROM users u LEFT JOIN student_profiles sp ON sp.user_id = u.id WHERE u.id = ? AND u.role='student'");
        $stmt->execute([$id]);
        $view_student = $stmt->fetch();
        if (!$view_student) {
            $error = 'Student not found.';
            $action = 'list';
        }
        // Enrich detail data if student found
        if ($view_student) {
            // Current active classes
            $stmt = $pdo->prepare("SELECT c.id, c.class_name, c.grade_level, c.academic_year FROM student_enrollments se JOIN classes c ON c.id = se.class_id WHERE se.student_id = ? AND se.status='active' ORDER BY c.grade_level, c.class_name");
            $stmt->execute([$id]);
            $current_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Attendance stats and recent entries
            $stmt = $pdo->prepare("SELECT status, COUNT(*) cnt FROM attendance WHERE student_id = ? GROUP BY status");
            $stmt->execute([$id]);
            $attendance_stats = ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $attendance_stats[$r['status']] = (int)$r['cnt'];
            }
            $stmt = $pdo->prepare("SELECT a.date, a.status, a.remarks, c.class_name, s.subject_name FROM attendance a JOIN classes c ON c.id=a.class_id JOIN subjects s ON s.id=a.subject_id WHERE a.student_id=? ORDER BY a.date DESC, a.id DESC LIMIT 10");
            $stmt->execute([$id]);
            $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Academic performance: average and recent test results
            $stmt = $pdo->prepare("SELECT AVG(percentage) AS avg_pct FROM test_results WHERE student_id = ?");
            $stmt->execute([$id]);
            $avg_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $avg_percentage = $avg_row && $avg_row['avg_pct'] !== null ? round((float)$avg_row['avg_pct'], 2) : null;

            $stmt = $pdo->prepare("SELECT tr.submitted_at, tr.percentage, tr.grade, t.title, t.test_type, t.total_marks, s.subject_name FROM test_results tr JOIN tests t ON t.id = tr.test_id LEFT JOIN subjects s ON s.id = t.subject_id WHERE tr.student_id = ? ORDER BY tr.submitted_at DESC, tr.id DESC LIMIT 5");
            $stmt->execute([$id]);
            $recent_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h1>Students</h1>
        </div>

        <?php if ($action === 'list'): ?>
            <div class="card-header">
                <div class="search-input-wrapper" style="max-width: 420px; width: 100%;">
                    <i class="fas fa-search"></i>
                    <input id="studentSearchInput" type="text" class="table-search-input" placeholder="Search students by name, ID, email or phone..." />
                </div>
                <div class="card-header--right">
                    <button type="button" class="btn btn-primary" onclick="openAddStudentModal()">
                        <i class="fas fa-user-plus"></i> Add New Student
                    </button>
                </div>
            </div>

            <div class="card-content">
                <?php if (!empty($students)): ?>
                <div id="studentsGrid" class="cards-grid teachers-grid">
                    <?php foreach ($students as $s): ?>
                        <?php $fullName = trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?>
                        <div class="teacher-card">
                            <div class="teacher-avatar">
                                <?php if (!empty($s['profile_image'])): ?>
                                    <img class="teacher-avatar-img" src="<?php echo htmlspecialchars($s['profile_image']); ?>" alt="<?php echo htmlspecialchars($fullName ?: $s['username']); ?> avatar" onerror="this.style.display='none'; var i=this.nextElementSibling; if(i) i.style.display='inline-block';" />
                                    <i class="fas fa-user-graduate" style="display:none;color: var(--primary-color);"></i>
                                <?php else: ?>
                                    <i class="fas fa-user-graduate" style="color: var(--primary-color);"></i>
                                <?php endif; ?>
                            </div>
                            <div class="teacher-name"><strong><?php echo htmlspecialchars($fullName ?: '-'); ?></strong></div>
                            <div class="teacher-username">ID: <?php echo htmlspecialchars($s['username']); ?></div>
                            <div class="teacher-card-body centered">
                                <div class="info-row"><i class="fas fa-envelope"></i><span><?php echo htmlspecialchars($s['email'] ?: '-'); ?></span></div>
                                <div class="info-row"><i class="fas fa-phone"></i><span><?php echo htmlspecialchars($s['phone'] ?: '-'); ?></span></div>
                                <div class="info-row"><i class="fas fa-calendar-plus"></i><span><?php echo !empty($s['admission_date']) ? htmlspecialchars(date('M j, Y', strtotime($s['admission_date']))) : '-'; ?></span></div>
                            </div>
                            <div class="teacher-card-actions action-buttons centered">
                                <a class="btn btn-sm btn-primary" href="?action=view&id=<?php echo (int)$s['id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-outline" type="button" onclick="openEditStudentModal(<?php echo (int)$s['id']; ?>)" title="Edit Student"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-error" type="button" onclick="openDeleteStudentModal(<?php echo (int)$s['id']; ?>)" title="Withdraw Student"><i class="fas fa-user-slash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-user-graduate"></i>
                    <p>No students found. Add your first student to get started.</p>
                    <button type="button" class="btn btn-primary" onclick="openAddStudentModal()">Add Student</button>
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

        <?php elseif ($action === 'view' && $view_student): ?>
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-graduate"></i> Student Details</h3>
                    <div class="header-actions">
                        <a href="?action=list" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to List</a>
                    </div>
                </div>
                <div class="card-content">
                    <div class="teacher-detail">
                        <aside class="profile-card">
                            <div class="profile-card-inner">
                                <div class="avatar-wrap">
                                    <?php if (!empty($view_student['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($view_student['profile_image']); ?>" alt="<?php echo htmlspecialchars(($view_student['first_name'] ?? '') . ' ' . ($view_student['last_name'] ?? '')); ?>" onerror="this.style.display='none'; var i=this.nextElementSibling; if(i) i.style.display='inline-block';" />
                                        <i class="fas fa-user-graduate" style="display:none;font-size:64px;color:var(--primary-color);"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-graduate" style="font-size:64px;color:var(--primary-color);"></i>
                                    <?php endif; ?>
                                </div>
                                <h2 class="profile-name"><?php echo htmlspecialchars(($view_student['first_name'] ?? '') . ' ' . ($view_student['last_name'] ?? '')); ?></h2>
                                <div class="profile-username">ID: <?php echo htmlspecialchars($view_student['username']); ?></div>
                                <div class="badge-row" style="justify-content:center;">
                                    <?php $isActive = !empty($view_student['is_active']); ?>
                                    <span class="status-badge <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>"><?php echo $isActive ? 'Active' : 'Withdrawn'; ?></span>
                                </div>
                            </div>
                        </aside>

                        <section class="info-table-card">
                            <div class="info-table-header">Student Information</div>
                            <div class="info-table-body">
                                <div class="info-row"><span>Full Name</span><span><?php echo htmlspecialchars(($view_student['first_name'] ?? '') . ' ' . ($view_student['last_name'] ?? '')); ?></span></div>
                                <div class="info-row"><span>Student ID</span><span><?php echo htmlspecialchars($view_student['username']); ?></span></div>
                                <div class="info-row"><span>Email</span><span><?php echo htmlspecialchars($view_student['email'] ?? '-'); ?></span></div>
                                <div class="info-row"><span>Phone</span><span><?php echo htmlspecialchars($view_student['phone'] ?? '-'); ?></span></div>
                                <div class="info-row"><span>Gender</span><span><?php echo htmlspecialchars($view_student['gender'] ?? '-'); ?></span></div>
                                <div class="info-row"><span>Date of Birth</span><span><?php echo !empty($view_student['dob']) ? htmlspecialchars(date('M j, Y', strtotime($view_student['dob']))) : '-'; ?></span></div>
                                <div class="info-row"><span>Admission Date</span><span><?php echo !empty($view_student['admission_date']) ? htmlspecialchars(date('M j, Y', strtotime($view_student['admission_date']))) : '-'; ?></span></div>
                                <div class="info-row"><span>Guardian</span><span><?php echo htmlspecialchars($view_student['guardian_name'] ?? '-') . ' ' . htmlspecialchars($view_student['guardian_phone'] ?? ''); ?></span></div>
                                <div class="info-row"><span>Previous School</span><span><?php echo htmlspecialchars($view_student['previous_school'] ?? '-'); ?></span></div>
                                <div class="info-row"><span>Address</span><span><?php echo htmlspecialchars(trim(($view_student['address_line'] ?? '') . ', ' . ($view_student['city'] ?? '') . ', ' . ($view_student['state'] ?? '') . ', ' . ($view_student['country'] ?? ''))); ?></span></div>
                            </div>
                        </section>
                    </div>
                </div>
            
            <!-- Current Class(es) -->
            <div class="content-card" style="margin-top:16px;">
                <div class="card-header">
                    <h3><i class="fas fa-school"></i> Current Class</h3>
                </div>
                <div class="card-content">
                    <?php if (!empty($current_classes)): ?>
                        <div class="badge-row" style="flex-wrap:wrap; gap:8px;">
                            <?php foreach ($current_classes as $cc): ?>
                                <span class="status-badge" style="background:var(--primary-50); color:var(--primary-color);">
                                    <?php echo htmlspecialchars($cc['class_name']); ?> · Grade <?php echo (int)$cc['grade_level']; ?> · AY <?php echo htmlspecialchars($cc['academic_year']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data"><p>No active class found for this student.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Academic Performance -->
            <div class="content-card" style="margin-top:16px;">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Academic Performance</h3>
                </div>
                <div class="card-content">
                    <div class="info-table-card">
                        <div class="info-table-header">Overview</div>
                        <div class="info-table-body">
                            <div class="info-row"><span>Average Percentage</span><span><?php echo isset($avg_percentage) && $avg_percentage !== null ? htmlspecialchars($avg_percentage.'%') : '-'; ?></span></div>
                        </div>
                    </div>
                    <div class="info-table-card" style="margin-top:12px;">
                        <div class="info-table-header">Recent Tests</div>
                        <div class="info-table-body">
                            <?php if (!empty($recent_tests)): ?>
                                <?php foreach ($recent_tests as $t): ?>
                                    <div class="info-row">
                                        <span><?php echo htmlspecialchars(!empty($t['subject_name']) ? $t['subject_name'] : $t['title']); ?> · <?php echo htmlspecialchars(ucfirst($t['test_type'] ?? '')); ?></span>
                                        <span><?php echo !empty($t['submitted_at']) ? htmlspecialchars(date('M j, Y', strtotime($t['submitted_at']))) : '-'; ?> · <?php echo isset($t['percentage']) ? htmlspecialchars(round((float)$t['percentage']).'%') : '-'; ?> <?php echo !empty($t['grade']) ? '(' . htmlspecialchars($t['grade']) . ')' : ''; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data"><p>No test results yet.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance -->
            <div class="content-card" style="margin-top:16px;">
                <div class="card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Attendance</h3>
                </div>
                <div class="card-content">
                    <div class="badge-row" style="gap:8px; flex-wrap:wrap;">
                        <span class="status-badge status-active">Present: <?php echo isset($attendance_stats['present']) ? (int)$attendance_stats['present'] : 0; ?></span>
                        <span class="status-badge" style="background:#fef3c7; color:#92400e;">Late: <?php echo isset($attendance_stats['late']) ? (int)$attendance_stats['late'] : 0; ?></span>
                        <span class="status-badge status-inactive">Absent: <?php echo isset($attendance_stats['absent']) ? (int)$attendance_stats['absent'] : 0; ?></span>
                        <span class="status-badge" style="background:#e0f2fe; color:#075985;">Excused: <?php echo isset($attendance_stats['excused']) ? (int)$attendance_stats['excused'] : 0; ?></span>
                    </div>
                    <div class="info-table-card" style="margin-top:12px;">
                        <div class="info-table-header">Recent Attendance</div>
                        <div class="info-table-body">
                            <?php if (!empty($recent_attendance)): ?>
                                <?php foreach ($recent_attendance as $a): ?>
                                    <div class="info-row">
                                        <span><?php echo htmlspecialchars($a['class_name']); ?> · <?php echo htmlspecialchars($a['subject_name']); ?></span>
                                        <span><?php echo !empty($a['date']) ? htmlspecialchars(date('M j, Y', strtotime($a['date']))) : '-'; ?> · <?php echo htmlspecialchars(ucfirst($a['status'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data"><p>No attendance records.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
    </main>
</div>

<?php
// Build class options HTML
$classesOptionsHtml = '<option value="">Select class</option>';
foreach (($classes ?? []) as $c) {
    $label = ($c['class_name'] ?? 'Class') . ' · Grade ' . (int)($c['grade_level'] ?? 0) . ' · AY ' . htmlspecialchars($c['academic_year'] ?? '', ENT_QUOTES);
    $classesOptionsHtml .= '<option value="' . (int)$c['id'] . '">' . htmlspecialchars($label, ENT_QUOTES) . '</option>';
}

// Add Student Modal
$addStudentForm = '
<form id="addStudentForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">

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
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter email (optional)">
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" placeholder="Enter phone number">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="dob">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="admission_date">Admission Date</label>
            <input type="date" id="admission_date" name="admission_date">
        </div>
        <div class="form-group">
            <label for="previous_school">Previous School</label>
            <input type="text" id="previous_school" name="previous_school" placeholder="Enter previous school">
        </div>
    </div>

    <div class="form-group">
        <label for="class_id">Assign to Class</label>
        <select id="class_id" name="class_id">
            ' . $classesOptionsHtml . '
        </select>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="guardian_name">Guardian Name</label>
            <input type="text" id="guardian_name" name="guardian_name" placeholder="Enter guardian full name">
        </div>
        <div class="form-group">
            <label for="guardian_phone">Guardian Phone</label>
            <input type="tel" id="guardian_phone" name="guardian_phone" placeholder="Enter guardian phone">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="address_line">Address</label>
            <input type="text" id="address_line" name="address_line" placeholder="Street address">
        </div>
        <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" placeholder="City">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="state">State</label>
            <input type="text" id="state" name="state" placeholder="State">
        </div>
        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" id="country" name="country" placeholder="Country">
        </div>
    </div>

    <div class="form-group">
        <label for="emergency_contact">Emergency Contact</label>
        <input type="text" id="emergency_contact" name="emergency_contact" placeholder="Name and phone">
    </div>

    <div class="form-group">
        <label for="medical_notes">Medical Notes</label>
        <textarea id="medical_notes" name="medical_notes" rows="3" placeholder="Allergies, conditions, etc."></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required placeholder="Set initial password" minlength="8">
        </div>
        <div class="form-group">
            <label for="profile_image">Profile Image</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">
            <small class="form-help">Accepted: JPG, PNG, GIF, WEBP. Max 2MB.</small>
        </div>
    </div>
</form>';

renderFormModal('addStudentModal', 'Add New Student', $addStudentForm, 'Add Student', 'Cancel', [
    'size' => 'large',
    'onSubmit' => 'handleAddStudent',
    'formId' => 'addStudentForm'
]);

// Edit Student Modal
$editStudentForm = '
<form id="editStudentForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
    <input type="hidden" id="edit_id" name="id" value="">

    <div class="form-row">
        <div class="form-group">
            <label for="edit_first_name">First Name *</label>
            <input type="text" id="edit_first_name" name="first_name" required maxlength="50">
        </div>
        <div class="form-group">
            <label for="edit_last_name">Last Name *</label>
            <input type="text" id="edit_last_name" name="last_name" required maxlength="50">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="edit_email">Email</label>
            <input type="email" id="edit_email" name="email">
        </div>
        <div class="form-group">
            <label for="edit_phone">Phone</label>
            <input type="tel" id="edit_phone" name="phone">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="edit_gender">Gender</label>
            <select id="edit_gender" name="gender">
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label for="edit_dob">Date of Birth</label>
            <input type="date" id="edit_dob" name="dob">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="edit_admission_date">Admission Date</label>
            <input type="date" id="edit_admission_date" name="admission_date">
        </div>
        <div class="form-group">
            <label for="edit_previous_school">Previous School</label>
            <input type="text" id="edit_previous_school" name="previous_school">
        </div>
    </div>

    <div class="form-group">
        <label for="edit_class_id">Assign to Class</label>
        <select id="edit_class_id" name="class_id">
            ' . $classesOptionsHtml . '
        </select>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="edit_guardian_name">Guardian Name</label>
            <input type="text" id="edit_guardian_name" name="guardian_name">
        </div>
        <div class="form-group">
            <label for="edit_guardian_phone">Guardian Phone</label>
            <input type="tel" id="edit_guardian_phone" name="guardian_phone">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="edit_address_line">Address</label>
            <input type="text" id="edit_address_line" name="address_line">
        </div>
        <div class="form-group">
            <label for="edit_city">City</label>
            <input type="text" id="edit_city" name="city">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="edit_state">State</label>
            <input type="text" id="edit_state" name="state">
        </div>
        <div class="form-group">
            <label for="edit_country">Country</label>
            <input type="text" id="edit_country" name="country">
        </div>
    </div>

    <div class="form-group">
        <label for="edit_emergency_contact">Emergency Contact</label>
        <input type="text" id="edit_emergency_contact" name="emergency_contact">
    </div>

    <div class="form-group">
        <label for="edit_medical_notes">Medical Notes</label>
        <textarea id="edit_medical_notes" name="medical_notes" rows="3"></textarea>
    </div>

    <div class="form-group">
        <label for="edit_profile_image">Profile Image</label>
        <input type="file" id="edit_profile_image" name="profile_image" accept="image/*">
        <small class="form-help">Accepted: JPG, PNG, GIF, WEBP. Max 2MB.</small>
    </div>
</form>';

renderFormModal('editStudentModal', 'Edit Student', $editStudentForm, 'Save Changes', 'Cancel', [
    'size' => 'large',
    'onSubmit' => 'handleEditStudent',
    'formId' => 'editStudentForm'
]);

// Delete/Withdraw Modal
$deleteStudentForm = '
<form id="deleteStudentForm" method="POST" class="form" data-validate="false">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
    <input type="hidden" id="delete_id" name="id" value="">
    <p>Are you sure you want to withdraw this student? This will deactivate the account without deleting data.</p>
</form>';

renderFormModal('deleteStudentModal', 'Withdraw Student', $deleteStudentForm, 'Withdraw', 'Cancel', [
    'size' => 'small',
    'onSubmit' => 'handleDeleteStudent',
    'formId' => 'deleteStudentForm'
]);
?>

<script>
// Data map for quick prefilling (list view)
window.studentDataMap = window.studentDataMap || {};
<?php if ($action === 'list' && !empty($students)): ?>
window.studentDataMap = {
    <?php foreach ($students as $st): ?>
    <?php echo (int)$st['id']; ?>: <?php echo json_encode([
        'id' => (int)$st['id'],
        'first_name' => $st['first_name'] ?? '',
        'last_name' => $st['last_name'] ?? '',
        'email' => $st['email'] ?? '',
        'phone' => $st['phone'] ?? '',
        'gender' => $st['gender'] ?? '',
        'dob' => $st['dob'] ?? '',
        'admission_date' => $st['admission_date'] ?? '',
        'guardian_name' => $st['guardian_name'] ?? '',
        'guardian_phone' => $st['guardian_phone'] ?? '',
        'previous_school' => $st['previous_school'] ?? '',
        'address_line' => $st['address_line'] ?? ($st['address'] ?? ''),
        'city' => $st['city'] ?? '',
        'state' => $st['state'] ?? '',
        'country' => $st['country'] ?? '',
        'emergency_contact' => $st['emergency_contact'] ?? '',
        'medical_notes' => $st['medical_notes'] ?? '',
        'current_class_id' => isset($st['current_class_id']) ? (int)$st['current_class_id'] : null
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    <?php endforeach; ?>
};
<?php elseif ($action === 'view' && $view_student): ?>
window.studentDataMap[<?php echo (int)$view_student['id']; ?>] = <?php echo json_encode([
    'id' => (int)$view_student['id'],
    'first_name' => $view_student['first_name'] ?? '',
    'last_name' => $view_student['last_name'] ?? '',
    'email' => $view_student['email'] ?? '',
    'phone' => $view_student['phone'] ?? '',
    'gender' => $view_student['gender'] ?? '',
    'dob' => $view_student['dob'] ?? '',
    'admission_date' => $view_student['admission_date'] ?? '',
    'guardian_name' => $view_student['guardian_name'] ?? '',
    'guardian_phone' => $view_student['guardian_phone'] ?? '',
    'previous_school' => $view_student['previous_school'] ?? '',
    'address_line' => $view_student['address_line'] ?? ($view_student['address'] ?? ''),
    'city' => $view_student['city'] ?? '',
    'state' => $view_student['state'] ?? '',
    'country' => $view_student['country'] ?? '',
    'emergency_contact' => $view_student['emergency_contact'] ?? '',
    'medical_notes' => $view_student['medical_notes'] ?? '',
    'current_class_id' => isset($current_classes[0]['id']) ? (int)$current_classes[0]['id'] : null
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
<?php endif; ?>

function openAddStudentModal() {
    var form = document.getElementById('addStudentForm');
    if (form) form.reset();
    if (typeof window.openModalAddStudentModal === 'function') {
        window.openModalAddStudentModal();
        return;
    }
    var modal = document.getElementById('addStudentModal');
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        modal.focus();
    }
}

function handleAddStudent() {
    const form = document.getElementById('addStudentForm');
    if (form.checkValidity()) {
        form.submit();
    } else {
        form.reportValidity();
    }
}

function openEditStudentModal(id) {
    var data = (window.studentDataMap || {})[id];
    var form = document.getElementById('editStudentForm');
    if (!form) return;
    form.reset();
    if (data) {
        document.getElementById('edit_id').value = data.id || id;
        document.getElementById('edit_first_name').value = data.first_name || '';
        document.getElementById('edit_last_name').value = data.last_name || '';
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_phone').value = data.phone || '';
        document.getElementById('edit_gender').value = data.gender || '';
        document.getElementById('edit_dob').value = data.dob || '';
        document.getElementById('edit_admission_date').value = data.admission_date || '';
        document.getElementById('edit_guardian_name').value = data.guardian_name || '';
        document.getElementById('edit_guardian_phone').value = data.guardian_phone || '';
        document.getElementById('edit_previous_school').value = data.previous_school || '';
        document.getElementById('edit_address_line').value = data.address_line || '';
        document.getElementById('edit_city').value = data.city || '';
        document.getElementById('edit_state').value = data.state || '';
        document.getElementById('edit_country').value = data.country || '';
        document.getElementById('edit_emergency_contact').value = data.emergency_contact || '';
        document.getElementById('edit_medical_notes').value = data.medical_notes || '';
        var select = document.getElementById('edit_class_id');
        if (select) { select.value = (data.current_class_id || '').toString(); }
    } else {
        document.getElementById('edit_id').value = id;
    }
    if (typeof window.openModalEditStudentModal === 'function') {
        window.openModalEditStudentModal();
        return;
    }
    var modal = document.getElementById('editStudentModal');
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        modal.focus();
    }
}

function handleEditStudent() {
    const form = document.getElementById('editStudentForm');
    if (form.checkValidity()) {
        form.submit();
    } else {
        form.reportValidity();
    }
}

function openDeleteStudentModal(id) {
    var form = document.getElementById('deleteStudentForm');
    if (!form) return;
    document.getElementById('delete_id').value = id;
    if (typeof window.openModalDeleteStudentModal === 'function') {
        window.openModalDeleteStudentModal();
        return;
    }
    var modal = document.getElementById('deleteStudentModal');
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        modal.focus();
    }
}

function handleDeleteStudent() {
    const form = document.getElementById('deleteStudentForm');
    form.submit();
}

document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('studentSearchInput');
    var grid = document.getElementById('studentsGrid');
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

<?php include '../components/footer.php'; ?>

