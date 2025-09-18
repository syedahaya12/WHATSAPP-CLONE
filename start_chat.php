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
$targetUserId = $input['user_id'] ?? null;
$currentUserId = getCurrentUserId();

error_log("[v0] Start chat request - Current User: $currentUserId, Target User: $targetUserId");

if (!$targetUserId) {
    error_log("[v0] Missing target user ID");
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

if ($targetUserId == $currentUserId) {
    error_log("[v0] User trying to chat with themselves");
    echo json_encode(['success' => false, 'message' => 'Cannot start chat with yourself']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Check if conversation already exists between these users
    $existingConversation = $database->fetchOne(
        "SELECT c.id 
         FROM conversations c
         JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
         JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
         WHERE c.type = 'private' 
         AND cp1.user_id = ? 
         AND cp2.user_id = ?
         AND (SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = c.id) = 2",
        [$currentUserId, $targetUserId]
    );
    
    if ($existingConversation) {
        error_log("[v0] Existing conversation found: " . $existingConversation['id']);
        echo json_encode([
            'success' => true,
            'conversation_id' => $existingConversation['id'],
            'message' => 'Conversation already exists'
        ]);
        exit();
    }
    
    // Verify target user exists
    $targetUser = $database->fetchOne("SELECT id FROM users WHERE id = ?", [$targetUserId]);
    if (!$targetUser) {
        error_log("[v0] Target user not found: $targetUserId");
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    error_log("[v0] Creating new conversation between users $currentUserId and $targetUserId");
    
    // Create new private conversation
    $database->executeQuery(
        "INSERT INTO conversations (type, created_by, created_at) VALUES ('private', ?, NOW())",
        [$currentUserId]
    );
    
    $conversationId = $database->getLastInsertId();
    error_log("[v0] Created conversation with ID: $conversationId");
    
    // Add both users as participants
    $database->executeQuery(
        "INSERT INTO conversation_participants (conversation_id, user_id, joined_at) VALUES (?, ?, NOW()), (?, ?, NOW())",
        [$conversationId, $currentUserId, $conversationId, $targetUserId]
    );
    
    error_log("[v0] Added participants to conversation $conversationId");
    
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId,
        'message' => 'Chat started successfully'
    ]);
    
} catch (Exception $e) {
    error_log("[v0] Start chat error: " . $e->getMessage());
    error_log("[v0] Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Failed to start chat: ' . $e->getMessage()]);
}
?>
