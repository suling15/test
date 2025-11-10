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

// Handle month/year filter
$currentYear = date('Y');
$currentMonth = date('m');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : $currentMonth;

// Validate month and year
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = $currentMonth;
if ($selectedYear < 2020 || $selectedYear > $currentYear + 1) $selectedYear = $currentYear;

// Generate month options
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Convert selected month to integer to remove leading zeros
$selectedMonth = intval($selectedMonth);

// Generate year options (last 5 years and next year)
$years = range($currentYear - 4, $currentYear + 1);

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
$isServices = ($current === 'citizen_services.php');
$isFeedback = ($current === 'citizen_feedback.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
  <title>Feedback</title>

  <link rel="stylesheet" href="../font/css2.css">
  <link rel="stylesheet" href="../css/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../css/sweetalert/sweetalert2.min.css">
  <link rel="stylesheet" href="../css/citizen/citizen_feedback.css">
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
        <!-- Filter Section -->
        <div class="filter-container mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">My Feedback</h4>
            <button class="btn btn-primary" data-toggle="modal" data-target="#feedbackModal">
              <i class="fas fa-plus"></i> Add Feedback
            </button>
          </div>
          
          <div class="filter-section">
            <h5 class="filter-title">
              <i class="fas fa-filter mr-2"></i>
              Filter Feedback
              <?php if ($selectedYear == $currentYear && $selectedMonth == $currentMonth): ?>
                <span class="current-month-badge">Current Month</span>
              <?php endif; ?>
            </h5>
            <form method="GET" class="filter-form">
              <div class="form-row align-items-center">
                <div class="col-auto">
                  <label class="filter-label">Month</label>
                  <select name="month" class="filter-select">
                    <?php foreach ($months as $num => $name): ?>
                      <option value="<?= $num ?>" <?= $selectedMonth == $num ? 'selected' : ''; ?>>
                        <?= $name; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-auto">
                  <label class="filter-label">Year</label>
                  <select name="year" class="filter-select">
                    <?php foreach ($years as $year): ?>
                      <option value="<?= $year; ?>" <?= $selectedYear == $year ? 'selected' : ''; ?>>
                        <?= $year; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-auto">
                  <button type="submit" class="btn btn-primary filter-btn">
                    <i class="fas fa-sync-alt mr-2"></i>Apply Filter
                  </button>
                  <?php if ($selectedYear != $currentYear || $selectedMonth != $currentMonth): ?>
                    <a href="citizen_feedback.php" class="btn btn-outline-secondary filter-btn">
                      <i class="fas fa-calendar-alt mr-2"></i>Current Month
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </form>
            <div class="mt-2 text-muted small">
              <i class="fas fa-info-circle"></i> Showing feedback for <?= $months[$selectedMonth]; ?> <?= $selectedYear; ?>
            </div>
          </div>
        </div>
        
        <div class="col">
          <div class="card">
            <div class="card-body">
              <!-- Feedback Cards Container -->
              <div class="feedback-cards-container" 
                   id="feedbackCards"
                   data-current-month="<?= $currentMonth ?>" 
                   data-current-year="<?= $currentYear ?>">
                <!-- Cards will be loaded via JavaScript -->
                <div class="text-center p-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                  </div>
                  <p class="mt-2">Loading feedback...</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Feedback Modal -->
  <div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="feedbackModalLabel">Magsumite ng Feedback</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="feedbackForm">
          <div class="modal-body">
            <!-- Service Selection -->
            <div class="form-section">
              <div class="form-group">
                <label class="form-section-title">Impormasyon sa Serbisyo</label>
                <select id="serviceSelect" class="form-control" required>
                  <option value="">Pumili ng mga serbisyo</option>
                  <!-- options loaded via JS -->
                </select>
              </div>
              <div class="form-group">
                <label>Service Offer</label>
                <select id="serviceOfferSelect" class="form-control" required>
                  <option value="">Pumili ng alok ng serbisyo</option>
                  <!-- options loaded via JS when service is selected -->
                </select>
              </div>
            </div>

            <!-- Feedback Text -->
            <div class="form-section">
              <div class="form-group">
                <label class="form-section-title">Mga Detalye ng Feedback</label>
                <textarea id="feedbackText" rows="4" class="form-control" placeholder="Write Your Feedback for services." required></textarea>
              </div>
            </div>
            
            <!-- Rating -->
            <div class="form-section">
              <div class="form-group">
                <label class="form-section-title">Rating</label><br>
                <div id="ratingStars">
                  <i class="far fa-star star" data-value="1"></i>
                  <i class="far fa-star star" data-value="2"></i>
                  <i class="far fa-star star" data-value="3"></i>
                  <i class="far fa-star star" data-value="4"></i>
                  <i class="far fa-star star" data-value="5"></i>
                </div>
                <input type="hidden" id="ratingValue" name="rating" value="0">
              </div>
            </div>

            <!-- Privacy Settings -->
            <div class="form-section">
              <div class="form-group">
                <label class="form-section-title">Mga Setting ng Privacy</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="is_anonymous" name="is_anonymous" value="1">
                  <label class="form-check-label" for="is_anonymous">
                    Isumite nang hindi nagpapakilala (Hindi ipapakita ang iyong pangalan sa staff)
                  </label>
                </div>
                <div class="privacy-info">
                  <small class="form-text">
                    <i class="fas fa-info-circle mr-1"></i>
                    Kung nilagyan ng check, ang iyong feedback ay ipapakita bilang "Anonymous Citizen" sa mga miyembro ng staff.
                    Itatala pa rin ang iyong pagkakakilanlan para sa aming mga talaan ngunit hindi ipapakita sa publiko.
                  </small>
                </div>
              </div>
            </div>
            
            <!-- CC Questions -->
            <div class="form-section">
              <div class="form-section">
                <label class="form-section-title" style="background-color: black; color: white; border: 1px solid black; padding: 5px; display: inline-block;">
                    INSTRUCTION: Piliin (⬤) ang iyong sagot sa Citizen's Charter (CC) Questions. Ang Citizen's Charter ay nasa opisyal na dokumento na sumasalamin sa mga serbisyo ng isang ahensya/opisina ng gobyerno kasama ang mga kinakailangan, bayad, at oras ng pagproseso nito bukod sa iba pa.
                </label>
              <div class="form-group">
                <label>CC1: Alin sa mga sumusunod ang pinakamahusay na naglalarawan sa iyong kamalayan sa Citizen's Charter (CC)?</label><br>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="CC1" id="cc1a" value="I know what a CC is and I saw this office's CC" required>
                  <label class="form-check-label" for="cc1a">I know what a CC is and I saw this office's CC</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="CC1" id="cc1b" value="I know what a CC is but I did NOT saw this office's CC">
                  <label class="form-check-label" for="cc1b">I know what a CC is but I did NOT saw this office's CC</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="CC1" id="cc1c" value="I learned of the CC only when I saw this office's CC">
                  <label class="form-check-label" for="cc1c">I learned of the CC only when I saw this office's CC</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="CC1" id="cc1d" value="I do not know what a CC is and I did not see one in this office">
                  <label class="form-check-label" for="cc1d">I do not know what a CC is and I did not see one in this office</label>
                </div>
              </div>

              <div class="form-group">
                <label>CC2: Kung alam mo ang CC (nasagot ang 1-3 sa CC1), masasabi mo ba na ang CC ng opisinang ito ay..?</label><br>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC2" value="Easy to see" required> Easy to see</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC2" value="Somewhat easy to see"> Somewhat easy to see</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC2" value="Difficult to see"> Difficult to see</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC2" value="Not visible at all"> Not visible at all</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC2" value="N/A"> N/A</div>
              </div>

              <div class="form-group">
                <label>CC3: Kung alam mo ang CC (Mga Sagot 1-3 sa CC1), gaano kalaki ang naitulong ng CC sa iyong transaksyon?</label><br>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC3" value="Helped very much" required> Helped very much</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC3" value="Somewhat helped"> Somewhat helped</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC3" value="Did not help"> Did not help</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="CC3" value="N/A"> N/A</div>
              </div>
            </div>

            <!-- SQD Ratings -->
<div class="form-section">
    <label class="form-section-title" style="background-color: black; color: white; border: 1px solid black; padding: 5px; display: inline-block;">
       INSTRUCTION: Para sa Service Quality Dimensions (SQD) 0-8, mangyaring piliin ang (⬤) sa bilog na pinakaangkop sa iyong sagot.
    </label>
    <p class="text-muted">1- Lubos na Hindi Sumasang-ayon, 2- Hindi Sumasang-ayon, 3- Hindi Sumasang-ayon o Hindi Sumasang-ayon, 4- Sumasang-ayon, 5- Lubos na Sumasang-ayon</p>
    
    <!-- Quick Rating Radio Buttons -->
    <div class="quick-rating-section mb-3 p-3 border rounded bg-light">
        <label class="font-weight-bold d-block mb-2">Quick Rate All SQD Questions:</label>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sqd_quick_rating" id="sqd_quick_1" value="1">
            <label class="form-check-label text-danger font-weight-bold" for="sqd_quick_1">1 - All Strongly Disagree</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sqd_quick_rating" id="sqd_quick_2" value="2">
            <label class="form-check-label text-warning font-weight-bold" for="sqd_quick_2">2 - All Disagree</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sqd_quick_rating" id="sqd_quick_3" value="3">
            <label class="form-check-label text-secondary font-weight-bold" for="sqd_quick_3">3 - All Neutral</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sqd_quick_rating" id="sqd_quick_4" value="4">
            <label class="form-check-label text-info font-weight-bold" for="sqd_quick_4">4 - All Agree</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sqd_quick_rating" id="sqd_quick_5" value="5" checked>
            <label class="form-check-label text-success font-weight-bold" for="sqd_quick_5">5 - All Strongly Agree</label>
        </div>
        <small class="form-text text-muted mt-1">
            <i class="fas fa-bolt mr-1"></i>Select any option above to quickly set ALL SQD questions (SQD0-SQD8) to the same rating
        </small>
    </div>
</div>
              <!-- Example for SQD0 -->
              <div class="form-group">
                <label>SQD0: Kuntento ako sa serbisyong na-avail ko</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD0" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD0" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD0" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD0" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD0" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD1: Gumugol ako ng makatwirang tagal ng oras para sa transaksyong ito</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD1" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD1" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD1" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD1" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD1" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD2: Sinunod ng opisina ang mga kinakailangan at hakbang ng transaksyon batay sa ibinigay na Impormasyon</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD2" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD2" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD2" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD2" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD2" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD3: Ang mga hakbang (kabilang ang pagbabayad) na kailangan kong gawin para sa transaksyong ito ay madali at simple</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD3" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD3" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD3" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD3" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD3" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD4: Madali akong nakahanap ng impormasyon tungkol sa aking transaksyon mula sa opisina o sa website nito</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD4" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD4" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD4" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD4" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD4" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD5: Nagbayad ako ng makatwirang halaga ng mga bayarin para sa aking transaksyon (kabilang ang mga dokumento, kung mayroon man)</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD5" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD5" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD5" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD5" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD5" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD6: Pakiramdam ko ang opisina ay patas sa lahat, o 'walang palakasan', sa aking transaksyon</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD6" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD6" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD6" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD6" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD6" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD7: Magalang akong tinatrato ng staff, at (kung hihingi ng tulong) ang staff ay matulungin</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD7" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD7" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD7" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD7" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD7" value="5"> 5</div>
              </div>
              
              <div class="form-group">
                <label>SQD8: Nakuha ko ang kailangan ko mula sa opisina ng gobyerno, o (kung tinanggihan) ang pagtanggi ay sapat na ipinaliwanag sa akin</label><br>
                <div class="form-check form-check-inline"><input type="radio" name="SQD8" value="1" required> 1</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD8" value="2"> 2</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD8" value="3"> 3</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD8" value="4"> 4</div>
                <div class="form-check form-check-inline"><input type="radio" name="SQD8" value="5"> 5</div>
              </div>
            </div>
            <div class="alert alert-info" role="alert" style="font-size: 14px; margin-bottom: 15px;">
              <strong>Note:</strong> After clicking the <strong>Submit Feedback</strong> button, your feedback will be recorded and cannot be edited later. Please review your answers carefully.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitFeedback">Submit Feedback</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="main-footer text-right">
    <strong>© <?= date('Y') ?> CADIZ CITY. All rights reserved.</strong>
  </footer>
</div>

<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/adminlte/adminlte.min.js"></script>
<script src="../js/sweetalert/sweetalert2.all.min.js"></script>

<script src="../js/citizen_feedback.js"></script>
</body>
</html>