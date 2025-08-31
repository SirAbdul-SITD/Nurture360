<?php
require 'settings.php';

//Get questions and opyions
// try {
//     // Fetch data from questions table
//     $sql = "SELECT question_id, question, option1, option2, option3, option4, option5 FROM questions";
//     $stmt = $pdo->query($sql);

//     if ($stmt->rowCount() > 0) {
//         // Display data
//         while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//             echo "<h3>Question ID: " . htmlspecialchars($row['question_id']) . "</h3>";
//             echo "<h3>" . htmlspecialchars($row['question']) . "</h3>";
//             echo "<ul>";
//             for ($i = 1; $i <= 5; $i++) {
//                 $option = 'option' . $i;
//                 if (!empty($row[$option])) {
//                     echo "<li>" . htmlspecialchars($row[$option]) . "</li>";
//                 }
//             }
//             echo "</ul>";
//         }
//     } else {
//         echo "No questions found.";
//     }
// } catch (PDOException $e) {
//     echo "Error: " . $e->getMessage();
// }

// Fetch only question_id and question from the questions table


try {
    // Fetch only question_id and question from the questions table
    $sql = "SELECT question_id, question FROM questions";
    $stmt = $pdo->query($sql);

    if ($stmt->rowCount() > 0) {
        // Display data
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<h3>Question ID: " . htmlspecialchars($row['question_id']) . "</h3>";
            echo "<p>" . htmlspecialchars($row['question']) . "</p>";
        }
    } else {
        echo "No questions found.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>




<!-- 
UPDATE questions SET answer = 2 WHERE question_id = 1;
UPDATE questions SET answer = 2 WHERE question_id = 2;
UPDATE questions SET answer = 2 WHERE question_id = 3;
UPDATE questions SET answer = 3 WHERE question_id = 4;
UPDATE questions SET answer = 2 WHERE question_id = 5;
UPDATE questions SET answer = 3 WHERE question_id = 6;
UPDATE questions SET answer = 2 WHERE question_id = 7;
UPDATE questions SET answer = 2 WHERE question_id = 8;
UPDATE questions SET answer = 1 WHERE question_id = 9;
UPDATE questions SET answer = 2 WHERE question_id = 10;
UPDATE questions SET answer = 2 WHERE question_id = 11;
UPDATE questions SET answer = 2 WHERE question_id = 12;
UPDATE questions SET answer = 3 WHERE question_id = 13;
UPDATE questions SET answer = 2 WHERE question_id = 14;
UPDATE questions SET answer = 3 WHERE question_id = 15;
UPDATE questions SET answer = 2 WHERE question_id = 16;
UPDATE questions SET answer = 2 WHERE question_id = 17;
UPDATE questions SET answer = 2 WHERE question_id = 18;
UPDATE questions SET answer = 2 WHERE question_id = 19;
UPDATE questions SET answer = 2 WHERE question_id = 20;
UPDATE questions SET answer = 2 WHERE question_id = 21;
UPDATE questions SET answer = 2 WHERE question_id = 22;
UPDATE questions SET answer = 1 WHERE question_id = 23;
UPDATE questions SET answer = 1 WHERE question_id = 24;
UPDATE questions SET answer = 3 WHERE question_id = 25;
UPDATE questions SET answer = 2 WHERE question_id = 26;
UPDATE questions SET answer = 3 WHERE question_id = 27;
UPDATE questions SET answer = 3 WHERE question_id = 28;
UPDATE questions SET answer = 1 WHERE question_id = 29;
UPDATE questions SET answer = 2 WHERE question_id = 30;
UPDATE questions SET answer = 2 WHERE question_id = 31;
UPDATE questions SET answer = 2 WHERE question_id = 32;
UPDATE questions SET answer = 2 WHERE question_id = 33;
UPDATE questions SET answer = 3 WHERE question_id = 34;
UPDATE questions SET answer = 3 WHERE question_id = 35;
UPDATE questions SET answer = 2 WHERE question_id = 36;
UPDATE questions SET answer = 2 WHERE question_id = 37;
UPDATE questions SET answer = 3 WHERE question_id = 38;
UPDATE questions SET answer = 2 WHERE question_id = 39;
UPDATE questions SET answer = 2 WHERE question_id = 40;
UPDATE questions SET answer = 1 WHERE question_id = 41;
UPDATE questions SET answer = 1 WHERE question_id = 42;
UPDATE questions SET answer = 2 WHERE question_id = 43;
UPDATE questions SET answer = 1 WHERE question_id = 44;
UPDATE questions SET answer = 2 WHERE question_id = 45; -->





INSERT INTO `lessons` (`lesson_id`, `subject_id`, `class_id`, `lesson_number`, `title`, `description`, `content`,
`thumbnail`, `video`) VALUES
(NULL, '11', '4', 'Lesson 1', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 2', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 3', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 4', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 5', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 6', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 7', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 8', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 9', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 10', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 11', '', '', '', '', ''),
(NULL, '11', '4', 'Lesson 12', '', '', '', '', '')