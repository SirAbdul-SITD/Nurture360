<?php
/**
 * Teacher Detail Component
 * 
 * @param array $teacher Teacher data
 * @param array $options Display options
 */
function renderTeacherDetail($teacher, $options = []) {
    $defaults = [
        'showActions' => true,
        'showAssignments' => true,
        'showHistory' => true,
        'compact' => false
    ];
    $options = array_merge($defaults, $options);
    
    $statusClass = $teacher['is_active'] ? 'status-active' : 'status-inactive';
    $statusText = $teacher['is_active'] ? 'Active' : 'Inactive';

    $verified = !empty($teacher['email_verified']);
    $badgeClass = $verified ? 'status-verified' : 'status-unverified';
    $badgeText = $verified ? 'Email Verified' : 'Email Not Verified';

    echo '<div class="teacher-detail' . ($options['compact'] ? ' compact' : '') . '">';

    echo '<div class="teacher-grid">';

    // Left profile card
    echo '  <aside class="profile-card">';
    echo '    <div class="profile-card-inner">';
    echo '      <div class="avatar-wrap">';
    if (!empty($teacher['profile_image'])) {
        echo '        <img class="avatar-img" src="' . htmlspecialchars($teacher['profile_image']) . '" alt="' . htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) . ' avatar" />';
    } else {
        echo '        <div class="avatar-fallback"><i class="fas fa-user"></i></div>';
    }
    echo '      </div>';
    echo '      <div class="profile-text">';
    echo '        <h2 class="profile-name">' . htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) . '</h2>';
    echo '        <p class="profile-sub">Teacher</p>';
    echo '        <div class="badge-row">';
    echo '          <span class="status-badge ' . $statusClass . '">' . $statusText . '</span>';
    echo '          <span id="emailVerifiedBadge_' . $teacher['id'] . '" class="status-badge ' . $badgeClass . '">' . $badgeText . '</span>';
    echo '        </div>';
    if (!$verified) {
        echo '        <button id="verifyEmailBtn_' . $teacher['id'] . '" type="button" class="btn btn-primary btn-block" onclick="verifyTeacherEmail(' . $teacher['id'] . ')"><i class="fas fa-check-circle"></i> Verify Email</button>';
    }
    echo '      </div>';
    echo '      <ul class="social-list">';
    echo '        <li><i class="fas fa-globe"></i><span>Website</span><span class="value">Not set</span></li>';
    echo '        <li><i class="fab fa-twitter"></i><span>Twitter</span><span class="value">' . htmlspecialchars($teacher['username']) . '</span></li>';
    echo '        <li><i class="fab fa-facebook"></i><span>Facebook</span><span class="value">Not set</span></li>';
    echo '        <li><i class="fab fa-instagram"></i><span>Instagram</span><span class="value">Not set</span></li>';
    echo '      </ul>';
    echo '    </div>';
    echo '  </aside>';

    // Right content area
    echo '  <section class="content-col">';
    echo '    <div class="info-table-card">';
    echo '      <div class="info-row"><span>Full Name</span><span>' . htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) . '</span></div>';
    echo '      <div class="info-row"><span>Email</span><span>' . htmlspecialchars($teacher['email']) . '</span></div>';
    echo '      <div class="info-row"><span>Phone</span><span>' . (isset($teacher['phone']) && $teacher['phone'] ? htmlspecialchars($teacher['phone']) : 'Not provided') . '</span></div>';
    echo '      <div class="info-row"><span>Mobile</span><span>' . (isset($teacher['phone']) && $teacher['phone'] ? htmlspecialchars($teacher['phone']) : 'Not provided') . '</span></div>';
    echo '      <div class="info-row"><span>Address</span><span>Not provided</span></div>';
    echo '    </div>';

    if ($options['showAssignments']) {
        // Server-side fetch of assignments to mirror supervisor-detail
        $classesSSR = [];
        $subjectsSSR = [];
        try {
            if (function_exists('getDBConnection')) {
                $pdo = getDBConnection();
                // Classes
                $stmt = $pdo->prepare(
                    "SELECT c.id, c.class_name, c.class_code, c.grade_level, c.academic_year, c.is_active
                     FROM teacher_assignments ta
                     INNER JOIN classes c ON c.id = ta.class_id
                     WHERE ta.teacher_id = ? AND ta.is_active = 1"
                );
                $stmt->execute([$teacher['id']]);
                $classesSSR = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                // Subjects
                $stmt2 = $pdo->prepare(
                    "SELECT s.id, s.subject_name, s.subject_code, s.is_active
                     FROM teacher_assignments ta
                     INNER JOIN subjects s ON s.id = ta.subject_id
                     WHERE ta.teacher_id = ? AND ta.is_active = 1"
                );
                $stmt2->execute([$teacher['id']]);
                $subjectsSSR = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Throwable $e) {
            // Silent fail; JS will try to hydrate
        }

        echo '    <div class="metrics-grid">';
        // Classes panel
        echo '      <div class="metric-card" id="classSection_' . $teacher['id'] . '">';
        echo '        <div class="metric-header">Class Assignments</div>';
        echo '        <div class="metric-content" id="classAssignments_' . $teacher['id'] . '">';
        if (!empty($classesSSR)) {
            foreach ($classesSSR as $cls) {
                $clsName = htmlspecialchars($cls['class_name'] ?? '-');
                $grade   = isset($cls['grade_level']) ? ('Grade ' . (int)$cls['grade_level']) : '';
                $code    = htmlspecialchars($cls['class_code'] ?? '');
                $year    = htmlspecialchars($cls['academic_year'] ?? '');
                $active  = !empty($cls['is_active']);
                $badge   = $active ? '<span class="status-badge status-active">Active</span>' : '<span class="status-badge status-inactive">Inactive</span>';
                echo '          <div class="assignments-item">';
                echo '            <div class="meta">';
                echo '              <div class="title">' . $clsName . ' <small>(' . $grade . ')</small></div>';
                echo '              <div class="sub">Code: ' . $code . ' • Year: ' . $year . '</div>';
                echo '            </div>';
                echo '            <div class="inline-actions">' . $badge . '</div>';
                echo '          </div>';
            }
        } else {
            echo '          <div class="placeholder">No classes assigned.</div>';
        }
        echo '        </div>';
        echo '      </div>';
        // Subjects panel
        echo '      <div class="metric-card" id="subjectSection_' . $teacher['id'] . '">';
        echo '        <div class="metric-header">Subject Assignments</div>';
        echo '        <div class="metric-content" id="subjectAssignments_' . $teacher['id'] . '">';
        if (!empty($subjectsSSR)) {
            foreach ($subjectsSSR as $sub) {
                $subName = htmlspecialchars($sub['subject_name'] ?? '-');
                $code    = htmlspecialchars($sub['subject_code'] ?? '');
                $active  = !empty($sub['is_active']);
                $badge   = $active ? '<span class="status-badge status-active">Active</span>' : '<span class="status-badge status-inactive">Inactive</span>';
                echo '          <div class="assignments-item">';
                echo '            <div class="meta">';
                echo '              <div class="title">' . $subName . '</div>';
                echo '              <div class="sub">Code: ' . $code . '</div>';
                echo '            </div>';
                echo '            <div class="inline-actions">' . $badge . '</div>';
                echo '          </div>';
            }
        } else {
            echo '          <div class="placeholder">No subjects assigned.</div>';
        }
        echo '        </div>';
        echo '      </div>';
        echo '    </div>';
    }

    // Hidden activity container to satisfy JS without changing layout
    echo '    <div id="teacherActivity_' . $teacher['id'] . '" style="display:none"></div>';
    echo '  </section>';

    echo '</div>'; // teacher-grid

    echo '</div>'; // teacher-detail
    
    // Emit JS function definitions needed on this page
    if (function_exists('loadTeacherAssignments')) { loadTeacherAssignments($teacher['id']); }
    if (function_exists('loadTeacherActivity')) { loadTeacherActivity($teacher['id']); }

    // Add JavaScript to load dynamic content after functions are defined
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '    if (typeof loadTeacherAssignments === "function") loadTeacherAssignments(' . $teacher['id'] . ');';
    echo '    if (typeof loadTeacherActivity === "function") loadTeacherActivity(' . $teacher['id'] . ');';
    echo '});';
    echo '</script>';
    // Emit verification JS once on page
    if (function_exists('emitVerifyTeacherEmailJS')) { emitVerifyTeacherEmailJS(); }
}

/**
 * Load teacher class assignments via AJAX
 */
function loadTeacherAssignments($teacherId) {
    echo '<script>';
    echo 'function loadTeacherAssignments(teacherId) {';
    echo '    console.log("[loadTeacherAssignments] fetching assignments for teacher:", teacherId);';
    echo '    fetch("../api/teacher_assignments.php?teacher_id=" + teacherId)';
    echo '        .then(async response => {';
    echo '            let payload = null;';
    echo '            console.log("[loadTeacherAssignments] HTTP status:", response.status);';
    echo '            try { payload = await response.json(); } catch (e) { payload = { success:false, error:"Invalid JSON from server" }; }';
    echo '            if (!response.ok) {';
    echo '                throw new Error(payload && (payload.error || payload.message) ? (payload.error || payload.message) : ("HTTP " + response.status));';
    echo '            }';
    echo '            console.log("[loadTeacherAssignments] parsed payload:", payload);';
    echo '            return payload;';
    echo '        })';
    echo '        .then(data => {';
    echo '            const classContainer = document.getElementById("classAssignments_" + teacherId);';
    echo '            const classSection = document.getElementById("classSection_" + teacherId);';
    echo '            const subjectContainer = document.getElementById("subjectAssignments_" + teacherId);';
    echo '            const subjectSection = document.getElementById("subjectSection_" + teacherId);';
    echo '            const hasSuccess = data && (data.success === true || (typeof data.success === "undefined" && (data.classes || data.subjects)));';
    echo '            console.log("[loadTeacherAssignments] containers:", {classContainer, subjectContainer, classSection, subjectSection});';
    echo '            console.log("[loadTeacherAssignments] hasSuccess:", hasSuccess, "classes:", data && data.classes, "subjects:", data && data.subjects);';
    echo '            if (!hasSuccess) {';
    echo '                const msg = (data && (data.error || data.message)) ? (data.error || data.message) : "Unable to load assignments";';
    echo '                if (classContainer) classContainer.innerHTML = "<p class=\\\"error\\\">" + msg + "</p>";';
    echo '                if (subjectContainer) subjectContainer.innerHTML = "<p class=\\\"error\\\">" + msg + "</p>";';
    echo '                if (classSection) classSection.style.display = "";';
    echo '                if (subjectSection) subjectSection.style.display = "";';
    echo '                return;';
    echo '            }';
    echo '            // Classes';
    echo '            if (data.classes && data.classes.length > 0) {';
    echo '                let html = "";';
    echo '                data.classes.forEach(cls => {';
    echo '                    const grade = (cls.grade_level !== undefined && cls.grade_level !== null) ? cls.grade_level : "-";';
    echo '                    const students = (cls.student_count !== undefined && cls.student_count !== null) ? cls.student_count : "-";';
    echo '                    html += "\n<div class=\\\"assignment-item\\\">" +
         "\n  <div class=\\\"assignment-info\\\">" +
         "\n    <h4>" + cls.class_name + "</h4>" +
         "\n    <p>Grade: " + grade + " | Students: " + students + "</p>" +
         "\n  </div>" +
         "\n  <div class=\\\"assignment-actions\\\">" +
         "\n    <button class=\\\"btn btn-sm btn-primary\\\" onclick=\\\"viewClass(" + cls.id + ")\\\">View</button>" +
         "\n    <button class=\\\"btn btn-sm btn-error\\\" onclick=\\\"removeClassAssignment(" + teacherId + ", " + cls.id + ")\\\">Remove</button>" +
         "\n  </div>" +
         "\n</div>";';
    echo '                });';
    echo '                console.log("[loadTeacherAssignments] rendering classes count:", data.classes.length);';
    echo '                classContainer.innerHTML = html;';
    echo '                if (classSection) classSection.style.display = "";';
    echo '            } else {';
    echo '                classContainer.innerHTML = "<p class=\"no-data\">No class assignments</p>";';
    echo '                if (classSection) classSection.style.display = "";';
    echo '            }';
    echo '            // Subjects';
    echo '            if (data.subjects && data.subjects.length > 0) {';
    echo '                let html = "";';
    echo '                data.subjects.forEach(subject => {';
    echo '                    const code = (subject.subject_code !== undefined && subject.subject_code !== null && subject.subject_code !== "") ? subject.subject_code : "-";';
    echo '                    html += "\n<div class=\\\"assignment-item\\\">" +
         "\n  <div class=\\\"assignment-info\\\">" +
         "\n    <h4>" + subject.subject_name + "</h4>" +
         "\n    <p>Code: " + code + "</p>" +
         "\n  </div>" +
         "\n  <div class=\\\"assignment-actions\\\">" +
         "\n    <button class=\\\"btn btn-sm btn-primary\\\" onclick=\\\"viewSubject(" + subject.id + ")\\\">View</button>" +
         "\n    <button class=\\\"btn btn-sm btn-error\\\" onclick=\\\"removeSubjectAssignment(" + teacherId + ", " + subject.id + ")\\\">Remove</button>" +
         "\n  </div>" +
         "\n</div>";';
    echo '                });';
    echo '                console.log("[loadTeacherAssignments] rendering subjects count:", data.subjects.length);';
    echo '                subjectContainer.innerHTML = html;';
    echo '                if (subjectSection) subjectSection.style.display = "";';
    echo '            } else {';
    echo '                subjectContainer.innerHTML = "<p class=\"no-data\">No subject assignments</p>";';
    echo '                if (subjectSection) subjectSection.style.display = "";';
    echo '            }';
    echo '        })';
    echo '        .catch(error => {';
    echo '            console.error("[loadTeacherAssignments] Error loading assignments:", error);';
    echo '            const classContainer = document.getElementById("classAssignments_" + teacherId);';
    echo '            const subjectContainer = document.getElementById("subjectAssignments_" + teacherId);';
    echo '            const classSection = document.getElementById("classSection_" + teacherId);';
    echo '            const subjectSection = document.getElementById("subjectSection_" + teacherId);';
    echo '            const errMsg = (error && error.message) ? error.message : "Failed to load assignments";';
    echo '            if (classContainer) classContainer.innerHTML = "<p class=\\\"error\\\">" + errMsg + "</p>";';
    echo '            if (subjectContainer) subjectContainer.innerHTML = "<p class=\\\"error\\\">" + errMsg + "</p>";';
    echo '            if (classSection) classSection.style.display = "";';
    echo '            if (subjectSection) subjectSection.style.display = "";';
    echo '        });';
    echo '}';
    echo '</script>';
}

/**
 * Load teacher activity via AJAX
 */
function loadTeacherActivity($teacherId) {
    echo '<script>';
    echo 'function loadTeacherActivity(teacherId) {';
    echo '    fetch("../api/teacher_activity.php?teacher_id=" + teacherId)';
    echo '        .then(response => response.json())';
    echo '        .then(data => {';
    echo '            const activityContainer = document.getElementById("teacherActivity_" + teacherId);';
    echo '            if (data.activities && data.activities.length > 0) {';
    echo '                let html = "";';
    echo '                data.activities.forEach(activity => {';
    echo '                    html += \'<div class="activity-item">\';';
    echo '                    html += \'<div class="activity-icon">\';';
    echo '                    html += \'<i class="fas \' + getActivityIcon(activity.action) + \'"></i>\';';
    echo '                    html += \'</div>\';';
    echo '                    html += \'<div class="activity-content">\';';
    echo '                    html += \'<p>\' + activity.description + \'</p>\';';
    echo '                    html += \'<small>\' + formatDate(activity.created_at) + \'</small>\';';
    echo '                    html += \'</div>\';';
    echo '                    html += \'</div>\';';
    echo '                });';
    echo '                activityContainer.innerHTML = html;';
    echo '            } else {';
    echo '                activityContainer.innerHTML = \'<p class="no-data">No recent activity</p>\';';
    echo '            }';
    echo '        })';
    echo '        .catch(error => {';
    echo '            console.error("Error loading activity:", error);';
    echo '            document.getElementById("teacherActivity_" + teacherId).innerHTML = \'<p class="error">Error loading activity</p>\';';
    echo '        });';
    echo '}';
    echo '';
    echo 'function getActivityIcon(action) {';
    echo '    const icons = {';
    echo '        "login": "fa-sign-in-alt",';
    echo '        "logout": "fa-sign-out-alt",';
    echo '        "grade_update": "fa-edit",';
    echo '        "attendance": "fa-calendar-check",';
    echo '        "test_created": "fa-file-alt",';
    echo '        "announcement": "fa-bullhorn"';
    echo '    };';
    echo '    return icons[action] || "fa-circle";';
    echo '}';
    echo '';
    echo 'function formatDate(dateString) {';
    echo '    const date = new Date(dateString);';
    echo '    const now = new Date();';
    echo '    const diffTime = Math.abs(now - date);';
    echo '    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));';
    echo '    ';
    echo '    if (diffDays === 1) return "Today";';
    echo '    if (diffDays === 2) return "Yesterday";';
    echo '    if (diffDays <= 7) return diffDays + " days ago";';
    echo '    return date.toLocaleDateString();';
    echo '}';
    echo '</script>';
}

/**
 * Emit JS for verifying teacher email and updating UI
 */
function emitVerifyTeacherEmailJS() {
    echo '<script>';
    echo 'function verifyTeacherEmail(teacherId) {';
    echo '  const btn = document.getElementById("verifyEmailBtn_" + teacherId);';
    echo '  const badge = document.getElementById("emailVerifiedBadge_" + teacherId);';
    echo '  if (btn) { btn.disabled = true; btn.innerHTML = "<i class=\\"fas fa-spinner fa-spin\\"></i> Verifying..."; }';
    echo '  fetch("../api/verify_teacher_email.php", {';
    echo '    method: "POST",';
    echo '    headers: { "Content-Type": "application/json" },';
    echo '    body: JSON.stringify({ teacher_id: teacherId })';
    echo '  })';
    echo '  .then(r => r.json())';
    echo '  .then(data => {';
    echo '    if (data && data.success) {';
    echo '      if (badge) {';
    echo '        badge.textContent = "Email Verified";';
    echo '        badge.className = "status-badge status-verified";';
    echo '      }';
    echo '      if (btn && btn.parentElement) { btn.parentElement.removeChild(btn); }';
    echo '      if (typeof showNotification === "function") showNotification("Email verified successfully", "success");';
    echo '    } else {';
    echo '      const msg = (data && (data.message || data.error)) ? (data.message || data.error) : "Verification failed";';
    echo '      if (typeof showNotification === "function") showNotification(msg, "error");';
    echo '      if (btn) { btn.disabled = false; btn.innerHTML = "<i class=\\"fas fa-check-circle\\"></i> Verify Email"; }';
    echo '    }';
    echo '  })';
    echo '  .catch(err => {';
    echo '    console.error("verifyTeacherEmail error", err);';
    echo '    if (typeof showNotification === "function") showNotification("Server error. Try again.", "error");';
    echo '    if (btn) { btn.disabled = false; btn.innerHTML = "<i class=\\"fas fa-check-circle\\"></i> Verify Email"; }';
    echo '  });';
    echo '}';
    echo '</script>';
}

?>