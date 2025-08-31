<?php
require_once '../config/config.php';

// Guard: only logged-in supervisors
if (!isLoggedIn() || ($_SESSION['role'] ?? null) !== 'supervisor') {
    redirect('../auth/login.php');
}

$supervisor_id = getCurrentUserId();
$page_title = 'Supervisor Dashboard';

$assigned_classes = [];
$total_students = 0;
$upcoming_virtual_classes = [];
$recent_announcements = [];
// New analytics datasets
$class_avg_scores = [];
$attendance_trend = [];

try {
    $pdo = getDBConnection();

    // Classes assigned to this supervisor
    $stmt = $pdo->prepare("SELECT sa.id, c.id AS class_id, c.class_name, c.class_code, c.grade_level, sa.academic_year
                           FROM supervisor_assignments sa
                           JOIN classes c ON sa.class_id = c.id
                           WHERE sa.supervisor_id = ? AND sa.is_active = 1
                           ORDER BY c.grade_level, c.class_name");
    $stmt->execute([$supervisor_id]);
    $assigned_classes = $stmt->fetchAll();

    $classIds = array_map(fn($r) => (int)$r['class_id'], $assigned_classes);

    // Total students across supervised classes
    if (!empty($classIds)) {
        $in = str_repeat('?,', count($classIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT se.student_id)
                                FROM student_enrollments se
                                WHERE se.class_id IN ($in) AND se.status = 'active'");
        $stmt->execute($classIds);
        $total_students = (int)$stmt->fetchColumn();
    }

    // Upcoming virtual classes for supervised classes (next 10)
    if (!empty($classIds)) {
        $in = str_repeat('?,', count($classIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT vc.id, vc.title, vc.scheduled_date, vc.start_time, vc.end_time, vc.meeting_link,
                                      c.class_name, s.subject_name
                               FROM virtual_classes vc
                               JOIN classes c ON vc.class_id = c.id
                               JOIN subjects s ON vc.subject_id = s.id
                               WHERE vc.class_id IN ($in) AND vc.is_active = 1 AND vc.scheduled_date >= CURDATE()
                               ORDER BY vc.scheduled_date, vc.start_time
                               LIMIT 10");
        $stmt->execute($classIds);
        $upcoming_virtual_classes = $stmt->fetchAll();
    }

    // Recent announcements targeting supervisors or all
    $stmt = $pdo->query("SELECT id, title, created_at, content
                         FROM announcements
                         WHERE is_active = 1 AND target_audience IN ('supervisors','all')
                         ORDER BY created_at DESC
                         LIMIT 5");
    $recent_announcements = $stmt->fetchAll();

    // Per-class average test percentage (last 60 days)
    if (!empty($classIds)) {
        $in = str_repeat('?,', count($classIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT t.class_id, ROUND(AVG(tr.percentage),2) AS avg_pct
                               FROM test_results tr
                               JOIN tests t ON tr.test_id = t.id
                               WHERE t.class_id IN ($in) AND t.scheduled_date >= (CURDATE() - INTERVAL 60 DAY)
                               GROUP BY t.class_id");
        $stmt->execute($classIds);
        $class_avg_scores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // class_id => avg_pct
    }

    // Attendance trend: present rate by day for last 14 days across all supervised classes
    if (!empty($classIds)) {
        $in = str_repeat('?,', count($classIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT a.date,
                                      SUM(a.status = 'present') AS present_cnt,
                                      COUNT(*) AS total_cnt
                               FROM attendance a
                               WHERE a.class_id IN ($in) AND a.date >= (CURDATE() - INTERVAL 14 DAY)
                               GROUP BY a.date
                               ORDER BY a.date");
        $stmt->execute($classIds);
        $attendance_trend = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    $error_message = 'Error loading supervisor dashboard: ' . $e->getMessage();
}

include '../components/header.php';
?>

<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1>Supervisor Dashboard</h1>
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

        <!-- Overview Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users-rectangle"></i></div>
                <div class="stat-content">
                    <h3><?php echo count($assigned_classes); ?></h3>
                    <p>Assigned Classes</p>
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
                <div class="stat-icon"><i class="fas fa-video"></i></div>
                <div class="stat-content">
                    <h3><?php echo count($upcoming_virtual_classes); ?></h3>
                    <p>Upcoming Virtual Classes</p>
                </div>
            </div>
        </div>

        <!-- Analytics & Feed -->
        <div class="charts-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start;">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Class Distribution</h3>
                </div>
                <div class="card-content">
                    <div style="height:280px;">
                        <canvas id="supervisorClassDist"></canvas>
                    </div>
                </div>
            </div>
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-bullhorn"></i> Recent Announcements</h3>
                    <a href="../pages/announcements.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="card-content">
                    <?php if (!empty($recent_announcements)): ?>
                        <div class="list announce-list">
                            <?php foreach ($recent_announcements as $row): ?>
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
                                        <a class="btn btn-sm btn-outline" href="<?php echo htmlspecialchars($row['meeting_link']); ?>" target="_blank" rel="noopener">Join</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No upcoming virtual classes.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Assigned Classes</h3>
                    <a href="./classes.php" class="btn btn-sm btn-outline">Manage</a>
                </div>
                <div class="card-content">
                    <?php if (!empty($assigned_classes)): ?>
                        <div class="list">
                            <?php foreach ($assigned_classes as $c): ?>
                                <a class="list-item" href="./class_view.php?class_id=<?php echo (int)$c['class_id']; ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($c['class_name']); ?></strong>
                                        <div><small>Code: <?php echo htmlspecialchars($c['class_code']); ?> • AY: <?php echo htmlspecialchars($c['academic_year']); ?></small></div>
                                        <?php
                                            $avg = isset($class_avg_scores[$c['class_id']]) ? number_format((float)$class_avg_scores[$c['class_id']], 2) . '%' : 'N/A';
                                        ?>
                                        <div style="color:#6b7280; font-size:12px;">Avg Score (60d): <?php echo $avg; ?></div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#9ca3af"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No assigned classes.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Analytics: Attendance Trend & Class Averages Chart -->
        <div class="charts-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start; margin-top:16px;">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Attendance Trend (14d)</h3>
                </div>
                <div class="card-content">
                    <div style="height:280px;"><canvas id="attendanceTrend"></canvas></div>
                </div>
            </div>
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Avg Test % by Class (60d)</h3>
                </div>
                <div class="card-content">
                    <div style="height:280px;"><canvas id="avgScoresByClass"></canvas></div>
                </div>
            </div>
        </div>

        <?php
        // Prepare data for Class Distribution chart by grade level
        $gradeCounts = [];
        foreach ($assigned_classes as $ac) {
            $g = $ac['grade_level'] ?: 'N/A';
            $gradeCounts[$g] = ($gradeCounts[$g] ?? 0) + 1;
        }
        $cdLabels = array_keys($gradeCounts);
        $cdValues = array_values($gradeCounts);
        // Prepare datasets for new charts
        $attLabels = [];
        $attRates = [];
        foreach ($attendance_trend as $row) {
            $attLabels[] = date('M j', strtotime($row['date']));
            $rate = ($row['total_cnt'] > 0) ? round(($row['present_cnt'] / $row['total_cnt']) * 100, 2) : 0;
            $attRates[] = $rate;
        }
        $avgClassLabels = [];
        $avgClassValues = [];
        foreach ($assigned_classes as $c) {
            $avgClassLabels[] = $c['class_name'];
            $avgClassValues[] = isset($class_avg_scores[$c['class_id']]) ? (float)$class_avg_scores[$c['class_id']] : 0;
        }
        ?>
        <script>
        (function(){
            const data = {
                labels: <?php echo json_encode($cdLabels); ?>,
                values: <?php echo json_encode($cdValues); ?>
            };
            let tries = 0;
            function tryChart(){
                const ctx = document.getElementById('supervisorClassDist');
                if (!ctx || !window.Chart) { if (tries++ < 25) return setTimeout(tryChart, 200); else return; }
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: ['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#14b8a6']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
            tryChart();
        })();
        </script>
        <script>
        (function(){
            let tries = 0;
            function draw(){
                if (!window.Chart) { if (tries++ < 25) return setTimeout(draw, 200); else return; }
                const attCtx = document.getElementById('attendanceTrend');
                const attLabels = <?php echo json_encode($attLabels); ?>;
                const attRates = <?php echo json_encode($attRates); ?>;
                if (attCtx) {
                    new Chart(attCtx, {
                        type: 'line',
                        data: { labels: attLabels, datasets: [{ label: 'Present %', data: attRates, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.15)', fill: true, tension: 0.3 }] },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { min:0, max:100, ticks: { callback: v=> v+'%' } } } }
                    });
                }
                const avgCtx = document.getElementById('avgScoresByClass');
                const avgLabels = <?php echo json_encode($avgClassLabels); ?>;
                const avgValues = <?php echo json_encode($avgClassValues); ?>;
                if (avgCtx) {
                    new Chart(avgCtx, {
                        type: 'bar',
                        data: { labels: avgLabels, datasets: [{ label: 'Avg %', data: avgValues, backgroundColor: '#10b981' }] },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { min:0, max:100, ticks: { callback: v=> v+'%' } } } }
                    });
                }
            }
            draw();
        })();
        </script>
    </main>
</div>

<?php include '../components/footer.php'; ?>
