<?php
require_once '../config/config.php';

// Check if user is logged in and is SuperAdmin
if (!isLoggedIn() || !isSuperAdmin()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    $pdo = getDBConnection();

    $teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
    if ($teacher_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Teacher ID is required']);
        exit;
    }

    // Helper to execute a query safely (for optional legacy tables)
    $safeQuery = function(string $sql, array $params = []) use ($pdo) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    };

    // Classes taught by teacher (from canonical table)
    $classes_main = $safeQuery(
        "
        SELECT 
            c.id,
            c.class_name,
            c.grade_level,
            COUNT(DISTINCT se.student_id) AS student_count
        FROM teacher_assignments ta
        INNER JOIN classes c ON c.id = ta.class_id AND c.is_active = 1
        LEFT JOIN student_enrollments se ON se.class_id = c.id AND se.status = 'active'
        WHERE ta.teacher_id = ? AND ta.is_active = 1
        GROUP BY c.id, c.class_name, c.grade_level
        ORDER BY c.grade_level, c.class_name
        ",
        [$teacher_id]
    );

    // Classes taught by teacher (from legacy teacher_class_assignments)
    $classes_legacy = $safeQuery(
        "
        SELECT 
            c.id,
            c.class_name,
            c.grade_level,
            COUNT(DISTINCT se.student_id) AS student_count
        FROM teacher_class_assignments tca
        INNER JOIN classes c ON c.id = tca.class_id AND c.is_active = 1
        LEFT JOIN student_enrollments se ON se.class_id = c.id AND se.status = 'active'
        WHERE tca.teacher_id = ?
        GROUP BY c.id, c.class_name, c.grade_level
        ORDER BY c.grade_level, c.class_name
        ",
        [$teacher_id]
    );

    // Merge and dedupe classes by id
    $classes_map = [];
    foreach (array_merge($classes_main, $classes_legacy) as $row) {
        $classes_map[$row['id']] = $row;
    }
    $classes = array_values($classes_map);

    // Subjects taught by teacher (from canonical table)
    $subjects_main = $safeQuery(
        "
        SELECT DISTINCT
            s.id,
            s.subject_name,
            s.subject_code,
            s.description
        FROM teacher_assignments ta
        INNER JOIN subjects s ON s.id = ta.subject_id AND s.is_active = 1
        WHERE ta.teacher_id = ? AND ta.is_active = 1
        ORDER BY s.subject_name
        ",
        [$teacher_id]
    );

    // Subjects taught by teacher (from legacy teacher_subject_assignments)
    $subjects_legacy = $safeQuery(
        "
        SELECT DISTINCT
            s.id,
            s.subject_name,
            s.subject_code,
            s.description
        FROM teacher_subject_assignments tsa
        INNER JOIN subjects s ON s.id = tsa.subject_id AND s.is_active = 1
        WHERE tsa.teacher_id = ?
        ORDER BY s.subject_name
        ",
        [$teacher_id]
    );

    // Merge and dedupe subjects by id
    $subjects_map = [];
    foreach (array_merge($subjects_main, $subjects_legacy) as $row) {
        $subjects_map[$row['id']] = $row;
    }
    $subjects = array_values($subjects_map);

    echo json_encode([
        'success' => true,
        'classes' => $classes,
        'subjects' => $subjects,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>