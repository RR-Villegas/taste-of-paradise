<?php
include 'php/config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                if ($user['role'] === 'admin') {
                    header("Location: template/admin.php");
                } else {
                    header("Location: template/homepage.html");
                }
                exit();
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "No user found with that email.";
        }
        $stmt->close();
    } elseif (isset($_POST['signup'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $password);

        if ($stmt->execute()) {
            $message = "Account created successfully. Please log in.";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taste of Paradise</title>
    <link rel="stylesheet" href="static/css/landingpage.css" />
    <style>
      /* Minimal CSS for fade/slide transitions between login and signup */
      .right-panel .form-box {
        position: absolute;
        width: 100%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        transition: opacity 0.6s ease, transform 0.6s ease;
      }
      .right-panel .form-box.hidden {
        opacity: 0;
        pointer-events: none;
        transform: translate(-50%, -60%);
      }
      .message {
        color: red;
        text-align: center;
        margin-bottom: 10px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="left-panel">
        <div class="overlay">
          <img src="static/image/Logo.png.png" alt="Taste of Paradise" />
          <h2>LOGIN YOUR ACCOUNT</h2>
          <div class="socials">
            <a href="#"><i class="fa fa-facebook"></i></a>
            <a href="#"><i class="fa fa-youtube"></i></a>
          </div>
        </div>
      </div>

      <div class="right-panel" style="position: relative">
        <!-- Login Form -->
        <div class="login-box form-box" id="loginBox">
          <p class="subtitle">Taste Happiness, One Bite at a Time</p>
          <?php if ($message) echo "<div class='message'>$message</div>"; ?>
          <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit" name="login">Login</button>
            <a href="#" class="forgot">Forgot Password?</a>
            <div class="tab">
              <a href="#" class="fb-btn" target="_blank">ⓕ 𝐟𝐚𝐜𝐞𝐛𝐨𝐯𝐨𝐨𝐤</a>
              <a href="#" class="ig-btn" target="_blank">🅾 𝐈𝐧𝐬𝐭𝐚𝐠𝐫𝐚𝐦</a>
            </div>
            <p style="margin-bottom: 10px; font-size: 0.95rem">
              Don't have an account?
              <span id="showSignup" style="cursor: pointer; color: #ffcc80"
                >Sign up</span
              >
            </p>
          </form>
        </div>

        <!-- Signup Form -->
        <div class="signup-box form-box hidden" id="signupBox">
          <p class="subtitle" style="margin-left: 42px">
            Create your Taste of Paradise Account
          </p>
          <form method="POST" action="">
            <div style="display: flex; gap: 10px">
              <input type="text" name="first_name" placeholder="First Name" required />
              <input type="text" name="last_name" placeholder="Last Name" required />
            </div>
            <input type="text" name="username" placeholder="Username" style="width: 82%" />
            <input
              type="email"
              name="email"
              placeholder="Email Address"
              style="width: 82%"
            />

            <div style="display: flex; gap: 10px">
              <input type="password" name="password" placeholder="Password" required />
              <input type="password" name="confirm_password" placeholder="Confirm Password" required />
            </div>
            <button type="submit" name="signup">Sign Up</button>
            <p style="margin-top: 10px; font-size: 0.95rem">
              Already have an account?
              <span id="showLogin" style="cursor: pointer; color: #ffcc80"
                >Log in</span
              >
            </p>
          </form>
        </div>
      </div>
    </div>

    <script>
      const loginBox = document.getElementById("loginBox");
      const signupBox = document.getElementById("signupBox");
      const showSignup = document.getElementById("showSignup");
      const showLogin = document.getElementById("showLogin");

      showSignup.addEventListener("click", () => {
        loginBox.classList.add("hidden");
        signupBox.classList.remove("hidden");
      });

      showLogin.addEventListener("click", () => {
        signupBox.classList.add("hidden");
        loginBox.classList.remove("hidden");
      });
    </script>
  </body>
</html>

