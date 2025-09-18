<?php
require_once '../db.php';
requireLogin();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$currentUserId = getCurrentUserId();

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit();
}

try {
    $users = $database->fetchAll(
        "SELECT id, username, full_name, profile_picture, is_online, status
         FROM users 
         WHERE id != ? 
         AND (full_name LIKE ? OR username LIKE ? OR email LIKE ?)
         ORDER BY is_online DESC, full_name ASC
         LIMIT 20",
        [$currentUserId, "%$query%", "%$query%", "%$query%"]
    );
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    error_log("Search users error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search failed']);
}
?>
