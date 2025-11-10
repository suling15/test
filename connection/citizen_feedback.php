<?php
header('Content-Type: application/json');
session_start();

require_once '../connection/config.php';
$db = new config();
$conn = $db->connectDB();

function json_response($ok, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $message, 'data' => $data]);
    exit;
}

if (!isset($_SESSION['user'])) {
    json_response(false, 'Unauthorized', null, 401);
}
$citizenId = (int)$_SESSION['user']['id'];

$action = $_GET['action'] ?? '';

function require_csrf() {
    $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tokenHeader)) {
        json_response(false, 'Invalid CSRF token', null, 403);
    }
}

function rating_ok($v) {
    return is_numeric($v) && (int)$v >= 1 && (int)$v <= 5;
}

function analyzeSentiment($text) {
    // Validate input
    if (empty(trim($text))) {
        return [
            'error' => 'Empty text provided',
            'sentiment' => 'unknown'
        ];
    }
    
    // Configuration
    $api_url = 'http://localhost:5000/analyze';
    $timeout = 60;
    $connect_timeout = 15;
    
    // Prepare data
    $clean_text = trim($text);
    if (strlen($clean_text) > 2000) {
        $clean_text = substr($clean_text, 0, 2000);
    }
    
    $data = json_encode([
        "text" => $clean_text
    ], JSON_UNESCAPED_UNICODE);
    
    if ($data === false) {
        return [
            'error' => 'Failed to encode text to JSON',
            'sentiment' => 'unknown'
        ];
    }
    
    // Initialize cURL
    $ch = curl_init();
    if ($ch === false) {
        return [
            'error' => 'Failed to initialize cURL',
            'sentiment' => 'unknown'
        ];
    }
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
            'Accept: application/json',
            'User-Agent: Feedback-System/1.0'
        ],
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $connect_timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Handle cURL errors
    if ($error) {
        error_log("Sentiment API cURL Error: " . $error . " | URL: " . $api_url);
        return [
            'error' => 'Network error: ' . $error,
            'sentiment' => 'unknown'
        ];
    }
    
    // Handle HTTP errors
    if ($http_code !== 200) {
        error_log("Sentiment API HTTP Error: HTTP {$http_code}, Response: {$response}");
        
        if ($http_code >= 500 && $http_code < 600) {
            return [
                'error' => "Sentiment service temporarily unavailable (HTTP {$http_code})",
                'http_code' => $http_code,
                'sentiment' => 'unknown'
            ];
        }
        
        return [
            'error' => "Service returned HTTP {$http_code}",
            'http_code' => $http_code,
            'sentiment' => 'unknown'
        ];
    }
    
    // Handle empty response
    if (empty($response)) {
        return [
            'error' => 'Empty response from sentiment service',
            'sentiment' => 'unknown'
        ];
    }
    
    // Parse JSON response
    $result = json_decode($response, true);
    if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Sentiment API JSON Error: " . json_last_error_msg() . ", Response: {$response}");
        return [
            'error' => 'Invalid JSON response: ' . json_last_error_msg(),
            'sentiment' => 'unknown'
        ];
    }
    
    // Handle API errors in response
    if (isset($result['error'])) {
        error_log("Sentiment API Error: " . $result['error']);
        return [
            'error' => $result['error'],
            'sentiment' => isset($result['sentiment']) ? $result['sentiment'] : 'unknown'
        ];
    }
    
    // Validate sentiment result
    if (!isset($result['sentiment'])) {
        return [
            'error' => 'No sentiment in response',
            'sentiment' => 'unknown'
        ];
    }
    
    // Normalize sentiment value
    $sentiment = strtolower(trim($result['sentiment']));
    $valid_sentiments = ['positive', 'negative', 'neutral', 'very positive', 'very negative'];
    
    if (!in_array($sentiment, $valid_sentiments)) {
        $sentiment = 'unknown';
    }
    
    // Log successful analysis
    $confidence = isset($result['confidence']) ? $result['confidence'] : 0;
    error_log("Sentiment Analysis Success: '{$sentiment}' with confidence {$confidence} for text length " . strlen($clean_text));
    
    // Return successful result
    return [
        'sentiment' => $sentiment,
        'scores' => isset($result['scores']) ? $result['scores'] : null,
        'confidence' => $confidence,
        'processing_time' => isset($result['processing_time']) ? $result['processing_time'] : null,
        'original_sentiment' => isset($result['original_sentiment']) ? $result['original_sentiment'] : null
    ];
}

function testSentimentAnalysis() {
    $test_texts = [
        "I am very satisfied with the excellent service provided.",
        "The service was average, nothing special.",
        "I am extremely disappointed with the poor quality of service."
    ];
    
    $results = [];
    foreach ($test_texts as $text) {
        $result = analyzeSentiment($text);
        $results[] = [
            'text' => $text,
            'result' => $result
        ];
    }
    
    return $results;
}

try {
    if ($action === 'test_sentiment') {
        $results = testSentimentAnalysis();
        json_response(true, 'Sentiment analysis test completed', $results);
    }
    
    if ($action === 'fetch_services') {
        $res = $conn->query("SELECT id, name FROM service ORDER BY name ASC");
        if (!$res) {
            json_response(false, 'Database error: ' . $conn->error);
        }
        
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        json_response(true, '', $rows);
    }
    
    if ($action === 'fetch_service_offers') {
        $service_id = (int)($_GET['service_id'] ?? 0);
        if ($service_id <= 0) {
            json_response(false, 'Invalid service ID');
        }
        
        $stmt = $conn->prepare("SELECT id, offer_name FROM service_offer WHERE service_id = ? ORDER BY offer_name ASC");
        if (!$stmt) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param('i', $service_id);
        if (!$stmt->execute()) {
            json_response(false, 'Database execute error: ' . $stmt->error);
        }
        
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        json_response(true, '', $rows);
    }

    if ($action === 'fetch_my_feedback') {
        // Get filter parameters
        $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        // Validate month and year
        if ($month < 1 || $month > 12) $month = date('m');
        if ($year < 2020 || $year > date('Y') + 1) $year = date('Y');
        
        $stmt = $conn->prepare("
            SELECT sf.id, s.name AS service_name, so.offer_name, sf.feedback_text, sf.rating, sf.sentiment,
                   sf.CC1, sf.CC2, sf.CC3,
                   sf.SQD0, sf.SQD1, sf.SQD2, sf.SQD3, sf.SQD4, sf.SQD5, sf.SQD6, sf.SQD7, sf.SQD8,
                   sf.create, sf.is_anonymous,
                   fr.id AS response_id,
                   CONCAT(sp.firstname, ' ', sp.lastname) AS staff_name
            FROM service_feedback sf
            INNER JOIN service s ON s.id = sf.service_id
            INNER JOIN service_offer so ON so.id = sf.service_offer_id
            LEFT JOIN feedback_response fr ON fr.feedback_id = sf.id
            LEFT JOIN staff st ON st.id = fr.staff_id
            LEFT JOIN staff_profile sp ON sp.staff_id = st.id
            WHERE sf.citizen_id = ? 
            AND YEAR(sf.create) = ? 
            AND MONTH(sf.create) = ?
            ORDER BY sf.create DESC
        ");
        
        if (!$stmt) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param('iii', $citizenId, $year, $month);
        if (!$stmt->execute()) {
            json_response(false, 'Database execute error: ' . $stmt->error);
        }
        
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            // Add a flag to indicate if feedback has a response
            $r['has_response'] = !empty($r['response_id']);
            $rows[] = $r;
        }
        json_response(true, '', $rows);
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(false, 'Invalid feedback ID');
        }
        
        $stmt = $conn->prepare("
            SELECT sf.id, sf.service_id, sf.service_offer_id, s.name AS service_name, so.offer_name, 
                   sf.feedback_text, sf.rating, sf.sentiment, sf.is_anonymous,
                   sf.CC1, sf.CC2, sf.CC3,
                   sf.SQD0, sf.SQD1, sf.SQD2, sf.SQD3, sf.SQD4, sf.SQD5, sf.SQD6, sf.SQD7, sf.SQD8
            FROM service_feedback sf
            INNER JOIN service s ON s.id = sf.service_id
            INNER JOIN service_offer so ON so.id = sf.service_offer_id
            WHERE sf.id = ? AND sf.citizen_id = ?
        ");
        
        if (!$stmt) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param('ii', $id, $citizenId);
        if (!$stmt->execute()) {
            json_response(false, 'Database execute error: ' . $stmt->error);
        }
        
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            json_response(false, 'Feedback not found', null, 404);
        }
        json_response(true, '', $row);
    }

    if ($action === 'get_response') {
        $feedback_id = (int)($_GET['feedback_id'] ?? 0);
        if ($feedback_id <= 0) {
            json_response(false, 'Invalid feedback ID');
        }
        
        // Check if the feedback belongs to the citizen
        $own = $conn->prepare("SELECT id FROM service_feedback WHERE id = ? AND citizen_id = ?");
        if (!$own) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $own->bind_param('ii', $feedback_id, $citizenId);
        if (!$own->execute()) {
            json_response(false, 'Database execute error: ' . $own->error);
        }
        
        if (!$own->get_result()->fetch_assoc()) {
            json_response(false, 'Feedback not found or access denied', null, 404);
        }
        
        // Get the response
        $stmt = $conn->prepare("
            SELECT fr.response_text, fr.created_at, 
                   CONCAT(sp.firstname, ' ', sp.lastname) AS staff_name
            FROM feedback_response fr
            INNER JOIN staff st ON st.id = fr.staff_id
            LEFT JOIN staff_profile sp ON sp.staff_id = st.id
            WHERE fr.feedback_id = ?
        ");
        
        if (!$stmt) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param('i', $feedback_id);
        if (!$stmt->execute()) {
            json_response(false, 'Database execute error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $response = $result->fetch_assoc();
        
        if (!$response) {
            json_response(false, 'No response found for this feedback', null, 404);
        }
        
        json_response(true, '', $response);
    }

    if ($action === 'add') {
        require_csrf();
        
        // Get form data
        $service_id   = (int)($_POST['service_id'] ?? 0);
        $service_offer_id = (int)($_POST['service_offer_id'] ?? 0);
        $feedback_txt = trim($_POST['feedback_text'] ?? '');
        $rating       = (int)($_POST['rating'] ?? 0);
        $is_anonymous = isset($_POST['is_anonymous']) ? (int)$_POST['is_anonymous'] : 0;

        // CC Questions
        $CC1 = isset($_POST['CC1']) ? trim($_POST['CC1']) : null;
        $CC2 = isset($_POST['CC2']) ? trim($_POST['CC2']) : null;
        $CC3 = isset($_POST['CC3']) ? trim($_POST['CC3']) : null;

        // SQD Ratings
        $SQD = [];
        for ($i=0; $i<=8; $i++) {
            $SQD[$i] = isset($_POST["SQD$i"]) && $_POST["SQD$i"] !== '' ? (int)$_POST["SQD$i"] : null;
        }

        // Validation
        if ($service_id <= 0) {
            json_response(false, 'Service is required');
        }
        if ($service_offer_id <= 0) {
            json_response(false, 'Service offer is required');
        }
        if ($feedback_txt === '') {
            json_response(false, 'Feedback is required');
        }
        if (!rating_ok($rating)) {
            json_response(false, 'Rating must be 1–5');
        }

        // Verify service and service offer relationship
        $chk = $conn->prepare("SELECT so.id FROM service_offer so WHERE so.id = ? AND so.service_id = ?");
        if (!$chk) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $chk->bind_param('ii', $service_offer_id, $service_id);
        if (!$chk->execute()) {
            json_response(false, 'Database execute error: ' . $chk->error);
        }
        
        if (!$chk->get_result()->fetch_assoc()) {
            json_response(false, 'Invalid service offer');
        }

        // Analyze sentiment using the API
        $sentiment_result = analyzeSentiment($feedback_txt);
        $sentiment = isset($sentiment_result['sentiment']) ? $sentiment_result['sentiment'] : 'unknown';
        $sentiment_scores = isset($sentiment_result['scores']) ? json_encode($sentiment_result['scores']) : null;
        
        // Log sentiment analysis result
        if (isset($sentiment_result['error'])) {
            error_log("Sentiment analysis warning for feedback: " . $sentiment_result['error']);
        }

        // Insert feedback into database
        $stmt = $conn->prepare("
            INSERT INTO service_feedback 
            (citizen_id, service_id, service_offer_id, feedback_text, rating, sentiment, sentiment_scores,
             CC1, CC2, CC3, SQD0, SQD1, SQD2, SQD3, SQD4, SQD5, SQD6, SQD7, SQD8, is_anonymous)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        
        if (!$stmt) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $bind_result = $stmt->bind_param(
            'iiisisssssiiiiiiiiii',
            $citizenId,
            $service_id,
            $service_offer_id,
            $feedback_txt,
            $rating,
            $sentiment,
            $sentiment_scores,
            $CC1,
            $CC2,
            $CC3,
            $SQD[0], $SQD[1], $SQD[2], $SQD[3], $SQD[4], $SQD[5], $SQD[6], $SQD[7], $SQD[8],
            $is_anonymous
        );
        
        if (!$bind_result) {
            json_response(false, 'Database bind error: ' . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            error_log("Insert failed: " . $stmt->error);
            json_response(false, 'Failed to save feedback: ' . $stmt->error);
        }

        $feedback_id = $conn->insert_id;
        
        // Log successful feedback submission
        error_log("Feedback submitted successfully: ID {$feedback_id}, Citizen {$citizenId}, Sentiment: {$sentiment}, Anonymous: {$is_anonymous}");
        
        json_response(true, 'Feedback submitted successfully', ['feedback_id' => $feedback_id]);
    }

    if ($action === 'delete') {
        require_csrf();
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response(false, 'Invalid feedback ID');
        }

        // Ownership check
        $own = $conn->prepare("SELECT id FROM service_feedback WHERE id = ? AND citizen_id = ?");
        if (!$own) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $own->bind_param('ii', $id, $citizenId);
        if (!$own->execute()) {
            json_response(false, 'Database execute error: ' . $own->error);
        }
        
        if (!$own->get_result()->fetch_assoc()) {
            json_response(false, 'Feedback not found or access denied', null, 404);
        }

        // Delete the feedback
        $stmt = $conn->prepare("DELETE FROM service_feedback WHERE id = ?");
        if (!$stmt) {
            json_response(false, 'Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            json_response(false, 'Failed to delete feedback: ' . $stmt->error);
        }

        error_log("Feedback deleted: ID {$id}, Citizen {$citizenId}");
        json_response(true, 'Feedback deleted successfully');
    }

    // Default response for unknown actions
    json_response(false, 'Unknown action: ' . $action, null, 400);

} catch (Throwable $e) {
    error_log("Error in citizen_feedback.php: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    json_response(false, 'Server error occurred', null, 500);
}
?>