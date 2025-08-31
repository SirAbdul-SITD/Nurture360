<?php
require("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Retrieve form data
    $email = $_POST['email'];
    $status = "expired";

    // Validate and sanitize the email
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);

    // Create an array to store the response data
    $response = array();

    if (!$email) {
        // Invalid email format
        $response['success'] = false;
        $response['message'] = 'Invalid email format. Please correct and try again';
    } else {
        // Check if the user email exists in the database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if there is an unexpired OTP for the user
            $stmt = $pdo->prepare("SELECT create_datetime FROM otp WHERE user = ? ORDER BY create_datetime DESC LIMIT 1");
            $stmt->execute([$email]);
            $latestOtp = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($latestOtp) {
                // Calculate the time difference between the current time and the latest OTP's create_datetime
                $createDatetime = new DateTime($latestOtp['create_datetime']);
                $currentDatetime = new DateTime();
                $interval = $createDatetime->diff($currentDatetime);

                // Check if the interval is greater than or equal to 6 minutes
                if ($interval->i >= 8 || $latestOtp['status'] !== $status) {
                    // Generate a random OTP (6-digit)
                    $otp = mt_rand(100000, 999999);

                    // Store OTP in the OTP table along with the user's email and create_datetime
                    $create_datetime = date("Y-m-d H:i:s");
                    $stmt = $pdo->prepare("INSERT INTO otp (user, otp, create_datetime) VALUES (?, ?, ?)");
                    $stmt->execute([$email, $otp, $create_datetime]);

                    // TODO: Send the OTP to the user via email
                    $to = $email;
                    $subject = "OTP Verification";
                    $message = file_get_contents("email_template.html"); // Load the email template
                    $message = str_replace("{otp}", $otp, $message); // Replace {otp} with the actual OTP
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= 'From: noreply@nurture360.com.ng' . "\r\n"; // Replace with your email address
                    mail($to, $subject, $message, $headers);

                    $_SESSION['otp_requester'] = $email;

                    $response['success'] = true;
                    $response['message'] = 'OTP sent successfully. Please check your email for the OTP.';
                } else {
                    // Calculate the time left for the OTP to expire
                    $minutesLeft = 8 - $interval->i;
                    $secondsLeft = 60 - $interval->s;

                    $response['success'] = false;
                    $response['message'] = "OTP already sent. Please wait 0{$minutesLeft}:{$secondsLeft}  before requesting a new one.";
                }
            } else {
                // Generate a random OTP (6-digit) for the user
                $otp = mt_rand(100000, 999999);

                // Store OTP in the OTP table along with the user's email and create_datetime
                $create_datetime = date("Y-m-d H:i:s");
                $stmt = $pdo->prepare("INSERT INTO otp (user, otp, create_datetime) VALUES (?, ?, ?)");
                $stmt->execute([$email, $otp, $create_datetime]);

                // TODO: Send the OTP to the user via email
                $to = $email;
                $subject = "OTP Verification";
                $message = file_get_contents("email_template.html"); // Load the email template
                $message = str_replace("{otp}", $otp, $message); // Replace {otp} with the actual OTP
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: noreply@nurture360.com.ng>' . "\r\n"; // Replace with your email address
                mail($to, $subject, $message, $headers);

                $_SESSION['otp_requester'] = $email;

                $response['success'] = true;
                $response['message'] = 'OTP sent successfully. Please check your email for the OTP.';
            }
        } else {
            // User email does not exist
            $response['success'] = false;
            $response['message'] = 'Email does not exist in our records. Please check and try again.';
        }
    }

    // Send the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>