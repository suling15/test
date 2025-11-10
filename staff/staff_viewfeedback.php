<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}
$user = $_SESSION['user'];

// DB connection
require_once '../connection/config.php';
$db = new config();
$conn = $db->connectDB();

// Get staff profile image
$profile = [];
$profileStmt = $conn->prepare("SELECT image FROM staff_profile WHERE staff_id = ?");
$profileStmt->bind_param("i", $user['id']);
$profileStmt->execute();
$profileResult = $profileStmt->get_result();
if ($profileResult->num_rows > 0) {
    $profile = $profileResult->fetch_assoc();
}
$profileStmt->close();

// Profile image
$userImage = !empty($profile['image']) ? '../staff_image/' . $profile['image'] : '../staff_image/default.png';

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Handle month/year filter from GET parameters
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : $currentMonth;
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;

// Validate month and year
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = $currentMonth;
if ($selectedYear < 2020 || $selectedYear > $currentYear + 1) $selectedYear = $currentYear;

// Get staff ID from session
$staffId = $user['id'];

// Get services assigned to this staff member
$assignedServices = [];
$serviceStmt = $conn->prepare("
    SELECT service_id 
    FROM staff_service 
    WHERE staff_id = ?
");
$serviceStmt->bind_param("i", $staffId);
$serviceStmt->execute();
$serviceResult = $serviceStmt->get_result();

while ($row = $serviceResult->fetch_assoc()) {
    $assignedServices[] = $row['service_id'];
}
$serviceStmt->close();

// Generate month options
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Generate year options (last 5 years and next year)
$years = range($currentYear - 4, $currentYear + 1);

// Generate DataTable messages with current month/year
$monthName = $months[$selectedMonth];
$emptyTableMessage = "No feedback available for your assigned services for the month of {$monthName} {$selectedYear}";
$zeroRecordsMessage = "No matching feedback found for your assigned services in {$monthName} {$selectedYear}";

// Detect current page for menu highlighting
$current = basename($_SERVER['PHP_SELF']);
$isDashboard = ($current === 'staff_dashboard.php');
$isProfile = ($current === 'staff_profile.php');
$isCitizenAccounts = ($current === 'citizen_accounts.php');
$isService = ($current === 'staff_service.php');
$isViewFeedback = ($current === 'staff_viewfeedback.php');
$isReports = ($current === 'staff_reports.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Feedback - My Assigned Services</title>
  <link rel="stylesheet" href="../font/css2.css">
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.4/css/select.bootstrap4.min.css">
  
  <link rel="stylesheet" href="../css/admin/view_feedback.css">
  
  <style>
    .response-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .response-section {
        background-color: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 1rem;
        margin-top: 1rem;
        border-radius: 0.25rem;
    }
    .response-form textarea {
        resize: vertical;
        min-height: 120px;
    }
    
    /* Print Styles */
    @media print {
        .no-print, .dt-custom-filters, .card-header .btn, .dataTables_length, 
        .dataTables_filter, .dataTables_info, .dataTables_paginate, .main-footer,
        .btn-group, .sentiment-badge-table, .star-rating {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-header {
            background: white !important;
            border-bottom: 2px solid #333 !important;
        }
        
        table {
            width: 100% !important;
            font-size: 12px !important;
        }
        
        th, td {
            padding: 4px !important;
        }
        
        .table-avatar, .table-avatar-fallback {
            width: 30px !important;
            height: 30px !important;
        }
        
        h4, h5 {
            color: #000 !important;
            margin-bottom: 10px !important;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .print-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        
        .print-subtitle {
            font-size: 14px;
            margin: 5px 0;
        }
        
        .print-date {
            font-size: 12px;
            margin: 0;
        }
        
        .rating-number {
            display: inline-block !important;
            font-weight: bold;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 11px;
        }
    }
    
    .rating-number {
        display: none;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <?php 
  // Pass the userImage variable to navbar.php
  $GLOBALS['userImage'] = $userImage;
  include 'staff_navbar.php'; 
  ?>

  <!-- Sidebar -->
  <?php 
  // Pass the userImage variable to aside.php
  $GLOBALS['userImage'] = $userImage;
  include 'staff_aside.php'; 
  ?>
  
  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">
        <h4 class="mb-3">View Feedback - My Assigned Services</h4>
        
        <!-- Hidden inputs to pass PHP data to JavaScript -->
        <input type="hidden" id="selectedMonth" value="<?= $selectedMonth ?>">
        <input type="hidden" id="selectedYear" value="<?= $selectedYear ?>">
        <input type="hidden" id="monthName" value="<?= $months[$selectedMonth] ?>">
        <input type="hidden" id="staffId" value="<?= $staffId ?>">
        
        <!-- Staff Assignment Info -->
        <?php if (empty($assignedServices)): ?>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            You are not currently assigned to any services. Please contact administrator to get service assignments.
          </div>
        <?php else: ?>
          <!-- Filter Section -->
          <div class="filter-container mb-4">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title mb-0">
                  <i class="fas fa-filter mr-2"></i>
                  Filter Feedback
                  <?php if ($selectedYear == $currentYear && $selectedMonth == $currentMonth): ?>
                      <span class="badge badge-success ml-2">Current Month</span>
                  <?php endif; ?>
                </h5>
              </div>
              <div class="card-body">
                <form method="GET" class="filter-form" id="filterForm">
                  <div class="row align-items-end">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-control" onchange="document.getElementById('filterForm').submit()">
                          <?php foreach ($months as $num => $name): ?>
                              <option value="<?= $num ?>" <?= $selectedMonth == $num ? 'selected' : ''; ?>>
                                  <?= $name; ?>
                              </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control" onchange="document.getElementById('filterForm').submit()">
                          <?php foreach ($years as $year): ?>
                            <option value="<?= $year; ?>" <?= $selectedYear == $year ? 'selected' : ''; ?>>
                              <?= $year; ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <div>
                          <?php if ($selectedYear != $currentYear || $selectedMonth != $currentMonth): ?>
                            <a href="staff_viewfeedback.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-sync-alt mr-2"></i>Reset to Current Month
                            </a>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12">
                      <div class="text-muted small">
                        <i class="fas fa-info-circle"></i> 
                        <span id="feedbackCount">Loading feedback data...</span>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Feedback Table -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">
                <i class="fas fa-table mr-2"></i>
                Feedback for My Assigned Services
              </h5>
              <div class="card-tools no-print">
                <button id="printTableBtn" class="btn btn-sm btn-outline-secondary mr-2">
                  <i class="fas fa-print mr-1"></i>Print All
                </button>
                <button id="printCurrentViewBtn" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-print mr-1"></i>Print Current View
                </button>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="dt-custom-filters no-print">
                <div class="filter-control">
                  <label for="sentimentFilter">Filter by Sentiment</label>
                  <select id="sentimentFilter" class="form-control form-control-sm">
                    <option value="">All Sentiments</option>
                    <option value="positive">Positive</option>
                    <option value="negative">Negative</option>
                    <option value="neutral">Neutral</option>
                    <option value="unknown">Not Analyzed</option>
                  </select>
                </div>
                
                <div class="filter-control">
                  <label for="serviceFilter">Filter by Service</label>
                  <select id="serviceFilter" class="form-control form-control-sm">
                    <option value="">All Services</option>
                    <!-- Services will be populated via JavaScript -->
                  </select>
                </div>
                
                <div class="filter-control">
                  <label for="ratingFilter">Filter by Rating</label>
                  <select id="ratingFilter" class="form-control form-control-sm">
                    <option value="">All Ratings</option>
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>
                  </select>
                </div>
                
                <div class="filter-control">
                  <label for="anonymousFilter">Filter by Type</label>
                  <select id="anonymousFilter" class="form-control form-control-sm">
                    <option value="">All Types</option>
                    <option value="anonymous">Anonymous</option>
                    <option value="non-anonymous">Non-Anonymous</option>
                  </select>
                </div>

                <div class="filter-control">
                  <label for="responseFilter">Filter by Response</label>
                  <select id="responseFilter" class="form-control form-control-sm">
                    <option value="">All Responses</option>
                    <option value="responded">With Response</option>
                    <option value="not-responded">Without Response</option>
                  </select>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="feedbackTable">
                  <thead class="thead-light">
                    <tr>
                      <th style="width: 5%;">#</th>
                      <th style="width: 15%;">Citizen</th>
                      <th style="width: 15%;">Service</th>
                      <th style="width: 25%;">Feedback</th>
                      <th style="width: 8%;">Rating</th>
                      <th style="width: 8%;" class="no-print">Sentiment</th>
                      <th style="width: 10%;">Date</th>
                      <th style="width: 8%;" class="no-print">Response</th>
                      <th style="width: 6%;" class="no-print">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Data will be loaded via JavaScript -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
  
  <!-- Feedback Details Modal -->
  <div class="modal fade" id="feedbackDetailsModal" tabindex="-1" role="dialog" aria-labelledby="feedbackDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="feedbackDetailsModalLabel">Feedback Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="feedbackDetailsContent">
          <!-- Content will be loaded via JavaScript -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Respond to Feedback Modal -->
  <div class="modal fade" id="respondFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="respondFeedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="respondFeedbackModalLabel">Respond to Feedback</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="responseForm">
          <div class="modal-body">
            <input type="hidden" id="feedbackId" name="feedback_id">
            <div class="form-group">
              <label for="responseText" class="form-label">Your Response</label>
              <textarea class="form-control" id="responseText" name="response_text" rows="6" placeholder="Type your response to this feedback..." required></textarea>
              <small class="form-text text-muted">Your response will be visible to the citizen who provided the feedback.</small>
            </div>
            <div id="existingResponse" class="response-section" style="display: none;">
              <h6><i class="fas fa-info-circle mr-2"></i>Existing Response</h6>
              <p id="existingResponseText" class="mb-2"></p>
              <small class="text-muted" id="existingResponseDate"></small>
              <hr>
              <p class="text-info"><small><i class="fas fa-exclamation-triangle mr-1"></i>Submitting a new response will replace the existing one.</small></p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitResponseBtn">
              <i class="fas fa-paper-plane mr-2"></i>Submit Response
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="main-footer text-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<!-- jQuery -->
<script src="../js/jquery/jquery-3.6.0.min.js"></script>
<!-- Bootstrap -->
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="../js/sweetalert/sweetalert2@11.js"></script>
<!-- AdminLTE -->
<script src="../js/adminlte/adminlte.min.js"></script>

<!-- DataTables JavaScript -->
<script src="../js/jquery/jquery.dataTables.min.js"></script>
<script src="../js/bootstrap/dataTables.bootstrap4.min.js"></script>
<script src="../js/buttons/dataTables.buttons.min.js"></script>
<script src="../js/buttons/buttons.bootstrap4.min.js"></script>
<script src="../js/buttons/buttons.html5.min.js"></script>
<script src="../js/buttons/buttons.print.min.js"></script>

<script>
// Pass PHP variables to JavaScript
const selectedMonth = <?= $selectedMonth ?>;
const selectedYear = <?= $selectedYear ?>;
const months = <?= json_encode($months) ?>;
const emptyTableMessage = "<?= $emptyTableMessage ?>";
const zeroRecordsMessage = "<?= $zeroRecordsMessage ?>";
</script>
<script src="../js/staff_viewfeedback.js"></script>
</body>
</html>