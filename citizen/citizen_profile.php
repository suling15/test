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

    // Get account data
    $stmt = $conn->prepare("SELECT * FROM citizen WHERE id = ?");
    $stmt->bind_param("i", $citizenId);
    $stmt->execute();
    $citizenData = $stmt->get_result()->fetch_assoc();

    // Profile image - FIXED PATH HANDLING
    if (!empty($profile['image'])) {
        // Extract just the filename in case full path was stored
        $imageFilename = basename($profile['image']);
        $userImage = $imageFilename;
        
        // Check if file actually exists
        $absolutePath = dirname(__DIR__) . '/citizen_image/' . $imageFilename;
        if (!file_exists($absolutePath)) {
            // File doesn't exist, use default
            $userImage = 'default.png';
        }
    } else {
        $userImage = 'default.png';
    }

    // Detect current page for menu highlighting
  $current = basename($_SERVER['PHP_SELF']);
  $isDashboard = ($current === 'citizen_dashboard.php');
  $isProfile = ($current === 'citizen_profile.php');
  $isServices = ($current === 'citizen_services.php');
  $isFeedback = ($current === 'citizen_feedback.php');
  $isPending = ($current === 'citizen_pending_complaint.php');
  $isProgress = ($current === 'citizen_progress_complaint.php');
  $isResolved = ($current === 'citizen_resolved_complaint.php');
  $isComplaints = ($isPending || $isProgress || $isResolved);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Citizen Portal</title>

    <link rel="stylesheet" href="../font/css2.css">
    <link rel="stylesheet" href="../css/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/citizen/citizen_profile.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <?php include 'citizen_navbar.php'; ?>

    <!-- Sidebar -->
    <?php include 'citizen_aside.php'; ?>

    <!-- Content -->
    <div class="content-wrapper">
        <section class="content">
            <div class="profile-wrapper">
                <div class="profile-title">Citizen Profile Information</div>
                <div class="profile-card-container">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-left">
                            <img src="../citizen_image/<?= htmlspecialchars($userImage) ?>" 
                                 alt="Profile Image" 
                                 onerror="this.src='../citizen_image/default.png'">
                            <div class="profile-info">
                                <p data-label="Firstname:"><span class="desktop-label">Firstname:</span> 
                                    <?= htmlspecialchars($profile['firstname'] ?? '') ?>
                                </p>
                                <p data-label="Middlename:"><span class="desktop-label">Middlename:</span> 
                                    <?= htmlspecialchars($profile['middlename'] ?? '') ?>
                                </p>
                                <p data-label="Lastname:"><span class="desktop-label">Lastname:</span> 
                                    <?= htmlspecialchars($profile['lastname'] ?? '') ?>
                                </p>
                                <p data-label="Gender:"><span class="desktop-label">Gender:</span> 
                                    <?= htmlspecialchars($profile['gender'] ?? '') ?>
                                </p>
                                <p data-label="Birthday:"><span class="desktop-label">Birthday:</span>
                                    <?= isset($profile['birthday']) ? date('m/d/Y', strtotime($profile['birthday'])) : '' ?>
                                </p>
                                <p data-label="Civil Status:"><span class="desktop-label">Civil Status:</span> 
                                    <?= htmlspecialchars($profile['civil_status'] ?? '') ?>
                                </p>
                                <p data-label="Contact:"><span class="desktop-label">Contact:</span> 
                                    <?= htmlspecialchars($profile['contact_number'] ?? '') ?>
                                </p>
                                <p data-label="Address:"><span class="desktop-label">Address:</span> 
                                    <?= htmlspecialchars($profile['address'] ?? '') ?>
                                </p>
                            </div>
                            <button class="edit-btn" data-toggle="modal" data-target="#editProfileModal" aria-label="Edit profile">
                                Update Profile <i class="fas fa-pen" aria-hidden="true"></i>
                            </button>
                            <button class="edit-btn mt-2" data-toggle="modal" data-target="#editAccountModal" aria-label="Edit account credentials" style="background: linear-gradient(135deg, #27ae60, #219a52);">
                                Update Credentials <i class="fas fa-key" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- Footer -->
    <footer class="main-footer text-right">
        <strong>© <?= date('Y') ?> CADIZ CITY GOVERNMENT. All rights reserved.</strong>
    </footer>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-modal="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Update Profile Information</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="profileForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="citizen_id" value="<?= $citizenId ?>">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="civil_status">Civil Status</label>
                                <select class="form-control" id="civil_status" name="civil_status" required>
                                    <option value="">Select Status</option>
                                    <option value="Single" <?= ($profile['civil_status'] ?? '') == 'Single' ? 'selected' : '' ?>>Single</option>
                                    <option value="Married" <?= ($profile['civil_status'] ?? '') == 'Married' ? 'selected' : '' ?>>Married</option>
                                    <option value="Widowed" <?= ($profile['civil_status'] ?? '') == 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                    <option value="Separated" <?= ($profile['civil_status'] ?? '') == 'Separated' ? 'selected' : '' ?>>Separated</option>
                                    <option value="Divorced" <?= ($profile['civil_status'] ?? '') == 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                </select>
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
                    
                    <?php if (!empty($profile['valid_id'])): ?>
                    <div class="form-group">
                        <label>Current Valid ID:</label>
                        <div class="current-valid-id-preview">
                            <?php
                            $validIdFilename = basename($profile['valid_id']); // Extract filename
                            $validIdExtension = pathinfo($validIdFilename, PATHINFO_EXTENSION);
                            $validIdPath = "../uploads/valid_id/" . $validIdFilename;
                            ?>
                            <?php if (in_array(strtolower($validIdExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="<?= $validIdPath ?>" alt="Current Valid ID" class="img-fluid mb-2" style="max-height: 200px; border: 1px solid #ddd; border-radius: 5px;" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-file-pdf mr-2"></i>
                                    Current ID file: <?= htmlspecialchars($validIdFilename) ?>
                                    <br>
                                    <a href="<?= $validIdPath ?>" target="_blank" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-download mr-1"></i> Download File
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profile_image" name="profile_image" 
                                   aria-describedby="profileImageHelp">
                            <label class="custom-file-label" for="profile_image">Choose file</label>
                        </div>
                        <small id="profileImageHelp" class="form-text text-muted">Leave blank to keep current image</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                <h5 class="modal-title" id="editAccountModalLabel">Update Account Credentials</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="accountForm">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $citizenId ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($citizenData['username'] ?? '') ?>" required>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Credentials</button>
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

<script src="../js/citizen_profile.js"></script>
</body>
</html>