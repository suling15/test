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

// Detect current page for menu highlighting
$current = basename($_SERVER['PHP_SELF']);
$isCitizen = ($current === 'citizen_account.php');
$isStaff = ($current === 'staff_account.php');
$isDashboard = ($current === 'admin_dashboard.php');
$isAccounts = ($isCitizen || $isStaff); 
$isServices = ($current === 'services.php');
$isViewFeedback = ($current === 'view_feedback.php');
$isReports = ($current === 'reports.php');
$isLogs = ($current === 'userslogs.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users Logs</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="../font/css2.css">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  <link rel="stylesheet" href="../css/admin/userslogs.css?v=<?= time() ?>">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <?php include 'navbar.php'; ?>

  <!-- Sidebar -->
  <?php include 'aside.php'; ?>
  
   <!-- Main Content -->
  <div class="content-wrapper">
    <section class="content pt-4">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <h2 class="page-title"><i class="fas fa-user-clock mr-2"></i>Users Logs</h2>
            
            <!-- Date Filter -->
            <div class="card mb-3">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-3">
                    <label for="dateFilter">Filter by Date:</label>
                    <input type="date" id="dateFilter" class="form-control" value="<?= date('Y-m-d') ?>">
                  </div>
                  <div class="col-md-2 d-flex align-items-end">
                    <button id="loadLogs" class="btn btn-primary">Load Logs</button>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="card logs-table">
              <div class="card-body p-0">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>User</th>
                      <th>User Type</th>
                      <th>Login Status</th>
                      <th>IP Address</th>
                      <th>Device Info</th>
                      <th>Login Time</th>
                      <th>Logout Time</th>
                    </tr>
                  </thead>
                  <tbody id="logsTableBody">
                    <!-- Data will be loaded here via JavaScript -->
                    <tr>
                      <td colspan="7" class="text-center py-4">Loading logs...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="main-footer text-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<!-- JS Libraries -->
<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>
<script src="../js/sweetalert/sweetalert2.min.js"></script>

<script>
  const today = new Date().toISOString().split('T')[0];
</script>
<script src="../js/logs.js?v=<?= time() ?>"></script>
</body>
</html>