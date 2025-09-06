<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

function canManageGrades(){ return isSuperAdmin() || (($_SESSION['role'] ?? '') === 'teacher'); }
function isStudentRole(){ return (($_SESSION['role'] ?? '') === 'student'); }

// Detect the primary key column in `subjects` table (`id` vs `subject_id`)
function getSubjectPkColumn(PDO $pdo){
  static $col = null;
  if ($col !== null) return $col;
  try {
    $st = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'id'");
    $col = ($st && $st->fetch()) ? 'id' : 'subject_id';
  } catch (Throwable $e) {
    // Fallback safely to 'id' if SHOW COLUMNS is not permitted
    $col = 'id';
  }
  return $col;
}

function getClasses(PDO $pdo){return $pdo->query("SELECT id,class_name,class_code,grade_level FROM classes WHERE COALESCE(is_active,1)=1 ORDER BY grade_level,class_name")->fetchAll();}
function getSubjects(PDO $pdo){
  $pk = getSubjectPkColumn($pdo);
  // Use `title` as the subject display/name field in the current schema
  $sql = "SELECT {$pk} AS id, title AS subject_name, subject_code FROM subjects WHERE COALESCE(is_active,1)=1 ORDER BY title";
  return $pdo->query($sql)->fetchAll();
}
function getStudents(PDO $pdo){$s=$pdo->prepare("SELECT id,first_name,last_name,username FROM users WHERE role='student' AND COALESCE(is_active,1)=1 ORDER BY first_name,last_name");$s->execute();return $s->fetchAll();}

// Default options
$classes = getClasses($pdo); $subjects = getSubjects($pdo); $students = getStudents($pdo);
// Teacher-scoped options
if (function_exists('isTeacher') && isTeacher()) {
  $teacherId = getCurrentUserId();
  // Classes assigned to teacher
  $stC = $pdo->prepare("SELECT DISTINCT c.id,c.class_name,c.class_code,c.grade_level
                        FROM teacher_assignments ta
                        JOIN classes c ON c.id=ta.class_id
                        WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(c.is_active,1)=1
                        ORDER BY c.grade_level,c.class_name");
  $stC->execute([$teacherId]);
  $classes = $stC->fetchAll();
  // Subjects assigned to teacher
  $subPk = getSubjectPkColumn($pdo);
  $stS = $pdo->prepare("SELECT DISTINCT s.".$subPk." AS id, s.title AS subject_name, s.subject_code
                        FROM teacher_assignments ta
                        JOIN subjects s ON s.".$subPk." = ta.subject_id
                        WHERE ta.teacher_id=? AND COALESCE(ta.is_active,1)=1 AND COALESCE(s.is_active,1)=1
                        ORDER BY s.title");
  $stS->execute([$teacherId]);
  $subjects = $stS->fetchAll();
  // Students enrolled in teacher's classes
  $stU = $pdo->prepare("SELECT DISTINCT u.id,u.first_name,u.last_name,u.username
                        FROM student_enrollments se
                        JOIN teacher_assignments ta ON ta.class_id=se.class_id
                        JOIN users u ON u.id=se.student_id
                        WHERE ta.teacher_id=? AND u.role='student' AND COALESCE(u.is_active,1)=1");
  $stU->execute([$teacherId]);
  $students = $stU->fetchAll();
}

// Filters
$type = $_GET['type'] ?? 'tests'; // tests|assignments|all
$classId = (int)($_GET['class_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);
$q = trim($_GET['q'] ?? '');
$pageNum = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Role-based: students can only see their own
if (isStudentRole()) { $studentId = (int)($_SESSION['user_id'] ?? 0); }

// Build queries
$params = [];
$whereTests = [];
$whereAssign = [];

// Teacher scoping: only results for classes/subjects assigned to this teacher
if (function_exists('isTeacher') && isTeacher()) {
  $teacherId = getCurrentUserId();
  // Add EXISTS guard to both queries (keep FROM intact)
  array_unshift($whereTests, 'EXISTS (SELECT 1 FROM teacher_assignments ta WHERE ta.class_id=t.class_id AND ta.subject_id=t.subject_id AND ta.teacher_id=?)');
  array_unshift($params, $teacherId);
  array_unshift($whereAssign, 'EXISTS (SELECT 1 FROM teacher_assignments ta2 WHERE ta2.class_id=a.class_id AND ta2.subject_id=a.subject_id AND ta2.teacher_id=?)');
  // For assignments EXISTS we also need the teacherId at the correct position when building params for assignments search
  // We'll reuse the same $params order for both queries; the EXISTS placeholder aligns before other filters
}

if ($classId>0) { $whereTests[] = 't.class_id = ?'; $params[]=$classId; $whereAssign[]='a.class_id = ?'; }
if ($subjectId>0) { $whereTests[] = 't.subject_id = ?'; $params[]=$subjectId; $whereAssign[]='a.subject_id = ?'; }
if ($studentId>0) { $whereTests[] = 'tr.student_id = ?'; $params[]=$studentId; $whereAssign[]='asub.student_id = ?'; }
if ($q!=='') { $like = '%'.$q.'%'; $whereTests[] = '(t.title LIKE ? OR s.title LIKE ? OR c.class_name LIKE ? OR CONCAT(u1.first_name, " ", u1.last_name) LIKE ?)'; $params[]=$like; $params[]=$like; $params[]=$like; $params[]=$like; $whereAssign[]='(a.title LIKE ? OR s2.title LIKE ? OR c2.class_name LIKE ? OR CONCAT(u2.first_name, " ", u2.last_name) LIKE ?)'; }

$subjectPk = getSubjectPkColumn($pdo);
$testsSQL = "SELECT 'test' AS rec_type, tr.id AS rec_id, tr.student_id, tr.obtained_marks, tr.total_marks, tr.percentage, tr.grade, tr.submitted_at,
  t.id AS item_id, t.title AS item_title, t.test_type AS item_type, c.class_name, c.class_code, s.title AS subject_name, s.subject_code,
  u1.first_name AS student_first, u1.last_name AS student_last, u1.username AS student_username
  FROM test_results tr
  INNER JOIN tests t ON t.id = tr.test_id
  LEFT JOIN classes c ON c.id=t.class_id
  LEFT JOIN subjects s ON s.".$subjectPk."=t.subject_id
  LEFT JOIN users u1 ON u1.id=tr.student_id";
if ($whereTests) { $testsSQL .= ' WHERE '.implode(' AND ', $whereTests); }

$assignSQL = "SELECT 'assignment' AS rec_type, asub.id AS rec_id, asub.student_id,
  COALESCE(asub.obtained_marks,0) AS obtained_marks, COALESCE(asub.total_marks,0) AS total_marks,
  CASE WHEN COALESCE(asub.total_marks,0)>0 THEN ROUND((COALESCE(asub.obtained_marks,0)/asub.total_marks)*100,2) ELSE 0 END AS percentage,
  asub.grade, asub.submitted_at,
  a.id AS item_id, a.title AS item_title, 'assignment' AS item_type, c2.class_name, c2.class_code, s2.title AS subject_name, s2.subject_code,
  u2.first_name AS student_first, u2.last_name AS student_last, u2.username AS student_username
  FROM assignment_submissions asub
  INNER JOIN assignments a ON a.id = asub.assignment_id
  LEFT JOIN classes c2 ON c2.id=a.class_id
  LEFT JOIN subjects s2 ON s2.".$subjectPk."=a.subject_id
  LEFT JOIN users u2 ON u2.id=asub.student_id";
if ($whereAssign) { $assignSQL .= ' WHERE '.implode(' AND ', $whereAssign); }

// Count and data
$listSQL = '';
$countSQL = '';
$joinedParams = $params;

if ($type==='tests') {
  $countSQL = 'SELECT COUNT(*) FROM ('.$testsSQL.') x';
  $listSQL = $testsSQL.' ORDER BY submitted_at DESC LIMIT '.$perPage.' OFFSET '.(($pageNum-1)*$perPage);
} elseif ($type==='assignments') {
  $countSQL = 'SELECT COUNT(*) FROM ('.$assignSQL.') x';
  $listSQL = $assignSQL.' ORDER BY submitted_at DESC LIMIT '.$perPage.' OFFSET '.(($pageNum-1)*$perPage);
} else { // all
  $countSQL = 'SELECT COUNT(*) FROM ('.$testsSQL.' UNION ALL '.$assignSQL.') x';
  $listSQL = '(' . $testsSQL . ' UNION ALL ' . $assignSQL . ') ORDER BY submitted_at DESC LIMIT '.$perPage.' OFFSET '.(($pageNum-1)*$perPage);
}

// For assignments, params need to include search placeholders if $q set
if ($q!==''){
  // we added 4 placeholders for assignments when q is set but not yet in $params array for assign
  // Duplicate the last 4 (like variables) for assignments search when type is all or assignments
  $likeSet = array_slice($params, -4, 4);
  if ($type==='assignments' || $type==='all') { $joinedParams = array_merge($params, $likeSet); }
}

// Handle export CSV
if (isset($_GET['export']) && $_GET['export']=='1') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=grades_export_'.date('Ymd_His').'.csv');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Type','Title','Student','Class','Subject','Obtained','Total','Percentage','Grade','Submitted At']);
  $stmt = $pdo->prepare($type==='tests' ? $testsSQL.' ORDER BY submitted_at DESC' : ($type==='assignments' ? $assignSQL.' ORDER BY submitted_at DESC' : $testsSQL.' UNION ALL '.$assignSQL.' ORDER BY submitted_at DESC'));
  $stmt->execute($joinedParams);
  while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $studentName = trim(($r['student_first']??'').' '.($r['student_last']??'')); if($studentName==='') $studentName = $r['student_username']??'';
    fputcsv($out, [
      $r['rec_type'], $r['item_title'], $studentName,
      ($r['class_name']??'').(isset($r['class_code'])?(' #'.$r['class_code']):''),
      ($r['subject_name']??'').(isset($r['subject_code'])?(' #'.$r['subject_code']):''),
      (int)$r['obtained_marks'], (int)$r['total_marks'], (float)$r['percentage'], $r['grade'], $r['submitted_at']
    ]);
  }
  fclose($out); exit;
}

// Count
$cnt = $pdo->prepare($countSQL); $cnt->execute($joinedParams); $totalRows = (int)$cnt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows/$perPage)); if ($pageNum>$totalPages) $pageNum=$totalPages;

// Fetch page
$st = $pdo->prepare($listSQL); $st->execute($joinedParams); $rows = $st->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Grades & Results';
include '../components/header.php';
?>
<style>
#gradesGrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
@media(max-width:1024px){#gradesGrid{grid-template-columns:1fr}}
.result-card .teacher-avatar{background:#f3f4f6;color:#111827}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff;color:#374151}
.badge-neutral{background:#f3f4f6;color:#374151}
.badge-success{background:#dcfce7;color:#166534}
.badge-danger{background:#fee2e2;color:#991b1b}
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header"><h1>Grades & Results</h1></div>
    <div class="card-header">
      <form method="GET" class="form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <div class="form-group">
          <label>Type</label>
          <select name="type" onchange="this.form.submit()">
            <option value="tests" <?php echo $type==='tests'?'selected':''; ?>>Tests</option>
            <option value="assignments" <?php echo $type==='assignments'?'selected':''; ?>>Assignments</option>
            <option value="all" <?php echo $type==='all'?'selected':''; ?>>All</option>
          </select>
        </div>
        <div class="form-group"><label>Class</label><select name="class_id" onchange="this.form.submit()"><option value="0">All</option><?php foreach($classes as $c){ $id=(int)$c['id']; $label=$c['class_name'].' (G'.(int)($c['grade_level']??0).') #'.($c['class_code']??''); echo '<option value="'.$id.'" '.($classId===$id?'selected':'').'>'.htmlspecialchars($label).'</option>'; } ?></select></div>
        <div class="form-group"><label>Subject</label><select name="subject_id" onchange="this.form.submit()"><option value="0">All</option><?php foreach($subjects as $s){ $id=(int)$s['id']; $label=($s['subject_name']??'-').' #'.($s['subject_code']??''); echo '<option value="'.$id.'" '.($subjectId===$id?'selected':'').'>'.htmlspecialchars($label).'</option>'; } ?></select></div>
        <?php if(!isStudentRole()): ?>
        <div class="form-group"><label>Student</label><select name="student_id" onchange="this.form.submit()"><option value="0">All</option><?php foreach($students as $stu){ $id=(int)$stu['id']; $name=trim(($stu['first_name']??'').' '.($stu['last_name']??'')) ?: ($stu['username']??''); echo '<option value="'.$id.'" '.($studentId===$id?'selected':'').'>'.htmlspecialchars($name).'</option>'; } ?></select></div>
        <?php endif; ?>
        <div class="search-input-wrapper" style="max-width:320px;width:100%">
          <i class="fas fa-search"></i>
          <input class="table-search-input" type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search by title, subject, class, student">
        </div>
        <button class="btn btn-secondary" type="submit">Filter</button>
        <a class="btn btn-primary" href="?<?php echo http_build_query(array_merge($_GET,['export'=>'1','page'=>null])); ?>"><i class="fas fa-download"></i> Export CSV</a>
      </form>
    </div>

    <div class="card-content">
      <div id="gradesGrid" class="cards-grid">
        <?php if($rows): foreach($rows as $r): $studentName=trim(($r['student_first']??'').' '.($r['student_last']??'')); if($studentName==='') $studentName=$r['student_username']??''; $pct=(float)($r['percentage']??0); $grade=$r['grade']??''; $badgeCls=$pct>=70?'badge-success':($pct<40?'badge-danger':'badge-neutral'); ?>
          <div class="teacher-card result-card">
            <div class="teacher-avatar"><i class="fas <?php echo $r['rec_type']==='test'?'fa-question-circle':'fa-tasks'; ?>"></i></div>
            <div class="teacher-name"><strong><?php echo htmlspecialchars($r['item_title']); ?></strong></div>
            <div class="virtual-badges">
              <span class="badge <?php echo $badgeCls; ?>">Score: <?php echo (int)$r['obtained_marks']; ?>/<?php echo (int)$r['total_marks']; ?> (<?php echo number_format($pct,2); ?>%)</span>
              <?php if($grade!==''): ?><span class="badge">Grade: <?php echo htmlspecialchars($grade); ?></span><?php endif; ?>
              <span class="badge">Type: <?php echo htmlspecialchars($r['rec_type']); ?></span>
            </div>
            <div class="teacher-username">Student: <?php echo htmlspecialchars($studentName); ?></div>
            <div class="virtual-meta">Class: <?php echo htmlspecialchars(($r['class_name']??'-').' #'.($r['class_code']??'')); ?> • Subject: <?php echo htmlspecialchars(($r['subject_name']??'-').' #'.($r['subject_code']??'')); ?></div>
            <div class="virtual-meta">Submitted: <?php echo htmlspecialchars($r['submitted_at']); ?></div>
            <div class="teacher-card-actions action-buttons centered">
              <?php if($r['rec_type']==='test'): ?>
                <a class="btn btn-sm btn-secondary" href="./test_detail.php?id=<?php echo (int)$r['item_id']; ?>" title="View Test"><i class="fas fa-eye"></i></a>
              <?php else: ?>
                <a class="btn btn-sm btn-secondary" href="./assignment_detail.php?id=<?php echo (int)$r['item_id']; ?>" title="View Assignment"><i class="fas fa-eye"></i></a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; else: ?>
          <div class="no-data"><i class="fas fa-chart-line"></i><p>No results found.</p></div>
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
<?php include '../components/footer.php'; ?>
