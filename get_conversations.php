<?php
require_once '../db.php';
requireLogin();

header('Content-Type: application/json');

$currentUserId = getCurrentUserId();

try {
    // Get user's conversations with latest message and unread count
    $conversations = $database->fetchAll("
        SELECT DISTINCT
            c.id as conversation_id,
            c.type,
            c.name as group_name,
            c.updated_at,
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
            (SELECT u.full_name FROM messages m 
             JOIN users u ON m.sender_id = u.id
             WHERE m.conversation_id = c.id 
             ORDER BY m.sent_at DESC LIMIT 1) as last_sender_name,
            (SELECT COUNT(*) FROM messages m 
             LEFT JOIN message_status ms ON m.id = ms.message_id AND ms.user_id = ?
             WHERE m.conversation_id = c.id AND m.sender_id != ? AND (ms.status IS NULL OR ms.status != 'read')) as unread_count
        FROM conversations c
        JOIN conversation_participants cp ON c.id = cp.conversation_id
        WHERE cp.user_id = ?
        ORDER BY last_message_time DESC NULLS LAST, c.created_at DESC
    ", [$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
    
    echo json_encode([
        'success' => true,
        'conversations' => $conversations
    ]);
    
} catch (Exception $e) {
    error_log("Get conversations error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load conversations']);
}
?>
