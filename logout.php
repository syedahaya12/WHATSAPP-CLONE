<?php
require_once 'db.php';
startSession();

if (isLoggedIn()) {
    // Update user offline status
    $database->executeQuery("UPDATE users SET is_online = FALSE, last_seen = NOW() WHERE id = ?", [getCurrentUserId()]);
    
    // Destroy session
    session_destroy();
}

// Redirect to home page
header('Location: index.php');
exit();
?>
