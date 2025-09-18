<?php
require_once '../db.php';
requireLogin();

header('Content-Type: application/json');

$currentUserId = getCurrentUserId();

try {
    // Get online users count and recent activity
    $onlineUsers = $database->fetchAll(
        "SELECT COUNT(*) as count FROM users WHERE is_online = TRUE AND id != ?",
        [$currentUserId]
    );
    
    $recentMessages = $database->fetchAll(
        "SELECT COUNT(*) as count FROM messages WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    
    echo json_encode([
        'success' => true,
        'online_users' => $onlineUsers[0]['count'],
        'recent_messages' => $recentMessages[0]['count']
    ]);
    
} catch (Exception $e) {
    error_log("Get online users error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get online users']);
}
?>
