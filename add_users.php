<?php
require_once 'db.php';
requireLogin();

$currentUserId = getCurrentUserId();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $existingUser = $database->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existingUser) {
            $errors[] = 'Username or email already exists';
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        try {
            $hashedPassword = hashPassword($password);
            
            $database->executeQuery(
                "INSERT INTO users (username, email, password, full_name, created_at) VALUES (?, ?, ?, ?, NOW())",
                [$username, $email, $hashedPassword, $fullName]
            );
            
            $newUserId = $database->getLastInsertId();
            
            // Automatically add as contact
            $database->executeQuery(
                "INSERT INTO contacts (user_id, contact_user_id, added_at) VALUES (?, ?, NOW())",
                [$currentUserId, $newUserId]
            );
            
            $success = "User '$fullName' has been added successfully and added to your contacts!";
        } catch (Exception $e) {
            $errors[] = 'Failed to create user: ' . $e->getMessage();
        }
    }
}

// Get all users for display
$allUsers = $database->fetchAll(
    "SELECT id, username, full_name, email, is_online, status, created_at 
     FROM users 
     WHERE id != ? 
     ORDER BY created_at DESC",
    [$currentUserId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Users - WhatsApp Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #20b358;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            flex: 1;
        }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #25D366;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }
        
        .submit-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #20b358;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .users-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .users-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .users-count {
            background: #25D366;
            color: white;
            border-radius: 12px;
            padding: 4px 12px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .user-item {
            padding: 20px;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: background 0.2s;
        }
        
        .user-item:hover {
            background: #f8f9fa;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            position: relative;
        }
        
        .online-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #25D366;
            border: 2px solid white;
            border-radius: 50%;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: #111b21;
            margin-bottom: 4px;
            font-size: 16px;
        }
        
        .user-details {
            color: #667781;
            font-size: 14px;
            display: flex;
            gap: 15px;
        }
        
        .user-status {
            color: #25D366;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 15px;
            }
            
            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <button class="back-btn" onclick="window.location.href='chat.php'">← Back</button>
            <div class="header-title">Add New Users</div>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                ❌ <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <div class="section-title">
                <span>👤</span>
                Create New User
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" 
                               placeholder="Enter username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" 
                               placeholder="Enter email address" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-input" 
                               placeholder="Enter full name" 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" 
                               placeholder="Enter password (min 6 characters)" required>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    ➕ Add User
                </button>
            </form>
        </div>
        
        <div class="users-list">
            <div class="users-header">
                <div class="section-title">
                    <span>👥</span>
                    All Users
                </div>
                <div class="users-count"><?php echo count($allUsers); ?></div>
            </div>
            
            <?php if (empty($allUsers)): ?>
                <div class="user-item" style="justify-content: center; text-align: center; color: #667781;">
                    <div>
                        <div style="font-size: 48px; margin-bottom: 10px;">👥</div>
                        <div style="font-weight: 600; margin-bottom: 5px;">No other users yet</div>
                        <div style="font-size: 14px;">Add the first user using the form above</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($allUsers as $user): ?>
                    <div class="user-item">
                        <div class="user-avatar" style="background: <?php echo '#' . substr(md5($user['full_name']), 0, 6); ?>">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            <?php if ($user['is_online']): ?>
                                <div class="online-indicator"></div>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-details">
                                <span>@<?php echo htmlspecialchars($user['username']); ?></span>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                                <span>Added: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                            </div>
                            <?php if ($user['status']): ?>
                                <div class="user-status"><?php echo htmlspecialchars($user['status']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
