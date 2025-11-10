<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Initialize connection via your config class
$db = new config();
$conn = $db->connectDB();

$role = $_POST['role'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$role || !$username || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

$validRoles = ['admin', 'staff', 'citizen'];
if (!in_array($role, $validRoles)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid role.']);
    exit;
}

// Use MySQLi syntax
$stmt = $conn->prepare("SELECT * FROM `$role` WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // For citizens, check if account is approved
    if ($role === 'citizen' && $user['status'] !== 'approved') {
        echo json_encode(['status' => 'error', 'message' => 'Your account is pending approval. Please wait for admin approval.']);
        exit;
    }
    
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $role
    ];
    
    // Log login for staff and citizen only (not admin)
    if ($role !== 'admin') {
        logLogin($conn, $role, $user['id']);
    }
    
    echo json_encode(['status' => 'success', 'message' => ucfirst($role) . ' click ok to continue.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
}

function logLogin($conn, $role, $userId) {
    // Generate a session token
    $sessionToken = bin2hex(random_bytes(32));
    
    // Get client information
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'];
    
    // Prepare the query based on role
    if ($role === 'citizen') {
        $query = "INSERT INTO logs (citizen_id, user_type, session_token, ip_address, device_info) 
                  VALUES (?, 'citizen', ?, ?, ?)";
    } else if ($role === 'staff') {
        $query = "INSERT INTO logs (staff_id, user_type, session_token, ip_address, device_info) 
                  VALUES (?, 'staff', ?, ?, ?)";
    } else {
        return; // Don't log admin logins
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $userId, $sessionToken, $ipAddress, $deviceInfo);
    $stmt->execute();
    
    // Store the session token for later use (for logout tracking)
    $_SESSION['session_token'] = $sessionToken;
}
?>