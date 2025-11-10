<?php
session_start();
require_once 'config.php';

class ServicesAPI {
    private $conn;
    private $uploadDir = '../uploads/services_image/';

    public function __construct() {
        $db = new config();
        $this->conn = $db->connectDB();
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    private function validateCSRFToken() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
    }

    private function handleImageUpload($currentImage = '') {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Only JPG, PNG, and GIF images are allowed');
            }
            
            // Validate file size (2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Image size must be less than 2MB');
            }
            
            // Generate unique filename - store ONLY the filename, not the path
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'service_' . uniqid() . '.' . $fileExtension;
            $filePath = $this->uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Delete old image if exists - extract filename if it contains path
                if (!empty($currentImage)) {
                    $oldFileName = basename($currentImage); // Extract just the filename
                    $oldImagePath = $this->uploadDir . $oldFileName;
                    if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                return $fileName; // Return ONLY the filename
            } else {
                throw new Exception('Failed to upload image');
            }
        }
        
        // If no new image uploaded, return current image (extract filename if it contains path)
        if (!empty($currentImage)) {
            return basename($currentImage); // Ensure we only return filename
        }
        
        return $currentImage;
    }

    public function addService() {
        try {
            $this->validateCSRFToken();
            
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');
            $contact_number = trim($_POST['contact_number'] ?? '');
            $location = trim($_POST['location'] ?? '');
            
            if (empty($name)) {
                throw new Exception('Service name is required');
            }
            
            // Handle image upload
            $image = $this->handleImageUpload();
            
            $stmt = $this->conn->prepare("INSERT INTO service (name, description, contact_number, location, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $description, $contact_number, $location, $image);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Service added successfully'];
            } else {
                throw new Exception('Failed to add service to database');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateService() {
        try {
            $this->validateCSRFToken();
            
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');
            $contact_number = trim($_POST['contact_number'] ?? '');
            $location = trim($_POST['location'] ?? '');
            
            if (empty($name)) {
                throw new Exception('Service name is required');
            }
            
            // Get current image first
            $currentImage = '';
            $stmt = $this->conn->prepare("SELECT image FROM service WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($currentImage);
            $stmt->fetch();
            $stmt->close();
            
            // Handle image upload
            $image = $this->handleImageUpload($currentImage);
            
            $stmt = $this->conn->prepare("UPDATE service SET name = ?, description = ?, contact_number = ?, location = ?, image = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssssi", $name, $description, $contact_number, $location, $image, $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Service updated successfully'];
            } else {
                throw new Exception('Failed to update service');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteService() {
        try {
            $this->validateCSRFToken();
            
            $id = $_POST['id'];
            
            // Get service image before deletion
            $stmt = $this->conn->prepare("SELECT image FROM service WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($image);
            $stmt->fetch();
            $stmt->close();
            
            // Delete service offers first
            $stmt = $this->conn->prepare("DELETE FROM service_offer WHERE service_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Delete service
            $stmt = $this->conn->prepare("DELETE FROM service WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Delete image file if exists
                if (!empty($image)) {
                    $imageFilename = basename($image); // Extract filename
                    $imagePath = $this->uploadDir . $imageFilename;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                return ['success' => true, 'message' => 'Service deleted successfully'];
            } else {
                throw new Exception('Failed to delete service');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getService($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM service WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $service = $result->fetch_assoc();
                return ['success' => true, 'data' => $service];
            } else {
                throw new Exception('Service not found');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAllServices($searchTerm = '') {
        try {
            if (!empty($searchTerm)) {
                $query = "SELECT * FROM service WHERE name LIKE ? OR description LIKE ? OR contact_number LIKE ? OR location LIKE ? ORDER BY create_at DESC";
                $stmt = $this->conn->prepare($query);
                $searchPattern = "%$searchTerm%";
                $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $query = "SELECT * FROM service ORDER BY create_at DESC";
                $result = $this->conn->query($query);
            }
            
            $services = [];
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
            
            return ['success' => true, 'data' => $services];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getServiceOffers($serviceId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM service_offer WHERE service_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $offers = [];
            while ($row = $result->fetch_assoc()) {
                $offers[] = $row;
            }
            
            return ['success' => true, 'data' => $offers];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function addServiceOffer() {
        try {
            $this->validateCSRFToken();
            
            $service_id = $_POST['service_id'];
            $offer_name = trim($_POST['offer_name']);
            $price = $_POST['price'] ?? 0;
            
            if (empty($offer_name)) {
                throw new Exception('Offer name is required');
            }
            
            $stmt = $this->conn->prepare("INSERT INTO service_offer (service_id, offer_name, price) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $service_id, $offer_name, $price);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Offer added successfully'];
            } else {
                throw new Exception('Failed to add offer');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateServiceOffer() {
        try {
            $this->validateCSRFToken();
            
            $id = $_POST['id'];
            $offer_name = trim($_POST['offer_name']);
            $price = $_POST['price'] ?? 0;
            
            if (empty($offer_name)) {
                throw new Exception('Offer name is required');
            }
            
            $stmt = $this->conn->prepare("UPDATE service_offer SET offer_name = ?, price = ? WHERE id = ?");
            $stmt->bind_param("sdi", $offer_name, $price, $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Offer updated successfully'];
            } else {
                throw new Exception('Failed to update offer');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteServiceOffer() {
        try {
            $this->validateCSRFToken();
            
            $id = $_POST['id'];
            
            $stmt = $this->conn->prepare("DELETE FROM service_offer WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Offer deleted successfully'];
            } else {
                throw new Exception('Failed to delete offer');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Handle API requests
$api = new ServicesAPI();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'add_service':
            echo json_encode($api->addService());
            break;
        case 'update_service':
            echo json_encode($api->updateService());
            break;
        case 'delete_service':
            echo json_encode($api->deleteService());
            break;
        case 'get_service':
            $id = $_GET['id'];
            echo json_encode($api->getService($id));
            break;
        case 'get_all_services':
            $searchTerm = $_GET['search'] ?? '';
            echo json_encode($api->getAllServices($searchTerm));
            break;
        case 'get_service_offers':
            $service_id = $_GET['service_id'];
            echo json_encode($api->getServiceOffers($service_id));
            break;
        case 'add_service_offer':
            echo json_encode($api->addServiceOffer());
            break;
        case 'update_service_offer':
            echo json_encode($api->updateServiceOffer());
            break;
        case 'delete_service_offer':
            echo json_encode($api->deleteServiceOffer());
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>