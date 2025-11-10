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

// Fetch all services for assignment
$services = [];
$service_query = "SELECT * FROM service ORDER BY name";
$service_result = $conn->query($service_query);
if ($service_result && $service_result->num_rows > 0) {
    while ($row = $service_result->fetch_assoc()) {
        $services[] = $row;
    }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Staff Accounts</title>

    <link rel="stylesheet" href="../font/css2.css">
    <link rel="stylesheet" href="../css/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../css/select/select2.min.css">
    <link rel="stylesheet" href="../css/select/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="../css/admin/staff_account.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Sidebar Container -->
    <?php include 'aside.php'; ?>

    <div class="content-wrapper">
        <section class="content pt-4">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Staff Accounts</h3>
                                <button class="btn float-right" style="background-color:#007bff; color:white;" data-toggle="modal" data-target="#addModal">
                                    <i class="fas fa-plus"></i> Add Staff
                                </button>
                            </div>
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table id="staffTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Assigned Services</th>
                                                <th>Gender</th>
                                                <th>Birthday</th>
                                                <th>Contact Number</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer text-right">
        <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
    </footer>
</div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addModalLabel">Add New Staff</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="username" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="firstname" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="middlename">Middle Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="middlename">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="lastname" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="services">Assign Services</label>
                                    <select id="addservices" name="services[]" class="form-control select2" multiple="multiple" data-placeholder="Select services to assign">
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="assignedServices" class="mt-3">
                                    <p class="text-muted">No services assigned yet.</p>
                                </div>
                                <div class="form-group">
                                    <label for="image">Profile Image</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-image"></i></span>
                                        <input type="file" class="form-control-file" name="image" accept="image/*">
                                    </div>
                                    <img id="addPreviewImage" src="#" alt="Preview" class="img-thumbnail preview-image" style="display: none;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                        <select class="form-control" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="birthday">Birthday</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" class="form-control" name="birthday" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <textarea class="form-control" name="address" rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" name="contact_number">
                                    </div>
                                </div>

                                <div class="form-group">
                                <label for="add_valid_id">Valid ID (Government Issued)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="file" class="form-control-file" id="add_valid_id" name="valid_id" accept="image/*,.pdf" required>
                                </div>
                                <small class="form-text text-muted">Upload a scanned copy of a valid government ID (e.g., passport, driver's license)</small>
                                <div id="addValidIdPreview" class="mt-2" style="display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Staff Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewModalLabel"><i class="fas fa-eye"></i> Staff Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- LEFT COLUMN -->
                        <div class="col-md-6">
                            <div class="text-center mb-4">
                                <img id="viewProfileImage" src="#" alt="Profile Image" class="img-thumbnail" style="max-height: 200px; display: none;">
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Username</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="viewUsername" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>First Name</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="viewFirstname" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Middle Name</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="viewMiddlename" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Last Name</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="viewLastname" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Assigned Services</strong></label>
                                <div id="viewServices" class="p-2 border rounded">
                                    <p class="text-muted mb-0">No services assigned</p>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Gender</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    <input type="text" class="form-control" id="viewGender" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Birthday</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="text" class="form-control" id="viewBirthday" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Address</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea class="form-control" id="viewAddress" rows="2" readonly></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Contact Number</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" id="viewContact" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Valid ID</strong></label>
                                <div id="viewValidId" class="text-center">
                                    <p class="text-muted">No valid ID uploaded</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="editModalLabel"><i class="fas fa-user-edit"></i> Edit Staff</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editForm" enctype="multipart/form-data">
                    <input type="hidden" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <!-- LEFT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="username" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="password">Password (leave blank to keep current)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="firstname" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="middlename">Middle Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="middlename">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="lastname" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="services">Assign Services</label>
                                    <select id="editservices" name="services[]" class="form-control select2" multiple="multiple" data-placeholder="Select services to assign">
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                    <div id="assignedServices" class="mt-3">
                                        <p class="text-muted">No services assigned yet.</p>
                                </div>
                                <div class="form-group">
                                    <label for="image">Profile Image</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-image"></i></span>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                    </div>
                                    <img id="editPreviewImage" src="#" alt="Preview" class="img-thumbnail preview-image" style="display: none;">
                                </div>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                        <select class="form-control" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="birthday">Birthday</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" class="form-control" name="birthday" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <textarea class="form-control" name="address" rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" name="contact_number">
                                    </div>
                                </div>

                                <div class="form-group">
                                <label for="edit_valid_id">Valid ID (Government Issued)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="file" class="form-control" id="edit_valid_id" name="valid_id" accept="image/*,.pdf">
                                </div>
                                <small class="form-text text-muted">Upload a new valid ID to replace the current one</small>
                                <div id="editValidIdPreview" class="mt-2" style="display: none;"></div>
                                <div id="currentValidId" class="mt-2"></div>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert/sweetalert2@11.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>
<script src="../js/select/select2.min.js"></script>
<script src="../js/jquery/jquery.dataTables.min.js"></script>
<script src="../js/bootstrap/dataTables.bootstrap4.min.js"></script>
<script src="../js/buttons/dataTables.buttons.min.js"></script>
<script src="../js/buttons/buttons.bootstrap4.min.js"></script>
<script src="../js/jszip/jszip.min.js"></script>
<script src="../js/pdfmake/pdfmake.min.js"></script>
<script src="../js/pdfmake/vfs_fonts.js"></script>
<script src="../js/buttons/buttons.html5.min.js"></script>
<script src="../js/buttons/buttons.print.min.js"></script>
<script src="../js/staff_account.js"></script>

</body>
</html>