<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

function fetchUsersMap(PDO $pdo){
  $st=$pdo->query("SELECT id, first_name, last_name FROM users");
  $m=[]; foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){ $m[(int)$r['id']]=$r['first_name'].' '.$r['last_name']; } return $m;
}

$errors=[]; $success=null;

// Filters
$user_id = isset($_GET['user_id']) && $_GET['user_id']!=='' ? (int)$_GET['user_id'] : null;
$action = isset($_GET['action_type']) && $_GET['action_type']!=='' ? trim($_GET['action_type']) : null;
$date_from = isset($_GET['date_from']) && $_GET['date_from']!=='' ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) && $_GET['date_to']!=='' ? $_GET['date_to'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where=[]; $params=[];
if ($user_id){ $where[] = 'sl.user_id = ?'; $params[]=$user_id; }
if ($action){ $where[] = 'sl.action LIKE ?'; $params[]='%'.$action.'%'; }
if ($date_from){ $where[] = 'sl.created_at >= ?'; $params[]=$date_from.' 00:00:00'; }
if ($date_to){ $where[] = 'sl.created_at <= ?'; $params[]=$date_to.' 23:59:59'; }
$where_sql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

try {
  $countSql = "SELECT COUNT(*) FROM system_logs sl $where_sql";
  $stc = $pdo->prepare($countSql); $stc->execute($params); $total = (int)$stc->fetchColumn();
} catch(Throwable $e){ $errors[]='Failed to count logs: '.$e->getMessage(); $total=0; }

$logs=[];
try {
  $sql = "SELECT sl.id, sl.user_id, sl.action, sl.description, sl.ip_address, sl.user_agent, sl.created_at,
                 u.first_name, u.last_name
          FROM system_logs sl
          LEFT JOIN users u ON u.id = sl.user_id
          $where_sql
          ORDER BY sl.id DESC
          LIMIT $per_page OFFSET $offset";
  $st=$pdo->prepare($sql); $st->execute($params); $logs=$st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $errors[]='Failed to fetch logs: '.$e->getMessage(); }

$usersMap = fetchUsersMap($pdo);
$total_pages = max(1, (int)ceil($total / $per_page));

$page_title = 'System Logs';
include '../components/header.php';
?>
<style>
  .filters{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
  @media(max-width:1100px){.filters{grid-template-columns:repeat(2,1fr)}}
  @media(max-width:640px){.filters{grid-template-columns:1fr}}
  .logs-table{width:100%;border-collapse:separate;border-spacing:0}
  .logs-table th,.logs-table td{padding:10px;border-bottom:1px solid var(--border-color)}
  .pagination{display:flex;gap:8px;align-items:center;justify-content:flex-end;margin-top:12px}
  .ua{max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .card{padding:16px}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>System Logs</h1></div>

    <?php if($success || $errors): ?>
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          <?php if($success): ?>
          if (typeof showNotification === 'function') { showNotification(<?php echo json_encode($success); ?>,'success'); }
          <?php endif; ?>
          <?php if($errors): ?>
          if (typeof showNotification === 'function') { showNotification(<?php echo json_encode(implode("\n", $errors)); ?>,'error'); }
          <?php endif; ?>
        });
      </script>
    <?php endif; ?>

    <div class="card">
      <h3>Filters</h3>
      <form method="GET" class="form">
        <div class="filters">
          <div class="form-group">
            <label>User</label>
            <select name="user_id">
              <option value="">All</option>
              <?php foreach($usersMap as $id=>$name): ?>
                <option value="<?php echo (int)$id; ?>" <?php echo ($user_id===(int)$id?'selected':''); ?>><?php echo htmlspecialchars($name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Action</label>
            <input type="text" name="action_type" placeholder="e.g. login, update_settings" value="<?php echo htmlspecialchars($action ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Date From</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Date To</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
          </div>
          <div class="form-group" style="display:flex;align-items:flex-end">
            <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Apply</button>
          </div>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:12px">
      <h3>Logs</h3>
      <?php if(empty($logs)): ?>
        <div class="no-data"><i class="fas fa-list-alt"></i><p>No logs found for current filters.</p></div>
      <?php else: ?>
        <div style="overflow:auto">
          <table class="logs-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP</th>
                <th>User Agent</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($logs as $row): ?>
              <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                <td><?php echo $row['user_id'] ? htmlspecialchars(($row['first_name'] ?? '').' '.($row['last_name'] ?? '').' (#'.$row['user_id'].')') : 'System'; ?></td>
                <td><?php echo htmlspecialchars($row['action']); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                <td class="ua" title="<?php echo htmlspecialchars($row['user_agent']); ?>"><?php echo htmlspecialchars($row['user_agent']); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="pagination">
          <?php if($page>1): $prev=$page-1; $q=$_GET; $q['page']=$prev; ?>
            <a class="btn btn-secondary" href="?<?php echo http_build_query($q); ?>">&laquo; Prev</a>
          <?php endif; ?>
          <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total; ?> records)</span>
          <?php if($page<$total_pages): $next=$page+1; $q=$_GET; $q['page']=$next; ?>
            <a class="btn btn-secondary" href="?<?php echo http_build_query($q); ?>">Next &raquo;</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
