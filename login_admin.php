<?php
// login_admin.php

session_start();
require_once 'config.php';
// 1. Include Brevo Configuration (Make sure this file exists and is configured)
require_once 'brevo_config.php'; // This file should define $emailApi

use Brevo\Client\Model\SendSmtpEmail;

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Error handling before DB interaction
    if (!$conn) {
        $_SESSION['message'] = "System Error: Database connection failed.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Prepare and execute the statement
    // We now select the OTP fields as well, though not strictly needed here, it's good for debugging/future use
    $sql = "SELECT user_id, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // User authenticated, check role
                if ($user['role'] === 'admin') {
                    
                    // --- OTP GENERATION & SENDING START ---
                    
                    // Generate a 6-digit OTP
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    // OTP expires in 5 minutes (300 seconds)
                    $expiry = date('Y-m-d H:i:s', time() + 300); 

                    // 1. Store OTP and Expiry in DB
                    $update_sql = "UPDATE users SET otp_code = ?, otp_expiry = ? WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssi", $otp, $expiry, $user['user_id']);
                    
                    if (!$update_stmt->execute()) {
                        $_SESSION['message'] = "Database error: Failed to save OTP.";
                        // Fall through to the final redirect
                    } else {
                        $update_stmt->close();
                        
                        // 2. Send OTP Email using Brevo
                        try {
                            // Ensure $emailApi is defined in brevo_config.php
                            if (!isset($emailApi)) {
                                throw new Exception("Brevo API client not configured.");
                            }
                            
                            $sendSmtpEmail = new SendSmtpEmail([
                                'to'      => [['email' => $email, 'name' => 'Admin User']],
                                // Set sender to a verified email in your Brevo account
                                'sender'  => ['email' => 'bigtouhouenjoyer@gmail.com', 'name' => 'Fish baitss'], 
                                'subject' => 'Your Admin Login Verification Code',
                                'htmlContent' => '
                                    <html>
                                    <head></head>
                                    <body>
                                        <p>Your One-Time Password (OTP) for admin login is: <strong>' . htmlspecialchars($otp) . '</strong></p>
                                        <p>This code is valid for 5 minutes.</p>
                                    </body>
                                    </html>
                                '
                            ]);
                            
                            $emailApi->sendTransacEmail($sendSmtpEmail);
                            
                            // 3. Store temporary data and redirect to verification page
                            $_SESSION['temp_user_id'] = $user['user_id'];
                            $_SESSION['temp_role'] = $user['role'];
                            $_SESSION['message'] = "A verification code has been sent to your email.";
                            
                            // Redirect to the new OTP verification page
                            header("Location: otp_verification.php");
                            exit();

                        } catch (Exception $e) {
                            $_SESSION['message'] = "Error sending OTP email: " . $e->getMessage();
                            // Fall through to the final redirect to display the error
                        }
                    }
                    // --- OTP GENERATION & SENDING END ---
                    
                } else {
                    $_SESSION['message'] = "Access denied: Admins only.";
                }
            } else {
                $_SESSION['message'] = "Invalid email or password."; 
            }
        } else {
            $_SESSION['message'] = "Invalid email or password."; 
        }
        
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error: Failed to prepare statement.";
    }
    
    $conn->close();

    // Redirect back to avoid form resubmission (Post-Redirect-Get Pattern)
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login | Taste of Paradise</title>
    <link rel="stylesheet" href="../static/css/admin_login.css" /> 
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="overlay">
                <img src="../static/image/Logo.png" alt="Taste of Paradise" />
                <h2>ADMIN LOGIN</h2>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-box form-box" id="loginBox">
                <p class="subtitle">Enter your admin credentials</p>
                
                <?php 
                // Display the flash message if it exists
                if ($message): 
                ?>
                <div class='message'><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="email" name="email" placeholder="Email" required />
                    <input type="password" name="password" placeholder="Password" required />
                    <button type="submit" name="login">Login</button>
                    <p style="margin-top: 10px; font-size: 0.95rem">
                        &nbsp;
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>