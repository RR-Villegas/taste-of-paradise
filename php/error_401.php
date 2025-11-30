<?php
http_response_code(401);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 Unauthorized</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); 
            margin: 0; 
        }
        .error-container { 
            background: white; 
            padding: 50px 40px; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
            text-align: center; 
            max-width: 500px; 
        }
        h1 { 
            color: #d32f2f; 
            margin: 0 0 10px 0; 
            font-size: 4rem; 
            line-height: 1; 
        }
        .error-title {
            color: #d32f2f;
            font-size: 1.5rem;
            margin: 10px 0 20px 0;
        }
        p { 
            color: #666; 
            margin: 10px 0; 
            font-size: 1rem; 
            line-height: 1.5;
        }
        .countdown { 
            color: #1976d2; 
            font-weight: bold; 
            font-size: 1.3rem; 
            margin: 30px 0 20px 0;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 6px;
        }
        .countdown span {
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>401</h1>
        <div class="error-title">Unauthorized Access</div>
        <p>You do not have permission to access this page.</p>
        <p>Please log in with admin credentials to continue.</p>
        <div class="countdown">Redirecting in <span id="countdown">5</span> seconds...</div>
    </div>
    <script>
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const redirectUrl = '<?php echo htmlspecialchars($_GET['redirect'] ?? '/taste-of-paradise-a/php/login_admin.php', ENT_QUOTES); ?>';
        
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
