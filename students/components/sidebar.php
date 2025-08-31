<?php
// students/components/sidebar.php
// Expects: $class_title, optionally $app_name, $show_name, $profile_image_url, $first_name, $last_name
?>
<aside class="sidebar">
  <div class="logo" style="flex-direction:column; align-items:flex-start; gap:24px;">
    <?php
      $appTitle = isset($app_name) && ($show_name ?? true) ? (string)$app_name : 'Dashboard';
      $pfp = $profile_image_url ?? '';
      $sf = trim((string)($first_name ?? ''));
      $sl = trim((string)($last_name ?? ''));
    ?>
    <div style="font-weight:800; font-size:22px; line-height:1;">&lrm;<?= htmlspecialchars($appTitle) ?></div>
    <div style="display:flex; flex-direction:column; align-items:center; gap:10px; width:100%; text-align:center;">
      <div class="avatar" style="width:64px; height:64px; background:#dde2ff; border:3px solid rgba(255,255,255,.4); box-shadow:0 6px 18px rgba(0,0,0,.12);">
        <?php if (!empty($pfp)): ?>
          <img src="<?= htmlspecialchars($pfp) ?>" alt="profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;"
               onerror="this.onerror=null; this.parentNode.textContent='<?= isset($sf) || isset($sl) ? htmlspecialchars(initials($sf,$sl)) : 'ST' ?>';" />
        <?php else: ?>
          <?= htmlspecialchars(initials($sf,$sl)) ?>
        <?php endif; ?>
      </div>
      <div style="font-weight:800; text-transform:none;"><?= htmlspecialchars(trim($sf.' '.$sl) ?: 'Student') ?></div>
      <div style="opacity:.9; font-size:13px;"><?= htmlspecialchars($class_title ?: 'Class') ?></div>
    </div>
  </div>
  <nav class="menu">
    <a class="active" href="dashboard.php"><i class="mdi mdi-view-dashboard"></i> Dashboard</a>
    <a href="index.php"><i class="mdi mdi-book-open-page-variant"></i> Subjects</a>
    <a href="assignments.php"><i class="mdi mdi-file-document-edit"></i> Assignments</a>
    <a href="assessments.php"><i class="mdi mdi-clipboard-text-outline"></i> Recent Exams</a>
    <a href="test-assessments.php"><i class="mdi mdi-clipboard-check"></i> Recent Tests</a>
    <a href="resources.php"><i class="mdi mdi-folder-multiple"></i> Learning Resources</a>
    <a href="timetable.php"><i class="mdi mdi-calendar-clock"></i> Timetable</a>
    <a href="announcements.php"><i class="mdi mdi-bullhorn"></i> Announcements</a>
    <a href="virtual-classes.php"><i class="mdi mdi-video"></i> Virtual Classes</a>
    <a href="results.php"><i class="mdi mdi-chart-bar"></i> Results</a>
    <a href="./auth/logout.php"><i class="mdi mdi-logout"></i> Logout</a>
  </nav>
</aside>
