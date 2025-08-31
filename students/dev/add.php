<?php
// Database connection
include("../settings.php");

// Fetch classes and subjects from the database
$classes = $pdo->query("SELECT class_id, title FROM classes");

$subjects = $pdo->query("SELECT sub.subject_id, sub.title AS subject_title, c.title AS class_title 
                         FROM subjects sub 
                         LEFT JOIN classes c ON sub.class_id = c.class_id");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lessonTitle = $_POST['lesson_title'];
    $classId = $_POST['class_id'];
    $subjectId = $_POST['subject_id'];
    $file = $_FILES['lesson_file'];

    // File upload path
    $targetDir = "../docs/";
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    // Check if file is a PDF
    if ($fileType != "pdf") {
        echo "Sorry, only PDF files are allowed.";
        exit;
    }

    // Insert lesson into database
    $stmt = $pdo->prepare("INSERT INTO lessons (lesson_number, class_id, subject_id) VALUES (?, ?, ?)");
    if ($stmt->execute([$lessonTitle, $classId, $subjectId])) {
        // Get the last inserted lesson ID
        $lessonId = $pdo->lastInsertId();

        // Rename the uploaded file to the lesson ID
        $targetFile = $targetDir . $lessonId . ".pdf";

        // Upload file
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            echo "The lesson has been uploaded successfully.";
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lesson</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white text-center">
                        <h4>Upload Lesson</h4>
                    </div>
                    <div class="card-body">
                        <form action="index.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="lesson_title" class="form-label">Lesson Title</label>
                                <input type="text" name="lesson_title" id="lesson_title" class="form-control"
                                    placeholder="Enter lesson title" required>
                            </div>

                            <div class="mb-3">
                                <label for="class_id" class="form-label">Class</label>
                                <select name="class_id" id="class_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Class --</option>
                                    <?php while ($row = $classes->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['class_id']; ?>"><?php echo $row['title']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="subject_id" class="form-label">Subject</label>
                                <select name="subject_id" id="subject_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Subject --</option>
                                    <?php while ($row = $subjects->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['subject_id']; ?>">
                                            <?php echo $row['class_title'] . " - " . $row['subject_title']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="lesson_file" class="form-label">Upload PDF</label>
                                <input type="file" name="lesson_file" id="lesson_file" class="form-control"
                                    accept="application/pdf">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Upload Lesson</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>