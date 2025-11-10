<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
        header("Location: ../index.php");
        exit;
}
$user = $_SESSION['user'];

require_once '../connection/config.php';
$db = new config();
$conn = $db->connectDB();

$staffId = $user['id'];

// Get profile data
$stmt = $conn->prepare("SELECT * FROM staff_profile WHERE staff_id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get account data
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$staffData = $stmt->get_result()->fetch_assoc();

// Profile image
$userImage = !empty($profile['image']) ? $profile['image'] : '../staff_image/default.png';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle search parameter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$validFilters = ['all', 'assigned', 'unassigned'];
if (!in_array($filter, $validFilters)) {
    $filter = 'all';
}

$current = basename($_SERVER['PHP_SELF']);
$isDashboard = ($current === 'staff_dashboard.php');
$isProfile = ($current === 'staff_profile.php');
$isCitizenAccounts = ($current === 'citizen_accounts.php');
$isService = ($current === 'staff_service.php');
$isViewFeedback = ($current === 'staff_viewfeedback.php');
$isReports = ($current === 'staff_reports.php');

// Filter labels for display
$filterLabels = [
    'all' => 'All Services',
    'assigned' => 'My Services',
    'unassigned' => 'Other Services'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes"> <!-- Mobile zoom fix -->
  <title>Services Management</title>
  <link rel="shortcut icon" type="image/jpg" href="../Seal_of_Cadiz,_Negros_Occidental.jpg"/>

  <!-- Google Font -->
  <link rel="stylesheet" href="../font/css2.css">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  
  <link rel="stylesheet" href="../css/staff/service.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed" data-staff-id="<?php echo $staffId; ?>" data-csrf-token="<?php echo $_SESSION['csrf_token']; ?>">
<div class="wrapper">

  <!-- Navbar -->
  <?php include 'staff_navbar.php'; ?>

  <!-- Sidebar -->
  <?php include 'staff_aside.php'; ?>

  <!-- Main Content -->
  <div class="content-wrapper">
    <section class="content pt-4">
      <div class="container-fluid">
        <div class="row mb-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">All Cadiz Services</h3>
                <div class="card-tools">
                  <span class="badge badge-success"><i class="fas fa-check-circle"></i> Assigned to me</span>
                </div>
              </div>
              <div class="card-body">
                <!-- Search and Filter Bar -->
                <div class="row mb-4">
                  <div class="col-md-6">
                    <form method="GET" class="search-form">
                      <input type="hidden" name="filter" value="<?= $filter ?>">
                      <div class="input-group">
                        <input type="search" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search services by name, description, contact, or location..."
                               value="<?= htmlspecialchars($searchTerm) ?>"
                               aria-label="Search services">
                        <div class="input-group-append">
                          <?php if (!empty($searchTerm)): ?>
                            <a href="staff_service.php?filter=<?= $filter ?>" class="btn btn-outline-secondary" title="Clear search">
                              <i class="fas fa-times"></i>
                            </a>
                          <?php endif; ?>
                          <button type="submit" class="btn btn-primary" title="Search">
                            <i class="fas fa-search"></i>
                          </button>
                        </div>
                      </div>
                      <?php if (!empty($searchTerm)): ?>
                        <small class="form-text text-muted">
                          Searching for: "<?= htmlspecialchars($searchTerm) ?>"
                        </small>
                      <?php endif; ?>
                    </form>
                  </div>
                  <div class="col-md-6">
                    <div class="d-flex justify-content-end align-items-center">
                      <!-- Filter Dropdown -->
                      <div class="dropdown mr-2">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="fas fa-filter mr-1"></i>
                          <?= $filterLabels[$filter] ?>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="filterDropdown">
                          <a class="dropdown-item <?= $filter === 'all' ? 'active' : '' ?>" 
                             href="?filter=all<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">
                            <i class="fas fa-layer-group mr-2"></i>All Services
                          </a>
                          <a class="dropdown-item <?= $filter === 'assigned' ? 'active' : '' ?>" 
                             href="?filter=assigned<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">
                            <i class="fas fa-user-check mr-2"></i>My Services
                          </a>
                          <a class="dropdown-item <?= $filter === 'unassigned' ? 'active' : '' ?>" 
                             href="?filter=unassigned<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">
                            <i class="fas fa-users mr-2"></i>Other Services
                          </a>
                        </div>
                      </div>

                      <?php if (!empty($searchTerm) || $filter !== 'all'): ?>
                        <a href="staff_service.php" class="btn btn-secondary">
                          <i class="fas fa-sync-alt mr-2"></i>Reset
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <!-- Active Filters Info -->
                <?php if (!empty($searchTerm) || $filter !== 'all'): ?>
                <div class="row mb-3">
                  <div class="col-12">
                    <div class="alert alert-info py-2">
                      <small>
                        <i class="fas fa-info-circle mr-1"></i>
                        Showing: 
                        <?php 
                        if (!empty($searchTerm) && $filter !== 'all') {
                            echo 'services matching "' . htmlspecialchars($searchTerm) . '" that are ' . $filterLabels[$filter];
                        } elseif (!empty($searchTerm)) {
                            echo 'services matching "' . htmlspecialchars($searchTerm) . '"';
                        } else {
                            echo $filterLabels[$filter];
                        }
                        ?>
                        <a href="staff_service.php" class="ml-2 text-danger">
                          <i class="fas fa-times"></i> Clear all
                        </a>
                      </small>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <?php
                // Build the SQL query with search and filter functionality
                $query = "SELECT s.*, 
                    COUNT(so.id) as offer_count,
                    CASE WHEN ss.staff_id IS NOT NULL THEN 1 ELSE 0 END as is_assigned,
                    ss.assigned_at
                  FROM service s 
                  LEFT JOIN service_offer so ON s.id = so.service_id 
                  LEFT JOIN staff_service ss ON s.id = ss.service_id AND ss.staff_id = ?
                  WHERE 1=1";

                $params = [];
                $types = "i";
                $params[] = $staffId;

                // Add filter condition
                if ($filter === 'assigned') {
                    $query .= " AND ss.staff_id IS NOT NULL";
                } elseif ($filter === 'unassigned') {
                    $query .= " AND ss.staff_id IS NULL";
                }

                // Add search condition if search term exists
                if (!empty($searchTerm)) {
                    $query .= " AND (s.name LIKE ? OR s.description LIKE ? OR s.contact_number LIKE ? OR s.location LIKE ?)";
                    $types .= "ssss";
                    $searchPattern = "%$searchTerm%";
                    $params[] = $searchPattern;
                    $params[] = $searchPattern;
                    $params[] = $searchPattern;
                    $params[] = $searchPattern;
                }

                $query .= " GROUP BY s.id ORDER BY s.create_at DESC";

                $stmt = $conn->prepare($query);

                // Bind parameters dynamically
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }

                $stmt->execute();
                $result = $stmt->get_result();

                // Service count for current filter
                $totalServices = $result->num_rows;
                ?>

                <!-- Service Count -->
                <div class="row mb-3">
                  <div class="col-12">
                    <div class="service-count-info">
                      <small class="text-muted">
                        <i class="fas fa-chart-bar mr-1"></i>
                        Showing <?= $totalServices ?> service<?= $totalServices !== 1 ? 's' : '' ?>
                        <?php if ($filter === 'assigned'): ?>
                          assigned to you
                        <?php elseif ($filter === 'unassigned'): ?>
                          not assigned to you
                        <?php endif; ?>
                      </small>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <?php
                  if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $isAssigned = $row['is_assigned'] == 1;
                            $assignedClass = $isAssigned ? 'assigned' : '';
                            
                            echo '<div class="col-lg-4 col-md-6 col-sm-12 mb-4">';
                            echo '<div class="card card-service ' . $assignedClass . '">';
                            
                            // Assignment badge
                            if ($isAssigned) {
                                echo '<div class="assigned-badge" title="Assigned on ' . date('M j, Y', strtotime($row['assigned_at'])) . '">';
                                echo '<i class="fas fa-user-check"></i> Assigned to Me';
                                echo '</div>';
                            }
                            
                            echo '<div class="card-body">';
                            
                            // Display image if exists
                            if (!empty($row['image'])) {
                                $imagePath = '../uploads/services_image/' . basename($row['image']);
                                if (file_exists($imagePath)) {
                                    echo '<img src="' . $imagePath . '" class="img-fluid" alt="' . htmlspecialchars($row['name']) . '">';
                                } else {
                                    echo '<div class="text-center py-3"><i class="fas fa-image fa-5x text-muted"></i></div>';
                                }
                            } else {
                                echo '<div class="text-center py-3"><i class="fas fa-image fa-5x text-muted"></i></div>';
                            }
                            
                            echo '<h5 class="service-title">';
                            if ($isAssigned) {
                                echo '<i class="fas fa-user-check assigned-icon"></i>';
                            }
                            echo htmlspecialchars($row['name']) . '</h5>';
                            
                            // Truncate description
                            $description = htmlspecialchars($row['description']);
                            if (strlen($description) > 100) {
                                $description = substr($description, 0, 100) . '...';
                            }
                            echo '<p class="service-description">' . ($description ?: 'No description available') . '</p>';
                            
                            // Display contact number if available
                            if (!empty($row['contact_number'])) {
                                echo '<p class="service-contact mb-1"><small><i class="fas fa-phone text-primary mr-1"></i> ' . htmlspecialchars($row['contact_number']) . '</small></p>';
                            }
                            
                            // Display location if available
                            if (!empty($row['location'])) {
                                echo '<p class="service-location mb-2"><small><i class="fas fa-map-marker-alt text-danger mr-1"></i> ' . htmlspecialchars($row['location']) . '</small></p>';
                            }
                            
                            // Assignment info
                            echo '<div class="service-meta mb-2">';
                            if ($isAssigned) {
                                echo '<small class="text-success"><i class="fas fa-calendar-alt"></i> Assigned: ' . date('M j, Y', strtotime($row['assigned_at'])) . '</small><br>';
                                echo '<small class="text-info"><i class="fas fa-tags"></i> Offers: ' . $row['offer_count'] . '</small>';
                            } else {
                                echo '<small class="text-muted"><i class="fas fa-info-circle"></i> Not assigned to you</small>';
                            }
                            echo '</div>';
                            
                            // Action buttons
                            echo '<div class="btn-group w-100">';
                            echo '<button class="btn btn-sm btn-primary view-service" data-id="' . $row['id'] . '"><i class="fas fa-eye"></i> View</button>';
                            echo '<button class="btn btn-sm btn-info view-offers btn-offers" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['name']) . '" data-assigned="' . $isAssigned . '">';
                            echo '<i class="fas fa-tags"></i> Offers';
                            if ($row['offer_count'] > 0) {
                                echo '<span class="offers-count-badge">' . $row['offer_count'] . '</span>';
                            }
                            echo '</button>';
                            
                            // Special assigned actions
                            if ($isAssigned) {
                                echo '<button class="btn btn-sm btn-success manage-offers" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['name']) . '">';
                                echo '<i class="fas fa-plus"></i> Add Offer';
                                echo '</button>';
                            }
                            
                            echo '</div>';
                            
                            echo '</div></div></div>';
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5">';
                        echo '<i class="fas fa-box-open fa-3x text-muted mb-3"></i>';
                        echo '<h4>No services found</h4>';
                        echo '<p>';
                        if (!empty($searchTerm) && $filter !== 'all') {
                            echo 'No services found for "' . htmlspecialchars($searchTerm) . '" that are ' . $filterLabels[$filter];
                        } elseif (!empty($searchTerm)) {
                            echo 'No services found for "' . htmlspecialchars($searchTerm) . '"';
                        } elseif ($filter === 'assigned') {
                            echo 'No services are currently assigned to you';
                        } elseif ($filter === 'unassigned') {
                            echo 'All services are currently assigned to staff members';
                        } else {
                            echo 'No services available';
                        }
                        echo '</p>';
                        if (!empty($searchTerm) || $filter !== 'all') {
                            echo '<a href="staff_service.php" class="btn btn-primary mt-2">';
                            echo '<i class="fas fa-sync-alt mr-2"></i>Show All Services';
                            echo '</a>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
              </div>
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

<!-- Service View Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1" role="dialog" aria-labelledby="serviceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="serviceModalLabel">Service Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <img id="modalServiceImage" src="" class="modal-service-image" alt="Service Image">
        </div>
        <h4 id="modalServiceName" class="text-center mb-3"></h4>
        
        <!-- Assignment info -->
        <div id="modalAssignmentInfo" class="alert alert-info mb-3" style="display:none;">
          <i class="fas fa-user-check"></i> <strong>This service is assigned to you</strong>
          <span id="modalAssignedDate" class="float-right"></span>
        </div>
        
        <p id="modalServiceDescription" class="mb-4"></p>
        
        <!-- Contact and Location Information -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="info-box bg-light">
              <span class="info-box-icon bg-primary"><i class="fas fa-phone"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Contact Number</span>
                <span id="modalServiceContact" class="info-box-number">Not specified</span>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="info-box bg-light">
              <span class="info-box-icon bg-danger"><i class="fas fa-map-marker-alt"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Location</span>
                <span id="modalServiceLocation" class="info-box-number">Not specified</span>
              </div>
            </div>
          </div>
        </div>
        
        <h5>Service Offers 
          <button id="addOfferBtn" class="btn btn-sm btn-success float-right" style="display:none;">
            <i class="fas fa-plus"></i> Add New Offer
          </button>
        </h5>
        <div id="serviceOffers" class="mb-4">
          <!-- Offers will be populated here -->
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <p><strong>Created:</strong> <span id="modalServiceCreated"></span></p>
          </div>
          <div class="col-md-6">
            <p><strong>Last Updated:</strong> <span id="modalServiceUpdated"></span></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button id="modalManageBtn" class="btn btn-success" style="display:none;">
          <i class="fas fa-cog"></i> Manage Service
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Offers Modal -->
<div class="modal fade" id="offersModal" tabindex="-1" role="dialog" aria-labelledby="offersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="offersModalLabel">Service Offers</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h4 id="offersServiceName" class="text-center mb-4"></h4>
        
        <!-- Add Offer Form (only for assigned services) -->
        <div id="addOfferFormContainer" class="mb-4" style="display:none;">
          <div class="card card-primary">
            <div class="card-header">
              <h6 class="card-title"><i class="fas fa-plus"></i> Add New Offer</h6>
            </div>
            <div class="card-body">
              <form id="addOfferForm">
                <input type="hidden" id="offerServiceId" name="service_id">
                <div class="form-group">
                  <label for="offerName">Offer Name *</label>
                  <input type="text" class="form-control" id="offerName" name="offer_name" required maxlength="150">
                </div>
                <div class="form-group">
                  <label for="offerPrice">Price (₱)</label>
                  <input type="number" class="form-control" id="offerPrice" name="price" step="0.01" min="0" placeholder="0.00">
                </div>
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-save"></i> Add Offer
                </button>
                <button type="button" class="btn btn-secondary" onclick="$('#addOfferFormContainer').hide()">Cancel</button>
              </form>
            </div>
          </div>
        </div>
        
        <button id="showAddOfferForm" class="btn btn-success mb-3" style="display:none;">
          <i class="fas fa-plus"></i> Add New Offer
        </button>
        
        <div id="offersList">
          <!-- Offers will be populated here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Offer Modal -->
<div class="modal fade" id="addOfferModal" tabindex="-1" role="dialog" aria-labelledby="addOfferModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addOfferModalLabel">Add New Offer</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="addOfferModalForm">
          <input type="hidden" id="modalOfferServiceId" name="service_id">
          <div class="form-group">
            <label for="modalOfferName">Offer Name *</label>
            <input type="text" class="form-control" id="modalOfferName" name="offer_name" required maxlength="150">
          </div>
          <div class="form-group">
            <label for="modalOfferPrice">Price (₱)</label>
            <input type="number" class="form-control" id="modalOfferPrice" name="price" step="0.01" min="0" placeholder="0.00">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="saveOfferBtn">
          <i class="fas fa-save"></i> Save Offer
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog" aria-labelledby="editServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editServiceForm" enctype="multipart/form-data">
          <input type="hidden" id="editServiceId" name="service_id">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          <div class="form-group">
            <label for="editServiceName">Service Name *</label>
            <input type="text" class="form-control" id="editServiceName" name="name" required maxlength="100" placeholder="Enter service name">
          </div>
          <div class="form-group">
            <label for="editServiceDescription">Description</label>
            <textarea class="form-control" id="editServiceDescription" name="description" rows="4" maxlength="500"></textarea>
          </div>
          
          <!-- Contact Number Field -->
          <div class="form-group">
            <label for="editServiceContact">Contact Number</label>
            <input type="text" class="form-control" id="editServiceContact" name="contact_number" placeholder="e.g., 09123456789" maxlength="20">
            <small class="form-text text-info">
              <i class="fas fa-info-circle mr-1"></i>
              Current: <span id="currentContactDisplay" class="font-weight-bold text-primary">None</span>
              <span class="text-muted ml-2">(Leave empty to keep current value)</span>
            </small>
          </div>
          
          <!-- Location Field -->
          <div class="form-group">
            <label for="editServiceLocation">Location</label>
            <input type="text" class="form-control" id="editServiceLocation" name="location" placeholder="e.g., City Hall, 2nd Floor" maxlength="255">
            <small class="form-text text-info">
              <i class="fas fa-info-circle mr-1"></i>
              Current: <span id="currentLocationDisplay" class="font-weight-bold text-primary">None</span>
              <span class="text-muted ml-2">(Leave empty to keep current value)</span>
            </small>
          </div>
          
          <div class="form-group">
            <label for="editServiceImage">Service Image</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="editServiceImage" name="image" accept="image/*">
              <label class="custom-file-label" for="editServiceImage">Choose file</label>
            </div>
            <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG, GIF. Leave empty to keep current image.</small>
          </div>
          <div class="current-image-container mt-3">
            <label>Current Image:</label>
            <div id="currentServiceImage" class="text-center mt-2"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="saveServiceBtn">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1" role="dialog" aria-labelledby="editOfferModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editOfferModalLabel">Edit Offer</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editOfferForm">
          <input type="hidden" id="editOfferId" name="offer_id">
          <input type="hidden" id="editOfferServiceId" name="service_id">
          <div class="form-group">
            <label for="editOfferName">Offer Name *</label>
            <input type="text" class="form-control" id="editOfferName" name="offer_name" required maxlength="150">
          </div>
          <div class="form-group">
            <label for="editOfferPrice">Price (₱)</label>
            <input type="number" class="form-control" id="editOfferPrice" name="price" step="0.01" min="0" placeholder="0.00">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveEditOfferBtn">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

<!-- JS Libraries -->
<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>
<script src="../js/sweetalert/sweetalert2@11.js"></script>
<script src="../js/jquery/jquery.dataTables.min.js"></script>
<script src="../js/bootstrap/dataTables.bootstrap4.min.js"></script>

<!-- External JavaScript -->
<script src="../js/staff_service.js"></script>
</body>
</html>