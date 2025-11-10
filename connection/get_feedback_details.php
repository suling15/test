<?php
session_start();
header('Content-Type: application/json');

// Check authorization - allow both admin and staff
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'staff')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';
$db = new config();
$conn = $db->connectDB();

// Handle different request types
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Case 1: Get individual feedback details by ID
    if (isset($_GET['id'])) {
        if (!is_numeric($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
            exit;
        }

        $feedbackId = (int)$_GET['id'];
        $staffId = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : null;

        // Base SQL query
        $sql = "
            SELECT sf.id, sf.feedback_text, so.offer_name, sf.rating, sf.create, sf.sentiment, sf.is_anonymous,
                   sf.CC1, sf.CC2, sf.CC3, sf.SQD0, sf.SQD1, sf.SQD2, sf.SQD3, sf.SQD4, sf.SQD5, sf.SQD6, sf.SQD7, sf.SQD8,
                   CONCAT(COALESCE(p.firstname, ''), ' ', COALESCE(p.middlename, ''), ' ', COALESCE(p.lastname, '')) AS citizen_fullname,
                   c.username AS citizen_username,
                   s.name AS service_name,
                   p.image AS profile_image,
                   fr.response_text,
                   fr.created_at as response_date,
                   CONCAT(COALESCE(sp.firstname, ''), ' ', COALESCE(sp.middlename, ''), ' ', COALESCE(sp.lastname, '')) as responder_name
            FROM service_feedback sf
            JOIN citizen c ON sf.citizen_id = c.id
            JOIN service s ON sf.service_id = s.id
            LEFT JOIN service_offer so ON sf.service_offer_id = so.id
            LEFT JOIN profile p ON p.citizen_id = c.id
            LEFT JOIN feedback_response fr ON sf.id = fr.feedback_id
            LEFT JOIN staff st ON fr.staff_id = st.id
            LEFT JOIN staff_profile sp ON st.id = sp.staff_id
            WHERE sf.id = ?
        ";

        // If staff ID is provided, only show feedback for services assigned to this staff
        if ($staffId) {
            $sql .= " AND sf.service_id IN (SELECT service_id FROM staff_service WHERE staff_id = ?)";
        }

        $stmt = $conn->prepare($sql);
        
        if ($staffId) {
            $stmt->bind_param('ii', $feedbackId, $staffId);
        } else {
            $stmt->bind_param('i', $feedbackId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Feedback not found or you are not authorized to view this feedback']);
            exit;
        }

        $feedback = $result->fetch_assoc();
        
        // Clean up the fullname
        if (isset($feedback['citizen_fullname'])) {
            $feedback['citizen_fullname'] = preg_replace('/\s+/', ' ', trim($feedback['citizen_fullname']));
        }
        
        echo json_encode(['success' => true, 'data' => $feedback]);
        
    } 
    // Case 2: Get all feedbacks for DataTable (with month/year filter and staff assignment)
    elseif (isset($_GET['month']) && isset($_GET['year'])) {
        
        $selectedMonth = intval($_GET['month']);
        $selectedYear = intval($_GET['year']);
        $staffId = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : null;

        // Validate month and year
        $currentMonth = date('m');
        $currentYear = date('Y');
        if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = $currentMonth;
        if ($selectedYear < 2020 || $selectedYear > $currentYear + 1) $selectedYear = $currentYear;

        // Base SQL query
        $sql = "
            SELECT sf.id, sf.feedback_text, so.offer_name, sf.rating, sf.create, sf.sentiment, sf.is_anonymous,
                sf.CC1, sf.CC2, sf.CC3, sf.SQD0, sf.SQD1, sf.SQD2, sf.SQD3, sf.SQD4, sf.SQD5, sf.SQD6, sf.SQD7, sf.SQD8,
                CONCAT(COALESCE(p.firstname, ''), ' ', COALESCE(p.middlename, ''), ' ', COALESCE(p.lastname, '')) AS citizen_fullname,
                c.username AS citizen_username,
                s.name AS service_name,
                p.image AS profile_image,
                fr.response_text,
                fr.created_at as response_date,
                CONCAT(COALESCE(sp.firstname, ''), ' ', COALESCE(sp.middlename, ''), ' ', COALESCE(sp.lastname, '')) as responder_name
            FROM service_feedback sf
            JOIN citizen c ON sf.citizen_id = c.id
            JOIN service s ON sf.service_id = s.id
            LEFT JOIN service_offer so ON sf.service_offer_id = so.id
            LEFT JOIN profile p ON p.citizen_id = c.id
            LEFT JOIN feedback_response fr ON sf.id = fr.feedback_id
            LEFT JOIN staff st ON fr.staff_id = st.id
            LEFT JOIN staff_profile sp ON st.id = sp.staff_id
            WHERE MONTH(sf.create) = ? AND YEAR(sf.create) = ?
        ";

        // If staff ID is provided, only show feedback for services assigned to this staff
        if ($staffId) {
            $sql .= " AND sf.service_id IN (SELECT service_id FROM staff_service WHERE staff_id = ?)";
        }

        $sql .= " ORDER BY sf.create DESC";

        $stmt = $conn->prepare($sql);
        
        if ($staffId) {
            $stmt->bind_param("iii", $selectedMonth, $selectedYear, $staffId);
        } else {
            $stmt->bind_param("ii", $selectedMonth, $selectedYear);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        $feedbacks = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure all fields are properly set
            $feedbacks[] = [
                'id' => $row['id'],
                'feedback_text' => $row['feedback_text'] ?? '',
                'offer_name' => $row['offer_name'] ?? null,
                'rating' => $row['rating'],
                'create' => $row['create'],
                'sentiment' => $row['sentiment'] ?? '',
                'is_anonymous' => $row['is_anonymous'],
                'CC1' => $row['CC1'] ?? null,
                'CC2' => $row['CC2'] ?? null,
                'CC3' => $row['CC3'] ?? null,
                'SQD0' => $row['SQD0'] ?? null,
                'SQD1' => $row['SQD1'] ?? null,
                'SQD2' => $row['SQD2'] ?? null,
                'SQD3' => $row['SQD3'] ?? null,
                'SQD4' => $row['SQD4'] ?? null,
                'SQD5' => $row['SQD5'] ?? null,
                'SQD6' => $row['SQD6'] ?? null,
                'SQD7' => $row['SQD7'] ?? null,
                'SQD8' => $row['SQD8'] ?? null,
                'citizen_fullname' => $row['citizen_fullname'] ?? '',
                'citizen_username' => $row['citizen_username'],
                'service_name' => $row['service_name'],
                'profile_image' => $row['profile_image'] ?? null,
                'response_text' => $row['response_text'] ?? null,
                'response_date' => $row['response_date'] ?? null,
                'responder_name' => $row['responder_name'] ?? null
            ];
        }

        echo json_encode($feedbacks);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>