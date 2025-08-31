<?php
require '../settings.php'; // Includes PDO connection

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? '';
    $subject_title = trim($_POST['title'] ?? '');

    if ($class_id && $subject_title) {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (class_id, title) VALUES (:class_id, :title)");
            $stmt->execute([
                ':class_id' => $class_id,
                ':title' => $subject_title
            ]);
            $message = "✅ Subject added successfully.";
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "⚠ Please select a class and enter a subject title.";
    }
}

// Fetch classes
try {
    $classes = $pdo->query("SELECT class_id, title FROM classes ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching classes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Subject</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 400px; }
        label { display: block; margin-top: 12px; }
        select, input[type=text], button {
            width: 100%; padding: 8px; margin-top: 5px;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            background-color: #f0f8ff;
            border-left: 5px solid #007acc;
        }
    </style>
</head>
<body>

<h2>Add New Subject</h2>

<?php if (!empty($message)): ?>
    <div class="message"><?= $message; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <label for="class_id">Select Class:</label>
    <select name="class_id" id="class_id" required>
        <option value="">-- Choose Class --</option>
        <?php foreach ($classes as $class): ?>
            <option value="<?= htmlspecialchars($class['class_id']); ?>">
                <?= htmlspecialchars($class['title']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="title">Subject Title:</label>
    <input type="text" name="title" id="title" placeholder="e.g. English Language" required>

    <button type="submit">Add Subject</button>
</form>

</body>
</html>
