<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

$isAdmin = isSuperAdmin();
$isTeacherRole = isTeacher();
$userId = getCurrentUserId();

$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
if ($subjectId <= 0) { redirect('./exams.php'); }

// Permission: admin or teacher assigned to this subject
if (!$isAdmin) {
  $st = $pdo->prepare('SELECT 1 FROM teacher_assignments WHERE teacher_id=? AND subject_id=? AND COALESCE(is_active,1)=1 LIMIT 1');
  $st->execute([$userId, $subjectId]);
  if (!$st->fetchColumn()) { redirect('../dashboard/index.php'); }
}

$subject = $pdo->prepare('SELECT subject_id AS id, title AS subject_name, subject_code FROM subjects WHERE subject_id=?');
$subject->execute([$subjectId]);
$S = $subject->fetch();
if (!$S) { redirect('./exams.php'); }

$csrf = generateCSRFToken();

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { throw new Exception('Invalid CSRF'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
      $qid = (int)($_POST['question_id'] ?? 0);
      $type = (int)($_POST['type'] ?? 1); // 1=MCQ, 2=Short, 3=Essay/Practical
      $question = trim((string)($_POST['question'] ?? ''));
      $marks = (float)($_POST['marks'] ?? 0);
      $answer = trim((string)($_POST['answer'] ?? ''));
      $option1 = trim((string)($_POST['option1'] ?? '')) ?: null;
      $option2 = trim((string)($_POST['option2'] ?? '')) ?: null;
      $option3 = trim((string)($_POST['option3'] ?? '')) ?: null;
      $option4 = trim((string)($_POST['option4'] ?? '')) ?: null;
      $option5 = trim((string)($_POST['option5'] ?? '')) ?: null;
      $feedback1 = trim((string)($_POST['feedback1'] ?? '')) ?: null;
      $feedback2 = trim((string)($_POST['feedback2'] ?? '')) ?: null;
      $feedback3 = trim((string)($_POST['feedback3'] ?? '')) ?: null;
      $feedback4 = trim((string)($_POST['feedback4'] ?? '')) ?: null;
      $feedback5 = trim((string)($_POST['feedback5'] ?? '')) ?: null;

      if ($question === '' || $marks <= 0) { throw new Exception('Question text and marks are required'); }
      if ($type === 1) {
        // MCQ requires at least three options and an answer (1-5)
        $opts = array_filter([$option1,$option2,$option3,$option4,$option5], fn($v)=>$v!==null && $v!=='');
        if (count($opts) < 3) { throw new Exception('Provide at least three options for MCQ'); }
        if ($answer === '' || !in_array($answer, ['1','2','3','4','5'], true)) { throw new Exception('Answer must be a number 1-5 for MCQ'); }
      }

      if ($action === 'create') {
        $ins = $pdo->prepare('INSERT INTO exam_questions (subject_id,`type`,question,option1,option2,option3,option4,option5,feedback1,feedback2,feedback3,feedback4,feedback5,answer,marks) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([$subjectId,$type,$question,$option1,$option2,$option3,$option4,$option5,$feedback1,$feedback2,$feedback3,$feedback4,$feedback5,$answer,$marks]);
        $_SESSION['flash_success'] = 'Question added';
      } else {
        if ($qid <= 0) { throw new Exception('Invalid question'); }
        $up = $pdo->prepare('UPDATE exam_questions SET `type`=?,question=?,option1=?,option2=?,option3=?,option4=?,option5=?,feedback1=?,feedback2=?,feedback3=?,feedback4=?,feedback5=?,answer=?,marks=? WHERE question_id=? AND subject_id=?');
        $up->execute([$type,$question,$option1,$option2,$option3,$option4,$option5,$feedback1,$feedback2,$feedback3,$feedback4,$feedback5,$answer,$marks,$qid,$subjectId]);
        $_SESSION['flash_success'] = 'Question updated';
      }
      header('Location: '.$_SERVER['REQUEST_URI']);
      exit;
    } elseif ($action === 'delete') {
      $qid = (int)($_POST['question_id'] ?? 0);
      if ($qid <= 0) { throw new Exception('Invalid question'); }
      $pdo->prepare('DELETE FROM exam_questions WHERE question_id=? AND subject_id=?')->execute([$qid,$subjectId]);
      $_SESSION['flash_success'] = 'Question deleted';
      header('Location: '.$_SERVER['REQUEST_URI']);
      exit;
    }
  }
} catch (Throwable $e) {
  $_SESSION['flash_error'] = $e->getMessage();
  header('Location: '.$_SERVER['REQUEST_URI']);
  exit;
}

$qs = $pdo->prepare('SELECT * FROM exam_questions WHERE subject_id=? ORDER BY question_id ASC');
$qs->execute([$subjectId]);
$questions = $qs->fetchAll();

$page_title = 'Exam Questions';
$current_page = 'exams';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1>Questions · <?php echo htmlspecialchars(($S['subject_name']??'').' #'.($S['subject_code']??'')); ?></h1>
        <div class="muted">Manage questions for this subject's exams</div>
      </div>
      <div class="header-actions">
        <a class="btn btn-secondary" href="./exams.php"><i class="fas fa-arrow-left"></i> Back to Exams</a>
        <?php if ($isAdmin || $isTeacherRole): ?>
          <a class="btn btn-primary" href="#" onclick="openAddQModal();return false;"><i class="fas fa-plus"></i> Add Question</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Flash messages are shown via global toasts in components/footer.php -->

    <div class="content-card">
      <div class="card-header"><h3>Questions (<?php echo (int)count($questions); ?>)</h3></div>
      <div class="card-content">
        <?php if (!$questions): ?>
          <div class="empty-state">No questions yet.</div>
        <?php else: ?>
          <div class="table-responsive sticky-area h-scroll">
            <table class="table sticky-head nowrap">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Question</th>
                  <th>Type</th>
                  <th>Marks</th>
                  <th>Answer</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($questions as $i=>$q): ?>
                  <tr>
                    <td><?php echo $i+1; ?></td>
                    <td>
                      <?php echo nl2br(htmlspecialchars($q['question'])); ?>
                      <?php
                        $opts = [];
                        for ($j=1;$j<=5;$j++) { $o = $q['option'.$j]; if ($o !== null && $o !== '') { $opts[] = $o; } }
                      ?>
                      <?php if ($opts): ?>
                        <div class="muted">Options: <?php echo htmlspecialchars(implode(' | ', $opts)); ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo (int)$q['type'] === 1 ? 'MCQ' : ((int)$q['type'] === 2 ? 'Short' : 'Essay/Practical'); ?></td>
                    <td><?php echo htmlspecialchars($q['marks']); ?></td>
                    <td><?php echo htmlspecialchars($q['answer']); ?></td>
                    <td class="action-buttons">
                      <button class="btn btn-sm btn-primary" onclick='openEditQModal(<?php echo (int)$q['question_id']; ?>, <?php echo json_encode($q, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                      <button class="btn btn-sm btn-error" onclick="confirmDeleteQ(<?php echo (int)$q['question_id']; ?>)"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
  ob_start();
?>
  <form id="addQForm" method="post" class="form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>"/>
    <input type="hidden" name="action" value="create"/>
    <input type="hidden" name="question_id" id="q_id"/>

    <div class="form-group">
      <label for="q_question">Question *</label>
      <textarea id="q_question" name="question" rows="4" required></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="q_type">Type *</label>
        <select id="q_type" name="type" onchange="toggleOptionsAdd()" required>
          <option value="1">MCQ</option>
          <option value="2">Short</option>
          <option value="3">Essay/Practical</option>
        </select>
        <small class="form-help">MCQ uses options with answer 1-5. Others use free text answer or leave blank.</small>
      </div>
      <div class="form-group">
        <label for="q_marks">Marks *</label>
        <input type="number" id="q_marks" name="marks" min="0.5" step="0.5" required/>
      </div>
    </div>

    <div id="mcq_options">
      <div class="muted" style="margin-bottom:8px;">For MCQ: provide at least 3 options. Options 4–5 are optional. Feedbacks are optional.</div>
      <div class="form-row">
        <div class="form-group">
          <label for="q_option1">Option 1 <span class="required">*</span></label>
          <input type="text" id="q_option1" name="option1"/>
          <small class="form-help">Optional feedback for option 1</small>
          <input type="text" id="q_feedback1" name="feedback1" placeholder="Feedback 1"/>
        </div>
        <div class="form-group">
          <label for="q_option2">Option 2 <span class="required">*</span></label>
          <input type="text" id="q_option2" name="option2"/>
          <small class="form-help">Optional feedback for option 2</small>
          <input type="text" id="q_feedback2" name="feedback2" placeholder="Feedback 2"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="q_option3">Option 3 <span class="required">*</span></label>
          <input type="text" id="q_option3" name="option3"/>
          <small class="form-help">Optional feedback for option 3</small>
          <input type="text" id="q_feedback3" name="feedback3" placeholder="Feedback 3"/>
        </div>
        <div class="form-group">
          <label for="q_option4">Option 4</label>
          <input type="text" id="q_option4" name="option4"/>
          <small class="form-help">Optional feedback for option 4</small>
          <input type="text" id="q_feedback4" name="feedback4" placeholder="Feedback 4"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="q_option5">Option 5</label>
          <input type="text" id="q_option5" name="option5"/>
          <small class="form-help">Optional feedback for option 5</small>
          <input type="text" id="q_feedback5" name="feedback5" placeholder="Feedback 5"/>
        </div>
        <div class="form-group">
          <label for="q_answer">Correct Answer (1–5) <span class="required">*</span></label>
          <input type="number" id="q_answer" name="answer" placeholder="1–5 for MCQ; free text otherwise" min="1" max="5" step="1"/>
        </div>
      </div>
    </div>
  </form>
<?php
  $formHtml = ob_get_clean();
  // Add modal uses addQForm
  renderFormModal('addQModal', 'Add Question', $formHtml, 'Save', 'Cancel', ['size'=>'large','formId'=>'addQForm','onOpen'=>'resetAddQForm()']);
  // Build Edit form variant with unique IDs and editQForm
  $editForm = str_replace(
    [
      'id="addQForm"', 'value="create"', 'id="q_id"', 'id="q_question"', 'id="q_type"', 'onchange="toggleOptionsAdd()"', 'id="q_marks"',
      'id="mcq_options"', 'id="q_option1"', 'id="q_option2"', 'id="q_option3"', 'id="q_option4"', 'id="q_option5"',
      'id="q_feedback1"', 'id="q_feedback2"', 'id="q_feedback3"', 'id="q_feedback4"', 'id="q_feedback5"', 'id="q_answer"'
    ],
    [
      'id="editQForm"', 'value="update"', 'id="q_id_edit"', 'id="q_question_edit"', 'id="q_type_edit"', 'onchange="toggleOptionsEdit()"', 'id="q_marks_edit"',
      'id="mcq_options_edit"', 'id="q_option1_edit"', 'id="q_option2_edit"', 'id="q_option3_edit"', 'id="q_option4_edit"', 'id="q_option5_edit"',
      'id="q_feedback1_edit"', 'id="q_feedback2_edit"', 'id="q_feedback3_edit"', 'id="q_feedback4_edit"', 'id="q_feedback5_edit"', 'id="q_answer_edit"'
    ],
    $formHtml
  );
  renderFormModal('editQModal', 'Edit Question', $editForm, 'Save', 'Cancel', ['size'=>'large','formId'=>'editQForm']);
  renderConfirmModal('deleteQModal','Delete Question','Delete this question?','Delete','Cancel',['type'=>'warning','onConfirm'=>'doDeleteQ']);
?>
<script>
function openAddQModal(){ resetAddQForm(); if(window.openModalAddQModal) window.openModalAddQModal(); else { var m=document.getElementById('addQModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function resetAddQForm(){ var f=document.getElementById('addQForm'); if(!f) return; f.reset(); f.querySelector('[name=action]').value='create'; var t=document.getElementById('q_type'); if(t){ t.value='1'; } toggleOptionsAdd(); }
function openEditQModal(id,data){ if(window.openModalEditQModal) window.openModalEditQModal(); else { var m=document.getElementById('editQModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } }
  var f=document.getElementById('editQForm'); if(!f) return; f.querySelector('[name=action]').value='update';
  var set = function(id,val){ var el=document.getElementById(id); if(el){ el.value = (val==null? '': val); } };
  set('q_id_edit', id);
  set('q_question_edit', data.question||'');
  set('q_type_edit', String(data.type||'1'));
  set('q_marks_edit', data.marks||1);
  set('q_option1_edit', data.option1||''); set('q_option2_edit', data.option2||''); set('q_option3_edit', data.option3||''); set('q_option4_edit', data.option4||''); set('q_option5_edit', data.option5||'');
  set('q_feedback1_edit', data.feedback1||''); set('q_feedback2_edit', data.feedback2||''); set('q_feedback3_edit', data.feedback3||''); set('q_feedback4_edit', data.feedback4||''); set('q_feedback5_edit', data.feedback5||'');
  set('q_answer_edit', data.answer||'');
  toggleOptionsEdit();
}
function setRequired(ids,on){ ids.forEach(function(id){ var el=document.getElementById(id); if(el){ if(on){ el.setAttribute('required','required'); } else { el.removeAttribute('required'); } } }); }
function toggleOptionsAdd(){ var t=document.getElementById('q_type'); var mcq=document.getElementById('mcq_options'); if(t&&mcq){ var isMcq = (t.value==='1'); mcq.style.display = isMcq ? 'block' : 'none'; setRequired(['q_option1','q_option2','q_option3','q_answer'], isMcq); } }
function toggleOptionsEdit(){ var t=document.getElementById('q_type_edit'); var mcq=document.getElementById('mcq_options_edit'); if(t&&mcq){ var isMcq = (t.value==='1'); mcq.style.display = isMcq ? 'block' : 'none'; setRequired(['q_option1_edit','q_option2_edit','q_option3_edit','q_answer_edit'], isMcq); } }
var delId=null; function confirmDeleteQ(id){ delId=id; if(window.openModalDeleteQModal) window.openModalDeleteQModal(); else { var m=document.getElementById('deleteQModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function doDeleteQ(){ if(!delId) return; var f=document.createElement('form'); f.method='POST'; f.innerHTML='<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="question_id" value="'+delId+'">'; document.body.appendChild(f); f.submit(); }
</script>
<?php include '../components/footer.php'; ?>
