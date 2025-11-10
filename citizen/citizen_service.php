<?php
  session_start();
  if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'citizen') {
        header("Location: ../index.php");
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

  // Generate CSRF token if not exists
  if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  // Detect current page for menu highlighting
  $current = basename($_SERVER['PHP_SELF']);
  $isDashboard = ($current === 'citizen_dashboard.php');
  $isProfile = ($current === 'citizen_profile.php');
  $isServices = ($current === 'citizen_service.php');
  $isFeedback = ($current === 'citizen_feedback.php');

  // Handle search
  $search = $_GET['search'] ?? '';
  $hasSearch = !empty($search);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
  <title>All City Services</title>

  <link rel="stylesheet" href="../font/css2.css">
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  <link rel="stylesheet" href="../css/citizen/citizen_service.css">

</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <?php include 'citizen_navbar.php'; ?>

  <!-- Sidebar -->
  <?php include 'citizen_aside.php'; ?>

  <!-- Main Content -->
<div class="content-wrapper">
    <section class="content pt-4">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <h3 class="card-title mb-2 mb-md-0">All City Services</h3>
                            <div class="search-container">
                                <form id="searchForm" method="GET" class="search-input-group">
                                    <input type="text" 
                                           name="search" 
                                           id="searchInput"
                                           class="form-control" 
                                           placeholder="Search services..." 
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           autocomplete="off">
                                    <button type="button" class="clear-search" id="clearSearch" title="Clear search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button type="submit" class="search-icon" title="Search">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="card-tools mt-2 mt-md-0">
                                <span class="badge badge-info" id="servicesCount"><?php 
                                    $countQuery = "SELECT COUNT(*) as total FROM service";
                                    $countResult = $conn->query($countQuery);
                                    $totalServices = $countResult ? $countResult->fetch_assoc()['total'] : 0;
                                    echo $totalServices . " Services Available";
                                ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($hasSearch): ?>
                            <div class="search-results-info alert alert-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-search mr-2"></i>
                                        Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                                    </div>
                                    <a href="citizen_service.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times mr-1"></i> Clear Search
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="row" id="servicesContainer">
                                <?php
                                // Build search query
                                $query = "SELECT s.*, COUNT(so.id) as offer_count 
                                          FROM service s 
                                          LEFT JOIN service_offer so ON s.id = so.service_id";
                                
                                $whereClause = "";
                                $params = [];
                                $types = "";
                                
                                if ($hasSearch) {
                                    $whereClause = " WHERE (s.name LIKE ? OR s.description LIKE ? OR s.contact_number LIKE ? OR s.location LIKE ?)";
                                    $searchTerm = "%" . $search . "%";
                                    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
                                    $types = "ssss";
                                }
                                
                                $query .= $whereClause . " GROUP BY s.id ORDER BY s.create_at DESC";
                                
                                $stmt = $conn->prepare($query);
                                
                                if ($hasSearch && !empty($params)) {
                                    $stmt->bind_param($types, ...$params);
                                }
                                
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $servicesCount = $result->num_rows;
                                
                                if ($result && $servicesCount > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<div class="col-lg-4 col-md-6 mb-4 service-card">';
                                        echo '<div class="card card-service h-100">';
                                        echo '<div class="card-body d-flex flex-column">';
                                        
                                        // Display image if exists
                                        if (!empty($row['image'])) {
                                            $imagePath = '../uploads/services_image/' . basename($row['image']);
                                            if (file_exists($imagePath)) {
                                                echo '<img src="' . $imagePath . '" class="img-fluid mb-3" alt="' . htmlspecialchars($row['name']) . '" style="height: 200px; object-fit: cover;">';
                                            } else {
                                                echo '<div class="text-center py-3"><i class="fas fa-image fa-5x text-muted"></i></div>';
                                            }
                                        } else {
                                            echo '<div class="text-center py-3"><i class="fas fa-image fa-5x text-muted"></i></div>';
                                        }
                                        
                                        echo '<h5 class="service-title">' . htmlspecialchars($row['name']) . '</h5>';
                                        
                                        // Truncate description
                                        $description = htmlspecialchars($row['description']);
                                        if (strlen($description) > 100) {
                                            $description = substr($description, 0, 100) . '...';
                                        }
                                        echo '<p class="service-description flex-grow-1">' . ($description ?: 'No description available') . '</p>';
                                        
                                        // Display contact number if available
                                        if (!empty($row['contact_number'])) {
                                            echo '<p class="service-contact mb-1"><small><i class="fas fa-phone text-primary mr-1"></i> ' . htmlspecialchars($row['contact_number']) . '</small></p>';
                                        }
                                        
                                        // Display location if available
                                        if (!empty($row['location'])) {
                                            echo '<p class="service-location mb-2"><small><i class="fas fa-map-marker-alt text-danger mr-1"></i> ' . htmlspecialchars($row['location']) . '</small></p>';
                                        }

                                        // Date information
                                        echo '<div class="service-meta mt-2">';
                                        echo '<small class="text-muted">';
                                        echo '<i class="fas fa-play-circle text-success mr-1"></i>';
                                        echo 'Started: ' . date('M j, Y', strtotime($row['create_at']));
                                        if ($row['updated_at'] && $row['updated_at'] !== $row['create_at']) {
                                            echo '<br><small class="text-muted ml-3">';
                                            echo '<i class="fas fa-sync-alt text-warning mr-1"></i>';
                                            echo 'Updated: ' . date('M j, Y', strtotime($row['updated_at']));
                                            echo '</small>';
                                        }
                                        echo '</small>';
                                        echo '</div>';
                                        
                                        // View and Offers buttons with offer count badge
                                        echo '<div class="btn-group w-100 mt-auto">';
                                        echo '<button class="btn btn-sm btn-primary view-service" data-id="' . $row['id'] . '"><i class="fas fa-eye"></i> View Details</button>';
                                        
                                        // Offers button with count badge
                                        echo '<button class="btn btn-sm btn-info view-offers position-relative" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['name']) . '">';
                                        echo '<i class="fas fa-tags"></i> Offers';
                                        if ($row['offer_count'] > 0) {
                                            echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">' . $row['offer_count'] . '</span>';
                                        }
                                        echo '</button>';
                                        
                                        echo '</div>';
                                        
                                        echo '</div></div></div>';
                                    }
                                    
                                } else {
                                    echo '<div class="col-12 no-results">';
                                    if ($hasSearch) {
                                        echo '<i class="fas fa-search fa-4x text-muted mb-3"></i>';
                                        echo '<h4>No services found</h4>';
                                        echo '<p class="text-muted">No services match your search for "<strong>' . htmlspecialchars($search) . '</strong>"</p>';
                                        echo '<a href="citizen_service.php" class="btn btn-primary mt-2">';
                                        echo '<i class="fas fa-list mr-1"></i> View All Services';
                                        echo '</a>';
                                    } else {
                                        echo '<i class="fas fa-box-open fa-3x text-muted mb-3"></i>';
                                        echo '<h4>No services found</h4>';
                                        echo '<p class="text-muted">There are currently no services available. Please check back later.</p>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            
                            <?php if ($hasSearch && $servicesCount > 0): ?>
                            <div class="text-center mt-4">
                                <a href="citizen_service.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i> Back to All Services
                                </a>
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
<footer class="main-footer text-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
</footer>
</div>

<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>
<script src="../js/sweetalert/sweetalert2.all.min.js"></script>
<script src="../js/custom/bs-custom-file-input.min.js"></script>
<script src="../js/citizen_service.js"></script>
</body>
</html>