<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
$user = $_SESSION['user'];

// DB connection
require_once '../connection/config.php';
$db = new config();
$conn = $db->connectDB();

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Handle month/year filter from GET parameters
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : $currentMonth;
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;

// Validate month and year
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = $currentMonth;
if ($selectedYear < 2020 || $selectedYear > $currentYear + 1) $selectedYear = $currentYear;

// Generate month options
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Generate year options (last 5 years and next year)
$years = range($currentYear - 4, $currentYear + 1);

// Detect current page for menu highlighting
$current = basename($_SERVER['PHP_SELF']);
$isCitizen = ($current === 'citizen_account.php');
$isStaff = ($current === 'staff_account.php');
$isDashboard = ($current === 'admin_dashboard.php');
$isAccounts = ($isCitizen || $isStaff); 
$isServices = ($current === 'services.php');
$isViewFeedback = ($current === 'view_feedback.php');
$isFeedbackAnalysis = ($current === 'feedback_analysis.php');
$isFeedback = ($isViewFeedback || $isFeedbackAnalysis);
$isReports = ($current === 'reports.php');
$isLogs = ($current === 'userslogs.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Feedback</title>
  <!-- Favicon -->
  <link rel="stylesheet" href="../font/css2.css">
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../css/bootstrap/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../css/buttons/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../css/select/select.bootstrap4.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
   
  <link rel="stylesheet" href="../css/admin/view_feedback.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <?php include 'navbar.php'; ?>

  <!-- Sidebar -->
  <?php include 'aside.php'; ?>
  
  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">
        <h4 class="mb-3">View All Services Feedback</h4>
        
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
                          <a href="view_feedback.php" class="btn btn-secondary btn-block">
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
              Feedback Data
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="dt-custom-filters">
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
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0" id="feedbackTable">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;">Citizen</th>
                    <th style="width: 15%;">Service</th>
                    <th style="width: 30%;">Feedback</th>
                    <th style="width: 10%;">Rating</th>
                    <th style="width: 10%;">Sentiment</th>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 5%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Data will be loaded via JavaScript -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
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

  <footer class="main-footer text-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<!-- jQuery -->
<script src="../js/jquery/jquery-3.6.0.min.js"></script>
<script src="../js/jquery/jquery.dataTables.min.js"></script>
<!-- Bootstrap -->
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/bootstrap/dataTables.bootstrap4.min.js"></script>
<!-- SweetAlert2 -->
<script src="../js/sweetalert/sweetalert2@11.js"></script>
<!-- AdminLTE -->
<script src="../js/adminlte/adminlte.min.js"></script>

<!-- buttons JavaScript -->
<script src="../js/buttons/dataTables.buttons.min.js"></script>
<script src="../js/buttons/buttons.bootstrap4.min.js"></script>
<script src="../js/buttons/buttons.html5.min.js"></script>
<script src="../js/buttons/buttons.print.min.js"></script>

<script>
// Pass PHP variables to JavaScript
const selectedMonth = <?= $selectedMonth ?>;
const selectedYear = <?= $selectedYear ?>;
const months = <?= json_encode($months) ?>;
</script>
<script src="../js/view_feedback.js"></script>
</body>
</html>