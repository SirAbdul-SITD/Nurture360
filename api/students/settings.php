<?php
// Use global app configuration and shared DB connection
require_once __DIR__ . '/../config/config.php';

// Optional: control error display locally for students area
ini_set('display_errors', 0);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);



// Check if user ID and email session variables are not set
if (!isset($_SESSION['student_id']) || !isset($_SESSION['email'])) {
	$_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
	// Redirect to login page
	header("Location: auth/");
	exit();
}

// Obtain shared PDO instance from global config
$pdo = getDBConnection();

// Load app settings: name, logo, favicon, theme (requires $pdo)
$app_name = 'Nurture360°';
$logo_url = 'assets/images/logo.png';
$has_logo = false;
$favicon_url = 'assets/images/favicon.ico';
// Default brand display flags (override after DB read)
$brand_mode = 'both';
$show_logo = false; // depends on $has_logo and brand_mode
$show_name = true;  // safe default to show name
// Theme defaults
$theme_primary_color = '#2563eb'; // blue fallback
$theme_secondary_color = '#1e40af';
$theme_accent_color = '#3b82f6';
$theme_mode = 'light';
try {
    $stmtSS = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('app_name','app_logo','app_favicon','brand_display','theme_primary_color','theme_secondary_color','theme_accent_color','theme_mode')");
    $stmtSS->execute();
    $settings = $stmtSS->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($settings['app_name'])) {
        $app_name = trim((string)$settings['app_name']);
    }
    // helper to resolve asset paths
    $resolveAsset = function ($val, $defaultPathRelativeToStudents) {
        $val = trim((string)$val);
        if ($val === '') return $defaultPathRelativeToStudents;
        if (preg_match('/^https?:\/\//i', $val)) return $val;
        $rel = ltrim($val, '/');
        $fs = __DIR__ . '/../' . $rel;
        if (is_file($fs)) return '../' . $rel;
        // try under uploads/system/
        $rel2 = 'uploads/system/' . basename($val);
        $fs2 = __DIR__ . '/../' . $rel2;
        if (is_file($fs2)) return '../' . $rel2;
        return $defaultPathRelativeToStudents;
    };
    if (!empty($settings['app_logo'])) {
        $candidate = trim((string)$settings['app_logo']);
        $logo_url = $resolveAsset($candidate, 'assets/images/logo.png');
        // determine availability
        if (preg_match('/^https?:\/\//i', $candidate)) {
            $has_logo = true; // assume reachable external URL
        } else {
            $rel = ltrim($candidate, '/');
            $fs = __DIR__ . '/../' . $rel;
            $rel2 = 'uploads/system/' . basename($candidate);
            $fs2 = __DIR__ . '/../' . $rel2;
            $has_logo = is_file($fs) || is_file($fs2);
        }
    } else {
        // also try a common upload name
        $logo_url = $resolveAsset('logo.png', 'assets/images/logo.png');
        $fsCommon = __DIR__ . '/../uploads/system/logo.png';
        $has_logo = is_file($fsCommon);
    }
    if (!empty($settings['app_favicon'])) {
        $favicon_url = $resolveAsset($settings['app_favicon'], 'assets/images/favicon.ico');
    } else {
        $favicon_url = $resolveAsset('favicon.ico', 'assets/images/favicon.ico');
    }
    // brand display mode
    if (!empty($settings['brand_display'])) {
        $val = strtolower(trim((string)$settings['brand_display']));
        if (in_array($val, ['logo','name','both'], true)) {
            $brand_mode = $val;
        }
    }
    // compute what to show
    $show_logo = ($brand_mode === 'logo' || $brand_mode === 'both') && !empty($has_logo);
    // Always show name if mode demands it OR if logo-only but no logo available
    $show_name = ($brand_mode === 'name' || $brand_mode === 'both') || (($brand_mode === 'logo') && empty($has_logo));

    // Theme colors
    if (!empty($settings['theme_primary_color'])) {
        $theme_primary_color = trim((string)$settings['theme_primary_color']);
    }
    if (!empty($settings['theme_secondary_color'])) {
        $theme_secondary_color = trim((string)$settings['theme_secondary_color']);
    }
    if (!empty($settings['theme_accent_color'])) {
        $theme_accent_color = trim((string)$settings['theme_accent_color']);
    }
    if (!empty($settings['theme_mode']) && in_array($settings['theme_mode'], ['light','dark'], true)) {
        $theme_mode = $settings['theme_mode'];
    }
} catch (Throwable $e) {
    // keep defaults on error
}

$student_id = $_SESSION['student_id'];
$student_email = $_SESSION['email'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Load all classes the student is enrolled in (active)
$available_classes = [];
try {
    $stmtEn = $pdo->prepare("SELECT c.id AS class_id, c.class_name FROM student_enrollments se JOIN classes c ON se.class_id = c.id WHERE se.student_id = ? AND se.status = 'active' ORDER BY se.id DESC");
    $stmtEn->execute([$student_id]);
    $available_classes = $stmtEn->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $available_classes = [];
}

// Determine requested class (from POST/GET), then session override, else latest active enrollment
$requested_class_id = null;
if (isset($_POST['class_id'])) {
    $requested_class_id = (int)$_POST['class_id'];
} elseif (isset($_GET['class_id'])) {
    $requested_class_id = (int)$_GET['class_id'];
}

// Helper to verify the class is in student's enrollments
$is_valid_class = function ($cid) use ($available_classes) {
    foreach ($available_classes as $ac) {
        if ((int)$ac['class_id'] === (int)$cid) return true;
    }
    return false;
};

if ($requested_class_id && $is_valid_class($requested_class_id)) {
    $_SESSION['selected_class_id'] = $requested_class_id;
}

// Fallback chain: selected_class_id -> legacy session class_id -> latest enrollment
$active_class_id = null;
if (!empty($_SESSION['selected_class_id']) && $is_valid_class($_SESSION['selected_class_id'])) {
    $active_class_id = (int)$_SESSION['selected_class_id'];
} elseif (!empty($_SESSION['class_id']) && $is_valid_class($_SESSION['class_id'])) {
    $active_class_id = (int)$_SESSION['class_id'];
} elseif (!empty($available_classes)) {
    $active_class_id = (int)$available_classes[0]['class_id']; // latest by ORDER BY se.id DESC
}

// Persist legacy key for downstream code compatibility
if ($active_class_id) {
    $_SESSION['class_id'] = $active_class_id;
}

$my_class = $_SESSION['class_id'] ?? null;
// Display the proper class name if available; otherwise fallback to id
if ($my_class) {
    $title = null;
    foreach ($available_classes as $ac) {
        if ((int)$ac['class_id'] === (int)$my_class) {
            $title = $ac['class_name'] ?? null;
            break;
        }
    }
    $my_class_Title = $title ?: ('Class ' . (int)$my_class);
} else {
    $my_class_Title = 'No Class Selected';
}

// Resolve profile image URL
$profile_image_url = 'assets/images/profile/male/1.jpg'; // default avatar path relative to students/
try {
    $stmtPI = $pdo->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
    $stmtPI->execute([$student_id]);
    $pi = $stmtPI->fetch(PDO::FETCH_ASSOC);
    if ($pi && !empty($pi['profile_image'])) {
        $piVal = trim((string)$pi['profile_image']);
        if (preg_match('/^https?:\/\//i', $piVal)) {
            // external URL
            $profile_image_url = $piVal;
        } else {
            // attempt to resolve local path
            $rel = ltrim($piVal, '/');
            $fsCandidate = __DIR__ . '/../' . $rel; // e.g., uploads/students/xyz.jpg
            if (is_file($fsCandidate)) {
                $profile_image_url = '../' . $rel; // web path from students/
            } else {
                // assume it's a filename under uploads/students/
                $rel2 = 'uploads/students/' . basename($piVal);
                $fs2 = __DIR__ . '/../' . $rel2;
                if (is_file($fs2)) {
                    $profile_image_url = '../' . $rel2;
                }
            }
        }
    }
} catch (Throwable $e) {
    // keep default image on error
}


// $query_session = "SELECT * FROM `general_settings`";
// $stmt_session = $pdo->query($query_session);
// $session_row = $stmt_session->fetch(PDO::FETCH_ASSOC);
// $session = $session_row['curr_session'];
// $curr_session = $session_row['curr_session'];
// $term = $session_row['term_id'];
// $term_name = $session_row['curr_term'];
// $school_name = $session_row['school_name'];
// $school_email = $session_row['email'];
// $school_address = $session_row['address'];
// $days_opened = $session_row['days_opened'];


// $user_id = $_SESSION['user_id'];
// $full_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
// $account_type = $_SESSION['account_type'];
// $gender = $_SESSION['gender'];



// $api_key = 'uR7zBKWFD1nyU63AWTvry6wNBFkJfRkCfz8LnuBf';
