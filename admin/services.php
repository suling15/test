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

// Handle search parameter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the SQL query with search functionality
if (!empty($searchTerm)) {
    $query = "SELECT * FROM service WHERE name LIKE ? OR description LIKE ? OR contact_number LIKE ? OR location LIKE ? ORDER BY create_at DESC";
    $stmt = $conn->prepare($query);
    $searchPattern = "%$searchTerm%";
    $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT * FROM service ORDER BY create_at DESC";
    $result = $conn->query($query);
}

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

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Cadiz City Services Offered</title>
  <!-- Google Font -->
  <link rel="stylesheet" href="../font/css2.css">
  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="../css/bootstrap/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../css/admin/service.css">
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
        <div class="row mb-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center">
                <h3 class="card-title mb-2 mb-md-0">All Cadiz City Services Offered</h3>
                <div class="ml-md-auto d-flex flex-column flex-md-row align-items-stretch align-items-md-center w-90 w-md-auto">
                  <!-- Search Form -->
                  <form method="GET" class="w-900 mb-2 mb-md-0 mr-md-2" id="searchForm">
                    <div class="input-group">
                      <input type="search" name="search" class="form-control" placeholder="Search services by name, description, contact, or location..." value="<?= htmlspecialchars($searchTerm) ?>" aria-label="Search services" id="searchInput" data-has-search="<?= !empty($searchTerm) ? 'true' : 'false' ?>">
                      <div class="input-group-append">
                        <?php if (!empty($searchTerm)): ?>
                          <a href="services.php" class="btn btn-outline-secondary" title="Clear search">
                              <i class="fas fa-times"></i>
                          </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary" title="Search" style="display: none;">
                            <i class="fas fa-search"></i>
                        </button>
                      </div>
                    </div>
                  </form>
                  <!-- Add Service Button -->
                  <button class="btn" style="background-color:#007bff; color:white;" data-toggle="modal" data-target="#addServiceModal">
                      <i class="fas fa-plus"></i> Add Service
                  </button>
                </div>
              </div>
              <div class="card-body">
                <!-- Search Results Info -->
                <?php if (!empty($searchTerm)): ?>
                  <div class="alert alert-info mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <i class="fas fa-search mr-2"></i>
                        Found <?= $result->num_rows ?> result(s) for "<strong><?= htmlspecialchars($searchTerm) ?></strong>"
                      </div>
                      <a href="services.php" class="btn btn-sm btn-outline-secondary">
                          <i class="fas fa-times mr-1"></i> Clear Search
                      </a>
                    </div>
                  </div>
                <?php endif; ?>
                  
                <div class="row">
                  <?php
                    if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        echo '<div class="col-12 col-sm-6 col-lg-4 mb-4">';
                        echo '<div class="card card-service h-100">';
                        echo '<div class="card-body d-flex flex-column">';
                            
                            // Display image if exists
                            if (!empty($row['image'])) {
                                $imagePath = '../uploads/services_image/' . basename($row['image']);
                                if (file_exists($imagePath)) {
                                    echo '<img src="' . $imagePath . '" class="img-fluid mb-3" alt="' . htmlspecialchars($row['name']) . '">';
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
                            echo '<p class="service-description">' . ($description ?: 'No description available') . '</p>';
                            
                            // Display contact number if available
                            if (!empty($row['contact_number'])) {
                                echo '<p class="service-contact mb-1"><small><i class="fas fa-phone text-primary mr-1"></i> ' . htmlspecialchars($row['contact_number']) . '</small></p>';
                            }
                            
                            // Display location if available
                            if (!empty($row['location'])) {
                                echo '<p class="service-location mb-2"><small><i class="fas fa-map-marker-alt text-danger mr-1"></i> ' . htmlspecialchars($row['location']) . '</small></p>';
                            }
                            
                            // Action buttons - aligned in a grid
                            echo '<div class="actions-grid mt-auto">';
                            echo '<div class="row no-gutters">';
                            echo '<div class="col-6 pr-1">';
                            echo '<button class="btn btn-sm btn-primary view-service w-100" data-id="' . $row['id'] . '"><i class="fas fa-eye"></i> View</button>';
                            echo '</div>';
                            echo '<div class="col-6 pl-1">';
                            echo '<button class="btn btn-sm btn-warning edit-service w-100" data-id="' . $row['id'] . '"><i class="fas fa-edit"></i> Edit</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '<div class="row no-gutters mt-1">';
                            echo '<div class="col-6 pr-1">';
                            echo '<button class="btn btn-sm btn-info manage-offers w-100" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['name']) . '"><i class="fas fa-tags"></i> Offers</button>';
                            echo '</div>';
                            echo '<div class="col-6 pl-1">';
                            echo '<button class="btn btn-sm btn-danger delete-service w-100" data-id="' . $row['id'] . '"><i class="fas fa-trash"></i> Delete</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            
                            echo '</div></div></div>';
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5">';
                        if (!empty($searchTerm)) {
                            echo '<i class="fas fa-search fa-3x text-muted mb-3"></i>';
                            echo '<h4>No services found</h4>';
                            echo '<p>No services found for "<strong>' . htmlspecialchars($searchTerm) . '</strong>"</p>';
                            echo '<a href="services.php" class="btn btn-primary">Show All Services</a>';
                        } else {
                            echo '<i class="fas fa-box-open fa-3x text-muted mb-3"></i>';
                            echo '<h4>No services found</h4>';
                            echo '<p>Click the "Add Service" button to create your first service</p>';
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

  <!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog" aria-labelledby="addServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addServiceForm" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="modal-body">
          <div class="form-group">
            <label for="serviceName">Service Name *</label>
            <input type="text" class="form-control" id="serviceName" name="name" required>
          </div>
          <div class="form-group">
            <label for="serviceDescription">Description</label>
            <textarea class="form-control" id="serviceDescription" name="description" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="serviceContact">Contact Number</label>
              <input type="text" class="form-control" id="serviceContact" name="contact_number" placeholder="e.g., 09123456789">
            </div>
            <div class="form-group col-md-6">
              <label for="serviceLocation">Location</label>
              <input type="text" class="form-control" id="serviceLocation" name="location" placeholder="e.g., City Hall, 2nd Floor">
            </div>
          </div>
          <div class="form-group">
            <label for="serviceImage">Image</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="serviceImage" name="image" accept="image/*">
              <label class="custom-file-label" for="serviceImage">Choose file</label>
            </div>
            <small class="text-muted">Max size: 2MB (JPEG, PNG, GIF)</small>
            <div id="addImagePreview" class="mt-2 text-center">
              <i class="fas fa-image fa-3x text-muted"></i>
            </div>
          </div>
        </div>
        <div class="modal-footer d-flex flex-column flex-sm-row">
          <button type="button" class="btn btn-secondary mb-2 mb-sm-0 w-100" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-save"></i> Save Service
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

  <div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog" aria-labelledby="editServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="editServiceForm" enctype="multipart/form-data">
        <input type="hidden" id="editServiceId" name="id">
        <input type="hidden" id="currentImage" name="current_image">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="modal-body">
          <div class="form-group">
            <label for="editServiceName">Service Name *</label>
            <input type="text" class="form-control" id="editServiceName" name="name" required>
          </div>
          <div class="form-group">
            <label for="editServiceDescription">Description</label>
            <textarea class="form-control" id="editServiceDescription" name="description" rows="3"></textarea>
          </div>
          
          <!-- Contact Number Field -->
          <div class="form-group">
            <label for="editServiceContact">Contact Number</label>
            <input type="text" class="form-control" id="editServiceContact" name="contact_number" placeholder="e.g., 09123456789">
            <small class="form-text text-info">
              <i class="fas fa-info-circle mr-1"></i>
              Current: <span id="currentContactDisplay" class="font-weight-bold">None</span>
            </small>
          </div>
          
          <!-- Location Field -->
          <div class="form-group">
            <label for="editServiceLocation">Location</label>
            <input type="text" class="form-control" id="editServiceLocation" name="location" placeholder="e.g., City Hall, 2nd Floor">
            <small class="form-text text-info">
              <i class="fas fa-info-circle mr-1"></i>
              Current: <span id="currentLocationDisplay" class="font-weight-bold">None</span>
            </small>
          </div>
          
          <div class="form-group">
            <label for="editServiceImage">Image</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="editServiceImage" name="image" accept="image/*">
              <label class="custom-file-label" for="editServiceImage">Choose new file (optional)</label>
            </div>
            <small class="form-text text-info">
              <i class="fas fa-info-circle mr-1"></i>
              Current image will be kept if no new file is selected
            </small>
            <div id="imagePreview" class="mt-2 text-center"></div>
          </div>
        </div>
        <div class="modal-footer d-flex flex-column flex-sm-row">
          <button type="button" class="btn btn-secondary mb-2 mb-sm-0 w-100" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-warning w-100">
            <i class="fas fa-save"></i> Update Service
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Manage Offers Modal -->
<div class="modal fade" id="manageOffersModal" tabindex="-1" role="dialog" aria-labelledby="manageOffersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="manageOffersModalLabel">Manage Service Offers</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h6 id="serviceNameTitle" class="mb-3"></h6>
        <div class="card mb-4">
          <div class="card-header">
              <h6 class="mb-0">Add New Offer</h6>
          </div>
          <div class="card-body">
            <form id="addOfferForm">
              <input type="hidden" id="offerServiceId" name="service_id">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <div class="form-row">
                <div class="form-group col-md-8">
                  <label for="offerName">Offer Name *</label>
                  <input type="text" class="form-control" id="offerName" name="offer_name" required>
                </div>
                <div class="form-group col-md-4">
                  <label for="offerPrice">Price (₱)</label>
                  <input type="number" class="form-control" id="offerPrice" name="price" step="0.01" min="0">
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto">
                <i class="fas fa-plus"></i> Add Offer
              </button>
            </form>
          </div>
        </div>
        <h6>Current Offers</h6>
        <div id="offersList" class="list-group">
          <!-- Offers will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100 w-md-auto" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1" role="dialog" aria-labelledby="editOfferModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="editOfferModalLabel">Edit Offer</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="editOfferForm">
        <input type="hidden" id="editOfferId" name="id">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-8">
                <label for="editOfferName">Offer Name *</label>
                <input type="text" class="form-control" id="editOfferName" name="offer_name" required>
            </div>
            <div class="form-group col-md-4">
                <label for="editOfferPrice">Price (₱)</label>
                <input type="number" class="form-control" id="editOfferPrice" name="price" step="0.01" min="0">
            </div>
          </div>
        </div>
        <div class="modal-footer d-flex flex-column flex-sm-row">
            <button type="button" class="btn btn-secondary mb-2 mb-sm-0 w-100" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning w-100">Update Offer</button>
        </div>
      </form>
    </div>
  </div>
</div>

  <!-- Footer -->
  <footer class="main-footer text-center text-md-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<!-- JS Libraries -->
<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>
<script src="../js/sweetalert/sweetalert2@11.js"></script>
<script src="../js/jquery/jquery.dataTables.min.js"></script>
<script src="../js/bootstrap/dataTables.bootstrap4.min.js"></script>

<!-- Custom JS -->
<script src="../js/services.js"></script>

<script>
  
// Additional mobile-specific JavaScript
$(document).ready(function() {
  // Handle file input labels on mobile
  $('.custom-file-input').on('change', function() {
    let fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName);
  });
  
  // Make modals more mobile-friendly
  $('.modal').on('shown.bs.modal', function() {
    // Focus on first input in modal
    $(this).find('input:first').focus();
  });
});
</script>

</body>
</html>