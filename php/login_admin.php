<?php
// login_admin.php

session_start();
require_once 'config.php';

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
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    // Redirect to the Admin Dashboard
                    header("Location: ../php/admin.php");
                    exit();
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