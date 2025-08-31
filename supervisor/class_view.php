<?php
require_once '../config/config.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'supervisor') {
    redirect('../auth/login.php');
}

$supervisor_id = getCurrentUserId();
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($class_id <= 0) { redirect('./classes.php'); }

$page_title = 'Class Overview';
$class = null;
$students = [];
// Comma-separated teacher names
$teacher = null;
// Detailed teachers with subjects for cards
$teachers = [];
// Subjects assigned to class
$subjects = [];
// New aggregates per student
$student_avg_scores = [];
$student_attendance_rates = [];

try {
    $pdo = getDBConnection();

    // Access check: supervisor must be assigned to this class
    $stmt = $pdo->prepare("SELECT 1 FROM supervisor_assignments WHERE supervisor_id = ? AND class_id = ? AND is_active = 1");
    $stmt->execute([$supervisor_id, $class_id]);
    if (!$stmt->fetchColumn()) {
        redirect('./classes.php');
    }

    // Class details (without non-existent class_teacher_id)
    $stmt = $pdo->prepare("SELECT c.* FROM classes c WHERE c.id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();

    // Fetch subjects assigned to this class (read-only)
    $sstmt = $pdo->prepare("SELECT cs.id AS class_subject_id, s.id, s.subject_name, s.subject_code
                            FROM class_subjects cs
                            JOIN subjects s ON s.id = cs.subject_id
                            WHERE cs.class_id = ?
                            ORDER BY s.subject_name");
    $sstmt->execute([$class_id]);
    $subjects = $sstmt->fetchAll();

    // Fetch teacher(s) assigned to this class (detailed with subjects)
    $tstmt = $pdo->prepare("SELECT ta.id as assignment_id, u.id as teacher_id, u.first_name, u.last_name, u.username, u.profile_image,
                                   s.subject_name, s.subject_code
                            FROM teacher_assignments ta
                            JOIN users u ON u.id = ta.teacher_id
                            JOIN subjects s ON s.id = ta.subject_id
                            WHERE ta.class_id = ? AND ta.is_active = 1
                            ORDER BY u.first_name, u.last_name, s.subject_name");
    $tstmt->execute([$class_id]);
    $rows = $tstmt->fetchAll();
    $map = [];
    $tnames = [];
    foreach ($rows as $r) {
        $tid = (int)$r['teacher_id'];
        $nm = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        if ($nm !== '') { $tnames[$tid] = $nm; }
        if (!isset($map[$tid])) {
            $map[$tid] = [
                'teacher_id' => $tid,
                'name' => $nm,
                'username' => $r['username'] ?? '',
                'profile_image' => $r['profile_image'] ?? null,
                'subjects' => []
            ];
        }
        $label = $r['subject_name'] . (!empty($r['subject_code']) ? ' (' . $r['subject_code'] . ')' : '');
        $map[$tid]['subjects'][] = $label;
    }
    $teachers = array_values($map);
    if (!empty($tnames)) { $teacher = implode(', ', array_values($tnames)); }

    // Enrolled students
    $stmt = $pdo->prepare("SELECT u.id AS student_id, u.first_name, u.last_name, u.email
                           FROM student_enrollments se
                           JOIN users u ON se.student_id = u.id
                           WHERE se.class_id = ? AND se.status = 'active'");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();

    // Avg test percentage per student for this class (last 60 days)
    $stmt = $pdo->prepare("SELECT tr.student_id, ROUND(AVG(tr.percentage),2) AS avg_pct
                           FROM test_results tr
                           JOIN tests t ON tr.test_id = t.id
                           WHERE t.class_id = ? AND t.scheduled_date >= (CURDATE() - INTERVAL 60 DAY)
                           GROUP BY tr.student_id");
    $stmt->execute([$class_id]);
    $student_avg_scores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // student_id => avg_pct

    // Attendance rate per student for this class (last 30 days)
    $stmt = $pdo->prepare("SELECT a.student_id,
                                  SUM(a.status = 'present') AS present_cnt,
                                  COUNT(*) AS total_cnt
                           FROM attendance a
                           WHERE a.class_id = ? AND a.date >= (CURDATE() - INTERVAL 30 DAY)
                           GROUP BY a.student_id");
    $stmt->execute([$class_id]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $rate = ($r['total_cnt'] > 0) ? round(($r['present_cnt'] / $r['total_cnt']) * 100, 2) : 0;
        $student_attendance_rates[(int)$r['student_id']] = $rate;
    }

} catch (PDOException $e) {
    $error_message = 'Error loading class: ' . $e->getMessage();
}

include '../components/header.php';
?>
<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1><?php echo htmlspecialchars($class['class_name'] ?? ''); ?></h1>
                <div class="muted">Grade <?php echo (int)($class['grade_level'] ?? 0); ?> · AY <?php echo htmlspecialchars($class['academic_year'] ?? ''); ?> · Code: <span class="badge"><?php echo htmlspecialchars($class['class_code'] ?? ''); ?></span></div>
                <?php if ($teacher): ?><div class="muted">Teachers: <?php echo htmlspecialchars($teacher); ?></div><?php endif; ?>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <p class="no-data" style="color:#dc2626;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <div class="timetable-row">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Subjects (<?php echo count($subjects); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (!$subjects): ?>
                        <div class="empty-state">No subjects assigned</div>
                    <?php else: ?>
                        <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
                            <?php foreach ($subjects as $sub): ?>
                                <div class="profile-card">
                                    <div class="profile-main">
                                        <div class="profile-text">
                                            <div class="profile-title"><?php echo htmlspecialchars($sub['subject_name']); ?></div>
                                            <div class="profile-subtitle"><?php echo htmlspecialchars($sub['subject_code']); ?></div>
                                        </div>
                                    </div>
                                    <div class="profile-actions" style="padding: 12px; border-top: 1px solid var(--border-color); display:flex; justify-content:flex-end;">
                                        <a class="btn btn-sm btn-outline" href="./lessons.php?class_id=<?php echo (int)$class_id; ?>&subject_id=<?php echo (int)$sub['id']; ?>">
                                            <i class="fas fa-book-open"></i> View Lessons
                                        </a>
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
                </div>
                <div class="card-content">
                    <?php if (!$teachers): ?>
                        <div class="empty-state">No teachers assigned</div>
                    <?php else: ?>
                        <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                            <?php foreach ($teachers as $t): ?>
                                <div class="profile-card">
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
                    <h3><i class="fas fa-user-graduate"></i> Students (<?php echo count($students); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (!$students): ?>
                        <div class="empty-state">No active students enrolled</div>
                    <?php else: ?>
                        <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                            <?php foreach ($students as $st): $sid=(int)$st['student_id']; $avg = $student_avg_scores[$sid] ?? null; $rate = $student_attendance_rates[$sid] ?? null; ?>
                                <div class="profile-card">
                                    <div class="profile-main">
                                        <div class="profile-avatar">
                                            <div class="profile-initials"><?php echo htmlspecialchars(strtoupper(substr($st['first_name'],0,1) . substr($st['last_name'],0,1))); ?></div>
                                            <span class="profile-badge" title="Enrolled"></span>
                                        </div>
                                        <div class="profile-text">
                                            <div class="profile-title-row">
                                                <div class="profile-title"><?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?></div>
                                                <a class="btn btn-sm btn-outline" href="./student_view.php?student_id=<?php echo $sid; ?>&class_id=<?php echo (int)$class_id; ?>"><i class="fas fa-eye"></i> View</a>
                                            </div>
                                            <div class="profile-subtitle">Avg: <?php echo $avg!==null? number_format((float)$avg,2).'%' : 'N/A'; ?> · Att: <?php echo $rate!==null? number_format((float)$rate,2).'%' : 'N/A'; ?></div>
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
<?php include '../components/footer.php'; ?>
