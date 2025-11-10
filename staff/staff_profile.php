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

// Check if valid ID exists
$hasValidId = !empty($profile['valid_id']);
$validIdPath = $hasValidId ? $profile['valid_id'] : '';

// Check if valid ID is an image file
$isImageFile = false;
if ($hasValidId) {
    $fileExtension = strtolower(pathinfo($validIdPath, PATHINFO_EXTENSION));
    $isImageFile = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']);
}

$current = basename($_SERVER['PHP_SELF']);
$isDashboard = ($current === 'staff_dashboard.php');
$isProfile = ($current === 'staff_profile.php');
$isCitizenAccounts = ($current === 'citizen_accounts.php');
$isService = ($current === 'staff_service.php');
$isViewFeedback = ($current === 'staff_viewfeedback.php');
$isReports = ($current === 'staff_reports.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile | Cadiz City</title>

    <link rel="stylesheet" href="../font/css2.css">
    <link rel="stylesheet" href="../css/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/staff/staff_profile.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <?php include 'staff_navbar.php'; ?>
        <!-- Sidebar -->
        <?php include 'staff_aside.php'; ?>
        <div class="content-wrapper">
            <div class="profile-container">
                <!-- Header -->
                <div class="profile-header">
                    <h2>Staff Profile</h2>
                </div>
                <!-- Profile and Account Information -->
                <div class="row">
                    <!-- Profile Information Card -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user mr-2"></i>Profile Information
                            </div>
                            <div class="card-body">
                                <div class="profile-img-container">
                                    <img src="../staff_image/<?= htmlspecialchars($userImage); ?>" 
                                         alt="Profile Image" class="profile-img">
                                </div>
    
                                <div class="profile-info-item">
                                    <div class="profile-info-label">First Name:</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($profile['firstname'] ?? '') ?></div>
                                </div>
                                
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Middle Name:</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($profile['middlename'] ?? '') ?></div>
                                </div>
                                
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Last Name:</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($profile['lastname'] ?? '') ?></div>
                                </div>
                                
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Gender:</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($profile['gender'] ?? '') ?></div>
                                </div>
                                
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Birthday:</div>
                                    <div class="profile-info-value"><?= isset($profile['birthday']) ? date('m/d/Y', strtotime($profile['birthday'])) : '' ?></div>
                                </div>
                                
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Contact Number:</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($profile['contact_number'] ?? '') ?></div>
                                </div>
                                
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Address:</div>
                                    <div class="profile-info-value"><?= htmlspecialchars($profile['address'] ?? '') ?></div>
                                </div>
                                
                                <div class="text-right mt-4">
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#editProfileModal">
                                        <i class="fas fa-edit mr-2"></i>Edit Profile
                                    </button>
                                    <button class="btn btn-secondary" data-toggle="modal" data-target="#editAccountModal">
                                        <i class="fas fa-key mr-2"></i>Update Credentials
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer text-center text-md-right">
            <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
        </footer>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-modal="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel"><i class="fas fa-user-edit mr-2"></i>Edit Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="profileForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstname">Firstname</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" 
                                           value="<?= htmlspecialchars($profile['firstname'] ?? '') ?>" required autofocus>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="middlename">Middlename</label>
                                    <input type="text" class="form-control" id="middlename" name="middlename" 
                                           value="<?= htmlspecialchars($profile['middlename'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastname">Lastname</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" 
                                           value="<?= htmlspecialchars($profile['lastname'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select class="form-control" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($profile['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($profile['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($profile['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="birthday">Birthday</label>
                                    <input type="date" class="form-control" id="birthday" name="birthday" 
                                           value="<?= isset($profile['birthday']) ? htmlspecialchars($profile['birthday']) : '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                           value="<?= htmlspecialchars($profile['contact_number'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?= htmlspecialchars($profile['address'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_image">Profile Image</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="profile_image" name="profile_image" 
                                       aria-describedby="profileImageHelp">
                                <label class="custom-file-label" for="profile_image">Choose file</label>
                            </div>
                            <small id="profileImageHelp" class="form-text text-muted">Leave blank to keep current image</small>
                            
                            <!-- Current Profile Image Preview -->
                            <?php if (!empty($profile['image'])): ?>
                            <div class="current-file-info mt-2">
                                <div class="file-preview">
                                    <div class="file-icon">
                                        <i class="fas fa-file-image"></i>
                                    </div>
                                    <div class="file-info">
                                        <div class="file-name">Current Profile Image</div>
                                        <div class="file-type">Uploaded</div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="../staff_image/<?= htmlspecialchars($profile['image']) ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-primary btn-file">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Valid ID Display (Read-only for staff) -->
                        <div class="form-group">
                            <label>Valid ID (Government Issued)</label>
                            
                            <?php if ($hasValidId): ?>
                                <?php if ($isImageFile): ?>
                                <!-- Display image directly -->
                                <div class="image-preview-container">
                                    <img src="../uploads/staff_validID/<?= htmlspecialchars($validIdPath) ?>" 
                                         alt="Valid ID" 
                                         class="valid-id-image"
                                         onerror="this.style.display='none'; document.getElementById('file-preview-fallback').style.display='block';">
                                    
                                    <!-- Fallback if image fails to load -->
                                    <div id="file-preview-fallback" style="display: none;">
                                        <div class="file-preview">
                                            <div class="file-icon">
                                                <i class="fas fa-file-image"></i>
                                            </div>
                                            <div class="file-info">
                                                <div class="file-name">Uploaded Valid ID</div>
                                                <div class="file-type"><?= strtoupper($fileExtension) ?> File</div>
                                            </div>
                                            <div class="file-actions">
                                                <a href="../uploads/staff_validID/<?= htmlspecialchars($validIdPath) ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-primary btn-file">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="../uploads/staff_validID/<?= htmlspecialchars($validIdPath) ?>" 
                                                   download 
                                                   class="btn btn-sm btn-success btn-file">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- For PDF and other non-image files -->
                                <div class="current-file-info">
                                    <div class="file-preview">
                                        <div class="file-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div class="file-info">
                                            <div class="file-name">Uploaded Valid ID</div>
                                            <div class="file-type"><?= strtoupper($fileExtension) ?> File</div>
                                        </div>
                                        <div class="file-actions">
                                            <a href="../uploads/staff_validID/<?= htmlspecialchars($validIdPath) ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-primary btn-file">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="../uploads/staff_validID/<?= htmlspecialchars($validIdPath) ?>" 
                                               download 
                                               class="btn btn-sm btn-success btn-file">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="field-note">
                                    <i class="fas fa-info-circle text-info"></i> 
                                    Valid ID cannot be modified. Contact administrator for updates.
                                </div>
                            <?php else: ?>
                            <div class="current-file-info">
                                <div class="text-center py-3">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                    <p class="mb-1">No valid ID uploaded</p>
                                    <div class="admin-contact">
                                        <small>
                                            <i class="fas fa-info-circle"></i>
                                            Please contact the administrator to upload your valid ID.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog" aria-labelledby="editAccountModalLabel" aria-modal="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAccountModalLabel"><i class="fas fa-key mr-2"></i>Edit Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="accountForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $staffId ?>">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($staffData['username'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   aria-describedby="passwordHelp">
                            <small id="passwordHelp" class="form-text text-muted">Leave blank to keep current password</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/jquery/jquery.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/adminlte/adminlte.min.js"></script>
    <script src="../js/sweetalert/sweetalert2.all.min.js"></script>
    <script src="../js/custom/bs-custom-file-input.min.js"></script>
    <script src="../js/staff_profile.js"></script>
    
    <script>
    $(document).ready(function() {
        bsCustomFileInput.init();
        
        // Remove valid_id from form data when submitting (staff cannot modify valid ID)
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            
            // Create new FormData without valid_id field
            let formData = new FormData();
            let formElements = $(this).serializeArray();
            
            // Add all form fields except valid_id
            formElements.forEach(function(element) {
                if (element.name !== 'valid_id') {
                    formData.append(element.name, element.value);
                }
            });
            
            // Add profile image if selected
            let profileImage = $('#profile_image')[0].files[0];
            if (profileImage) {
                formData.append('profile_image', profileImage);
            }
            
            // Show loading state
            let submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
            
            $.ajax({
                url: '../connection/staff_profile_update.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(rawResponse) {
                    submitBtn.prop('disabled', false).text('Save Changes');
                    
                    try {
                        let response = typeof rawResponse === 'string' ? 
                                     JSON.parse(rawResponse) : rawResponse;
                        
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message || 'Profile updated successfully',
                                icon: 'success'
                            }).then(() => location.reload());
                        } else {
                            showError(response.message || 'Operation failed');
                        }
                    } catch (e) {
                        if (typeof rawResponse === 'string' && rawResponse.includes('success')) {
                            location.reload();
                        } else {
                            showError('Invalid server response format');
                        }
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).text('Save Changes');
                    showError(xhr.responseText || 'Request failed');
                }
            });
        });
        
        function showError(message) {
            Swal.fire({
                title: 'Error!',
                text: message || 'An unexpected error occurred',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
    </script>
</body>
</html>