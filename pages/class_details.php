<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();

$classId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($classId <= 0) { redirect('classes.php'); }

// Load class info
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch();
if (!$class) { redirect('classes.php'); }

$page_title = 'Class Details — ' . $class['class_name'];

// Assigned subjects (via class_subjects)
$subjects = [];
try {
  $q = $pdo->prepare("SELECT cs.id AS class_subject_id, s.id, s.subject_name, s.subject_code, s.description
                      FROM class_subjects cs
                      JOIN subjects s ON s.id = cs.subject_id
                      WHERE cs.class_id = ?
                      ORDER BY s.subject_name");
  $q->execute([$classId]);
  $subjects = $q->fetchAll();
} catch (Throwable $e) {}

// Assigned supervisor(s) for this class
$supervisors = [];
try {
  $q = $pdo->prepare("SELECT sa.id as assignment_id, u.id as supervisor_id, u.first_name, u.last_name, u.username, u.profile_image, sa.academic_year
                       FROM supervisor_assignments sa
                       JOIN users u ON u.id = sa.supervisor_id
                       WHERE sa.class_id = ? AND sa.is_active = 1
                       ORDER BY sa.created_at DESC");
  $q->execute([$classId]);
  $rows = $q->fetchAll();
  foreach ($rows as $r) {
    $supervisors[] = [
      'assignment_id' => (int)$r['assignment_id'],
      'id' => (int)$r['supervisor_id'],
      'name' => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
      'username' => $r['username'] ?? '',
      'profile_image' => $r['profile_image'] ?? null,
      'academic_year' => $r['academic_year'] ?? ''
    ];
  }
} catch (Throwable $e) {}

// Assigned teachers (via teacher_assignments) for the class academic year
$teachers = [];
try {
  $q = $pdo->prepare("SELECT ta.id as assignment_id, u.id as teacher_id, u.first_name, u.last_name, u.username, u.profile_image,
                              s.subject_name, s.subject_code, ta.academic_year
                       FROM teacher_assignments ta
                       JOIN users u ON u.id = ta.teacher_id
                       JOIN subjects s ON s.id = ta.subject_id
                       WHERE ta.class_id = ? AND ta.is_active = 1
                       ORDER BY u.first_name, u.last_name, s.subject_name");
  $q->execute([$classId]);
  $rows = $q->fetchAll();
  // Group by teacher -> subjects
  $map = [];
  foreach ($rows as $r) {
    $tid = (int)$r['teacher_id'];
    if (!isset($map[$tid])) {
      $map[$tid] = [
        'teacher_id' => $tid,
        'assignment_id' => (int)$r['assignment_id'],
        'name' => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
        'username' => $r['username'] ?? '',
        'profile_image' => $r['profile_image'] ?? null,
        'subjects' => []
      ];
    }
    $map[$tid]['subjects'][] = $r['subject_name'] . (!empty($r['subject_code']) ? ' (' . $r['subject_code'] . ')' : '');
  }
  $teachers = array_values($map);
} catch (Throwable $e) {}

// Enrolled students (active) for this class
$students = [];
try {
  $q = $pdo->prepare("SELECT u.id as student_id, u.first_name, u.last_name, u.username, u.profile_image, se.status
                       FROM student_enrollments se
                       JOIN users u ON u.id = se.student_id
                       WHERE se.class_id = ? AND se.status = 'active'
                       ORDER BY u.first_name, u.last_name");
  $q->execute([$classId]);
  $students = $q->fetchAll();
} catch (Throwable $e) {}

include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars($class['class_name']); ?></h1>
        <div class="muted">Grade <?php echo (int)$class['grade_level']; ?> · AY <?php echo htmlspecialchars($class['academic_year']); ?> · Code: <span class="badge"><?php echo htmlspecialchars($class['class_code']); ?></span></div>
      </div>

    <script>
    document.addEventListener('click', async (e) => {
      const open = e.target.closest('[data-open-modal]');
      if (open) {
        e.preventDefault();
        const id = open.getAttribute('data-open-modal');
        const fn = window['openModal' + id.charAt(0).toUpperCase() + id.slice(1)];
        if (typeof fn === 'function') {
          fn();
        } else {
          const m = document.getElementById(id);
          if (m) { m.classList.add('show','active'); document.body.classList.add('modal-open'); }
        }
        return;
      }
      const btn = e.target.closest('.card-remove');
      if (!btn) return;
      e.preventDefault();
      // Store the button for confirmation handler and open confirm modal
      window.pendingRemoveBtn = btn;
      if (typeof window.openModalRemoveConfirm === 'function') {
        window.openModalRemoveConfirm();
      } else {
        const m = document.getElementById('removeConfirm');
        if (m) { m.classList.add('show','active'); document.body.classList.add('modal-open'); }
      }
    });

    // Confirm modal handler (wired via renderConfirmModal onConfirm)
    window.doRemoveConfirmAction = async function() {
      const btn = window.pendingRemoveBtn;
      window.pendingRemoveBtn = null;
      if (!btn) return;
      btn.disabled = true;
      const type = btn.dataset.type;
      try {
        let url = '';
        let form = new FormData();
        if (type === 'teacher') {
          url = '../api/remove_teacher_assignment.php';
          form.append('assignment_id', btn.dataset.assignmentId);
        } else if (type === 'supervisor') {
          url = '../api/remove_supervisor_assignment.php';
          form.append('assignment_id', btn.dataset.assignmentId);
        } else if (type === 'subject') {
          url = '../api/remove_class_subject.php';
          form.append('class_subject_id', btn.dataset.classSubjectId);
        }
        const res = await fetch(url, { method: 'POST', body: form, credentials: 'same-origin' });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.error) throw new Error(data.error || 'Request failed');
        const card = btn.closest('.profile-card');
        if (card) card.remove();
        if (typeof showNotification==='function') showNotification('Removed successfully','success');
      } catch (err) {
        if (typeof showNotification==='function') showNotification('Failed to remove: ' + err.message, 'error');
        btn.disabled = false;
        return;
      }
      // Close the confirm modal
      if (typeof window.closeModalRemoveConfirm === 'function') { window.closeModalRemoveConfirm(); }
      else { const m=document.getElementById('removeConfirm'); if(m){ m.classList.remove('show','active'); document.body.classList.remove('modal-open'); } }
    };
    </script>
      <div class="header-actions">
        <a class="btn btn-primary" href="classes.php"><i class="fas fa-arrow-left"></i> Back to Classes</a>
      </div>
    </div>

    <div class="timetable-row">
      <div class="content-card">
        <div class="card-header">
          <h3><i class="fas fa-book"></i> Subjects (<?php echo count($subjects); ?>)</h3>
          <div class="header-actions">
            <button class="btn btn-sm btn-primary" type="button" data-open-modal="addSubject"><i class="fas fa-plus"></i> Add</button>
          </div>
        </div>
        <div class="card-content">
          <?php if (!$subjects): ?>
            <div class="empty-state">No subjects assigned</div>
          <?php else: ?>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
              <?php foreach ($subjects as $sub): ?>
                <div class="profile-card">
                  <button class="card-remove" title="Remove subject" data-type="subject" data-class-subject-id="<?php echo (int)$sub['class_subject_id']; ?>">
                    <i class="fas fa-times"></i>
                  </button>
                  <div class="profile-main">
                    <div class="profile-text">
                      <div class="profile-title"><?php echo htmlspecialchars($sub['subject_name']); ?></div>
                      <div class="profile-subtitle"><?php echo htmlspecialchars($sub['subject_code']); ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="content-card">
        <div class="card-header">
          <h3><i class="fas fa-chalkboard-teacher"></i> Teachers (<?php echo count($teachers); ?>)</h3>
          <div class="header-actions">
            <button class="btn btn-sm btn-primary" type="button" data-open-modal="addTeacher"><i class="fas fa-plus"></i> Add</button>
          </div>
        </div>
        <div class="card-content">
          <?php if (!$teachers): ?>
            <div class="empty-state">No teachers assigned</div>
          <?php else: ?>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
              <?php foreach ($teachers as $t): ?>
                <div class="profile-card">
                  <button class="card-remove" title="Remove teacher" data-type="teacher" data-assignment-id="<?php echo (int)$t['assignment_id']; ?>">
                    <i class="fas fa-times"></i>
                  </button>
                  <div class="profile-main">
                    <div class="profile-avatar">
                      <?php if (!empty($t['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($t['profile_image']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>">
                      <?php else: ?>
                        <div class="profile-initials"><?php echo htmlspecialchars(strtoupper(substr($t['name'],0,2))); ?></div>
                      <?php endif; ?>
                      <span class="profile-badge" title="Assigned"></span>
                    </div>
                    <div class="profile-text">
                      <div class="profile-title"><?php echo htmlspecialchars($t['name']); ?></div>
                      <div class="profile-subtitle"><?php echo htmlspecialchars(implode(', ', $t['subjects'])); ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="content-grid">
      <div class="content-card compact">
        <div class="card-header">
          <h3><i class="fas fa-user-shield"></i> Supervisor (<?php echo count($supervisors); ?>)</h3>
          <div class="header-actions">
            <button class="btn btn-sm btn-primary" type="button" data-open-modal="addSupervisor"><i class="fas fa-plus"></i> Add</button>
          </div>
        </div>
        <div class="card-content">
          <?php if (!$supervisors): ?>
            <div class="empty-state">No supervisor assigned</div>
          <?php else: ?>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
              <?php foreach ($supervisors as $s): ?>
                <div class="profile-card">
                  <button class="card-remove" title="Remove supervisor" data-type="supervisor" data-assignment-id="<?php echo (int)$s['assignment_id']; ?>">
                    <i class="fas fa-times"></i>
                  </button>
                  <div class="profile-main">
                    <div class="profile-avatar">
                      <?php if (!empty($s['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($s['profile_image']); ?>" alt="<?php echo htmlspecialchars($s['name']); ?>">
                      <?php else: ?>
                        <div class="profile-initials"><?php echo htmlspecialchars(strtoupper(substr($s['name'],0,2))); ?></div>
                      <?php endif; ?>
                      <span class="profile-badge" title="Supervisor"></span>
                    </div>
                    <div class="profile-text">
                      <div class="profile-title"><?php echo htmlspecialchars($s['name']); ?></div>
                      <div class="profile-subtitle">@<?php echo htmlspecialchars($s['username']); ?><?php echo $s['academic_year'] ? ' · AY ' . htmlspecialchars($s['academic_year']) : ''; ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="content-card compact">
        <div class="card-header">
          <h3><i class="fas fa-user-graduate"></i> Students (<?php echo count($students); ?>)</h3>
          <div class="header-actions">
            <button class="btn btn-sm btn-primary" type="button" data-open-modal="enrollStudent"><i class="fas fa-user-plus"></i> Enroll Student</button>
          </div>
        </div>
        <div class="card-content">
          <?php if (!$students): ?>
            <div class="empty-state">No active students enrolled</div>
          <?php else: ?>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
              <?php foreach ($students as $st): ?>
                <div class="profile-card">
                  <div class="profile-main">
                    <div class="profile-avatar">
                      <?php if (!empty($st['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($st['profile_image']); ?>" alt="<?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?>">
                      <?php else: ?>
                        <div class="profile-initials"><?php echo htmlspecialchars(strtoupper(substr($st['first_name'],0,1) . substr($st['last_name'],0,1))); ?></div>
                      <?php endif; ?>
                      <span class="profile-badge" title="Enrolled"></span>
                    </div>
                    <div class="profile-text">
                      <div class="profile-title-row">
                        <div class="profile-title"><?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?></div>
                        <button class="btn btn-sm btn-primary btn-promote-student" type="button"
                                data-student-id="<?php echo (int)$st['student_id']; ?>"
                                data-student-name="<?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?>">
                          <i class="fas fa-level-up-alt"></i> Promote
                        </button>
                      </div>
                      <div class="profile-subtitle">@<?php echo htmlspecialchars($st['username']); ?></div>
                    </div>
                  </div>
                  
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
  // Render confirmation modal for removals
  include_once '../components/modal.php';
  renderConfirmModal(
    'removeConfirm',
    'Remove Assignment',
    'Are you sure you want to remove this assignment? This action cannot be undone.',
    'Remove',
    'Cancel',
    [ 'type' => 'danger', 'confirmClass' => 'btn-danger', 'cancelClass' => 'btn-outline', 'onConfirm' => 'doRemoveConfirmAction' ]
  );

  // Prepare data for Add modals
  try {
    // Subjects not yet assigned to this class
    $stmt = $pdo->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE is_active = 1 AND id NOT IN (SELECT subject_id FROM class_subjects WHERE class_id = ?)");
    $stmt->execute([$classId]);
    $availableSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $availableSubjects = []; }

  try {
    $stmt = $pdo->prepare(
      "SELECT id, first_name, last_name, username
       FROM users
       WHERE role = 'teacher' AND is_active = 1
         AND id NOT IN (
           SELECT teacher_id FROM teacher_assignments
           WHERE class_id = ? AND academic_year = ? AND is_active = 1
         )
       ORDER BY first_name, last_name"
    );
    $stmt->execute([$classId, $class['academic_year']]);
    $allTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $allTeachers = []; }

  try {
    $stmt = $pdo->prepare(
      "SELECT id, first_name, last_name, username
       FROM users
       WHERE role = 'supervisor' AND is_active = 1
         AND id NOT IN (
           SELECT supervisor_id FROM supervisor_assignments
           WHERE class_id = ? AND academic_year = ? AND is_active = 1
         )
       ORDER BY first_name, last_name"
    );
    $stmt->execute([$classId, $class['academic_year']]);
    $allSupervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $allSupervisors = []; }

  // Reuse subjects list for teacher modal as well (use all active subjects)
  try {
    $stmt = $pdo->query("SELECT id, subject_name, subject_code FROM subjects WHERE is_active = 1 ORDER BY subject_name");
    $allSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $allSubjects = []; }

  // Students not yet enrolled in this class (active only)
  try {
    $stmt = $pdo->prepare(
      "SELECT id, first_name, last_name, username
       FROM users
       WHERE role='student' AND is_active = 1
         AND id NOT IN (
           SELECT student_id FROM student_enrollments
           WHERE class_id = ? AND status = 'active'
         )
       ORDER BY first_name, last_name"
    );
    $stmt->execute([$classId]);
    $availableStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $availableStudents = []; }

  // Target classes for promotion (all active classes except current; ordered by grade)
  try {
    $stmt = $pdo->prepare(
      "SELECT id, class_name, grade_level, academic_year
       FROM classes
       WHERE is_active = 1 AND id <> ?
       ORDER BY grade_level, class_name"
    );
    $stmt->execute([$classId]);
    $targetClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $targetClasses = []; }

  // Build map of eligible target classes per student (exclude any class where student already active)
  $studentEligibleTargets = [];
  if (!empty($students) && !empty($targetClasses)) {
    try {
      // Get all current student IDs listed on the page
      $ids = array_map(function($s){ return (int)$s['student_id']; }, $students);
      $in = implode(',', array_fill(0, count($ids), '?'));
      $stmt = $pdo->prepare("SELECT student_id, class_id FROM student_enrollments WHERE status='active' AND student_id IN ($in)");
      $stmt->execute($ids);
      $activeByStudent = [];
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sid = (int)$row['student_id'];
        $cid = (int)$row['class_id'];
        if (!isset($activeByStudent[$sid])) $activeByStudent[$sid] = [];
        $activeByStudent[$sid][$cid] = true;
      }
      foreach ($students as $s) {
        $sid = (int)$s['student_id'];
        $eligible = [];
        foreach ($targetClasses as $c) {
          $tcid = (int)$c['id'];
          if (empty($activeByStudent[$sid][$tcid])) {
            $eligible[] = $c;
          }
        }
        $studentEligibleTargets[$sid] = $eligible;
      }
    } catch (Throwable $e) {
      // fallback: allow all target classes
      foreach ($students as $s) { $studentEligibleTargets[(int)$s['student_id']] = $targetClasses; }
    }
  }

  // Build Add Subject form
  ob_start();
?>
  <form id="addSubjectForm" class="form">
    <input type="hidden" name="class_id" value="<?php echo (int)$classId; ?>">
    <div class="form-group">
      <label for="subject_id">Subject</label>
      <select id="subject_id" name="subject_id" required>
        <option value="">Select subject</option>
        <?php foreach ($availableSubjects as $s): ?>
          <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['subject_name'] . (!empty($s['subject_code']) ? ' (' . $s['subject_code'] . ')' : '')); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
<?php
  $addSubjectForm = ob_get_clean();
  renderFormModal('addSubject', 'Add Subject to Class', $addSubjectForm, 'Add Subject', 'Cancel', [ 'size' => 'medium' ]);

  // Build Add Teacher form
  ob_start();
?>
  <form id="addTeacherForm" class="form">
    <input type="hidden" name="class_id" value="<?php echo (int)$classId; ?>">
    <div class="form-group">
      <label for="teacher_id">Teacher</label>
      <select id="teacher_id" name="teacher_id" required>
        <option value="">Select teacher</option>
        <?php foreach ($allTeachers as $t): $nm = trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')); ?>
          <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($nm . ' @' . $t['username']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="teacher_subject_id">Subject</label>
      <select id="teacher_subject_id" name="subject_id" required>
        <option value="">Select subject</option>
        <?php foreach ($allSubjects as $s): ?>
          <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['subject_name'] . (!empty($s['subject_code']) ? ' (' . $s['subject_code'] . ')' : '')); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="teacher_academic_year">Academic Year</label>
      <input id="teacher_academic_year" name="academic_year" type="text" value="<?php echo htmlspecialchars($class['academic_year']); ?>" required />
    </div>
  </form>
<?php
  $addTeacherForm = ob_get_clean();
  renderFormModal('addTeacher', 'Assign Teacher to Class', $addTeacherForm, 'Assign Teacher', 'Cancel', [ 'size' => 'large' ]);

  // Build Add Supervisor form
  ob_start();
?>
  <form id="addSupervisorForm" class="form">
    <input type="hidden" name="class_id" value="<?php echo (int)$classId; ?>">
    <div class="form-group">
      <label for="supervisor_id">Supervisor</label>
      <select id="supervisor_id" name="supervisor_id" required>
        <option value="">Select supervisor</option>
        <?php foreach ($allSupervisors as $sp): $nm = trim(($sp['first_name'] ?? '') . ' ' . ($sp['last_name'] ?? '')); ?>
          <option value="<?php echo (int)$sp['id']; ?>"><?php echo htmlspecialchars($nm . ' @' . $sp['username']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="supervisor_academic_year">Academic Year</label>
      <input id="supervisor_academic_year" name="academic_year" type="text" value="<?php echo htmlspecialchars($class['academic_year']); ?>" required />
    </div>
  </form>
<?php
  $addSupervisorForm = ob_get_clean();
  renderFormModal('addSupervisor', 'Assign Supervisor to Class', $addSupervisorForm, 'Assign Supervisor', 'Cancel', [ 'size' => 'medium' ]);

  // Build Promote Student form
  ob_start();
?>
  <form id="promoteStudentForm" class="form">
    <input type="hidden" name="student_id" id="promote_student_id" value="">
    <input type="hidden" name="from_class_id" value="<?php echo (int)$classId; ?>">
    <div class="form-group">
      <label>Student</label>
      <div id="promote_student_name" class="muted">Select a student to promote</div>
    </div>
    <div class="form-group">
      <label for="to_class_id">Promote to Class</label>
      <select id="to_class_id" name="to_class_id" required>
        <option value="">Select class</option>
        <?php foreach ($targetClasses as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' · Grade ' . $c['grade_level'] . ' · AY ' . $c['academic_year']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="promote_academic_year">Academic Year</label>
      <input id="promote_academic_year" name="academic_year" type="text" value="<?php echo htmlspecialchars($class['academic_year']); ?>" required />
    </div>
  </form>
<?php
  $promoteStudentForm = ob_get_clean();
  renderFormModal('promoteStudent', 'Promote Student', $promoteStudentForm, 'Promote', 'Cancel', [ 'size' => 'medium' ]);

  // Build Enroll Student form
  ob_start();
?>
  <form id="enrollStudentForm" class="form">
    <input type="hidden" name="class_id" value="<?php echo (int)$classId; ?>">
    <div class="form-group">
      <label for="enroll_student_id">Student</label>
      <select id="enroll_student_id" name="student_id" required>
        <option value="">Select student</option>
        <?php foreach ($availableStudents as $s): $nm = trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?>
          <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($nm . ' @' . $s['username']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="enroll_academic_year">Academic Year</label>
      <input id="enroll_academic_year" name="academic_year" type="text" value="<?php echo htmlspecialchars($class['academic_year']); ?>" required />
    </div>
  </form>
<?php
  $enrollStudentForm = ob_get_clean();
  renderFormModal('enrollStudent', 'Enroll Student', $enrollStudentForm, 'Enroll', 'Cancel', [ 'size' => 'medium' ]);
?>

<script>
// Submit handlers for add modals
document.addEventListener('DOMContentLoaded', function(){
  const submitForm = async (formEl, url, onSuccess) => {
    if (!formEl) return;
    formEl.addEventListener('submit', async function(e){
      e.preventDefault();
      const btn = formEl.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;
      try {
        const res = await fetch(url, { method: 'POST', body: new FormData(formEl), credentials: 'same-origin' });
        const data = await res.json().catch(()=>({}));
        if (!res.ok || data.error) throw new Error(data.error || 'Request failed');
        onSuccess && onSuccess(data);
      } catch(err){
        if (typeof showNotification==='function') showNotification('Failed to submit: ' + err.message, 'error');
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  };

  submitForm(document.getElementById('addSubjectForm'), '../api/assign_class_subject.php', function(){
    if (typeof window.closeModalAddSubject === 'function') window.closeModalAddSubject();
    else { const m=document.getElementById('addSubject'); if(m){ m.classList.remove('show'); document.body.classList.remove('modal-open'); } }
    if (typeof showNotification==='function') showNotification('Subject added to class','success');
    setTimeout(()=>window.location.reload(), 600);
  });

  submitForm(document.getElementById('addTeacherForm'), '../api/assign_teacher_to_class.php', function(){
    if (typeof window.closeModalAddTeacher === 'function') window.closeModalAddTeacher();
    else { const m=document.getElementById('addTeacher'); if(m){ m.classList.remove('show'); document.body.classList.remove('modal-open'); } }
    if (typeof showNotification==='function') showNotification('Teacher assigned to class','success');
    setTimeout(()=>window.location.reload(), 600);
  });

  submitForm(document.getElementById('addSupervisorForm'), '../api/assign_supervisor_to_class.php', function(){
    if (typeof window.closeModalAddSupervisor === 'function') window.closeModalAddSupervisor();
    else { const m=document.getElementById('addSupervisor'); if(m){ m.classList.remove('show'); document.body.classList.remove('modal-open'); } }
    if (typeof showNotification==='function') showNotification('Supervisor assigned to class','success');
    setTimeout(()=>window.location.reload(), 600);
  });

  // Wire promote buttons
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-promote-student');
    if (!btn) return;
    e.preventDefault();
    const sid = btn.getAttribute('data-student-id');
    const sname = btn.getAttribute('data-student-name');
    const nameEl = document.getElementById('promote_student_name');
    const hid = document.getElementById('promote_student_id');
    // Populate target class options based on eligibility
    try {
      const map = window.studentEligibleTargets || {};
      const list = map[sid] || [];
      const sel = document.getElementById('to_class_id');
      if (sel) {
        // reset options
        sel.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = 'Select class';
        sel.appendChild(opt0);
        list.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = `${c.class_name} · Grade ${c.grade_level} · AY ${c.academic_year}`;
          sel.appendChild(opt);
        });
      }
    } catch(err) {
      // ignore population errors; backend will validate
    }
    if (nameEl) nameEl.textContent = sname || 'Selected student';
    if (hid) hid.value = sid || '';
    if (typeof window.openModalPromoteStudent === 'function') window.openModalPromoteStudent();
    else { const m=document.getElementById('promoteStudent'); if(m){ m.classList.add('show'); document.body.classList.add('modal-open'); } }
  });

  // Submit promote and enroll forms
  submitForm(document.getElementById('promoteStudentForm'), '../api/promote_student.php', function(){
    if (typeof window.closeModalPromoteStudent === 'function') window.closeModalPromoteStudent();
    else { const m=document.getElementById('promoteStudent'); if(m){ m.classList.remove('show'); document.body.classList.remove('modal-open'); } }
    if (typeof showNotification==='function') showNotification('Student promoted successfully','success');
    setTimeout(()=>window.location.reload(), 600);
  });

  submitForm(document.getElementById('enrollStudentForm'), '../api/enroll_student.php', function(){
    if (typeof window.closeModalEnrollStudent === 'function') window.closeModalEnrollStudent();
    else { const m=document.getElementById('enrollStudent'); if(m){ m.classList.remove('show'); document.body.classList.remove('modal-open'); } }
    if (typeof showNotification==='function') showNotification('Student enrolled successfully','success');
    setTimeout(()=>window.location.reload(), 600);
  });
});
</script>

<script>
// Eligibility map injected from PHP
window.studentEligibleTargets = <?php echo json_encode(array_map(function($list){
  return array_map(function($c){ return [
    'id'=>(int)$c['id'],
    'class_name'=>$c['class_name'],
    'grade_level'=>(int)$c['grade_level'],
    'academic_year'=>$c['academic_year']
  ]; }, $list);
}, $studentEligibleTargets), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
</script>

<?php include '../components/footer.php'; ?>
