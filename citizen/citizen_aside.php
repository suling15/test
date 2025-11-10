<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <div class="sidebar">
    <!-- User Panel -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
      <div class="image">
        <img src="../citizen_image/<?= htmlspecialchars($userImage); ?>" 
             alt="Citizen Image" 
             class="img-circle elevation-2" 
             style="width: 60px; height: 60px; object-fit: cover;">
      </div>
      <div class="info ml-2">
        <a href="#" class="d-block text-white font-weight-bold"><?= htmlspecialchars($user['username']); ?></a>
        <span class="text-muted small">Citizen</span>
      </div>
    </div> <!-- End user-panel -->

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <!-- Dashboard -->
        <li class="nav-item">
          <a href="../citizen_dashboard.php" class="nav-link <?= $isDashboard ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <!-- Profile -->
        <li class="nav-item">
            <a href="citizen_profile.php" class="nav-link <?= $isProfile ? 'active' : '' ?>">
                <i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
            </a>
        </li>
        <!-- Services -->
        <li class="nav-item">
          <a href="citizen_service.php" class="nav-link <?= $isServices ? 'active' : '' ?>">
            <i class="nav-icon fas fa-concierge-bell"></i>
            <p>Services</p>
          </a>
        </li>

        <!-- Feedback -->
        <li class="nav-item">
          <a href="citizen_feedback.php" class="nav-link <?= $isFeedback ? 'active' : '' ?>">
            <i class="nav-icon fas fa-comments"></i>
            <p>Feedback</p>
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