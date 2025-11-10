    <?php
  session_start();
  if (!isset($_SESSION['user'])) {
      header("Location: index.php");
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
  $isServices = ($current === 'services.php');
  $isAccounts = ($isCitizen || $isStaff);  // True if either account page is active
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .main-header {
            background: linear-gradient(135deg, #9e9fd3ff, #858586ff);
        }

        .main-sidebar {
            background-image: url('../sidebar.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            z-index: 0;
        }

        .main-sidebar::before {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }

        .sidebar {
            position: relative;
            z-index: 2;
        }

        .nav-sidebar .nav-link {
            color: #cbd5e0;
        }

        .nav-sidebar .nav-link.active {
            background: linear-gradient(135deg, #c3c4c9ff, #8f8f94ff);
            color: #fff;
        }

        .nav-treeview .nav-link {
            padding-left: 2.5rem;
            font-size: 14px;
        }

        .user-panel .info a {
            color: white;
        }

        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .main-footer {
            background-color: #f3f4f6;
            border-top: 1px solid #ddd;
        }

        .avatar {
            width: 45px;
            height: 45px;
        }

        .small-box {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .small-box:hover {
            transform: translateY(-4px);
        }

        .small-box .icon {
            font-size: 3rem;
            position: absolute;
            top: 10px;
            right: 15px;
            color: rgba(255,255,255,0.3);
        }

        .preview-image {
            max-height: 150px;
            margin-bottom: 10px;
        }
        .icon-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .icon-btn:focus {
            outline: none;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="sidebar">
      <!-- User Panel -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
      <div class="image">
        <img src="admin.png" alt="Admin Image" class="img-circle elevation-2" style="width: 60px; height: 60px; object-fit: cover;">
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
              <a href="../admin_dashboard.php" class="nav-link <?= $isDashboard ? 'active' : '' ?>">
                  <i class="nav-icon fas fa-tachometer-alt"></i>
                  <p>Dashboard</p>
              </a>
          </li>

          <!-- Updated Accounts Dropdown -->
          <li class="nav-item has-treeview <?= $isAccounts ? 'menu-open' : '' ?>">
              <a href="#" class="nav-link <?= $isAccounts ? 'active' : '' ?>">
                  <i class="nav-icon fas fa-users"></i>
                  <p>
                      Accounts
                      <i class="right fas fa-angle-left"></i>
                  </p>
              </a>
              <ul class="nav nav-treeview">
                  <!-- Citizen Accounts Link -->
                  <li class="nav-item">
                      <a href="citizen_account.php" class="nav-link <?= $isCitizen ? 'active' : '' ?>">
                          <i class="far fa-circle nav-icon"></i>
                          <p>Citizen Accounts</p>
                      </a>
                  </li>
                  <!-- Staff Accounts Link -->
                  <li class="nav-item">
                      <a href="staff_account.php" class="nav-link <?= $isStaff ? 'active' : '' ?>">
                          <i class="far fa-circle nav-icon"></i>
                          <p>Staff Accounts</p>
                      </a>
                  </li>
              </ul>
          </li>

          <!-- Services -->
          <li class="nav-item">
            <a href="services.php" class="nav-link <?= $isServices ? ' active' : '' ?>">
              <i class="nav-icon fas fa-concierge-bell"></i>
              <p>Services</p>
            </a>
          </li>

          <!--DropDown Services feedback -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-comments"></i>
              <p>
                Feedback
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>View Feedback</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Analyze Feedback</p>
                </a>
              </li>
            </ul>
          </li>

          <!--Dropdown Complaints -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-comments"></i>
              <p>
                Complaints
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Pending Complaints</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>In Progress Complaints</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Resolved Complaints</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Reports -->
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Reports</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="#" class="nav-link">
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
        <div class="row">
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
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
