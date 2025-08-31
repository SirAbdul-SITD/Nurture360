<?php
// Supervisor Detail Component
// Mirrors components/teacher-detail.php for consistent UI

if (!function_exists('renderSupervisorDetail')) {
    function renderSupervisorDetail(array $supervisor, array $options = []): void {
        // Align with teacher-detail structure and classes for identical layout
        $defaults = [
            'showActions' => false,
            'showAssignments' => false,
            'showHistory' => false,
            'compact' => false
        ];
        $options = array_merge($defaults, $options);

        $statusClass = !empty($supervisor['is_active']) ? 'status-active' : 'status-inactive';
        $statusText  = !empty($supervisor['is_active']) ? 'Active' : 'Inactive';

        $verified   = !empty($supervisor['email_verified']);
        $badgeClass = $verified ? 'status-verified' : 'status-unverified';
        $badgeText  = $verified ? 'Email Verified' : 'Email Not Verified';

        echo '<div class="teacher-detail' . ($options['compact'] ? ' compact' : '') . '">';

        echo '<div class="teacher-grid">';

        // Left profile card (mirrors teacher-detail)
        echo '  <aside class="profile-card">';
        echo '    <div class="profile-card-inner">';
        echo '      <div class="avatar-wrap">';
        if (!empty($supervisor['profile_image'])) {
            echo '        <img class="avatar-img" src="' . htmlspecialchars($supervisor['profile_image']) . '" alt="' . htmlspecialchars(($supervisor['first_name'] ?? '') . ' ' . ($supervisor['last_name'] ?? '')) . ' avatar" />';
        } else {
            echo '        <div class="avatar-fallback"><i class="fas fa-user-shield"></i></div>';
        }
        echo '      </div>';
        echo '      <div class="profile-text">';
        $fullName = trim(($supervisor['first_name'] ?? '') . ' ' . ($supervisor['last_name'] ?? ''));
        $displayName = $fullName !== '' ? $fullName : ($supervisor['username'] ?? '');
        echo '        <h2 class="profile-name">' . htmlspecialchars($displayName) . '</h2>';
        echo '        <p class="profile-sub">Supervisor</p>';
        echo '        <div class="badge-row">';
        echo '          <span class="status-badge ' . $statusClass . '">' . $statusText . '</span>';
        echo '          <span id="emailVerifiedBadge_' . ($supervisor['id'] ?? '0') . '" class="status-badge ' . $badgeClass . '">' . $badgeText . '</span>';
        echo '        </div>';
        if (!$verified) {
            echo '        <button id="verifyEmailBtn_' . ($supervisor['id'] ?? '0') . '" type="button" class="btn btn-primary btn-block" onclick="verifySupervisorEmail(' . ($supervisor['id'] ?? 0) . ')"><i class="fas fa-check-circle"></i> Verify Email</button>';
        }
        echo '      </div>';
        echo '      <ul class="social-list">';
        echo '        <li><i class="fas fa-globe"></i><span>Website</span><span class="value">Not set</span></li>';
        echo '        <li><i class="fab fa-twitter"></i><span>Twitter</span><span class="value">' . htmlspecialchars($supervisor['username'] ?? '') . '</span></li>';
        echo '        <li><i class="fab fa-facebook"></i><span>Facebook</span><span class="value">Not set</span></li>';
        echo '        <li><i class="fab fa-instagram"></i><span>Instagram</span><span class="value">Not set</span></li>';
        echo '      </ul>';
        echo '    </div>';
        echo '  </aside>';

        // Right content area (info table + optional assignments + optional metrics grid to keep layout parity)
        echo '  <section class="content-col">';
        echo '    <div class="info-table-card">';
        echo '      <div class="info-row"><span>Full Name</span><span>' . htmlspecialchars($fullName !== '' ? $fullName : '-') . '</span></div>';
        echo '      <div class="info-row"><span>Email</span><span>' . htmlspecialchars($supervisor['email'] ?? '-') . '</span></div>';
        echo '      <div class="info-row"><span>Phone</span><span>' . (!empty($supervisor['phone']) ? htmlspecialchars($supervisor['phone']) : 'Not provided') . '</span></div>';
        echo '      <div class="info-row"><span>Mobile</span><span>' . (!empty($supervisor['phone']) ? htmlspecialchars($supervisor['phone']) : 'Not provided') . '</span></div>';
        echo '      <div class="info-row"><span>Address</span><span>Not provided</span></div>';
        echo '    </div>';

        // Assigned Classes Section
        if (!empty($options['showAssignments'])) {
            $assignments = $options['assignments'] ?? [];
            $count = is_array($assignments) ? count($assignments) : 0;
            echo '    <div class="section-card" style="margin-top:16px;">';
            echo '      <div class="section-header">';
            echo '        <div>Assigned Classes</div>';
            echo '        <div><span class="status-badge">' . (int)$count . ' total</span></div>';
            echo '      </div>';
            echo '      <div class="section-content">';
            if ($count === 0) {
                echo '        <div class="placeholder">No classes assigned.</div>';
            } else {
                echo '        <div class="assignments-list">';
                foreach ($assignments as $a) {
                    $clsName = htmlspecialchars($a['class_name'] ?? '-');
                    $grade = isset($a['grade_level']) ? 'Grade ' . (int)$a['grade_level'] : '';
                    $code = htmlspecialchars($a['class_code'] ?? '');
                    $year = htmlspecialchars($a['academic_year'] ?? ($a['class_year'] ?? ''));
                    $active = !empty($a['is_active']);
                    $badge = $active ? '<span class="status-badge status-active">Active</span>' : '<span class="status-badge status-inactive">Inactive</span>';
                    echo '          <div class="assignments-item">';
                    echo '            <div class="meta">';
                    echo '              <div class="title">' . $clsName . ' <small>(' . $grade . ')</small></div>';
                    echo '              <div class="sub">Code: ' . $code . ' • Year: ' . $year . '</div>';
                    echo '            </div>';
                    echo '            <div class="inline-actions">' . $badge . '</div>';
                    echo '          </div>';
                }
                echo '        </div>';
            }
            echo '      </div>';
            echo '    </div>';
        }

        // Optional metrics placeholders to preserve identical spacing of teacher layout (hidden by default)
        echo '    <div class="metrics-grid" style="display:none">';
        echo '      <div class="metric-card">';
        echo '        <div class="metric-header">Responsibilities</div>';
        echo '        <div class="metric-content"><div class="loading-placeholder">No data</div></div>';
        echo '      </div>';
        echo '      <div class="metric-card">';
        echo '        <div class="metric-header">Teams Supervised</div>';
        echo '        <div class="metric-content"><div class="loading-placeholder">No data</div></div>';
        echo '      </div>';
        echo '    </div>';

        // Hidden activity container to match teacher-detail structure if scripts expect it
        echo '    <div id="supervisorActivity_' . ($supervisor['id'] ?? '0') . '" style="display:none"></div>';
        echo '  </section>';

        echo '</div>'; // teacher-grid

        echo '</div>'; // teacher-detail

        // Emit verification JS once on page
        if (function_exists('emitVerifySupervisorEmailJS')) { emitVerifySupervisorEmailJS(); }
    }
}

// Emit JS for verifying supervisor email and updating UI (mirrors teacher variant)
if (!function_exists('emitVerifySupervisorEmailJS')) {
    function emitVerifySupervisorEmailJS() {
        echo '<script>';
        echo 'function verifySupervisorEmail(supervisorId) {';
        echo '  const btn = document.getElementById("verifyEmailBtn_" + supervisorId);';
        echo '  const badge = document.getElementById("emailVerifiedBadge_" + supervisorId);';
        echo '  if (btn) { btn.disabled = true; btn.innerHTML = "<i class=\\"fas fa-spinner fa-spin\\"></i> Verifying..."; }';
        echo '  fetch("../api/verify_supervisor_email.php", {';
        echo '    method: "POST",';
        echo '    headers: { "Content-Type": "application/json" },';
        echo '    body: JSON.stringify({ supervisor_id: supervisorId })';
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
        echo '    console.error("verifySupervisorEmail error", err);';
        echo '    if (typeof showNotification === "function") showNotification("Server error. Try again.", "error");';
        echo '    if (btn) { btn.disabled = false; btn.innerHTML = "<i class=\\"fas fa-check-circle\\"></i> Verify Email"; }';
        echo '  });';
        echo '}';
        echo '</script>';
    }
}

?>
