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

$staffId = $user['id'];

// Get profile data
$stmt = $conn->prepare("SELECT * FROM staff_profile WHERE staff_id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

$staffFullName = '';
if ($profile) {
    $staffFullName = trim($profile['firstname'] . ' ' . 
                    ($profile['middlename'] ? $profile['middlename'] . ' ' : '') . 
                    $profile['lastname']);
}

// Get account data
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$staffData = $stmt->get_result()->fetch_assoc();

// Profile image
$userImage = !empty($profile['image']) ? $profile['image'] : '../staff_image/default.png';

// Get services assigned to this staff member only
$serviceList = [];
$serviceSql = "SELECT s.id, s.name FROM service s 
              INNER JOIN staff_service ss ON s.id = ss.service_id 
              WHERE ss.staff_id = ? 
              ORDER BY s.name";
$serviceStmt = $conn->prepare($serviceSql);
$serviceStmt->bind_param("i", $staffId);
$serviceStmt->execute();
$serviceRes = $serviceStmt->get_result();
while ($row = $serviceRes->fetch_assoc()) {
    $serviceList[] = $row;
}
$serviceStmt->close();

// Add month/year filter
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Handle search
$selectedServiceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$serviceName = '';
$serviceImage = '';
$reportData = [];
$serviceTotals = ['positive' => 0, 'negative' => 0, 'neutral' => 0];

if ($selectedServiceId > 0) {
    // Verify that this service is assigned to the current staff member
    $verifyStmt = $conn->prepare("SELECT COUNT(*) as count FROM staff_service WHERE staff_id = ? AND service_id = ?");
    $verifyStmt->bind_param("ii", $staffId, $selectedServiceId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result()->fetch_assoc();
    $verifyStmt->close();
    
    if ($verifyResult['count'] == 0) {
        // Service not assigned to this staff member, redirect or show error
        header("Location: staff_reports.php");
        exit;
    }

    // Get service name and image
    $stmt = $conn->prepare("SELECT name, image FROM service WHERE id = ?");
    $stmt->bind_param("i", $selectedServiceId);
    $stmt->execute();
    $stmt->bind_result($serviceName, $serviceImage);
    $stmt->fetch();
    $stmt->close();

    // Fix the image path - handle both filename-only and full path cases
    if (!empty($serviceImage)) {
        // Extract just the filename in case full path was stored
        $imageFilename = basename($serviceImage);
        $serviceImage = '../uploads/services_image/' . $imageFilename;
        
        // Check if file actually exists
        $absolutePath = dirname(__DIR__) . '/uploads/services_image/' . $imageFilename;
        if (!file_exists($absolutePath)) {
            // File doesn't exist, set to null
            $serviceImage = null;
        }
    } else {
        $serviceImage = null;
    }

    // First, get all service offers for this service
    $offers = [];
    $offerSql = "SELECT id, offer_name FROM service_offer WHERE service_id = ? ORDER BY offer_name";
    $offerStmt = $conn->prepare($offerSql);
    $offerStmt->bind_param("i", $selectedServiceId);
    $offerStmt->execute();
    $offerResult = $offerStmt->get_result();
    while ($row = $offerResult->fetch_assoc()) {
        $offers[$row['id']] = [
            'offer_name' => $row['offer_name'],
            'positive_count' => 0,
            'negative_count' => 0,
            'neutral_count' => 0
        ];
    }
    $offerStmt->close();

    // SQL for all months or specific month
    $whereMonth = ($selectedMonth > 0) ? "AND MONTH(sf.create) = ?" : "";
    $sql = "
        SELECT
            so.id as offer_id,
            so.offer_name,
            SUM(CASE WHEN sf.sentiment IN ('positive', 'very positive') THEN 1 ELSE 0 END) AS positive_count,
            SUM(CASE WHEN sf.sentiment IN ('negative', 'very negative') THEN 1 ELSE 0 END) AS negative_count,
            SUM(CASE WHEN sf.sentiment = 'neutral' THEN 1 ELSE 0 END) AS neutral_count
        FROM
            service_offer so
            LEFT JOIN service_feedback sf ON so.id = sf.service_offer_id 
              AND sf.sentiment IS NOT NULL 
              AND sf.sentiment != 'unknown'
              $whereMonth
              AND YEAR(sf.create) = ?
        WHERE
            so.service_id = ?
        GROUP BY
            so.id, so.offer_name
        ORDER BY
            so.offer_name
    ";
    
    if ($selectedMonth > 0) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $selectedMonth, $selectedYear, $selectedServiceId);
    } else {
        // For "All Months", we need to adjust the query
        $whereMonth = "";
        $sql = "
            SELECT
                so.id as offer_id,
                so.offer_name,
                SUM(CASE WHEN sf.sentiment IN ('positive', 'very positive') THEN 1 ELSE 0 END) AS positive_count,
                SUM(CASE WHEN sf.sentiment IN ('negative', 'very negative') THEN 1 ELSE 0 END) AS negative_count,
                SUM(CASE WHEN sf.sentiment = 'neutral' THEN 1 ELSE 0 END) AS neutral_count
            FROM
                service_offer so
                LEFT JOIN service_feedback sf ON so.id = sf.service_offer_id 
                  AND sf.sentiment IS NOT NULL 
                  AND sf.sentiment != 'unknown'
                  AND YEAR(sf.create) = ?
            WHERE
                so.service_id = ?
            GROUP BY
                so.id, so.offer_name
            ORDER BY
                so.offer_name
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $selectedYear, $selectedServiceId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $offers[$row['offer_id']] = [
            'offer_name' => $row['offer_name'],
            'positive_count' => (int)$row['positive_count'],
            'negative_count' => (int)$row['negative_count'],
            'neutral_count' => (int)$row['neutral_count']
        ];
        
        $serviceTotals['positive'] += (int)$row['positive_count'];
        $serviceTotals['negative'] += (int)$row['negative_count'];
        $serviceTotals['neutral']  += (int)$row['neutral_count'];
    }
    $stmt->close();
    
    // Convert the offers array to reportData for the table
    $reportData = array_values($offers);
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
  <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
  <title>My Service Reports</title>
  <link rel="shortcut icon" type="image/jpg" href="../Seal_of_Cadiz,_Negros_Occidental.jpg"/>

  <!-- Google Font -->
  <link rel="stylesheet" href="../font/css2.css">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="../css/jquery/jquery.dataTables.min.css">
  <link rel="stylesheet" href="../css/buttons/buttons.dataTables.min.css">
  
  <link rel="stylesheet" href="../css/staff/reports.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <?php include 'staff_navbar.php'; ?>

  <!-- Sidebar Overlay for Mobile -->
  <div class="sidebar-overlay"></div>

  <!-- Sidebar -->
  <?php include 'staff_aside.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>My Service Reports</h3>
          </div>
          <div class="card-body">
            <!-- Hidden logos for JS export -->
            <img id="cityLogo" src="../Seal_of_Cadiz,_Negros_Occidental.jpg" style="display:none;">
            
            <?php if (!empty($serviceImage)): ?>
                <img id="serviceLogo" src="<?= htmlspecialchars($serviceImage) ?>" style="display:none;">
            <?php else: ?>
                <div id="serviceLogoPlaceholder" style="display:none;"></div>
            <?php endif; ?>
            
            <span id="staffFullName" style="display:none;"><?= htmlspecialchars($staffFullName) ?></span>
            
            <?php if (empty($serviceList)): ?>
              <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>No Services Assigned:</strong> You currently don't have any services assigned to you. Please contact your administrator to get services assigned.
              </div>
            <?php else: ?>
              <form method="get" class="mb-4 form-inline">
                <label for="service_id">Select Service:</label>
                <select name="service_id" id="service_id" onchange="this.form.submit()" class="form-control">
                  <option value="">-- Select Service --</option>
                  <?php foreach ($serviceList as $service): ?>
                    <option value="<?= $service['id'] ?>" <?= $selectedServiceId == $service['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($service['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                
                <label for="month">Month:</label>
                <select name="month" id="month" onchange="this.form.submit()" class="form-control">
                  <option value="0" <?= $selectedMonth == 0 ? 'selected' : '' ?>>All Months</option>
                  <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>>
                      <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                    </option>
                  <?php endfor; ?>
                </select>
                
                <label for="year">Year:</label>
                <select name="year" id="year" onchange="this.form.submit()" class="form-control">
                  <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 10; $y--): ?>
                    <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>>
                      <?= $y ?>
                    </option>
                  <?php endfor; ?>
                </select>
              </form>
              
              <?php if ($selectedServiceId > 0): ?>
                <div class="logo-header" id="reportHeader">
                  <img src="../Seal_of_Cadiz,_Negros_Occidental.jpg" alt="City Logo">
                  <span class="report-title">My Service Feedback Report</span>
                  
                  <?php if (!empty($serviceImage)): ?>
                      <img src="<?= htmlspecialchars($serviceImage) ?>" alt="Service Logo">
                  <?php else: ?>
                      <div class="no-image-placeholder">
                          <i class="fas fa-image"></i>
                          <div>No Image</div>
                      </div>
                  <?php endif; ?>
                </div>

                <!-- Print-only service information -->
                <div class="print-service-info">
                    Service: <?= htmlspecialchars($serviceName) ?><br>
                    Period: <?= $selectedMonth == 0 ? 'All Months' : date('F', mktime(0,0,0,$selectedMonth,10)) ?> <?= $selectedYear ?>
                </div>
                
                <div class="row mb-3">
                  <div class="col-12">
                    <h4><i class="fas fa-cog mr-2"></i>Service: <?= htmlspecialchars($serviceName) ?></h4>
                    <h5><i class="fas fa-calendar mr-2"></i>
                      Period: <?= $selectedMonth == 0 ? 'All Months' : date('F', mktime(0,0,0,$selectedMonth,10)) ?> <?= $selectedYear ?>
                    </h5>
                  </div>
                </div>

                <?php if ($serviceTotals['positive'] == 0 && $serviceTotals['negative'] == 0 && $serviceTotals['neutral'] == 0): ?>
                  <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>No Feedback Data:</strong> There is no feedback data available for this service in the selected time period.
                  </div>
                <?php else: ?>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0">Sentiment Distribution</h5>
                        </div>
                        <div class="card-body">
                          <div class="chart-container">
                            <canvas id="servicePieChart" width="300" height="300"></canvas>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0">Summary Statistics</h5>
                        </div>
                        <div class="card-body">
                          <div class="row text-center summary-stats">
                            <div class="col-4">
                              <div class="bg-success p-3 rounded text-white">
                                <i class="fas fa-laugh mr-1 fa-4x mb-2"></i>
                                <h4><?= $serviceTotals['positive'] ?></h4>
                                <small>Positive</small>
                              </div>
                            </div>
                            <div class="col-4">
                              <div class="bg-danger p-3 rounded text-white">
                                <i class="fas fa-angry mr-1 fa-4x mb-2"></i>
                                <h4><?= $serviceTotals['negative'] ?></h4>
                                <small>Negative</small>
                              </div>
                            </div>
                            <div class="col-4">
                              <div class="bg-warning p-3 rounded text-white">
                                <i class="fas fa-meh mr-1 fa-4x mb-2"></i>
                                <h4><?= $serviceTotals['neutral'] ?></h4>
                                <small>Neutral</small>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row mt-4">
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0">Detailed Report by Service Offer</h5>
                        </div>
                        <div class="card-body">
                          <div class="table-responsive">
                            <table id="reportTable" class="table table-bordered table-striped">
                              <thead>
                                <tr>
                                  <th>Offer Name</th>
                                  <th>Positive</th>
                                  <th>Negative</th>
                                  <th>Neutral</th>
                                  <th>Total Feedback</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($reportData as $row): 
                                  $total = $row['positive_count'] + $row['negative_count'] + $row['neutral_count'];
                                ?>
                                  <tr>
                                    <td><?= htmlspecialchars($row['offer_name']) ?></td>
                                    <td><span class="badge badge-success"><?= (int)$row['positive_count'] ?></span></td>
                                    <td><span class="badge badge-danger"><?= (int)$row['negative_count'] ?></span></td>
                                    <td><span class="badge badge-warning"><?= (int)$row['neutral_count'] ?></span></td>
                                    <td><strong><?= $total ?></strong></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                              <tfoot>
                                <tr style="font-weight:bold; background-color: #f8f9fa;">
                                  <td>Total (Service)</td>
                                  <td><span class="badge badge-success"><?= $serviceTotals['positive'] ?></span></td>
                                  <td><span class="badge badge-danger"><?= $serviceTotals['negative'] ?></span></td>
                                  <td><span class="badge badge-warning"><?= $serviceTotals['neutral'] ?></span></td>
                                  <td><strong><?= array_sum($serviceTotals) ?></strong></td>
                                </tr>
                              </tfoot>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="main-footer">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<!-- JS Libraries -->
<script src="../js/jquery/jquery-3.6.0.min.js"></script>
<script src="../js/jquery/jquery.dataTables.min.js"></script>
<script src="../js/buttons/dataTables.buttons.min.js"></script>
<script src="../js/buttons/buttons.html5.min.js"></script>
<script src="../js/buttons/buttons.print.min.js"></script>
<script src="../js/buttons/buttons.colVis.min.js"></script>
<script src="../js/jszip/jszip.min.js"></script>
<script src="../js/pdfmake/pdfmake.min.js"></script>
<script src="../js/pdfmake/vfs_fonts.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert/sweetalert2@11.js"></script>
<!-- Chart.js -->
<script src="../js/chart/chart.js"></script>

<?php if ($selectedServiceId > 0 && array_sum($serviceTotals) > 0): ?>
<script>
// Pass PHP variables to JavaScript
window.staffReportData = {
    hasData: true,
    staffName: "<?= htmlspecialchars($staffFullName) ?>",
    serviceName: "<?= htmlspecialchars($serviceName) ?>",
    yearMonth: "<?= $selectedMonth == 0 ? 'All Months' : date('F', mktime(0,0,0,$selectedMonth,10)) . ' ' . $selectedYear ?>",
    totals: [
        <?= $serviceTotals['positive'] ?>,
        <?= $serviceTotals['negative'] ?>,
        <?= $serviceTotals['neutral'] ?>
    ]
};
</script>
<?php endif; ?>

<!-- Load the external JavaScript file -->
<script src="../js/staff_reports.js"></script>
</body>
</html>