<?php
require_once 'db.php';
requireLogin();

$currentUser = getCurrentUser();
$currentUserId = getCurrentUserId();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $status = sanitizeInput($_POST['status']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) {
        $error = 'Full name is required';
    } else {
        try {
            // Update basic profile info
            $database->executeQuery(
                "UPDATE users SET full_name = ?, status = ? WHERE id = ?",
                [$full_name, $status, $currentUserId]
            );
            
            // Handle password change if provided
            if (!empty($current_password)) {
                if (empty($new_password) || empty($confirm_password)) {
                    $error = 'Please fill in all password fields';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long';
                } elseif (!verifyPassword($current_password, $currentUser['password'])) {
                    $error = 'Current password is incorrect';
                } else {
                    $hashedPassword = hashPassword($new_password);
                    $database->executeQuery(
                        "UPDATE users SET password = ? WHERE id = ?",
                        [$hashedPassword, $currentUserId]
                    );
                    $success = 'Profile and password updated successfully!';
                }
            } else {
                $success = 'Profile updated successfully!';
            }
            
            // Refresh user data
            $currentUser = getCurrentUser();
            
        } catch (Exception $e) {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - WhatsApp Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }
        
        .header {
            background: #25D366;
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .header-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .profile-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            margin-top: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #25D366, #20b358);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            margin: 0 auto 20px;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .profile-username {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .profile-form {
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        textarea:focus {
            outline: none;
            border-color: #25D366;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #25D366;
            color: white;
        }
        
        .btn-primary:hover {
            background: #20b358;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e1e5e9;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f2f5;
        }
        
        .password-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #25D366;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                margin: 10px;
                border-radius: 0;
            }
            
            .profile-form {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="back-btn" onclick="window.location.href='chat.php'">←</button>
        <div class="header-title">Profile Settings</div>
    </div>
    
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
            </div>
            <div class="profile-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
            <div class="profile-username">@<?php echo htmlspecialchars($currentUser['username']); ?></div>
        </div>
        
        <div class="profile-form">
            <?php
            // Get user stats
            $messageCount = $database->fetchOne("SELECT COUNT(*) as count FROM messages WHERE sender_id = ?", [$currentUserId])['count'];
            $conversationCount = $database->fetchOne("SELECT COUNT(*) as count FROM conversation_participants WHERE user_id = ?", [$currentUserId])['count'];
            $joinDate = date('M Y', strtotime($currentUser['created_at']));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $messageCount; ?></div>
                    <div class="stat-label">Messages Sent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $conversationCount; ?></div>
                    <div class="stat-label">Conversations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $joinDate; ?></div>
                    <div class="stat-label">Member Since</div>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required 
                               value="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status Message</label>
                        <textarea id="status" name="status" placeholder="Hey there! I am using WhatsApp Clone"><?php echo htmlspecialchars($currentUser['status']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="text" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled 
                               style="background: #e9ecef; color: #6c757d;">
                        <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                            Email cannot be changed for security reasons
                        </small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">Change Password</h3>
                    <div class="password-section">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   placeholder="Enter current password to change">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   placeholder="Enter new password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm new password">
                        </div>
                        
                        <small style="color: #666; font-size: 12px;">
                            Leave password fields empty if you don't want to change your password
                        </small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="chat.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            if (this.value !== newPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Auto-resize textarea
        document.getElementById('status').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>
