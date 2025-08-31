<?php
require_once 'config/config.php';

echo "<h1>Exam Database Test</h1>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if exam_results table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'exam_results'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p style='color: green;'>✓ exam_results table exists</p>";
        
        // Check table structure
        $structure = $pdo->query("DESCRIBE exam_results");
        echo "<h3>exam_results table structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if there's any data
        $count = $pdo->query("SELECT COUNT(*) FROM exam_results")->fetchColumn();
        echo "<p>Total exam results: <strong>$count</strong></p>";
        
        if ($count > 0) {
            echo "<h3>Recent exam results:</h3>";
            $recent = $pdo->query("SELECT * FROM exam_results ORDER BY id DESC LIMIT 5");
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Assessment ID</th><th>Student ID</th><th>Subject ID</th><th>Class ID</th><th>Obtained</th><th>Total</th><th>Percentage</th><th>Grade</th><th>Status</th></tr>";
            while ($row = $recent->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['exam_assessment_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['subject_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['class_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['obtained_marks']) . "</td>";
                echo "<td>" . htmlspecialchars($row['total_marks']) . "</td>";
                echo "<td>" . htmlspecialchars($row['percentage']) . "</td>";
                echo "<td>" . htmlspecialchars($row['grade']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ exam_results table does NOT exist</p>";
    }
    
    // Check exam_assessments table
    $assessCheck = $pdo->query("SHOW TABLES LIKE 'exam_assessments'");
    if ($assessCheck->rowCount() > 0) {
        echo "<p style='color: green;'>✓ exam_assessments table exists</p>";
        
        $count = $pdo->query("SELECT COUNT(*) FROM exam_assessments")->fetchColumn();
        echo "<p>Total exam assessments: <strong>$count</strong></p>";
        
        // Check for student submissions
        $studentCount = $pdo->query("SELECT COUNT(*) FROM exam_assessments WHERE student_id > 0")->fetchColumn();
        echo "<p>Student submissions: <strong>$studentCount</strong></p>";
        
        if ($studentCount > 0) {
            echo "<h3>Recent student assessments:</h3>";
            $recent = $pdo->query("SELECT * FROM exam_assessments WHERE student_id > 0 ORDER BY exam_assessment_id DESC LIMIT 5");
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Assessment ID</th><th>Student ID</th><th>Subject ID</th><th>Class ID</th><th>Type</th><th>Status</th><th>Timespan</th></tr>";
            while ($row = $recent->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['exam_assessment_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['subject_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['class_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td>" . htmlspecialchars($row['timespan']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ exam_assessments table does NOT exist</p>";
    }
    
    // Check exam_assessments_data table
    $dataCheck = $pdo->query("SHOW TABLES LIKE 'exam_assessments_data'");
    if ($dataCheck->rowCount() > 0) {
        echo "<p style='color: green;'>✓ exam_assessments_data table exists</p>";
        
        $count = $pdo->query("SELECT COUNT(*) FROM exam_assessments_data")->fetchColumn();
        echo "<p>Total question responses: <strong>$count</strong></p>";
        
    } else {
        echo "<p style='color: red;'>✗ exam_assessments_data table does NOT exist</p>";
    }
    
    // Check exam_questions table
    $questionsCheck = $pdo->query("SHOW TABLES LIKE 'exam_questions'");
    if ($questionsCheck->rowCount() > 0) {
        echo "<p style='color: green;'>✓ exam_questions table exists</p>";
        
        $count = $pdo->query("SELECT COUNT(*) FROM exam_questions")->fetchColumn();
        echo "<p>Total questions: <strong>$count</strong></p>";
        
    } else {
        echo "<p style='color: red;'>✗ exam_questions table does NOT exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If tables don't exist, run the migration script: <code>database/run_exam_migration.sql</code></li>";
echo "<li>If tables exist but no data, check if students are actually submitting exams</li>";
echo "<li>Check the error logs for any submission errors</li>";
echo "<li>Verify that the exam_take.php page is working correctly</li>";
echo "</ol>";
?>
