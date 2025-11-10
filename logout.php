<?php
session_start();
require_once __DIR__ . '/connection/config.php';

// If user is logged in either citizen or staff except for admin, update the logout time in logs
if (isset($_SESSION['user']) && $_SESSION['user']['role'] !== 'admin' && isset($_SESSION['session_token'])) {
    $db = new config();
    $conn = $db->connectDB();
    
    $role = $_SESSION['user']['role'];
    $sessionToken = $_SESSION['session_token'];
    
    // Update the logout time for this session
    $query = "UPDATE logs SET logout_time = NOW(), login_status = 'inactive' WHERE session_token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $sessionToken);
    $stmt->execute();
}

// Clear all session data
session_unset();
session_destroy();
header("Location: index.php");
exit;
?>