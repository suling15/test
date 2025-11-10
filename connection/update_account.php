<?php
session_start();
require_once '../connection/config.php';
$db = new config();
$conn = $db->connectDB();

$response = ['success' => false, 'message' => '', 'redirect' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? '';
        $username = $_POST['username'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM citizen WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($currentPassword, $result['password'])) {
            throw new Exception("Current password is incorrect.");
        }
        
        // Prepare update query
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE citizen SET username = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $username, $hashedPassword, $id);
        } else {
            $sql = "UPDATE citizen SET username = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $username, $id);
        }
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Account updated successfully!';
            
            // If username changed, update session and redirect to prevent issues
            if ($_SESSION['user']['username'] !== $username) {
                $_SESSION['user']['username'] = $username;
                $response['redirect'] = 'citizen_profile.php';
            }
        } else {
            throw new Exception("Failed to update account: " . $stmt->error);
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>