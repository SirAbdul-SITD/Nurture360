<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isSuperAdmin()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { redirect('./resources.php'); }

$stmt = $pdo->prepare("SELECT id, file_path FROM learning_resources WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row || empty($row['file_path'])) { redirect('./resources.php'); }

// Increment download count (best-effort)
try {
  $pdo->prepare("UPDATE learning_resources SET download_count = COALESCE(download_count,0) + 1 WHERE id = ?")->execute([$id]);
} catch (Throwable $e) {}

// Redirect to actual file
$target = $row['file_path'];
header('Location: ' . $target);
exit;
