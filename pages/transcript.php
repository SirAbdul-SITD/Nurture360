<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

function isStudentRole(){ return (($_SESSION['role'] ?? '') === 'student'); }
function canManage(){ return isSuperAdmin() || (($_SESSION['role'] ?? '') === 'teacher'); }

function getClasses(PDO $pdo){return $pdo->query("SELECT id,class_name,class_code,grade_level,academic_year FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name")->fetchAll();}
function getStudents(PDO $pdo){$s=$pdo->prepare("SELECT id,first_name,last_name,username FROM users WHERE role='student' AND COALESCE(is_active,1)=1 ORDER BY first_name,last_name");$s->execute();return $s->fetchAll();}
function getSubjects(PDO $pdo){return $pdo->query("SELECT id,subject_name,subject_code FROM subjects WHERE COALESCE(is_active,1)=1 ORDER BY subject_name")->fetchAll();}

$classes = getClasses($pdo); $students = getStudents($pdo); $subjects = getSubjects($pdo);

// Filters
$studentId = (int)($_GET['student_id'] ?? 0);
$classId = (int)($_GET['class_id'] ?? 0);
$academicYear = trim($_GET['academic_year'] ?? '');
$term = $_GET['term'] ?? 'all'; // all|term1|term2|term3
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$weightTests = max(0, min(100, (int)($_GET['w_tests'] ?? 60))); // tests weight percent
$weightAssign = 100 - $weightTests;

// Students can only view their own transcript
if (isStudentRole()) { $studentId = (int)($_SESSION['user_id'] ?? 0); }

// Default date range based on term if not supplied
if ($from==='' || $to===''){
  $yearForRange = date('Y');
  if ($academicYear!=='') { $yearForRange = preg_replace('/[^0-9]/','',$academicYear) ?: date('Y'); }
  if ($term==='term1') { $from = $from ?: ($yearForRange.'-01-01'); $to = $to ?: ($yearForRange.'-04-30'); }
  elseif ($term==='term2') { $from = $from ?: ($yearForRange.'-05-01'); $to = $to ?: ($yearForRange.'-08-31'); }
  elseif ($term==='term3') { $from = $from ?: ($yearForRange.'-09-01'); $to = $to ?: ($yearForRange.'-12-31'); }
}

$params = [];
$whereT = [];
$whereA = [];
if ($studentId>0) { $whereT[]='tr.student_id=?'; $params[]=$studentId; $whereA[]='asub.student_id=?'; }
if ($classId>0) { $whereT[]='t.class_id=?'; $params[]=$classId; $whereA[]='a.class_id=?'; }
if ($academicYear!=='') { $whereT[]='c.academic_year=?'; $params[]=$academicYear; $whereA[]='c2.academic_year=?'; }
if ($from!=='') { $whereT[]='t.scheduled_date>=?'; $params[]=$from; $whereA[]='a.due_date>=?'; }
if ($to!=='') { $whereT[]='t.scheduled_date<=?'; $params[]=$to; $whereA[]='a.due_date<=?'; }

$testsAggSQL = "SELECT s.id AS subject_id, s.subject_name, s.subject_code,
  SUM(tr.obtained_marks) AS t_obtained, SUM(tr.total_marks) AS t_total, COUNT(*) AS t_count
  FROM test_results tr
  INNER JOIN tests t ON t.id=tr.test_id
  LEFT JOIN subjects s ON s.id=t.subject_id
  LEFT JOIN classes c ON c.id=t.class_id" . ($whereT? (' WHERE '.implode(' AND ', $whereT)) : '') . "
  GROUP BY s.id, s.subject_name, s.subject_code";

$assignAggSQL = "SELECT s2.id AS subject_id, s2.subject_name, s2.subject_code,
  SUM(COALESCE(asub.obtained_marks,0)) AS a_obtained, SUM(COALESCE(asub.total_marks,0)) AS a_total, COUNT(*) AS a_count
  FROM assignment_submissions asub
  INNER JOIN assignments a ON a.id=asub.assignment_id
  LEFT JOIN subjects s2 ON s2.id=a.subject_id
  LEFT JOIN classes c2 ON c2.id=a.class_id" . ($whereA? (' WHERE '.implode(' AND ', $whereA)) : '') . "
  GROUP BY s2.id, s2.subject_name, s2.subject_code";

// Execute and merge by subject
$subjMap = [];
$stT = $pdo->prepare($testsAggSQL); $stT->execute($params); while($r=$stT->fetch(PDO::FETCH_ASSOC)){
  $sid = (int)($r['subject_id'] ?? 0); if(!$sid) continue; if(!isset($subjMap[$sid])){ $subjMap[$sid] = ['subject_id'=>$sid,'name'=>$r['subject_name'],'code'=>$r['subject_code'],'t_obtained'=>0,'t_total'=>0,'t_count'=>0,'a_obtained'=>0,'a_total'=>0,'a_count'=>0]; }
  $subjMap[$sid]['t_obtained'] += (int)$r['t_obtained'];
  $subjMap[$sid]['t_total'] += (int)$r['t_total'];
  $subjMap[$sid]['t_count'] += (int)$r['t_count'];
}
$stA = $pdo->prepare($assignAggSQL); $stA->execute($params); while($r=$stA->fetch(PDO::FETCH_ASSOC)){
  $sid = (int)($r['subject_id'] ?? 0); if(!$sid) continue; if(!isset($subjMap[$sid])){ $subjMap[$sid] = ['subject_id'=>$sid,'name'=>$r['subject_name'],'code'=>$r['subject_code'],'t_obtained'=>0,'t_total'=>0,'t_count'=>0,'a_obtained'=>0,'a_total'=>0,'a_count'=>0]; }
  $subjMap[$sid]['a_obtained'] += (int)$r['a_obtained'];
  $subjMap[$sid]['a_total'] += (int)$r['a_total'];
  $subjMap[$sid]['a_count'] += (int)$r['a_count'];
}

// Compute per-subject weighted
$rows = [];
$totalWeighted = 0.0; $subjectsCount = 0;
foreach($subjMap as $sid=>$S){
  $t_pct = ($S['t_total']>0)? ($S['t_obtained']/$S['t_total']*100.0) : null;
  $a_pct = ($S['a_total']>0)? ($S['a_obtained']/$S['a_total']*100.0) : null;
  $weighted = null;
  if ($t_pct!==null || $a_pct!==null){
    $t_val = $t_pct!==null ? $t_pct : 0.0;
    $a_val = $a_pct!==null ? $a_pct : 0.0;
    $w = ($t_val*$weightTests + $a_val*$weightAssign)/100.0;
    $weighted = $w;
    $totalWeighted += $w;
    $subjectsCount++;
  }
  $rows[] = [
    'subject_id'=>$sid,
    'subject'=>$S['name'].' #'.($S['code']??''),
    'tests_pct'=>$t_pct,
    'assign_pct'=>$a_pct,
    'weighted'=>$weighted,
    'tests_items'=>$S['t_count'],
    'assign_items'=>$S['a_count']
  ];
}

// Overall
$overall = $subjectsCount>0 ? round($totalWeighted/$subjectsCount,2) : 0.00;

// Grade mapping
function letter_from_pct($p){ if($p>=85) return 'A'; if($p>=70) return 'B'; if($p>=55) return 'C'; if($p>=40) return 'D'; return 'F'; }
$overallLetter = letter_from_pct($overall);

// Export CSV
if (isset($_GET['export']) && $_GET['export']=='1'){
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=transcript_'.date('Ymd_His').'.csv');
  $out=fopen('php://output','w');
  fputcsv($out,['Subject','Tests %','Assignments %','Weighted %','Tests Items','Assignments Items']);
  foreach($rows as $r){ fputcsv($out,[$r['subject'], $r['tests_pct']!==null?number_format($r['tests_pct'],2):'', $r['assign_pct']!==null?number_format($r['assign_pct'],2):'', $r['weighted']!==null?number_format($r['weighted'],2):'', $r['tests_items'], $r['assign_items']]); }
  fputcsv($out,[]); fputcsv($out,['Overall', '', '', number_format($overall,2).' ('.$overallLetter.')']);
  fclose($out); exit;
}

$page_title = 'Transcript / Term Report';
include '../components/header.php';
?>
<style>
.table{width:100%;border-collapse:collapse}
.table th,.table td{border-bottom:1px solid #e5e7eb;padding:8px;text-align:left}
.transcript-summary{display:flex;gap:12px;flex-wrap:wrap;margin:8px 0}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-primary{background:#dbeafe;color:#1e40af}
.badge-success{background:#dcfce7;color:#166534}
.print-hide{display:inline-flex;gap:8px}
@media print{ .sidebar, .page-header, .card-header, .print-hide, .pagination, .btn { display:none !important; } .main-content{margin:0} }
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Transcript / Term Report</h1></div>
    <div class="card-header">
      <form method="GET" class="form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <div class="form-group"><label>Student</label><select name="student_id" <?php echo isStudentRole()?'disabled':''; ?>><option value="0">Select</option><?php foreach($students as $stu){ $id=(int)$stu['id']; $name=trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username']??''); $sel = ($studentId===$id)?'selected':''; echo '<option value="'.$id.'" '.$sel.'>'.htmlspecialchars($name).'</option>'; } ?></select></div>
        <div class="form-group"><label>Class</label><select name="class_id"><option value="0">All</option><?php foreach($classes as $c){ $id=(int)$c['id']; $label=$c['class_name'].' (G'.(int)($c['grade_level']??0).') #'.($c['class_code']??''); echo '<option value="'.$id.'" '.($classId===$id?'selected':'').'>'.htmlspecialchars($label).'</option>'; } ?></select></div>
        <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" value="<?php echo htmlspecialchars($academicYear); ?>" placeholder="e.g. 2025" style="width:120px"></div>
        <div class="form-group"><label>Term</label><select name="term"><option value="all" <?php echo $term==='all'?'selected':''; ?>>All</option><option value="term1" <?php echo $term==='term1'?'selected':''; ?>>Term 1</option><option value="term2" <?php echo $term==='term2'?'selected':''; ?>>Term 2</option><option value="term3" <?php echo $term==='term3'?'selected':''; ?>>Term 3</option></select></div>
        <div class="form-group"><label>From</label><input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>"></div>
        <div class="form-group"><label>To</label><input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>"></div>
        <div class="form-group"><label>Weight (Tests %)</label><input type="number" min="0" max="100" name="w_tests" value="<?php echo (int)$weightTests; ?>" style="width:90px"></div>
        <div class="print-hide">
          <button class="btn btn-secondary" type="submit">Apply</button>
          <a class="btn btn-primary" href="?<?php echo http_build_query(array_merge($_GET,['export'=>'1'])); ?>"><i class="fas fa-download"></i> Export CSV</a>
          <button class="btn btn-secondary" type="button" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        </div>
      </form>
    </div>

    <div class="card-content">
      <div class="transcript-summary">
        <span class="badge badge-primary">Overall: <?php echo number_format($overall,2); ?>% (<?php echo $overallLetter; ?>)</span>
        <span class="badge">Weighting: Tests <?php echo (int)$weightTests; ?>% / Assignments <?php echo (int)$weightAssign; ?>%</span>
        <?php if($academicYear!==''): ?><span class="badge">Academic Year: <?php echo htmlspecialchars($academicYear); ?></span><?php endif; ?>
        <?php if($term!=='all'): ?><span class="badge">Term: <?php echo htmlspecialchars(strtoupper($term)); ?></span><?php endif; ?>
        <?php if($from||$to): ?><span class="badge">Range: <?php echo htmlspecialchars($from ?: '...'); ?> → <?php echo htmlspecialchars($to ?: '...'); ?></span><?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Tests %</th>
              <th>Assignments %</th>
              <th>Weighted %</th>
              <th># Tests</th>
              <th># Assignments</th>
            </tr>
          </thead>
          <tbody>
            <?php if($rows): foreach($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['subject']); ?></td>
              <td><?php echo $r['tests_pct']!==null?number_format($r['tests_pct'],2).'%' : '<span class="muted">—</span>'; ?></td>
              <td><?php echo $r['assign_pct']!==null?number_format($r['assign_pct'],2).'%' : '<span class="muted">—</span>'; ?></td>
              <td><strong><?php echo $r['weighted']!==null?number_format($r['weighted'],2).'%' : '<span class="muted">—</span>'; ?></strong></td>
              <td><?php echo (int)$r['tests_items']; ?></td>
              <td><?php echo (int)$r['assign_items']; ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="muted">No data for the selected filters.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
