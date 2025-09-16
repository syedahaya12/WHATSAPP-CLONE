  <?php
require_once 'db.php';
startSession();

// Redirect to chat if already logged in
if (isLoggedIn()) {
    header('Location: chat.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone - Welcome</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .welcome-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: #25D366;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            font-weight: bold;
        }
        
        h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 300;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .btn-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            min-width: 140px;
        }
        
        .btn-primary {
            background: #25D366;
            color: white;
        }
        
        .btn-primary:hover {
            background: #20b358;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .features {
            margin-top: 50px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 30px;
        }
        
        .feature {
            text-align: center;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: #f8f9ff;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #667eea;
        }
        
        .feature h3 {
            color: #333;
            font-size: 1rem;
            margin-bottom: 8px;
        }
        
        .feature p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        @media (max-width: 768px) {
            .welcome-container {
                padding: 40px 30px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .btn-group {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="logo">W</div>
        <h1>WhatsApp Clone</h1>
        <p class="subtitle">Connect with friends and family through our secure messaging platform. Experience real-time communication like never before.</p>
        
        <div class="btn-group">
            <a href="login.php" class="btn btn-primary">Sign In</a>
            <a href="register.php" class="btn btn-secondary">Create Account</a>
        </div>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">💬</div>
                <h3>Real-time Chat</h3>
                <p>Instant messaging with live updates</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <h3>Secure</h3>
                <p>Your conversations are protected</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📱</div>
                <h3>Responsive</h3>
                <p>Works on all devices</p>
            </div>
        </div>
    </div>
</body>
</html>
	
