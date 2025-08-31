<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$page_title = 'Reports';
$current_page = 'reports';

// Filters
$today = date('Y-m-d');
$defaultStart = date('Y-m-d', strtotime('-30 days'));
$start = $_GET['start'] ?? $defaultStart;
$end = $_GET['end'] ?? $today;
$role = $_GET['role'] ?? 'all'; // for user list filter
$action = $_GET['action'] ?? ''; // system_logs filter

// Clamp dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = $defaultStart;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = $today;
if ($start > $end) { $tmp = $start; $start = $end; $end = $tmp; }

// KPI: counts
$counts = [ 'teachers'=>0, 'students'=>0, 'supervisors'=>0, 'classes'=>0, 'subjects'=>0 ];
try {
  $counts['teachers'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND is_active=1")->fetchColumn();
  $counts['students'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1")->fetchColumn();
  $counts['supervisors'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='supervisor' AND is_active=1")->fetchColumn();
  $counts['classes'] = (int)$pdo->query("SELECT COUNT(*) FROM classes WHERE is_active=1")->fetchColumn();
  $counts['subjects'] = (int)$pdo->query("SELECT COUNT(*) FROM subjects WHERE is_active=1")->fetchColumn();
} catch (Throwable $e) {}

// Attendance summary in range
$attendance = [ 'present'=>0, 'absent'=>0, 'late'=>0, 'excused'=>0, 'total_days'=>0 ];
try {
  $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE date BETWEEN ? AND ? GROUP BY status");
  $stmt->execute([$start, $end]);
  foreach ($stmt->fetchAll() as $r) { $attendance[$r['status']] = (int)$r['cnt']; }
  $stmtDays = $pdo->prepare("SELECT COUNT(DISTINCT date) FROM attendance WHERE date BETWEEN ? AND ?");
  $stmtDays->execute([$start, $end]);
  $attendance['total_days'] = (int)$stmtDays->fetchColumn();
} catch (Throwable $e) {}

// Performance summary (tests within range): avg percentage overall and per class top 10
$avgOverall = null; $classPerf = [];
try {
  $stmt = $pdo->prepare("SELECT ROUND(AVG(tr.percentage),2) FROM test_results tr JOIN tests t ON t.id=tr.test_id WHERE t.scheduled_date BETWEEN ? AND ?");
  $stmt->execute([$start,$end]);
  $avgOverall = $stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT c.id as class_id, c.class_name, ROUND(AVG(tr.percentage),2) as avg_pct, COUNT(*) as samples
                          FROM test_results tr
                          JOIN tests t ON t.id=tr.test_id
                          JOIN classes c ON c.id = t.class_id
                          WHERE t.scheduled_date BETWEEN ? AND ?
                          GROUP BY c.id, c.class_name
                          ORDER BY avg_pct DESC
                          LIMIT 10");
  $stmt->execute([$start, $end]);
  $classPerf = $stmt->fetchAll();
} catch (Throwable $e) {}

// Recent system logs
$logs = [];
try {
  $sql = "SELECT l.*, u.username, u.first_name, u.last_name FROM system_logs l LEFT JOIN users u ON u.id = l.user_id WHERE l.created_at BETWEEN ? AND ?";
  $params = [$start.' 00:00:00', $end.' 23:59:59'];
  if ($action !== '') { $sql .= " AND l.action = ?"; $params[] = $action; }
  $sql .= " ORDER BY l.created_at DESC LIMIT 200";
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $logs = $st->fetchAll();
} catch (Throwable $e) {}

// Available actions for filter
$actions = [];
try { $actions = $pdo->query("SELECT DISTINCT action FROM system_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) {}

include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Reports</h1>
    </div>

    <div class="content-card compact">
      <div class="card-content">
        <form class="form" method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <div class="form-group">
            <label>Start</label>
            <input type="date" name="start" value="<?php echo htmlspecialchars($start); ?>">
          </div>
          <div class="form-group">
            <label>End</label>
            <input type="date" name="end" value="<?php echo htmlspecialchars($end); ?>">
          </div>
          <div class="form-group">
            <label>Log Action</label>
            <select name="action">
              <option value="">All</option>
              <?php foreach ($actions as $a): ?>
                <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $action===$a?'selected':''; ?>><?php echo htmlspecialchars($a); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <button class="btn btn-outline" type="submit"><i class="fas fa-filter"></i> Apply</button>
          </div>
        </form>
      </div>
    </div>

    <div class="content-grid">
      <div class="content-card compact">
        <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Key Metrics</h3></div>
        <div class="card-content">
          <div class="stats-grid">
            <div class="stat-card"><div class="stat-title">Teachers</div><div class="stat-value"><?php echo (int)$counts['teachers']; ?></div></div>
            <div class="stat-card"><div class="stat-title">Students</div><div class="stat-value"><?php echo (int)$counts['students']; ?></div></div>
            <div class="stat-card"><div class="stat-title">Supervisors</div><div class="stat-value"><?php echo (int)$counts['supervisors']; ?></div></div>
            <div class="stat-card"><div class="stat-title">Classes</div><div class="stat-value"><?php echo (int)$counts['classes']; ?></div></div>
            <div class="stat-card"><div class="stat-title">Subjects</div><div class="stat-value"><?php echo (int)$counts['subjects']; ?></div></div>
          </div>
        </div>
      </div>

      <div class="content-card compact">
        <div class="card-header"><h3><i class="fas fa-user-check"></i> Attendance (<?php echo htmlspecialchars($start); ?> to <?php echo htmlspecialchars($end); ?>)</h3></div>
        <div class="card-content">
          <div class="stats-grid">
            <div class="stat-card"><div class="stat-title">Present</div><div class="stat-value"><?php echo (int)$attendance['present']; ?></div></div>
            <div class="stat-card"><div class="stat-title">Absent</div><div class="stat-value"><?php echo (int)$attendance['absent']; ?></div></div>
            <div class="stat-card"><div class="stat-title">Late</div><div class="stat-value"><?php echo (int)$attendance['late']; ?></div></div>
            <div class="stat-card"><div class="stat-title">Excused</div><div class="stat-value"><?php echo (int)$attendance['excused']; ?></div></div>
          </div>
        </div>
      </div>

      <div class="content-card compact">
        <div class="card-header"><h3><i class="fas fa-percentage"></i> Performance (Tests in range)</h3></div>
        <div class="card-content">
          <div class="stats-grid">
            <div class="stat-card"><div class="stat-title">Average %</div><div class="stat-value"><?php echo $avgOverall!==null ? number_format((float)$avgOverall,2).'%' : 'N/A'; ?></div></div>
          </div>
          <div class="table-responsive" style="margin-top:12px;">
            <table class="table">
              <thead><tr><th>#</th><th>Class</th><th>Avg %</th><th>Samples</th></tr></thead>
              <tbody>
                <?php $i=1; foreach ($classPerf as $row): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                    <td><?php echo number_format((float)$row['avg_pct'],2); ?>%</td>
                    <td><?php echo (int)$row['samples']; ?></td>
                  </tr>
                <?php endforeach; if (!$classPerf): ?>
                  <tr><td colspan="4" class="muted">No test performance data in range.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="card-header"><h3><i class="fas fa-clipboard-list"></i> Recent System Logs</h3></div>
        <div class="card-content">
          <?php if (!$logs): ?>
            <div class="empty-state">No logs found for the selected period.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>#</th><th>When</th><th>User</th><th>Action</th><th>Description</th><th>IP</th><th>Agent</th></tr></thead>
                <tbody>
                  <?php $i=1; foreach ($logs as $l): $nm = trim(($l['first_name']??'').' '.($l['last_name']??'')) ?: ($l['username']??''); ?>
                    <tr>
                      <td><?php echo $i++; ?></td>
                      <td><?php echo htmlspecialchars($l['created_at']); ?></td>
                      <td><?php echo htmlspecialchars($nm); ?></td>
                      <td><span class="badge"><?php echo htmlspecialchars($l['action']); ?></span></td>
                      <td style="max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($l['description']); ?>"><?php echo htmlspecialchars($l['description']); ?></td>
                      <td><?php echo htmlspecialchars($l['ip_address']); ?></td>
                      <td style="max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($l['user_agent']); ?>"><?php echo htmlspecialchars($l['user_agent']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>

<style>
/* Simple stat cards using existing styles */
.stats-grid{ display:grid; grid-template-columns: repeat(auto-fill,minmax(160px,1fr)); gap:12px; }
.stat-card{ background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:12px; }
.stat-title{ color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
.stat-value{ font-size:22px; font-weight:700; margin-top:6px; }
</style>
