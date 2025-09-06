<?php
require_once '../config/config.php';
if (!isLoggedIn() || !(isTeacher() || isSuperAdmin())) { redirect('../auth/login.php'); }

$pdo = getDBConnection();
$teacherId = getCurrentUserId();
$isAdmin = isSuperAdmin();
$page_title = $isAdmin ? 'Manage Lessons' : 'My Lessons';
$current_page = 'teacher_lessons';

// Ensure upload dir exists
$uploadRoot = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$resourceDir = $uploadRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
if (!is_dir($resourceDir)) { @mkdir($resourceDir, 0775, true); }

// Helpers
function tHas(PDO $pdo, int $tid, int $cid, int $sid): bool {
  $st = $pdo->prepare('SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND COALESCE(is_active,1)=1');
  $st->execute([$tid,$cid,$sid]);
  return (int)$st->fetchColumn() > 0;
}
function tClasses(PDO $pdo, int $tid): array {
  $q=$pdo->prepare('SELECT DISTINCT c.id,c.class_name,c.class_code,c.grade_level,c.academic_year FROM teacher_assignments ta JOIN classes c ON c.id=ta.class_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 ORDER BY c.grade_level,c.class_name');
  $q->execute([$tid]);
  return $q->fetchAll();
}
function tSubjects(PDO $pdo, int $tid): array {
  $q=$pdo->prepare('SELECT DISTINCT s.subject_id AS id, s.title AS subject_name, s.subject_code FROM teacher_assignments ta JOIN subjects s ON s.subject_id=ta.subject_id WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 ORDER BY s.title');
  $q->execute([$tid]);
  return $q->fetchAll();
}

$message = isset($_SESSION['flash_success']) ? (string)$_SESSION['flash_success'] : '';
$error = isset($_SESSION['flash_error']) ? (string)$_SESSION['flash_error'] : '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
$csrf = generateCSRFToken();

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Detect when PHP discards POST because of post_max_size/upload_max_filesize
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
      throw new Exception('Request too large. Please reduce file sizes or increase server limits (post_max_size, upload_max_filesize).');
    }
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { throw new Exception('Invalid CSRF token. Please refresh the page and try again.'); }
    $act = $_POST['action'] ?? '';

    if ($act === 'create' || $act === 'update') {
      $lessonId = $act==='update' ? (int)($_POST['lesson_id'] ?? 0) : 0;
      if ($act==='update' && $lessonId<=0) { throw new Exception('Invalid lesson'); }

      $classId = (int)($_POST['class_id'] ?? 0);
      $subjectId = (int)($_POST['subject_id'] ?? 0);
      $lessonNumber = trim($_POST['lesson_number'] ?? '');
      $title = trim($_POST['title'] ?? '');
      $description = trim($_POST['description'] ?? '');
      // Vocabulary is now free text (textarea)
      $vocabularyText = trim($_POST['vocabulary'] ?? '');
      // Content is now a file upload (PDF/DOC/DOCX)
      $contentPath = null;
      $video = trim($_POST['video'] ?? '');

      if ($classId<=0 || $subjectId<=0) { throw new Exception('Class and subject are required'); }
      if (!$isAdmin && !tHas($pdo, $teacherId, $classId, $subjectId)) { throw new Exception('You are not assigned to this class and subject'); }

      $vocabularyPath = null; // legacy: keep variable for compatibility when editing old lessons
      $thumbnailPath = null;

      // Content upload (pdf/doc/docx) — swapped from vocabulary
      if (!empty($_FILES['content']['name']) && is_uploaded_file($_FILES['content']['tmp_name'])) {
        $tmp = $_FILES['content']['tmp_name'];
        $name = $_FILES['content']['name'];
        $size = (int)$_FILES['content']['size'];
        if ($size > MAX_FILE_SIZE) { throw new Exception('Content file too large'); }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx'];
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? @finfo_file($finfo, $tmp) : null;
        $allowedM = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($ext,$allowed,true) || ($mime && !in_array($mime,$allowedM,true))) { throw new Exception('Content must be PDF/DOC/DOCX'); }
        $newName = 'content_'.$teacherId.'_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $dest = $resourceDir.$newName;
        if (!@move_uploaded_file($tmp,$dest)) { throw new Exception('Failed to upload content'); }
        $contentPath = '../uploads/resources/'.$newName; // relative from pages
      }

      // Thumbnail upload (image)
      if (!empty($_FILES['thumbnail']['name']) && is_uploaded_file($_FILES['thumbnail']['tmp_name'])) {
        $tmp = $_FILES['thumbnail']['tmp_name'];
        $name = $_FILES['thumbnail']['name'];
        $size = (int)$_FILES['thumbnail']['size'];
        if ($size > MAX_FILE_SIZE) { throw new Exception('Thumbnail too large'); }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? @finfo_file($finfo, $tmp) : null;
        $allowedM = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($ext,$allowed,true) || ($mime && !in_array($mime,$allowedM,true))) { throw new Exception('Thumbnail must be an image'); }
        $newName = 'thumb_'.$teacherId.'_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $dest = $resourceDir.$newName;
        if (!@move_uploaded_file($tmp,$dest)) { throw new Exception('Failed to upload thumbnail'); }
        $thumbnailPath = '../uploads/resources/'.$newName;
      }

      if ($act === 'create') {
        $stmt = $pdo->prepare('INSERT INTO lessons (subject_id,class_id,lesson_number,title,description,vocabulary,content,thumbnail,video) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$subjectId,$classId,$lessonNumber?:null,$title,$description,$vocabularyText,$contentPath,$thumbnailPath,$video]);
        $_SESSION['flash_success'] = 'Lesson created';
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'teacher_lessons.php'));
        exit;
      } else {
        $ex = $pdo->prepare('SELECT vocabulary,thumbnail,content FROM lessons WHERE lesson_id=?');
        $ex->execute([$lessonId]);
        $prev = $ex->fetch();
        if (!$prev) { throw new Exception('Lesson not found'); }
        // Preserve previous file paths when not replaced
        $contentPath = $contentPath ?: ($prev['content'] ?? null);
        // Vocabulary is now text; if left empty, preserve previous text if any
        if ($vocabularyText === '') { $vocabularyText = (string)($prev['vocabulary'] ?? ''); }
        $thumbnailPath = $thumbnailPath ?: ($prev['thumbnail'] ?? null);
        $stmt = $pdo->prepare('UPDATE lessons SET subject_id=?,class_id=?,lesson_number=?,title=?,description=?,vocabulary=?,content=?,thumbnail=?,video=? WHERE lesson_id=?');
        $stmt->execute([$subjectId,$classId,$lessonNumber?:null,$title,$description,$vocabularyText,$contentPath,$thumbnailPath,$video,$lessonId]);
        $_SESSION['flash_success'] = 'Lesson updated';
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'teacher_lessons.php'));
        exit;
      }
    } elseif ($act === 'delete') {
      $lessonId = (int)($_POST['lesson_id'] ?? 0);
      if ($lessonId<=0) { throw new Exception('Invalid lesson'); }
      $row = $pdo->prepare('SELECT class_id,subject_id,vocabulary,thumbnail FROM lessons WHERE lesson_id=?');
      $row->execute([$lessonId]);
      $L = $row->fetch();
      if (!$L) { throw new Exception('Lesson not found'); }
      if (!$isAdmin && !tHas($pdo, $teacherId, (int)$L['class_id'], (int)$L['subject_id'])) { throw new Exception('Not allowed'); }
      foreach (['vocabulary','thumbnail','content'] as $f) {
        if (!empty($L[$f])) {
          $fsPath = realpath(__DIR__ . '/' . $L[$f]);
          if ($fsPath && strpos($fsPath, realpath(__DIR__ . '/..')) === 0) { @unlink($fsPath); }
        }
      }
      $pdo->prepare('DELETE FROM lessons WHERE lesson_id=?')->execute([$lessonId]);
      $_SESSION['flash_success'] = 'Lesson deleted';
      header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'teacher_lessons.php'));
      exit;
    }
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flash_error'] = $error;
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'teacher_lessons.php'));
    exit;
  }
}

// Load filters and list
if ($isAdmin) {
  $classes = $pdo->query('SELECT id,class_name,class_code,grade_level,academic_year FROM classes ORDER BY grade_level,class_name')->fetchAll();
  $subjects = $pdo->query('SELECT subject_id AS id, title AS subject_name, subject_code FROM subjects ORDER BY title')->fetchAll();
} else {
  $classes = tClasses($pdo,$teacherId);
  $subjects = tSubjects($pdo,$teacherId);
}
$fClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$fSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$params = [];
$sql = 'SELECT l.*, c.class_name,c.class_code, s.title AS subject_name, s.subject_code FROM lessons l LEFT JOIN classes c ON c.id=l.class_id LEFT JOIN subjects s ON s.subject_id=l.subject_id WHERE 1=1';
if ($fClass>0) { $sql .= ' AND l.class_id = ?'; $params[] = $fClass; }
if ($fSubject>0) { $sql .= ' AND l.subject_id = ?'; $params[] = $fSubject; }
$sql .= $isAdmin ? '' : ' AND EXISTS (SELECT 1 FROM teacher_assignments ta WHERE ta.teacher_id=? AND ta.class_id=l.class_id AND ta.subject_id=l.subject_id AND COALESCE(ta.is_active,1)=1)';
if (!$isAdmin) { $params[] = $teacherId; }
$sql .= ' ORDER BY l.lesson_number IS NULL, l.lesson_number, l.lesson_id DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$lessons = $st->fetchAll();

include '../components/header.php';
?>
<!-- Quill.js Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1><?php echo htmlspecialchars($page_title); ?></h1></div>
    <?php if ($message): ?>
      <script>document.addEventListener('DOMContentLoaded',function(){ if(typeof showNotification==='function'){ showNotification(<?php echo json_encode($message); ?>,'success'); } });</script>
    <?php endif; ?>
    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded',function(){ if(typeof showNotification==='function'){ showNotification(<?php echo json_encode($error); ?>,'error'); } });</script>
    <?php endif; ?>

    <div class="card-header">
      <form method="GET" class="form" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <div class="form-group" style="min-width:220px;">
          <select name="class_id">
            <option value="0">All Classes</option>
            <?php foreach ($classes as $c): $id=(int)$c['id']; ?>
              <option value="<?php echo $id; ?>" <?php echo $fClass===$id?'selected':''; ?>><?php echo htmlspecialchars(($c['class_name']??'').' #'.($c['class_code']??'')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="min-width:220px;">
          <select name="subject_id">
            <option value="0">All Subjects</option>
            <?php foreach ($subjects as $s): $id=(int)$s['id']; ?>
              <option value="<?php echo $id; ?>" <?php echo $fSubject===$id?'selected':''; ?>><?php echo htmlspecialchars(($s['subject_name']??'').' #'.($s['subject_code']??'')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-secondary" type="submit">Filter</button>
      </form>
      <div class="card-header--right">
        <button class="btn btn-primary" type="button" onclick="openCreateLessonModal()"><i class="fas fa-plus"></i> New Lesson</button>
      </div>
    </div>

    <div class="card-content">
      <?php if (!$lessons): ?>
        <div class="empty-state">No lessons yet. Create your first one.</div>
      <?php else: ?>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
          <?php foreach ($lessons as $L): ?>
            <div class="teacher-card">
              <!-- Top Thumbnail -->
              <div class="lesson-thumb" style="width:100%; height:160px; border-radius:12px; overflow:hidden; background:#f3f4f6; display:block;">
                <?php if (!empty($L['thumbnail'])): ?>
                  <img src="<?php echo htmlspecialchars((strpos($L['thumbnail'],'http')===0)?$L['thumbnail']:'../uploads/resources/'.basename($L['thumbnail'])); ?>" alt="Thumbnail" style="width:100%; height:100%; object-fit:cover; display:block;" />
                <?php else: ?>
                  <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-weight:600;">No Image</div>
                <?php endif; ?>
              </div>

              <div class="teacher-name" style="margin-top:10px;"><strong><?php echo htmlspecialchars(($L['lesson_number']?($L['lesson_number'].' - '):'').$L['title']); ?></strong></div>
              <div class="resource-meta">Class: <?php echo htmlspecialchars(($L['class_name']??'').' #'.($L['class_code']??'')); ?> • Subject: <?php echo htmlspecialchars(($L['subject_name']??'').' #'.($L['subject_code']??'')); ?></div>
              <!-- Description intentionally hidden on cards to keep layout compact; visible on detail page -->
              <div class="resource-badges">
                <?php if (!empty($L['vocabulary'])): ?><span class="badge"><i class="fas fa-file-alt"></i> Vocabulary</span><?php endif; ?>
                <?php if (!empty($L['video'])): ?><span class="badge"><i class="fas fa-video"></i> Video</span><?php endif; ?>
              </div>
              <div class="teacher-card-actions action-buttons centered">
                <a class="btn btn-sm btn-secondary" href="../pages/lesson_detail.php?lesson_id=<?php echo (int)$L['lesson_id']; ?>"><i class="fas fa-eye"></i></a>
                <button class="btn btn-sm btn-primary" type="button" onclick='openEditLessonModal(<?php echo (int)$L['lesson_id']; ?>, <?php echo json_encode($L, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-error" type="button" onclick='openDeleteLessonModal(<?php echo (int)$L['lesson_id']; ?>)'><i class="fas fa-trash"></i></button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
function opts(array $items, string $idKey, callable $labelFn): string { $h=''; foreach ($items as $it){ $id=(int)$it[$idKey]; $h.='<option value="'.$id.'">'.htmlspecialchars($labelFn($it)).'</option>'; } return $h; }
$classOpts = opts($classes,'id',fn($c)=>($c['class_name']??'-').' #'.($c['class_code']??''));
$subjectOpts = opts($subjects,'id',fn($s)=>($s['subject_name']??'-').' #'.($s['subject_code']??''));

$createForm = '
<form id="lessonCreateForm" method="POST" class="form" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="'.htmlspecialchars($csrf).'">
  <input type="hidden" name="action" value="create">
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Lesson #</label><input type="text" name="lesson_number" placeholder="e.g., 1, Week 2, Unit 3"></div>
    <div class="form-group"><label>Title</label><input type="text" name="title"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Content (PDF/DOC/DOCX)</label><input type="file" name="content" accept=".pdf,.doc,.docx"></div>
    <div class="form-group"><label>Thumbnail (Image)</label><input type="file" name="thumbnail" accept="image/*"></div>
  </div>
  <div class="form-group"><label>Video URL</label><input type="url" name="video" placeholder="https://... (YouTube, etc.)"></div>
  <div class="col-12">
    <div class="form-group">
      <label for="create_description" class="sr-only">Description</label>
      <textarea class="form-control bg-light" id="create_description" name="description" rows="6" style="display:none;"></textarea>
      <label for="create_description_quill" class="form-label" style="display:block; margin:6px 0 6px; font-weight:600;">Description</label>
      <div id="create_description_quill" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
    </div>
  </div>
  <div class="col-12">
    <div class="form-group">
      <label for="create_vocabulary" class="sr-only">Vocabulary</label>
      <textarea class="form-control bg-light" id="create_vocabulary" name="vocabulary" rows="6" style="display:none;"></textarea>
      <label for="create_vocabulary_quill" class="form-label" style="display:block; margin:6px 0 6px; font-weight:600;">Vocabulary</label>
      <div id="create_vocabulary_quill" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
    </div>
  </div>
</form>';
renderFormModal('createLessonModal','Create Lesson',$createForm,'Create','Cancel',['size'=>'large','formId'=>'lessonCreateForm']);

$editForm = '
<form id="lessonEditForm" method="POST" class="form" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="'.htmlspecialchars($csrf).'">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="lesson_id" id="edit_lesson_id">
  <div class="form-row">
    <div class="form-group"><label>Class *</label><select name="class_id" id="edit_class_id" required><option value="">Select class</option>'.$classOpts.'</select></div>
    <div class="form-group"><label>Subject *</label><select name="subject_id" id="edit_subject_id" required><option value="">Select subject</option>'.$subjectOpts.'</select></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Lesson #</label><input type="text" name="lesson_number" id="edit_lesson_number"></div>
    <div class="form-group"><label>Title</label><input type="text" name="title" id="edit_title"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Replace Content (PDF/DOC/DOCX)</label><input type="file" name="content" accept=".pdf,.doc,.docx"></div>
    <div class="form-group"><label>Replace Thumbnail</label><input type="file" name="thumbnail" accept="image/*"></div>
  </div>
  <div class="form-group"><label>Video URL</label><input type="url" name="video" id="edit_video" placeholder="https://..."></div>
  <div class="col-12">
    <div class="form-group">
      <label for="edit_description" class="sr-only">Description</label>
      <textarea class="form-control bg-light" id="edit_description" name="description" rows="6" style="display:none;"></textarea>
      <label for="edit_description_quill" class="form-label" style="display:block; margin:6px 0 6px; font-weight:600;">Description</label>
      <div id="edit_description_quill" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
    </div>
  </div>
  <div class="col-12">
    <div class="form-group">
      <label for="edit_vocabulary" class="sr-only">Vocabulary</label>
      <textarea class="form-control bg-light" id="edit_vocabulary" name="vocabulary" rows="6" style="display:none;"></textarea>
      <label for="edit_vocabulary_quill" class="form-label" style="display:block; margin:6px 0 6px; font-weight:600;">Vocabulary</label>
      <div id="edit_vocabulary_quill" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
    </div>
  </div>
</form>';
renderFormModal('editLessonModal','Edit Lesson',$editForm,'Save','Cancel',['size'=>'large','formId'=>'lessonEditForm']);

renderConfirmModal('deleteLessonModal','Delete Lesson','Are you sure you want to delete this lesson?','Delete','Cancel',['type'=>'warning','onConfirm'=>'confirmDeleteLesson']);
?>
<script>
function openCreateLessonModal(){
  if(typeof window.openModalCreateLessonModal==='function'){window.openModalCreateLessonModal();} else { var m=document.getElementById('createLessonModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
  // Initialize Quill editors for Create modal
  initQuillOnce('create_description_quill','create_description');
  initQuillOnce('create_vocabulary_quill','create_vocabulary');
  // Set initial (empty) content based on hidden textareas
  setQuillHtml('create_description_quill', document.getElementById('create_description').value || '');
  setQuillHtml('create_vocabulary_quill', document.getElementById('create_vocabulary').value || '');
}
function openEditLessonModal(id, data){
  document.getElementById('edit_lesson_id').value = id;
  document.getElementById('edit_class_id').value = data.class_id || '';
  document.getElementById('edit_subject_id').value = data.subject_id || '';
  document.getElementById('edit_lesson_number').value = data.lesson_number || '';
  document.getElementById('edit_title').value = data.title || '';
  document.getElementById('edit_description').value = data.description || '';
  document.getElementById('edit_vocabulary').value = data.vocabulary || '';
  document.getElementById('edit_video').value = data.video || '';
  if(typeof window.openModalEditLessonModal==='function'){window.openModalEditLessonModal();} else { var m=document.getElementById('editLessonModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } }

  // Initialize Quill editors for Edit modal
  initQuillOnce('edit_description_quill','edit_description');
  initQuillOnce('edit_vocabulary_quill','edit_vocabulary');
  // Set initial HTML content
  setQuillHtml('edit_description_quill', document.getElementById('edit_description').value);
  setQuillHtml('edit_vocabulary_quill', document.getElementById('edit_vocabulary').value);
}
var deleteLessonId = null;
function openDeleteLessonModal(id){ deleteLessonId = id; if(typeof window.openModalDeleteLessonModal==='function'){window.openModalDeleteLessonModal();} else { var m=document.getElementById('deleteLessonModal'); if(m){m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function confirmDeleteLesson(){ if(!deleteLessonId) return; var f=document.createElement('form'); f.method='POST'; var t1=document.createElement('input'); t1.type='hidden'; t1.name='csrf_token'; t1.value=<?php echo json_encode($csrf); ?>; f.appendChild(t1); var t2=document.createElement('input'); t2.type='hidden'; t2.name='action'; t2.value='delete'; f.appendChild(t2); var t3=document.createElement('input'); t3.type='hidden'; t3.name='lesson_id'; t3.value=String(deleteLessonId); f.appendChild(t3); document.body.appendChild(f); f.submit(); }

// Quill helpers
var __quill_instances = {};
function initQuillOnce(quillId, textareaId){
  if (!window.Quill) return;
  if (__quill_instances[quillId]) return __quill_instances[quillId];
  var el = document.getElementById(quillId);
  var ta = document.getElementById(textareaId);
  if (!el || !ta) return;
  var q = new Quill('#'+quillId, {
    theme: 'snow',
    modules: { toolbar: [ [{ header: [1,2,3,false]}], ['bold','italic','underline','strike'], [{color:[]},{background:[]}], [{list:'ordered'},{list:'bullet'}], [{indent:'-1'},{indent:'+1'}], [{align:[]}], ['link','image'], ['clean'] ] }
  });
  q.on('text-change', function(){ ta.value = q.root.innerHTML; });
  __quill_instances[quillId] = q;
  return q;
}
function setQuillHtml(quillId, html){ var q=__quill_instances[quillId]; if(q){ q.root.innerHTML = html || ''; } }

// (Keep no-op: initialization now happens inside openCreateLessonModal)

// Ensure content synced on form submit
['lessonCreateForm','lessonEditForm'].forEach(function(fid){
  document.addEventListener('submit', function(ev){
    if (ev.target && ev.target.id === fid){
      ['create_description_quill','create_vocabulary_quill','edit_description_quill','edit_vocabulary_quill'].forEach(function(qid){
        var q = __quill_instances[qid];
        if (q){
          var taId = qid.replace('_quill','');
          var ta = document.getElementById(taId);
          if (ta) ta.value = q.root.innerHTML;
        }
      });
    }
  }, true);
});
</script>
<?php include '../components/footer.php'; ?>
