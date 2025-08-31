<?php
// api/timetable_list.php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();

    $classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'class'; // class | exam
    $academicYear = isset($_GET['academic_year']) ? trim($_GET['academic_year']) : '';

    if ($classId <= 0) {
        throw new Exception('class_id is required');
    }

    if ($type === 'class') {
        $params = [':class_id' => $classId];
        $sql = "SELECT t.id, t.class_id, t.subject_id, t.teacher_id, t.day_of_week, t.start_time, t.end_time, t.room_number, t.academic_year,
                       s.subject_name, s.subject_code,
                       CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
                FROM timetable t
                JOIN subjects s ON s.id = t.subject_id
                JOIN users u ON u.id = t.teacher_id
                WHERE t.class_id = :class_id AND t.is_active = 1";
        if ($academicYear !== '') {
            $sql .= " AND t.academic_year = :ay";
            $params[':ay'] = $academicYear;
        }
        $sql .= " ORDER BY FIELD(t.day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday'), t.start_time";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        echo json_encode(['success' => true, 'type' => 'class', 'items' => $items]);
        exit;
    } else {
        // exam timetable uses tests table; optional filter by date range
        $params = [':class_id' => $classId];
        $sql = "SELECT te.id, te.class_id, te.subject_id, te.teacher_id, te.title, te.test_type,
                       te.scheduled_date AS date, te.start_time, te.end_time,
                       s.subject_name, s.subject_code,
                       CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
                FROM tests te
                JOIN subjects s ON s.id = te.subject_id
                JOIN users u ON u.id = te.teacher_id
                WHERE te.class_id = :class_id AND te.is_active = 1";
        // Optional: date range filters
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $sql .= " AND te.scheduled_date BETWEEN :sd AND :ed";
            $params[':sd'] = $_GET['start_date'];
            $params[':ed'] = $_GET['end_date'];
        }
        // treat test types that are exam-like
        if (!empty($_GET['exam_types'])) {
            // comma-separated list validated to allowed values
            $allowed = ['weekly','monthly','quarterly','final','quiz'];
            $types = array_values(array_filter(array_map('trim', explode(',', $_GET['exam_types'])), function($t) use ($allowed){ return in_array($t, $allowed, true); }));
            if ($types) {
                $in = implode(",", array_fill(0, count($types), '?'));
                $sql .= " AND te.test_type IN ($in)";
                $params = array_merge($params, $types);
            }
        }
        $sql .= " ORDER BY te.scheduled_date ASC, te.start_time ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        echo json_encode(['success' => true, 'type' => 'exam', 'items' => $items]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
