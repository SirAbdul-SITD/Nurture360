<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$message = '';$error='';

// Ensure upload folder exists
$resourceUploadDir = realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
if (!is_dir($resourceUploadDir)) { @mkdir($resourceUploadDir, 0775, true); }

// Helpers
function getClasses(PDO $pdo){return $pdo->query("SELECT id,class_name,class_code,grade_level,academic_year FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name")->fetchAll();}
function getSubjects(PDO $pdo){return $pdo->query("SELECT id,subject_name,subject_code FROM subjects WHERE COALESCE(is_active,1)=1 ORDER BY subject_name")->fetchAll();}

function determineResourceTypeFromExt($ext){
  $ext = strtolower($ext);
  $doc = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','rtf'];
  $img = ['jpg','jpeg','png','gif','webp'];
  $vid = ['mp4','avi','mov','mkv','webm'];
  $aud = ['mp3','wav','m4a','aac','ogg'];
  if (in_array($ext,$img)) return 'image';
  if (in_array($ext,$vid)) return 'video';
  if (in_array($ext,$aud)) return 'other'; 
  if (in_array($ext,$doc)) return 'document';
  return 'other';
}

try{
  if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validateCSRFToken($_POST['csrf_token']??'')) throw new Exception('Invalid CSRF token');
    $a=$_POST['action']??'';

    if($a==='create' || $a==='update'){
      $id = $a==='update' ? (int)($_POST['id']??0) : 0; if($a==='update' && $id<=0) throw new Exception('Bad ID');
      $title=trim($_POST['title']??'');
      $desc=trim($_POST['description']??'');
      $class_id=(int)($_POST['class_id']??0);
      $subject_id=(int)($_POST['subject_id']??0);
      $is_public=isset($_POST['is_public'])?1:0;
      $url=trim($_POST['url']??'');
      $uploaded_by=getCurrentUserId();
      if($title===''||$class_id<=0||$subject_id<=0) throw new Exception('Title, class and subject are required');

      $file_path=null;$file_size=null;$file_type=null;$resource_type=null;

      // File upload handling
      if(isset($_FILES['file']) && is_array($_FILES['file']) && ($_FILES['file']['error']??UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
        $err=$_FILES['file']['error']??UPLOAD_ERR_OK; if($err!==UPLOAD_ERR_OK) throw new Exception('Upload failed with error code '.$err);
        $tmp=$_FILES['file']['tmp_name']; $name=$_FILES['file']['name']; $size=(int)$_FILES['file']['size'];
        if($size>MAX_FILE_SIZE) throw new Exception('File too large. Max '.(int)(MAX_FILE_SIZE/1048576).'MB');
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if(!in_array($ext, ALLOWED_FILE_TYPES)) throw new Exception('File type not allowed: '.$ext);
        $safeBase=preg_replace('/[^A-Za-z0-9_\-\.]+/','_', pathinfo($name,PATHINFO_FILENAME));
        $newName=$safeBase.'_'.bin2hex(random_bytes(6)).'.'.$ext;
        $dest=$resourceUploadDir.$newName;
        if(!@move_uploaded_file($tmp,$dest)) throw new Exception('Failed to move uploaded file');
        $file_path = '../uploads/resources/'.$newName; // relative from pages
        $file_size = $size;
        $file_type = $ext;
        $resource_type = determineResourceTypeFromExt($ext);
      }

      // If URL provided and no file, treat as link (supports YouTube or any URL)
      if(!$file_path && $url!==''){
        $resource_type='link';
      }

      // If neither file nor url, store as text/other using description
      if(!$file_path && $url===''){
        $resource_type = $resource_type ?: 'other';
      }

      if($a==='create'){
        $st=$pdo->prepare("INSERT INTO learning_resources (title,description,resource_type,file_path,file_size,file_type,url,class_id,subject_id,uploaded_by,is_public) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $st->execute([$title,$desc,$resource_type,$file_path,$file_size,$file_type,$url,$class_id,$subject_id,$uploaded_by,$is_public]);
        $message='Resource created';
      }else{
        // Fetch existing for potential file replacement
        $existing=$pdo->prepare("SELECT file_path FROM learning_resources WHERE id=?");
        $existing->execute([$id]);
        $prev=$existing->fetch();
        $prev_path=$prev['file_path']??null;
        $st=$pdo->prepare("UPDATE learning_resources SET title=?,description=?,resource_type=?,file_path=?,file_size=?,file_type=?,url=?,class_id=?,subject_id=?,is_public=? WHERE id=?");
        $st->execute([$title,$desc,$resource_type,$file_path?:$prev_path,$file_size,$file_type,$url,$class_id,$subject_id,$is_public,$id]);
        $message='Resource updated';
      }
    }
    elseif($a==='delete'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      // remove file on disk if exists
      $s=$pdo->prepare("SELECT file_path FROM learning_resources WHERE id=?"); $s->execute([$id]); $r=$s->fetch();
      if($r && !empty($r['file_path'])){
        $fsPath = realpath(__DIR__ . '/' . $r['file_path']);
        if($fsPath && strpos($fsPath, realpath(__DIR__.'/..'))===0){ @unlink($fsPath); }
      }
      $st=$pdo->prepare("DELETE FROM learning_resources WHERE id=?"); $st->execute([$id]);
      $message='Resource deleted';
    }
  }
}catch(Throwable $e){$error=$e->getMessage();}

$classes=getClasses($pdo);$subjects=getSubjects($pdo);
$classesCount=count($classes); $subjectsCount=count($subjects);
$resources=$pdo->query("SELECT lr.*,c.class_name,c.class_code,c.grade_level,c.academic_year,s.subject_name,s.subject_code,u.username,u.first_name,u.last_name FROM learning_resources lr LEFT JOIN classes c ON c.id=lr.class_id LEFT JOIN subjects s ON s.id=lr.subject_id LEFT JOIN users u ON u.id=lr.uploaded_by ORDER BY lr.created_at DESC")->fetchAll();

include '../components/header.php';
?>
<style>
#resourcesGrid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
@media (max-width: 1024px) { #resourcesGrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) { #resourcesGrid { grid-template-columns: 1fr; } }
.resource-card .teacher-avatar{background:#ecfeff;color:#0369a1}
.resource-meta{font-size:12px;color:#6b7280;margin-top:6px}
.resource-desc{font-size:13px;color:#374151;margin-top:8px}
.resource-badges{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-type{background:#d1fae5;color:#065f46}
.badge-public{background:#e0e7ff;color:#3730a3}
.badge-private{background:#fee2e2;color:#991b1b}
.cards-grid .teacher-card{min-height: 180px}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Learning Resources</h1></div>
    <div class="card-header">
      <div class="search-input-wrapper" style="max-width:420px;width:100%"><i class="fas fa-search"></i>
        <input id="resourceSearchInput" type="text" class="table-search-input" placeholder="Search by title, class, subject..." />
      </div>
      <div class="card-header--right">
        <button type="button" class="btn btn-primary" onclick="openCreateResourceModal()"><i class="fas fa-plus"></i> New Resource</button>
      </div>
    </div>
    <div class="card-content">
      <?php if(!empty($resources)): ?>
      <div id="resourcesGrid" class="cards-grid">
        <?php foreach($resources as $r): $u=trim(($r['first_name']??'').' '.($r['last_name']??'')); $u=$u!==''?$u:($r['username']??''); $cls=(($r['class_name']??'-').' ('.($r['class_code']??'-').')'); $sub=(($r['subject_name']??'-').' #'.($r['subject_code']??'-')); $safe=json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); $isPub=(int)($r['is_public']??0)===1; ?>
        <div class="teacher-card resource-card">
          <div class="teacher-avatar"><i class="fas fa-file"></i></div>
          <div class="teacher-name"><strong><a href="./resource_detail.php?id=<?php echo (int)$r['id']; ?>" style="text-decoration:none; color:inherit;"><?php echo htmlspecialchars($r['title']); ?></a></strong></div>
          <div class="resource-meta">Class: <?php echo htmlspecialchars($cls); ?> • Subject: <?php echo htmlspecialchars($sub); ?></div>
          <div class="resource-badges">
            <span class="badge badge-type"><?php echo htmlspecialchars(ucfirst($r['resource_type'])); ?></span>
            <span class="badge <?php echo $isPub?'badge-public':'badge-private'; ?>"><?php echo $isPub?'Public':'Private'; ?></span>
            <?php if(!empty($r['file_type'])): ?><span class="badge">Ext: <?php echo htmlspecialchars($r['file_type']); ?></span><?php endif; ?>
          </div>
          <?php if(!empty($r['description'])): ?><div class="resource-desc"><?php echo nl2br(htmlspecialchars($r['description'])); ?></div><?php endif; ?>
          <div class="teacher-card-actions action-buttons centered">
            <a class="btn btn-sm btn-secondary" href="./resource_detail.php?id=<?php echo (int)$r['id']; ?>" title="View"><i class="fas fa-eye"></i></a>
            <?php if($r['resource_type']==='link' && !empty($r['url'])): ?>
              <a class="btn btn-sm btn-primary" href="./resource_open.php?id=<?php echo (int)$r['id']; ?>" target="_blank" title="Open Link"><i class="fas fa-link"></i></a>
            <?php elseif(!empty($r['file_path'])): ?>
              <a class="btn btn-sm btn-primary" href="./resource_download.php?id=<?php echo (int)$r['id']; ?>" target="_blank" title="Download/View"><i class="fas fa-download"></i></a>
            <?php endif; ?>
            <button class="btn btn-sm btn-primary" type="button" onclick='openEditResourceModal(<?php echo (int)$r['id']; ?>, <?php echo $safe; ?>)' title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-error" type="button" onclick="openDeleteResourceModal(<?php echo (int)$r['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="no-data"><i class="fas fa-file"></i><p>No learning resources yet. Add the first one.</p><button type="button" class="btn btn-primary" onclick="openCreateResourceModal()">Add Resource</button></div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
function optionsHtml($items,$idKey,$labelCb){$h='';foreach($items as $it){$id=(int)$it[$idKey];$label=call_user_func($labelCb,$it);$h.='<option value="'.$id.'">'.htmlspecialchars($label).'</option>'; }return $h;}
$classOpts=optionsHtml($classes,'id',function($c){$g=isset($c['grade_level'])?('G'.(int)$c['grade_level']):'-';$code=$c['class_code']??'';return $c['class_name'].' ('.$g.') #'.$code;});
$subjectOpts=optionsHtml($subjects,'id',function($s){return ($s['subject_name']??'-').' #'.($s['subject_code']??'');});

$createForm = '
<form id="createResourceForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="create">
  <div class="muted" style="margin-bottom:8px; font-size:12px; color:#6b7280;">
    Classes: '.(int)$classesCount.' • Subjects: '.(int)$subjectsCount.'
  </div>
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" required></div>
    <div class="form-group"><label>Public</label><label><input type="checkbox" name="is_public"> Public</label></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-group"><label>Description / Text Content</label><textarea name="description" placeholder="Optional text or notes..."></textarea></div>
  <div class="form-row">
    <div class="form-group"><label>File (pdf, docx, xlsx, pptx, images, video, audio)</label><input type="file" name="file" /></div>
    <div class="form-group"><label>Or URL (YouTube or any link)</label><input type="url" name="url" placeholder="https://..." /></div>
  </div>
  <p class="muted" style="font-size:12px;color:#6b7280">Provide either a file or a URL, or leave both empty to save text-only resource.</p>
</form>';
renderFormModal('createResourceModal','Add Learning Resource',$createForm,'Create','Cancel',['size'=>'large','formId'=>'createResourceForm']);

$editForm = '
<form id="editResourceForm" method="POST" class="form" data-validate="true" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" id="edit_id">
  <div class="form-row">
    <div class="form-group"><label>Title *</label><input type="text" name="title" id="edit_title" required></div>
    <div class="form-group"><label>Public</label><label><input type="checkbox" name="is_public" id="edit_public"> Public</label></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" id="edit_class" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" id="edit_subject" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-group"><label>Description / Text Content</label><textarea name="description" id="edit_desc"></textarea></div>
  <div class="form-row">
    <div class="form-group"><label>Replace File (optional)</label><input type="file" name="file" /></div>
    <div class="form-group"><label>URL</label><input type="url" name="url" id="edit_url" placeholder="https://..." /></div>
  </div>
</form>';
renderFormModal('editResourceModal','Edit Learning Resource',$editForm,'Save','Cancel',['size'=>'large','formId'=>'editResourceForm']);

renderConfirmModal('deleteResourceModal','Delete Resource','Are you sure you want to delete this resource?','Delete','Cancel',['type'=>'warning','onConfirm'=>'handleDeleteResource']);
?>
<script>
function openCreateResourceModal(){ if(typeof window.openModalCreateResourceModal==='function'){window.openModalCreateResourceModal();} else { var m=document.getElementById('createResourceModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function openEditResourceModal(id,data){
  document.getElementById('edit_id').value=id;
  document.getElementById('edit_title').value=data.title||'';
  document.getElementById('edit_class').value=data.class_id||'';
  document.getElementById('edit_subject').value=data.subject_id||'';
  document.getElementById('edit_public').checked=!!Number(data.is_public||0);
  document.getElementById('edit_desc').value=data.description||'';
  document.getElementById('edit_url').value=data.url||'';
  if(typeof window.openModalEditResourceModal==='function'){window.openModalEditResourceModal();} else { var m=document.getElementById('editResourceModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
var currentResourceId=null;
function openDeleteResourceModal(id){ currentResourceId=id; if(typeof window.openModalDeleteResourceModal==='function'){window.openModalDeleteResourceModal();} else { var m=document.getElementById('deleteResourceModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function handleDeleteResource(){ if(!currentResourceId) return; var form=document.createElement('form'); form.method='POST'; var t1=document.createElement('input'); t1.type='hidden'; t1.name='csrf_token'; t1.value='<?php echo generateCSRFToken(); ?>'; form.appendChild(t1); var t2=document.createElement('input'); t2.type='hidden'; t2.name='action'; t2.value='delete'; form.appendChild(t2); var t3=document.createElement('input'); t3.type='hidden'; t3.name='id'; t3.value=String(currentResourceId); form.appendChild(t3); document.body.appendChild(form); form.submit(); }
// Search filter
(function(){ document.addEventListener('DOMContentLoaded',function(){ var input=document.getElementById('resourceSearchInput'); var grid=document.getElementById('resourcesGrid'); if(!input||!grid) return; var cards=[].slice.call(grid.querySelectorAll('.teacher-card')); function norm(s){return (s||'').toLowerCase();} function f(){ var q=norm(input.value); cards.forEach(function(c){ var m=!q||norm(c.textContent).indexOf(q)!==-1; c.style.display=m?'':'none'; }); } input.addEventListener('input',f); }); })();
// Toasts
<?php if ($message || $error): ?>
document.addEventListener('DOMContentLoaded',function(){ <?php if($message): ?> if(typeof showNotification==='function') showNotification(<?php echo json_encode($message); ?>,'success'); <?php endif; ?> <?php if($error): ?> if(typeof showNotification==='function') showNotification(<?php echo json_encode($error); ?>,'error'); <?php endif; ?> });
<?php endif; ?>
</script>
<?php include '../components/footer.php'; ?>
