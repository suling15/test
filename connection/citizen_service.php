<?php
session_start();
require_once 'config.php';
$db = new config();
$conn = $db->connectDB();

// Set headers first to prevent any output
header('Content-Type: application/json');

// Disable error display (enable for debugging if needed)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$response = ['success' => false, 'message' => ''];

try {
    // Verify CSRF token for GET requests
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }

    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_service':
            $id = $_GET['id'] ?? 0;
            if (!is_numeric($id)) {
                throw new Exception('Invalid service ID');
            }
            
            // Get service details
            $stmt = $conn->prepare("SELECT * FROM service WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $service = $result->fetch_assoc();
                
                // Format date
                $service['create_at'] = date('Y-m-d H:i:s', strtotime($service['create_at']));
                
                // Handle image path
                if (!empty($service['image'])) {
                    $service['image'] = '../uploads/services_image/' . pathinfo($service['image'], PATHINFO_BASENAME);
                }
                
                // Get service offers
                $offerStmt = $conn->prepare("SELECT * FROM service_offer WHERE service_id = ? ORDER BY created_at DESC");
                $offerStmt->bind_param("i", $id);
                $offerStmt->execute();
                $offers = $offerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                $response = [
                    'success' => true, 
                    'data' => $service,
                    'offers' => $offers,
                    'message' => 'Service retrieved successfully'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Service not found'];
            }
            break;

        case 'get_offers':
            $id = $_GET['id'] ?? 0;
            if (!is_numeric($id)) {
                throw new Exception('Invalid service ID');
            }
            
            // Get service offers only
            $stmt = $conn->prepare("SELECT so.*, s.name as service_name 
                                   FROM service_offer so 
                                   JOIN service s ON so.service_id = s.id 
                                   WHERE so.service_id = ? 
                                   ORDER BY so.created_at DESC");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $offers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (count($offers) > 0) {
                $response = [
                    'success' => true, 
                    'offers' => $offers,
                    'message' => 'Offers retrieved successfully'
                ];
            } else {
                $response = [
                    'success' => true, 
                    'offers' => [],
                    'message' => 'No offers found for this service'
                ];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    error_log("Service API Error: " . $e->getMessage());
}

// Ensure clean JSON output
echo json_encode($response);
exit;
?>