<?php
// Set the HTTP response code to 404
http_response_code(404);

// Default redirection URL (e.g., the public homepage)
// Adjust this path if your main page is located elsewhere
$redirectUrl = htmlspecialchars('../index.php', ENT_QUOTES); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found | Taste of Paradise</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../static/css/404_not_found.css"> 
    
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <div class="error-title">🔍 Page Not Found</div>
        <p>Uh oh! The page you were looking for doesn't exist.</p>
        <p>It might have been moved, deleted, or you mistyped the address.</p>
        <div class="countdown">Redirecting to the homepage in <span id="countdown">5</span> seconds...</div>
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