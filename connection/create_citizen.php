<?php
require_once 'config.php';
$conn = (new config())->connectDB();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Handle GET requests for viewing files
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'viewValidId':
            viewValidIdFile();
            exit;
        case 'viewProfileImage':
            viewProfileImage();
            exit;
    }
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    switch ($action) {
        case 'fetch':
            $sql = "SELECT c.id, c.username, c.status, p.firstname, p.middlename, p.lastname, 
                           p.gender, p.birthday, p.civil_status, p.address, p.contact_number, 
                           p.image, p.valid_id
                    FROM citizen c
                    JOIN profile p ON c.id = p.citizen_id
                    ORDER BY c.status, p.lastname, p.firstname";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Database query failed: " . $conn->error);
            }
            
            $citizens = [];
            while ($row = $result->fetch_assoc()) {
                $citizens[] = $row;
            }
            echo json_encode($citizens);
            break;

        case 'fetchOne':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception("Invalid citizen ID");
            }

            $stmt = $conn->prepare("SELECT c.id, c.username, c.status, p.firstname, p.middlename, p.lastname,
                                    p.gender, p.birthday, p.civil_status, p.address, p.contact_number, 
                                    p.image, p.valid_id
                                FROM citizen c
                                JOIN profile p ON c.id = p.citizen_id
                                WHERE c.id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $citizen = $result->fetch_assoc();
            
            if (!$citizen) {
                throw new Exception("Citizen not found");
            }

            echo json_encode($citizen);
            break;

        case 'updateStatus':
            $id = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            if (!$id) {
                throw new Exception("Invalid citizen ID");
            }
            
            if (!in_array($status, ['approved', 'rejected', 'pending'])) {
                throw new Exception("Invalid status value");
            }
            
            $stmt = $conn->prepare("UPDATE citizen SET status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("si", $status, $id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
            break;

        case 'add':
            // Validate required fields
            $required = ['username', 'password', 'firstname', 'lastname', 'gender', 'birthday', 'civil_status'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            $username = $_POST['username'];
            $password = $_POST['password'];
            $firstname = $_POST['firstname'];
            $middlename = $_POST['middlename'] ?? '';
            $lastname = $_POST['lastname'];
            $gender = $_POST['gender'];
            $birthday = $_POST['birthday'];
            $civil_status = $_POST['civil_status'];
            $address = $_POST['address'] ?? '';
            $contact_number = $_POST['contact_number'] ?? '';
            $imageName = '';
            $validIdName = '';

            // Create upload directories if they don't exist
            createUploadDirectories();

            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $imageName = uploadFile($_FILES['image'], '../citizen_image/');
            }

            // Handle valid ID upload
            if (!empty($_FILES['valid_id']['name'])) {
                $validIdName = uploadFile($_FILES['valid_id'], '../uploads/valid_id/');
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert into citizen table
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO citizen (username, password) VALUES (?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param("ss", $username, $hashedPassword);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                $citizen_id = $conn->insert_id;

                // Insert into profile table with valid_id
                $stmt = $conn->prepare("INSERT INTO profile 
                    (citizen_id, firstname, middlename, lastname, gender, birthday, civil_status, address, contact_number, image, valid_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param(
                    "issssssssss",
                    $citizen_id, $firstname, $middlename, $lastname, $gender, $birthday, 
                    $civil_status, $address, $contact_number, $imageName, $validIdName
                );

                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Citizen added successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        case 'edit':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception("Invalid citizen ID");
            }

            // Validate required fields
            $required = ['username', 'firstname', 'lastname', 'gender', 'birthday', 'civil_status'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            $username = $_POST['username'];
            $password = $_POST['password'] ?? '';
            $firstname = $_POST['firstname'];
            $middlename = $_POST['middlename'] ?? '';
            $lastname = $_POST['lastname'];
            $gender = $_POST['gender'];
            $birthday = $_POST['birthday'];
            $civil_status = $_POST['civil_status'];
            $address = $_POST['address'] ?? '';
            $contact_number = $_POST['contact_number'] ?? '';
            $imageName = '';
            $validIdName = '';

            // Create upload directories if they don't exist
            createUploadDirectories();

            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $imageName = uploadFile($_FILES['image'], '../citizen_image/');
            }

            // Handle valid ID upload
            if (!empty($_FILES['valid_id']['name'])) {
                $validIdName = uploadFile($_FILES['valid_id'], '../uploads/valid_id/');
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update citizen table
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE citizen SET username = ?, password = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("ssi", $username, $hashedPassword, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE citizen SET username = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("si", $username, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                // Build profile update query dynamically
                $query = "UPDATE profile SET 
                    firstname = ?, middlename = ?, lastname = ?, gender = ?, birthday = ?, 
                    civil_status = ?, address = ?, contact_number = ?";
                
                $params = [$firstname, $middlename, $lastname, $gender, $birthday, $civil_status, $address, $contact_number];
                $types = "ssssssss";

                if (!empty($imageName)) {
                    $query .= ", image = ?";
                    $params[] = $imageName;
                    $types .= "s";
                }

                if (!empty($validIdName)) {
                    $query .= ", valid_id = ?";
                    $params[] = $validIdName;
                    $types .= "s";
                }

                $query .= " WHERE citizen_id = ?";
                $params[] = $id;
                $types .= "i";

                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param($types, ...$params);

                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Citizen updated successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception("Invalid citizen ID");
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // First delete from profile table
                $stmt = $conn->prepare("DELETE FROM profile WHERE citizen_id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                // Then delete from citizen table
                $stmt = $conn->prepare("DELETE FROM citizen WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Citizen deleted successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        default:
            throw new Exception("Invalid action specified");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

// Helper functions
function createUploadDirectories() {
    $directories = [
        '../uploads/valid_id/',
        '../citizen_image/'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

function uploadFile($file, $targetDir) {
    $fileName = time() . '_' . uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload file: " . $file['name']);
    }
    
    return $fileName;
}

function viewValidIdFile() {
    global $conn;
    
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        header("HTTP/1.0 404 Not Found");
        exit("Invalid citizen ID");
    }

    $stmt = $conn->prepare("SELECT valid_id FROM profile WHERE citizen_id = ?");
    if (!$stmt) {
        header("HTTP/1.0 500 Internal Server Error");
        exit("Database error");
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        header("HTTP/1.0 500 Internal Server Error");
        exit("Database error");
    }
    
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    
    if (!$profile || empty($profile['valid_id']) || $profile['valid_id'] == '0') {
        header("HTTP/1.0 404 Not Found");
        exit("Valid ID not found");
    }

    $validIdPath = $profile['valid_id'];
    $fullPath = '../uploads/valid_id/' . basename($validIdPath);

    // Check if file exists
    if (!file_exists($fullPath)) {
        error_log("File not found: " . $fullPath);
        header("HTTP/1.0 404 Not Found");
        exit("Valid ID file not found");
    }

    // Set appropriate content type
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $contentTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf'
    ];
    
    header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($fullPath));
    header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    
    readfile($fullPath);
    exit;
}

function viewProfileImage() {
    global $conn;
    
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        header("HTTP/1.0 404 Not Found");
        exit("Invalid citizen ID");
    }

    $stmt = $conn->prepare("SELECT image FROM profile WHERE citizen_id = ?");
    if (!$stmt) {
        header("HTTP/1.0 500 Internal Server Error");
        exit("Database error");
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        header("HTTP/1.0 500 Internal Server Error");
        exit("Database error");
    }
    
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    
    if (!$profile || empty($profile['image']) || $profile['image'] == '0') {
        // Return a default image if no profile image exists
        $defaultImage = '../citizen_image/default-avatar.png';
        if (file_exists($defaultImage)) {
            header('Content-Type: image/png');
            readfile($defaultImage);
        } else {
            header("HTTP/1.0 404 Not Found");
            exit("Profile image not found");
        }
        exit;
    }

    $imagePath = $profile['image'];
    $fullPath = '../citizen_image/' . basename($imagePath);

    // Check if file exists
    if (!file_exists($fullPath)) {
        error_log("File not found: " . $fullPath);
        header("HTTP/1.0 404 Not Found");
        exit("Profile image not found");
    }

    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $contentTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    header('Content-Type: ' . ($contentTypes[$extension] ?? 'image/jpeg'));
    header('Content-Length: ' . filesize($fullPath));
    
    readfile($fullPath);
    exit;
}
?>