<?php
require_once '../db.php';

// This script should be run periodically (via cron job) to mark inactive users as offline
// Users are considered offline if they haven't updated their status in the last 5 minutes

try {
    $database->executeQuery(
        "UPDATE users SET is_online = FALSE 
         WHERE is_online = TRUE 
         AND last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Offline users cleaned up'
    ]);
    
} catch (Exception $e) {
    error_log("Cleanup offline users error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Cleanup failed']);
}
?>
