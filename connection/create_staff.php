<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header first
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../connection/config.php';

try {
    $conn = (new config())->connectDB();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';

// Ensure upload directories exist
$uploadDirs = [
    '../staff_image/',
    '../uploads/staff_validID/'
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
}

// Function to handle file uploads
function handleFileUpload($file, $targetDir) {
    if (!empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedDocTypes = ['application/pdf'];
        $allowedTypes = array_merge($allowedImageTypes, $allowedDocTypes);
        
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Please upload an image or PDF.');
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $targetDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        } else {
            throw new Exception('Failed to upload file.');
        }
    }
    return null;
}

try {
    switch ($action) {
        case 'fetch':
            $sql = "SELECT c.id, c.username, p.firstname, p.middlename, p.lastname, 
                           p.gender, p.birthday, p.address, p.contact_number, p.image, p.valid_id
                    FROM staff c
                    JOIN staff_profile p ON c.id = p.staff_id
                    ORDER BY p.lastname, p.firstname";
            $result = $conn->query($sql);
            $staff = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Get assigned services for each staff
                    $staff_id = $row['id'];
                    $services_sql = "SELECT s.id, s.name 
                                     FROM service s 
                                     JOIN staff_service ss ON s.id = ss.service_id 
                                     WHERE ss.staff_id = $staff_id";
                    $services_result = $conn->query($services_sql);
                    $services = [];
                    if ($services_result && $services_result->num_rows > 0) {
                        while ($service = $services_result->fetch_assoc()) {
                            $services[] = $service;
                        }
                    }
                    $row['services'] = $services;
                    $staff[] = $row;
                }
            }
            echo json_encode($staff);
            break;

        case 'fetchOne':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception('Staff ID is required');
            }
            
            $stmt = $conn->prepare("SELECT c.id, c.username, p.firstname, p.middlename, p.lastname, 
                                           p.gender, p.birthday, p.address, p.contact_number, p.image, p.valid_id
                                    FROM staff c
                                    JOIN staff_profile p ON c.id = p.staff_id
                                    WHERE c.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $staff = $result->fetch_assoc();
            
            if ($staff) {
                // Get assigned services
                $services_sql = "SELECT s.id, s.name 
                                 FROM service s 
                                 JOIN staff_service ss ON s.id = ss.service_id 
                                 WHERE ss.staff_id = $id";
                $services_result = $conn->query($services_sql);
                $services = [];
                if ($services_result && $services_result->num_rows > 0) {
                    while ($service = $services_result->fetch_assoc()) {
                        $services[] = $service;
                    }
                }
                $staff['services'] = $services;
            }
            
            echo json_encode($staff ? $staff : []);
            break;

        case 'add':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $firstname = $_POST['firstname'] ?? '';
            $middlename = $_POST['middlename'] ?? '';
            $lastname = $_POST['lastname'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $birthday = $_POST['birthday'] ?? '';
            $address = $_POST['address'] ?? '';
            $contact_number = $_POST['contact_number'] ?? '';
            
            // Validate required fields
            if (empty($username) || empty($password) || empty($firstname) || empty($lastname) || 
                empty($gender) || empty($birthday)) {
                throw new Exception('All required fields must be filled');
            }

            // Handle file uploads
            $imageName = handleFileUpload($_FILES['image'] ?? [], '../staff_image/');
            $validIdName = handleFileUpload($_FILES['valid_id'] ?? [], '../uploads/staff_validID/');
            
            if (!$validIdName) {
                throw new Exception('Valid ID is required');
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO staff (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashedPassword);
            
            if ($stmt->execute()) {
                $staff_id = $conn->insert_id;
                
                $stmt2 = $conn->prepare("INSERT INTO staff_profile 
                    (staff_id, firstname, middlename, lastname, gender, birthday, address, contact_number, image, valid_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssssssss", 
                    $staff_id, $firstname, $middlename, $lastname, $gender, $birthday, $address, $contact_number, $imageName, $validIdName);
                
                if ($stmt2->execute()) {
                    // Handle service assignments if any
                    $services = $_POST['services'] ?? [];
                    if (!empty($services)) {
                        $insert_stmt = $conn->prepare("INSERT IGNORE INTO staff_service (staff_id, service_id) VALUES (?, ?)");
                        foreach ($services as $service_id) {
                            $insert_stmt->bind_param("ii", $staff_id, $service_id);
                            $insert_stmt->execute();
                        }
                    }
                    
                    echo json_encode(['status' => 'success', 'message' => 'Staff added successfully']);
                } else {
                    throw new Exception('Failed to add staff profile: ' . $conn->error);
                }
            } else {
                throw new Exception('Failed to add staff account: ' . $conn->error);
            }
            break;

        case 'edit':
            $id = $_POST['id'] ?? 0;
            $username = $_POST['username'] ?? '';
            $firstname = $_POST['firstname'] ?? '';
            $middlename = $_POST['middlename'] ?? '';
            $lastname = $_POST['lastname'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $birthday = $_POST['birthday'] ?? '';
            $address = $_POST['address'] ?? '';
            $contact_number = $_POST['contact_number'] ?? '';
            $password = $_POST['password'] ?? '';
            $services = isset($_POST['services']) ? (array)$_POST['services'] : [];
            
            if (!$id) {
                throw new Exception('Staff ID is required');
            }

            // Validate required fields
            if (empty($username) || empty($firstname) || empty($lastname) || 
                empty($gender) || empty($birthday)) {
                throw new Exception('All required fields must be filled');
            }

            // Handle file uploads
            $imageName = handleFileUpload($_FILES['image'] ?? [], '../staff_image/');
            $validIdName = handleFileUpload($_FILES['valid_id'] ?? [], '../uploads/staff_validID/');

            // Update staff account
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE staff SET username = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $hashedPassword, $id);
            } else {
                $stmt = $conn->prepare("UPDATE staff SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $username, $id);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update staff account: ' . $conn->error);
            }

            // Update staff profile - build dynamic query based on which files were uploaded
            $queryParts = [];
            $paramTypes = "";
            $paramValues = [];
            
            // Base fields
            $queryParts[] = "firstname = ?";
            $paramTypes .= "s";
            $paramValues[] = $firstname;
            
            $queryParts[] = "middlename = ?";
            $paramTypes .= "s";
            $paramValues[] = $middlename;
            
            $queryParts[] = "lastname = ?";
            $paramTypes .= "s";
            $paramValues[] = $lastname;
            
            $queryParts[] = "gender = ?";
            $paramTypes .= "s";
            $paramValues[] = $gender;
            
            $queryParts[] = "birthday = ?";
            $paramTypes .= "s";
            $paramValues[] = $birthday;
            
            $queryParts[] = "address = ?";
            $paramTypes .= "s";
            $paramValues[] = $address;
            
            $queryParts[] = "contact_number = ?";
            $paramTypes .= "s";
            $paramValues[] = $contact_number;
            
            // Handle image if uploaded
            if ($imageName) {
                $queryParts[] = "image = ?";
                $paramTypes .= "s";
                $paramValues[] = $imageName;
            }
            
            // Handle valid ID if uploaded
            if ($validIdName) {
                $queryParts[] = "valid_id = ?";
                $paramTypes .= "s";
                $paramValues[] = $validIdName;
            }
            
            // Add staff_id to parameters
            $paramTypes .= "i";
            $paramValues[] = $id;
            
            // Build the final query
            $query = "UPDATE staff_profile SET " . implode(", ", $queryParts) . " WHERE staff_id = ?";
            $stmt2 = $conn->prepare($query);
            
            // Bind parameters dynamically
            $stmt2->bind_param($paramTypes, ...$paramValues);
            
            if ($stmt2->execute()) {
                // Handle service assignments - only update if services were provided
                if (isset($_POST['services'])) {
                    // First, remove all existing service assignments
                    $delete_stmt = $conn->prepare("DELETE FROM staff_service WHERE staff_id = ?");
                    $delete_stmt->bind_param("i", $id);
                    $delete_stmt->execute();
                    
                    // Then add the new service assignments if any were selected
                    if (!empty($services)) {
                        $insert_stmt = $conn->prepare("INSERT IGNORE INTO staff_service (staff_id, service_id) VALUES (?, ?)");
                        foreach ($services as $service_id) {
                            $insert_stmt->bind_param("ii", $id, $service_id);
                            $insert_stmt->execute();
                        }
                    }
                }
                
                echo json_encode(['status' => 'success', 'message' => 'Staff updated successfully']);
            } else {
                throw new Exception('Failed to update staff profile: ' . $conn->error);
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception('Staff ID is required');
            }
            
            // First delete service assignments
            $stmt_services = $conn->prepare("DELETE FROM staff_service WHERE staff_id = ?");
            $stmt_services->bind_param('i', $id);
            $stmt_services->execute();
            
            // Then delete profile
            $stmt_profile = $conn->prepare("DELETE FROM staff_profile WHERE staff_id = ?");
            $stmt_profile->bind_param('i', $id);
            $stmt_profile->execute();

            // Finally delete staff account
            $stmt_staff = $conn->prepare("DELETE FROM staff WHERE id = ?");
            $stmt_staff->bind_param('i', $id);

            if ($stmt_staff->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Staff deleted successfully']);
            } else {
                throw new Exception('Failed to delete staff: ' . $conn->error);
            }
            break;

        default:
            throw new Exception('Invalid action specified');
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>