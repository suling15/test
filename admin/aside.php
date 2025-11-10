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

          <!-- Services feedback -->
          <li class="nav-item">
            <a href="view_feedback.php" class="nav-link <?= $isViewFeedback ? ' active' : '' ?>">
              <i class="nav-icon fas fa-comments"></i>
              <p>View Feedback</p>
            </a>

          <!-- Reports -->
          <li class="nav-item">
            <a href="reports.php" class="nav-link <?= $isReports ? 'active' : '' ?>">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Reports</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="userslogs.php" class="nav-link <?= $isLogs ? 'active' : '' ?>">
              <i class="nav-icon fas fa-user-clock"></i>
              <p>User Logs</p>
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