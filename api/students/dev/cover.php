<?php
require 'settings.php'; // assumes $pdo is defined here

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = $_POST['lesson_id'];

    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
        $uploadDir = 'assets/images/lessons/';
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $newName = $lesson_id . '.' . $ext;
        $uploadPath = $uploadDir . $newName;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['cover']['tmp_name'], $uploadPath)) {
            echo "<p style='color: green;'>Upload successful! Saved as: $newName</p>";
        } else {
            echo "<p style='color: red;'>Failed to move uploaded file.</p>";
        }
    } else {
        echo "<p style='color: red;'>No file selected or an error occurred.</p>";
    }
}

// Fetch lessons
$stmt = $pdo->query("SELECT lesson_id, title FROM lessons ORDER BY title ASC");
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Upload Lesson Cover</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
        }

        label,
        select,
        input,
        button {
            display: block;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <h2>Upload Cover Image</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="lesson_id">Lesson Title:</label>
        <select name="lesson_id" required>
            <option value="">-- Select Lesson --</option>
            <?php foreach ($lessons as $lesson): ?>
                <option value="<?= htmlspecialchars($lesson['lesson_id']) ?>">
                    <?= htmlspecialchars($lesson['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="cover">Choose Image:</label>
        <input type="file" name="cover" accept="image/*" required>

        <button type="submit">Upload</button>
    </form>
</body>

</html>