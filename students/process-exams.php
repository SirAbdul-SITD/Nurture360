<?php
require_once('settings.php'); // Include settings for database connection, exams_type and student_id

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        header('Content-Type: application/json');

        // Get the submitted data
        $subject_id = intval($_POST['subject_id']);
        $class_id = intval($_POST['class_id']);
        $total_questions = intval($_POST['total_questions']);
        $questions = $_POST['questions'];
        $student_id = $_SESSION['student_id']; // Assuming student_id is stored in session
         $exams_type = 'Third Term'; // ssuming you pass this too from your frontend

        if (!is_array($questions)) {
            throw new Exception("Invalid question data format.");
        }

        $correctAnswers = 0;
        $autoGradable = 0;
        foreach ($questions as $question_id => $data) {
            $correct = isset($data['correct_answer']) ? trim((string)$data['correct_answer']) : '';
            $selected = isset($data['selected_option']) ? trim((string)$data['selected_option']) : '';
            $textAns = isset($data['text_answer']) ? trim((string)$data['text_answer']) : '';
            $qtype = isset($data['qtype']) ? (int)$data['qtype'] : 0;

            // Treat as MCQ if a correct option index is provided (e.g., 1..5)
            if ($correct !== '') {
                $autoGradable++;
                if ($correct === $selected) {
                    $correctAnswers++;
                }
            }
        }

        // Compute percentage over auto-gradable only; if none, set 0
        $percentage = $autoGradable > 0 ? ($correctAnswers / $autoGradable) * 100 : 0;
        
        // Determine exam type and grading method
        $examType = 'auto'; // Default to auto-grading
        $examStatus = 'graded'; // Default to graded
        
        // Check if this should be manual grading (if no auto-gradable questions)
        if ($autoGradable === 0) {
            $examType = 'manual';
            $examStatus = 'pending';
        }

        // Insert into exam_assessments
        $stmt = $pdo->prepare("INSERT INTO exam_assessments (student_id, subject_id, class_id, type, status, timespan) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$student_id, $subject_id, $class_id, $examType, $examStatus]);

        $assessment_id = $pdo->lastInsertId();

        // Calculate total marks and obtained marks
        $totalMarks = count($questions); // Each question = 1 mark for simplicity
        $obtainedMarks = $correctAnswers;
        
        // Calculate grade based on percentage
        $grade = '';
        if ($percentage >= 70) $grade = 'A';
        elseif ($percentage >= 60) $grade = 'B';
        elseif ($percentage >= 50) $grade = 'C';
        elseif ($percentage >= 45) $grade = 'D';
        else $grade = 'F';

        // Insert into exam_results table for history and admin dashboard
        try {
            $resultStmt = $pdo->prepare("INSERT INTO exam_results (exam_assessment_id, student_id, subject_id, class_id, obtained_marks, total_marks, percentage, grade, grading_type, status, submitted_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $resultStmt->execute([
                $assessment_id,
                $student_id,
                $subject_id,
                $class_id,
                $obtainedMarks,
                $totalMarks,
                $percentage,
                $grade,
                $examType,
                $examStatus
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the exam submission
            error_log("Failed to insert into exam_results: " . $e->getMessage());
        }

        // Insert each question response
        $stmt = $pdo->prepare("INSERT INTO exam_assessments_data (exam_assessment_id, student_id, question_id, question, correct_answer, student_answer) 
                               VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($questions as $question_id => $data) {
            $questionText = isset($data['question']) ? (string)$data['question'] : '';
            $correct = isset($data['correct_answer']) ? (string)$data['correct_answer'] : '';
            $selected = isset($data['selected_option']) ? (string)$data['selected_option'] : '';
            $textAns = isset($data['text_answer']) ? (string)$data['text_answer'] : '';

            $studentAnswer = ($correct !== '') ? $selected : $textAns; // MCQ stores option index; Essay stores free text

            $stmt->execute([
                $assessment_id,
                $student_id,
                $question_id,
                $questionText,
                $correct,
                $studentAnswer
            ]);
        }

        echo json_encode([
            'assessment_id' => $assessment_id,
            'success' => true,
            'percentage' => $percentage
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
