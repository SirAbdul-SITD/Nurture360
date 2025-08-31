// Dashboard JavaScript - SuperAdmin Dashboard Functionality

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    setupDashboardEventListeners();
    loadDashboardData();
});

// Initialize dashboard
function initializeDashboard() {
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeDashboardCharts();
    }

    // Doughnut chart is initialized in initializeDashboardCharts()
    
    // Initialize data tables
    initializeDataTables();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize notifications
    initializeNotifications();
    
    // Initialize real-time updates
    initializeRealTimeUpdates();
}

// Setup dashboard event listeners
function setupDashboardEventListeners() {
    // Refresh dashboard data
    const refreshBtn = document.querySelector('.refresh-dashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshDashboardData);
    }
    
    // Export data buttons
    const exportBtns = document.querySelectorAll('[data-export]');
    exportBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const format = this.getAttribute('data-export');
            exportDashboardData(format);
        });
    });
    
    // Quick action buttons
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            handleQuickAction(action);
        });
    });
    
    // Chart refresh buttons
    const chartRefreshBtns = document.querySelectorAll('.chart-refresh');
    chartRefreshBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const chartId = this.getAttribute('data-chart');
            refreshChart(chartId);
        });
    });
}

// Initialize dashboard charts
function initializeDashboardCharts() {
    // User Distribution Chart
    const userCtx = document.getElementById('userChart');
    if (userCtx) {
        const userChart = new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: ['Teachers', 'Students', 'Supervisors'],
                datasets: [{
                    data: [0, 0, 0], // Will be populated with real data
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
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
        
        // Store chart reference for later updates
        window.dashboardCharts = window.dashboardCharts || {};
        window.dashboardCharts.userChart = userChart;
    }
    
    // Activity Chart
    const activityCtx = document.getElementById('activityChart');
    if (activityCtx) {
        const activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'User Logins',
                    data: [0, 0, 0, 0, 0, 0, 0], // Will be populated with real data
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
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
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });
        
        window.dashboardCharts.activityChart = activityChart;
    }

    // Assignments Analytics - Wave (filled line) chart
    const assignmentCtx = document.getElementById('assignmentChart');
    if (assignmentCtx && typeof Chart !== 'undefined') {
        const assignmentChart = new Chart(assignmentCtx, {
            type: 'line',
            data: {
                labels: ['W1', 'W2', 'W3', 'W4'],
                datasets: [{
                    label: 'Assignments Created',
                    data: [0, 0, 0, 0],
                    borderColor: '#000000',
                    backgroundColor: 'rgba(0,0,0,0.08)',
                    tension: 0.45,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        window.dashboardCharts = window.dashboardCharts || {};
        window.dashboardCharts.assignmentChart = assignmentChart;
    }
    // Students Attendance Chart (last 7 days placeholder)
    const attendanceCtx = document.getElementById('attendanceChart');
    if (attendanceCtx && typeof Chart !== 'undefined') {
        const attendanceChart = new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Attendance (%)',
                    data: [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#111827', // black-ish
                    backgroundColor: 'rgba(17, 24, 39, 0.08)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
        window.dashboardCharts = window.dashboardCharts || {};
        window.dashboardCharts.attendanceChart = attendanceChart;
    }

    // Student Performance Chart (placeholder by subject) - Radar chart
    const performanceCtx = document.getElementById('performanceChart');
    if (performanceCtx && typeof Chart !== 'undefined') {
        const performanceChart = new Chart(performanceCtx, {
            type: 'radar',
            data: {
                labels: ['Math', 'Science', 'English', 'History', 'ICT'],
                datasets: [{
                    label: 'Avg Score',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(0,0,0,0.08)',
                    borderColor: '#000000',
                    borderWidth: 2,
                    pointBackgroundColor: '#000000',
                    pointBorderColor: '#000000'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    r: { beginAtZero: true, max: 100, ticks: { stepSize: 20 } }
                }
            }
        });
        window.dashboardCharts = window.dashboardCharts || {};
        window.dashboardCharts.performanceChart = performanceChart;
    }

    // My Classes & Subjects doughnut chart
    const classesSubjectsCtx = document.getElementById('classesSubjectsChart');
    if (classesSubjectsCtx && typeof Chart !== 'undefined') {
        const classesSubjectsChart = new Chart(classesSubjectsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Class A', 'Class B', 'Class C'],
                datasets: [{
                    data: [0, 0, 0],
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
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '60%'
            }
        });
        window.dashboardCharts = window.dashboardCharts || {};
        window.dashboardCharts.classesSubjectsChart = classesSubjectsChart;
    }
}

// Initialize data tables
function initializeDataTables() {
    const tables = document.querySelectorAll('.table-container[data-sortable]');
    tables.forEach(table => {
        // Add sort indicators to headers
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <i class="fas fa-sort"></i>';
            
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-sort');
                const direction = this.getAttribute('data-direction') === 'asc' ? 'desc' : 'asc';
                
                // Update sort indicators
                headers.forEach(h => {
                    h.setAttribute('data-direction', '');
                    h.innerHTML = h.innerHTML.replace(/<i class="fas fa-sort-(up|down)"><\/i>/, '<i class="fas fa-sort"></i>');
                });
                
                this.setAttribute('data-direction', direction);
                this.innerHTML = this.innerHTML.replace(/<i class="fas fa-sort"><\/i>/, 
                    `<i class="fas fa-sort-${direction === 'asc' ? 'up' : 'down'}"></i>`);
                
                // Sort table
                sortTable(table, column, direction);
            });
        });
    });
}

// Sort table data
function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aCell = a.querySelector(`td[data-${column}]`);
        const bCell = b.querySelector(`td[data-${column}]`);
        
        if (!aCell || !bCell) return 0;
        
        let aValue = aCell.getAttribute(`data-${column}`) || aCell.textContent;
        let bValue = bCell.getAttribute(`data-${column}`) || bCell.textContent;
        
        // Handle numeric values
        if (!isNaN(aValue) && !isNaN(bValue)) {
            aValue = parseFloat(aValue);
            bValue = parseFloat(bValue);
        }
        
        if (direction === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

// Initialize search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            const targetTable = this.getAttribute('data-search');
            const table = document.querySelector(targetTable);
            
            if (table) {
                searchTable(table, searchTerm);
            }
        }, 300));
    });
}

// Search table content
function searchTable(table, searchTerm) {
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Initialize notifications
function initializeNotifications() {
    // Load notification count
    loadNotificationCount();
    
    // Load recent notifications
    loadRecentNotifications();
    
    // Mark all as read functionality
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllNotificationsAsRead);
    }
}

// Load notification count
function loadNotificationCount() {
    fetch('../api/notifications/count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            if (badge && data.count !== undefined) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'block' : 'none';
            }
        })
        .catch(error => {
            console.error('Error loading notification count:', error);
        });
}

// Load recent notifications
function loadRecentNotifications() {
    fetch('../api/notifications/recent.php')
        .then(response => response.json())
        .then(data => {
            const notificationList = document.getElementById('notificationList');
            if (notificationList && data.notifications) {
                displayNotifications(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

// Display notifications
function displayNotifications(notifications) {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    if (notifications.length === 0) {
        notificationList.innerHTML = '<div class="no-notifications">No new notifications</div>';
        return;
    }
    
    const html = notifications.map(notification => `
        <div class="notification-item ${notification.is_read ? 'read' : 'unread'}">
            <div class="notification-icon">
                <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
            </div>
            <div class="notification-content">
                <p class="notification-title">${notification.title}</p>
                <p class="notification-message">${notification.message}</p>
                <small class="notification-time">${formatTimeAgo(notification.created_at)}</small>
            </div>
        </div>
    `).join('');
    
    notificationList.innerHTML = html;
}

// Mark all notifications as read
function markAllNotificationsAsRead() {
    fetch('../api/notifications/mark-all-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update notification list
            const notificationItems = document.querySelectorAll('.notification-item');
            notificationItems.forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
            });
            
            // Update badge
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error marking notifications as read:', error);
    });
}

// Initialize real-time updates
function initializeRealTimeUpdates() {
    // Update dashboard data every 5 minutes
    setInterval(updateDashboardStats, 300000);
    
    // Update notifications every 2 minutes
    setInterval(loadNotificationCount, 120000);
}

// Update dashboard statistics
function updateDashboardStats() {
    fetch('../api/dashboard/stats.php')
        .then(response => response.json())
        .then(data => {
            updateStatsDisplay(data);
            updateCharts(data);
        })
        .catch(error => {
            console.error('Error updating dashboard stats:', error);
        });
}

// Update stats display
function updateStatCard(type, value) {
    const statCard = document.querySelector(`[data-stat="${type}"]`);
    if (statCard) {
        const valueElement = statCard.querySelector('.stat-content h3');
        if (valueElement) {
            valueElement.textContent = value;
        }
    }
}

// Update charts with new data
function updateCharts(stats) {
    // Update user distribution chart
    if (window.dashboardCharts && window.dashboardCharts.userChart && stats.userDistribution) {
        window.dashboardCharts.userChart.data.datasets[0].data = [
            stats.userDistribution.teachers || 0,
            stats.userDistribution.students || 0,
            stats.userDistribution.supervisors || 0
        ];
        window.dashboardCharts.userChart.update();
    }
    
    // Update activity chart
    if (window.dashboardCharts && window.dashboardCharts.activityChart && stats.activityData) {
        window.dashboardCharts.activityChart.data.datasets[0].data = stats.activityData;
        window.dashboardCharts.activityChart.update();
    }

    // Update assignment wave chart if provided: stats.assignments = { labels:[], values:[] }
    if (window.dashboardCharts && window.dashboardCharts.assignmentChart && stats.assignments) {
        const chA = window.dashboardCharts.assignmentChart;
        if (Array.isArray(stats.assignments.labels) && Array.isArray(stats.assignments.values)) {
            chA.data.labels = stats.assignments.labels;
            chA.data.datasets[0].data = stats.assignments.values;
            chA.update();
        }
    }

    // Update classes & subjects doughnut if provided: stats.classesSubjects = { labels:[], values:[] }
    if (window.dashboardCharts && window.dashboardCharts.classesSubjectsChart && stats.classesSubjects) {
        const cs = window.dashboardCharts.classesSubjectsChart;
        if (Array.isArray(stats.classesSubjects.labels) && Array.isArray(stats.classesSubjects.values)) {
            cs.data.labels = stats.classesSubjects.labels;
            cs.data.datasets[0].data = stats.classesSubjects.values;
            cs.update();
        }
    }
}

// Refresh dashboard data
function refreshDashboardData() {
    // Show loading state
    const refreshBtn = document.querySelector('.refresh-dashboard');
    if (refreshBtn) {
        const originalText = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;
        
        // Reload page after a short delay
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
}

// Export dashboard data
function exportDashboardData(format) {
    const url = `../api/dashboard/export.php?format=${format}`;
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = `dashboard-data.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Handle quick actions
function handleQuickAction(action) {
    switch (action) {
        case 'add-teacher':
            window.location.href = '../pages/teachers.php?action=add';
            break;
        case 'add-student':
            window.location.href = '../pages/students.php?action=add';
            break;
        case 'create-class':
            window.location.href = '../pages/classes.php?action=add';
            break;
        case 'new-announcement':
            window.location.href = '../pages/announcements.php?action=add';
            break;
        case 'system-settings':
            window.location.href = '../pages/settings.php';
            break;
        case 'generate-report':
            window.location.href = '../pages/reports.php';
            break;
        default:
            console.log('Unknown action:', action);
    }
}

// Refresh specific chart
function refreshChart(chartId) {
    if (window.dashboardCharts && window.dashboardCharts[chartId]) {
        // Reload chart data
        fetch(`../api/charts/${chartId}.php`)
            .then(response => response.json())
            .then(data => {
                window.dashboardCharts[chartId].data = data;
                window.dashboardCharts[chartId].update();
            })
            .catch(error => {
                console.error(`Error refreshing chart ${chartId}:`, error);
            });
    }
}

// Load dashboard data on page load
function loadDashboardData() {
    // Load initial stats
    updateDashboardStats();
    
    // Load recent activities
    loadRecentActivities();
    
    // Load upcoming events
    loadUpcomingEvents();
}

// Load recent activities
function loadRecentActivities() {
    fetch('../api/dashboard/recent-activities.php')
        .then(response => response.json())
        .then(data => {
            if (data.activities) {
                displayRecentActivities(data.activities);
            }
        })
        .catch(error => {
            console.error('Error loading recent activities:', error);
        });
}

// Display recent activities
function displayRecentActivities(activities) {
    const activityList = document.querySelector('.activity-list');
    if (!activityList) return;
    
    if (activities.length === 0) {
        activityList.innerHTML = '<div class="no-data">No recent activities</div>';
        return;
    }
    
    const html = activities.map(activity => `
        <div class="activity-item">
            <div class="activity-icon">
                <i class="fas fa-${getActivityIcon(activity.type)}"></i>
            </div>
            <div class="activity-content">
                <p><strong>${activity.user_name}</strong> ${activity.description}</p>
                <small>${formatTimeAgo(activity.created_at)}</small>
            </div>
        </div>
    `).join('');
    
    activityList.innerHTML = html;
}

// Load upcoming events
function loadUpcomingEvents() {
    fetch('../api/dashboard/upcoming-events.php')
        .then(response => response.json())
        .then(data => {
            if (data.events) {
                displayUpcomingEvents(data.events);
            }
        })
        .catch(error => {
            console.error('Error loading upcoming events:', error);
        });
}

// Display upcoming events
function displayUpcomingEvents(events) {
    const eventList = document.querySelector('.event-list');
    if (!eventList) return;
    
    if (events.length === 0) {
        eventList.innerHTML = '<div class="no-data">No upcoming events</div>';
        return;
    }
    
    const html = events.map(event => `
        <div class="event-item">
            <div class="event-info">
                <h4>${event.title}</h4>
                <p>${event.description}</p>
                <small>${formatDateTime(event.scheduled_date, event.scheduled_time)}</small>
            </div>
        </div>
    `).join('');
    
    eventList.innerHTML = html;
}

// Utility functions
function getNotificationIcon(type) {
    const icons = {
        'info': 'info-circle',
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'error': 'exclamation-circle'
    };
    return icons[type] || 'info-circle';
}

function getActivityIcon(type) {
    const icons = {
        'login': 'sign-in-alt',
        'logout': 'sign-out-alt',
        'create': 'plus',
        'update': 'edit',
        'delete': 'trash',
        'upload': 'upload'
    };
    return icons[type] || 'circle';
}

function formatTimeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diffInSeconds = Math.floor((now - past) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
}

function formatDateTime(date, time) {
    const dateObj = new Date(date + ' ' + time);
    return dateObj.toLocaleDateString() + ' at ' + dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for global use
window.Dashboard = {
    refreshDashboardData,
    exportDashboardData,
    refreshChart,
    updateDashboardStats
}; 