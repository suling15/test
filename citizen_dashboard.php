<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'citizen') {
    header("Location: ../index.php");
    exit;
}
$user = $_SESSION['user'];

// Connect to DB
require_once 'connection/config.php';
$db = new config();
$conn = $db->connectDB();

// Get profile image
$citizenId = $user['id'];
$stmt = $conn->prepare("SELECT image FROM profile WHERE citizen_id = ?");
$stmt->bind_param("i", $citizenId);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

$userImage = !empty($profile['image']) ? $profile['image'] : 'default.png';

// Count services
$serviceCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM service");
if ($row = $result->fetch_assoc()) {
    $serviceCount = $row['total'];
}

// Count feedback submitted by this citizen
$feedbackCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM service_feedback WHERE citizen_id = ?");
$stmt->bind_param("i", $citizenId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $feedbackCount = $row['total'];
}

// Count responses received
$responseCount = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM feedback_response fr 
    JOIN service_feedback sf ON fr.feedback_id = sf.id 
    WHERE sf.citizen_id = ?
");
$stmt->bind_param("i", $citizenId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $responseCount = $row['total'];
}

// Get feedback responses for this citizen
$feedbackResponses = [];
$responseQuery = "
    SELECT fr.*, s.name as service_name, st.username as staff_username,
           CONCAT(sp.firstname, ' ', sp.lastname) as staff_name
    FROM feedback_response fr
    JOIN service_feedback sf ON fr.feedback_id = sf.id
    JOIN service s ON sf.service_id = s.id
    JOIN staff st ON fr.staff_id = st.id
    LEFT JOIN staff_profile sp ON st.id = sp.staff_id
    WHERE sf.citizen_id = ?
    ORDER BY fr.created_at DESC
    LIMIT 5
";
$stmt = $conn->prepare($responseQuery);
$stmt->bind_param("i", $citizenId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $feedbackResponses[] = $row;
}

// Get recent feedback submitted by this citizen
$recentFeedback = [];
$feedbackQuery = "
    SELECT sf.*, s.name as service_name,
           (SELECT COUNT(*) FROM feedback_response WHERE feedback_id = sf.id) as response_count
    FROM service_feedback sf
    JOIN service s ON sf.service_id = s.id
    WHERE sf.citizen_id = ?
    ORDER BY sf.create DESC
    LIMIT 5
";
$stmt = $conn->prepare($feedbackQuery);
$stmt->bind_param("i", $citizenId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recentFeedback[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Citizen Dashboard</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="font/css2.css">

  <!-- Font Awesome & AdminLTE -->
  <link rel="stylesheet" href="css/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">

  <link rel="stylesheet" href="css/citizen_dashboard.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="sidebar">
      <!-- User Panel -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
        <div class="image">
          <img src="citizen_image/<?= htmlspecialchars($userImage); ?>" 
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
            <a href="citizen_dashboard.php" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <!-- Profile -->
          <li class="nav-item">
              <a href="citizen/citizen_profile.php" class="nav-link">
                  <i class="nav-icon fas fa-user"></i>
                  <p>Profile</p>
              </a>
          </li>
          <!-- Services -->
          <li class="nav-item">
            <a href="citizen/citizen_service.php" class="nav-link">
              <i class="nav-icon fas fa-concierge-bell"></i>
              <p>Services</p>
            </a>
          </li>

          <!-- Feedback -->
          <li class="nav-item">
            <a href="citizen/citizen_feedback.php" class="nav-link">
              <i class="nav-icon fas fa-comments"></i>
              <p>Feedback</p>
            </a>
          </li>

          <!-- Logout -->
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

  <!-- Overlay for mobile sidebar -->
  <div class="sidebar-overlay"></div>

  <!-- Main Content -->
  <div class="content-wrapper">
    <section class="content pt-4">
      <div class="container-fluid">
        <!-- Stats Cards - All Clickable -->
        <div class="row">
          <!-- Total Services -->
          <div class="col-lg-3 col-6">
            <a href="citizen/citizen_service.php" class="card-link">
              <div class="small-box bg-gradient-info text-white">
                <div class="inner">
                  <h3><?= $serviceCount; ?></h3>
                  <p>Total Services</p>
                </div>
                <div class="icon">
                  <i class="fas fa-concierge-bell"></i>
                </div>
              </div>
            </a>
          </div>

          <!-- Your Feedback -->
          <div class="col-lg-3 col-6">
            <a href="citizen/citizen_feedback.php" class="card-link">
              <div class="small-box bg-gradient-success text-white">
                <div class="inner">
                  <h3><?= $feedbackCount; ?></h3>
                  <p>Your Feedback</p>
                </div>
                <div class="icon">
                  <i class="fas fa-comments"></i>
                </div>
              </div>
            </a>
          </div>

          <!-- Responses Received -->
          <div class="col-lg-3 col-6">
            <a href="citizen/citizen_feedback.php" class="card-link">
              <div class="small-box bg-gradient-warning text-white">
                <div class="inner">
                  <h3><?= $responseCount; ?></h3>
                  <p>Responses Received</p>
                </div>
                <div class="icon">
                  <i class="fas fa-reply"></i>
                </div>
              </div>
            </a>
          </div>

          <!-- Recent Activities -->
          <div class="col-lg-3 col-6">
            <a href="citizen/citizen_feedback.php" class="card-link">
              <div class="small-box bg-gradient-primary text-white">
                <div class="inner">
                  <h3><?= count($recentFeedback); ?></h3>
                  <p>Recent Activities</p>
                </div>
                <div class="icon">
                  <i class="fas fa-clock"></i>
                </div>
              </div>
            </a>
          </div>
        </div>
        
        <!-- Recent Feedback and Responses Section -->
        <div class="row mt-4">
          <!-- Recent Feedback Submitted -->
          <div class="col-md-6">
            <div class="card">
              <div class="card-header bg-gradient-primary">
                <h3 class="card-title"><i class="fas fa-comment mr-2"></i> Your Recent Feedback</h3>
                <div class="card-tools">
                  <a href="citizen/citizen_feedback.php" class="btn btn-sm btn-light">View All</a>
                </div>
              </div>
              <div class="card-body">
                <?php if (!empty($recentFeedback)): ?>
                  <?php foreach ($recentFeedback as $feedback): ?>
                    <div class="feedback-card" style="position: relative;">
                      <?php if ($feedback['response_count'] > 0): ?>
                        <span class="response-badge" title="Has response">
                          <i class="fas fa-reply"></i>
                        </span>
                      <?php endif; ?>
                      
                      <div class="card-header-custom">
                        <span class="service-name">
                          <?php if (!empty($feedback['service_icon'])): ?>
                            <img src="service_icons/<?= htmlspecialchars($feedback['service_icon']) ?>" 
                                 alt="<?= htmlspecialchars($feedback['service_name']) ?>" 
                                 class="service-icon">
                          <?php endif; ?>
                          <?= htmlspecialchars($feedback['service_name']) ?>
                        </span>
                        <span class="feedback-date">
                          <i class="far fa-clock mr-1"></i>
                          <?= date('M j, Y', strtotime($feedback['create'])) ?>
                        </span>
                      </div>
                      <div class="card-body-custom">
                        <?php if (!empty($feedback['rating'])): ?>
                          <div class="rating-stars">
                            <?php
                            $rating = $feedback['rating'];
                            for ($i = 1; $i <= 5; $i++):
                              if ($i <= $rating):
                                echo '<i class="fas fa-star"></i>';
                              else:
                                echo '<i class="far fa-star"></i>';
                              endif;
                            endfor;
                            ?>
                            <span class="ml-1">(<?= $rating ?>/5)</span>
                          </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($feedback['feedback_text'])): ?>
                          <div class="feedback-text">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($feedback['feedback_text'])) ?></p>
                          </div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                          <small class="text-muted">
                            Status: 
                            <span class="badge badge-<?= 
                              $feedback['response_count'] > 0 ? 'success' : 'warning' 
                            ?>">
                              <?= $feedback['response_count'] > 0 ? 'Responded' : 'Pending' ?>
                            </span>
                          </small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="no-data">
                    <i class="fas fa-comment-slash"></i>
                    <p>You haven't submitted any feedback yet</p>
                    <a href="citizen/citizen_feedback.php" class="btn btn-primary mt-2">
                      <i class="fas fa-comment mr-1"></i> Submit Your First Feedback
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Feedback Responses -->
          <div class="col-md-6">
            <div class="card">
              <div class="card-header bg-gradient-success">
                <h3 class="card-title"><i class="fas fa-reply mr-2"></i> Responses to Your Feedback</h3>
                <div class="card-tools">
                  <a href="citizen/citizen_feedback.php" class="btn btn-sm btn-light">View All Feedback</a>
                </div>
              </div>
              <div class="card-body">
                <?php if (!empty($feedbackResponses)): ?>
                  <?php foreach ($feedbackResponses as $response): ?>
                    <div class="response-card">
                      <div class="card-header-custom">
                        <span class="service-name"><?= htmlspecialchars($response['service_name']) ?></span>
                        <span class="response-date">
                          <i class="far fa-clock mr-1"></i>
                          <?= date('M j, Y', strtotime($response['created_at'])) ?>
                        </span>
                      </div>
                      <div class="card-body-custom">
                        <p><?= nl2br(htmlspecialchars($response['response_text'])) ?></p>
                        <div class="staff-name">
                          <i class="fas fa-user-tie mr-1"></i>
                          Response by: 
                          <?= !empty($response['staff_name']) ? 
                              htmlspecialchars($response['staff_name']) : 
                              htmlspecialchars($response['staff_username']) ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="no-data">
                    <i class="fas fa-comment-dots"></i>
                    <p>No responses to your feedback yet</p>
                    <small class="text-muted">You'll see responses here when staff members reply to your feedback</small>
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
    <strong>© <?= date('Y') ?> Cadiz City Government. All rights reserved.</strong>
  </footer>
</div>

<!-- JS Libraries -->
<script src="js/jquery/jquery.min.js"></script>
<script src="js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/adminlte/adminlte.min.js"></script>

<script src="js/citizen_dasboard.js"></script>
</body>
</html>