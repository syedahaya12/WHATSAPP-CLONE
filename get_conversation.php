<?php
require_once '../db.php';
requireLogin();

header('Content-Type: application/json');

$conversationId = $_GET['id'] ?? null;
$currentUserId = getCurrentUserId();

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
    exit();
}

try {
    // Get conversation details
    $conversation = $database->fetchOne(
        "SELECT 
            c.id,
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
                    (SELECT u.is_online FROM users u 
                     JOIN conversation_participants cp ON u.id = cp.user_id 
                     WHERE cp.conversation_id = c.id AND u.id != ?)
                ELSE FALSE
            END as is_online,
            CASE 
                WHEN c.type = 'private' THEN 
                    (SELECT u.last_seen FROM users u 
                     JOIN conversation_participants cp ON u.id = cp.user_id 
                     WHERE cp.conversation_id = c.id AND u.id != ?)
                ELSE NULL
            END as last_seen
        FROM conversations c
        JOIN conversation_participants cp ON c.id = cp.conversation_id
        WHERE c.id = ? AND cp.user_id = ?",
        [$currentUserId, $currentUserId, $currentUserId, $conversationId, $currentUserId]
    );
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'conversation' => $conversation
    ]);
    
} catch (Exception $e) {
    error_log("Get conversation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load conversation']);
}
?>
