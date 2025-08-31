<?php
require_once '../settings.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id'])) {
    $lessonId = $_POST['lesson_id'];
    $targetDir = "../docs/";
    $fileExtension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $fileName = $lessonId . "." . $fileExtension;
    $targetFilePath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
        echo "File uploaded successfully.";
    } else {
        echo "File upload failed.";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Lesson Content</title>
    <script>
        function fetchLessons(classId) {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "fetch_lessons.php?class_id=" + classId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const lessons = JSON.parse(xhr.responseText); // Parse the JSON response
                    const lessonDropdown = document.getElementById("lessonDropdown");
                    lessonDropdown.innerHTML = ""; // Clear existing options

                    // Add a default "Select Lesson" option
                    const defaultOption = document.createElement("option");
                    defaultOption.value = "";
                    defaultOption.textContent = "Select Lesson";
                    defaultOption.disabled = true;
                    defaultOption.selected = true;
                    lessonDropdown.appendChild(defaultOption);

                    // Populate the dropdown with lessons
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
        /* Reset & Box-Sizing */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Typography & Colors */
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
        input[type="file"],
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
        input[type="file"]:focus,
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

        /* Responsive spacing */
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
    <h1>Upload Lesson Content</h1>
    <form id="uploadForm" enctype="multipart/form-data">
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
        <br><br>

        <label for="lesson">Lesson - Subject:</label>
        <select name="lesson_id" id="lessonDropdown" required>
            <option value="">Select Lesson</option>
        </select>
        <br><br>

        <label for="file">File:</label>
        <input type="file" name="file" id="file" required>
        <br><br>

        <button type="submit" id="submitBtn">
            <span id="btnText">Upload</span>
            <span id="btnLoader" style="display: none;">⏳</span>
        </button>

        <div id="uploadResult" style="margin-top:1rem; font-weight:bold;"></div>
    </form>

    <script>
        document.getElementById("uploadForm").addEventListener("submit", function (e) {
            e.preventDefault(); // Prevent default form submission

            const btnText = document.getElementById("btnText");
            const btnLoader = document.getElementById("btnLoader");
            const submitBtn = document.getElementById("submitBtn");

            // Show loader, disable button
            btnText.style.display = "none";
            btnLoader.style.display = "inline";
            submitBtn.disabled = true;

            const form = e.target;
            const formData = new FormData(form);
            const resultDiv = document.getElementById("uploadResult");

            fetch("process_upload.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.text())
                .then(data => {
                    resultDiv.textContent = data;
                    resultDiv.style.color = data.includes("successfully") ? "green" : "red";
                    // form.reset(); // optional: reset form on success
                })
                .catch(error => {
                    resultDiv.textContent = "Upload failed. Please try again.";
                    resultDiv.style.color = "red";
                    console.error("Upload error:", error);
                }).finally(() => {
                    // Restore button
                    btnText.style.display = "inline";
                    btnLoader.style.display = "none";
                    submitBtn.disabled = false;
                    // form.reset();
                });
        });
    </script>
</body>

</html>