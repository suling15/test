<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

require_once '../connection/config.php';

try {
    $db = new config();
    $conn = $db->connectDB();

    // Get date from POST request, default to today
    $date = $_POST['date'] ?? date('Y-m-d');
    
    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        throw new Exception('Invalid date format');
    }

    $query = "
        SELECT 
            l.*,
            COALESCE(c.username, s.username) as username,
            CASE 
                WHEN l.user_type = 'citizen' THEN c.username 
                WHEN l.user_type = 'staff' THEN s.username 
            END as user_name
        FROM logs l
        LEFT JOIN citizen c ON l.citizen_id = c.id
        LEFT JOIN staff s ON l.staff_id = s.id
        WHERE DATE(l.login_time) = ?
        ORDER BY l.login_time DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    if ($result) {
        $logs = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>