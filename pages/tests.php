<?php
require_once '../config/config.php';
if (!isLoggedIn() || (!isSuperAdmin() && !isTeacher())) { redirect('../auth/login.php'); }
$pdo=getDBConnection();
$message=''; $error='';
function canManage(){ return isSuperAdmin() || isTeacher(); }

// Detect optional lesson linkage support
$hasLessonId=false;
try{
  $cols=$pdo->query("SHOW COLUMNS FROM tests")->fetchAll(PDO::FETCH_ASSOC);
  foreach($cols as $c){ if(($c['Field']??'')==='lesson_id'){ $hasLessonId=true; break; } }
}catch(Throwable $e){ $hasLessonId=false; }

function getClasses(PDO $pdo){return $pdo->query("SELECT id,class_name,class_code,grade_level,academic_year FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name")->fetchAll();}
function getSubjects(PDO $pdo){return $pdo->query("SELECT subject_id AS id, title AS subject_name, subject_code FROM subjects WHERE COALESCE(is_active,1)=1 ORDER BY title")->fetchAll();}
function getTeachers(PDO $pdo){$s=$pdo->prepare("SELECT id,first_name,last_name,username FROM users WHERE role='teacher' AND COALESCE(is_active,1)=1 ORDER BY first_name,last_name");$s->execute();return $s->fetchAll();}

try{
  if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!canManage()) throw new Exception('Unauthorized');
    if(!validateCSRFToken($_POST['csrf_token']??'')) throw new Exception('Invalid CSRF token');
    $a=$_POST['action']??'';
    if($a==='create' || $a==='update'){
      $title=trim($_POST['title']??'');
      $desc=trim($_POST['description']??'');
      $class_id=(int)($_POST['class_id']??0);
      $subject_id=(int)($_POST['subject_id']??0);
      // Force teacher_id for teachers
      if (isTeacher()) { $teacher_id = getCurrentUserId(); } else { $teacher_id=(int)($_POST['teacher_id']??0); }
      $test_type=$_POST['test_type']??'quiz';
      $total_marks=(int)($_POST['total_marks']??0);
      $duration=(int)($_POST['duration_minutes']??0);
      $scheduled_date=$_POST['scheduled_date']??'';
      $start_time=$_POST['start_time']??'';
      $end_time=$_POST['end_time']??'';
      $scope=$_POST['scope']??'subject';
      $lesson_id=($scope==='lesson') ? (int)($_POST['lesson_id']??0) : 0;
      if($title===''||$class_id<=0||$subject_id<=0||$teacher_id<=0||$total_marks<=0||$duration<=0||$scheduled_date===''||$start_time===''||$end_time==='') throw new Exception('All fields are required');
      // Teachers can only manage assigned class/subject
      if (isTeacher()) {
        $chk=$pdo->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1");
        $chk->execute([getCurrentUserId(),$class_id,$subject_id]);
        if(!$chk->fetchColumn()) throw new Exception('You are not assigned to this class/subject');
      }
      $sTs=@strtotime($scheduled_date.' '.$start_time); $eTs=@strtotime($scheduled_date.' '.$end_time);
      if($sTs===false||$eTs===false) throw new Exception('Invalid schedule');
      if($eTs<=$sTs) throw new Exception('End time must be after start time');
      if($a==='create'){
        if($hasLessonId){
          $st=$pdo->prepare("INSERT INTO tests (title,description,class_id,subject_id,teacher_id,test_type,total_marks,duration_minutes,scheduled_date,start_time,end_time,lesson_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
          $st->execute([$title,$desc,$class_id,$subject_id,$teacher_id,$test_type,$total_marks,$duration,$scheduled_date,$start_time,$end_time, ($scope==='lesson' && $lesson_id>0)?$lesson_id:null]);
        } else {
          $st=$pdo->prepare("INSERT INTO tests (title,description,class_id,subject_id,teacher_id,test_type,total_marks,duration_minutes,scheduled_date,start_time,end_time) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
          $st->execute([$title,$desc,$class_id,$subject_id,$teacher_id,$test_type,$total_marks,$duration,$scheduled_date,$start_time,$end_time]);
        }
        $message='Test created';
      }else{
        $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
        if (isTeacher()) {
          $own=$pdo->prepare('SELECT COUNT(*) FROM tests WHERE id=? AND teacher_id=?');
          $own->execute([$id,getCurrentUserId()]);
          if(!$own->fetchColumn()) throw new Exception('Not authorized to edit this test');
        }
        if($hasLessonId){
          $st=$pdo->prepare("UPDATE tests SET title=?,description=?,class_id=?,subject_id=?,teacher_id=?,test_type=?,total_marks=?,duration_minutes=?,scheduled_date=?,start_time=?,end_time=?,lesson_id=? WHERE id=?");
          $st->execute([$title,$desc,$class_id,$subject_id,$teacher_id,$test_type,$total_marks,$duration,$scheduled_date,$start_time,$end_time, ($scope==='lesson' && $lesson_id>0)?$lesson_id:null, $id]);
        } else {
          $st=$pdo->prepare("UPDATE tests SET title=?,description=?,class_id=?,subject_id=?,teacher_id=?,test_type=?,total_marks=?,duration_minutes=?,scheduled_date=?,start_time=?,end_time=? WHERE id=?");
          $st->execute([$title,$desc,$class_id,$subject_id,$teacher_id,$test_type,$total_marks,$duration,$scheduled_date,$start_time,$end_time,$id]);
        }
        $message='Test updated';
      }
    }elseif($a==='delete'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      if (isTeacher()) {
        $st=$pdo->prepare("DELETE FROM tests WHERE id=? AND teacher_id=?");
        $st->execute([$id,getCurrentUserId()]);
        if($st->rowCount()===0) throw new Exception('Not authorized to delete this test');
      } else {
        $st=$pdo->prepare("DELETE FROM tests WHERE id=?");$st->execute([$id]);
      }
      $message='Test deleted';
    }
  }
}catch(Throwable $e){ $error=$e->getMessage(); }

$teacherId = getCurrentUserId();
if (isTeacher()) {
  $s=$pdo->prepare("SELECT DISTINCT c.id,c.class_name,c.class_code,c.grade_level,c.academic_year FROM teacher_assignments ta JOIN classes c ON c.id=ta.class_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(c.is_active,1)=1 ORDER BY c.grade_level,c.class_name");
  $s->execute([$teacherId]); $classes=$s->fetchAll();
  $s=$pdo->prepare("SELECT DISTINCT s.subject_id AS id, s.title AS subject_name, s.subject_code FROM teacher_assignments ta JOIN subjects s ON s.subject_id=ta.subject_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(s.is_active,1)=1 ORDER BY s.title");
  $s->execute([$teacherId]); $subjects=$s->fetchAll();
  $teachers=[[ 'id'=>$teacherId, 'first_name'=>$_SESSION['first_name']??'', 'last_name'=>$_SESSION['last_name']??'', 'username'=>$_SESSION['username']??'' ]];
} else {
  $classes=getClasses($pdo); $subjects=getSubjects($pdo); $teachers=getTeachers($pdo);
}

// Search & pagination
$q=trim($_GET['q']??'');
$where=[]; $params=[];
if (isTeacher()) { $where[]='t.teacher_id = ?'; $params[]=$teacherId; }
if($q!==''){
  $where[]='(t.title LIKE ? OR s.title LIKE ? OR c.class_name LIKE ?)';
  $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%';
}
$baseSql="FROM tests t LEFT JOIN classes c ON c.id=t.class_id LEFT JOIN subjects s ON s.subject_id=t.subject_id LEFT JOIN users u ON u.id=t.teacher_id";
if($where){ $baseSql.=' WHERE '.implode(' AND ',$where); }
$perPage=9; $pageNum=max(1,(int)($_GET['page']??1));
$cnt=$pdo->prepare('SELECT COUNT(*) ' . $baseSql); $cnt->execute($params); $totalRows=(int)$cnt->fetchColumn();
$totalPages=max(1,(int)ceil($totalRows/$perPage)); if($pageNum>$totalPages) $pageNum=$totalPages; $offset=($pageNum-1)*$perPage;
$st=$pdo->prepare('SELECT t.*, c.class_name,c.class_code, s.title AS subject_name, s.subject_code, u.first_name,u.last_name,u.username ' . $baseSql . ' ORDER BY t.scheduled_date DESC, t.start_time DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
$st->execute($params); $tests=$st->fetchAll();

$page_title='Tests & Quizzes';
include '../components/header.php';
?>
<style>
#testsGrid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
@media(max-width:1024px){#testsGrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){#testsGrid{grid-template-columns:1fr}}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-danger{background:#fee2e2;color:#991b1b}.badge-success{background:#dcfce7;color:#166534}.badge-primary{background:#dbeafe;color:#1e40af}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Tests & Quizzes</h1></div>
    <div class="card-header">
      <form method="GET" class="form" style="display:flex;gap:8px;align-items:center;flex:1;max-width:420px">
        <div class="search-input-wrapper" style="max-width:420px;width:100%">
          <i class="fas fa-search"></i>
          <input class="table-search-input" type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search by title, class, subject...">
        </div>
      </form>
      <div class="card-header--right">
        <?php if(canManage()): ?>
          <button type="button" class="btn btn-primary" onclick="openCreateTestModal()"><i class="fas fa-plus"></i> New Test</button>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-content">
      <div id="testsGrid" class="cards-grid">
        <?php if($tests): foreach($tests as $t): $teacher=trim(($t['first_name']??'').' '.($t['last_name']??'')) ?: ($t['username']??''); $sched=(($t['scheduled_date']??'').' '.($t['start_time']??'').' - '.($t['end_time']??'')); $now=time(); $startTs=@strtotime(($t['scheduled_date']??'').' '.($t['start_time']??'')); $endTs=@strtotime(($t['scheduled_date']??'').' '.($t['end_time']??'')); $status='Upcoming'; $cls='badge-primary'; if($startTs&&$endTs){ if($now>$endTs){ $status='Ended'; $cls='badge-danger'; } elseif($now>=$startTs && $now<=$startTs + (int)0 && $now<=$endTs){ $status='Live'; $cls='badge-success'; } elseif($now>=$startTs && $now<=$endTs){ $status='Live'; $cls='badge-success'; } } $isLive=($status==='Live'); $isStudent=(isset($_SESSION['role']) && $_SESSION['role']==='student'); ?>
        <div class="teacher-card">
          <div class="teacher-avatar"><i class="fas fa-question-circle"></i></div>
          <div class="teacher-name"><strong><a href="./test_detail.php?id=<?php echo (int)$t['id']; ?>" style="text-decoration:none;color:inherit;">&nbsp;<?php echo htmlspecialchars($t['title']); ?></a></strong></div>
          <div class="virtual-badges"><span class="badge <?php echo $cls; ?>"><?php echo $status; ?></span><span class="badge">Type: <?php echo htmlspecialchars($t['test_type']); ?></span><span class="badge">Marks: <?php echo (int)$t['total_marks']; ?></span><span class="badge">Dur: <?php echo (int)$t['duration_minutes']; ?>m</span></div>
          <div class="teacher-username">Teacher: <?php echo htmlspecialchars($teacher); ?></div>
          <div class="virtual-meta">Class: <?php echo htmlspecialchars(($t['class_name']??'-').' #'.($t['class_code']??'-')); ?> • Subject: <?php echo htmlspecialchars(($t['subject_name']??'-').' #'.($t['subject_code']??'-')); ?></div>
          <div class="virtual-meta">Schedule: <?php echo htmlspecialchars($sched); ?></div>
          <div class="teacher-card-actions action-buttons centered">
            <?php if(canManage()): ?>
              <a class="btn btn-sm btn-secondary" href="./test_detail.php?id=<?php echo (int)$t['id']; ?>" title="Manage"><i class="fas fa-eye"></i></a>
              <button class="btn btn-sm btn-primary" type="button" onclick='openEditTestModal(<?php echo (int)$t['id']; ?>, <?php echo json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)' title="Edit"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-error" type="button" onclick="openDeleteTestModal(<?php echo (int)$t['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
            <?php endif; ?>
            <?php if($isStudent): ?>
              <a class="btn btn-sm btn-success" href="./test_take.php?id=<?php echo (int)$t['id']; ?>" title="Take" <?php echo $isLive?'' :'style="opacity:0.6;pointer-events:none"'; ?>><i class="fas fa-play"></i></a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; else: ?>
        <div class="no-data" style="grid-column: 1 / -1; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height: 45vh; text-align:center; color:#6b7280;">
          <i class="fas fa-question-circle" style="font-size:48px; margin-bottom:8px; color:#9ca3af;"></i>
          <p style="font-size:18px; margin:0;">No tests yet.</p>
        </div>
        <?php endif; ?>
      </div>
      <?php if($totalPages>1): $qs=$_GET; unset($qs['page']); $query=http_build_query($qs); ?>
      <div class="pagination" style="margin-top:12px;display:flex;gap:6px;align-items:center">
        <?php for($i=1;$i<=$totalPages;$i++): $active=($i===$pageNum); ?>
          <a class="btn btn-sm <?php echo $active?'btn-primary':'btn-secondary'; ?>" href="?<?php echo $query.($query?'&':'').'page='.$i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
$clsOpts=''; foreach($classes as $c){ $id=(int)$c['id']; $label=$c['class_name'].' (G'.(int)($c['grade_level']??0).') #'.($c['class_code']??''); $clsOpts.='<option value="'.$id.'">'.htmlspecialchars($label).'</option>'; }
$subOpts=''; foreach($subjects as $s){ $id=(int)$s['id']; $label=($s['subject_name']??'-').' #'.($s['subject_code']??''); $subOpts.='<option value="'.$id.'">'.htmlspecialchars($label).'</option>'; }
$teachOpts=''; foreach($teachers as $t){ $id=(int)$t['id']; $name=trim(($t['first_name']??'').' '.($t['last_name']??'')) ?: ($t['username']??''); $teachOpts.='<option value="'.$id.'">'.htmlspecialchars($name).'</option>'; }

$teacherFieldCreate = isTeacher()
  ? '<div class="form-group"><label>Teacher</label><div class="muted">You</div><input type="hidden" name="teacher_id" value="'.(int)$teacherId.'"></div>'
  : '<div class="form-group"><label>Teacher *</label><select name="teacher_id" required><option value="">Select</option>'.$teachOpts.'</select></div>';

$form=''
.'<form id="createTestForm" method="POST" class="form">'
.'<input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">'
.'<input type="hidden" name="action" value="create">'
.'<div class="form-row">'
.'<div class="form-group" style="flex:1"><label>Title *</label><input type="text" name="title" required></div>'
.'<div class="form-group"><label>Type *</label><select name="test_type" required><option value="quiz">Quiz</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="final">Final</option></select></div>'
.'</div>'
.'<div class="form-row">'
.'<div class="form-group"><label>Class *</label><select name="class_id" required><option value="">Select</option>'.$clsOpts.'</select></div>'
.'<div class="form-group"><label>Subject *</label><select name="subject_id" required><option value="">Select</option>'.$subOpts.'</select><div class="muted">Filtered by selected class</div></div>'
.$teacherFieldCreate
.'</div>'
.($hasLessonId
  ? '<div class="form-row">'
    .'<div class="form-group"><label>Scope *</label><select name="scope" id="scope_create" required>'
      .'<option value="subject" selected>Whole subject</option>'
      .'<option value="lesson">Specific lesson</option>'
    .'</select></div>'
    .'<div class="form-group" id="lesson_wrapper_create" style="display:none">'
      .'<label>Lesson *</label><select name="lesson_id" id="lesson_id_create"><option value="">Select lesson</option></select>'
      .'<div class="muted">Lessons filtered by selected Class and Subject</div>'
    .'</div>'
  .'</div>'
  : ''
)
.'<div class="form-row">'
.'<div class="form-group"><label>Total Marks *</label><input type="number" name="total_marks" min="1" required></div>'
.'<div class="form-group"><label>Duration (minutes) *</label><input type="number" name="duration_minutes" min="1" required></div>'
.'</div>'
.'<div class="form-row">'
.'<div class="form-group"><label>Scheduled Date *</label><input type="date" name="scheduled_date" required></div>'
.'<div class="form-group"><label>Start Time *</label><input type="time" name="start_time" required></div>'
.'<div class="form-group"><label>End Time *</label><input type="time" name="end_time" required></div>'
.'</div>'
.'<div class="form-group"><label>Description</label><textarea name="description"></textarea></div>'
.'</form>';
renderFormModal('createTestModal','Create Test',$form,'Create','Cancel',['size'=>'large','formId'=>'createTestForm']);

$editForm = $form;
// Update action to update and form id
$editForm = str_replace('name="action" value="create"','name="action" value="update"',$editForm);
$editForm = str_replace('id="createTestForm"','id="editTestForm"',$editForm);
// Insert hidden id input right after the action hidden input
$editForm = str_replace(
  '<input type="hidden" name="action" value="update">',
  '<input type="hidden" name="action" value="update"><input type="hidden" name="id" id="edit_id">',
  $editForm
);
// Make IDs unique for edit modal controls
$editForm = str_replace('id="scope_create"','id="scope_edit"',$editForm);
$editForm = str_replace('id="lesson_wrapper_create"','id="lesson_wrapper_edit"',$editForm);
$editForm = str_replace('id="lesson_id_create"','id="lesson_id_edit"',$editForm);
if (isTeacher()) {
  // Replace teacher select with hidden field + label for edit form
  $editForm = str_replace('<div class="form-group"><label>Teacher *</label><select name="teacher_id" required><option value="">Select</option>'.$teachOpts.'</select></div>', '<div class="form-group"><label>Teacher</label><div class="muted">You</div><input type="hidden" name="teacher_id" value="'.(int)$teacherId.'"></div>', $editForm);
}
renderFormModal('editTestModal','Edit Test',$editForm,'Save','Cancel',['size'=>'large','formId'=>'editTestForm']);

renderConfirmModal('deleteTestModal','Delete Test','Are you sure you want to delete this test?','Delete','Cancel',["type"=>"warning","onConfirm"=>"handleDeleteTest"]);
?>
<script>
// Fetch subjects by class
async function fetchClassSubjects(classId){
  if(!classId) return [];
  try{
    const res = await fetch('../api/subjects_by_class.php?class_id='+encodeURIComponent(classId));
    if(!res.ok) return [];
    return await res.json();
  }catch(e){ return []; }
}

function bindSubjectFilter(formId){
  const form = document.getElementById(formId);
  if(!form) return;
  const classSel = form.querySelector('[name="class_id"]');
  const subjSel = form.querySelector('[name="subject_id"]');
  const lessonWrap = formId==='createTestForm' ? document.getElementById('lesson_wrapper_create') : document.getElementById('lesson_wrapper_edit');
  const lessonSel = formId==='createTestForm' ? document.getElementById('lesson_id_create') : document.getElementById('lesson_id_edit');
  async function reloadSubjects(){
    const cid = classSel && classSel.value ? classSel.value : '';
    const current = subjSel ? subjSel.value : '';
    subjSel.innerHTML = '<option value="">Select</option>';
    if(!cid){ if(lessonSel){ lessonSel.innerHTML='<option value="">Select lesson</option>'; } return; }
    const list = await fetchClassSubjects(cid);
    (list||[]).forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = (s.subject_name||'') + (s.subject_code?(' #'+s.subject_code):'');
      subjSel.appendChild(opt);
    });
    // Try to keep previously selected subject if still available
    if(current){ subjSel.value = current; }
    // Reset lessons when subject list changes
    if(lessonSel){ lessonSel.innerHTML = '<option value="">Select lesson</option>'; }
    if(lessonWrap){ /* visibility handled elsewhere by scope controls */ }
  }
  if(classSel){ classSel.addEventListener('change', reloadSubjects); }
}
// Helper to fetch lessons for class+subject
async function fetchLessons(classId, subjectId){
  if(!classId || !subjectId) return [];
  try{
    const res = await fetch('../api/lessons_by_class_subject.php?class_id='+encodeURIComponent(classId)+'&subject_id='+encodeURIComponent(subjectId));
    if(!res.ok) return [];
    return await res.json();
  }catch(e){ return []; }
}

function bindCreateScopeControls(){
  const scope = document.getElementById('scope_create');
  const wrap = document.getElementById('lesson_wrapper_create');
  const lessonSel = document.getElementById('lesson_id_create');
  if(!scope || !wrap || !lessonSel) return;
  function toggle(){ wrap.style.display = (scope.value==='lesson') ? '' : 'none'; if(scope.value!=='lesson'){ lessonSel.value=''; } }
  async function maybeLoad(){
    if(scope.value!=='lesson') return;
    const f=document.getElementById('createTestForm'); if(!f) return;
    const cls=f.querySelector('[name="class_id"]').value; const sub=f.querySelector('[name="subject_id"]').value;
    const list = await fetchLessons(cls, sub); lessonSel.innerHTML='<option value="">Select lesson</option>'+(list||[]).map(l=>'<option value="'+(l.lesson_id||'')+'">'+((l.lesson_number?l.lesson_number+' - ':'')+(l.title||''))+'</option>').join('');
  }
  scope.addEventListener('change', ()=>{ toggle(); maybeLoad(); });
  const f=document.getElementById('createTestForm'); if(f){
    ['class_id','subject_id'].forEach(n=>{ const el=f.querySelector('[name="'+n+'"]'); if(el){ el.addEventListener('change', maybeLoad); }});
  }
  toggle();
}

function openCreateTestModal(){ if(window.openModalCreateTestModal) window.openModalCreateTestModal(); else { var m=document.getElementById('createTestModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function openEditTestModal(id,data){
  document.getElementById('edit_id').value=id; var f=document.getElementById('editTestForm'); if(!f) return;
  ['title','test_type','class_id','subject_id','teacher_id','total_marks','duration_minutes','scheduled_date','start_time','end_time','description'].forEach(function(k){ var el=f.querySelector('[name="'+k+'"]'); if(el) el.value = data[k]||''; });
  // Scope + lesson handling (if present)
  var scopeSel=document.getElementById('scope_edit'); var lessonSel=document.getElementById('lesson_id_edit'); var lessonWrap=document.getElementById('lesson_wrapper_edit');
  if(scopeSel){
    var hasLesson = !!(data['lesson_id']); scopeSel.value = hasLesson ? 'lesson' : 'subject';
    if(lessonWrap) lessonWrap.style.display = hasLesson ? '' : 'none';
    if(hasLesson && lessonSel){
      // populate lessons list then set value
      (async function(){
        const cls=f.querySelector('[name="class_id"]').value; const sub=f.querySelector('[name="subject_id"]').value;
        const list = await fetchLessons(cls, sub); lessonSel.innerHTML='<option value="">Select lesson</option>'+(list||[]).map(l=>'<option value="'+(l.lesson_id||'')+'">'+((l.lesson_number?l.lesson_number+' - ':'')+(l.title||''))+'</option>').join('');
        lessonSel.value = data['lesson_id']||'';
      })();
    } else if(lessonSel){ lessonSel.value=''; }
  }
  if(window.openModalEditTestModal) window.openModalEditTestModal(); else { var m=document.getElementById('editTestModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
  // Ensure listeners are active
  bindEditScopeControls();
  // Reload subject list according to selected class for Edit form, then set selected subject
  (async function(){
    const f = document.getElementById('editTestForm');
    if(!f) return;
    bindSubjectFilter('editTestForm');
    const classId = f.querySelector('[name="class_id"]').value;
    const subjSel = f.querySelector('[name="subject_id"]');
    const list = await fetchClassSubjects(classId);
    subjSel.innerHTML = '<option value="">Select</option>' + (list||[]).map(s=>'<option value="'+s.id+'">'+((s.subject_name||'')+(s.subject_code?(' #'+s.subject_code):''))+'</option>').join('');
    subjSel.value = data['subject_id']||'';
  })();
}
var currentTestId=null; function openDeleteTestModal(id){ currentTestId=id; if(window.openModalDeleteTestModal) window.openModalDeleteTestModal(); else { var m=document.getElementById('deleteTestModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function handleDeleteTest(){ if(!currentTestId) return; var f=document.createElement('form'); f.method='POST'; f.innerHTML='<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'+currentTestId+'">'; document.body.appendChild(f); f.submit(); }

// Initialize dynamic controls when modals are present
document.addEventListener('DOMContentLoaded', function(){
  bindCreateScopeControls();
  bindEditScopeControls();
  bindSubjectFilter('createTestForm');
  bindSubjectFilter('editTestForm');
});
</script>
<?php include '../components/footer.php'; ?>
