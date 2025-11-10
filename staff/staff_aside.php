<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <div class="sidebar">
    <!-- User Panel -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
      <div class="image">
        <img src="../staff_image/<?= htmlspecialchars($userImage); ?>" 
             alt="Staff Image" 
             class="img-circle elevation-2" 
             style="width: 50px; height: 50px; object-fit: cover;">
      </div>
      <div class="info ml-2">
        <a href="#" class="d-block text-white font-weight-bold"><?= htmlspecialchars($user['username']); ?></a>
        <span class="text-muted small">Staff</span>
      </div>
    </div> <!-- End user-panel -->

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <!-- Dashboard -->
        <li class="nav-item">
          <a href="../staff_dashboard.php" class="nav-link <?= $isDashboard ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- Profile -->
        <li class="nav-item">
            <a href="staff_profile.php" class="nav-link <?= $isProfile ? 'active' : '' ?>">
                <i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
            </a>
        </li>

        <!-- Services -->
        <li class="nav-item">
          <a href="staff_service.php" class="nav-link <?= $isService ? 'active' : '' ?>">
            <i class="nav-icon fas fa-concierge-bell"></i>
            <p>Services</p>
          </a>
        </li>

        <!-- Services feedback -->
        <li class="nav-item">
          <a href="staff_viewfeedback.php" class="nav-link <?= $isViewFeedback ? 'active' : '' ?>">
            <i class="nav-icon fas fa-comments"></i>
            <p>ViewFeedback</p>
          </a>

        <!-- Reports -->
        <li class="nav-item">
          <a href="staff_reports.php" class="nav-link <?= $isReports ? 'active' : '' ?>">
            <i class="nav-icon fas fa-chart-bar"></i>
            <p>Reports</p>
          </a>
        </li>

        <!-- Logout -->
        <li class="nav-item">
          <a href="../logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt nav-icon"></i>
            <p>Logout</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>