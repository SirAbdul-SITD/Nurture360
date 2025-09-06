<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo=getDBConnection();
$id=(int)($_GET['id']??0); if($id<=0){ redirect('./tests.php'); }
$message=''; $error='';

function canManage(){ return isSuperAdmin() || ($_SESSION['role']??'')==='teacher'; }

try{
  if($_SERVER['REQUEST_METHOD']==='POST' && canManage()){
    if(!validateCSRFToken($_POST['csrf_token']??'')) throw new Exception('Invalid CSRF token');
    $a=$_POST['action']??'';
    if($a==='add_q' || $a==='edit_q'){
      $qid=(int)($_POST['id']??0);
      $qtext=trim($_POST['question_text']??'');
      $qtype=$_POST['question_type']??'objective_single';
      $marks=(int)($_POST['marks']??0);
      $options_raw=$_POST['options']??''; // JSON or line-separated
      $correct=$_POST['correct_answer']??''; // For objective_multiple can be JSON array or comma-separated
      if($qtext===''||$marks<=0) throw new Exception('Question text and marks are required');
      // Normalize options to JSON when objective types
      $optionsJson=null;
      if(in_array($qtype,['objective_single','objective_multiple'])){
        if($options_raw==='') throw new Exception('Options are required for objective questions');
        // Support JSON or newline separated
        $decoded=json_decode($options_raw,true);
        if(!is_array($decoded)){
          $lines=array_values(array_filter(array_map('trim',preg_split('/\r?\n/',$options_raw)),fn($v)=>$v!==''));
          $decoded=$lines;
        }
        $optionsJson=json_encode($decoded, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        // Normalize correct answer
        if($qtype==='objective_multiple'){
          // Accept JSON array or comma-separated values
          $c=json_decode($correct,true);
          if(!is_array($c)){
            $c=array_values(array_filter(array_map('trim',preg_split('/\s*,\s*/',$correct)) , fn($v)=>$v!==''));
          }
          $correct=json_encode($c, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
      }
      if($a==='add_q'){
        $st=$pdo->prepare("INSERT INTO test_questions (test_id,question_text,question_type,marks,options,correct_answer) VALUES (?,?,?,?,?,?)");
        $st->execute([$id,$qtext,$qtype,$marks,$optionsJson,$correct]);
        $message='Question added';
      }else{
        if($qid<=0) throw new Exception('Bad question');
        $st=$pdo->prepare("UPDATE test_questions SET question_text=?,question_type=?,marks=?,options=?,correct_answer=? WHERE id=? AND test_id=?");
        $st->execute([$qtext,$qtype,$marks,$optionsJson,$correct,$qid,$id]);
        $message='Question updated';
      }
    }elseif($a==='delete_q'){
      $qid=(int)($_POST['id']??0); if($qid<=0) throw new Exception('Bad question');
      $st=$pdo->prepare("DELETE FROM test_questions WHERE id=? AND test_id=?");$st->execute([$qid,$id]);
      $message='Question deleted';
    }
  }
}catch(Throwable $e){ $error=$e->getMessage(); }

$test=$pdo->prepare("SELECT t.*, c.class_name,c.class_code, s.title AS subject_name,s.subject_code, u.first_name,u.last_name,u.username FROM tests t LEFT JOIN classes c ON c.id=t.class_id LEFT JOIN subjects s ON s.subject_id=t.subject_id LEFT JOIN users u ON u.id=t.teacher_id WHERE t.id=?");
$test->execute([$id]);
$test=$test->fetch(); if(!$test){ redirect('./tests.php'); }

$qs=$pdo->prepare("SELECT * FROM test_questions WHERE test_id=? ORDER BY id ASC");
$qs->execute([$id]);
$questions=$qs->fetchAll();

// Fetch submissions count for this test
try {
  $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM test_results WHERE test_id = ?");
  $cntStmt->execute([$id]);
  $submissionCount = (int)$cntStmt->fetchColumn();
} catch (Throwable $e) {
  $submissionCount = 0;
}

$page_title='Test Detail';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars($test['title']); ?></h1>
        <div class="muted">Class: <?php echo htmlspecialchars(($test['class_name']??'-').' #'.($test['class_code']??'-')); ?> · Subject: <?php echo htmlspecialchars(($test['subject_name']??'-').' #'.($test['subject_code']??'-')); ?> · Schedule: <?php echo htmlspecialchars(($test['scheduled_date']??'').' '.($test['start_time']??'').' - '.($test['end_time']??'')); ?></div>
      </div>
      <div class="header-actions">
        <a class="btn btn-secondary" href="./tests.php"><i class="fas fa-arrow-left"></i> Back</a>
        <?php if(canManage()): ?><a class="btn btn-primary" href="#" onclick="openAddQModal();return false;"><i class="fas fa-plus"></i> Add Question</a><?php endif; ?>
        <a class="btn" href="./test_take.php?id=<?php echo (int)$test['id']; ?>" title="Preview as Student"><i class="fas fa-play"></i> Preview</a>
        <a class="btn btn-secondary" href="./test_submissions.php?id=<?php echo (int)$test['id']; ?>"><i class="fas fa-list"></i> View Submissions (<?php echo (int)($submissionCount ?? 0); ?>)</a>
      </div>
    </div>

    <div class="card-content">
      <?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>#</th><th>Question</th><th>Type</th><th>Marks</th><th>Correct Answer</th><?php if(canManage()): ?><th>Actions</th><?php endif; ?></tr>
          </thead>
          <tbody>
            <?php if($questions): foreach($questions as $i=>$q): $opts=$q['options']?json_decode($q['options'],true):null; $correctDisp=$q['correct_answer']; if($correctDisp && ($q['question_type']==='objective_multiple')){ $tmp=json_decode($correctDisp,true); if(is_array($tmp)) $correctDisp=implode(', ',$tmp); } ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                <?php if(is_array($opts) && in_array($q['question_type'],['objective_single','objective_multiple'])): ?>
                  <div class="muted">Options: <?php echo htmlspecialchars(implode(' | ', $opts)); ?></div>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($q['question_type']); ?></td>
              <td><?php echo (int)$q['marks']; ?></td>
              <td><?php echo htmlspecialchars($correctDisp??''); ?></td>
              <?php if(canManage()): ?>
              <td>
                <button class="btn btn-sm btn-primary" onclick='openEditQModal(<?php echo (int)$q['id']; ?>, <?php echo json_encode($q, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-error" onclick="confirmDeleteQ(<?php echo (int)$q['id']; ?>)"><i class="fas fa-trash"></i></button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="muted">No questions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
$form='<form id="qForm" method="POST" class="form">'
.'<input type="hidden" name="csrf_token" value="'.generateCSRFToken().'">'
.'<input type="hidden" name="action" value="add_q">'
.'<input type="hidden" name="id" id="q_id">'
.'<div class="form-group"><label>Question Text *</label><textarea name="question_text" id="q_text" rows="4" required></textarea></div>'
.'<div class="form-row">'
.'<div class="form-group"><label>Type *</label><select name="question_type" id="q_type" required onchange="toggleOptions()">'
.'<option value="objective_single">Objective (Single Answer)</option>'
.'<option value="objective_multiple">Objective (Multiple Answers)</option>'
.'<option value="true_false">True/False</option>'
.'<option value="short_answer">Short Answer</option>'
.'<option value="essay">Essay</option>'
.'<option value="practical">Practical (Manual Grading)</option>'
.'</select></div>'
.'<div class="form-group"><label>Marks *</label><input type="number" name="marks" id="q_marks" min="1" required></div>'
.'</div>'
.'<div class="form-group" id="opt_wrap"><label>Options (JSON array or one per line)</label><textarea name="options" id="q_options" rows="4" placeholder="e.g. A\nB\nC\nD"></textarea></div>'
.'<div class="form-group"><label>Correct Answer</label><input type="text" name="correct_answer" id="q_correct" placeholder="Single: exact option text; Multiple: comma-separated or JSON array; T/F: true or false"></div>'
.'</form>';
renderFormModal('addQModal','Add Question',$form,'Save','Cancel',["size"=>"large","formId"=>"qForm","onOpen"=>"resetQForm()"]);

// Reuse for edit
$editForm=str_replace('value=\"add_q\"','value=\"edit_q\"',$form);
renderFormModal('editQModal','Edit Question',$editForm,'Save','Cancel',["size"=>"large","formId"=>"qForm"]);

renderConfirmModal('deleteQModal','Delete Question','Delete this question?','Delete','Cancel',["type"=>"warning","onConfirm"=>"doDeleteQ"]);
?>
<script>
function openAddQModal(){ resetQForm(); if(window.openModalAddQModal) window.openModalAddQModal(); else { var m=document.getElementById('addQModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function resetQForm(){ var f=document.getElementById('qForm'); if(!f) return; f.reset(); f.querySelector('[name=action]').value='add_q'; document.getElementById('q_id').value=''; toggleOptions(); }
function openEditQModal(id,data){ if(window.openModalEditQModal) window.openModalEditQModal(); else { var m=document.getElementById('editQModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } } var f=document.getElementById('qForm'); if(!f) return; f.querySelector('[name=action]').value='edit_q'; document.getElementById('q_id').value=id; document.getElementById('q_text').value=data.question_text||''; document.getElementById('q_type').value=data.question_type||'objective_single'; document.getElementById('q_marks').value=data.marks||1; document.getElementById('q_options').value = data.options ? (function(o){ try{ var arr=JSON.parse(o); if(Array.isArray(arr)) return arr.join('\n'); }catch(e){} return o; })(data.options):''; document.getElementById('q_correct').value=(function(c,qt){ if(qt==='objective_multiple'){ try{ var arr=JSON.parse(c); if(Array.isArray(arr)) return arr.join(', ');}catch(e){} } return c||''; })(data.correct_answer||'', data.question_type||''); toggleOptions(); }
function toggleOptions(){ var t=document.getElementById('q_type').value; document.getElementById('opt_wrap').style.display = (t==='objective_single' || t==='objective_multiple') ? 'block' : 'none'; }
var delId=null; function confirmDeleteQ(id){ delId=id; if(window.openModalDeleteQModal) window.openModalDeleteQModal(); else { var m=document.getElementById('deleteQModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
function doDeleteQ(){ if(!delId) return; var f=document.createElement('form'); f.method='POST'; f.innerHTML='<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="delete_q"><input type="hidden" name="id" value="'+delId+'">'; document.body.appendChild(f); f.submit(); }
</script>
<?php include '../components/footer.php'; ?>
