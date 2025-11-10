<?php
session_start();
require_once '../connection/config.php';

header('Content-Type: application/json');

// Fix: Check for request_type instead of just service_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_type'])) {
    $db = new config();
    $conn = $db->connectDB();
    
    // Verify CSRF token for state-changing operations
    $requestType = $_POST['request_type'] ?? 'details';
    
    if (in_array($requestType, ['add_offer', 'delete_offer', 'edit_offer', 'edit_service'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ]);
            exit;
        }
    }
    
    $staffId = $_SESSION['user']['id'] ?? 0;
    
    switch ($requestType) {
        case 'offers':
            // Get service offers only - service_id is required here
            if (!isset($_POST['service_id'])) {
                echo json_encode(['success' => false, 'message' => 'Service ID is required']);
                exit;
            }
            
            $serviceId = intval($_POST['service_id']);
            $stmt = $conn->prepare("SELECT * FROM service_offer WHERE service_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();
            $offers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'success' => true,
                'offers' => $offers
            ]);
            break;
            
        case 'add_offer':
            // Add new offer (only for assigned staff) - service_id is required here
            if (!isset($_POST['service_id']) || !isset($_POST['offer_name'])) {
                echo json_encode(['success' => false, 'message' => 'Service ID and offer name are required']);
                exit;
            }
            
            $serviceId = intval($_POST['service_id']);
            $offerName = trim($_POST['offer_name']);
            $price = floatval($_POST['price'] ?? 0);
            
            // Verify staff is assigned to this service
            $stmt = $conn->prepare("SELECT id FROM staff_service WHERE service_id = ? AND staff_id = ?");
            $stmt->bind_param("ii", $serviceId, $staffId);
            $stmt->execute();
            
            if (!$stmt->get_result()->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'You are not assigned to this service']);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO service_offer (service_id, offer_name, price) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $serviceId, $offerName, $price);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Offer added successfully',
                    'offer_id' => $stmt->insert_id
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add offer: ' . $conn->error
                ]);
            }
            break;
            
        case 'edit_offer':
            // Edit existing offer (only for assigned staff) - service_id is required here
            if (!isset($_POST['offer_id']) || !isset($_POST['offer_name']) || !isset($_POST['service_id'])) {
                echo json_encode(['success' => false, 'message' => 'Offer ID, service ID and name are required']);
                exit;
            }
            
            $offerId = intval($_POST['offer_id']);
            $serviceId = intval($_POST['service_id']);
            $offerName = trim($_POST['offer_name']);
            $price = floatval($_POST['price'] ?? 0);
            
            // Verify staff is assigned to the service that contains this offer
            $stmt = $conn->prepare("SELECT so.id 
                                   FROM service_offer so 
                                   JOIN staff_service ss ON so.service_id = ss.service_id 
                                   WHERE so.id = ? AND ss.staff_id = ?");
            $stmt->bind_param("ii", $offerId, $staffId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if (!$result->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'You cannot edit this offer']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE service_offer SET offer_name = ?, price = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sdi", $offerName, $price, $offerId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Offer updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update offer: ' . $conn->error
                ]);
            }
            break;
            
        case 'delete_offer':
            // Delete offer (only for assigned staff) - service_id is required here
            if (!isset($_POST['offer_id']) || !isset($_POST['service_id'])) {
                echo json_encode(['success' => false, 'message' => 'Offer ID and Service ID are required']);
                exit;
            }
            
            $offerId = intval($_POST['offer_id']);
            $serviceId = intval($_POST['service_id']);
            
            // Verify staff is assigned to the service that contains this offer
            $stmt = $conn->prepare("SELECT so.id FROM service_offer so 
                                   JOIN staff_service ss ON so.service_id = ss.service_id 
                                   WHERE so.id = ? AND ss.staff_id = ? AND ss.service_id = ?");
            $stmt->bind_param("iii", $offerId, $staffId, $serviceId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if (!$result->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete this offer or offer not found']);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM service_offer WHERE id = ?");
            $stmt->bind_param("i", $offerId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Offer deleted successfully',
                    'deleted_id' => $offerId
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to delete offer: ' . $conn->error
                ]);
            }
            break;
            
        case 'edit_service':
            // Edit service (only for assigned staff) - service_id is required here
            if (!isset($_POST['service_id']) || !isset($_POST['name']) || !isset($_POST['description'])) {
                echo json_encode(['success' => false, 'message' => 'Service ID, name and description are required']);
                exit;
            }
            
            $serviceId = intval($_POST['service_id']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $contact_number = $_POST['contact_number'] ?? null;
            $location = $_POST['location'] ?? null;
            
            // Verify staff is assigned to this service
            $stmt = $conn->prepare("SELECT id FROM staff_service WHERE service_id = ? AND staff_id = ?");
            $stmt->bind_param("ii", $serviceId, $staffId);
            $stmt->execute();
            
            if (!$stmt->get_result()->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'You are not assigned to this service']);
                exit;
            }
            
            // Handle file upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/services_image/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $fileName;
                
                // Validate file type
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (in_array($fileExtension, $allowedTypes)) {
                    // Validate file size (2MB limit)
                    if ($_FILES['image']['size'] <= 2 * 1024 * 1024) {
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                            $imagePath = $fileName;
                            
                            // Delete old image if exists
                            $stmt = $conn->prepare("SELECT image FROM service WHERE id = ?");
                            $stmt->bind_param("i", $serviceId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc() && !empty($row['image'])) {
                                $oldImagePath = $uploadDir . basename($row['image']);
                                if (file_exists($oldImagePath)) {
                                    unlink($oldImagePath);
                                }
                            }
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Image size must be less than 2MB']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']);
                    exit;
                }
            }
            
            if ($imagePath) {
                $stmt = $conn->prepare("UPDATE service SET name = ?, description = ?, contact_number = ?, location = ?, image = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $description, $contact_number, $location, $imagePath, $serviceId);
            } else {
                $stmt = $conn->prepare("UPDATE service SET name = ?, description = ?, contact_number = ?, location = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $description, $contact_number, $location, $serviceId);
            }
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Service updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update service: ' . $conn->error
                ]);
            }
            break;
            
        default:
            // Get service details with offers - service_id is required here
            if (!isset($_POST['service_id'])) {
                echo json_encode(['success' => false, 'message' => 'Service ID is required']);
                exit;
            }
            
            $serviceId = intval($_POST['service_id']);
            $stmt = $conn->prepare("SELECT s.*, 
                                   CASE WHEN ss.staff_id IS NOT NULL THEN 1 ELSE 0 END as is_assigned,
                                   ss.assigned_at
                            FROM service s 
                            LEFT JOIN staff_service ss ON s.id = ss.service_id AND ss.staff_id = ?
                            WHERE s.id = ?");
            $stmt->bind_param("ii", $staffId, $serviceId);
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
            
            if ($service) {
                // Get service offers
                $stmt = $conn->prepare("SELECT * FROM service_offer WHERE service_id = ? ORDER BY created_at DESC");
                $stmt->bind_param("i", $serviceId);
                $stmt->execute();
                $offers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'service' => $service,
                    'offers' => $offers,
                    'is_assigned' => $service['is_assigned'] == 1,
                    'assigned_at' => $service['assigned_at']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Service not found'
                ]);
            }
            break;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>