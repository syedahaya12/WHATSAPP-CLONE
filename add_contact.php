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
$contactUserId = $input['user_id'] ?? null;
$currentUserId = getCurrentUserId();

if (!$contactUserId) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

if ($contactUserId == $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Cannot add yourself as contact']);
    exit();
}

try {
    // Check if user exists
    $user = $database->fetchOne("SELECT id FROM users WHERE id = ?", [$contactUserId]);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Check if contact already exists
    $existingContact = $database->fetchOne(
        "SELECT id FROM contacts WHERE user_id = ? AND contact_user_id = ?",
        [$currentUserId, $contactUserId]
    );
    
    if ($existingContact) {
        echo json_encode(['success' => false, 'message' => 'Contact already exists']);
        exit();
    }
    
    // Add contact
    $database->executeQuery(
        "INSERT INTO contacts (user_id, contact_user_id, added_at) VALUES (?, ?, NOW())",
        [$currentUserId, $contactUserId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact added successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Add contact error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add contact']);
}
?>
