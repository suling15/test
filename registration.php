<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Citizen Registration</title>
  <link rel="stylesheet" href="css/sweetalert/sweetalert2.min.css">
  <link rel="stylesheet" href="css/css/all.min.css">
  <link rel="stylesheet" href="css/registration_citizen.css">
</head>
<body>
  <div class="language-switcher">
    <button class="lang-btn active" data-lang="tagalog">Tagalog</button>
    <button class="lang-btn" data-lang="english">English</button>
  </div>
  
  <div class="form-wrapper">
    <div class="mobile-header">
      <div class="mobile-header-content">
        <a href="index.php" class="back-button">
          <i class="fas fa-arrow-left"></i> <span class="lang-back">Back</span>
        </a>
        <h1 class="lang-title">Citizen Registration</h1>
        <p class="form-subtitle lang-subtitle">Lumikha ng iyong account para ma-access ang mga serbisyo ng gobyerno</p>
        
        <div class="mobile-language-switcher">
          <button class="lang-btn active" data-lang="tagalog">Tagalog</button>
          <button class="lang-btn" data-lang="english">English</button>
        </div>
        
        <div class="mobile-step-indicator">
          <div class="mobile-step active">
            <div class="mobile-step-number">1</div>
            <div class="mobile-step-text lang-step1">Personal na Impormasyon</div>
          </div>
          <div class="mobile-step active">
            <div class="mobile-step-number">2</div>
            <div class="mobile-step-text lang-step2">Pagkakakilanlan</div>
          </div>
          <div class="mobile-step active">
            <div class="mobile-step-number">3</div>
            <div class="mobile-step-text lang-step3">Pag-setup ng Account</div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="form-sidebar">
      <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i> <span class="lang-back">Back</span>
      </a>
      <div class="sidebar-content">
        <h1 class="lang-title">Pagpaparehistro ng Mamamayan</h1>
        <p class="form-subtitle lang-subtitle">Lumikha ng iyong account para ma-access ang mga serbisyo ng gobyerno</p>
        
        <div class="step-indicator">
          <div class="step active">
            <div class="step-number">1</div>
            <div class="step-text lang-step1">Personal na Impormasyon</div>
          </div>
          <div class="step active">
            <div class="step-number">2</div>
            <div class="step-text lang-step2">Pagkakakilanlan</div>
          </div>
          <div class="step active">
            <div class="step-number">3</div>
            <div class="step-text lang-step3">Pag-setup ng Account</div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="form-content">
      <form id="registerForm" enctype="multipart/form-data">
        <div class="section-title">
          <div class="section-icon"><i class="fas fa-user"></i></div>
          <span class="lang-section1">Personal na Impormasyon</span>
        </div>
        
        <div class="form-grid">
          <div class="form-group">
            <label for="firstname" class="required lang-firstname-label">Unang Pangalan</label>
            <div class="input-wrapper">
              <i class="fas fa-user input-icon"></i>
              <input type="text" id="firstname" name="firstname" class="form-control" placeholder="First name" required>
            </div>
          </div>

          <div class="form-group">
            <label for="middlename" class="lang-middlename-label">Gitnang Pangalan</label>
            <div class="input-wrapper">
              <i class="fas fa-user input-icon"></i>
              <input type="text" id="middlename" name="middlename" class="form-control" placeholder="Middle name">
            </div>
          </div>

          <div class="form-group">
            <label for="lastname" class="required lang-lastname-label">Apelyido</label>
            <div class="input-wrapper">
              <i class="fas fa-user input-icon"></i>
              <input type="text" id="lastname" name="lastname" class="form-control" placeholder="Last name" required>
            </div>
          </div>

          <div class="form-group">
            <label for="birthday" class="required lang-birthday-label">Petsa ng Kapanganakan</label>
            <div class="input-wrapper">
              <i class="fas fa-birthday-cake input-icon"></i>
              <input type="date" id="birthday" name="birthday" class="form-control" required>
            </div>
          </div>

          <div class="form-group">
            <label for="gender" class="required lang-gender-label">Kasarian</label>
            <div class="input-wrapper">
              <i class="fas fa-venus-mars input-icon"></i>
              <select id="gender" name="gender" class="form-control" required>
                <option value="">Select</option>
                <option value="Male" class="lang-gender-male">Lalaki</option>
                <option value="Female" class="lang-gender-female">Babae</option>
                <option value="Other" class="lang-gender-other">Iba pa</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="civil_status" class="required lang-civil-label">Katayuang Sibil</label>
            <div class="input-wrapper">
              <i class="fas fa-heart input-icon"></i>
              <select id="civil_status" name="civil_status" class="form-control" required>
                <option value="">Select</option>
                <option value="Single" class="lang-civil-single">Single</option>
                <option value="Married" class="lang-civil-married">Married</option>
                <option value="Widowed" class="lang-civil-widowed">Widowed</option>
                <option value="Divorced" class="lang-civil-divorced">Divorced</option>
                <option value="Separated" class="lang-civil-separated">Separated</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="contact_number" class="lang-contact-label">Contact Number</label>
            <div class="input-wrapper">
              <i class="fas fa-phone input-icon"></i>
              <input type="tel" id="contact_number" name="contact_number" class="form-control" placeholder="Contact number">
            </div>
          </div>

          <div class="form-group" style="grid-column: span 2;">
            <label for="address" class="lang-address-label">Address</label>
            <div class="input-wrapper">
              <i class="fas fa-map-marker-alt input-icon"></i>
              <input type="text" id="address" name="address" class="form-control" placeholder="Complete address">
            </div>
          </div>
        </div>

        <div class="divider"></div>

        <div class="section-title">
          <div class="section-icon"><i class="fas fa-id-card"></i></div>
          <span class="lang-section2">Pagkakakilanlan at Account</span>
        </div>

        <div class="form-grid">
          <div class="form-group full-width">
            <label class="required lang-id-label">Wastong ID (Inisyu ng Gobyerno)</label>
            <div class="file-upload-area" id="fileUploadArea">
              <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
              <p class="file-upload-text lang-upload-text"><strong>Click to upload</strong> or drag and drop (Max 5MB)</p>
              <input type="file" class="file-input" id="valid_id" name="valid_id" accept="image/*,.pdf" required>
            </div>
            <div class="file-selected" id="fileSelected">
              <i class="fas fa-file-alt"></i>
              <span class="file-name" id="fileName"></span>
              <button type="button" class="file-remove" id="fileRemove">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label for="username" class="required lang-username-label">Username</label>
            <div class="input-wrapper">
              <i class="fas fa-user-circle input-icon"></i>
              <input type="text" id="username" name="username" class="form-control" placeholder="Choose username" required>
            </div>
          </div>

          <div class="form-group">
            <label for="password" class="required lang-password-label">Password</label>
            <div class="input-wrapper">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" id="password" name="password" class="form-control" placeholder="Create password (min. 8 characters)" required minlength="8">
            </div>
            <div class="password-requirements">
              <small id="passwordRequirement" class="lang-password-requirement">Dapat na hindi bababa sa 8 character ang haba ng password</small>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-submit lang-submit-btn" id="submitBtn">Lumikha ng Account</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/sweetalert/sweetalert2@11.js"></script>
  <script src="js/registration_citizen.js"></script>
</body>
</html>