<?php
require_once '../config/config.php';

// Check if user is logged in (header is used globally by all roles)
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Get user data
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$user_role = $_SESSION['role'] ?? '';
$email = $_SESSION['email'] ?? '';
$profile_image = '';
$profile_image_url = '';

// Get system settings for display
try {
    $pdo = getDBConnection();
    $settings_stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('app_name', 'theme_primary_color', 'theme_secondary_color', 'theme_accent_color', 'theme_mode', 'app_logo', 'app_favicon', 'brand_display')");
    $settings_stmt->execute();
    $settings = [];
    while ($row = $settings_stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    // Fetch current user's profile image
    if ($user_id) {
        $uStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$user_id]);
        $uRow = $uStmt->fetch();
        if ($uRow && !empty($uRow['profile_image'])) {
            $profile_image = $uRow['profile_image'];
        }
    }
} catch (PDOException $e) {
    $settings = [
        'app_name' => APP_NAME,
        'theme_primary_color' => DEFAULT_PRIMARY_COLOR,
        'theme_mode' => 'light'
    ];
}

$app_name = $settings['app_name'] ?? APP_NAME;
$theme_color = $settings['theme_primary_color'] ?? DEFAULT_PRIMARY_COLOR;
$theme_secondary = $settings['theme_secondary_color'] ?? DEFAULT_SECONDARY_COLOR;
$theme_accent = $settings['theme_accent_color'] ?? DEFAULT_ACCENT_COLOR;
$theme_mode = $settings['theme_mode'] ?? 'light';
$app_logo = $settings['app_logo'] ?? '';
$app_favicon = $settings['app_favicon'] ?? '';
$brand_display = $settings['brand_display'] ?? 'both'; // 'logo' | 'name' | 'both'

// Determine current page for active state in nav
$current_page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

// Unread messages count for mobile badge (teachers mainly)
$unread_messages_count = 0;
try {
    if ($user_id) {
        $stmtUnread = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0');
        $stmtUnread->execute([$user_id]);
        $unread_messages_count = (int)$stmtUnread->fetchColumn();
    }
} catch (Throwable $e) {
    // ignore
}

// Compute visibility
$hasLogo = !empty($app_logo);
$mode = in_array($brand_display, ['logo','name','both'], true) ? $brand_display : 'both';
$showLogo = $hasLogo && ($mode === 'logo' || $mode === 'both');
$showName = ($mode === 'name' || $mode === 'both');
$showIcon = !$showLogo; // only show generic icon when no logo is shown

// Build profile image URL according to role storage location
if (!empty($profile_image)) {
    // If it's already an absolute URL, use as-is
    if (preg_match('/^https?:\/\//i', $profile_image)) {
        $profile_image_url = $profile_image;
    } else if (strpos($profile_image, '/') !== false) {
        // Normalize stored relative/path-like values
        $p = ltrim($profile_image, '/');
        // Remove duplicated app folder prefix like 'rinda/' if present
        if (strpos($p, 'rinda/') === 0) {
            $p = substr($p, strlen('rinda/'));
        }
        // If path already points into uploads/, just prefix '../'
        if (strpos($p, 'uploads/') === 0) {
            $profile_image_url = '../' . $p;
        } else {
            // Fallback to role-based folder
            if ($user_role === 'teacher') {
                $profile_image_url = '../uploads/teachers/' . rawurlencode(basename($p));
            } else {
                $profile_image_url = '../uploads/users/' . rawurlencode(basename($p));
            }
        }
    } else {
        // Simple filename, map by role
        if ($user_role === 'teacher') {
            $profile_image_url = '../uploads/teachers/' . rawurlencode($profile_image);
        } else {
            $profile_image_url = '../uploads/users/' . rawurlencode($profile_image);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme_mode; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $app_name : $app_name; ?></title>
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Favicon -->
    <?php
      $favicon_url = !empty($app_favicon) ? ('../uploads/system/'.rawurlencode($app_favicon)) : '../assets/img/favicon.ico';
    ?>
    <link rel="icon" href="<?php echo $favicon_url; ?>">
    
    <style>
        :root {
            --primary-color: <?php echo $theme_color; ?>;
            --secondary-color: <?php echo $theme_secondary; ?>;
            --accent-color: <?php echo $theme_accent; ?>;
            --bg-color: #ffffff;
            --text-color: #111827;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
        }
        html[data-theme="dark"] {
            --bg-color: #111827;
            --text-color: #e5e7eb;
            --card-bg: #1f2937;
            --border-color: #374151;
        }
        body{background: var(--bg-color); color: var(--text-color);}        
        .card, .teacher-card, .modal, .notification-panel, .user-panel { background: var(--card-bg); border-color: var(--border-color); }
        .header, .sidebar { background: var(--card-bg); border-color: var(--border-color); }
        .btn.btn-primary{ background: var(--primary-color); border-color: var(--primary-color); }
        .btn.btn-secondary{ border-color: var(--border-color); }
        .search-input-wrapper input{ background: var(--card-bg); color: var(--text-color); border:1px solid var(--border-color); }
        .table th,.table td{ border-color: var(--border-color); }

        /* App name styling */
        .logo h1 { font-size: 1.8rem; }
        @media (max-width: 768px) {
            .logo h1 { font-size: 0.9rem; }
        }
    </style>
</head>
<body class="role-<?php echo htmlspecialchars($user_role); ?>">
    <header class="header">
        <div class="header-left">
            <?php if ($showIcon): ?>
            <div class="school-icon">
                <i class="fas fa-school"></i>
            </div>
            <?php endif; ?>
            
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="logo">
                <?php if ($showLogo): ?>
                    <?php $logo_url = '../uploads/system/'.rawurlencode($app_logo); ?>
                    <img src="<?php echo $logo_url; ?>" alt="Logo">
                <?php endif; ?>
                <?php if ($showName): ?>
                    <h1 style="margin-left: 8px; display:inline-block;"><?php echo htmlspecialchars($app_name); ?></h1>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="header-center">
            <div class="search-bar">
                <input type="text" placeholder="Search..." id="globalSearch">
                <button type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <div class="header-right">
            <div class="header-actions">
                <!-- Notifications -->
                <div class="notification-dropdown">
                    <button class="notification-btn" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                    <div class="notification-panel" id="notificationPanel">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <button class="mark-all-read">Mark all read</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <!-- Notifications will be loaded here -->
                        </div>
                        <div class="notification-footer">
                            <a href="../pages/notifications.php">View all notifications</a>
                        </div>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            <?php if (!empty($profile_image_url)): ?>
                                <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='\\u003ci class=\\'fas fa-user\\'\\u003e\\u003c/i\\u003e';">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo $first_name . ' ' . $last_name; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-panel" id="userPanel">
                        <div class="user-info">
                            <div class="user-avatar-large">
                                <?php if (!empty($profile_image_url)): ?>
                                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Avatar" style="width:48px;height:48px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='\\u003ci class=\\'fas fa-user\\'\\u003e\\u003c/i\\u003e';">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="user-details">
                                <h4><?php echo $first_name . ' ' . $last_name; ?></h4>
                                <p><?php echo ucfirst($user_role); ?></p>
                                <p class="user-email"><?php echo $email; ?></p>
                            </div>
                        </div>
                        <div class="user-menu">
                            <?php if ($user_role === 'teacher'): ?>
                            <a href="../pages/teacher_profile.php">
                                <i class="fas fa-user-circle"></i>
                                My Profile
                            </a>
                            <a href="../pages/teacher_settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            <a href="../pages/teacher_change_password.php">
                                <i class="fas fa-key"></i>
                                Change Password
                            </a>
                            <?php elseif ($user_role === 'supervisor'): ?>
                            <a href="../supervisor/profile.php">
                                <i class="fas fa-user-circle"></i>
                                My Profile
                            </a>
                            <a href="../supervisor/settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            <a href="../supervisor/change_password.php">
                                <i class="fas fa-key"></i>
                                Change Password
                            </a>
                            <?php else: ?>
                            <a href="../pages/profile.php">
                                <i class="fas fa-user-circle"></i>
                                My Profile
                            </a>
                            <a href="../pages/settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            <a href="../pages/change-password.php">
                                <i class="fas fa-key"></i>
                                Change Password
                            </a>
                            <?php endif; ?>
                            <div class="menu-divider"></div>
                            <a href="../auth/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation -->
    <nav class="mobile-nav" id="mobileNav">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
            <?php
                $isActive = function(string $file) use ($current_page) { return $current_page === $file ? ' active' : ''; };
            ?>
            <a href="../pages/teacher-dashboard.php" class="nav-item<?php echo $isActive('teacher-dashboard.php'); ?>">
                <i class="fas fa-chalkboard"></i>
                <span>Dashboard</span>
            </a>
            <a href="../pages/messages.php" class="nav-item<?php echo $isActive('messages.php'); ?>">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php if ($unread_messages_count > 0): ?>
                    <span style="background:#ef4444;color:#fff;border-radius:10px;padding:2px 6px;font-size:11px;line-height:1; margin-left:6px;">
                        <?php echo $unread_messages_count; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="../pages/assignments.php" class="nav-item<?php echo $isActive('assignments.php'); ?>">
                <i class="fas fa-tasks"></i>
                <span>Assignments</span>
            </a>
            <a href="../pages/teacher_settings.php" class="nav-item<?php echo $isActive('teacher_settings.php'); ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor'): ?>
            <?php
                $isActive = function(string $file) use ($current_page) { return $current_page === $file ? ' active' : ''; };
            ?>
            <a href="../supervisor/index.php" class="nav-item supervisor-dashboard<?php echo $isActive('index.php'); ?>">
                <i class="fas fa-chalkboard"></i>
                <span>Dashboard</span>
            </a>
            <a href="../pages/messages.php" class="nav-item<?php echo $isActive('messages.php'); ?>">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php if ($unread_messages_count > 0): ?>
                    <span style="background:#ef4444;color:#fff;border-radius:10px;padding:2px 6px;font-size:11px;line-height:1; margin-left:6px;">
                        <?php echo $unread_messages_count; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="../supervisor/classes.php" class="nav-item<?php echo $isActive('classes.php'); ?>">
                <i class="fas fa-school"></i>
                <span>Classes</span>
            </a>
            <a href="../supervisor/settings.php" class="nav-item<?php echo $isActive('settings.php'); ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        <?php else: ?>
            <a href="../dashboard/index.php" class="nav-item<?php echo ($current_page==='index.php' ? ' active' : ''); ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="../pages/teachers.php" class="nav-item<?php echo ($current_page==='teachers.php' ? ' active' : ''); ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teachers</span>
            </a>
            <a href="../pages/students.php" class="nav-item<?php echo ($current_page==='students.php' ? ' active' : ''); ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="../pages/classes.php" class="nav-item<?php echo ($current_page==='classes.php' ? ' active' : ''); ?>">
                <i class="fas fa-school"></i>
                <span>Classes</span>
            </a>
            <a href="../pages/settings.php" class="nav-item<?php echo ($current_page==='settings.php' ? ' active' : ''); ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        <?php endif; ?>
    </nav>
    
    <script>
        // Header functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            const logoToggle = document.querySelector('.logo');
            
            console.log('Sidebar toggle element:', sidebarToggle);
            console.log('Sidebar element:', sidebar);
            console.log('Logo toggle element:', logoToggle);
            
            // Mobile toggle button functionality (≤1024px)
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    console.log('Sidebar toggle clicked!');
                    console.log('Window width:', window.innerWidth);
                    
                    // Only handle mobile sidebar toggle (≤1024px)
                    if (window.innerWidth <= 1024) {
                        console.log('Mobile mode - toggling active class');
                        sidebar.classList.toggle('active');
                        console.log('Sidebar classes after toggle:', sidebar.className);
                    }
                });
            } else {
                console.error('Sidebar toggle or sidebar not found!');
                console.error('sidebarToggle:', sidebarToggle);
                console.error('sidebar:', sidebar);
            }
            
            // Desktop app name click functionality (>1024px)
            if (logoToggle && sidebar) {
                logoToggle.addEventListener('click', function() {
                    console.log('Logo clicked!');
                    console.log('Window width:', window.innerWidth);
                    
                    // Only handle desktop sidebar toggle (>1024px)
                    if (window.innerWidth > 1024) {
                        console.log('Desktop mode - toggling collapsed class');
                        sidebar.classList.toggle('collapsed');
                        document.body.classList.toggle('sidebar-collapsed');
                    }
                });
            } else {
                console.error('Logo toggle or sidebar not found!');
                console.error('logoToggle:', logoToggle);
                console.error('sidebar:', sidebar);
            }
            
            // User dropdown
            const userBtn = document.getElementById('userBtn');
            const userPanel = document.getElementById('userPanel');
            
            if (userBtn && userPanel) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userPanel.classList.toggle('active');
                });
                
                // Close when clicking outside
                document.addEventListener('click', function() {
                    userPanel.classList.remove('active');
                });
            }
            
            // Notification dropdown
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationPanel = document.getElementById('notificationPanel');
            
            if (notificationBtn && notificationPanel) {
                notificationBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationPanel.classList.toggle('active');
                });
                
                // Close when clicking outside
                document.addEventListener('click', function() {
                    notificationPanel.classList.remove('active');
                });
            }
            
            // Global search
            const globalSearch = document.getElementById('globalSearch');
            if (globalSearch) {
                globalSearch.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const query = this.value.trim();
                        if (query) {
                            window.location.href = `../pages/search.php?q=${encodeURIComponent(query)}`;
                        }
                    }
                });
            }
            
            // Handle window resize to update sidebar behavior
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    // Desktop: remove active class and restore normal behavior
                    if (sidebar) {
                        sidebar.classList.remove('active');
                    }
                } else {
                    // Mobile: remove collapsed class and restore mobile behavior
                    if (sidebar) {
                        sidebar.classList.remove('collapsed');
                        document.body.classList.remove('sidebar-collapsed');
                    }
                }
            });
            
        });
        
        // Notifications: polling, rendering, mark all read
        (function(){
            const badgeEl = document.getElementById('notificationBadge');
            const listEl = document.getElementById('notificationList');
            const panelEl = document.getElementById('notificationPanel');
            const markAllBtn = panelEl ? panelEl.querySelector('.mark-all-read') : null;
            const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
            let latestId = 0;
            let initialized = false;

            function formatTime(ts){
                try { const d = new Date(ts.replace(' ', 'T')); return d.toLocaleString(); } catch(e) { return ts; }
            }

            function renderList(items){
                if (!listEl) return;
                if (!items || items.length === 0){
                    listEl.innerHTML = '<div class="notification-item empty">No notifications</div>';
                    return;
                }
                listEl.innerHTML = items.map(n => {
                    const type = n.type || 'info';
                    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
                    const readCls = n.is_read ? 'read' : 'unread';
                    const action = n.action_url ? `<a href="${n.action_url}" class="notification-action">Open</a>` : '';
                    return `
                        <div class="notification-item ${readCls}">
                            <div class="notification-icon ${type}"><i class="fas fa-${icon}"></i></div>
                            <div class="notification-body">
                                <div class="notification-title">${escapeHtml(n.title || '')}</div>
                                <div class="notification-message">${escapeHtml(n.message || '')}</div>
                                <div class="notification-meta">${formatTime(n.created_at || '')}</div>
                            </div>
                            ${action}
                        </div>
                    `;
                }).join('');
            }

            function escapeHtml(s){
                return String(s).replace(/[&<>"']/g, function(c){
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]);
                });
            }

            function updateBadge(count){
                if (badgeEl) badgeEl.textContent = String(count || 0);
            }

            let firstLoad = true;
            function handleNewItems(items){
                if (firstLoad) { firstLoad = false; return; }
                if (!Array.isArray(items)) return;
                // Show popups for newly arrived items only
                items.forEach(n => {
                    if (typeof window.showNotification === 'function') {
                        const msg = n.title ? `${n.title}: ${n.message}` : n.message;
                        window.showNotification(msg, n.type || 'info', 6000);
                    }
                });
            }

            function fetchNotifications(){
                fetch(`../api/fetch_notifications.php?since_id=${latestId}&limit=${initialized ? 50 : 20}`, {credentials:'same-origin'})
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.success) return;
                    const items = data.data || [];
                    if (!initialized) {
                        renderList(items);
                        initialized = true;
                    } else if (items.length) {
                        // Prepend new items to top of list
                        const existing = listEl ? listEl.innerHTML : '';
                        const html = items.map(n => {
                            const type = n.type || 'info';
                            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
                            const readCls = n.is_read ? 'read' : 'unread';
                            const action = n.action_url ? `<a href="${n.action_url}" class=\"notification-action\">Open</a>` : '';
                            return `
                                <div class=\"notification-item ${readCls}\">
                                    <div class=\"notification-icon ${type}\"><i class=\"fas fa-${icon}\"></i></div>
                                    <div class=\"notification-body\">
                                        <div class=\"notification-title\">${escapeHtml(n.title || '')}</div>
                                        <div class=\"notification-message\">${escapeHtml(n.message || '')}</div>
                                        <div class=\"notification-meta\">${formatTime(n.created_at || '')}</div>
                                    </div>
                                    ${action}
                                </div>
                            `;
                        }).join('');
                        if (listEl) listEl.innerHTML = html + existing;
                    }
                    updateBadge(data.unread_count || 0);
                    if (items.length) {
                        handleNewItems(items);
                    }
                    if (typeof data.latest_id === 'number' && data.latest_id > latestId) {
                        latestId = data.latest_id;
                    }
                }).catch(()=>{});
            }

            // Poll every 15 seconds
            fetchNotifications();
            setInterval(fetchNotifications, 15000);

            if (markAllBtn) {
                markAllBtn.addEventListener('click', function(){
                    // Bulk mark as read
                    fetch('../api/mark_all_notifications_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'csrf_token=' + encodeURIComponent(csrf)
                    }).then(r=>r.json()).then(data => {
                        if (data && data.success) {
                            updateBadge(0);
                            // Update UI to reflect read state
                            if (listEl) {
                                listEl.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
                            }
                            if (typeof window.showNotification === 'function') {
                                window.showNotification('All notifications marked as read', 'success');
                            }
                        }
                    }).catch(()=>{});
                });
            }
        })();
    </script>