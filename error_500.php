<?php
// Set the HTTP response code to 500
http_response_code(500);

// Default redirection URL (e.g., the public homepage or a known stable page)
$redirectUrl = htmlspecialchars('../index.php', ENT_QUOTES); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Server Error | Taste of Paradise</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../static/css/500_server_error.css"> 
</head>
<body>
    <div class="error-container">
        <h1>500</h1>
        <div class="error-title">💣 Internal Server Error</div>
        <p>A critical error occurred on the server while trying to process your request.</p>
        <p>The site administrator has been notified. We apologize for the inconvenience.</p>
        
        <p style="margin-top: 20px;">
            <a href="<?php echo $redirectUrl; ?>" style="color: #d89b4d; font-weight: 600;">Click here to return to the homepage.</a>
        </p>
    </div>
    
    </body>
</html>