<?php
require_once '../settings.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id']) && isset($_FILES['file'])) {
    $lessonId = $_POST['lesson_id'];
    $targetDir = "../docs/";

    // Get original file extension
    $fileExtension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);

    // Sanitize and generate new filename
    $fileName = basename($lessonId . "." . $fileExtension);
    $targetFilePath = $targetDir . $fileName;

    // Check and create directory if not exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Upload the file
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
        echo "File uploaded successfully. $fileExtension";
    } else {
        echo "File upload failed.";
    }
} else {
    echo "Invalid request. Lesson ID and file are required.";
}
?>