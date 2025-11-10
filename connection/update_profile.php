<?php
ob_start(); // Start output buffering
error_reporting(0); // Disable error display
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

    $citizenId = $_POST['citizen_id'] ?? '';
    if (empty($citizenId)) {
        throw new Exception("Missing citizen ID");
    }

    // Handle profile image upload
    $imagePath = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "../citizen_image/";
        if (!is_writable($targetDir)) {
            throw new Exception("Upload directory is not writable");
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
            throw new Exception("Failed to save uploaded file");
        }
        $imagePath = $newFileName;
    }

    // Handle valid ID upload if provided (but not shown in UI)
    $validIdPath = null;
    if (!empty($_FILES['valid_id']['name'])) {
        $targetDir = "../uploads/valid_id/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        if (!is_writable($targetDir)) {
            throw new Exception("Valid ID upload directory is not writable");
        }
        
        // Check if it's a valid file type for ID
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $extension = strtolower(pathinfo($_FILES['valid_id']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception("Only JPG, PNG, and PDF files are allowed for valid ID");
        }
        
        if ($_FILES['valid_id']['size'] > 5000000) {
            throw new Exception("Valid ID file size exceeds 5MB limit");
        }
        
        $newFileName = 'valid_id_' . $citizenId . '_' . uniqid() . '.' . $extension;
        $targetFile = $targetDir . $newFileName;
        
        if (!move_uploaded_file($_FILES['valid_id']['tmp_name'], $targetFile)) {
            throw new Exception("Failed to save valid ID file");
        }
        $validIdPath = $newFileName;
    }

    // Prepare data
    $fields = [
        'firstname' => $_POST['firstname'] ?? '',
        'middlename' => $_POST['middlename'] ?? '',
        'lastname' => $_POST['lastname'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'birthday' => $_POST['birthday'] ?? '',
        'civil_status' => $_POST['civil_status'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];

    // Check if profile exists
    $stmt = $conn->prepare("SELECT id, valid_id FROM profile WHERE citizen_id = ?");
    $stmt->bind_param("i", $citizenId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profileExists = $result->num_rows > 0;
    $existingProfile = $profileExists ? $result->fetch_assoc() : null;

    if ($profileExists) {
        // Update existing profile
        $sql = "UPDATE profile SET ";
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
        
        $sql = rtrim($sql, ', ') . " WHERE citizen_id = ?";
        $params[] = $citizenId;
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
        
        array_unshift($columns, 'citizen_id');
        array_unshift($values, $citizenId);
        $types = 'i' . $types;
        
        $sql = "INSERT INTO profile (" . implode(', ', $columns) . ") VALUES (" . rtrim(str_repeat('?, ', count($columns)), ', ') . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
    }

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Profile updated successfully',
            'imagePath' => $imagePath
        ];
    } else {
        throw new Exception("Database operation failed: " . $conn->error);
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

ob_end_clean(); // Clean any output
die(json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
?>