<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();

$resourceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($resourceId <= 0) { redirect('./resources.php'); }

// Fetch resource with relations
$stmt = $pdo->prepare(
  "SELECT lr.*, 
          c.class_name, c.class_code, c.grade_level, c.academic_year,
          s.subject_name, s.subject_code,
          u.first_name AS u_first, u.last_name AS u_last, u.username AS u_user
     FROM learning_resources lr
     LEFT JOIN classes c ON c.id = lr.class_id
     LEFT JOIN subjects s ON s.id = lr.subject_id
     LEFT JOIN users u ON u.id = lr.uploaded_by
    WHERE lr.id = ?"
);
$stmt->execute([$resourceId]);
$resource = $stmt->fetch();
if (!$resource) { redirect('./resources.php'); }

// Helper: format bytes
function fmtBytes($bytes){ if(!$bytes) return '-'; $s=['B','KB','MB','GB']; $e=floor(log($bytes,1024)); $e=$e<0?0:($e>3?3:$e); return number_format($bytes/pow(1024,$e),($e>0?2:0)).' '.$s[$e]; }

// Compute display values
$uploader = trim(($resource['u_first'] ?? '') . ' ' . ($resource['u_last'] ?? ''));
if ($uploader === '') { $uploader = (string)($resource['u_user'] ?? ''); }
$classLabel = ($resource['class_name'] ?? '-') . ' (' . ($resource['class_code'] ?? '-') . ')';
$subjectLabel = ($resource['subject_name'] ?? '-') . (!empty($resource['subject_code']) ? ' (' . $resource['subject_code'] . ')' : '');
$isPublic = (int)($resource['is_public'] ?? 0) === 1;
$type = (string)($resource['resource_type'] ?? 'other');
$file = (string)($resource['file_path'] ?? '');
$fileType = (string)($resource['file_type'] ?? '');
$fileSize = $resource['file_size'] ?? null;
$url = (string)($resource['url'] ?? '');
$created = (string)($resource['created_at'] ?? '');

// Detect preview type
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$isImg = $type === 'image' || in_array($ext, ['jpg','jpeg','png','gif','webp']);
$isVideo = $type === 'video' || in_array($ext, ['mp4','webm','mov','mkv']);
$isAudio = in_array($ext, ['mp3','wav','m4a','aac','ogg']);
$isPdf = ($ext === 'pdf');

function youtubeEmbedId($url){
  if (!$url) return null;
  $host = parse_url($url, PHP_URL_HOST);
  if (!$host) return null;
  if (strpos($host,'youtu.be') !== false) {
    $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
    return $path ?: null;
  }
  if (strpos($host,'youtube.com') !== false) {
    parse_str((string)parse_url($url, PHP_URL_QUERY), $q);
    return $q['v'] ?? null;
  }
  return null;
}
$ytId = youtubeEmbedId($url);

$page_title = 'Resource — ' . ($resource['title'] ?? '');

// Build absolute URL for file when needed (e.g., Google Docs Viewer)
function absoluteFileUrl($relPath){
  if (!$relPath) return null;
  if (preg_match('/^https?:\/\//i', $relPath)) return $relPath;
  $fs = realpath(__DIR__ . '/' . $relPath);
  if (!$fs) return $relPath; // fallback
  $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
  if ($docRoot && strpos($fs, $docRoot) === 0) {
    $urlPath = str_replace(DIRECTORY_SEPARATOR, '/', substr($fs, strlen($docRoot)));
  } else {
    // Fallback to relative URL from current script
    $urlPath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/' . ltrim($relPath, '/');
  }
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme . '://' . $host . $urlPath;
}

// Flags for Office docs (use Google Docs Viewer)
$isOffice = in_array(strtolower($fileType), ['doc','docx','xls','xlsx','ppt','pptx']);
$_absFileUrl = $file ? absoluteFileUrl($file) : '';

include '../components/header.php';
?>
<style>
.preview-box { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px; }
.preview-box iframe, .preview-box video, .preview-box img, .preview-box embed, .preview-box audio { width:100%; max-height:520px; border-radius:8px; }
.detail-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
@media(max-width: 900px){ .detail-grid { grid-template-columns: 1fr; } }
.detail-item { padding:10px 12px; background:#f9fafb; border:1px solid #edf2f7; border-radius:8px; }
.detail-item .label { font-size:12px; color:#6b7280; }
.detail-item .value { font-size:14px; color:#111827; margin-top:4px; }
.badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; background:#eef2ff; color:#374151; margin-right:6px; }
.badge-type{ background:#d1fae5;color:#065f46 }
.badge-public{ background:#e0e7ff;color:#3730a3 }
.badge-private{ background:#fee2e2;color:#991b1b }
</style>
<div class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><?php echo htmlspecialchars($resource['title']); ?></h1>
        <div class="muted">
          Class: <?php echo htmlspecialchars($classLabel); ?> ·
          Subject: <?php echo htmlspecialchars($subjectLabel); ?> ·
          Uploader: <?php echo htmlspecialchars($uploader ?: '-'); ?> ·
          Created: <?php echo htmlspecialchars($created ?: '-'); ?>
        </div>
      </div>
      <div class="header-actions">
        <a class="btn btn-primary" href="./resources.php"><i class="fas fa-arrow-left"></i> Back to Resources</a>
        <?php if ($url): ?>
          <a class="btn btn-secondary" href="./resource_open.php?id=<?php echo (int)$resourceId; ?>" target="_blank"><i class="fas fa-link"></i> Open Link</a>
        <?php endif; ?>
        <?php if ($file): ?>
          <a class="btn btn-secondary" href="./resource_download.php?id=<?php echo (int)$resourceId; ?>" target="_blank"><i class="fas fa-download"></i> Download</a>
        <?php endif; ?>
        <!-- <button class="btn btn-error" type="button" onclick="openDeleteResourceModal(<?php echo (int)$resourceId; ?>)"><i class="fas fa-trash"></i> Delete</button> -->
      </div>
    </div>

    <!-- Overview -->
    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> Overview</h3>
      </div>
      <div class="card-content">
        <div class="detail-grid">
          <div class="detail-item"><div class="label">Type</div><div class="value"><span class="badge badge-type"><?php echo htmlspecialchars(ucfirst($type)); ?></span></div></div>
          <div class="detail-item"><div class="label">Visibility</div><div class="value"><span class="badge <?php echo $isPublic ? 'badge-public' : 'badge-private'; ?>"><?php echo $isPublic ? 'Public' : 'Private'; ?></span></div></div>
          <div class="detail-item"><div class="label">File Type</div><div class="value"><?php echo htmlspecialchars($fileType ?: '-'); ?></div></div>
          <div class="detail-item"><div class="label">File Size</div><div class="value"><?php echo htmlspecialchars(fmtBytes($fileSize)); ?></div></div>
          <div class="detail-item"><div class="label">Download Count</div><div class="value"><?php echo (int)($resource['download_count'] ?? 0); ?></div></div>
          <div class="detail-item"><div class="label">Class</div><div class="value"><?php echo htmlspecialchars($classLabel); ?></div></div>
          <div class="detail-item"><div class="label">Subject</div><div class="value"><?php echo htmlspecialchars($subjectLabel); ?></div></div>
          <div class="detail-item"><div class="label">URL</div><div class="value"><?php echo $url ? '<a href="'.htmlspecialchars($url).'" target="_blank">'.htmlspecialchars($url).'</a>' : '-'; ?></div></div>
          <div class="detail-item"><div class="label">Uploaded By</div><div class="value"><?php echo htmlspecialchars($uploader ?: '-'); ?></div></div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-align-left"></i> Description</h3>
      </div>
      <div class="card-content">
        <?php if (!empty($resource['description'])): ?>
          <div style="white-space:pre-wrap; line-height:1.6; color:#1f2937;">
            <?php echo htmlspecialchars($resource['description']); ?>
          </div>
        <?php else: ?>
          <div class="empty-state">No description provided</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Preview -->
    <div class="content-card">
      <div class="card-header">
        <h3><i class="fas fa-eye"></i> Preview</h3>
      </div>
      <div class="card-content">
        <div class="preview-box">
          <?php if ($ytId): ?>
            <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($ytId); ?>" frameborder="0" allowfullscreen style="aspect-ratio:16/9; height: 100vh;"></iframe>
          <?php elseif ($isImg && $file): ?>
            <img src="<?php echo htmlspecialchars($file); ?>" alt="Image preview" style="max-height:85vh; object-fit:contain;" />
          <?php elseif ($isVideo && $file): ?>
            <video controls src="<?php echo htmlspecialchars($file); ?>" style="height:80vh"></video>
          <?php elseif ($isAudio && $file): ?>
            <audio controls src="<?php echo htmlspecialchars($file); ?>" style="width:100%"></audio>
          <?php elseif ($isPdf && $file): ?>
            <iframe src="<?php echo htmlspecialchars($file); ?>#view=FitH" style="width:100%; height:85vh; border:0;"></iframe>
          <?php elseif ($isOffice && $file && $_absFileUrl): ?>
            <iframe src="https://docs.google.com/gview?embedded=1&url=<?php echo urlencode($_absFileUrl); ?>" style="width:100%; height:85vh; border:0;"></iframe>
          <?php elseif ($url): ?>
            <div class="empty-state">External link: <a href="<?php echo htmlspecialchars($url); ?>" target="_blank"><?php echo htmlspecialchars($url); ?></a></div>
          <?php elseif ($file): ?>
            <div class="empty-state">No inline preview for this file type. Use Download.</div>
          <?php else: ?>
            <div class="empty-state">No file or link attached.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include '../components/modal.php'; ?>
<?php
// Delete confirm modal (posts to resources.php)
$delOnConfirm = 'confirmDeleteResourceDetail';
renderConfirmModal('deleteResourceDetailModal','Delete Resource','Are you sure you want to delete this resource?','Delete','Cancel',[ 'type' => 'warning', 'onConfirm' => $delOnConfirm ]);
?>
<script>
  var currentResourceIdDetail = <?php echo (int)$resourceId; ?>;
  function openDeleteResourceModal(id){ currentResourceIdDetail = id; if (typeof window.openModalDeleteResourceDetailModal === 'function') { window.openModalDeleteResourceDetailModal(); } else { var m=document.getElementById('deleteResourceDetailModal'); if(m){ m.classList.add('show','active'); document.body.classList.add('modal-open'); } } }
  function confirmDeleteResourceDetail(){ var form=document.createElement('form'); form.method='POST'; form.action='./resources.php'; var t1=document.createElement('input'); t1.type='hidden'; t1.name='csrf_token'; t1.value='<?php echo generateCSRFToken(); ?>'; form.appendChild(t1); var t2=document.createElement('input'); t2.type='hidden'; t2.name='action'; t2.value='delete'; form.appendChild(t2); var t3=document.createElement('input'); t3.type='hidden'; t3.name='id'; t3.value=String(currentResourceIdDetail); form.appendChild(t3); document.body.appendChild(form); form.submit(); }
</script>
<?php include '../components/footer.php'; ?>
