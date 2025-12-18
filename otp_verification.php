<?php
// otp_verification.php

session_start();
require_once 'config.php'; // Contains $conn

// Check if the user is in the middle of a 2FA process
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_role'])) {
    $_SESSION['message'] = "Please log in first to receive a verification code.";
    header("Location: login_admin.php");
    exit();
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_otp'])) {
    $user_id = $_SESSION['temp_user_id'];
    $submitted_otp = $_POST['otp'] ?? '';
    
    if (empty($submitted_otp) || strlen($submitted_otp) !== 6) {
        $_SESSION['message'] = "Invalid OTP format.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 1. Fetch the stored OTP and expiry
    $sql = "SELECT otp_code, otp_expiry FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        $current_time = date('Y-m-d H:i:s');
        
        // 2. Check if OTP matches and is not expired
        if ($submitted_otp === $user['otp_code'] && $current_time < $user['otp_expiry']) {
            
            // Success! Finalize the login.
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['role'] = $_SESSION['temp_role'];

            // Clean up temporary and OTP data in the DB
            $cleanup_sql = "UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE user_id = ?";
            $cleanup_stmt = $conn->prepare($cleanup_sql);
            $cleanup_stmt->bind_param("i", $user_id);
            $cleanup_stmt->execute();
            $cleanup_stmt->close();

            // Clear temporary session data
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_role']);
            
            // Redirect to the Admin Dashboard
            header("Location: ../php/admin.php");
            exit();

        } elseif ($current_time >= $user['otp_expiry']) {
            $_SESSION['message'] = "Verification code has expired. Please log in again.";
        } else {
            $_SESSION['message'] = "Invalid verification code.";
        }
    } else {
        // Should not happen if temp_user_id is set
        $_SESSION['message'] = "User data error. Please try again.";
    }
    
    $conn->close();

    // Redirect to display error/message
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($conn && $conn->ping()) {
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>OTP Verification</title>
    <link rel="stylesheet" href="../static/css/admin_login.css" /> </head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="overlay">
                <h2>OTP VERIFICATION</h2>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-box form-box" id="otpBox">
                <p class="subtitle">Enter the 6-digit code sent to your email.</p>
                
                <?php if ($message): ?>
                <div class='message'><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="text" name="otp" placeholder="6-Digit OTP" required maxlength="6" pattern="\d{6}" title="Must be a 6-digit number"/>
                    <button type="submit" name="verify_otp">Verify Code</button>
                    <p style="margin-top: 10px; font-size: 0.95rem">
                        Code expires in 5 minutes.
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>