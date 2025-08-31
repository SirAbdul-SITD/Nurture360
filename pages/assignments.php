<?php
require_once '../config/config.php';
if (!isLoggedIn() || (!isSuperAdmin() && !isTeacher())) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$message = '';$error='';

// Helpers
// Use COALESCE to treat NULL is_active as active to avoid empty dropdowns on legacy data
function getClasses(PDO $pdo){return $pdo->query("SELECT id,class_name,class_code,grade_level,academic_year FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name")->fetchAll();}
function getSubjects(PDO $pdo){return $pdo->query("SELECT id,subject_name,subject_code FROM subjects WHERE COALESCE(is_active,1)=1 ORDER BY subject_name")->fetchAll();}
function getTeachers(PDO $pdo){$s=$pdo->prepare("SELECT id,first_name,last_name,username,email FROM users WHERE role='teacher' AND COALESCE(is_active,1)=1 ORDER BY first_name,last_name");$s->execute();return $s->fetchAll();}

try{
  if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validateCSRFToken($_POST['csrf_token']??'')) throw new Exception('Invalid CSRF token');
    $a=$_POST['action']??'';
    if($a==='create'){
      $title=trim($_POST['title']??'');$desc=trim($_POST['description']??'');
      $class_id=(int)($_POST['class_id']??0);$subject_id=(int)($_POST['subject_id']??0);
      // For superadmin, teacher_id comes from form; for teacher, force to current user
      if (isTeacher()) {
        $teacher_id = getCurrentUserId();
      } else {
        $teacher_id=(int)($_POST['teacher_id']??0);
      }
      $due_date=$_POST['due_date']??'';$due_time=$_POST['due_time']??'';$marks=(int)($_POST['total_marks']??100);$active=isset($_POST['is_active'])?1:1;
      if($title===''||$class_id<=0||$subject_id<=0||$teacher_id<=0||$due_date===''||$due_time==='') throw new Exception('All required fields must be filled');
      // Teacher can only create for assigned class+subject
      if (isTeacher()) {
        $chk=$pdo->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1");
        $chk->execute([getCurrentUserId(),$class_id,$subject_id]);
        if(!$chk->fetchColumn()) throw new Exception('You are not assigned to this class/subject');
      }
      $st=$pdo->prepare("INSERT INTO assignments (title,description,class_id,subject_id,teacher_id,due_date,due_time,total_marks,is_active) VALUES (?,?,?,?,?,?,?,?,?)");
      $st->execute([$title,$desc,$class_id,$subject_id,$teacher_id,$due_date,$due_time,$marks,$active]);
      $assignmentId = (int)$pdo->lastInsertId();
      $message='Assignment created';

      // Notify superadmins about new assignment
      try {
        $sa = $pdo->query("SELECT id FROM users WHERE role='superadmin' AND COALESCE(is_active,1)=1")->fetchAll();
        if ($sa) {
          // Build message parts
          $tStmt = $pdo->prepare("SELECT first_name,last_name,username FROM users WHERE id=?");
          $tStmt->execute([$teacher_id]);
          $tRow = $tStmt->fetch();
          $tName = trim(($tRow['first_name']??'').' '.($tRow['last_name']??''));
          if ($tName==='') { $tName = (string)($tRow['username']??'Teacher'); }
          $cStmt = $pdo->prepare("SELECT class_name,class_code FROM classes WHERE id=?");
          $cStmt->execute([$class_id]);
          $cRow = $cStmt->fetch();
          $classLabel = (($cRow['class_name']??'-').' (#'.($cRow['class_code']??'-').')');
          $sStmt = $pdo->prepare("SELECT subject_name,subject_code FROM subjects WHERE id=?");
          $sStmt->execute([$subject_id]);
          $sRow = $sStmt->fetch();
          $subjectLabel = (($sRow['subject_name']??'-').' ('.($sRow['subject_code']??'-').')');

          $titleN = 'Assignment Created';
          $msgN = $tName.' created \"'.$title.'\" for '.$classLabel.' — '.$subjectLabel.' (Due: '.$due_date.' '.$due_time.')';
          $actionUrl = './assignment_detail.php?id='.($assignmentId>0?$assignmentId:0);
          $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?,?,?,?,?)");
          foreach ($sa as $row) {
            $ins->execute([(int)$row['id'], $titleN, $msgN, 'info', $actionUrl]);
          }
        }
      } catch (Throwable $e) { /* swallow */ }
    }elseif($a==='update'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      $title=trim($_POST['title']??'');$desc=trim($_POST['description']??'');
      $class_id=(int)($_POST['class_id']??0);$subject_id=(int)($_POST['subject_id']??0);
      if (isTeacher()) {
        // Ensure the assignment belongs to this teacher
        $own=$pdo->prepare('SELECT COUNT(*) FROM assignments WHERE id=? AND teacher_id=?');
        $own->execute([$id,getCurrentUserId()]);
        if(!$own->fetchColumn()) throw new Exception('Not authorized to edit this assignment');
        $teacher_id = getCurrentUserId();
        // And class/subject pair is within assignment scope
        $chk=$pdo->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1");
        $chk->execute([$teacher_id,$class_id,$subject_id]);
        if(!$chk->fetchColumn()) throw new Exception('You are not assigned to this class/subject');
      } else {
        $teacher_id=(int)($_POST['teacher_id']??0);
      }
      $due_date=$_POST['due_date']??'';$due_time=$_POST['due_time']??'';$marks=(int)($_POST['total_marks']??100);$active=isset($_POST['is_active'])?1:0;
      if($title===''||$class_id<=0||$subject_id<=0||$teacher_id<=0||$due_date===''||$due_time==='') throw new Exception('All required fields must be filled');
      $st=$pdo->prepare("UPDATE assignments SET title=?,description=?,class_id=?,subject_id=?,teacher_id=?,due_date=?,due_time=?,total_marks=?,is_active=? WHERE id=?");
      $st->execute([$title,$desc,$class_id,$subject_id,$teacher_id,$due_date,$due_time,$marks,$active,$id]);
      $message='Assignment updated';
    }elseif($a==='delete'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      if (isTeacher()) {
        // Only delete own assignment
        $st=$pdo->prepare("DELETE FROM assignments WHERE id=? AND teacher_id=?");
        $st->execute([$id,getCurrentUserId()]);
        if($st->rowCount()===0) throw new Exception('Not authorized to delete this assignment');
      } else {
        $st=$pdo->prepare("DELETE FROM assignments WHERE id=?");$st->execute([$id]);
      }
      $message='Assignment deleted';
    }
  }
}catch(Throwable $e){$error=$e->getMessage();}

$teacherIdSession = getCurrentUserId();
if (isTeacher()) {
  // Limit classes/subjects to those assigned to this teacher
  $s=$pdo->prepare("SELECT DISTINCT c.id,c.class_name,c.class_code,c.grade_level,c.academic_year
                    FROM teacher_assignments ta JOIN classes c ON c.id=ta.class_id
                    WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(c.is_active,1)=1
                    ORDER BY c.grade_level,c.class_name");
  $s->execute([$teacherIdSession]);
  $classes=$s->fetchAll();
  $s=$pdo->prepare("SELECT DISTINCT s.id,s.subject_name,s.subject_code
                    FROM teacher_assignments ta JOIN subjects s ON s.id=ta.subject_id
                    WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(s.is_active,1)=1
                    ORDER BY s.subject_name");
  $s->execute([$teacherIdSession]);
  $subjects=$s->fetchAll();
  // Teachers list is only self
  $teachers=[[ 'id'=>$teacherIdSession, 'first_name'=>$_SESSION['first_name']??'', 'last_name'=>$_SESSION['last_name']??'', 'username'=>$_SESSION['username']??'', 'email'=>$_SESSION['email']??'' ]];
} else {
  $classes=getClasses($pdo);$subjects=getSubjects($pdo);$teachers=getTeachers($pdo);
}
$classesCount=count($classes); $subjectsCount=count($subjects); $teachersCount=count($teachers);
if (isTeacher()) {
  $st=$pdo->prepare("SELECT a.*,c.class_name,c.class_code,c.grade_level,c.academic_year,s.subject_name,s.subject_code,u.first_name,u.last_name,u.username
                     FROM assignments a
                     LEFT JOIN classes c ON c.id=a.class_id
                     LEFT JOIN subjects s ON s.id=a.subject_id
                     LEFT JOIN users u ON u.id=a.teacher_id
                     WHERE a.teacher_id=?
                     ORDER BY a.created_at DESC,a.due_date ASC,a.due_time ASC");
  $st->execute([$teacherIdSession]);
  $assignments=$st->fetchAll();
} else {
  $assignments=$pdo->query("SELECT a.*,c.class_name,c.class_code,c.grade_level,c.academic_year,s.subject_name,s.subject_code,u.first_name,u.last_name,u.username FROM assignments a LEFT JOIN classes c ON c.id=a.class_id LEFT JOIN subjects s ON s.id=a.subject_id LEFT JOIN users u ON u.id=a.teacher_id ORDER BY a.created_at DESC,a.due_date ASC,a.due_time ASC")->fetchAll();
}

include '../components/header.php';
?>
<style>
/* Force 3-column grid for assignments */
#assignmentsGrid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
@media (max-width: 1024px) { #assignmentsGrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) { #assignmentsGrid { grid-template-columns: 1fr; } }

.assignment-card .teacher-avatar{background:#eef2ff;color:#4338ca}
.assignment-meta{font-size:12px;color:#6b7280;margin-top:6px}
.assignment-desc{font-size:13px;color:#374151;margin-top:8px}
.assignment-top{display:flex;justify-content:space-between;align-items:center;margin-top:6px}
.assignment-badges{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-primary{background:#dbeafe;color:#1e40af}
.badge-danger{background:#fee2e2;color:#991b1b}
.badge-success{background:#dcfce7;color:#166534}
.badge-neutral{background:#f3f4f6;color:#374151}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Assignments</h1></div>
    <div class="card-header">
      <div class="search-input-wrapper" style="max-width:420px;width:100%"><i class="fas fa-search"></i>
        <input id="assignmentSearchInput" type="text" class="table-search-input" placeholder="Search by title, class, subject, teacher..." />
      </div>
      <div class="card-header--right">
        <button type="button" class="btn btn-primary" onclick="openCreateAssignmentModal()"><i class="fas fa-plus"></i> New Assignment</button>
      </div>
    </div>
    <div class="card-content">
      <?php if(!empty($assignments)): ?>
      <div id="assignmentsGrid" class="cards-grid">
        <?php foreach($assignments as $a): $teacher=trim(($a['first_name']??'').' '.($a['last_name']??'')); $teacher=$teacher!==''?$teacher:($a['username']??''); $cls=(($a['class_name']??'-').' ('.($a['class_code']??'-').')'); $sub=(($a['subject_name']??'-').' #'.($a['subject_code']??'-')); $due=(($a['due_date']??'').' '.($a['due_time']??'')); $safe=json_encode($a, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
          $dueTs = @strtotime(($a['due_date']??'').' '.($a['due_time']??''));
          $isClosed = $dueTs !== false && $dueTs < time();
          $statusText = $isClosed ? 'Closed' : 'Open';
          $statusClass = $isClosed ? 'badge-danger' : 'badge-success';
          $marksVal = (int)($a['total_marks']??0);
        ?>
        <div class="teacher-card assignment-card">
          <div class="teacher-avatar"><i class="fas fa-tasks"></i></div>
          <div class="teacher-name"><strong><a href="./assignment_detail.php?id=<?php echo (int)$a['id']; ?>" style="text-decoration:none;color:inherit;"><?php echo htmlspecialchars($a['title']); ?></a></strong></div>
          <div class="assignment-top">
            <span class="badge badge-neutral">Marks: <?php echo $marksVal; ?></span>
            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
          </div>
          <div class="teacher-username">Teacher: <?php echo htmlspecialchars($teacher); ?></div>
          <div class="assignment-meta">Class: <?php echo htmlspecialchars($cls); ?> • Subject: <?php echo htmlspecialchars($sub); ?></div>
          <div class="assignment-badges">
            <span class="badge badge-primary">Due: <?php echo htmlspecialchars($due); ?></span>
          </div>
          <div class="teacher-card-actions action-buttons centered">
            <a class="btn btn-sm btn-primary" href="./assignment_detail.php?id=<?php echo (int)$a['id']; ?>" title="View"><i class="fas fa-eye"></i></a>
            <button class="btn btn-sm btn-primary" type="button" onclick='openEditAssignmentModal(<?php echo (int)$a['id']; ?>, <?php echo $safe; ?>)' title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-error" type="button" onclick="openDeleteAssignmentModal(<?php echo (int)$a['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="no-data"><i class="fas fa-tasks"></i><p>No assignments yet. Create your first one.</p><button type="button" class="btn btn-primary" onclick="openCreateAssignmentModal()">Create Assignment</button></div>
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

// Teacher field rendering
$teacherFieldCreate = '';
if (isTeacher()) {
  $teacherFieldCreate = '<div class="form-group"><label>Teacher</label><div class="muted">You</div><input type="hidden" name="teacher_id" value="'.(int)$teacherIdSession.'"></div>';
} else {
  $teacherFieldCreate = '<div class="form-group"><label>Teacher *</label><select name="teacher_id" required><option value="">Select teacher</option>'.$teacherOpts.'</select></div>';
}

$createForm = '
<form id="createAssignmentForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="create">
  <div class="muted" style="margin-bottom:8px; font-size:12px; color:#6b7280;">
    Classes: '.(int)$classesCount.' • Subjects: '.(int)$subjectsCount.' • Teachers: '.(int)$teachersCount.'
    '.(($classesCount&&$subjectsCount&&$teachersCount)?'':' — Some lists are empty. Ensure data exists and is active.').'
  </div>
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" required></div>
    <div class="form-group"><label>Total Marks</label><input type="number" name="total_marks" min="1" value="100"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-row">
    '.$teacherFieldCreate.'
    <div class="form-group"><label>Active</label><label><input type="checkbox" name="is_active" checked> Active</label></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Due Date *</label><input type="date" name="due_date" required></div>
    <div class="form-group"><label>Due Time *</label><input type="time" name="due_time" required></div>
  </div>
  <div class="form-group"><label>Description</label><textarea name="description" placeholder="Optional notes..."></textarea></div>
</form>';
renderFormModal('createAssignmentModal','Create Assignment',$createForm,'Create','Cancel',['size'=>'large','formId'=>'createAssignmentForm']);

$editForm = '
<form id="editAssignmentForm" method="POST" class="form" data-validate="true">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" id="edit_id">
  <div class="muted" style="margin-bottom:8px; font-size:12px; color:#6b7280;">
    Classes: '.(int)$classesCount.' • Subjects: '.(int)$subjectsCount.' • Teachers: '.(int)$teachersCount.'
    '.(($classesCount&&$subjectsCount&&$teachersCount)?'':' — Some lists are empty. Ensure data exists and is active.').'
  </div>
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" id="edit_title" required></div>
    <div class="form-group"><label>Total Marks</label><input type="number" name="total_marks" id="edit_marks" min="1" value="100"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" id="edit_class" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" id="edit_subject" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-row">
    '.(isTeacher() ? ('<div class="form-group"><label>Teacher</label><div class="muted">You</div><input type="hidden" name="teacher_id" value="'.(int)$teacherIdSession.'"></div>') : ('<div class="form-group"><label>Teacher *</label><select name="teacher_id" id="edit_teacher" required><option value="">Select teacher</option>'.$teacherOpts.'</select></div>')).'
    <div class="form-group"><label>Active</label><label><input type="checkbox" name="is_active" id="edit_active"> Active</label></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Due Date *</label><input type="date" name="due_date" id="edit_date" required></div>
    <div class="form-group"><label>Due Time *</label><input type="time" name="due_time" id="edit_time" required></div>
  </div>
  <div class="form-group"><label>Description</label><textarea name="description" id="edit_desc"></textarea></div>
</form>';
renderFormModal('editAssignmentModal','Edit Assignment',$editForm,'Save','Cancel',['size'=>'large','formId'=>'editAssignmentForm']);

renderConfirmModal('deleteAssignmentModal','Delete Assignment','Are you sure you want to delete this assignment?','Delete','Cancel',['type'=>'warning','onConfirm'=>'handleDeleteAssignment']);
?>
<script>
function openCreateAssignmentModal(){ if(typeof window.openModalCreateAssignmentModal==='function'){window.openModalCreateAssignmentModal();} else { var m=document.getElementById('createAssignmentModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function submitCreateAssignment(){ var f=document.getElementById('createAssignmentForm'); if(f) f.submit(); }
function openEditAssignmentModal(id,data){
  document.getElementById('edit_id').value=id;
  document.getElementById('edit_title').value=data.title||'';
  document.getElementById('edit_marks').value=data.total_marks||100;
  document.getElementById('edit_class').value=data.class_id||'';
  document.getElementById('edit_subject').value=data.subject_id||'';
  var et=document.getElementById('edit_teacher'); if(et){ et.value=data.teacher_id||''; }
  document.getElementById('edit_active').checked=!!Number(data.is_active||0);
  document.getElementById('edit_date').value=data.due_date||'';
  document.getElementById('edit_time').value=data.due_time||'';
  document.getElementById('edit_desc').value=data.description||'';
  if(typeof window.openModalEditAssignmentModal==='function'){window.openModalEditAssignmentModal();} else { var m=document.getElementById('editAssignmentModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
function submitEditAssignment(){ var f=document.getElementById('editAssignmentForm'); if(f) f.submit(); }
var currentAssignmentId=null;
function openDeleteAssignmentModal(id){ currentAssignmentId=id; if(typeof window.openModalDeleteAssignmentModal==='function'){window.openModalDeleteAssignmentModal();} else { var m=document.getElementById('deleteAssignmentModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function handleDeleteAssignment(){ if(!currentAssignmentId) return; var form=document.createElement('form'); form.method='POST'; var t1=document.createElement('input'); t1.type='hidden'; t1.name='csrf_token'; t1.value='<?php echo generateCSRFToken(); ?>'; form.appendChild(t1); var t2=document.createElement('input'); t2.type='hidden'; t2.name='action'; t2.value='delete'; form.appendChild(t2); var t3=document.createElement('input'); t3.type='hidden'; t3.name='id'; t3.value=String(currentAssignmentId); form.appendChild(t3); document.body.appendChild(form); form.submit(); }
// Search filter
(function(){ document.addEventListener('DOMContentLoaded',function(){ var input=document.getElementById('assignmentSearchInput'); var grid=document.getElementById('assignmentsGrid'); if(!input||!grid) return; var cards=[].slice.call(grid.querySelectorAll('.teacher-card')); function norm(s){return (s||'').toLowerCase();} function f(){ var q=norm(input.value); cards.forEach(function(c){ var m=!q||norm(c.textContent).indexOf(q)!==-1; c.style.display=m?'':'none'; }); } input.addEventListener('input',f); }); })();
// Toasts
<?php if ($message || $error): ?>
document.addEventListener('DOMContentLoaded',function(){ <?php if($message): ?> if(typeof showNotification==='function') showNotification(<?php echo json_encode($message); ?>,'success'); <?php endif; ?> <?php if($error): ?> if(typeof showNotification==='function') showNotification(<?php echo json_encode($error); ?>,'error'); <?php endif; ?> });
<?php endif; ?>
</script>
<?php include '../components/footer.php'; ?>
