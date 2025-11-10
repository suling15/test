
    // Language data
    const languages = {
      tagalog: {
        back: "Back",
        title: "Pagpaparehistro ng Mamamayan",
        subtitle: "Lumikha ng iyong account para ma-access ang mga serbisyo ng gobyerno",
        step1: "Personal na Impormasyon",
        step2: "Pagkakakilanlan",
        step3: "Pag-setup ng Account",
        section1: "Personal na Impormasyon",
        section2: "Pagkakakilanlan at Account",
        firstnameLabel: "Unang Pangalan",
        middlenameLabel: "Gitnang Pangalan",
        lastnameLabel: "Apelyido",
        birthdayLabel: "Petsa ng Kapanganakan",
        genderLabel: "Kasarian",
        genderMale: "Lalaki",
        genderFemale: "Babae",
        genderOther: "Iba pa",
        civilLabel: "Katayuang Sibil",
        civilSingle: "Single",
        civilMarried: "Married",
        civilWidowed: "Widowed",
        civilDivorced: "Divorced",
        civilSeparated: "Separated",
        contactLabel: "Contact Number",
        addressLabel: "Address",
        idLabel: "Wastong ID (Inisyu ng Gobyerno)",
        uploadText: "<strong>Click to upload</strong> or drag and drop (Max 5MB)",
        usernameLabel: "Username",
        passwordLabel: "Password",
        passwordRequirement: "Dapat na hindi bababa sa 8 character ang haba ng password",
        submitBtn: "Lumikha ng Account"
      },
      english: {
        back: "Back",
        title: "Citizen Registration",
        subtitle: "Create your account to access government services",
        step1: "Personal Information",
        step2: "Identification",
        step3: "Account Setup",
        section1: "Personal Information",
        section2: "Identification and Account",
        firstnameLabel: "First Name",
        middlenameLabel: "Middle Name",
        lastnameLabel: "Last Name",
        birthdayLabel: "Date of Birth",
        genderLabel: "Gender",
        genderMale: "Male",
        genderFemale: "Female",
        genderOther: "Other",
        civilLabel: "Civil Status",
        civilSingle: "Single",
        civilMarried: "Married",
        civilWidowed: "Widowed",
        civilDivorced: "Divorced",
        civilSeparated: "Separated",
        contactLabel: "Contact Number",
        addressLabel: "Address",
        idLabel: "Valid ID (Government Issued)",
        uploadText: "<strong>Click to upload</strong> or drag and drop (Max 5MB)",
        usernameLabel: "Username",
        passwordLabel: "Password",
        passwordRequirement: "Password must be at least 8 characters long",
        submitBtn: "Create Account"
      }
    };

    // Language switching functionality
    let currentLang = 'tagalog';
    
    function switchLanguage(lang) {
      currentLang = lang;
      
      // Update all elements with language classes
      Object.keys(languages[lang]).forEach(key => {
        const elements = document.querySelectorAll(`.lang-${key}`);
        elements.forEach(element => {
          if (key === 'uploadText') {
            element.innerHTML = languages[lang][key];
          } else {
            element.textContent = languages[lang][key];
          }
        });
      });
      
      // Update active button state
      document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.lang === lang) {
          btn.classList.add('active');
        }
      });
      
      // Update placeholders
      document.getElementById('firstname').placeholder = lang === 'tagalog' ? 'First name' : 'First name';
      document.getElementById('middlename').placeholder = lang === 'tagalog' ? 'Middle name' : 'Middle name';
      document.getElementById('lastname').placeholder = lang === 'tagalog' ? 'Last name' : 'Last name';
      document.getElementById('contact_number').placeholder = lang === 'tagalog' ? 'Contact number' : 'Contact number';
      document.getElementById('address').placeholder = lang === 'tagalog' ? 'Complete address' : 'Complete address';
      document.getElementById('username').placeholder = lang === 'tagalog' ? 'Choose username' : 'Choose username';
      document.getElementById('password').placeholder = lang === 'tagalog' ? 'Create password (min. 8 characters)' : 'Create password (min. 8 characters)';
      
      // Update select placeholder
      const genderSelect = document.getElementById('gender');
      const civilSelect = document.getElementById('civil_status');
      
      if (genderSelect.options[0]) {
        genderSelect.options[0].textContent = lang === 'tagalog' ? 'Select' : 'Select';
      }
      if (civilSelect.options[0]) {
        civilSelect.options[0].textContent = lang === 'tagalog' ? 'Select' : 'Select';
      }
    }

    // Initialize language buttons
    document.querySelectorAll('.lang-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        switchLanguage(btn.dataset.lang);
      });
    });

    // File upload functionality
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('valid_id');
    const fileSelected = document.getElementById('fileSelected');
    const fileName = document.getElementById('fileName');
    const fileRemove = document.getElementById('fileRemove');

    fileUploadArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', updateFileDisplay);
    fileRemove.addEventListener('click', (e) => {
      e.stopPropagation();
      fileInput.value = '';
      fileSelected.classList.remove('show');
    });

    function updateFileDisplay() {
      if (fileInput.files.length > 0) {
        fileName.textContent = fileInput.files[0].name;
        fileSelected.classList.add('show');
      }
    }

    // Real-time password validation
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const requirement = document.getElementById('passwordRequirement');
      
      if (password.length > 0 && password.length < 8) {
        requirement.style.color = '#ef4444';
        requirement.innerHTML = currentLang === 'tagalog' 
          ? 'Password must be at least 8 characters long' 
          : 'Password must be at least 8 characters long';
      } else if (password.length >= 8) {
        requirement.style.color = '#10b981';
        requirement.innerHTML = currentLang === 'tagalog' 
          ? '✓ Password meets requirements' 
          : '✓ Password meets requirements';
      } else {
        requirement.style.color = '#64748b';
        requirement.innerHTML = currentLang === 'tagalog' 
          ? 'Dapat na hindi bababa sa 8 character ang haba ng password' 
          : 'Password must be at least 8 characters long';
      }
    });

    document.getElementById('registerForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const submitBtn = document.getElementById('submitBtn');
      const password = document.getElementById('password').value;
      
      // Password validation
      if (password.length < 8) {
        const errorMsg = currentLang === 'tagalog' 
          ? 'Password must be at least 8 characters long' 
          : 'Password must be at least 8 characters long';
        Swal.fire('Error', errorMsg, 'error');
        return;
      }
      
      if (fileInput.files[0] && fileInput.files[0].size > 5 * 1024 * 1024) {
        const errorMsg = currentLang === 'tagalog' 
          ? 'File size must be less than 5MB' 
          : 'File size must be less than 5MB';
        Swal.fire('Error', errorMsg, 'error');
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = currentLang === 'tagalog' ? 'Creating Account...' : 'Creating Account...';

      fetch('connection/registration_citizen.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(text => {
        try {
          const data = JSON.parse(text);
          Swal.fire({
            icon: data.status === 'success' ? 'success' : 'error',
            title: data.status === 'success' ? 'Success' : 'Error',
            text: data.message,
            confirmButtonColor: '#667eea'
          }).then(() => {
            if (data.status === 'success') {
              window.location.href = 'index.php';
            }
          });
        } catch (e) {
          console.error('Invalid JSON:', text);
          const errorMsg = currentLang === 'tagalog' 
            ? 'Invalid response from server.' 
            : 'Invalid response from server.';
          Swal.fire('Oops!', errorMsg, 'error');
        }
      })
      .catch(err => {
        console.error(err);
        const errorMsg = currentLang === 'tagalog' 
          ? 'Something went wrong.' 
          : 'Something went wrong.';
        Swal.fire('Oops!', errorMsg, 'error');
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = currentLang === 'tagalog' ? 'Lumikha ng Account' : 'Create Account';
      });
    });
  