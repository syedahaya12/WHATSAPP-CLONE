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
$message = trim($input['message'] ?? '');
$currentUserId = getCurrentUserId();

if (!$conversationId || !$message) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
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
    
    // Insert the message
    $database->executeQuery(
        "INSERT INTO messages (conversation_id, sender_id, message_text, sent_at) VALUES (?, ?, ?, NOW())",
        [$conversationId, $currentUserId, $message]
    );
    
    $messageId = $database->getLastInsertId();
    
    // Get all participants except sender for message status
    $participants = $database->fetchAll(
        "SELECT user_id FROM conversation_participants WHERE conversation_id = ? AND user_id != ?",
        [$conversationId, $currentUserId]
    );
    
    // Create message status entries for all participants
    foreach ($participants as $participant) {
        $database->executeQuery(
            "INSERT INTO message_status (message_id, user_id, status, status_time) VALUES (?, ?, 'sent', NOW())",
            [$messageId, $participant['user_id']]
        );
    }
    
    // Update conversation timestamp
    $database->executeQuery(
        "UPDATE conversations SET updated_at = NOW() WHERE id = ?",
        [$conversationId]
    );
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'message' => 'Message sent successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Send message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
?>
