<?php
require_once 'db.php';
requireLogin();

$currentUser = getCurrentUser();
$currentUserId = getCurrentUserId();

// Get user's conversations with latest message
$conversations = $database->fetchAll("
    SELECT DISTINCT
        c.id as conversation_id,
        c.type,
        c.name as group_name,
        CASE 
            WHEN c.type = 'private' THEN 
                (SELECT u.full_name FROM users u 
                 JOIN conversation_participants cp ON u.id = cp.user_id 
                 WHERE cp.conversation_id = c.id AND u.id != ?)
            ELSE c.name
        END as display_name,
        CASE 
            WHEN c.type = 'private' THEN 
                (SELECT u.profile_picture FROM users u 
                 JOIN conversation_participants cp ON u.id = cp.user_id 
                 WHERE cp.conversation_id = c.id AND u.id != ?)
            ELSE 'group-avatar.png'
        END as avatar,
        CASE 
            WHEN c.type = 'private' THEN 
                (SELECT u.is_online FROM users u 
                 JOIN conversation_participants cp ON u.id = cp.user_id 
                 WHERE cp.conversation_id = c.id AND u.id != ?)
            ELSE FALSE
        END as is_online,
        (SELECT m.message_text FROM messages m 
         WHERE m.conversation_id = c.id 
         ORDER BY m.sent_at DESC LIMIT 1) as last_message,
        (SELECT m.sent_at FROM messages m 
         WHERE m.conversation_id = c.id 
         ORDER BY m.sent_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages m 
         LEFT JOIN message_status ms ON m.id = ms.message_id AND ms.user_id = ?
         WHERE m.conversation_id = c.id AND m.sender_id != ? AND (ms.status IS NULL OR ms.status != 'read')) as unread_count
    FROM conversations c
    JOIN conversation_participants cp ON c.id = cp.conversation_id
    WHERE cp.user_id = ?
    ORDER BY last_message_time DESC
", [$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);

// Get all users for starting new chats
$allUsers = $database->fetchAll("
    SELECT id, username, full_name, profile_picture, is_online, status
    FROM users 
    WHERE id != ? 
    ORDER BY full_name
", [$currentUserId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone - Chat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            height: 100vh;
            overflow: hidden;
        }
        
        .chat-container {
            display: flex;
            height: 100vh;
            background: white;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 350px;
            background: white;
            border-right: 1px solid #e9edef;
            display: flex;
            flex-direction: column;
            min-width: 300px;
        }
        
        .sidebar-header {
            background: #f0f2f5;
            padding: 20px;
            border-bottom: 1px solid #e9edef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #25D366;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .user-name {
            font-weight: 600;
            color: #111b21;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border: none;
            background: transparent;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #54656f;
            transition: background 0.2s;
        }
        
        .action-btn:hover {
            background: #f5f6f6;
        }
        
        .search-container {
            padding: 12px 16px;
            background: white;
        }
        
        .search-box {
            width: 100%;
            padding: 10px 16px;
            border: none;
            background: #f0f2f5;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }
        
        .search-box:focus {
            background: white;
            box-shadow: 0 0 0 2px #25D366;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 16px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        
        .conversation-item:hover {
            background: #f5f6f6;
        }
        
        .conversation-item.active {
            background: #e7f3ff;
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #ddd;
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
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        
        .conversation-name {
            font-weight: 600;
            color: #111b21;
            font-size: 16px;
        }
        
        .conversation-time {
            font-size: 12px;
            color: #667781;
        }
        
        .conversation-preview {
            color: #667781;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .unread-badge {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            background: #25D366;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #efeae2;
            position: relative;
        }
        
        .chat-header {
            background: #f0f2f5;
            padding: 16px 20px;
            border-bottom: 1px solid #e9edef;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .chat-info {
            flex: 1;
        }
        
        .chat-name {
            font-weight: 600;
            color: #111b21;
            margin-bottom: 2px;
        }
        
        .chat-status {
            font-size: 13px;
            color: #667781;
        }
        
        .chat-actions {
            display: flex;
            gap: 10px;
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><defs><pattern id="chat-bg" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23d1d7db" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23chat-bg)"/></svg>');
        }
        
        .message {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 65%;
            padding: 8px 12px;
            border-radius: 8px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message.received .message-bubble {
            background: white;
            border-bottom-left-radius: 2px;
        }
        
        .message.sent .message-bubble {
            background: #d9fdd3;
            border-bottom-right-radius: 2px;
        }
        
        .message-text {
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 11px;
            color: #667781;
            text-align: right;
        }
        
        .message-input-container {
            background: #f0f2f5;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 24px;
            background: white;
            font-size: 15px;
            outline: none;
            resize: none;
            max-height: 100px;
        }
        
        .send-btn {
            width: 45px;
            height: 45px;
            border: none;
            background: #25D366;
            color: white;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: background 0.2s;
        }
        
        .send-btn:hover {
            background: #20b358;
        }
        
        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #667781;
        }
        
        .welcome-icon {
            width: 120px;
            height: 120px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 32px;
            color: #41525d;
            margin-bottom: 16px;
            font-weight: 300;
        }
        
        .welcome-subtitle {
            font-size: 16px;
            line-height: 1.5;
            max-width: 400px;
        }
        
        /* New Chat Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .modal-header {
            padding: 20px;
            background: #25D366;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .users-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .user-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.2s;
        }
        
        .user-item:hover {
            background: #f5f6f6;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: absolute;
                z-index: 100;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .chat-main {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                </div>
                <div class="header-actions">
                    <button class="action-btn" onclick="openNewChatModal()" title="New Chat">
                        <span>💬</span>
                    </button>
                    <!-- Added Add Users button -->
                    <button class="action-btn" onclick="window.location.href='add_users.php'" title="Add Users">
                        <span>👥</span>
                    </button>
                    <!-- Added Contacts button -->
                    <button class="action-btn" onclick="window.location.href='contacts.php'" title="Contacts">
                        <span>📞</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='logout.php'" title="Logout">
                        <span>🚪</span>
                    </button>
                </div>
            </div>
            
            <div class="search-container">
                <input type="text" class="search-box" placeholder="Search conversations..." id="searchBox">
            </div>
            
            <div class="conversations-list" id="conversationsList">
                <?php if (empty($conversations)): ?>
                    <div style="padding: 40px 20px; text-align: center; color: #667781;">
                        <p>No conversations yet.</p>
                        <p style="margin-top: 8px; font-size: 14px;">Start a new chat to begin messaging!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <div class="conversation-item" onclick="openConversation(<?php echo $conv['conversation_id']; ?>)" data-conversation-id="<?php echo $conv['conversation_id']; ?>">
                            <div class="conversation-avatar" style="background: <?php echo '#' . substr(md5($conv['display_name']), 0, 6); ?>">
                                <?php echo strtoupper(substr($conv['display_name'], 0, 1)); ?>
                                <?php if ($conv['is_online']): ?>
                                    <div class="online-indicator"></div>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-header">
                                    <div class="conversation-name"><?php echo htmlspecialchars($conv['display_name']); ?></div>
                                    <div class="conversation-time">
                                        <?php 
                                        if ($conv['last_message_time']) {
                                            echo date('H:i', strtotime($conv['last_message_time']));
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="conversation-preview">
                                    <?php echo $conv['last_message'] ? htmlspecialchars(substr($conv['last_message'], 0, 50)) . (strlen($conv['last_message']) > 50 ? '...' : '') : 'No messages yet'; ?>
                                </div>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $conv['unread_count']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <div class="chat-main" id="chatMain">
            <div class="welcome-screen" id="welcomeScreen">
                <div class="welcome-icon">W</div>
                <h2 class="welcome-title">WhatsApp Clone</h2>
                <p class="welcome-subtitle">
                    Send and receive messages without keeping your phone online.<br>
                    Use WhatsApp Clone on up to 4 linked devices and 1 phone at the same time.
                </p>
            </div>
            
            <!-- Chat Interface (Hidden by default) -->
            <div id="chatInterface" style="display: none; height: 100%; flex-direction: column;">
                <div class="chat-header" id="chatHeader">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <!-- Messages will be loaded here -->
                </div>
                
                <div class="message-input-container">
                    <textarea class="message-input" id="messageInput" placeholder="Type a message" rows="1"></textarea>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                        <span>➤</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Chat Modal -->
    <div class="modal" id="newChatModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Start New Chat</h3>
                <button class="close-btn" onclick="closeNewChatModal()">&times;</button>
            </div>
            <div class="users-list">
                <?php foreach ($allUsers as $user): ?>
                    <div class="user-item" onclick="startNewChat(<?php echo $user['id']; ?>)">
                        <div class="conversation-avatar" style="background: <?php echo '#' . substr(md5($user['full_name']), 0, 6); ?>">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            <?php if ($user['is_online']): ?>
                                <div class="online-indicator"></div>
                            <?php endif; ?>
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="conversation-preview" style="color: #25D366;">
                                <?php echo htmlspecialchars($user['status']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        let currentConversationId = null;
        let currentUserId = <?php echo $currentUserId; ?>;
        let messagePollingInterval = null;
        
        // Auto-resize message input
        document.getElementById('messageInput').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
        
        // Send message on Enter (but allow Shift+Enter for new line)
        document.getElementById('messageInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // New Chat Modal Functions
        function openNewChatModal() {
            document.getElementById('newChatModal').style.display = 'block';
        }
        
        function closeNewChatModal() {
            document.getElementById('newChatModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('newChatModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNewChatModal();
            }
        });
        
        // Start new chat with user
        function startNewChat(userId) {
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
                    closeNewChatModal();
                    // Refresh the page to show the new conversation
                    window.location.reload();
                } else {
                    alert('Failed to start chat: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to start chat');
            });
        }
        
        // Open conversation
        function openConversation(conversationId) {
            // Remove active class from all conversations
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to selected conversation
            document.querySelector(`[data-conversation-id="${conversationId}"]`).classList.add('active');
            
            currentConversationId = conversationId;
            
            // Hide welcome screen and show chat interface
            document.getElementById('welcomeScreen').style.display = 'none';
            document.getElementById('chatInterface').style.display = 'flex';
            
            // Load conversation details and messages
            loadConversation(conversationId);
            
            // Start polling for new messages
            if (messagePollingInterval) {
                clearInterval(messagePollingInterval);
            }
            messagePollingInterval = setInterval(() => {
                loadMessages(conversationId);
            }, 2000);
        }
        
        // Load conversation details
        function loadConversation(conversationId) {
            fetch(`api/get_conversation.php?id=${conversationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateChatHeader(data.conversation);
                        loadMessages(conversationId);
                    }
                })
                .catch(error => console.error('Error loading conversation:', error));
        }
        
        // Update chat header
        function updateChatHeader(conversation) {
            const chatHeader = document.getElementById('chatHeader');
            const isOnline = conversation.is_online;
            
            chatHeader.innerHTML = `
                <div class="chat-avatar" style="background: #${conversation.display_name.substring(0, 6)}">
                    ${conversation.display_name.charAt(0).toUpperCase()}
                </div>
                <div class="chat-info">
                    <div class="chat-name">${conversation.display_name}</div>
                    <div class="chat-status">${isOnline ? 'Online' : 'Last seen recently'}</div>
                </div>
                <div class="chat-actions">
                    <button class="action-btn" title="Call">
                        <span>📞</span>
                    </button>
                    <button class="action-btn" title="Video Call">
                        <span>📹</span>
                    </button>
                    <button class="action-btn" title="More">
                        <span>⋮</span>
                    </button>
                </div>
            `;
        }
        
        // Load messages for conversation
        function loadMessages(conversationId) {
            fetch(`api/get_messages.php?conversation_id=${conversationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }
        
        // Display messages in chat
        function displayMessages(messages) {
            const container = document.getElementById('messagesContainer');
            const scrollToBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 10;
            
            container.innerHTML = '';
            
            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${message.sender_id == currentUserId ? 'sent' : 'received'}`;
                
                messageDiv.innerHTML = `
                    <div class="message-bubble">
                        <div class="message-text">${message.message_text}</div>
                        <div class="message-time">${formatTime(message.sent_at)}</div>
                    </div>
                `;
                
                container.appendChild(messageDiv);
            });
            
            if (scrollToBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }
        
        // Send message
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentConversationId) return;
            
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            
            fetch('api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    input.style.height = 'auto';
                    loadMessages(currentConversationId);
                } else {
                    alert('Failed to send message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message');
            })
            .finally(() => {
                sendBtn.disabled = false;
                input.focus();
            });
        }
        
        // Format time for messages
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false 
            });
        }
        
        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const conversations = document.querySelectorAll('.conversation-item');
            
            conversations.forEach(conv => {
                const name = conv.querySelector('.conversation-name').textContent.toLowerCase();
                const preview = conv.querySelector('.conversation-preview').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || preview.includes(searchTerm)) {
                    conv.style.display = 'flex';
                } else {
                    conv.style.display = 'none';
                }
            });
        });
        
        // Update user online status periodically
        setInterval(() => {
            fetch('api/update_status.php', { method: 'POST' });
        }, 30000); // Update every 30 seconds
        
        // Handle page unload
        window.addEventListener('beforeunload', () => {
            if (messagePollingInterval) {
                clearInterval(messagePollingInterval);
            }
        });
    </script>
</body>
</html>
