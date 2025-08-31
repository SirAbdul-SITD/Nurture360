<?php
require_once('settings.php'); // Include settings for database connection and student_id

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the submitted data
        $lesson_id = intval($_POST['lesson_id'] ?? 0);
        $type_id = intval($_POST['type_id'] ?? 0);
        $total_questions = intval($_POST['total_questions'] ?? 0);
        $questions = isset($_POST['questions']) && is_array($_POST['questions']) ? $_POST['questions'] : [];
        $student_id = intval($_SESSION['student_id'] ?? 0);
        $test_id = intval($_SESSION['test_id'] ?? 0);

        if ($student_id <= 0) { throw new Exception('Not authenticated.'); }
        if ($total_questions <= 0 || empty($questions)) { throw new Exception('No questions submitted.'); }

        // Calculate score
        // Weighted marking: sum marks for all questions and marks for correct ones
        $obtained = 0.0;
        $total    = 0.0;
        $questionIds = array_map('intval', array_keys($questions));
        if (!empty($questionIds) && $test_id > 0) {
            // Build IN clause safely
            $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
            $sqlQ = "SELECT id, correct_answer, COALESCE(marks, 1) AS marks FROM test_questions WHERE test_id = ? AND id IN ($placeholders)";
            $params = array_merge([$test_id], $questionIds);
            $stmtQ = $pdo->prepare($sqlQ);
            $stmtQ->execute($params);
            $map = [];
            while ($row = $stmtQ->fetch(PDO::FETCH_ASSOC)) {
                $map[(int)$row['id']] = [
                    'correct' => (string)$row['correct_answer'],
                    'marks'   => (float)$row['marks']
                ];
            }
            foreach ($questions as $qid => $data) {
                $qid = (int)$qid;
                $studentAns = isset($data['selected_option']) ? (string)$data['selected_option'] : '';
                $info = $map[$qid] ?? null;
                if ($info) {
                    $qMarks = (float)$info['marks'];
                    $total += $qMarks;
                    $correctAns = (string)$info['correct'];
                    if ($studentAns !== '' && strtolower(trim($studentAns)) == strtolower(trim($correctAns))) {
                        $obtained += $qMarks;
                    }
                } else {
                    // Fallback: count as 1 mark if schema missing
                    $total += 1.0;
                }
            }
        }
        $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : 0.0;

        if ($test_id > 0) {
            // Store into test_results
            $stmt = $pdo->prepare("INSERT INTO test_results (test_id, student_id, obtained_marks, total_marks, percentage) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$test_id, $student_id, $obtained, $total, $percentage]);
            $result_id = $pdo->lastInsertId();

            // Ensure per-question table exists, then store answers
            $pdo->exec("CREATE TABLE IF NOT EXISTS test_answers (
                id INT PRIMARY KEY AUTO_INCREMENT,
                test_result_id INT NOT NULL,
                test_id INT NOT NULL,
                student_id INT NOT NULL,
                question_id INT NOT NULL,
                question_type VARCHAR(32) NULL,
                selected_answer TEXT,
                correct_answer TEXT,
                is_correct TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (test_result_id),
                FOREIGN KEY (test_result_id) REFERENCES test_results(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // If table exists but some columns are missing (from older schema), add them
            $colsStmt = $pdo->query("SHOW COLUMNS FROM test_answers");
            $existingCols = array_map(function($r){ return $r['Field']; }, $colsStmt->fetchAll(PDO::FETCH_ASSOC));
            $alterSqls = [];
            if (!in_array('test_result_id', $existingCols)) {
                $alterSqls[] = "ADD COLUMN test_result_id INT NOT NULL AFTER id";
                $alterSqls[] = "ADD INDEX idx_tri (test_result_id)";
            }
            if (!in_array('test_id', $existingCols)) {
                $alterSqls[] = "ADD COLUMN test_id INT NOT NULL AFTER test_result_id";
            }
            if (!in_array('student_id', $existingCols)) {
                $alterSqls[] = "ADD COLUMN student_id INT NOT NULL AFTER test_id";
            }
            if (!in_array('question_id', $existingCols)) {
                $alterSqls[] = "ADD COLUMN question_id INT NOT NULL AFTER student_id";
            }
            if (!in_array('question_type', $existingCols)) {
                $alterSqls[] = "ADD COLUMN question_type VARCHAR(32) NULL AFTER question_id";
            }
            if (!in_array('selected_answer', $existingCols)) {
                $alterSqls[] = "ADD COLUMN selected_answer TEXT AFTER question_type";
            }
            if (!in_array('correct_answer', $existingCols)) {
                $alterSqls[] = "ADD COLUMN correct_answer TEXT AFTER selected_answer";
            }
            if (!in_array('is_correct', $existingCols)) {
                $alterSqls[] = "ADD COLUMN is_correct TINYINT(1) NOT NULL DEFAULT 0 AFTER correct_answer";
            }
            if (!in_array('created_at', $existingCols)) {
                $alterSqls[] = "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_correct";
            }
            if (!empty($alterSqls)) {
                $pdo->exec("ALTER TABLE test_answers " . implode(", ", $alterSqls));
            }

            $ins = $pdo->prepare("INSERT INTO test_answers (test_result_id, test_id, student_id, question_id, question_type, selected_answer, correct_answer, is_correct)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($questions as $qid => $data) {
                $studentAns = isset($data['selected_option']) ? (string)$data['selected_option'] : '';
                $correctAns = isset($data['correct_answer']) ? (string)$data['correct_answer'] : '';
                $qType = isset($data['question_type']) ? (string)$data['question_type'] : null; // may be absent for legacy
                $isCorrect = ($studentAns !== '' && strtolower(trim($studentAns)) == strtolower(trim($correctAns))) ? 1 : 0;
                $ins->execute([$result_id, $test_id, $student_id, intval($qid), $qType, $studentAns, $correctAns, $isCorrect]);
            }

            echo json_encode([
                // Keep key name for frontend compatibility
                'assessment_id' => $result_id,
                'success' => true,
                'percentage' => $percentage
            ]);
            exit;
        }

        // Legacy fallback is not available (assessments table missing)
        throw new Exception('Test context missing (test_id not set).');
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>