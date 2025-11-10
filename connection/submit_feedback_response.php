<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once 'config.php';
$db = new config();
$conn = $db->connectDB();

// Define auto-response templates in code (no database table needed)
$autoResponseTemplates = [
    // Sentiment-based templates
    'sentiment_positive' => [
        'trigger' => ['positive', 'happy', 'good', 'satisfied', 'excellent', 'great', 'wonderful', 'amazing', 'fantastic'],
        'response' => "Thank you for your positive feedback! We're delighted to hear that you had a great experience with our {service_name} service. Your satisfaction is our top priority, and we look forward to serving you again."
    ],
    'sentiment_negative' => [
        'trigger' => ['negative', 'angry', 'bad', 'poor', 'disappointed', 'frustrated', 'terrible', 'awful', 'horrible', 'upset'],
        'response' => "We sincerely apologize for the unsatisfactory experience you've had with our {service_name} service. Please know that we take your feedback seriously and will use it to improve our services. If you'd like to discuss this further, please contact us directly."
    ],
    'sentiment_neutral' => [
        'trigger' => ['neutral', 'average', 'okay', 'fine', 'decent', 'acceptable'],
        'response' => "Thank you for your feedback regarding our {service_name} service. We appreciate you taking the time to share your experience and will consider your comments as we work to improve our services."
    ],
    
    // Rating-based templates
    'rating_5' => [
        'trigger' => 5,
        'response' => "Thank you for the 5-star rating! We're thrilled that you're happy with our {service_name} service. Our team works hard to provide excellent service, and we're glad it shows. We hope to serve you again soon!"
    ],
    'rating_4' => [
        'trigger' => 4,
        'response' => "Thank you for the 4-star rating and your positive feedback on our {service_name} service! We're pleased you had a good experience and will continue working to earn that 5th star in the future."
    ],
    'rating_3' => [
        'trigger' => 3,
        'response' => "Thank you for your feedback and the 3-star rating for our {service_name} service. We appreciate your input and will use it to identify areas where we can improve your experience."
    ],
    'rating_low' => [
        'trigger' => [1, 2],
        'response' => "We're sorry to see your low rating for our {service_name} service. We take all feedback seriously and would appreciate the opportunity to understand how we can better serve you. Please feel free to contact us directly to discuss your experience."
    ],
    
    // Keyword-based templates
    'keyword_wait_time' => [
        'trigger' => ['wait', 'time', 'long', 'slow', 'delay', 'waiting', 'queue', 'line'],
        'response' => "Thank you for your feedback about wait times for our {service_name} service. We apologize for any delay you experienced and are continuously working to streamline our processes to serve you more efficiently."
    ],
    'keyword_staff' => [
        'trigger' => ['staff', 'employee', 'personnel', 'officer', 'assistant', 'worker', 'person'],
        'response' => "Thank you for your comments about our staff regarding the {service_name} service. We strive to provide courteous and professional service, and we appreciate you sharing your experience with us."
    ],
    'keyword_helpful' => [
        'trigger' => ['helpful', 'friendly', 'professional', 'courteous', 'knowledgeable', 'polite', 'kind'],
        'response' => "We're glad to hear that you found our staff helpful during your {service_name} experience! We'll be sure to share your positive comments with our team. Thank you for recognizing their efforts."
    ],
    'keyword_clean' => [
        'trigger' => ['clean', 'cleanliness', 'dirty', 'messy', 'tidy', 'organized'],
        'response' => "Thank you for your feedback about the cleanliness of our facility for the {service_name} service. We maintain high standards of cleanliness and appreciate you bringing this to our attention."
    ]
];

function generateAutoResponse($feedbackData, $templates) {
    $feedbackText = strtolower($feedbackData['feedback_text'] ?? '');
    $sentiment = strtolower($feedbackData['sentiment'] ?? '');
    $rating = intval($feedbackData['rating'] ?? 0);
    $serviceName = $feedbackData['service_name'] ?? 'our service';
    
    // Check sentiment-based templates first
    foreach ($templates as $templateKey => $template) {
        if (strpos($templateKey, 'sentiment_') === 0) {
            foreach ($template['trigger'] as $triggerWord) {
                if (strpos($sentiment, $triggerWord) !== false) {
                    return processTemplate($template['response'], $serviceName, $feedbackData);
                }
            }
        }
    }
    
    // Check rating-based templates
    foreach ($templates as $templateKey => $template) {
        if (strpos($templateKey, 'rating_') === 0) {
            if ($templateKey === 'rating_low' && in_array($rating, $template['trigger'])) {
                return processTemplate($template['response'], $serviceName, $feedbackData);
            } elseif ($template['trigger'] === $rating) {
                return processTemplate($template['response'], $serviceName, $feedbackData);
            }
        }
    }
    
    // Check keyword-based templates
    foreach ($templates as $templateKey => $template) {
        if (strpos($templateKey, 'keyword_') === 0) {
            foreach ($template['trigger'] as $keyword) {
                if (strpos($feedbackText, $keyword) !== false) {
                    return processTemplate($template['response'], $serviceName, $feedbackData);
                }
            }
        }
    }
    
    // Default template if no specific match
    if ($rating >= 4) {
        return processTemplate($templates['rating_4']['response'], $serviceName, $feedbackData);
    } elseif ($rating <= 2) {
        return processTemplate($templates['rating_low']['response'], $serviceName, $feedbackData);
    } else {
        return processTemplate($templates['sentiment_neutral']['response'], $serviceName, $feedbackData);
    }
}

function processTemplate($template, $serviceName, $feedbackData) {
    $replacements = [
        '{service_name}' => $serviceName,
        '{rating}' => $feedbackData['rating'] ?? '',
        '{citizen_name}' => !empty($feedbackData['citizen_fullname']) ? 
            $feedbackData['citizen_fullname'] : 
            (!empty($feedbackData['citizen_username']) ? $feedbackData['citizen_username'] : 'Valued Citizen')
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

function getFeedbackDetails($conn, $feedbackId, $staffId) {
    $stmt = $conn->prepare("
        SELECT sf.*, s.name as service_name, so.offer_name,
               c.username as citizen_username,
               CONCAT(COALESCE(p.firstname, ''), ' ', COALESCE(p.middlename, ''), ' ', COALESCE(p.lastname, '')) AS citizen_fullname
        FROM service_feedback sf
        JOIN service s ON sf.service_id = s.id
        LEFT JOIN service_offer so ON sf.service_offer_id = so.id
        JOIN citizen c ON sf.citizen_id = c.id
        LEFT JOIN profile p ON p.citizen_id = c.id
        WHERE sf.id = ? AND sf.service_id IN (SELECT service_id FROM staff_service WHERE staff_id = ?)
    ");
    $stmt->bind_param("ii", $feedbackId, $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function checkStaffAuthorization($conn, $feedbackId, $staffId) {
    $checkStmt = $conn->prepare("
        SELECT sf.id 
        FROM service_feedback sf
        JOIN staff_service ss ON sf.service_id = ss.service_id
        WHERE sf.id = ? AND ss.staff_id = ?
    ");
    $checkStmt->bind_param("ii", $feedbackId, $staffId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $isAuthorized = $checkResult->num_rows > 0;
    $checkStmt->close();
    
    return $isAuthorized;
}

function saveResponse($conn, $feedbackId, $staffId, $responseText, $isAutoResponse = false) {
    // Check if response already exists
    $existingStmt = $conn->prepare("SELECT id FROM feedback_response WHERE feedback_id = ?");
    $existingStmt->bind_param("i", $feedbackId);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    
    if ($existingResult->num_rows > 0) {
        // Update existing response
        $updateStmt = $conn->prepare("
            UPDATE feedback_response 
            SET response_text = ?, staff_id = ?, updated_at = NOW() 
            WHERE feedback_id = ?
        ");
        $updateStmt->bind_param("sii", $responseText, $staffId, $feedbackId);
        $result = $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new response
        $insertStmt = $conn->prepare("
            INSERT INTO feedback_response (feedback_id, staff_id, response_text, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $insertStmt->bind_param("iis", $feedbackId, $staffId, $responseText);
        $result = $insertStmt->execute();
        $insertStmt->close();
    }
    
    $existingStmt->close();
    return $result;
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'submit_manual';
    $feedbackId = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;
    $staffId = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;

    // Validate IDs
    if ($feedbackId <= 0 || $staffId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid feedback or staff ID']);
        exit;
    }

    // Check staff authorization for all actions
    if (!checkStaffAuthorization($conn, $feedbackId, $staffId)) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to respond to this feedback']);
        exit;
    }

    if ($action === 'submit_manual') {
        // Manual response submission
        $responseText = isset($_POST['response_text']) ? trim($_POST['response_text']) : '';

        if (empty($responseText)) {
            echo json_encode(['success' => false, 'message' => 'Response text cannot be empty']);
            exit;
        }

        try {
            if (saveResponse($conn, $feedbackId, $staffId, $responseText)) {
                echo json_encode(['success' => true, 'message' => 'Response submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit response']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        
    } elseif ($action === 'generate_auto_response') {
        // Generate auto-response without saving
        $feedbackData = getFeedbackDetails($conn, $feedbackId, $staffId);
        
        if (!$feedbackData) {
            echo json_encode(['success' => false, 'message' => 'Feedback not found']);
            exit;
        }
        
        $autoResponse = generateAutoResponse($feedbackData, $autoResponseTemplates);
        
        if ($autoResponse) {
            echo json_encode([
                'success' => true,
                'auto_response' => $autoResponse,
                'message' => 'Auto-response generated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No matching template found for this feedback'
            ]);
        }
        
    } elseif ($action === 'apply_auto_response') {
        // Generate and apply auto-response
        $feedbackData = getFeedbackDetails($conn, $feedbackId, $staffId);
        
        if (!$feedbackData) {
            echo json_encode(['success' => false, 'message' => 'Feedback not found']);
            exit;
        }
        
        $autoResponse = generateAutoResponse($feedbackData, $autoResponseTemplates);
        
        if ($autoResponse) {
            try {
                if (saveResponse($conn, $feedbackId, $staffId, $autoResponse)) {
                    echo json_encode([
                        'success' => true,
                        'auto_response' => $autoResponse,
                        'message' => 'Auto-response applied successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save auto-response']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No matching template found for this feedback']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>