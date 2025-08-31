<?php
// students/components/card_instructors.php
// Expects: $teachers, $class_title
?>
<div class="card">
  <div class="sec-header">
    <h4 style="margin:0;">Class Teachers</h4>
    <span style="font-size:12px; opacity:.7;">Class: <?= htmlspecialchars($class_title) ?></span>
  </div>
  <div class="notice">
    <?php if (!empty($teachers)): foreach ($teachers as $t): $name = trim(($t['first_name'] ?? '').' '.($t['last_name'] ?? '')); ?>
      <div class="inst">
        <div class="avatar" style="background:#dde2ff;">
          <?php if (!empty($t['profile_image'])): ?>
            <img src="<?= htmlspecialchars($t['profile_image']) ?>" alt="<?= htmlspecialchars($name ?: 'Teacher') ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%;" />
          <?php else: ?>
            <?= htmlspecialchars(initials($t['first_name']??'', $t['last_name']??'')) ?>
          <?php endif; ?>
        </div>
        <div>
          <div style="font-weight:700; color:#1f2b6c;"><?= htmlspecialchars($name ?: 'Teacher') ?></div>
          <div style="font-size:12px; opacity:.7;">Teacher</div>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="notice-item">No teachers assigned to this class.</div>
    <?php endif; ?>
  </div>
</div>
