<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user = $_SESSION['user'];

require_once '../connection/config.php';
$db = new config();
$conn = $db->connectDB();

$citizenId = $user['id'];

// Get profile data
$stmt = $conn->prepare("SELECT * FROM profile WHERE citizen_id = ?");
$stmt->bind_param("i", $citizenId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();


// Profile image
$userImage = !empty($profile['image']) ? $profile['image'] : 'default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Services</title>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
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
</style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars" aria-hidden="true"></i>
                <span class="sr-only">Toggle navigation</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <img src="../citizen_image/<?= htmlspecialchars($userImage) ?>" class="img-circle elevation-2" alt="User profile picture" style="width:60px;height:60px;object-fit:cover;">
            </div>
            <div class="info ml-2">
                <a href="#" class="d-block text-white font-weight-bold"><?= htmlspecialchars($user['username']) ?></a>
                <span class="text-muted small">Citizen</span>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column">
                <li class="nav-item">
                    <a href="../citizen_dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="citizen_profile.php" class="nav-link ">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../citizen/citizen_service.php" class="nav-link active">
                        <i class="nav-icon fas fa-concierge-bell"></i>
                        <p>Services</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="feedback.php" class="nav-link">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Feedback</p>
                    </a>
                </li>

                <!-- Complaints -->
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-comment"></i>
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

<!-- Content -->
<div class="content-wrapper">
    <section class="content">
        <div class="profile-wrapper">
</section>
</div>

<!-- Footer -->
<footer class="main-footer text-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>

<script>
</script>
</body>
</html>