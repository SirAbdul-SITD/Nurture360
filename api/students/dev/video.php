<?php
require_once '../settings.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id']) && isset($_POST['video_link'])) {
    $lessonId = $_POST['lesson_id'];
    $videoLink = trim($_POST['video_link']);

    if (!empty($videoLink)) {
        try {
            $stmt = $pdo->prepare("UPDATE lessons SET video = :video WHERE lesson_id = :lesson_id");
            $stmt->execute([
                ':video' => $videoLink,
                ':lesson_id' => $lessonId
            ]);
            echo "Video link updated successfully.";
        } catch (PDOException $e) {
            echo "Failed to update video link: " . $e->getMessage();
        }
    } else {
        echo "Video link cannot be empty.";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Lesson Video</title>
    <script>
        function fetchLessons(classId) {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "fetch_lessons.php?class_id=" + classId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const lessons = JSON.parse(xhr.responseText);
                    const lessonDropdown = document.getElementById("lessonDropdown");
                    lessonDropdown.innerHTML = "";

                    const defaultOption = document.createElement("option");
                    defaultOption.value = "";
                    defaultOption.textContent = "Select Lesson";
                    defaultOption.disabled = true;
                    defaultOption.selected = true;
                    lessonDropdown.appendChild(defaultOption);

                    lessons.forEach(lesson => {
                        const option = document.createElement("option");
                        option.value = lesson.lesson_id;
                        option.textContent = `${lesson.lesson_number} - ${lesson.title}`;
                        lessonDropdown.appendChild(option);
                    });
                }
            };
            xhr.send();
        }
    </script>

    <style>
        /* (Keep your original clean CSS) */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #4e73df;
            --primary-hover: #2e59d9;
            --text: #3a3b45;
            --bg: #f8f9fc;
            --white: #ffffff;
            --border: #d1d3e2;
            --radius: 0.375rem;
            --spacing: 1rem;
            --font: 'Segoe UI', Roboto, sans-serif;
        }

        body {
            font-family: var(--font);
            color: var(--text);
            background-color: var(--bg);
            line-height: 1.6;
            padding: var(--spacing);
        }

        h1 {
            text-align: center;
            margin-bottom: calc(var(--spacing) * 1.5);
            font-size: 1.75rem;
            color: var(--primary);
        }

        form {
            background: var(--white);
            max-width: 480px;
            margin: 0 auto;
            padding: calc(var(--spacing) * 1.5);
            border-radius: var(--radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        select,
        input[type="text"],
        button {
            width: 100%;
            display: block;
            font-size: 1rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: var(--spacing);
            font-family: var(--font);
        }

        select:focus,
        input[type="text"]:focus,
        button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.3);
            border-color: var(--primary);
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath fill='%236c757d' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 10px 6px;
            cursor: pointer;
        }

        button {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        @media (max-width: 576px) {
            form {
                padding: var(--spacing);
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>

</head>

<body>
    <h1>Upload Lesson Video Link</h1>
    <form action="video.php" method="POST">
        <label for="class">Class:</label>
        <select name="class" id="classDropdown" onchange="fetchLessons(this.value)" required>
            <option value="">Select Class</option>
            <?php
            try {
                $stmt = $pdo->query("SELECT class_id, title FROM classes");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['class_id']}'>{$row['title']}</option>";
                }
            } catch (PDOException $e) {
                echo "<option value=''>Error loading classes</option>";
            }
            ?>
        </select>

        <label for="lesson">Lesson - Subject:</label>
        <select name="lesson_id" id="lessonDropdown" required>
            <option value="">Select Lesson</option>
        </select>

        <label for="video_link">Video Link:</label>
        <input type="text" name="video_link" id="video_link" placeholder="Enter video URL (e.g., YouTube, Vimeo)"
            required>

        <button type="submit">Update Video Link</button>
    </form>
</body>

</html>