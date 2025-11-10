<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
$user = $_SESSION['user'];

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
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Citizen Accounts</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="../font/css2.css">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
  
  <link rel="stylesheet" href="../css/admin/citizen_account.css?v=<?= time() ?>">
  
  <style>
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        margin-top: 10px;
    }
    .btn-group {
        display: flex;
        gap: 5px;
    }
    .btn-group .btn {
        flex: 1;
    }
    .status-badge {
        font-size: 0.8em;
        padding: 4px 8px;
    }
    .form-control:read-only {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        color: #495057;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_processing,
    .dataTables_wrapper .dataTables_paginate {
        color: #333;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px 8px;
    }
    .table-responsive {
        overflow-x: auto;
    }
  </style>
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
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Citizen Accounts</h3>
                                <button class="btn float-right" style="background-color:#007bff; color:white;" data-toggle="modal" data-target="#addModal">
                                    <i class="fas fa-plus"></i> Add Citizen
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="citizenTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Gender</th>
                                                <th>Birthday</th>
                                                <th>Contact</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be loaded via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
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

<!-- Add Citizen Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addModalLabel">Add New Citizen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_username">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="add_username" name="username" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="add_password">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="add_password" name="password" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="add_firstname">First Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="add_firstname" name="firstname" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="add_middlename">Middle Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="add_middlename" name="middlename">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="add_lastname">Last Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="add_lastname" name="lastname" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="add_image">Profile Image</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-image"></i></span>
                                    <input type="file" class="form-control-file" id="add_image" name="image" accept="image/*">
                                </div>
                                <img id="addPreviewImage" src="#" alt="Preview" class="img-thumbnail preview-image" style="display: none;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_gender">Gender *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    <select class="form-control" id="add_gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="add_birthday">Birthday *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="add_birthday" name="birthday" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="add_civil_status">Civil Status *</label>
                                <select class="form-control" id="add_civil_status" name="civil_status" required>
                                    <option value="">Select Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="add_address">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea class="form-control" id="add_address" name="address" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="add_contact_number">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" id="add_contact_number" name="contact_number">
                                </div>
                            </div>

                            <div class="form-group">
                              <label for="add_valid_id">Valid ID (Government Issued) *</label>
                              <div class="input-group">
                                  <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                  <input type="file" class="form-control-file" id="add_valid_id" name="valid_id" accept="image/*,.pdf" required>
                              </div>
                              <small class="form-text text-muted">Upload a scanned copy of a valid government ID (e.g., National ID, Passport, Driver's License) (JPG, PNG, or PDF, max 5MB)</small>
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
                        <i class="fas fa-save"></i> Save Citizen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Citizen Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editModalLabel"><i class="fas fa-user-edit"></i> Edit Citizen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_username">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="edit_username" name="username" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_password">Password (leave blank to keep current)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="edit_password" name="password">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_firstname">First Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="edit_firstname" name="firstname" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_middlename">Middle Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="edit_middlename" name="middlename">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_lastname">Last Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="edit_lastname" name="lastname" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="edit_image">Profile Image</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-image"></i></span>
                                    <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                </div>
                                <img id="editPreviewImage" src="#" alt="Preview" class="img-thumbnail preview-image" style="display: none;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_gender">Gender *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    <select class="form-control" id="edit_gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_birthday">Birthday *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="edit_birthday" name="birthday" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_civil_status">Civil Status *</label>
                                <select class="form-control" id="edit_civil_status" name="civil_status" required>
                                    <option value="">Select Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="edit_address">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_contact_number">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" id="edit_contact_number" name="contact_number">
                                </div>
                            </div>

                            <div class="form-group">
                              <label for="edit_valid_id">Valid ID (Government Issued)</label>
                              <div class="input-group">
                                  <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                  <input type="file" class="form-control" id="edit_valid_id" name="valid_id" accept="image/*,.pdf">
                              </div>
                              <small class="form-text text-muted">Upload a new file only if you want to replace the current valid ID</small>
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
                        <i class="fas fa-save"></i> Update Citizen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Citizen Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewModalLabel"><i class="fas fa-eye mr-2"></i> View Citizen Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="view_username" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">First Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="view_firstname" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Middle Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="view_middlename" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Last Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="view_lastname" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Profile Image</label>
                            <div class="text-center">
                                <img id="view_profile_image" src="#" class="img-thumbnail preview-image" style="max-height: 200px; display: none;" alt="Profile Image">
                                <div id="view_no_image" class="text-muted">No profile image</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Gender</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                <input type="text" class="form-control" id="view_gender" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Birthday</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" id="view_birthday" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Civil Status</label>
                            <input type="text" class="form-control" id="view_civil_status" readonly>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea class="form-control" id="view_address" rows="2" readonly></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Contact Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" class="form-control" id="view_contact_number" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Status</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                                <input type="text" class="form-control" id="view_status" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Valid ID (Government Issued)</label>
                            <div id="view_valid_id" class="mt-2">
                                <div class="alert alert-warning">No valid ID uploaded</div>
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

<!-- JS Libraries -->
<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert/sweetalert2@11.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>

<!-- DataTables JS -->
<script src="../js/jquery/jquery.dataTables.min.js"></script>
<script src="../js/bootstrap/dataTables.bootstrap4.min.js"></script>
<script src="../js/buttons/dataTables.buttons.min.js"></script>
<script src="../js/buttons/buttons.bootstrap4.min.js"></script>
<script src="../js/jszip/jszip.min.js"></script>
<script src="../js/pdfmake/pdfmake.min.js"></script>
<script src="../js/pdfmake/vfs_fonts.js"></script>
<script src="../js/buttons/buttons.html5.min.js"></script>
<script src="../js/buttons/buttons.print.min.js"></script>
<script src="../js/buttons/buttons.colVis.min.js"></script>

<script src="../js/create_citizen.js"></script>

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
