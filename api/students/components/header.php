<?php
// students/components/header.php
// Expects: $first_name, $last_name
?>
<div class="topbar">
  <button class="mobile-toggle" type="button" aria-label="Open menu" onclick="document.body.classList.add('sidebar-open')">
    <i class="mdi mdi-menu"></i>
  </button>
  <div class="search">
    <i class="mdi mdi-magnify"></i>
    <input type="text" placeholder="Search" />
  </div>
  <div class="user-mini">
    <div class="avatar">
      <!-- <?php echo htmlspecialchars(mb_substr($first_name,0,1).mb_substr($last_name,0,1)); ?> -->
      <img src="<?= htmlspecialchars($pfp) ?>" alt="profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;"
      onerror="this.onerror=null; this.parentNode.textContent='<?= isset($sf) || isset($sl) ? htmlspecialchars(initials($sf,$sl)) : 'ST' ?>';" />
    </div>
    <!-- <div>
      <div style="font-weight:700;"><?= htmlspecialchars($first_name.' '.$last_name) ?></div>
      <div style="font-size:12px; opacity:.7;">Student</div>
    </div> -->
  </div>
</div>
