<?php
// Set the HTTP response code to 401
http_response_code(401);

// Get the redirect URL from the query string or default to the admin login page
$redirectUrl = htmlspecialchars($_GET['redirect'] ?? '/taste-of-paradise-a/index.php', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 Unauthorized - Admin Access</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/taste-of-paradise-a/static/css/401_unauthorized.css"> 
</head>
<body>
    <div class="error-container">
        <h1>401</h1>
        <div class="error-title">🔒 Unauthorized Access</div>
        <p>You attempted to access an **Admin Area** without proper credentials.</p>
        <p>Access is restricted. You must log in as an administrator to continue.</p>
        <div class="countdown">Redirecting to login in <span id="countdown">5</span> seconds...</div>
    </div>
    <script>
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        // PHP securely outputs the redirect URL into the JavaScript variable
        const redirectUrl = '<?php echo $redirectUrl; ?>'; 
        
        const interval = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = redirectUrl;
            }
        }, 1000);
    </script>
</body>
</html>