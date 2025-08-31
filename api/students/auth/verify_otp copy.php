<?php
require("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Retrieve form data
    $otp = $_POST['otp'];

    // Validate and sanitize the OTP
    $otp = filter_var($otp, FILTER_SANITIZE_NUMBER_INT);

    // Create an array to store the response data
    $response = array();

    if (!$otp || strlen($otp) !== 6) {
        // Invalid OTP format
        $response['success'] = false;
        $response['message'] = 'Invalid OTP format. Please enter a 6-digit OTP.';
    } else {
        // Check if the OTP exists and is still valid (created within the last 10 minutes)
        $now = date("Y-m-d H:i:s");
        $stmt = $pdo->prepare("SELECT * FROM otp WHERE otp = ? AND create_datetime >= DATE_SUB(NOW(), INTERVAL 8 MINUTE)");
        $stmt->execute([$otp]);
        $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otpRecord) {
            // OTP is valid, log the user in (you can perform the login actions here or in otp_login.php)
            $_SESSION['email'] = $otpRecord['user'];

            $response['success'] = true;
            $response['message'] = 'OTP verified successfully. You are now logged in.';
        } else {
            // Invalid or expired OTP
            $response['success'] = false;
            $response['message'] = 'Invalid or expired OTP. Please check and try again or request a new OTP.';
        }
    }

    // Send the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>