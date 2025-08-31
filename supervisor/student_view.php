<?php
require_once '../config/config.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'supervisor') {
    redirect('../auth/login.php');
}

$supervisor_id = getCurrentUserId();
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($student_id <= 0 || $class_id <= 0) { redirect('./classes.php'); }

$page_title = 'Student Overview';
$student = null;
$attendance_summary = [];
$test_results = [];

try {
    $pdo = getDBConnection();

    // Access check: supervisor must be assigned to this class
    $stmt = $pdo->prepare("SELECT 1 FROM supervisor_assignments WHERE supervisor_id = ? AND class_id = ? AND is_active = 1");
    $stmt->execute([$supervisor_id, $class_id]);
    if (!$stmt->fetchColumn()) {
        redirect('./classes.php');
    }

    // Student basics
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, address, profile_image FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    if (!$student) { redirect('./class_view.php?class_id=' . $class_id); }

    // Attendance summary for this class
    $stmt = $pdo->prepare("SELECT 
                                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_days,
                                SUM(CASE WHEN status = 'absent'  THEN 1 ELSE 0 END) AS absent_days,
                                SUM(CASE WHEN status = 'late'    THEN 1 ELSE 0 END) AS late_days,
                                COUNT(*) AS total_days
                           FROM attendance
                           WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);
    $attendance_summary = $stmt->fetch() ?: ['present_days'=>0,'absent_days'=>0,'late_days'=>0,'total_days'=>0];

    // Recent test results for this student (in any class; you can filter by class if needed)
    $stmt = $pdo->prepare("SELECT tr.id, t.title, t.test_type, tr.obtained_marks, tr.total_marks, tr.graded_at
                           FROM test_results tr
                           JOIN tests t ON tr.test_id = t.id
                           WHERE tr.student_id = ?
                           ORDER BY tr.graded_at DESC
                           LIMIT 20");
    $stmt->execute([$student_id]);
    $test_results = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'Error loading student: ' . $e->getMessage();
}

include '../components/header.php';
?>
<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Student: <?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></h1>
            <p>Class ID: <?php echo (int)$class_id; ?></p>
        </div>

        <?php if (!empty($error_message)): ?>
            <p class="no-data" style="color:#dc2626;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <div class="charts-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start;">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-id-card"></i> Basic Information</h3>
                </div>
                <div class="card-content">
                    <div style="display:flex; gap:16px; align-items:center;">
                        <div style="width:64px; height:64px; border-radius:50%; background:#e5e7eb; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            <?php if (!empty($student['profile_image'])): ?>
                                <img src="<?php echo '../uploads/students/' . htmlspecialchars($student['profile_image']); ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-user" style="color:#6b7280;"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></div>
                            <div><?php echo htmlspecialchars($student['email']); ?></div>
                            <div><?php echo htmlspecialchars($student['phone'] ?? ''); ?></div>
                            <div style="color:#6b7280; max-width:520px;">Address: <?php echo htmlspecialchars($student['address'] ?? ''); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Attendance Summary</h3>
                </div>
                <div class="card-content">
                    <ul style="display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:12px;">
                        <li><strong>Present:</strong> <?php echo (int)$attendance_summary['present_days']; ?></li>
                        <li><strong>Absent:</strong> <?php echo (int)$attendance_summary['absent_days']; ?></li>
                        <li><strong>Late:</strong> <?php echo (int)$attendance_summary['late_days']; ?></li>
                        <li><strong>Total Days:</strong> <?php echo (int)$attendance_summary['total_days']; ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="content-card" style="margin-top:16px;">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Recent Test Results</h3>
            </div>
            <div class="card-content">
                <?php if (empty($test_results)): ?>
                    <p class="no-data">No results available.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Type</th>
                                    <th>Score</th>
                                    <th>Graded At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_results as $tr): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tr['title']); ?></td>
                                        <td><?php echo htmlspecialchars($tr['test_type']); ?></td>
                                        <td>
                                            <?php echo (int)$tr['obtained_marks'] . ' / ' . (int)$tr['total_marks']; ?>
                                            (<?php echo $tr['total_marks'] > 0 ? round(($tr['obtained_marks'] / $tr['total_marks']) * 100) : 0; ?>%)
                                        </td>
                                        <td><?php echo $tr['graded_at'] ? date('M j, Y g:i A', strtotime($tr['graded_at'])) : '-'; ?></td>
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
<?php include '../components/footer.php'; ?>
