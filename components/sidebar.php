<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar" id="sidebar">
    <!-- <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../assets/img/logo.png" alt="Rinda Logo" onerror="this.style.display='none'">
          
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div> -->
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <?php if (function_exists('isSupervisor') && isSupervisor()): ?>
            <!-- Supervisor Sidebar -->
            <li class="nav-item">
                <a href="../supervisor/index.php" class="nav-link <?php echo ($current_page === 'index' && $current_dir === 'supervisor') ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-section"><span class="nav-section-title">Supervision</span></li>
            <li class="nav-item">
                <a href="../supervisor/classes.php" class="nav-link <?php echo $current_page === 'classes' && $current_dir === 'supervisor' ? 'active' : ''; ?>">
                    <i class="fas fa-users-rectangle"></i>
                    <span>Assigned Classes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/announcements.php" class="nav-link <?php echo $current_page === 'announcements' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/messages.php" class="nav-link <?php echo $current_page === 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li class="nav-section"><span class="nav-section-title">Account</span></li>
            <li class="nav-item">
                <a href="../supervisor/profile.php" class="nav-link <?php echo $current_page === 'profile' && $current_dir === 'supervisor' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../supervisor/settings.php" class="nav-link <?php echo $current_page === 'settings' && $current_dir === 'supervisor' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../supervisor/change_password.php" class="nav-link <?php echo $current_page === 'change_password' && $current_dir === 'supervisor' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </a>
            </li>
            <?php elseif (function_exists('isTeacher') && isTeacher()): ?>
            <!-- Teacher Sidebar -->
            <li class="nav-item">
                <a href="../pages/teacher-dashboard.php" class="nav-link <?php echo ($current_page === 'teacher-dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard"></i>
                    <span>My Dashboard</span>
                </a>
            </li>
            <li class="nav-section"><span class="nav-section-title">My Teaching</span></li>
            <li class="nav-item">
                <a href="../pages/my_classes.php" class="nav-link <?php echo $current_page === 'my_classes' ? 'active' : ''; ?>">
                    <i class="fas fa-users-rectangle"></i>
                    <span>My Classes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/assignments.php" class="nav-link <?php echo $current_page === 'assignments' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/tests.php" class="nav-link <?php echo $current_page === 'tests' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>Tests & Quizzes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/exams.php" class="nav-link <?php echo $current_page === 'exams' ? 'active' : ''; ?>">
                    <i class="fas fa-file-signature"></i>
                    <span>Exams</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/virtual-classes.php" class="nav-link <?php echo $current_page === 'virtual-classes' ? 'active' : ''; ?>">
                    <i class="fas fa-video"></i>
                    <span>Virtual Classes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/teacher_attendance.php" class="nav-link <?php echo $current_page === 'teacher_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/teacher_attendance_summary.php" class="nav-link <?php echo $current_page === 'teacher_attendance_summary' ? 'active' : ''; ?>">
                    <i class="fas fa-table"></i>
                    <span>Attendance Summary</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/grades.php" class="nav-link <?php echo $current_page === 'grades' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Grades</span>
                </a>
            </li>
            <li class="nav-section"><span class="nav-section-title">Resources & Comms</span></li>
            <li class="nav-item">
                <a href="../pages/teacher_lessons.php" class="nav-link <?php echo $current_page === 'teacher_lessons' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i>
                    <span>Lessons</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/teacher_resources.php" class="nav-link <?php echo $current_page === 'teacher_resources' ? 'active' : ''; ?>">
                    <i class="fas fa-folder-open"></i>
                    <span>Resources</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/announcements.php" class="nav-link <?php echo $current_page === 'announcements' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/messages.php" class="nav-link <?php echo $current_page === 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li class="nav-section"><span class="nav-section-title">Account</span></li>
            <li class="nav-item">
                <a href="../pages/teacher_profile.php" class="nav-link <?php echo $current_page === 'teacher_profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/teacher_settings.php" class="nav-link <?php echo $current_page === 'teacher_settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/teacher_change_password.php" class="nav-link <?php echo $current_page === 'teacher_change_password' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </a>
            </li>
            <?php else: ?>
            <!-- Admin/Other Roles Sidebar (existing) -->
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="../dashboard/index.php" class="nav-link <?php echo ($current_page === 'index' && $current_dir === 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- User Management -->
            <li class="nav-section">
                <span class="nav-section-title">User Management</span>
            </li>
            
            <li class="nav-item">
                <a href="../pages/teachers.php" class="nav-link <?php echo $current_page === 'teachers' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teachers</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/students.php" class="nav-link <?php echo $current_page === 'students' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/supervisors.php" class="nav-link <?php echo $current_page === 'supervisors' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Supervisors</span>
                </a>
            </li>
            
            <!-- Academics -->
            <li class="nav-section">
                <span class="nav-section-title">Academics</span>
            </li>
            
            <li class="nav-item">
                <a href="../pages/classes.php" class="nav-link <?php echo $current_page === 'classes' ? 'active' : ''; ?>">
                    <i class="fas fa-school"></i>
                    <span>Classes</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/subjects.php" class="nav-link <?php echo $current_page === 'subjects' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Subjects</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/timetable.php" class="nav-link <?php echo $current_page === 'timetable' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Timetable</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/assignments.php" class="nav-link <?php echo $current_page === 'assignments' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments</span>
                </a>
            </li>
            
            <!-- Learning Management -->
            <li class="nav-section">
                <span class="nav-section-title">Learning Management</span>
            </li>
            
            <li class="nav-item">
                <a href="../pages/teacher_lessons.php" class="nav-link <?php echo $current_page === 'teacher_lessons' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i>
                    <span>Lessons</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../pages/content_pages.php" class="nav-link <?php echo $current_page === 'content_pages' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Content Pages</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/resources.php" class="nav-link <?php echo $current_page === 'resources' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Learning Resources</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/virtual-classes.php" class="nav-link <?php echo $current_page === 'virtual-classes' ? 'active' : ''; ?>">
                    <i class="fas fa-video"></i>
                    <span>Virtual Classes</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/tests.php" class="nav-link <?php echo $current_page === 'tests' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>Tests & Quizzes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../pages/exams.php" class="nav-link <?php echo $current_page === 'exams' ? 'active' : ''; ?>">
                    <i class="fas fa-file-signature"></i>
                    <span>Exams</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/grades.php" class="nav-link <?php echo $current_page === 'grades' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Grades & Results</span>
                </a>
            </li>
            
            <!-- Communication -->
            <li class="nav-section">
                <span class="nav-section-title">Communication</span>
            </li>
            
            <li class="nav-item">
                <a href="../pages/messages.php" class="nav-link <?php echo $current_page === 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/notifications.php" class="nav-link <?php echo $current_page === 'notifications' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/announcements.php" class="nav-link <?php echo $current_page === 'announcements' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>
            
            <!-- Reports & Analytics -->
            <li class="nav-section">
                <span class="nav-section-title">Reports & Analytics</span>
            </li>
            
            <li class="nav-item">
                <a href="../pages/reports.php" class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/analytics.php" class="nav-link <?php echo $current_page === 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Analytics</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/transcript.php" class="nav-link <?php echo $current_page === 'transcript' ? 'active' : ''; ?>">
                    <i class="fas fa-file-signature"></i>
                    <span>Transcript</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/attendance.php" class="nav-link <?php echo $current_page === 'attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            
            <!-- System -->
            <li class="nav-section">
                <span class="nav-section-title">System</span>
            </li>
            
            <li class="nav-item">
                <a href="../pages/settings.php" class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/themes.php" class="nav-link <?php echo $current_page === 'themes' ? 'active' : ''; ?>">
                    <i class="fas fa-palette"></i>
                    <span>Themes & UI</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/email-settings.php" class="nav-link <?php echo $current_page === 'email-settings' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Email Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/access-control.php" class="nav-link <?php echo $current_page === 'access-control' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Access Control</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../pages/system-logs.php" class="nav-link <?php echo $current_page === 'system-logs' ? 'active' : ''; ?>">
                    <i class="fas fa-list-alt"></i>
                    <span>System Logs</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <!-- <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <p class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></p>
                <p class="user-role">SuperAdmin</p>
            </div>
        </div>
    </div> -->
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar close button for mobile
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Add active class to current page
    const currentPage = '<?php echo $current_page; ?>';
    const currentDir = '<?php echo $current_dir; ?>';
    
    if (currentPage === 'index' && currentDir === 'dashboard') {
        document.querySelector('a[href="../dashboard/index.php"]').classList.add('active');
    }
});
</script> 