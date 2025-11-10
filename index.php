<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Cadiz City</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

    <header class="header">
        <div class="logo-mobile">CADIZ CITY</div>
        <button class="menu-toggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="nav-links">
            <a href="#" class="active"><i class="fas fa-home"></i> Home</a>
            <a href="#about-cadiz"><i class="fas fa-info-circle"></i> About us</a>
            <div class="dropdown">
                <button class="dropbtn">
                    <i class="fas fa-user"></i> Login ▾
                </button>
                <div class="dropdown-content">
                    <a href="#" class="login-option" data-role="admin">Admin</a>
                    <a href="#" class="login-option" data-role="staff">Staff</a>
                    <a href="#" class="login-option" data-role="citizen">Citizen</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero-section">
            <img src="https://images.unsplash.com/photo-1587339277936-2c3e370a5988?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80" class="background-image" alt="Cadiz City Hall">
            <div class="overlay-content">
                <div class="logo-and-text-container">
                    <div class="logo-container">
                        <div class="logo-placeholder">
                            <!-- Logo would be placed here -->
                        </div>
                    </div>
                    <div class="republic">REPUBLIC OF THE PHILIPPINES</div>
                    <h1 class="city-name">Cadiz City</h1>
                    <div class="province">Province of Negros Occidental</div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section">
            <div class="services-background"></div>
            <div class="services-content">
                <h2 class="section-title">Our Services</h2>
                <div class="services-container" id="services-container">
                    <!-- Services will be dynamically loaded here -->
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </section>
    </main>

       <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-section" id="about-cadiz">
                <h3>About Cadiz City</h3>
                <p>The city government is committed to providing efficient public services, promoting sustainable development, and improving the quality of life for all Cadiznons.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>City Hall Building, Burgos Street, Cadiz City, Negros Occidental, Philippines 6121</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>(034) 4931-772 / 4450-117</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="http://cadizcity.gov.ph/" target="_blank" class="external-link">cadizcity.gov.ph</a>
                    </li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Emergency Hotlines</h3>
                <div class="hotline-item">
                    <span class="hotline-name">PNP HOTLINE </span>
                    <span class="hotline-number"> (034) 4930-166</span>
                </div>
                <div class="hotline-item">
                    <span class="hotline-name">DRRMO HOTLINE </span>
                    <span class="hotline-number"> 0909-140-6322</span>
                </div>
                <div class="hotline-item">
                    <span class="hotline-name">TEL DRRMO HOTLINE </span>
                    <span class="hotline-number">– (034) 493-1787 / (023) 4354-639</span>
                </div>
                <div class="hotline-item">
                    <span class="hotline-name">CITY HEALTH OFFICE HOTLINE </span>
                    <span class="hotline-number"> (034) 4931-772 / 4450-117</span>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Government Links</h3>
                <ul>
                    <li><a href="https://op-proper.gov.ph/"><i class="fas fa-chevron-right"></i> Office of the President</a></li>
                    <li><a href="https://www.ovp.gov.ph/"><i class="fas fa-chevron-right"></i> Office of the Vice President</a></li>
                    <li><a href="https://web.senate.gov.ph/"><i class="fas fa-chevron-right"></i> Senate of the Philippines</a></li>
                    <li><a href="https://www.congress.gov.ph/"><i class="fas fa-chevron-right"></i> House of Representatives</a></li>
                    <li><a href="https://sc.judiciary.gov.ph/"><i class="fas fa-chevron-right"></i>Supreme Court</a></li>
                    <li><a href="https://ca.judiciary.gov.ph/"><i class="fas fa-chevron-right"></i> Court of Appeals</a></li>
                    <li><a href="https://sb.judiciary.gov.ph/"><i class="fas fa-chevron-right"></i> Sandiganbayan</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <strong>© 2023 CADIZ CITY. All rights reserved.</strong>
        </div>
    </footer>   

    <!-- Login Modals -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Admin Login</h2>
            <form class="login-form" data-role="admin">
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>

                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
            </form>
        </div>
    </div>

    <div id="staffModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Staff Login</h2>
            <form class="login-form" data-role="staff">
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>

                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
            </form>
        </div>
    </div>

    <div id="citizenModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Citizen Login</h2>
            <form class="login-form" data-role="citizen">
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>

                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
                <a href="registration.php" style="display: block; text-align: center; margin-top: 15px; font-size: 14px; color: #0776E5;">
                    Gumawa ng Bagong Account
                </a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/index.js"></script>

</body>
</html>