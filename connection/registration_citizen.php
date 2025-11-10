<?php
require_once 'config.php'; // includes conn.php and config class
$conn = (new config())->connectDB();

header('Content-Type: application/json');

// Collect input
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$middlename = $_POST['middlename'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$gender = $_POST['gender'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$civil_status = $_POST['civil_status'] ?? '';
$address = $_POST['address'] ?? '';
$contact_number = $_POST['contact_number'] ?? '';

// Validate required fields
if (!$username || !$password || !$firstname || !$lastname || !$gender || !$birthday || !$civil_status) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

// Validate password length
if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

// Check if username already exists BEFORE file upload
try {
    $checkStmt = $conn->prepare("SELECT username FROM citizen WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username is already taken. Please choose a different username.']);
        exit;
    }
    $checkStmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error checking username availability.']);
    exit;
}

// Handle file upload
$validIdPath = '';
if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/valid_id/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES['valid_id']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;
    
    // Validate file type
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file format. Only JPG, PNG, and PDF are allowed.']);
        exit;
    }
    
    // Validate file size (max 2MB)
    if ($_FILES['valid_id']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File size must be less than 2MB.']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['valid_id']['tmp_name'], $targetPath)) {
        $validIdPath = $targetPath;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload file. Please try again.']);
        exit;
    }
} else {
    // Handle different file upload errors
    $uploadError = $_FILES['valid_id']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File is too large. Maximum size is 2MB.',
        UPLOAD_ERR_FORM_SIZE => 'File is too large. Maximum size is 2MB.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'Valid ID is required.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
    ];
    
    $errorMessage = $errorMessages[$uploadError] ?? 'File upload failed. Please try again.';
    echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    exit;
}

try {
    $conn->begin_transaction();

    // Insert into citizen table
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO citizen (username, password, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ss", $username, $hashedPassword);
    $stmt->execute();
    $citizen_id = $stmt->insert_id;

    // Insert into profile table
    $stmt = $conn->prepare("INSERT INTO profile 
        (citizen_id, firstname, middlename, lastname, gender, birthday, civil_status, address, contact_number, valid_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssss", $citizen_id, $firstname, $middlename, $lastname, $gender, $birthday, $civil_status, $address, $contact_number, $validIdPath);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Registration successful! Your account is pending approval.']);
    
} catch (Exception $e) {
    $conn->rollback();
    
    // Delete uploaded file if registration failed
    if ($validIdPath && file_exists($validIdPath)) {
        unlink($validIdPath);
    }
    
    // Check for duplicate entry error
    if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'username') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Username is already taken. Please choose a different username.']);
    } else {
        // Log the actual error for debugging
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    }
}

// Close connection
$conn->close();
?>