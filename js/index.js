document.addEventListener("DOMContentLoaded", () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    // Mobile menu functionality
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function () {
            this.classList.toggle('active');
            navLinks.classList.toggle('active');

            // Prevent body scroll when menu is open
            document.body.style.overflow = this.classList.contains('active') ? 'hidden' : '';
        });

        // Close mobile menu when clicking on a nav link (except dropdown button)
        document.querySelectorAll('.nav-links a:not(.dropbtn)').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                navLinks.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }

    // Dropdown functionality
    document.querySelectorAll('.dropbtn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('show');
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-content').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    });

    // Prevent dropdown from closing when clicking inside
    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
        dropdown.addEventListener('click', e => e.stopPropagation());
    });

    // Modal functionality
    document.querySelectorAll('.login-option').forEach(option => {
        option.addEventListener('click', function (e) {
            e.preventDefault();
            const role = this.getAttribute('data-role');
            const modal = document.getElementById(role + 'Modal');
            if (modal) {
                modal.style.display = 'block';
                // Trigger reflow to enable transition
                modal.offsetHeight;
                modal.classList.add('show');
            }

            // Close mobile menu if open
            if (menuToggle && navLinks) {
                menuToggle.classList.remove('active');
                navLinks.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close modals
    document.querySelectorAll('.close-button').forEach(button => {
        button.addEventListener('click', function () {
            const modal = this.closest('.modal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function (e) {
        document.querySelectorAll('.modal').forEach(modal => {
            if (e.target === modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
        });
    });

    // Form submission
    document.querySelectorAll('.login-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const role = this.getAttribute('data-role');
            const formData = new FormData(this);
            formData.append('role', role);

            fetch('connection/login.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Logged in successfully!',
                            text: data.message
                        }).then(() => {
                            window.location.href = `${role}_dashboard.php`;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again.'
                    });
                });
        });
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && menuToggle && navLinks) {
            menuToggle.classList.remove('active');
            navLinks.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Preload images to prevent flickering
    window.addEventListener('load', () => {
        document.querySelectorAll('img').forEach(img => {
            img.style.opacity = 1;
        });
    });

    // Load services from database
    loadServices();
});

// Load services from database
function loadServices() {
    const servicesContainer = document.getElementById('services-container');
    
    fetch('connection/get-services.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                servicesContainer.innerHTML = '';
                
                data.services.forEach(service => {
                    const serviceCard = document.createElement('div');
                    serviceCard.className = 'service-card';
                    
                    // FIXED: Handle image path correctly
                    let imagePath = 'placeholder-image.jpg'; // Default placeholder
                    
                    if (service.image) {
                        // Extract just the filename in case full path was stored
                        const imageFilename = service.image.split('/').pop().split('\\').pop();
                        imagePath = `uploads/services_image/${imageFilename}`;
                    }
                    
                    serviceCard.innerHTML = `
                        <div class="service-logo">
                            <img src="${imagePath}" alt="${service.name} Logo" onerror="this.src='placeholder-image.jpg'">
                        </div>
                        <h3 class="service-title">${service.name}</h3>
                        <p class="service-description">${service.description}</p>
                    `;
                    
                    servicesContainer.appendChild(serviceCard);
                });
            } else {
                servicesContainer.innerHTML = '<p>Error loading services. Please try again later.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading services:', error);
            servicesContainer.innerHTML = '<p>Error loading services. Please try again later.</p>';
        });
}