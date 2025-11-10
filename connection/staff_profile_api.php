<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// DB connection
require_once '../connection/config.php';
$db = new config();
$conn = $db->connectDB();

// Fetch complete user data
$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Prepare data for frontend
$profileData = [
    'firstname' => htmlspecialchars($user['firstname'] ?? ''),
    'middlename' => htmlspecialchars($user['middlename'] ?? ''),
    'lastname' => htmlspecialchars($user['lastname'] ?? ''),
    'gender' => htmlspecialchars($user['gender'] ?? ''),
    'birthday' => htmlspecialchars($user['birthday'] ?? ''),
    'contact_number' => htmlspecialchars($user['contact_number'] ?? ''),
    'address' => htmlspecialchars($user['address'] ?? ''),
    'username' => htmlspecialchars($user['username'] ?? ''),
    'password' => htmlspecialchars($user['password'] ?? ''),
    'fullname' => htmlspecialchars(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))
];

// Detect current page for menu highlighting
$current = basename($_SERVER['PHP_SELF']);
$isProfile = ($current === 'staff_profile.php');

// Include the view
require_once '../staff/staff_profile.php';
?>