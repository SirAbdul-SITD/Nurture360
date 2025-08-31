<?php
require_once '../config/config.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'supervisor') {
    redirect('../auth/login.php');
}

$supervisor_id = getCurrentUserId();
$page_title = 'My Profile';
$user = null;
$errors = [];
$success = null;
$csrf = generateCSRFToken();

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone, address, profile_image FROM users WHERE id = ? AND role = 'supervisor'");
    $stmt->execute([$supervisor_id]);
    $user = $stmt->fetch();
    if (!$user) { redirect('../auth/logout.php'); }

    // Resolve avatar URL (support legacy teacher folder fallback)
    $avatarUrl = null;
    if (!empty($user['profile_image'])) {
        $base = dirname(__DIR__);
        $fname = basename($user['profile_image']);
        $candUsers = $base . '/uploads/users/' . $fname;
        $candTeachers = $base . '/uploads/teachers/' . $fname;
        if (is_file($candUsers)) {
            $avatarUrl = '../uploads/users/' . rawurlencode($fname);
        } elseif (is_file($candTeachers)) {
            $avatarUrl = '../uploads/teachers/' . rawurlencode($fname);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid CSRF token.';
        }

        if (!$errors) {
            $first = trim($_POST['first_name'] ?? $user['first_name']);
            $last  = trim($_POST['last_name'] ?? $user['last_name']);
            $email = trim($_POST['email'] ?? $user['email']);
            $phone = trim($_POST['phone'] ?? ($user['phone'] ?? ''));
            $addr  = trim($_POST['address'] ?? ($user['address'] ?? ''));

            if ($first === '' || $last === '' || $email === '') {
                $errors[] = 'First name, last name and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format.';
            }

            // Handle avatar upload
            $avatarPath = $user['profile_image'];
            $uploadBase = dirname(__DIR__) . '/uploads/users';
            if (!is_dir($uploadBase)) { @mkdir($uploadBase, 0775, true); }

            if (isset($_POST['remove_avatar']) && !empty($avatarPath)) {
                $old = $uploadBase . '/' . basename($avatarPath);
                if (is_file($old)) { @unlink($old); }
                $avatarPath = null;
            }

            if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['name'])) {
                if (is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                    $name = $_FILES['avatar']['name'];
                    $tmp  = $_FILES['avatar']['tmp_name'];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif','webp'];
                    $allowedM = ['image/jpeg','image/png','image/gif','image/webp'];
                    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? @finfo_file($finfo, $tmp) : null;
                    if (!in_array($ext, $allowed, true) || ($mime && !in_array($mime, $allowedM, true))) {
                        $errors[] = 'Invalid avatar file type.';
                    } else {
                        $newName = 'avatar_' . $supervisor_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $dest = $uploadBase . '/' . $newName;
                        if (!@move_uploaded_file($tmp, $dest)) {
                            $errors[] = 'Failed to upload avatar.';
                        } else {
                            // Delete old
                            if (!empty($avatarPath)) {
                                $old = $uploadBase . '/' . basename($avatarPath);
                                if (is_file($old)) { @unlink($old); }
                            }
                            $avatarPath = $newName;
                        }
                    }
                }
            }

            if (!$errors) {
                try {
                    $pdo->beginTransaction();
                    $q = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, profile_image=?, updated_at=NOW() WHERE id=?");
                    $q->execute([$first, $last, $email, $phone, $addr, $avatarPath, $supervisor_id]);
                    $pdo->commit();

                    // Update session data for header
                    $_SESSION['first_name'] = $first;
                    $_SESSION['last_name'] = $last;
                    $_SESSION['email'] = $email;

                    logAction($supervisor_id, 'update_profile', 'Supervisor updated profile.');
                    $success = 'Profile updated successfully.';
                    // Refresh $user
                    $stmt->execute([$supervisor_id]);
                    $user = $stmt->fetch();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                        $errors[] = 'Email is already in use.';
                    } else {
                        $errors[] = 'Failed to update profile.';
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    $error_message = 'Error loading profile: ' . $e->getMessage();
}

include '../components/header.php';
?>
<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header"><h1>My Profile</h1></div>

        <?php if (!empty($error_message)): ?>
            <p class="no-data" style="color:#dc2626;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
          <script>
            (function(){
              const msg = <?php echo json_encode($success); ?>;
              (window.RindaApp && typeof window.RindaApp.showNotification === 'function')
                ? window.RindaApp.showNotification(msg, 'success')
                : (window.showNotification && window.showNotification(msg, 'success'));
            })();
          </script>
        <?php endif; ?>
        <?php if ($errors): ?>
          <script>
            (function(){
              const msg = <?php echo json_encode(implode("\n", $errors)); ?>;
              (window.RindaApp && typeof window.RindaApp.showNotification === 'function')
                ? window.RindaApp.showNotification(msg, 'error')
                : (window.showNotification && window.showNotification(msg, 'error'));
            })();
          </script>
        <?php endif; ?>

        <div class="card">
          <div class="card-content">
            <?php if ($user): ?>
            <form method="POST" enctype="multipart/form-data" class="form">
              <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
              <div style="display:flex; gap:24px; align-items:flex-start; flex-wrap:wrap;">
                <div>
                  <label>Avatar</label>
                  <div style="margin:8px 0;">
                    <?php if (!empty($avatarUrl)): ?>
                      <img src="<?php echo $avatarUrl; ?>" alt="Avatar" style="width:96px;height:96px;object-fit:cover;border-radius:50%;border:1px solid var(--border-color);">
                    <?php else: ?>
                      <div style="width:96px;height:96px;border-radius:50%;border:1px dashed var(--border-color);display:flex;align-items:center;justify-content:center;color:#888;">No Avatar</div>
                    <?php endif; ?>
                  </div>
                  <input type="file" name="avatar" accept="image/*">
                  <?php if (!empty($user['profile_image'])): ?>
                    <div style="margin-top:8px;">
                      <button type="submit" name="remove_avatar" value="1" class="btn btn-danger" onclick="return confirm('Remove current avatar?')">Remove Avatar</button>
                    </div>
                  <?php endif; ?>
                </div>

                <div style="flex:1; min-width:280px;">
                  <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required></div>
                  <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required></div>
                  <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                  <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
                  <div class="form-group"><label>Address</label><textarea name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></div>
                </div>
              </div>

              <div style="margin-top:12px">
                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Changes</button>
              </div>
            </form>
            <?php else: ?>
              <p class="no-data">Profile not found.</p>
            <?php endif; ?>
          </div>
        </div>
    </main>
</div>
<?php include '../components/footer.php'; ?>
