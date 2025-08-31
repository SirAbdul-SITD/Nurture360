<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$message=''; $error='';

function getClasses(PDO $pdo){return $pdo->query("SELECT id,class_name,class_code,grade_level,academic_year FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name")->fetchAll();}
function getSubjects(PDO $pdo){return $pdo->query("SELECT id,subject_name,subject_code FROM subjects WHERE COALESCE(is_active,1)=1 ORDER BY subject_name")->fetchAll();}

try{
  if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validateCSRFToken($_POST['csrf_token']??'')) throw new Exception('Invalid CSRF token');
    $a=$_POST['action']??'';
    if(!isSuperAdmin() && $_SESSION['role']!=='teacher') throw new Exception('Unauthorized');

    if($a==='create'){
      $title=trim($_POST['title']??'');
      $content=$_POST['content_html']??'';
      $class_id=(int)($_POST['class_id']??0) ?: null;
      $subject_id=(int)($_POST['subject_id']??0) ?: null;
      $is_published=isset($_POST['is_published'])?1:0;
      if($title===''||$content==='') throw new Exception('Title and content are required');
      $st=$pdo->prepare("INSERT INTO content_pages (title,content_html,class_id,subject_id,teacher_id,is_published) VALUES (?,?,?,?,?,?)");
      $st->execute([$title,$content,$class_id,$subject_id,getCurrentUserId(),$is_published]);
      $message='Content page created';
    }elseif($a==='update'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      $title=trim($_POST['title']??'');
      $content=$_POST['content_html']??'';
      $class_id=(int)($_POST['class_id']??0) ?: null;
      $subject_id=(int)($_POST['subject_id']??0) ?: null;
      $is_published=isset($_POST['is_published'])?1:0;
      if($title===''||$content==='') throw new Exception('Title and content are required');
      $st=$pdo->prepare("UPDATE content_pages SET title=?,content_html=?,class_id=?,subject_id=?,is_published=? WHERE id=?");
      $st->execute([$title,$content,$class_id,$subject_id,$is_published,$id]);
      $message='Content page updated';
    }elseif($a==='delete'){
      $id=(int)($_POST['id']??0); if($id<=0) throw new Exception('Bad ID');
      $st=$pdo->prepare("DELETE FROM content_pages WHERE id=?");
      $st->execute([$id]);
      $message='Content page deleted';
    }
  }
}catch(Throwable $e){ $error=$e->getMessage(); }

$classes=getClasses($pdo); $subjects=getSubjects($pdo);

$filterClass=(int)($_GET['class_id']??0);
$filterSubject=(int)($_GET['subject_id']??0);
$q = trim($_GET['q']??'');
$where=[]; $params=[];
if($filterClass>0){ $where[]='(cp.class_id=?)'; $params[]=$filterClass; }
if($filterSubject>0){ $where[]='(cp.subject_id=?)'; $params[]=$filterSubject; }
if($q!==''){ $where[]='(cp.title LIKE ?)'; $params[]='%'.$q.'%'; }

$baseSql="FROM content_pages cp
      LEFT JOIN classes c ON c.id=cp.class_id
      LEFT JOIN subjects s ON s.id=cp.subject_id
      LEFT JOIN users u ON u.id=cp.teacher_id";
if($where){ $baseSql.=' WHERE '.implode(' AND ',$where); }

// Pagination and listing with graceful error handling if table is missing
$perPage = 9; $pageNum = max(1, (int)($_GET['page']??1));
try{
  $cnt = $pdo->prepare('SELECT COUNT(*) '.$baseSql); $cnt->execute($params); $totalRows=(int)$cnt->fetchColumn();
  $totalPages = max(1, (int)ceil($totalRows/$perPage));
  if($pageNum>$totalPages) $pageNum=$totalPages;
  $offset = ($pageNum-1)*$perPage;
  $st=$pdo->prepare('SELECT cp.*, c.class_name,c.class_code, s.subject_name,s.subject_code, u.first_name,u.last_name,u.username '.$baseSql.' ORDER BY cp.created_at DESC LIMIT '.$perPage.' OFFSET '.$offset);
  $st->execute($params); $pages=$st->fetchAll();
}catch(Throwable $e){
  $error = $error ?: 'Content module is not initialized (missing tables). Please run the database migrations.';
  $totalPages = 1; $pageNum = 1; $pages = [];
}

$page_title='Content Pages';
include '../components/header.php';
?>
<style>
#contentGrid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
@media(max-width:1024px){#contentGrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){#contentGrid{grid-template-columns:1fr}}
.content-card .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-green{background:#dcfce7;color:#166534}
.badge-gray{background:#f3f4f6;color:#374151}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Content Pages</h1>
      <div class="card-header--right">
        <?php if(isSuperAdmin() || $_SESSION['role']==='teacher'): ?>
        <button type="button" class="btn btn-primary" onclick="openCreateContentModal()"><i class="fas fa-plus"></i> New Page</button>
        <?php endif; ?>
      </div>
    </div>
    <?php if($message || $error): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        <?php if($message): ?>
        if (window.RindaApp && typeof window.RindaApp.showNotification === 'function') {
          window.RindaApp.showNotification(<?php echo json_encode($message); ?>,'success');
        }
        <?php endif; ?>
        <?php if($error): ?>
        if (window.RindaApp && typeof window.RindaApp.showNotification === 'function') {
          window.RindaApp.showNotification(<?php echo json_encode($error); ?>,'error');
        }
        <?php endif; ?>
      });
    </script>
    <?php endif; ?>
    <div class="card-content">
      <div class="form-row" style="margin-bottom:12px">
        <form method="GET" class="form" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
          <div class="form-group"><label>Class</label>
            <select name="class_id"><option value="">All</option>
              <?php foreach($classes as $c): $id=(int)$c['id']; $label=$c['class_name'].' (G'.(int)($c['grade_level']??0).') #'.($c['class_code']??''); ?>
                <option value="<?php echo $id; ?>" <?php echo $filterClass===$id?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Subject</label>
            <select name="subject_id"><option value="">All</option>
              <?php foreach($subjects as $s): $id=(int)$s['id']; $label=($s['subject_name']??'-').' #'.($s['subject_code']??''); ?>
                <option value="<?php echo $id; ?>" <?php echo $filterSubject===$id?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="flex:1;min-width:220px"><label>Search</label><input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search title..."></div>
          <button class="btn btn-secondary" type="submit"><i class="fas fa-filter"></i> Filter</button>
        </form>
      </div>
      <div id="contentGrid" class="cards-grid">
        <?php if($pages): foreach($pages as $p): $author=trim(($p['first_name']??'').' '.($p['last_name']??'')) ?: ($p['username']??'-'); ?>
        <div class="teacher-card content-card">
          <div class="teacher-avatar"><i class="fas fa-file-alt"></i></div>
          <div class="teacher-name"><strong><a href="./content_page_detail.php?id=<?php echo (int)$p['id']; ?>" style="color:inherit;text-decoration:none;"><?php echo htmlspecialchars($p['title']); ?></a></strong></div>
          <div class="virtual-badges"><span class="badge <?php echo $p['is_published']?'badge-green':'badge-gray'; ?>"><?php echo $p['is_published']?'Published':'Draft'; ?></span></div>
          <div class="teacher-username">Author: <?php echo htmlspecialchars($author); ?></div>
          <div class="virtual-meta">Class: <?php echo htmlspecialchars(($p['class_name']??'-').' #'.($p['class_code']??'-')); ?> • Subject: <?php echo htmlspecialchars(($p['subject_name']??'-').' #'.($p['subject_code']??'-')); ?></div>
          <div class="teacher-card-actions action-buttons centered">
            <a class="btn btn-sm btn-secondary" href="./content_page_detail.php?id=<?php echo (int)$p['id']; ?>" title="View"><i class="fas fa-eye"></i></a>
            <?php if(isSuperAdmin() || $_SESSION['role']==='teacher'): ?>
            <button class="btn btn-sm btn-primary" type="button" onclick='openEditContentModal(<?php echo (int)$p['id']; ?>, <?php echo json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)' title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-error" type="button" onclick="openDeleteContentModal(<?php echo (int)$p['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; else: ?>
        <div class="no-data"><i class="fas fa-file-alt"></i><p>No content pages yet.</p></div>
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
$classesOpts=''; foreach($classes as $c){ $id=(int)$c['id']; $label=$c['class_name'].' (G'.(int)($c['grade_level']??0).') #'.($c['class_code']??''); $classesOpts.='<option value="'.$id.'">'.htmlspecialchars($label).'</option>'; }
$subjectsOpts=''; foreach($subjects as $s){ $id=(int)$s['id']; $label=($s['subject_name']??'-').' #'.($s['subject_code']??''); $subjectsOpts.='<option value="'.$id.'">'.htmlspecialchars($label).'</option>'; }

$createForm=''
.'<form id="createContentForm" method="POST" class="form">'
.'<input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">'
.'<input type="hidden" name="action" value="create">'
.'<div class="form-row">'
.'<div class="form-group" style="flex:1"><label>Title *</label><input type="text" name="title" required></div>'
.'<div class="form-group"><label>Published</label><label class="switch"><input type="checkbox" name="is_published"><span class="slider"></span></label></div>'
.'</div>'
.'<div class="form-row">'
.'<div class="form-group"><label>Class</label><select name="class_id"><option value="">-- Any --</option>'.$classesOpts.'</select></div>'
.'<div class="form-group"><label>Subject</label><select name="subject_id"><option value="">-- Any --</option>'.$subjectsOpts.'</select></div>'
.'</div>'
.'<div class="form-group"><label>Content *</label><textarea name="content_html" rows="10" placeholder="Rich text (HTML supported)" required></textarea></div>'
.'</form>';
renderFormModal('createContentModal','Create Content Page',$createForm,'Create','Cancel',['size'=>'large','formId'=>'createContentForm']);

$editForm=''
.'<form id="editContentForm" method="POST" class="form">'
.'<input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">'
.'<input type="hidden" name="action" value="update">'
.'<input type="hidden" name="id" id="edit_id">'
.'<div class="form-row">'
.'<div class="form-group" style="flex:1"><label>Title *</label><input type="text" name="title" id="edit_title" required></div>'
.'<div class="form-group"><label>Published</label><label class="switch"><input type="checkbox" name="is_published" id="edit_published"><span class="slider"></span></label></div>'
.'</div>'
.'<div class="form-row">'
.'<div class="form-group"><label>Class</label><select name="class_id" id="edit_class"><option value="">-- Any --</option>'.$classesOpts.'</select></div>'
.'<div class="form-group"><label>Subject</label><select name="subject_id" id="edit_subject"><option value="">-- Any --</option>'.$subjectsOpts.'</select></div>'
.'</div>'
.'<div class="form-group"><label>Content *</label><textarea name="content_html" id="edit_content" rows="10" required></textarea></div>'
.'</form>';
renderFormModal('editContentModal','Edit Content Page',$editForm,'Save','Cancel',['size'=>'large','formId'=>'editContentForm']);

renderConfirmModal('deleteContentModal','Delete Content Page','Are you sure you want to delete this content page?','Delete','Cancel',['type'=>'warning','onConfirm'=>'handleDeleteContent']);
?>
<script>
function openCreateContentModal(){ if(window.openModalCreateContentModal) window.openModalCreateContentModal(); else { var m=document.getElementById('createContentModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function openEditContentModal(id,data){
  document.getElementById('edit_id').value=id;
  document.getElementById('edit_title').value=data.title||'';
  document.getElementById('edit_content').value=data.content_html||'';
  document.getElementById('edit_class').value=data.class_id||'';
  document.getElementById('edit_subject').value=data.subject_id||'';
  document.getElementById('edit_published').checked = (data.is_published==1);
  if(window.openModalEditContentModal) window.openModalEditContentModal(); else { var m=document.getElementById('editContentModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
}
var currentContentId=null;
function openDeleteContentModal(id){ currentContentId=id; if(window.openModalDeleteContentModal) window.openModalDeleteContentModal(); else { var m=document.getElementById('deleteContentModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function handleDeleteContent(){ if(!currentContentId) return; var f=document.createElement('form'); f.method='POST'; f.innerHTML='<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'+currentContentId+'">'; document.body.appendChild(f); f.submit(); }
// TinyMCE WYSIWYG
</script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({ selector:'textarea[name="content_html"]', menubar:false, plugins:'lists link table autoresize', toolbar:'undo redo | bold italic underline | bullist numlist | link table | removeformat', height:400 });
</script>
<?php include '../components/footer.php'; ?>
