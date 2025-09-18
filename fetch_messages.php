<?php
// fetch_messages.php - Fetch Messages for a Conversation

session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized or missing user_id']);
    exit;
}

$user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
$current_user = $_SESSION['user_id'];

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Find or create conversation
try {
    $stmt = $conn->prepare("SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$current_user, $user_id, $user_id, $current_user]);
    $convo = $stmt->fetch();

    if (!$convo) {
        $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
        $stmt->execute([$current_user, $user_id]);
        $convo_id = $conn->lastInsertId();
    } else {
        $convo_id = $convo['id'];
    }

    // Get messages with sender usernames
    $stmt = $conn->prepare("
        SELECT m.*, u.username 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.conversation_id = ? 
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$convo_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage(), 3, "error.log");
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
