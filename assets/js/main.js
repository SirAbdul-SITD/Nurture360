// Rinda School Management System - Main JavaScript
// Common functionality and utility functions

// Global variables
let currentTheme = localStorage.getItem('theme') || 'light';
let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadUserPreferences();
});

// Initialize application
function initializeApp() {
    // Set theme
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    // Set sidebar state
    if (sidebarCollapsed) {
        document.body.classList.add('sidebar-collapsed');
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.add('collapsed');
        }
    }
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize modals
    initializeModals();
    
    // Initialize dropdowns
    initializeDropdowns();
    
    // Initialize tables
    initializeTables();
    
    // Initialize forms
    initializeForms();
}

// Setup event listeners
function setupEventListeners() {
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    // Sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Global click handler for closing dropdowns
    document.addEventListener('click', function(e) {
        closeAllDropdowns(e);
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
    
    // Window resize handler
    window.addEventListener('resize', handleWindowResize);
}

// Theme management
function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    localStorage.setItem('theme', currentTheme);
    
    // Update theme toggle button if exists
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.className = currentTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
    }
    
    // Trigger custom event
    document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: currentTheme } }));
}

function setTheme(theme) {
    currentTheme = theme;
    document.documentElement.setAttribute('data-theme', currentTheme);
    localStorage.setItem('theme', currentTheme);
}

// Sidebar management
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-collapsed');
        sidebarCollapsed = !sidebarCollapsed;
        localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
    }
}

function collapseSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && !sidebar.classList.contains('collapsed')) {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
        sidebarCollapsed = true;
        localStorage.setItem('sidebarCollapsed', 'true');
    }
}

function expandSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        document.body.classList.remove('sidebar-collapsed');
        sidebarCollapsed = false;
        localStorage.setItem('sidebarCollapsed', 'false');
    }
}

// Tooltip initialization
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltipText = e.target.getAttribute('data-tooltip');
    if (!tooltipText) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    e.target._tooltip = tooltip;
}

function hideTooltip(e) {
    if (e.target._tooltip) {
        e.target._tooltip.remove();
        e.target._tooltip = null;
    }
}

// Modal management
function initializeModals() {
    // Close modal on backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            closeModal(e.target.closest('.modal'));
        }
    });
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.active');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });

    // Open modal on any element with [data-open-modal]
    document.addEventListener('click', function(e) {
        const opener = e.target.closest('[data-open-modal]');
        if (!opener) return;
        e.preventDefault();
        const id = opener.getAttribute('data-open-modal');
        if (!id) return;
        // Prefer page-specific openModal<Name> if defined by components/modal.php
        const fnName = 'openModal' + id.charAt(0).toUpperCase() + id.slice(1);
        const fn = window[fnName];
        if (typeof fn === 'function') {
            try { fn(); } catch (_) { /* noop */ }
            return;
        }
        // Fallback to generic opener
        openModal(id);
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus first input if exists
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    }
}

function closeModal(modal) {
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal.active');
    modals.forEach(modal => {
        closeModal(modal);
    });
}

// Dropdown management
function initializeDropdowns() {
    const dropdownToggles = document.querySelectorAll('[data-dropdown]');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdownId = this.getAttribute('data-dropdown');
            const dropdown = document.getElementById(dropdownId);
            
            if (dropdown) {
                // Close other dropdowns
                closeOtherDropdowns(dropdown);
                
                // Toggle current dropdown
                dropdown.classList.toggle('active');
            }
        });
    });
}

function closeOtherDropdowns(currentDropdown) {
    const allDropdowns = document.querySelectorAll('.dropdown.active');
    allDropdowns.forEach(dropdown => {
        if (dropdown !== currentDropdown) {
            dropdown.classList.remove('active');
        }
    });
}

function closeAllDropdowns(event) {
    const dropdowns = document.querySelectorAll('.dropdown.active');
    dropdowns.forEach(dropdown => {
        if (!dropdown.contains(event.target)) {
            dropdown.classList.remove('active');
        }
    });
}

// Table initialization
function initializeTables() {
    const tables = document.querySelectorAll('.table-container');
    tables.forEach(table => {
        // Add sort functionality if data-sortable attribute exists
        if (table.hasAttribute('data-sortable')) {
            initializeSortableTable(table);
        }
        
        // Add search functionality if data-searchable attribute exists
        if (table.hasAttribute('data-searchable')) {
            initializeSearchableTable(table);
        }
        
        // Add pagination if data-paginated attribute exists
        if (table.hasAttribute('data-paginated')) {
            initializePaginatedTable(table);
        }
    });
}

function initializeSortableTable(table) {
    const headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-sort');
            const direction = this.getAttribute('data-direction') === 'asc' ? 'desc' : 'asc';
            
            // Update all headers
            headers.forEach(h => h.setAttribute('data-direction', ''));
            this.setAttribute('data-direction', direction);
            
            // Sort table
            sortTable(table, column, direction);
        });
    });
}

function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`td[data-${column}]`).textContent;
        const bValue = b.querySelector(`td[data-${column}]`).textContent;
        
        if (direction === 'asc') {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

// Form initialization
function initializeForms() {
    const forms = document.querySelectorAll('form[data-ajax]');
    forms.forEach(form => {
        form.addEventListener('submit', handleAjaxFormSubmit);
    });
    
    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"][data-strength]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', checkPasswordStrength);
    });
    
    // Form validation
    const validateForms = document.querySelectorAll('form[data-validate]');
    validateForms.forEach(form => {
        form.addEventListener('submit', validateForm);
    });
}

function handleAjaxFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method || 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Success!', 'success');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function checkPasswordStrength(e) {
    const password = e.target.value;
    const strengthBar = e.target.parentNode.querySelector('.password-strength-bar');
    
    if (!strengthBar) return;
    
    let strength = 0;
    let className = 'password-strength-weak';
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    switch (strength) {
        case 0:
        case 1:
            className = 'password-strength-weak';
            break;
        case 2:
            className = 'password-strength-medium';
            break;
        case 3:
            className = 'password-strength-strong';
            break;
        case 4:
        case 5:
            className = 'password-strength-very-strong';
            break;
    }
    
    strengthBar.className = `password-strength-bar ${className}`;
}

function validateForm(e) {
    const form = e.target;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(input);
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        showNotification('Please fill in all required fields', 'error');
    }
    
    return isValid;
}

function showFieldError(field, message) {
    const formGroup = field.closest('.form-group');
    if (formGroup) {
        formGroup.classList.add('error');
        
        let errorElement = formGroup.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            formGroup.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }
}

function clearFieldError(field) {
    const formGroup = field.closest('.form-group');
    if (formGroup) {
        formGroup.classList.remove('error');
        const errorElement = formGroup.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
    }
}

// Notification system
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto hide
    if (duration > 0) {
        setTimeout(() => {
            hideNotification(notification);
        }, duration);
    }
    
    // Close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        hideNotification(notification);
    });
}

function hideNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        case 'info': return 'info-circle';
        default: return 'info-circle';
    }
}

// Utility functions
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

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

function formatDate(date, format = 'YYYY-MM-DD') {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day);
}

function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

// Keyboard shortcuts
function handleKeyboardShortcuts(e) {
    // Ctrl/Cmd + K: Global search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + B: Toggle sidebar
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        toggleSidebar();
    }
    
    // Ctrl/Cmd + Shift + T: Toggle theme
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
        e.preventDefault();
        toggleTheme();
    }
}

// Window resize handler
const handleWindowResize = debounce(function() {
    if (window.innerWidth <= 768) {
        collapseSidebar();
    }
}, 250);

// Load user preferences
function loadUserPreferences() {
    // Load theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        setTheme(savedTheme);
    }
    
    // Load sidebar state
    const savedSidebarState = localStorage.getItem('sidebarCollapsed');
    if (savedSidebarState === 'true') {
        collapseSidebar();
    }
}

// Export functions for global use
window.RindaApp = {
    toggleTheme,
    setTheme,
    toggleSidebar,
    openModal,
    closeModal,
    showNotification,
    formatDate,
    formatCurrency,
    generateId
}; 