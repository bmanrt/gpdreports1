<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not logged in']));
}

// Get user data
$auth = new Auth();
$currentUser = $auth->getUser($_SESSION['user_id']);

// Check if user is admin
if (!isset($currentUser['role']) || $currentUser['role'] !== 'admin') {
    die(json_encode(['error' => 'Not authorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    try {
        $userId = (int)$_POST['user_id'];
        
        // Don't allow deleting self
        if ($userId === (int)$_SESSION['user_id']) {
            die(json_encode(['error' => 'Cannot delete your own account']));
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
