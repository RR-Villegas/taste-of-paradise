<?php
require_once 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                header("Location: ../php/admin.php");
                exit();
            } else {
                $message = "Access denied: Admins only.";
            }
        } else {
            $message = "Invalid password.";
        }
    } else {
        $message = "No user found with that email.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login | Taste of Paradise</title>
    <link rel="stylesheet" href="../static/css/index.css" />
  </head>
  <body>
    <div class="container">
      <div class="left-panel">
        <div class="overlay">
          <img src="../static/image/Logo.png.png" alt="Taste of Paradise" />
          <h2>ADMIN LOGIN</h2>
        </div>
      </div>

      <div class="right-panel" style="position: relative">
        <div class="login-box form-box" id="loginBox">
          <p class="subtitle">Enter your admin credentials</p>
          <?php if ($message) echo "<div class='message'>$message</div>"; ?>
          <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit" name="login">Login</button>
            <p style="margin-top: 10px; font-size: 0.95rem">
              Not an admin? <a href="../index.php">User login</a>
            </p>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>
