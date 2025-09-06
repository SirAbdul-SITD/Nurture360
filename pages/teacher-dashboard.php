<?php
require_once '../config/config.php';

// Guard: only logged-in teachers
if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'teacher') {
    redirect('../auth/login.php');
}

$teacher_id = getCurrentUserId();
$page_title = 'My Teaching Dashboard';

// Data containers
$assigned_classes = [];
$todays_schedule = [];
$upcoming_virtual_classes = [];
$recent_content_pages = [];
$recent_teacher_announcements = [];
$total_students = 0;
$total_teacher_assignments = 0;

try {
    $pdo = getDBConnection();

    // Assigned classes & subjects
    $stmt = $pdo->prepare("SELECT ta.id, c.class_name, c.class_code, c.grade_level, s.title AS subject_name, ta.academic_year
                           FROM teacher_assignments ta
                           JOIN classes c ON ta.class_id = c.id
                           JOIN subjects s ON ta.subject_id = s.subject_id
                           WHERE ta.teacher_id = ? AND ta.is_active = 1
                           ORDER BY c.grade_level, c.class_name, s.title");
    $stmt->execute([$teacher_id]);
    $assigned_classes = $stmt->fetchAll();

    // Today's schedule from timetable
    $dayOfWeek = strtolower(date('l')); // monday..sunday
    $stmt = $pdo->prepare("SELECT t.id, c.class_name, s.title AS subject_name, t.start_time, t.end_time, t.room_number
                           FROM timetable t
                           JOIN classes c ON t.class_id = c.id
                           JOIN subjects s ON t.subject_id = s.subject_id
                           WHERE t.teacher_id = ? AND t.day_of_week = ? AND t.is_active = 1
                           ORDER BY t.start_time");
    $stmt->execute([$teacher_id, $dayOfWeek]);
    $todays_schedule = $stmt->fetchAll();

    // Totals: students in teacher's assigned classes (active enrollments)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT se.student_id)
                           FROM student_enrollments se
                           JOIN teacher_assignments ta ON ta.class_id = se.class_id AND ta.is_active = 1
                           WHERE ta.teacher_id = ? AND se.status = 'active'");
    $stmt->execute([$teacher_id]);
    $total_students = (int)$stmt->fetchColumn();

    // Totals: assignments created by this teacher
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $total_teacher_assignments = (int)$stmt->fetchColumn();

    // (Removed) pending grading queries: not needed for teacher dashboard now

    // Upcoming virtual classes
    $stmt = $pdo->prepare("SELECT vc.id, vc.title, c.class_name, s.title AS subject_name, vc.scheduled_date, vc.start_time, vc.end_time, vc.meeting_link
                           FROM virtual_classes vc
                           JOIN classes c ON vc.class_id = c.id
                           JOIN subjects s ON vc.subject_id = s.subject_id
                           WHERE vc.teacher_id = ? AND vc.is_active = 1 AND vc.scheduled_date >= CURDATE()
                           ORDER BY vc.scheduled_date, vc.start_time
                           LIMIT 5");
    $stmt->execute([$teacher_id]);
    $upcoming_virtual_classes = $stmt->fetchAll();

    // Recent content pages created by teacher
    $stmt = $pdo->prepare("SELECT cp.id, cp.title, cp.updated_at, c.class_name, s.title AS subject_name, cp.is_published
                           FROM content_pages cp
                           LEFT JOIN classes c ON cp.class_id = c.id
                           LEFT JOIN subjects s ON cp.subject_id = s.subject_id
                           WHERE cp.teacher_id = ?
                           ORDER BY cp.updated_at DESC
                           LIMIT 5");
    $stmt->execute([$teacher_id]);
    $recent_content_pages = $stmt->fetchAll();

    // Recent announcements for teachers (only 'teachers' or 'all')
    $stmt = $pdo->prepare("SELECT a.id, a.title, a.created_at, a.content
                           FROM announcements a
                           WHERE a.is_active = 1 AND a.target_audience IN ('teachers','all')
                           ORDER BY a.created_at DESC
                           LIMIT 5");
    $stmt->execute();
    $recent_teacher_announcements = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'Error loading teacher dashboard: ' . $e->getMessage();
}

include '../components/header.php';
?>

<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1>My Teaching Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.RindaApp && typeof window.RindaApp.showNotification === 'function') {
                        window.RindaApp.showNotification(<?php echo json_encode($error_message); ?>, 'error');
                    }
                });
            </script>
        <?php endif; ?>

        <!-- Quick Actions -->
        <!-- <div class="actions-row" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
            <a class="btn btn-primary" href="../pages/content_pages.php"><i class="fas fa-file-alt"></i> Create Content</a>
            <a class="btn btn-secondary" href="../pages/assignments.php"><i class="fas fa-tasks"></i> New Assignment</a>
            <a class="btn btn-secondary" href="../pages/tests.php"><i class="fas fa-question-circle"></i> New Test/Quiz</a>
            <a class="btn btn-secondary" href="../pages/virtual-classes.php"><i class="fas fa-video"></i> Start Virtual Class</a>
            <a class="btn" href="../pages/resources.php"><i class="fas fa-file"></i> Upload Resource</a>
        </div> -->

        <!-- Overview Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-school"></i></div>
                <div class="stat-content">
                    <h3><?php echo count($assigned_classes); ?></h3>
                    <p>Assigned Classes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-content">
                    <h3><?php echo count($todays_schedule); ?></h3>
                    <p>Classes Today</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-content">
                    <h3><?php echo (int)$total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-content">
                    <h3><?php echo (int)$total_teacher_assignments; ?></h3>
                    <p>Total Assignments</p>
                </div>
            </div>
        </div>

        <!-- Two-column layout -->
        <div class="charts-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start;">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-wave-square"></i> Assignments Analytics</h3>
                </div>
                <div class="card-content">
                    <div style="height:280px;">
                        <canvas id="assignmentChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-bullhorn"></i> Recent Announcements</h3>
                    <a href="../pages/announcements.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="card-content">
                    <?php if (!empty($recent_teacher_announcements)): ?>
                        <div class="list announce-list">
                            <?php foreach ($recent_teacher_announcements as $row): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        <div><small><?php echo date('M j, Y', strtotime($row['created_at'])); ?></small></div>
                                    </div>
                                    <div style="max-width:60%; color:#6b7280; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        <?php echo htmlspecialchars($row['content']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No recent announcements.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        

        <div class="charts-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start; margin-top:16px;">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-video"></i> Upcoming Virtual Classes</h3>
                    <a href="../pages/virtual-classes.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="card-content">
                    <?php if (!empty($upcoming_virtual_classes)): ?>
                        <div class="list">
                            <?php foreach ($upcoming_virtual_classes as $row): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        <div><?php echo htmlspecialchars($row['class_name'] . ' • ' . $row['subject_name']); ?></div>
                                        <small><?php echo date('M j, Y', strtotime($row['scheduled_date'])) . ' at ' . date('g:i A', strtotime($row['start_time'])); ?></small>
                                    </div>
                                    <?php if (!empty($row['meeting_link'])): ?>
                                        <a class="btn btn-sm btn-outline" href="<?php echo htmlspecialchars($row['meeting_link']); ?>" target="_blank" rel="noopener">Join Now</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No upcoming virtual classes.</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Donut chart aligned with Upcoming Virtual Classes -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> My Classes & Subjects</h3>
                </div>
                <div class="card-content">
                    <div style="height:280px;">
                        <canvas id="classesSubjectsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Charts -->
        <div class="charts-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start; margin-top:16px;">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Students Attendance</h3>
                </div>
                <div class="card-content">
                    <div style="height:280px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Student Performance</h3>
                </div>
                <div class="card-content">
                    <div style="height:280px;">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Prepare real data for Classes & Subjects doughnut chart (group by subject)
        $subjectCounts = [];
        if (!empty($assigned_classes)) {
            foreach ($assigned_classes as $ac) {
                $subj = $ac['subject_name'] ?? 'Subject';
                $subjectCounts[$subj] = ($subjectCounts[$subj] ?? 0) + 1;
            }
        }
        $csLabels = array_keys($subjectCounts);
        $csValues = array_values($subjectCounts);
        ?>
        <script>
        (function() {
            // Real data for Classes & Subjects doughnut chart (grouped by subject)
            const csData = {
                labels: <?php echo json_encode($csLabels); ?>,
                values: <?php echo json_encode($csValues); ?>
            };
            if (!csData.labels || csData.labels.length === 0) return;

            // Try updating once the chart is ready (dashboard.js loads after this file)
            let attempts = 0;
            const maxAttempts = 25; // ~5s total
            function tryUpdate() {
                const hasFn = typeof window.updateCharts === 'function';
                const hasChart = window.dashboardCharts && window.dashboardCharts.classesSubjectsChart;
                if (hasFn && hasChart) {
                    window.updateCharts({ classesSubjects: csData });
                } else if (attempts++ < maxAttempts) {
                    setTimeout(tryUpdate, 200);
                }
            }
            // Start trying after load to give scripts time to register
            if (document.readyState === 'complete') {
                tryUpdate();
            } else {
                window.addEventListener('load', tryUpdate);
            }
        })();
        </script>

    </main>
</div>

<?php include '../components/footer.php'; ?>
