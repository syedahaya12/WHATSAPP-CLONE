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

try {
    // Remove contact
    $result = $database->executeQuery(
        "DELETE FROM contacts WHERE user_id = ? AND contact_user_id = ?",
        [$currentUserId, $contactUserId]
    );
    
    if ($result->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Contact removed successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contact not found']);
    }
    
} catch (Exception $e) {
    error_log("Remove contact error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to remove contact']);
}
?>
