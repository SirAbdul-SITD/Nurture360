<?php
// Rinda School Management System - Configuration
// SuperAdmin Dashboard Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'rinda_school');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Rinda SMS');
define('APP_LOGO_ALT', 'Rinda SMS Logo');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/rinda');
define('APP_TIMEZONE', 'Africa/Lagos');
define('APP_CURRENCY', 'NGN');
define('APP_LANGUAGE', 'en');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Configuration
define('MAX_FILE_SIZE', 33554432); // 32MB
// Expanded to support audio and common resource formats
define('ALLOWED_FILE_TYPES', [
    // Documents
    'pdf','doc','docx','ppt','pptx','xls','xlsx','txt','rtf',
    // Images
    'jpg','jpeg','png','gif','webp',
    // Video
    'mp4','avi','mov','mkv','webm',
    // Audio
    'mp3','wav','m4a','aac','ogg'
]);

// Email Configuration (Demo - Replace with real SMTP)
define('SMTP_HOST', 'smtp.mailtrap.io');
define('SMTP_PORT', 2525);
define('SMTP_USERNAME', 'demo_user');
define('SMTP_PASSWORD', 'demo_pass');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'noreply@rinda.edu');
define('FROM_NAME', 'Rinda School System');

// Theme Configuration
define('DEFAULT_THEME', 'blue');
define('DEFAULT_PRIMARY_COLOR', '#2563eb');
define('DEFAULT_SECONDARY_COLOR', '#1e40af');
define('DEFAULT_ACCENT_COLOR', '#3b82f6');

// Database Connection
function getDBConnection() {
    try {
        // Use XAMPP MySQL socket for Linux
        $dsn = "mysql:unix_socket=/opt/lampp/var/mysql/mysql.sock;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Utility Functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url) {
    // Handle both absolute and relative URLs
    if (strpos($url, 'http') === 0) {
        header("Location: $url");
    } else {
        // For relative URLs, ensure they work from any subdirectory
        header("Location: $url");
    }
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function isSupervisor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor';
}

/**
 * Return the current authenticated user's ID from session, or null.
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Log an action to the system_logs table. Swallows errors to avoid breaking flows.
 */
function logAction($userId, $action, $description = '') {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $userId,
            (string)$action,
            (string)$description,
            $ip,
            $ua
        ]);
    } catch (Throwable $e) {
        // Intentionally ignore logging failures
    }
}

/**
 * Fetch system settings from the database.
 * When $onlyPublic is true, returns only settings marked is_public=1.
 * Returns associative array: [setting_key => setting_value].
 */
function getSystemSettings(bool $onlyPublic = false): array {
    try {
        $pdo = getDBConnection();
        $sql = 'SELECT setting_key, setting_value FROM system_settings';
        if ($onlyPublic) {
            $sql .= ' WHERE is_public = 1';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Throwable $e) {
        // On failure, fall back to sensible defaults already defined as constants
        return [
            'app_name' => APP_NAME,
        ];
    }
}

/**
 * Convenience to fetch a single setting with a default.
 */
function getSetting(string $key, $default = null, bool $onlyPublic = false) {
    $settings = getSystemSettings($onlyPublic);
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?> 