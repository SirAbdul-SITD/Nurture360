<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }

$page_title = 'Timetable';
$pdo = getDBConnection();

// Read app name and address from settings
$app_name = APP_NAME;
$app_address = '';
try {
  $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('app_name','app_address')");
  $stmt->execute();
  while ($row = $stmt->fetch()) {
    if ($row['setting_key'] === 'app_name') $app_name = $row['setting_value'];
    if ($row['setting_key'] === 'app_address') $app_address = $row['setting_value'];
  }
} catch (PDOException $e) {
  // fallback to defaults already set
}

// Preload classes, subjects, teachers for selects
$classes = $pdo->query("SELECT id, class_name, class_code, academic_year FROM classes WHERE is_active = 1 ORDER BY grade_level, class_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name, subject_code FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetchAll();
$teachers = $pdo->query("SELECT id, first_name, last_name, username FROM users WHERE role='teacher' AND is_active=1 ORDER BY first_name, last_name")->fetchAll();

include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Timetable</h1>
      <div class="btn-group">
        <button id="printBtn" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>

    <div class="timetable-row">
    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-calendar-alt"></i> Builder</h3>
      </div>
      <div class="card-content">
        <form id="timetableForm" class="form">
          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select id="tt_type">
                <option value="class">Class Timetable</option>
                <option value="exam">Exam Timetable</option>
              </select>
            </div>
            <div class="form-group">
              <label>Class</label>
              <select id="tt_class_id" required>
                <option value=""></option>
                <?php foreach ($classes as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Academic Year</label>
              <input type="text" id="tt_year" placeholder="2025/2026" value="<?php echo date('Y') . '/' . (date('Y')+1); ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Subject</label>
              <select id="tt_subject_id" required>
                <option value="">Select subject...</option>
                <?php foreach ($subjects as $s): ?>
                <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['subject_name'] . ' (' . $s['subject_code'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Teacher</label>
              <select id="tt_teacher_id" required>
                <option value="">Select teacher...</option>
                <?php foreach ($teachers as $t): ?>
                <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name'] . ' (@' . $t['username'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" data-show-if="type=class">
              <label>Day of week</label>
              <select id="tt_day">
                <option value="monday">Monday</option>
                <option value="tuesday">Tuesday</option>
                <option value="wednesday">Wednesday</option>
                <option value="thursday">Thursday</option>
                <option value="friday">Friday</option>
                <option value="saturday">Saturday</option>
                <option value="sunday">Sunday</option>
              </select>
            </div>
            <div class="form-group" data-show-if="type=exam">
              <label>Date</label>
              <input type="date" id="tt_date">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Start time</label>
              <input type="time" id="tt_start" required>
            </div>
            <div class="form-group">
              <label>End time</label>
              <input type="time" id="tt_end" required>
            </div>
            <div class="form-group">
              <label>Room (optional)</label>
              <input type="text" id="tt_room" placeholder="e.g., Lab 2">
            </div>
            <div class="form-group" data-show-if="type=exam">
              <label>Exam Title</label>
              <input type="text" id="tt_title" placeholder="Midterm / Final / ...">
            </div>
          </div>

          <div class="form-actions">
            <button type="button" id="addItemBtn" class="btn btn-primary"><i class="fas fa-plus"></i> Add to Timetable</button>
            <button type="button" id="loadBtn" class="btn"><i class="fas fa-rotate"></i> Load</button>
          </div>
        </form>
      </div>
    </div>

    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-list"></i> Subjects <span id="tt_items_title" class="muted"></span></h3>
      </div>
      <div class="card-content">
        <div id="tt_cards" class="cards-grid teachers-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
          <!-- Cards injected here -->
        </div>
      </div>
    </div>
    </div>

    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-file"></i> Print Preview</h3>
        <div class="action-buttons">
          <button id="openPrintBtn" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
      </div>
      <div class="card-content">
        <div id="printArea" class="print-area" data-app-name="<?php echo htmlspecialchars($app_name); ?>" data-app-address="<?php echo htmlspecialchars($app_address); ?>">
          <!-- Printable layout injected here -->
        </div>
      </div>
    </div>
  </main>
</div>

<?php include '../components/modal.php'; ?>
<script src="../assets/js/timetable.js"></script>
<?php include '../components/footer.php'; ?>
