<?php
require_once 'db.php';
requireLogin();

$currentUserId = getCurrentUserId();

// Get user's contacts
$contacts = $database->fetchAll("
    SELECT 
        u.id,
        u.username,
        u.full_name,
        u.profile_picture,
        u.is_online,
        u.last_seen,
        u.status,
        c.contact_name,
        c.added_at
    FROM contacts c
    JOIN users u ON c.contact_user_id = u.id
    WHERE c.user_id = ?
    ORDER BY u.is_online DESC, u.full_name ASC
", [$currentUserId]);

// Get suggested users (users not in contacts)
$suggestedUsers = $database->fetchAll("
    SELECT 
        u.id,
        u.username,
        u.full_name,
        u.profile_picture,
        u.is_online,
        u.status
    FROM users u
    WHERE u.id != ?
    AND u.id NOT IN (
        SELECT contact_user_id FROM contacts WHERE user_id = ?
    )
    ORDER BY u.is_online DESC, u.full_name ASC
    LIMIT 10
", [$currentUserId, $currentUserId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts - WhatsApp Clone</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
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
            flex: 1;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .action-btn:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .search-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-box {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #25D366;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }
        
        .section {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .section-count {
            background: #25D366;
            color: white;
            border-radius: 12px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .contact-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .contact-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .contact-item:hover {
            background: #f8f9fa;
        }
        
        .contact-item:last-child {
            border-bottom: none;
        }
        
        .contact-avatar {
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
        
        .contact-info {
            flex: 1;
            min-width: 0;
        }
        
        .contact-name {
            font-weight: 600;
            color: #111b21;
            margin-bottom: 4px;
            font-size: 16px;
        }
        
        .contact-status {
            color: #667781;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .contact-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .btn-chat {
            background: #25D366;
            color: white;
        }
        
        .btn-chat:hover {
            background: #20b358;
            transform: scale(1.1);
        }
        
        .btn-add {
            background: #007bff;
            color: white;
        }
        
        .btn-add:hover {
            background: #0056b3;
            transform: scale(1.1);
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
        }
        
        .btn-remove:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #667781;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .empty-subtitle {
            font-size: 14px;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .contact-item {
                padding: 12px 16px;
            }
            
            .contact-avatar {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }
            
            .contact-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="back-btn" onclick="window.location.href='chat.php'">←</button>
        <div class="header-title">Contacts</div>
        <div class="header-actions">
            <button class="action-btn" onclick="window.location.href='profile.php'" title="Profile">
                <span>👤</span>
            </button>
        </div>
    </div>
    
    <div class="container">
        <div class="search-container">
            <input type="text" class="search-box" placeholder="Search contacts..." id="searchBox">
        </div>
        
        <!-- My Contacts Section -->
        <div class="section">
            <div class="section-header">
                <span>My Contacts</span>
                <span class="section-count"><?php echo count($contacts); ?></span>
            </div>
            <div class="contact-list" id="contactsList">
                <?php if (empty($contacts)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📱</div>
                        <div class="empty-title">No contacts yet</div>
                        <div class="empty-subtitle">Add people from the suggestions below to start chatting</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <div class="contact-item" data-name="<?php echo strtolower($contact['full_name']); ?>">
                            <div class="contact-avatar" style="background: <?php echo '#' . substr(md5($contact['full_name']), 0, 6); ?>">
                                <?php echo strtoupper(substr($contact['full_name'], 0, 1)); ?>
                                <?php if ($contact['is_online']): ?>
                                    <div class="online-indicator"></div>
                                <?php endif; ?>
                            </div>
                            <div class="contact-info">
                                <div class="contact-name">
                                    <?php echo htmlspecialchars($contact['contact_name'] ?: $contact['full_name']); ?>
                                </div>
                                <div class="contact-status">
                                    <?php 
                                    if ($contact['is_online']) {
                                        echo 'Online';
                                    } else {
                                        echo htmlspecialchars($contact['status']);
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="contact-actions">
                                <button class="btn-icon btn-chat" onclick="startChat(<?php echo $contact['id']; ?>)" title="Start Chat">
                                    💬
                                </button>
                                <button class="btn-icon btn-remove" onclick="removeContact(<?php echo $contact['id']; ?>)" title="Remove Contact">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Suggested Contacts Section -->
        <?php if (!empty($suggestedUsers)): ?>
        <div class="section">
            <div class="section-header">
                <span>People You May Know</span>
                <span class="section-count"><?php echo count($suggestedUsers); ?></span>
            </div>
            <div class="contact-list" id="suggestionsList">
                <?php foreach ($suggestedUsers as $user): ?>
                    <div class="contact-item" data-name="<?php echo strtolower($user['full_name']); ?>">
                        <div class="contact-avatar" style="background: <?php echo '#' . substr(md5($user['full_name']), 0, 6); ?>">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            <?php if ($user['is_online']): ?>
                                <div class="online-indicator"></div>
                            <?php endif; ?>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="contact-status">
                                <?php 
                                if ($user['is_online']) {
                                    echo 'Online';
                                } else {
                                    echo htmlspecialchars($user['status']);
                                }
                                ?>
                            </div>
                        </div>
                        <div class="contact-actions">
                            <button class="btn-icon btn-chat" onclick="startChat(<?php echo $user['id']; ?>)" title="Start Chat">
                                💬
                            </button>
                            <button class="btn-icon btn-add" onclick="addContact(<?php echo $user['id']; ?>)" title="Add Contact">
                                ➕
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const allContacts = document.querySelectorAll('.contact-item');
            
            allContacts.forEach(contact => {
                const name = contact.getAttribute('data-name');
                if (name.includes(searchTerm)) {
                    contact.style.display = 'flex';
                } else {
                    contact.style.display = 'none';
                }
            });
        });
        
        // Start chat with user
        function startChat(userId) {
            fetch('api/start_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'chat.php';
                } else {
                    alert('Failed to start chat: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to start chat');
            });
        }
        
        // Add contact
        function addContact(userId) {
            if (!confirm('Add this person to your contacts?')) return;
            
            fetch('api/add_contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to add contact: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add contact');
            });
        }
        
        // Remove contact
        function removeContact(userId) {
            if (!confirm('Remove this contact? You can still chat with them.')) return;
            
            fetch('api/remove_contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to remove contact: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove contact');
            });
        }
    </script>
</body>
</html>
