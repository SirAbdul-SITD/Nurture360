<?php
require_once '../config/config.php';

// Check if user is logged in and is SuperAdmin
if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../auth/login.php');
}

$page_title = 'Dashboard';

// Get dashboard statistics
try {
    $pdo = getDBConnection();
    
    // Count users by role
    $user_counts = [];
    $roles = ['teacher', 'student', 'supervisor'];
    foreach ($roles as $role) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = ? AND is_active = 1");
        $stmt->execute([$role]);
        $user_counts[$role] = $stmt->fetch()['count'];
    }
    
    // Count classes and subjects
    $class_count = $pdo->query("SELECT COUNT(*) FROM classes WHERE is_active = 1")->fetchColumn();
    $subject_count = $pdo->query("SELECT COUNT(*) FROM subjects WHERE is_active = 1")->fetchColumn();
    
    // Get recent activities
    $recent_activities = [];
    $activity_stmt = $pdo->prepare("
        SELECT 'user_login' as type, u.first_name, u.last_name, u.role, sl.created_at
        FROM system_logs sl
        JOIN users u ON sl.user_id = u.id
        WHERE sl.action = 'login'
        ORDER BY sl.created_at DESC
        LIMIT 10
    ");
    $activity_stmt->execute();
    $recent_activities = $activity_stmt->fetchAll();
    
    // Get upcoming tests
    $upcoming_tests = [];
    $test_stmt = $pdo->prepare("
        SELECT t.title, t.scheduled_date, t.start_time, c.class_name, s.subject_name
        FROM tests t
        JOIN classes c ON t.class_id = c.id
        JOIN subjects s ON t.subject_id = s.id
        WHERE t.scheduled_date >= CURDATE() AND t.is_active = 1
        ORDER BY t.scheduled_date, t.start_time
        LIMIT 5
    ");
    $test_stmt->execute();
    $upcoming_tests = $test_stmt->fetchAll();
    
    // Get recent announcements
    $recent_announcements = [];
    $announcement_stmt = $pdo->prepare("
        SELECT a.title, a.content, a.created_at, u.first_name, u.last_name
        FROM announcements a
        JOIN users u ON a.author_id = u.id
        WHERE a.is_active = 1
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $announcement_stmt->execute();
    $recent_announcements = $announcement_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'Error loading dashboard data: ' . $e->getMessage();
    $user_counts = ['teacher' => 0, 'student' => 0, 'supervisor' => 0];
    $class_count = 0;
    $subject_count = 0;
    $recent_activities = [];
    $upcoming_tests = [];
    $recent_announcements = [];
}

// Include header
include '../components/header.php';
?>

<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo $_SESSION['first_name']; ?>! Here's what's happening in your school system.</p>
        </div>
        
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $user_counts['teacher']; ?></h3>
                    <p>Total Teachers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $user_counts['student']; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $user_counts['supervisor']; ?></h3>
                    <p>Total Supervisors</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $class_count; ?></h3>
                    <p>Active Classes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $subject_count; ?></h3>
                    <p>Total Subjects</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($upcoming_tests); ?></h3>
                    <p>Upcoming Tests</p>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-container">
                <div class="chart-header">
                    <h3>User Distribution</h3>
                    <div class="chart-actions">
                        <button class="btn btn-sm btn-outline" onclick="refreshChart('userChart')">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-content">
                    <canvas id="userChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Recent Activity</h3>
                    <div class="chart-actions">
                        <button class="btn btn-sm btn-outline" onclick="refreshChart('activityChart')">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-content">
                    <canvas id="activityChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
            <div class="content-grid">
                <!-- Upcoming Tests -->
                <div class="content-card compact">
                    <div class="card-header">
                        <h3><i class="fas fa-question-circle"></i> Upcoming Tests</h3>
                        <a href="../pages/tests.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($upcoming_tests)): ?>
                            <div class="test-list">
                                <?php foreach ($upcoming_tests as $test): ?>
                                    <div class="test-item">
                                        <div class="test-info">
                                            <h4><?php echo htmlspecialchars($test['title']); ?></h4>
                                            <p><?php echo htmlspecialchars($test['class_name'] . ' - ' . $test['subject_name']); ?></p>
                                            <small><?php echo date('M j, Y', strtotime($test['scheduled_date'])) . ' at ' . date('g:i A', strtotime($test['start_time'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No upcoming tests</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Announcements -->
                <div class="content-card compact">
                    <div class="card-header">
                        <h3><i class="fas fa-bullhorn"></i> Recent Announcements</h3>
                        <a href="../pages/announcements.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($recent_announcements)): ?>
                            <div class="announcement-list">
                                <?php foreach ($recent_announcements as $announcement): ?>
                                    <div class="announcement-item">
                                        <div class="announcement-header">
                                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                            <small>by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?></small>
                                        </div>
                                        <p><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)) . (strlen($announcement['content']) > 100 ? '...' : ''); ?></p>
                                        <small><?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No recent announcements</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
                    </div>
                    </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initializeCharts();
    
    // Auto-refresh dashboard data every 5 minutes
    setInterval(refreshDashboardData, 300000);
});

function initializeCharts() {
    // User Distribution Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(userCtx, {
        type: 'doughnut',
        data: {
            labels: ['Teachers', 'Students', 'Supervisors'],
            datasets: [{
                data: [
                    <?php echo $user_counts['teacher']; ?>,
                    <?php echo $user_counts['student']; ?>,
                    <?php echo $user_counts['supervisor']; ?>
                ],
                backgroundColor: [
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Activity Chart (placeholder for now)
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityChart = new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'User Logins',
                data: [12, 19, 15, 25, 22, 18, 24],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function refreshChart(chartId) {
    // Refresh chart data
    console.log('Refreshing chart:', chartId);
}

function refreshDashboardData() {
    // Refresh dashboard data
    location.reload();
}
</script>

<?php include '../components/footer.php'; ?> 