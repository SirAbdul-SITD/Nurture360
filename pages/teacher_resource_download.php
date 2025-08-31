<?php
require_once '../config/config.php';
if (!isLoggedIn() || !isTeacher()) { redirect('../auth/login.php'); }
$pdo = getDBConnection();
$teacherId = getCurrentUserId();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { redirect('./teacher_resources.php'); }

// Ensure this resource belongs to the teacher and has a file
$stmt = $pdo->prepare("SELECT id, file_path FROM learning_resources WHERE id = ? AND uploaded_by = ?");
$stmt->execute([$id, $teacherId]);
$row = $stmt->fetch();
if (!$row || empty($row['file_path'])) { redirect('./teacher_resources.php'); }

// Increment download count (best-effort)
try {
  $pdo->prepare("UPDATE learning_resources SET download_count = COALESCE(download_count,0) + 1 WHERE id = ? AND uploaded_by = ?")->execute([$id,$teacherId]);
} catch (Throwable $e) {}

header('Location: ' . $row['file_path']);
exit;
