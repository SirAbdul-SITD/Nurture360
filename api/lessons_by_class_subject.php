<?php
// Return lessons by class and subject for use in tests modal
require_once '../config/config.php';
header('Content-Type: application/json');
try {
  if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
  $pdo = getDBConnection();
  $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
  $subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
  if ($class_id<=0 || $subject_id<=0) { http_response_code(400); echo json_encode([]); exit; }
  $st = $pdo->prepare("SELECT lesson_id, lesson_number, title FROM lessons WHERE class_id=? AND subject_id=? ORDER BY (lesson_number IS NULL), lesson_number, lesson_id DESC");
  $st->execute([$class_id,$subject_id]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows ?: []);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Failed','message'=>$e->getMessage()]);
}
