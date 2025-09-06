<?php
require_once '../config/config.php';
if (!isLoggedIn() || (!isSuperAdmin() && !isTeacher())) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$message = '';$error='';

// Helpers
function getClasses(PDO $pdo){return $pdo->query("SELECT id,class_name,class_code,grade_level,academic_year FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name")->fetchAll();}
function getSubjects(PDO $pdo){return $pdo->query("SELECT id,subject_name,subject_code FROM subjects WHERE COALESCE(is_active,1)=1 ORDER BY subject_name")->fetchAll();}
function getTeachers(PDO $pdo){$s=$pdo->prepare("SELECT id,first_name,last_name,username,email FROM users WHERE role='teacher' AND COALESCE(is_active,1)=1 ORDER BY first_name,last_name");$s->execute();return $s->fetchAll();}

function generateUniqueMeetingCode(PDO $pdo, $length = 6){
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  do {
    $code = '';
    for ($i=0;$i<$length;$i++){ $code .= $chars[random_int(0, strlen($chars)-1)]; }
    $st=$pdo->prepare("SELECT COUNT(*) FROM virtual_classes WHERE meeting_code=?");
    $st->execute([$code]);
  } while ((int)$st->fetchColumn()>0);
  return $code;
}

try{
  if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validateCSRFToken($_POST['csrf_token']??'')) throw new Exception('Invalid CSRF token');
    $a=$_POST['action']??'';

    if($a==='create'){
      $title=trim($_POST['title']??'');
      $desc=trim($_POST['description']??'');
      $class_id=(int)($_POST['class_id']??0);
      $subject_id=(int)($_POST['subject_id']??0);
      if (isTeacher()) { $teacher_id=getCurrentUserId(); } else { $teacher_id=(int)($_POST['teacher_id']??0); }
      $meeting_link=trim($_POST['meeting_link']??'');
      $meeting_code=trim($_POST['meeting_code']??'');
      $scheduled_date=$_POST['scheduled_date']??'';
      $start_time=$_POST['start_time']??'';
      $end_time=$_POST['end_time']??'';
      $max_participants=(int)($_POST['max_participants']??50);
      if($title===''||$class_id<=0||$subject_id<=0||$teacher_id<=0||$scheduled_date===''||$start_time===''||$end_time==='') throw new Exception('All required fields must be filled');
      if($max_participants<=0) throw new Exception('Max participants must be a positive number');
      if (isTeacher()) {
        $chk=$pdo->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1");
        $chk->execute([getCurrentUserId(),$class_id,$subject_id]);
        if(!$chk->fetchColumn()) throw new Exception('You are not assigned to this class/subject');
      }
      $startTs=@strtotime($scheduled_date.' '.$start_time); $endTs=@strtotime($scheduled_date.' '.$end_time);
      if($startTs===false||$endTs===false) throw new Exception('Invalid schedule date/time');
      if($endTs<=$startTs) throw new Exception('End time must be after start time');
      if($meeting_code===''){ $meeting_code=generateUniqueMeetingCode($pdo,8); }
      $st=$pdo->prepare("INSERT INTO virtual_classes (class_id,subject_id,teacher_id,title,description,meeting_link,meeting_code,scheduled_date,start_time,end_time,max_participants) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
      $st->execute([$class_id,$subject_id,$teacher_id,$title,$desc,($meeting_link!==''?$meeting_link:null),$meeting_code,$scheduled_date,$start_time,$end_time,$max_participants]);
      $message='Virtual class created';
    }elseif($a==='update'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      $title=trim($_POST['title']??'');
      $desc=trim($_POST['description']??'');
      $class_id=(int)($_POST['class_id']??0);
      $subject_id=(int)($_POST['subject_id']??0);
      if (isTeacher()) {
        // Check ownership
        $own=$pdo->prepare('SELECT COUNT(*) FROM virtual_classes WHERE id=? AND teacher_id=?');
        $own->execute([$id,getCurrentUserId()]);
        if(!$own->fetchColumn()) throw new Exception('Not authorized to edit this virtual class');
        $teacher_id=getCurrentUserId();
        $chk=$pdo->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1");
        $chk->execute([$teacher_id,$class_id,$subject_id]);
        if(!$chk->fetchColumn()) throw new Exception('You are not assigned to this class/subject');
      } else { $teacher_id=(int)($_POST['teacher_id']??0); }
      $meeting_link=trim($_POST['meeting_link']??'');
      $meeting_code=trim($_POST['meeting_code']??'');
      $scheduled_date=$_POST['scheduled_date']??'';
      $start_time=$_POST['start_time']??'';
      $end_time=$_POST['end_time']??'';
      $max_participants=(int)($_POST['max_participants']??50);
      if($title===''||$class_id<=0||$subject_id<=0||$teacher_id<=0||$scheduled_date===''||$start_time===''||$end_time==='') throw new Exception('All required fields must be filled');
      if($meeting_code===''){ $meeting_code=generateUniqueMeetingCode($pdo,8); }
      $st=$pdo->prepare("UPDATE virtual_classes SET class_id=?,subject_id=?,teacher_id=?,title=?,description=?,meeting_link=?,meeting_code=?,scheduled_date=?,start_time=?,end_time=?,max_participants=? WHERE id=?");
      $st->execute([$class_id,$subject_id,$teacher_id,$title,$desc,($meeting_link!==''?$meeting_link:null),$meeting_code,$scheduled_date,$start_time,$end_time,$max_participants,$id]);
      $message='Virtual class updated';
    }elseif($a==='delete'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      if (isTeacher()) {
        $st=$pdo->prepare("DELETE FROM virtual_classes WHERE id=? AND teacher_id=?");
        $st->execute([$id,getCurrentUserId()]);
        if($st->rowCount()===0) throw new Exception('Not authorized to delete this virtual class');
      } else {
        $st=$pdo->prepare("DELETE FROM virtual_classes WHERE id=?");$st->execute([$id]);
      }
      $message='Virtual class deleted';
    }
  }
}catch(Throwable $e){$error=$e->getMessage();}

$teacherId = getCurrentUserId();
if (isTeacher()) {
  $s=$pdo->prepare("SELECT DISTINCT c.id,c.class_name,c.class_code,c.grade_level,c.academic_year FROM teacher_assignments ta JOIN classes c ON c.id=ta.class_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(c.is_active,1)=1 ORDER BY c.grade_level,c.class_name");
  $s->execute([$teacherId]); $classes=$s->fetchAll();
  $s=$pdo->prepare("SELECT DISTINCT s.subject_id AS id, s.title AS subject_name, s.subject_code FROM teacher_assignments ta JOIN subjects s ON s.subject_id=ta.subject_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(s.is_active,1)=1 ORDER BY s.title");
  $s->execute([$teacherId]); $subjects=$s->fetchAll();
  $teachers=[[ 'id'=>$teacherId, 'first_name'=>$_SESSION['first_name']??'', 'last_name'=>$_SESSION['last_name']??'', 'username'=>$_SESSION['username']??'', 'email'=>$_SESSION['email']??'' ]];
} else {
  $classes=getClasses($pdo);$subjects=getSubjects($pdo);$teachers=getTeachers($pdo);
}
$classesCount=count($classes); $subjectsCount=count($subjects); $teachersCount=count($teachers);

// Fetch list
if (isTeacher()) {
  $st=$pdo->prepare("SELECT v.*, c.class_name,c.class_code,c.grade_level,c.academic_year, s.title AS subject_name,s.subject_code, u.first_name,u.last_name,u.username
                     FROM virtual_classes v
                     LEFT JOIN classes c ON c.id=v.class_id
                     LEFT JOIN subjects s ON s.subject_id=v.subject_id
                     LEFT JOIN users u ON u.id=v.teacher_id
                     WHERE v.teacher_id=?
                     ORDER BY v.scheduled_date DESC, v.start_time DESC");
  $st->execute([$teacherId]);
  $virtuals=$st->fetchAll();
} else {
  $sql = "SELECT v.*, c.class_name,c.class_code,c.grade_level,c.academic_year, s.title AS subject_name,s.subject_code, u.first_name,u.last_name,u.username
          FROM virtual_classes v
          LEFT JOIN classes c ON c.id=v.class_id
          LEFT JOIN subjects s ON s.subject_id=v.subject_id
          LEFT JOIN users u ON u.id=v.teacher_id
          ORDER BY v.scheduled_date DESC, v.start_time DESC";
  $virtuals=$pdo->query($sql)->fetchAll();
}

$page_title = 'Virtual Classes';
include '../components/header.php';
?>
<style>
#virtualGrid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
@media (max-width: 1024px) { #virtualGrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) { #virtualGrid { grid-template-columns: 1fr; } }
.virtual-card .teacher-avatar{background:#ecfeff;color:#0369a1}
.virtual-meta{font-size:12px;color:#6b7280;margin-top:6px}
.virtual-desc{font-size:13px;color:#374151;margin-top:8px}
.virtual-badges{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-primary{background:#dbeafe;color:#1e40af}
.badge-danger{background:#fee2e2;color:#991b1b}
.badge-success{background:#dcfce7;color:#166534}
.badge-neutral{background:#f3f4f6;color:#374151}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Virtual Classes</h1></div>
    <div class="card-header">
      <div class="search-input-wrapper" style="max-width:420px;width:100%"><i class="fas fa-search"></i>
        <input id="virtualSearchInput" type="text" class="table-search-input" placeholder="Search by title, class, subject, teacher..." />
      </div>
      <div class="card-header--right">
        <button type="button" class="btn btn-primary" onclick="openCreateVirtualModal()"><i class="fas fa-plus"></i> New Virtual Class</button>
      </div>
    </div>
    <div class="card-content">
      <?php if(!empty($virtuals)): ?>
      <div id="virtualGrid" class="cards-grid">
        <?php foreach($virtuals as $v): 
          $teacher=trim(($v['first_name']??'').' '.($v['last_name']??'')); $teacher=$teacher!==''?$teacher:($v['username']??''); 
          $cls=(($v['class_name']??'-').' ('.($v['class_code']??'-').')'); 
          $sub=(($v['subject_name']??'-').' #'.($v['subject_code']??'-'));
          $sched=(($v['scheduled_date']??'').' '.($v['start_time']??'').' - '.($v['end_time']??''));
          $safe=json_encode($v, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
          $now=time();
          $startTs=@strtotime(($v['scheduled_date']??'').' '.($v['start_time']??''));
          $endTs=@strtotime(($v['scheduled_date']??'').' '.($v['end_time']??''));
          $statusText='Upcoming'; $statusClass='badge-primary';
          if($startTs && $endTs){ if($now>$endTs){ $statusText='Ended'; $statusClass='badge-danger'; } elseif($now>=$startTs && $now<=$endTs){ $statusText='Live'; $statusClass='badge-success'; } }
        ?>
        <div class="teacher-card virtual-card" data-date="<?php echo htmlspecialchars($v['scheduled_date']); ?>" data-start="<?php echo htmlspecialchars($v['start_time']); ?>" data-end="<?php echo htmlspecialchars($v['end_time']); ?>">
          <div class="teacher-avatar"><i class="fas fa-video"></i></div>
          <div class="teacher-name"><strong><a href="./virtual_class_detail.php?id=<?php echo (int)$v['id']; ?>" style="text-decoration:none;color:inherit;"><?php echo htmlspecialchars($v['title']); ?></a></strong></div>
          <div class="virtual-badges">
            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
            <span class="badge badge-neutral">Code: <?php echo htmlspecialchars($v['meeting_code']); ?></span>
            <span class="badge badge-neutral">Cap: <?php echo (int)($v['max_participants']??0); ?></span>
          </div>
          <div class="teacher-username">Teacher: <?php echo htmlspecialchars($teacher); ?></div>
          <div class="virtual-meta">Class: <?php echo htmlspecialchars($cls); ?> • Subject: <?php echo htmlspecialchars($sub); ?></div>
          <div class="virtual-meta">Schedule: <?php echo htmlspecialchars($sched); ?></div>
          <?php if(!empty($v['description'])): ?><div class="virtual-desc"><?php echo nl2br(htmlspecialchars($v['description'])); ?></div><?php endif; ?>
          <div class="teacher-card-actions action-buttons centered">
            <?php if(!empty($v['meeting_link'])): ?>
              <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($v['meeting_link']); ?>" target="_blank" title="Join"><i class="fas fa-external-link-alt"></i></a>
            <?php endif; ?>
            <!-- <a class="btn btn-sm btn-secondary" href="./virtual_class_detail.php?id=<?php echo (int)$v['id']; ?>" title="View"><i class="fas fa-eye"></i></a> -->
            <button class="btn btn-sm btn-primary" type="button" onclick='openEditVirtualModal(<?php echo (int)$v['id']; ?>, <?php echo $safe; ?>)' title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-error" type="button" onclick="openDeleteVirtualModal(<?php echo (int)$v['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="no-data"><i class="fas fa-video"></i><p>No virtual classes yet. Create one to get started.</p><button type="button" class="btn btn-primary" onclick="openCreateVirtualModal()">Create Virtual Class</button></div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
function optionsHtml($items,$idKey,$labelCb){$h='';foreach($items as $it){$id=(int)$it[$idKey];$label=call_user_func($labelCb,$it);$h.='<option value="'.$id.'">'.htmlspecialchars($label).'</option>'; }return $h;}
$classOpts=optionsHtml($classes,'id',function($c){$g=isset($c['grade_level'])?('G'.(int)$c['grade_level']):'-';$code=$c['class_code']??'';return $c['class_name'].' ('.$g.') #'.$code;});
$subjectOpts=optionsHtml($subjects,'id',function($s){return ($s['subject_name']??'-').' #'.($s['subject_code']??'');});
$teacherOpts=optionsHtml($teachers,'id',function($t){$full=trim(($t['first_name']??'').' '.($t['last_name']??''));$name=$full!==''?$full:($t['username']??'');return $name.' - '.($t['email']??'');});

$teacherFieldCreate = isTeacher()
  ? '<div class="form-group"><label>Teacher</label><div class="muted">You</div><input type="hidden" name="teacher_id" value="'.(int)$teacherId.'"></div>'
  : '<div class="form-group"><label>Teacher *</label><select name="teacher_id" required><option value="">Select teacher</option>'.$teacherOpts.'</select></div>';

$createForm = '
<form id="createVirtualForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="create">
  <div class="muted" style="margin-bottom:8px; font-size:12px; color:#6b7280;">
    Classes: '.(int)$classesCount.' • Subjects: '.(int)$subjectsCount.' • Teachers: '.(int)$teachersCount.'
  </div>
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" required></div>
    <div class="form-group"><label>Meeting Code</label><input type="text" name="meeting_code" placeholder="Auto-generate if empty"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-row">
    '.$teacherFieldCreate.'
    <div class="form-group"><label>Max Participants</label><input type="number" name="max_participants" min="1" value="50"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Scheduled Date *</label><input type="date" name="scheduled_date" required></div>
    <div class="form-group"><label>Start Time *</label><input type="time" name="start_time" required></div>
    <div class="form-group"><label>End Time *</label><input type="time" name="end_time" required></div>
  </div>
  <div class="form-row">
    <div class="form-group" style="flex:1"><label>Meeting Link</label><input type="url" name="meeting_link" placeholder="https://... (Zoom/Meet/Teams)"></div>
  </div>
  <div class="form-group"><label>Description</label><textarea name="description" placeholder="Optional notes..."></textarea></div>
</form>';
renderFormModal('createVirtualModal','Create Virtual Class',$createForm,'Create','Cancel',['size'=>'large','formId'=>'createVirtualForm']);

$editForm = '
<form id="editVirtualForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" id="edit_id">
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" id="edit_title" required></div>
    <div class="form-group"><label>Meeting Code</label><input type="text" name="meeting_code" id="edit_code" placeholder="Auto-generate if empty"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" id="edit_class" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" id="edit_subject" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-row">
    '.(isTeacher() ? ('<div class="form-group"><label>Teacher</label><div class="muted">You</div><input type="hidden" name="teacher_id" value="'.(int)$teacherId.'"></div>') : ('<div class="form-group"><label>Teacher *</label><select name="teacher_id" id="edit_teacher" required><option value="">Select teacher</option>'.$teacherOpts.'</select></div>')).'
    <div class="form-group"><label>Max Participants</label><input type="number" name="max_participants" id="edit_max" min="1" value="50"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Scheduled Date *</label><input type="date" name="scheduled_date" id="edit_date" required></div>
    <div class="form-group"><label>Start Time *</label><input type="time" name="start_time" id="edit_start" required></div>
    <div class="form-group"><label>End Time *</label><input type="time" name="end_time" id="edit_end" required></div>
  </div>
  <div class="form-row">
    <div class="form-group" style="flex:1"><label>Meeting Link</label><input type="url" name="meeting_link" id="edit_link" placeholder="https://... (Zoom/Meet/Teams)"></div>
  </div>
  <div class="form-group"><label>Description</label><textarea name="description" id="edit_desc"></textarea></div>
</form>';
renderFormModal('editVirtualModal','Edit Virtual Class',$editForm,'Save','Cancel',['size'=>'large','formId'=>'editVirtualForm']);

renderConfirmModal('deleteVirtualModal','Delete Virtual Class','Are you sure you want to delete this virtual class?','Delete','Cancel',['type'=>'warning','onConfirm'=>'handleDeleteVirtual']);
?>
<script>
function openCreateVirtualModal(){ if(typeof window.openModalCreateVirtualModal==='function'){window.openModalCreateVirtualModal();} else { var m=document.getElementById('createVirtualModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function openEditVirtualModal(id,data){
  document.getElementById('edit_id').value=id;
  document.getElementById('edit_title').value=data.title||'';
  document.getElementById('edit_class').value=data.class_id||'';
  document.getElementById('edit_subject').value=data.subject_id||'';
  var editTeacher=document.getElementById('edit_teacher'); if(editTeacher){ editTeacher.value=data.teacher_id||''; }
  document.getElementById('edit_max').value=data.max_participants||50;
  document.getElementById('edit_date').value=data.scheduled_date||'';
  document.getElementById('edit_start').value=data.start_time||'';
  document.getElementById('edit_end').value=data.end_time||'';
  document.getElementById('edit_link').value=data.meeting_link||'';
  document.getElementById('edit_desc').value=data.description||'';
  document.getElementById('edit_code').value=data.meeting_code||'';
  if(typeof window.openModalEditVirtualModal==='function'){window.openModalEditVirtualModal();} else { var m=document.getElementById('editVirtualModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
var currentVirtualId=null;
function openDeleteVirtualModal(id){ currentVirtualId=id; if(typeof window.openModalDeleteVirtualModal==='function'){window.openModalDeleteVirtualModal();} else { var m=document.getElementById('deleteVirtualModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function handleDeleteVirtual(){ if(!currentVirtualId) return; var form=document.createElement('form'); form.method='POST'; var t1=document.createElement('input'); t1.type='hidden'; t1.name='csrf_token'; t1.value='<?php echo generateCSRFToken(); ?>'; form.appendChild(t1); var t2=document.createElement('input'); t2.type='hidden'; t2.name='action'; t2.value='delete'; form.appendChild(t2); var t3=document.createElement('input'); t3.type='hidden'; t3.name='id'; t3.value=String(currentVirtualId); form.appendChild(t3); document.body.appendChild(form); form.submit(); }
// Search filter
(function(){ document.addEventListener('DOMContentLoaded',function(){ var input=document.getElementById('virtualSearchInput'); var grid=document.getElementById('virtualGrid'); if(!input||!grid) return; var cards=[].slice.call(grid.querySelectorAll('.teacher-card')); function norm(s){return (s||'').toLowerCase();} function f(){ var q=norm(input.value); cards.forEach(function(c){ var m=!q||norm(c.textContent).indexOf(q)!==-1; c.style.display=m?'':'none'; }); } input.addEventListener('input',f); }); })();

// Client-side validation for create/edit forms
(function(){
  function validateSchedule(form){
    var date=form.querySelector('[name="scheduled_date"]');
    var start=form.querySelector('[name="start_time"]');
    var end=form.querySelector('[name="end_time"]');
    var max=form.querySelector('[name="max_participants"]');
    if(!date||!start||!end) return true;
    var sTs=Date.parse(date.value+'T'+(start.value||'00:00'));
    var eTs=Date.parse(date.value+'T'+(end.value||'00:00'));
    if(isNaN(sTs)||isNaN(eTs)){ alert('Please provide a valid schedule date and times.'); return false; }
    if(eTs<=sTs){ alert('End time must be after start time.'); return false; }
    if(max && parseInt(max.value||'0',10)<=0){ alert('Max participants must be a positive number.'); return false; }
    return true;
  }
  document.addEventListener('DOMContentLoaded',function(){
    var today=new Date(); var y=today.getFullYear(); var m=String(today.getMonth()+1).padStart(2,'0'); var d=String(today.getDate()).padStart(2,'0'); var minDate=y+'-'+m+'-'+d;
    document.querySelectorAll('input[type=date][name="scheduled_date"]').forEach(function(el){ if(!el.min) el.min=minDate; });
    var createForm=document.getElementById('createVirtualForm'); if(createForm){ createForm.addEventListener('submit',function(ev){ if(!validateSchedule(createForm)){ ev.preventDefault(); } }); }
    var editForm=document.getElementById('editVirtualForm'); if(editForm){ editForm.addEventListener('submit',function(ev){ if(!validateSchedule(editForm)){ ev.preventDefault(); } }); }
  });
})();

// Live status badge updater
(function(){
  function computeStatus(dateStr,startStr,endStr){
    if(!dateStr||!startStr||!endStr) return {text:'Upcoming', cls:'badge-primary'};
    var now=Date.now();
    var s=Date.parse(dateStr+'T'+startStr);
    var e=Date.parse(dateStr+'T'+endStr);
    if(isNaN(s)||isNaN(e)) return {text:'Upcoming', cls:'badge-primary'};
    if(now>e) return {text:'Ended', cls:'badge-danger'};
    if(now>=s && now<=e) return {text:'Live', cls:'badge-success'};
    return {text:'Upcoming', cls:'badge-primary'};
  }
  function updateBadges(){
    document.querySelectorAll('#virtualGrid .virtual-card').forEach(function(card){
      var date=card.getAttribute('data-date');
      var start=card.getAttribute('data-start');
      var end=card.getAttribute('data-end');
      var st=computeStatus(date,start,end);
      var badge=card.querySelector('.virtual-badges .badge');
      if(badge){ badge.textContent=st.text; badge.className='badge '+st.cls; }
    });
  }
  document.addEventListener('DOMContentLoaded',function(){ updateBadges(); setInterval(updateBadges, 60000); });
})();
// Toasts
<?php if ($message || $error): ?>
document.addEventListener('DOMContentLoaded',function(){ <?php if($message): ?> if(typeof showNotification==='function') showNotification(<?php echo json_encode($message); ?>,'success'); <?php endif; ?> <?php if($error): ?> if(typeof showNotification==='function') showNotification(<?php echo json_encode($error); ?>,'error'); <?php endif; ?> });
<?php endif; ?>
</script>
<?php include '../components/footer.php'; ?>
