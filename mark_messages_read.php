<?php
require_once '../db.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$conversationId = $input['conversation_id'] ?? null;
$currentUserId = getCurrentUserId();

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
    exit();
}

try {
    // Mark all messages in conversation as read for current user
    $database->executeQuery(
        "UPDATE message_status ms
         JOIN messages m ON ms.message_id = m.id
         SET ms.status = 'read', ms.status_time = NOW()
         WHERE m.conversation_id = ? 
         AND ms.user_id = ? 
         AND m.sender_id != ?
         AND ms.status != 'read'",
        [$conversationId, $currentUserId, $currentUserId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Messages marked as read'
    ]);
    
} catch (Exception $e) {
    error_log("Mark messages read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read']);
}
?>
