<?php
require_once '../config/config.php';
if (!isLoggedIn()) { redirect('../auth/login.php'); }
$pdo=getDBConnection();
$id=(int)($_GET['id']??0); if($id<=0){ redirect('./content_pages.php'); }

$page=null; $error='';
try{
  $st=$pdo->prepare("SELECT cp.*, c.class_name,c.class_code, s.subject_name,s.subject_code, u.first_name,u.last_name,u.username
                     FROM content_pages cp
                     LEFT JOIN classes c ON c.id=cp.class_id
                     LEFT JOIN subjects s ON s.id=cp.subject_id
                     LEFT JOIN users u ON u.id=cp.teacher_id
                     WHERE cp.id=?");
  $st->execute([$id]);
  $page=$st->fetch();
  if(!$page){ redirect('./content_pages.php'); }
  if(!$page['is_published'] && !isSuperAdmin() && $_SESSION['role']!=='teacher'){
    throw new Exception('This page is not published.');
  }
}catch(Throwable $e){ $error=$e->getMessage(); }

$page_title='Content Page';
include '../components/header.php';
?>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars($page['title']); ?></h1>
        <div class="muted">
          Class: <?php echo htmlspecialchars(($page['class_name']??'-').' #'.($page['class_code']??'-')); ?> · Subject: <?php echo htmlspecialchars(($page['subject_name']??'-').' #'.($page['subject_code']??'-')); ?>
        </div>
      </div>
      <div class="header-actions">
        <a class="btn btn-primary" href="./content_pages.php"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>
    <div class="content-card">
      <div class="content-card-body">
        <div class="rich-content" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px">
          <?php echo $page['content_html']; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include '../components/footer.php'; ?>
