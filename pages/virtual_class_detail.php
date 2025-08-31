<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

$resourceId = (int)($_GET['id'] ?? 0);
if ($resourceId <= 0) { redirect('./virtual-classes.php'); }

$message=''; $error='';

try {
  // Handle delete from detail page
  if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validateCSRFToken($_POST['csrf_token']??'')) throw new Exception('Invalid CSRF token');
    $a=$_POST['action']??'';
    if($a==='delete'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      $st=$pdo->prepare("DELETE FROM virtual_classes WHERE id=?");
      $st->execute([$id]);
      $message='Virtual class deleted';
      redirect('./virtual-classes.php');
    }
  }

  $sql = "SELECT v.*, 
                  c.class_name,c.class_code,c.grade_level,c.academic_year,
                  s.subject_name,s.subject_code,
                  u.first_name,u.last_name,u.username
           FROM virtual_classes v
           LEFT JOIN classes c ON c.id=v.class_id
           LEFT JOIN subjects s ON s.id=v.subject_id
           LEFT JOIN users u ON u.id=v.teacher_id
           WHERE v.id = ?";
  $st = $pdo->prepare($sql);
  $st->execute([$resourceId]);
  $vc = $st->fetch();
  if(!$vc){ redirect('./virtual-classes.php'); }

  $teacher = trim(($vc['first_name']??'').' '.($vc['last_name']??''));
  if($teacher==='') $teacher = $vc['username']??'-';
  $classLabel = ($vc['class_name']??'-').' ('.($vc['class_code']??'-').')';
  $subjectLabel = ($vc['subject_name']??'-').' #'.($vc['subject_code']??'-');
  $sched = ($vc['scheduled_date']??'').' '.($vc['start_time']??'').' - '.($vc['end_time']??'');
  $now=time();
  $startTs=@strtotime(($vc['scheduled_date']??'').' '.($vc['start_time']??''));
  $endTs=@strtotime(($vc['scheduled_date']??'').' '.($vc['end_time']??''));
  $statusText='Upcoming'; $statusClass='badge-primary';
  if($startTs && $endTs){ if($now>$endTs){ $statusText='Ended'; $statusClass='badge-danger'; } elseif($now>=$startTs && $now<=$endTs){ $statusText='Live'; $statusClass='badge-success'; } }

} catch(Throwable $e){ $error=$e->getMessage(); }

$page_title = 'Virtual Class Detail';
include '../components/header.php';
?>
<style>
.preview-box { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px; }
.detail-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
@media(max-width: 900px){ .detail-grid { grid-template-columns: 1fr; } }
.detail-item { padding:10px 12px; background:#f9fafb; border:1px solid #edf2f7; border-radius:8px; }
.detail-item .label { font-size:12px; color:#6b7280; }
.detail-item .value { font-size:14px; color:#111827; margin-top:4px; }
.badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; background:#eef2ff; color:#374151; margin-right:6px; }
.badge-primary{background:#dbeafe;color:#1e40af}
.badge-danger{background:#fee2e2;color:#991b1b}
.badge-success{background:#dcfce7;color:#166534}
.badge-neutral{background:#f3f4f6;color:#374151}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars($vc['title']); ?></h1>
        <div class="muted">
          Class: <?php echo htmlspecialchars($classLabel); ?> ·
          Subject: <?php echo htmlspecialchars($subjectLabel); ?> ·
          Teacher: <?php echo htmlspecialchars($teacher); ?>
        </div>
      </div>
      <div class="header-actions">
        <a class="btn btn-primary" href="./virtual-classes.php"><i class="fas fa-arrow-left"></i> Back to Virtual Classes</a>
        <?php if (!empty($vc['meeting_link'])): ?>
          <a class="btn btn-secondary" href="<?php echo htmlspecialchars($vc['meeting_link']); ?>" target="_blank"><i class="fas fa-video"></i> Join Meeting</a>
        <?php endif; ?>
        <button class="btn btn-error" type="button" onclick="openDeleteVirtualModal(<?php echo (int)$vc['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
      </div>
    </div>

    <div class="content-card">
      <div class="content-card-header">
        <h2>Overview</h2>
      </div>
      <div class="content-card-body">
        <div class="detail-grid">
          <div class="detail-item">
            <div class="label">Status</div>
            <div class="value"><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></div>
          </div>
          <div class="detail-item">
            <div class="label">Meeting Code</div>
            <div class="value"><?php echo htmlspecialchars($vc['meeting_code'] ?: '-'); ?></div>
          </div>
          <div class="detail-item">
            <div class="label">Schedule</div>
            <div class="value"><?php echo htmlspecialchars($sched); ?></div>
          </div>
          <div class="detail-item">
            <div class="label">Max Participants</div>
            <div class="value"><?php echo (int)($vc['max_participants'] ?? 0); ?></div>
          </div>
          <div class="detail-item" style="grid-column: 1 / -1;">
            <div class="label">Description</div>
            <div class="value"><?php echo nl2br(htmlspecialchars($vc['description'] ?? '')); ?></div>
          </div>
          <div class="detail-item" style="grid-column: 1 / -1;">
            <div class="label">Meeting Link</div>
            <div class="value">
              <?php if (!empty($vc['meeting_link'])): ?>
                <div class="input-group">
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($vc['meeting_link']); ?>" readonly>
                  <button class="btn btn-secondary" onclick="copyText(this)" title="Copy link"><i class="fas fa-copy"></i></button>
                </div>
              <?php else: ?>
                <em>No link provided</em>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php renderConfirmModal('deleteVirtualModal','Delete Virtual Class','Are you sure you want to delete this virtual class?','Delete','Cancel',["type"=>"warning","onConfirm"=>"handleDeleteVirtualDetail"]); ?>
<script>
var currentVirtualIdDetail = <?php echo (int)$vc['id']; ?>;
function openDeleteVirtualModal(id){ currentVirtualIdDetail=id; if(typeof window.openModalDeleteVirtualModal==='function'){window.openModalDeleteVirtualModal();} else { var m=document.getElementById('deleteVirtualModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function handleDeleteVirtualDetail(){ if(!currentVirtualIdDetail) return; var form=document.createElement('form'); form.method='POST'; var t1=document.createElement('input'); t1.type='hidden'; t1.name='csrf_token'; t1.value='<?php echo generateCSRFToken(); ?>'; form.appendChild(t1); var t2=document.createElement('input'); t2.type='hidden'; t2.name='action'; t2.value='delete'; form.appendChild(t2); var t3=document.createElement('input'); t3.type='hidden'; t3.name='id'; t3.value=String(currentVirtualIdDetail); form.appendChild(t3); document.body.appendChild(form); form.submit(); }
function copyText(btn){ try { var input = btn && btn.parentElement ? btn.parentElement.querySelector('input,textarea') : null; if(!input){ return; } input.select(); input.setSelectionRange(0, 99999); document.execCommand('copy'); if(typeof showNotification==='function'){ showNotification('Link copied to clipboard','success'); } } catch(e){ console.error(e); }
  if(document.activeElement) document.activeElement.blur(); }
</script>
<?php include '../components/footer.php'; ?>
