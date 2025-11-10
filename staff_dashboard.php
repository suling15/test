<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}
$user = $_SESSION['user'];

require_once 'connection/config.php';
$db = new config();
$conn = $db->connectDB();

$staffId = $user['id'];

// Handle month/year filter
$currentYear = date('Y');
$currentMonth = date('m');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : $currentMonth;

// Validate month and year
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = $currentMonth;
if ($selectedYear < 2020 || $selectedYear > $currentYear + 1) $selectedYear = $currentYear;

// Count services (not filtered by date)
$serviceCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM service");
if ($row = $result->fetch_assoc()) {
    $serviceCount = $row['total'];
}

// Count assigned services for this staff (not filtered by date)
$assignedServiceCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM staff_service WHERE staff_id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $assignedServiceCount = $row['total'];
}

// Count feedback for assigned services (filtered by month/year)
$feedbackCount = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM service_feedback sf
    JOIN staff_service ss ON sf.service_id = ss.service_id
    WHERE ss.staff_id = ? AND YEAR(sf.create) = ? AND MONTH(sf.create) = ?
");
$stmt->bind_param("iii", $staffId, $selectedYear, $selectedMonth);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $feedbackCount = $row['total'];
}

// Get recent feedback for assigned services (last 7 days)
$recentFeedback = [];
$stmt = $conn->prepare("
    SELECT sf.id, sf.feedback_text, sf.rating, sf.create, 
           c.username AS citizen_name, 
           s.name AS service_name,
           so.offer_name
    FROM service_feedback sf
    JOIN citizen c ON sf.citizen_id = c.id
    JOIN service s ON sf.service_id = s.id
    JOIN staff_service ss ON s.id = ss.service_id
    LEFT JOIN service_offer so ON sf.service_offer_id = so.id
    WHERE ss.staff_id = ? AND sf.create >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY sf.create DESC
    LIMIT 5
");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
$recentFeedback = $result->fetch_all(MYSQLI_ASSOC);

// Check if we should show only top 3 with feedback or all service offers (with or without feedback)
$showTop3 = true;
if (isset($_GET['show']) && $_GET['show'] === 'all') {
    $showTop3 = false;
}

// Get feedback data for chart - Service Offers with feedback counts and average ratings (filtered by month/year)
$chartData = [];
if ($showTop3) {
    // Show only top 3 service offers WITH feedback
    $stmt = $conn->prepare("
        SELECT 
            CONCAT(s.name, ' - ', so.offer_name) AS service_offer,
            COUNT(sf.id) AS feedback_count,
            AVG(sf.rating) AS avg_rating,
            so.offer_name,
            s.name AS service_name
        FROM service_offer so
        JOIN service s ON so.service_id = s.id
        JOIN staff_service ss ON s.id = ss.service_id
        LEFT JOIN service_feedback sf ON so.id = sf.service_offer_id AND YEAR(sf.create) = ? AND MONTH(sf.create) = ?
        WHERE ss.staff_id = ?
        GROUP BY so.id, s.id
        HAVING feedback_count > 0
        ORDER BY feedback_count DESC
        LIMIT 3
    ");
    $stmt->bind_param("iii", $selectedYear, $selectedMonth, $staffId);
} else {
    // Show ALL service offers assigned to this staff (with or without feedback)
    $stmt = $conn->prepare("
        SELECT 
            CONCAT(s.name, ' - ', so.offer_name) AS service_offer,
            COUNT(sf.id) AS feedback_count,
            COALESCE(AVG(sf.rating), 0) AS avg_rating,
            so.offer_name,
            s.name AS service_name
        FROM service_offer so
        JOIN service s ON so.service_id = s.id
        JOIN staff_service ss ON s.id = ss.service_id
        LEFT JOIN service_feedback sf ON so.id = sf.service_offer_id AND YEAR(sf.create) = ? AND MONTH(sf.create) = ?
        WHERE ss.staff_id = ?
        GROUP BY so.id, s.id
        ORDER BY feedback_count DESC
    ");
    $stmt->bind_param("iii", $selectedYear, $selectedMonth, $staffId);
}

$stmt->execute();
$result = $stmt->get_result();
$chartData = $result->fetch_all(MYSQLI_ASSOC);

// Get rating distribution data (filtered by month/year)
$ratingDistribution = [];
$stmt = $conn->prepare("
    SELECT 
        sf.rating,
        COUNT(*) as count
    FROM service_feedback sf
    JOIN staff_service ss ON sf.service_id = ss.service_id
    WHERE ss.staff_id = ? AND YEAR(sf.create) = ? AND MONTH(sf.create) = ?
    GROUP BY sf.rating
    ORDER BY sf.rating
");
$stmt->bind_param("iii", $staffId, $selectedYear, $selectedMonth);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ratingDistribution[$row['rating']] = $row['count'];
}

// Fill missing ratings with 0
for ($i = 1; $i <= 5; $i++) {
    if (!isset($ratingDistribution[$i])) {
        $ratingDistribution[$i] = 0;
    }
}
ksort($ratingDistribution);

// --- Profile Image --- //
$stmt = $conn->prepare("SELECT image FROM staff_profile WHERE staff_id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$userImage = !empty($profile['image']) ? $profile['image'] : 'default.png';

// Generate month options
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Convert selected month to integer to remove leading zeros
$selectedMonth = intval($selectedMonth);

// Generate year options (last 5 years and next year)
$years = range($currentYear - 4, $currentYear + 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard - Cadiz City Government</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="font/css2.css">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="css/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">

  <link rel="stylesheet" href="css/staff_dashboard.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
  </nav>

  <!-- Fixed Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="sidebar">
      <!-- User Panel -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
        <div class="image">
          <img src="staff_image/<?= htmlspecialchars($userImage); ?>" 
               alt="Staff Image" 
               class="img-circle elevation-2" 
               style="width: 50px; height: 50px; object-fit: cover;">
        </div>
        <div class="info">
          <a href="#" class="d-block"><?= htmlspecialchars($user['username']); ?></a>
          <span>Staff</span>
        </div>
      </div> <!-- End user-panel -->

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Dashboard -->
          <li class="nav-item">
            <a href="staff_dashboard.php" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <!-- Profile -->
          <li class="nav-item">
            <a href="staff/staff_profile.php" class="nav-link">
              <i class="nav-icon fas fa-user"></i>
              <p>Profile</p>
            </a>
          </li>
          <!-- Services -->
          <li class="nav-item">
            <a href="staff/staff_service.php" class="nav-link">
              <i class="nav-icon fas fa-concierge-bell"></i>
              <p>Services</p>
            </a>
          </li>
          <!-- Services feedback -->
          <li class="nav-item">
            <a href="staff/staff_viewfeedback.php" class="nav-link">
              <i class="nav-icon fas fa-comments"></i>
              <p> View Feedback</p>
            </a>
          <!-- Reports -->
          <li class="nav-item">
            <a href="staff/staff_reports.php" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>Reports</p>
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
    <section class="content">
      <div class="container-fluid">
        <h2 class="dashboard-title">Staff Dashboard</h2>
        
        <!-- Filter Section -->
        <div class="filter-container">
          <h4 class="chart-title">
            <i class="fas fa-filter mr-2"></i>
            Filter Data
            <?php if ($selectedYear == $currentYear && $selectedMonth == $currentMonth): ?>
              <span class="current-month-badge">Current Month</span>
            <?php endif; ?>
          </h4>
          <form method="GET" class="filter-form" id="filterForm">
            <div class="filter-group">
              <label class="filter-label">Month</label>
              <select name="month" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <?php foreach ($months as $num => $name): ?>
                  <option value="<?= $num ?>" <?= $selectedMonth == $num ? 'selected' : ''; ?>>
                    <?= $name; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-group">
              <label class="filter-label">Year</label>
              <select name="year" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <?php foreach ($years as $year): ?>
                  <option value="<?= $year; ?>" <?= $selectedYear == $year ? 'selected' : ''; ?>>
                    <?= $year; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <input type="hidden" name="show" value="<?= $showTop3 ? '' : 'all'; ?>">
            <?php if ($selectedYear != $currentYear || $selectedMonth != $currentMonth): ?>
              <a href="staff_dashboard.php" class="filter-btn" style="background: #6c757d; text-decoration: none;">
                <i class="fas fa-calendar-alt mr-2"></i>Current Month
              </a>
            <?php endif; ?>
          </form>
          <div class="mt-2 text-muted small">
            <i class="fas fa-info-circle"></i> Showing data for <?= $months[$selectedMonth]; ?> <?= $selectedYear; ?>
          </div>
        </div>
        
        <!-- Stats Cards - All Clickable -->
        <div class="row">          
          <!-- Total Services -->
          <div class="col-lg-3 col-6">
            <a href="staff/staff_service.php" class="card-link">
              <div class="small-box bg-gradient-info">
                <div class="inner">
                  <h3><?= $serviceCount; ?></h3>
                  <p>Total Services</p>
                </div>
                <div class="icon">
                  <i class="fas fa-concierge-bell"></i>
                </div>
              </div>
            </a>
          </div>
          
          <!-- Assigned Services -->
          <div class="col-lg-3 col-6">
            <a href="staff/staff_service.php" class="card-link">
              <div class="small-box bg-gradient-success">
                <div class="inner">
                  <h3><?= $assignedServiceCount; ?></h3>
                  <p>Assigned Services</p>
                </div>
                <div class="icon">
                  <i class="fas fa-tasks"></i>
                </div>
              </div>
            </a>
          </div>
          
          <!-- Total Feedback -->
          <div class="col-lg-3 col-6">
            <a href="staff/staff_viewfeedback.php" class="card-link">
              <div class="small-box bg-gradient-warning">
                <div class="inner">
                  <h3><?= $feedbackCount; ?></h3>
                  <p>Total Feedback</p>
                </div>
                <div class="icon">
                  <i class="fas fa-comments"></i>
                </div>
              </div>
            </a>
          </div>
          
          <!-- Recent Feedback -->
          <div class="col-lg-3 col-6">
            <a href="staff/staff_viewfeedback.php" class="card-link">
              <div class="small-box bg-gradient-danger">
                <div class="inner">
                  <h3><?= count($recentFeedback); ?></h3>
                  <p>Recent Feedback</p>
                </div>
                <div class="icon">
                  <i class="fas fa-clock"></i>
                </div>
              </div>
            </a>
          </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
          <!-- Service Offers Feedback Chart -->
          <div class="col-lg-8">
            <div class="chart-container">
              <div class="chart-header">
                <h4 class="chart-title">
                  <i class="fas fa-chart-bar mr-2"></i>
                  Service Offers Feedback Overview
                </h4>
                <?php if (!empty($chartData)): ?>
                  <button class="chart-toggle-btn" onclick="toggleChartView()">
                    <?= $showTop3 ? 'Show All' : 'Show Top 3' ?>
                  </button>
                <?php endif; ?>
              </div>
              <?php if (!empty($chartData)): ?>
                <div class="chart-canvas">
                  <canvas id="serviceOffersChart"></canvas>
                </div>
                <div class="mt-3 text-muted small">
                  <?php if ($showTop3): ?>
                    <i class="fas fa-info-circle"></i> Showing top 3 service offers with feedback for <?= $months[$selectedMonth]; ?> <?= $selectedYear; ?>
                  <?php else: ?>
                    <i class="fas fa-info-circle"></i> Showing all assigned service offers for <?= $months[$selectedMonth]; ?> <?= $selectedYear; ?>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="no-activities">
                  <i class="fas fa-chart-line"></i>
                  <p>No service offers with feedback</p>
                  <small>No feedback data found for <?= $months[$selectedMonth]; ?> <?= $selectedYear; ?></small>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Rating Distribution Chart -->
          <div class="col-lg-4">
            <div class="chart-container">
              <h4 class="chart-title">
                <i class="fas fa-star mr-2"></i>
                Rating Distribution
              </h4>
              <?php if (array_sum($ratingDistribution) > 0): ?>
                <div class="chart-canvas">
                  <canvas id="ratingDistributionChart"></canvas>
                </div>
                <div class="mt-3 text-muted small">
                  <i class="fas fa-info-circle"></i> Rating distribution for <?= $months[$selectedMonth]; ?> <?= $selectedYear; ?>
                </div>
              <?php else: ?>
                <div class="no-activities">
                  <i class="fas fa-star"></i>
                  <p>No ratings yet</p>
                  <small>No rating data found for <?= $months[$selectedMonth]; ?> <?= $selectedYear; ?></small>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Recent Activities Section -->
        <div class="row">
          <div class="col-12">
            <div class="activities-container">
              <h4 class="chart-title">Recent Activities (Last 7 Days)</h4>
              
              <?php if (!empty($recentFeedback)): ?>
                <?php foreach ($recentFeedback as $activity): ?>
                  <div class="activity-item">
                    <div class="activity-icon">
                      <i class="fas fa-comment"></i>
                    </div>
                    <div class="activity-content">
                      <div class="activity-title">New Feedback Received</div>
                      <div class="activity-details">
                        <strong>Service:</strong> <?= htmlspecialchars($activity['service_name']); ?>
                        <?php if (!empty($activity['offer_name'])): ?>
                          - <?= htmlspecialchars($activity['offer_name']); ?>
                        <?php endif; ?>
                      </div>
                      <div class="activity-details">
                        <strong>From:</strong> <?= htmlspecialchars($activity['citizen_name']); ?>
                      </div>
                      <div class="activity-details">
                        <?= htmlspecialchars(substr($activity['feedback_text'], 0, 100)); ?>
                        <?= strlen($activity['feedback_text']) > 100 ? '...' : ''; ?>
                      </div>
                      <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <?= $i <= $activity['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                        <?php endfor; ?>
                      </div>
                      <div class="activity-time">
                        <?= date("M j, Y g:i A", strtotime($activity['create'])); ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="no-activities">
                  <i class="fas fa-comment-slash"></i>
                  <p>No recent activities</p>
                  <small>You'll see new feedback here when it's received in the last 7 days</small>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="main-footer text-center text-md-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<!-- JS Libraries -->
<script src="js/jquery/jquery.min.js"></script>
<script src="js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/adminlte/adminlte.min.js"></script>
<script src="js/chart/chart.js"></script>

<script>
// Service Offers Chart Data
const chartData = <?= json_encode($chartData); ?>;
const ratingDistribution = <?= json_encode($ratingDistribution); ?>;

// Function to toggle between Top 3 and All view
function toggleChartView() {
    const currentUrl = new URL(window.location.href);
    if (currentUrl.searchParams.get('show') === 'all') {
        currentUrl.searchParams.delete('show');
    } else {
        currentUrl.searchParams.set('show', 'all');
    }
    window.location.href = currentUrl.toString();
}

// Service Offers Feedback Chart
if (chartData.length > 0) {
    const ctx1 = document.getElementById('serviceOffersChart').getContext('2d');
    
    // Prepare data for chart
    const labels = chartData.map(item => {
        const label = item.service_offer;
        return label.length > 30 ? label.substring(0, 30) + '...' : label;
    });
    
    const feedbackCounts = chartData.map(item => item.feedback_count);
    const avgRatings = chartData.map(item => parseFloat(item.avg_rating));
    
    // Create different background colors for services with and without feedback
    const backgroundColors = chartData.map(item => 
        item.feedback_count > 0 ? 'rgba(74, 108, 247, 0.6)' : 'rgba(108, 117, 125, 0.4)'
    );
    
    const borderColors = chartData.map(item => 
        item.feedback_count > 0 ? 'rgba(74, 108, 247, 1)' : 'rgba(108, 117, 125, 0.8)'
    );

    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Feedback Count',
                    data: feedbackCounts,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Average Rating',
                    data: avgRatings,
                    backgroundColor: 'rgba(255, 193, 7, 0.6)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 2,
                    type: 'line',
                    yAxisID: 'y1',
                    spanGaps: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '<?= $showTop3 ? 'Top 3 Service Offers with Feedback' : 'All Assigned Service Offers' ?>'
                },
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            const index = context[0].dataIndex;
                            return chartData[index].service_offer;
                        },
                        afterBody: function(context) {
                            const index = context[0].dataIndex;
                            const item = chartData[index];
                            let tooltipText = `Service: ${item.service_name}\nOffer: ${item.offer_name}`;
                            if (item.feedback_count > 0) {
                                tooltipText += `\nFeedback Count: ${item.feedback_count}\nAvg Rating: ${parseFloat(item.avg_rating).toFixed(1)}`;
                            } else {
                                tooltipText += '\nNo feedback received yet';
                            }
                            return tooltipText;
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Service Offers'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Number of Feedback'
                    },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Average Rating'
                    },
                    min: 0,
                    max: 5,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

// Rating Distribution Pie Chart
if (Object.values(ratingDistribution).some(val => val > 0)) {
    const ctx2 = document.getElementById('ratingDistributionChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
            datasets: [{
                data: Object.values(ratingDistribution),
                backgroundColor: [
                    '#dc3545',  // Red for 1 star
                    '#fd7e14',  // Orange for 2 stars
                    '#ffc107',  // Yellow for 3 stars
                    '#20c997',  // Teal for 4 stars
                    '#28a745'   // Green for 5 stars
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Feedback Rating Distribution'
                },
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}
</script>
</body>
</html>