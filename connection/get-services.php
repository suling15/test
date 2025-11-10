<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Initialize connection via your config class
$db = new config();
$conn = $db->connectDB();

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Fetch services from database
$sql = "SELECT id, name, description, image FROM service ORDER BY id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $services = [];
    while($row = $result->fetch_assoc()) {
        // FIXED: Extract only filename from image path
        if (!empty($row['image'])) {
            $row['image'] = basename($row['image']);
        }
        $services[] = $row;
    }
    echo json_encode(['status' => 'success', 'services' => $services]);
} else {
    echo json_encode(['status' => 'success', 'services' => []]);
}

$conn->close();
?>