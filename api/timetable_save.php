<?php
// api/timetable_save.php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Invalid method'); }

    $pdo = getDBConnection();

    $type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : 'class'; // class | exam
    $classId = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $academicYear = trim($_POST['academic_year'] ?? '');

    if ($classId <= 0 || $subjectId <= 0 || $teacherId <= 0) { throw new Exception('class_id, subject_id and teacher_id are required'); }

    if ($type === 'class') {
        $day = strtolower(trim($_POST['day_of_week'] ?? ''));
        $start = trim($_POST['start_time'] ?? '');
        $end = trim($_POST['end_time'] ?? '');
        $room = trim($_POST['room_number'] ?? '');
        if (!in_array($day, ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'], true)) {
            throw new Exception('Invalid day_of_week');
        }
        if ($start === '' || $end === '') { throw new Exception('start_time and end_time are required'); }
        if ($academicYear === '') { throw new Exception('academic_year is required'); }
        if ($end <= $start) { throw new Exception('end_time must be after start_time'); }

        // Detect conflicts: same class and day with overlapping time ranges
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE class_id = ? AND day_of_week = ? AND is_active = 1 AND NOT (end_time <= ? OR start_time >= ?)");
        $stmt->execute([$classId, $day, $start, $end]);
        $conflicts = (int)$stmt->fetchColumn();
        if ($conflicts > 0) { throw new Exception('Time conflict with existing timetable entry'); }

        $stmt = $pdo->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room_number, academic_year, is_active) VALUES (?,?,?,?,?,?,?,?,1)");
        $stmt->execute([$classId, $subjectId, $teacherId, $day, $start, $end, ($room !== '' ? $room : null), $academicYear]);
        echo json_encode(['success' => true, 'type' => 'class', 'id' => $pdo->lastInsertId()]);
        exit;
    } else {
        // Exam timetable -> create a test row
        $title = trim($_POST['title'] ?? 'Exam');
        $date = trim($_POST['date'] ?? '');
        $start = trim($_POST['start_time'] ?? '');
        $end = trim($_POST['end_time'] ?? '');
        $testType = trim($_POST['test_type'] ?? 'final');
        if ($date === '' || $start === '' || $end === '') { throw new Exception('date, start_time and end_time are required'); }
        if ($end <= $start) { throw new Exception('end_time must be after start_time'); }

        // Optional conflict: same class/date overlapping
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE class_id = ? AND scheduled_date = ? AND is_active = 1 AND NOT (end_time <= ? OR start_time >= ?)");
        $stmt->execute([$classId, $date, $start, $end]);
        $conflicts = (int)$stmt->fetchColumn();
        if ($conflicts > 0) { throw new Exception('Exam time conflicts with existing schedule'); }

        $stmt = $pdo->prepare("INSERT INTO tests (title, description, class_id, subject_id, teacher_id, test_type, total_marks, duration_minutes, scheduled_date, start_time, end_time, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,1)");
        $duration = (int)round((strtotime($end) - strtotime($start))/60);
        $stmt->execute([$title, null, $classId, $subjectId, $teacherId, $testType, 100, max(30, $duration), $date, $start, $end]);
        echo json_encode(['success' => true, 'type' => 'exam', 'id' => $pdo->lastInsertId()]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
