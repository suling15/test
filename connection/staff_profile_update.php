<?php
ob_start();
error_reporting(0);
header('Content-Type: application/json');

session_start();
require_once '../connection/config.php';

$response = ['success' => false, 'message' => ''];
$db = new config();
$conn = $db->connectDB();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $staffId = $_POST['staff_id'] ?? '';
    if (empty($staffId)) {
        throw new Exception("Missing staff ID");
    }

    // Handle profile image upload
    $imagePath = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "../staff_image/";
        
        // Check and create directory for profile image
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception("Failed to create profile image directory: $targetDir");
            }
        }
        
        if (!is_writable($targetDir)) {
            throw new Exception("Upload directory is not writable: $targetDir");
        }
        
        $imageInfo = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception("Uploaded file is not a valid image");
        }
        
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            throw new Exception("Only JPG, PNG & GIF images are allowed");
        }
        
        if ($_FILES['profile_image']['size'] > 5000000) {
            throw new Exception("Image size exceeds 5MB limit");
        }
        
        $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . strtolower($extension);
        $targetFile = $targetDir . $newFileName;
        
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            throw new Exception("Failed to save uploaded file to: $targetFile");
        }
        $imagePath = $newFileName;
    }

    // Handle valid ID upload
    $validIdPath = null;
    if (!empty($_FILES['valid_id']['name'])) {
        $targetDir = "../uploads/staff_validID/";
        
        // Create directory if it doesn't exist with proper permissions
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception("Failed to create valid ID directory: $targetDir. Please check parent directory permissions.");
            }
        } else {
            // Directory exists, check if it's writable
            if (!is_writable($targetDir)) {
                // Try to change permissions
                if (!chmod($targetDir, 0755)) {
                    throw new Exception("Valid ID directory exists but is not writable: $targetDir");
                }
            }
        }
        
        // Check if file is a valid document type
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $fileExtension = strtolower(pathinfo($_FILES['valid_id']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("Only JPG, PNG, and PDF files are allowed for valid ID");
        }
        
        if ($_FILES['valid_id']['size'] > 5000000) {
            throw new Exception("Valid ID file size exceeds 5MB limit");
        }
        
        $newFileName = "staff_" . $staffId . "_" . uniqid() . '.' . $fileExtension;
        $targetFile = $targetDir . $newFileName;
        
        if (!move_uploaded_file($_FILES['valid_id']['tmp_name'], $targetFile)) {
            $error = error_get_last();
            throw new Exception("Failed to save valid ID file to: $targetFile. Error: " . ($error['message'] ?? 'Unknown error'));
        }
        $validIdPath = $newFileName;
        
        // Debug: Check if file was actually saved
        if (!file_exists($targetFile)) {
            throw new Exception("File upload verification failed - file not found at: $targetFile");
        }
    }

    // Prepare data
    $fields = [
        'firstname' => $_POST['firstname'] ?? '',
        'middlename' => $_POST['middlename'] ?? '',
        'lastname' => $_POST['lastname'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'birthday' => $_POST['birthday'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];

    // Check if profile exists
    $stmt = $conn->prepare("SELECT id FROM staff_profile WHERE staff_id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $profileExists = $stmt->get_result()->num_rows > 0;

    if ($profileExists) {
        // Update existing profile
        $sql = "UPDATE staff_profile SET ";
        $params = [];
        $types = '';
        
        foreach ($fields as $field => $value) {
            $sql .= "$field = ?, ";
            $params[] = $value;
            $types .= 's';
        }
        
        if ($imagePath) {
            $sql .= "image = ?, ";
            $params[] = $imagePath;
            $types .= 's';
        }
        
        if ($validIdPath) {
            $sql .= "valid_id = ?, ";
            $params[] = $validIdPath;
            $types .= 's';
        }

        $sql = rtrim($sql, ', ') . " WHERE staff_id = ?";
        $params[] = $staffId;
        $types .= 'i';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
    } else {
        // Insert new profile
        $columns = array_keys($fields);
        $values = array_values($fields);
        $types = str_repeat('s', count($values));
        
        if ($imagePath) {
            $columns[] = 'image';
            $values[] = $imagePath;
            $types .= 's';
        }
        
        if ($validIdPath) {
            $columns[] = 'valid_id';
            $values[] = $validIdPath;
            $types .= 's';
        }
        
        array_unshift($columns, 'staff_id');
        array_unshift($values, $staffId);
        $types = 'i' . $types;
        
        $sql = "INSERT INTO staff_profile (" . implode(', ', $columns) . ") VALUES (" . rtrim(str_repeat('?, ', count($columns)), ', ') . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
    }

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Profile updated successfully',
            'imagePath' => $imagePath,
            'validIdPath' => $validIdPath
        ];
    } else {
        throw new Exception("Database operation failed: " . $stmt->error);
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

ob_end_clean();
echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>