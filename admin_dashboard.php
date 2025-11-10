<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
$user = $_SESSION['user'];

// Handle month/year selection
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate month and year
if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = date('m');
}
if ($selectedYear < 2020 || $selectedYear > date('Y')) {
    $selectedYear = date('Y');
}

// DB connection
require_once 'connection/config.php';
$db = new config();
$conn = $db->connectDB();

// Count staff
$staffCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM staff");
if ($row = $result->fetch_assoc()) {
    $staffCount = $row['total'];
}

// Count citizen
$citizenCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM citizen");
if ($row = $result->fetch_assoc()) {
    $citizenCount = $row['total'];
}

// Count services
$serviceCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM service");
if ($row = $result->fetch_assoc()) {
    $serviceCount = $row['total'];
}

// Get services with feedback count for SELECTED month/year
$servicesFeedbackData = [];
$serviceFeedbackQuery = "
    SELECT s.id, s.name, COUNT(sf.id) as feedback_count 
    FROM service s 
    LEFT JOIN service_feedback sf ON s.id = sf.service_id 
        AND MONTH(sf.create) = ? AND YEAR(sf.create) = ?
    GROUP BY s.id, s.name 
    ORDER BY feedback_count DESC
";
$stmt = $conn->prepare($serviceFeedbackQuery);
$stmt->bind_param("ii", $selectedMonth, $selectedYear);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $servicesFeedbackData[] = $row;
}
$stmt->close();

// Get service offers data for SELECTED month/year
$serviceOffersData = [];
if (isset($_GET['selected_service_id'])) {
    $selectedServiceId = intval($_GET['selected_service_id']);
    $offersQuery = "
        SELECT so.offer_name, so.price, COUNT(sf.id) as feedback_count
        FROM service_offer so
        LEFT JOIN service_feedback sf ON so.id = sf.service_offer_id 
            AND MONTH(sf.create) = ? AND YEAR(sf.create) = ?
        WHERE so.service_id = ?
        GROUP BY so.id, so.offer_name, so.price
        ORDER BY feedback_count DESC
    ";
    $stmt = $conn->prepare($offersQuery);
    $stmt->bind_param("iii", $selectedMonth, $selectedYear, $selectedServiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $serviceOffersData[] = $row;
    }
    $stmt->close();
}

// Get monthly feedback trend (last 30 days from selected date)
$monthlyTrendData = [];
$baseDate = date("$selectedYear-$selectedMonth-01");
$trendQuery = "
    SELECT DATE(sf.create) as feedback_date, COUNT(sf.id) as daily_count
    FROM service_feedback sf 
    WHERE sf.create >= DATE_SUB(?, INTERVAL 30 DAY) 
        AND sf.create < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(sf.create)
    ORDER BY feedback_date ASC
";
$stmt = $conn->prepare($trendQuery);
$stmt->bind_param("ss", $baseDate, $baseDate);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monthlyTrendData[] = $row;
    }
}
$stmt->close();

// Get recent feedback (last 5)
$recentFeedback = [];
$feedbackQuery = "
    SELECT sf.*, p.firstname, p.lastname, s.name as service_name 
    FROM service_feedback sf 
    JOIN citizen c ON sf.citizen_id = c.id 
    JOIN profile p ON c.id = p.citizen_id
    JOIN service s ON sf.service_id = s.id 
    ORDER BY sf.create DESC 
    LIMIT 5
";
$result = $conn->query($feedbackQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentFeedback[] = $row;
    }
}

// Get recent responses (last 5)
$recentResponses = [];
$responseQuery = "
    SELECT fr.*, 
           CONCAT(COALESCE(sp.firstname, ''), ' ', COALESCE(sp.lastname, '')) as staff_fullname,
           p.firstname, p.lastname 
    FROM feedback_response fr 
    JOIN staff st ON fr.staff_id = st.id 
    LEFT JOIN staff_profile sp ON st.id = sp.staff_id
    JOIN service_feedback sf ON fr.feedback_id = sf.id 
    JOIN citizen c ON sf.citizen_id = c.id 
    JOIN profile p ON c.id = p.citizen_id
    ORDER BY fr.created_at DESC 
    LIMIT 5
";
$result = $conn->query($responseQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentResponses[] = $row;
    }
}

// Prepare data for charts
$serviceNames = [];
$feedbackCounts = [];
foreach ($servicesFeedbackData as $service) {
    $serviceNames[] = $service['name'];
    $feedbackCounts[] = $service['feedback_count'];
}

$offerNames = [];
$offerFeedbackCounts = [];
foreach ($serviceOffersData as $offer) {
    $offerNames[] = $offer['offer_name'];
    $offerFeedbackCounts[] = $offer['feedback_count'];
}

// Prepare monthly trend data
$trendDates = [];
$trendCounts = [];
$last30Days = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days", strtotime($baseDate)));
    $last30Days[$date] = 0;
}

foreach ($monthlyTrendData as $trend) {
    $last30Days[$trend['feedback_date']] = $trend['daily_count'];
}

foreach ($last30Days as $date => $count) {
    $trendDates[] = date('M j', strtotime($date));
    $trendCounts[] = $count;
}

// Generate month options
$months = [];
for ($i = 1; $i <= 12; $i++) {
    $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
}

// Generate year options (last 5 years + current year)
$currentYear = date('Y');
$years = [];
for ($i = $currentYear - 5; $i <= $currentYear; $i++) {
    $years[] = $i;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

 <!-- Google Font -->
  <link rel="stylesheet" href="font/css2.css">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="css/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">

  <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Overlay -->
  <div class="overlay"></div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button" id="sidebarToggle"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-block d-sm-none">
        <a class="nav-link" href="#">Admin Dashboard</a>
      </li>
    </ul>
    
    <!-- Mobile user info -->
    <ul class="navbar-nav ml-auto d-flex d-md-none">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <img src="admin/admin.png" alt="Admin Image" class="img-circle elevation-2" style="width: 35px; height: 35px; object-fit: cover;">
        </a>
        <div class="dropdown-menu dropdown-menu-right">
          <span class="dropdown-item">Administrator</span>
          <div class="dropdown-divider"></div>
          <a href="logout.php" class="dropdown-item text-danger">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
          </a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="sidebar">
      <!-- User Panel -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
        <div class="image">
          <img src="admin/admin.png" alt="Admin Image" class="img-circle elevation-2" style="width: 60px; height: 60px; object-fit: cover;">
        </div>
        <div class="info ml-2">
          <a href="#" class="d-block"><?= htmlspecialchars($user['username']); ?></a>
          <small class="text-muted">Administrator</small>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <!-- Dashboard -->
          <li class="nav-item">
            <a href="admin_dashboard.php" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <!-- Dropdown Menu for Accounts -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Accounts
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="admin/citizen_account.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Citizen Accounts</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="admin/staff_account.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Staff Accounts</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Services -->
          <li class="nav-item">
            <a href="admin/services.php" class="nav-link">
              <i class="nav-icon fas fa-concierge-bell"></i>
              <p>Services</p>
            </a>
          </li>

          <!-- Services feedback -->
          <li class="nav-item">
            <a href="admin/view_feedback.php" class="nav-link">
              <i class="nav-icon fas fa-comments"></i>
              <p>View Feedback</p>
            </a>
          </li>

          <!-- Reports -->
          <li class="nav-item">
            <a href="admin/reports.php" class="nav-link">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Reports</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="admin/userslogs.php" class="nav-link">
              <i class="nav-icon fas fa-user-clock"></i>
              <p>User Logs</p>
            </a>
          </li>

          <!-- Logout -->
          <li class="nav-item">
            <a href="logout.php" class="nav-link text-danger">
              <i class="fas fa-sign-out-alt nav-icon"></i>
              <p>Logout</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="content-wrapper">
    <section class="content pt-4">
      <div class="container-fluid">
        <!-- Month/Year Selector -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                  <h5 class="mb-0 text-primary">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Feedback Analytics for 
                    <?php 
                    if ($selectedMonth == date('m') && $selectedYear == date('Y')) {
                        echo date('F Y');
                    } else {
                        echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
                    }
                    ?>
                  </h5>
                  <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                    <form method="GET" action="admin_dashboard.php" class="month-year-selector">
                      <!-- Hidden field to preserve service selection -->
                      <?php if (isset($_GET['selected_service_id'])): ?>
                        <input type="hidden" name="selected_service_id" value="<?= htmlspecialchars($_GET['selected_service_id']) ?>">
                      <?php endif; ?>
                      
                      <div class="form-group mb-0">
                        <select name="month" class="form-control form-control-sm" onchange="this.form.submit()">
                          <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num == $selectedMonth ? 'selected' : '' ?>>
                              <?= $name ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      
                      <div class="form-group mb-0">
                        <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                          <?php foreach ($years as $year): ?>
                            <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>>
                              <?= $year ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      
                      <?php if ($selectedMonth != date('m') || $selectedYear != date('Y')): ?>
                        <a href="admin_dashboard.php<?= isset($_GET['selected_service_id']) ? '?selected_service_id=' . htmlspecialchars($_GET['selected_service_id']) : '' ?>" 
                           class="btn btn-sm btn-outline-primary">
                          Current Month
                        </a>
                      <?php endif; ?>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mt-3">
          <div class="col-lg-3 col-6">
            <a href="admin/citizen_account.php" style="text-decoration: none; display: block;">
              <div class="smaller-box bg-gradient-primary text-white">
                <div class="inner">
                  <h3><?= $citizenCount ?></h3>
                  <p>Total Citizen Accounts</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
              </div>
            </a>
          </div>

          <div class="col-lg-3 col-6">
            <a href="admin/staff_account.php" style="text-decoration: none; display: block;">
              <div class="smaller-box bg-gradient-success text-white">
                <div class="inner">
                  <h3><?= $staffCount ?></h3>
                  <p>Total Staff Accounts</p>
                </div>
                <div class="icon"><i class="fas fa-user-tie"></i></div>
              </div>
            </a>
          </div>

          <div class="col-lg-3 col-6">
            <a href="admin/services.php" style="text-decoration: none; display: block;">
              <div class="smaller-box bg-gradient-info text-white">
                <div class="inner">
                  <h3><?= $serviceCount ?></h3>
                  <p>Total Services</p>
                </div>
                <div class="icon"><i class="fas fa-concierge-bell"></i></div>
              </div>
            </a>
          </div>

          <div class="col-lg-3 col-6">
            <a href="admin/view_feedback.php" style="text-decoration: none; display: block;">
              <div class="smaller-box bg-gradient-warning text-white">
                <div class="inner">
                  <h3><?= array_sum($feedbackCounts) ?></h3>
                  <p>Monthly Feedback</p>
                </div>
                <div class="icon"><i class="fas fa-comments"></i></div>
              </div>
            </a>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="card activity-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i> Monthly Feedback Trend (Last 30 Days)</h3>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="monthlyTrendChart"></canvas>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card activity-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Services Feedback - <?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?></h3>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="servicesFeedbackChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Service Offers Section -->
        <div class="row mt-4">
          <div class="col-md-12">
            <div class="card activity-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-list-alt mr-2"></i> 
                  <?php 
                  if (!empty($serviceOffersData)) {
                      $selectedServiceName = "";
                      if (isset($_GET['selected_service_id'])) {
                          $serviceId = intval($_GET['selected_service_id']);
                          foreach ($servicesFeedbackData as $service) {
                              if ($service['id'] == $serviceId) {
                                  $selectedServiceName = $service['name'];
                                  break;
                              }
                          }
                      }
                      echo "Offers for: " . htmlspecialchars($selectedServiceName) . " - " . date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
                  } else {
                      echo "Service Offers - " . date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
                  }
                  ?>
                </h3>
                <div class="chart-controls">
                  <?php if (!empty($serviceOffersData)): ?>
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary" id="zoomOut">
                        <i class="fas fa-search-minus"></i>
                      </button>
                      <button type="button" class="btn btn-outline-secondary" id="resetZoom">
                        <i class="fas fa-expand-arrows-alt"></i>
                      </button>
                      <button type="button" class="btn btn-outline-secondary" id="zoomIn">
                        <i class="fas fa-search-plus"></i>
                      </button>
                      <button type="button" class="btn btn-outline-info" id="toggleOrientation">
                        <i class="fas fa-exchange-alt"></i> 
                      </button>
                    </div>
                    <a href="admin_dashboard.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>" class="btn btn-sm btn-outline-secondary ml-2">Clear Selection</a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body">
                <?php if (!empty($serviceOffersData)): ?>
                  <div class="chart-container-wrapper">
                    <div class="chart-container" style="height: 300px;">
                      <canvas id="serviceOffersChart"></canvas>
                    </div>
                  </div>
                  <div class="chart-info mt-2">
                    <small class="text-muted">
                      <i class="fas fa-info-circle"></i> 
                      Use controls to resize. Current height: <span id="chartHeight">300px</span>
                      | <kbd>Ctrl</kbd>+<kbd>+</kbd> Zoom In | <kbd>Ctrl</kbd>+<kbd>-</kbd> Zoom Out
                    </small>
                  </div>
                <?php else: ?>
                  <div class="text-center py-4">
                    <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Click on a service in the bar chart to view its offers for <?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="card activity-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comment mr-2"></i> Recent Feedback</h3>
                <div class="card-tools">
                  <a href="admin/view_feedback.php" class="btn btn-sm btn-light">View All</a>
                </div>
              </div>
              <div class="card-body p-0">
                <?php if (!empty($recentFeedback)): ?>
                  <div class="p-3">
                    <?php foreach ($recentFeedback as $feedback): ?>
                      <div class="activity-item feedback">
                        <div class="activity-time">
                          <i class="far fa-clock mr-1"></i>
                          <?= date('M j, Y g:i A', strtotime($feedback['create'])) ?>
                        </div>
                        <div class="activity-content">
                          <span class="activity-user"><?= htmlspecialchars($feedback['firstname'] . ' ' . $feedback['lastname']) ?></span>
                          submitted feedback for <strong><?= htmlspecialchars($feedback['service_name']) ?></strong>
                          <?php if (!empty($feedback['feedback_text'])): ?>
                            <div class="text-truncate mt-1">
                              <i class="fas fa-quote-left text-xs mr-1"></i>
                              <?= htmlspecialchars(substr($feedback['feedback_text'], 0, 100)) ?>
                              <?= strlen($feedback['feedback_text']) > 100 ? '...' : '' ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="empty-activity">
                    <i class="fas fa-comment-slash"></i>
                    <p>No feedback submitted yet</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card activity-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-reply mr-2"></i> Recent Responses</h3>
                <div class="card-tools">
                  <a href="admin/view_feedback.php" class="btn btn-sm btn-light">View All</a>
                </div>
              </div>
              <div class="card-body p-0">
                <?php if (!empty($recentResponses)): ?>
                  <div class="p-3">
                    <?php foreach ($recentResponses as $response): ?>
                      <div class="activity-item response">
                        <div class="activity-time">
                          <i class="far fa-clock mr-1"></i>
                          <?= date('M j, Y g:i A', strtotime($response['created_at'])) ?>
                        </div>
                        <div class="activity-content">
                          <span class="activity-user"><?= htmlspecialchars($response['staff_fullname']) ?></span>
                          responded to <strong><?= htmlspecialchars($response['firstname'] . ' ' . $response['lastname']) ?></strong>'s feedback
                          <div class="text-truncate mt-1">
                            <i class="fas fa-quote-left text-xs mr-1"></i>
                            <?= htmlspecialchars(substr($response['response_text'], 0, 100)) ?>
                            <?= strlen($response['response_text']) > 100 ? '...' : '' ?>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="empty-activity">
                    <i class="fas fa-comment-dots"></i>
                    <p>No responses yet</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="main-footer">
    <strong>© 2025 CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<!-- JS Libraries -->
<script src="js/jquery/jquery.min.js"></script>
<script src="js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/adminlte/adminlte.min.js"></script>
<script src="js/chart/chart.js"></script>
<script src="js/chart/chartjs-plugin-zoom.min.js"></script>

<script>
// Chart data from PHP
const serviceNames = <?php echo json_encode($serviceNames); ?>;
const feedbackCounts = <?php echo json_encode($feedbackCounts); ?>;
const offerNames = <?php echo json_encode($offerNames); ?>;
const offerFeedbackCounts = <?php echo json_encode($offerFeedbackCounts); ?>;
const trendDates = <?php echo json_encode($trendDates); ?>;
const trendCounts = <?php echo json_encode($trendCounts); ?>;

// Global variables for chart controls
let serviceOffersChart;
let currentChartHeight = 300;
let isHorizontal = true;

// Monthly Trend Line Chart
const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
const monthlyTrendChart = new Chart(monthlyTrendCtx, {
    type: 'line',
    data: {
        labels: trendDates,
        datasets: [{
            label: 'Daily Feedback',
            data: trendCounts,
            backgroundColor: 'rgba(158, 159, 211, 0.1)',
            borderColor: 'rgba(158, 159, 211, 1)',
            borderWidth: 2,
            tension: 0.4,
            pointBackgroundColor: 'rgba(158, 159, 211, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                mode: 'index',
                intersect: false
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Feedback'
                },
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Date'
                }
            }
        }
    }
});

// Services Feedback Bar Chart for Selected Month
const servicesFeedbackCtx = document.getElementById('servicesFeedbackChart').getContext('2d');
const servicesFeedbackChart = new Chart(servicesFeedbackCtx, {
    type: 'bar',
    data: {
        labels: serviceNames,
        datasets: [{
            label: `Feedback Count - <?php echo date('M Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>`,
            data: feedbackCounts,
            backgroundColor: 'rgba(78, 115, 223, 0.7)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                mode: 'index',
                intersect: false
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Feedback'
                },
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Services'
                },
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        },
        onClick: (e, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const serviceId = <?php echo json_encode(array_column($servicesFeedbackData, 'id')); ?>[index];
                window.location.href = `admin_dashboard.php?selected_service_id=${serviceId}&month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>`;
            }
        }
    }
});

// Make bar chart clickable
servicesFeedbackCtx.canvas.style.cursor = 'pointer';

// Service Offers Chart with Zoom and Resize functionality
function initializeServiceOffersChart() {
    if (offerNames.length > 0) {
        const serviceOffersCtx = document.getElementById('serviceOffersChart').getContext('2d');
        
        serviceOffersChart = new Chart(serviceOffersCtx, {
            type: 'bar',
            data: {
                labels: offerNames,
                datasets: [{
                    label: `Feedback Count - <?php echo date('M Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>`,
                    data: offerFeedbackCounts,
                    backgroundColor: generateColors(offerNames.length),
                    borderColor: generateBorderColors(offerNames.length),
                    borderWidth: 1,
                    barThickness: 30,
                    maxBarThickness: 40,
                    minBarLength: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: isHorizontal ? 'y' : 'x',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'nearest',
                        intersect: true
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: isHorizontal ? 'Number of Feedback' : 'Service Offers'
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (value % 1 === 0) {
                                    return value;
                                }
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: isHorizontal ? 'Service Offers' : 'Number of Feedback'
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: isHorizontal ? 0 : 45,
                            minRotation: isHorizontal ? 0 : 45
                        }
                    }
                }
            }
        });

        updateChartHeightDisplay();
    }
}

// Generate colors for bars
function generateColors(count) {
    const colors = [
        'rgba(28, 200, 138, 0.7)',
        'rgba(54, 185, 204, 0.7)',
        'rgba(255, 193, 7, 0.7)',
        'rgba(253, 126, 20, 0.7)',
        'rgba(158, 159, 211, 0.7)',
        'rgba(78, 115, 223, 0.7)',
        'rgba(108, 117, 125, 0.7)',
        'rgba(40, 167, 69, 0.7)',
        'rgba(0, 123, 255, 0.7)',
        'rgba(102, 16, 242, 0.7)'
    ];
    
    let result = [];
    for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
    }
    return result;
}

function generateBorderColors(count) {
    const colors = [
        'rgba(28, 200, 138, 1)',
        'rgba(54, 185, 204, 1)',
        'rgba(255, 193, 7, 1)',
        'rgba(253, 126, 20, 1)',
        'rgba(158, 159, 211, 1)',
        'rgba(78, 115, 223, 1)',
        'rgba(108, 117, 125, 1)',
        'rgba(40, 167, 69, 1)',
        'rgba(0, 123, 255, 1)',
        'rgba(102, 16, 242, 1)'
    ];
    
    let result = [];
    for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
    }
    return result;
}

// Chart Controls Functions
function zoomIn() {
    if (serviceOffersChart) {
        currentChartHeight += 50;
        updateChartSize();
    }
}

function zoomOut() {
    if (serviceOffersChart && currentChartHeight > 200) {
        currentChartHeight -= 50;
        updateChartSize();
    }
}

function resetZoom() {
    if (serviceOffersChart) {
        currentChartHeight = 300;
        updateChartSize();
    }
}

function toggleOrientation() {
    if (serviceOffersChart) {
        isHorizontal = !isHorizontal;
        serviceOffersChart.destroy();
        initializeServiceOffersChart();
        showTempMessage(`Orientation changed to ${isHorizontal ? 'Horizontal' : 'Vertical'}`);
    }
}

function updateChartSize() {
    const container = document.querySelector('#serviceOffersChart').parentElement;
    if (container) {
        container.style.height = currentChartHeight + 'px';
        updateChartHeightDisplay();
        
        if (serviceOffersChart) {
            serviceOffersChart.resize();
        }
    }
}

function updateChartHeightDisplay() {
    const heightDisplay = document.getElementById('chartHeight');
    if (heightDisplay) {
        heightDisplay.textContent = currentChartHeight + 'px';
    }
}

function showTempMessage(message) {
    // Simple alert for demonstration
    alert(message);
}

// Initialize charts when page loads
$(document).ready(function() {
    // Sidebar toggle
    $('#sidebarToggle').click(function(e) {
        e.preventDefault();
        $('body').toggleClass('sidebar-open');
    });
    
    // Close sidebar when clicking overlay
    $('.overlay').click(function() {
        $('body').removeClass('sidebar-open');
    });
    
    // Auto-close sidebar when clicking a link on mobile
    if ($(window).width() < 992) {
        $('.sidebar').on('click', 'a.nav-link', function() {
            if (!$(this).parent().hasClass('has-treeview')) {
                $('body').removeClass('sidebar-open');
            }
        });
    }

    // Initialize service offers chart if data exists
    if (offerNames.length > 0) {
        initializeServiceOffersChart();
        
        // Add event listeners for controls
        document.getElementById('zoomIn').addEventListener('click', zoomIn);
        document.getElementById('zoomOut').addEventListener('click', zoomOut);
        document.getElementById('resetZoom').addEventListener('click', resetZoom);
        document.getElementById('toggleOrientation').addEventListener('click', toggleOrientation);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case '+': case '=': e.preventDefault(); zoomIn(); break;
                    case '-': e.preventDefault(); zoomOut(); break;
                    case '0': e.preventDefault(); resetZoom(); break;
                    case 'r': e.preventDefault(); resetZoom(); break;
                    case 't': e.preventDefault(); toggleOrientation(); break;
                }
            }
        });
    }
});
</script>
</body>
</html>