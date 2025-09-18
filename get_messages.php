<?php
require_once '../db.php';
requireLogin();

header('Content-Type: application/json');

$conversationId = $_GET['conversation_id'] ?? null;
$currentUserId = getCurrentUserId();

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
    exit();
}

try {
    // Verify user is part of this conversation
    $participant = $database->fetchOne(
        "SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?",
        [$conversationId, $currentUserId]
    );
    
    if (!$participant) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    // Get messages for this conversation
    $messages = $database->fetchAll(
        "SELECT 
            m.id,
            m.sender_id,
            m.message_text,
            m.message_type,
            m.sent_at,
            u.full_name as sender_name,
            u.username as sender_username
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.sent_at ASC
        LIMIT 100",
        [$conversationId]
    );
    
    // Mark messages as read for current user
    $database->executeQuery(
        "UPDATE message_status 
         SET status = 'read', status_time = NOW() 
         WHERE message_id IN (
             SELECT id FROM messages WHERE conversation_id = ? AND sender_id != ?
         ) AND user_id = ? AND status != 'read'",
        [$conversationId, $currentUserId, $currentUserId]
    );
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    error_log("Get messages error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load messages']);
}
?>
