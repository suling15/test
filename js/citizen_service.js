
  $(document).ready(function() {
    // Initialize AdminLTE sidebar
    $('[data-widget="pushmenu"]').PushMenu('collapse');
    
    // Search functionality
    const searchInput = $('#searchInput');
    const clearSearch = $('#clearSearch');
    const searchForm = $('#searchForm');
    
    // Show/hide clear button based on input
    function toggleClearButton() {
      if (searchInput.val().length > 0) {
        clearSearch.show();
      } else {
        clearSearch.hide();
      }
    }
    
    // Initialize clear button visibility
    toggleClearButton();
    
    // Toggle clear button on input
    searchInput.on('input', toggleClearButton);
    
    // Clear search
    clearSearch.on('click', function() {
      searchInput.val('');
      toggleClearButton();
      searchInput.focus();
    });
    
    // Prevent empty search submission
    searchForm.on('submit', function(e) {
      if (searchInput.val().trim() === '') {
        e.preventDefault();
        window.location.href = 'citizen_service.php';
      }
    });
    
    // Auto-focus search input if there's a search term
    if (searchInput.val().length > 0) {
      searchInput.focus();
    }
    
    // Mobile-specific adjustments
    function adjustMobileLayout() {
      if ($(window).width() < 768) {
        // Ensure sidebar is collapsed on mobile
        $('body').addClass('sidebar-collapse');
        // Make sure the overlay is working
        $('.sidebar-overlay').click(function() {
          $('body').removeClass('sidebar-open');
        });
      } else {
        // Expand sidebar on larger screens
        $('body').removeClass('sidebar-collapse');
      }
    }
    
    // Run on load and resize
    adjustMobileLayout();
    $(window).resize(adjustMobileLayout);
    
    // Fix for sidebar links on mobile
    $(document).on('click', '.nav-sidebar a', function(e) {
      // If on mobile and sidebar is open, close it after clicking a link
      if ($(window).width() < 768 && $('body').hasClass('sidebar-open')) {
        $('body').removeClass('sidebar-open');
        $('body').addClass('sidebar-collapse');
      }
    });
  });

$(document).ready(function() {
    // Get CSRF token from the meta tag
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // View Service
    $(document).on('click', '.view-service', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: '../connection/citizen_service.php',
            method: 'GET',
            data: { 
                action: 'get_service', 
                id: id,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const service = response.data;
                    const offers = response.offers || [];
                    
                    let imageHtml = '<i class="fas fa-image fa-5x text-muted"></i>';
                    
                    if (service.image) {
                        imageHtml = `<img src="${service.image}" class="img-fluid" style="max-height: 200px; border-radius: 8px;" alt="${service.name}">`;
                    }
                    
                    // Build contact and location info for modal
                    let contactInfo = '';
                    if (service.contact_number) {
                        contactInfo = `
                            <div class="service-info-item mb-2">
                                <i class="fas fa-phone text-primary mr-2"></i>
                                <strong>Contact Number:</strong> ${service.contact_number}
                            </div>
                        `;
                    } else {
                        contactInfo = `
                            <div class="service-info-item mb-2">
                                <i class="fas fa-phone text-muted mr-2"></i>
                                <strong>Contact Number:</strong> <span class="text-muted">Not specified</span>
                            </div>
                        `;
                    }
                    
                    let locationInfo = '';
                    if (service.location) {
                        locationInfo = `
                            <div class="service-info-item mb-3">
                                <i class="fas fa-map-marker-alt text-danger mr-2"></i>
                                <strong>Location:</strong> ${service.location}
                            </div>
                        `;
                    } else {
                        locationInfo = `
                            <div class="service-info-item mb-3">
                                <i class="fas fa-map-marker-alt text-muted mr-2"></i>
                                <strong>Location:</strong> <span class="text-muted">Not specified</span>
                            </div>
                        `;
                    }

                    // Format dates
                    const startedAt = new Date(service.create_at);
                    const latestUpdate = service.updated_at && service.updated_at !== service.create_at 
                        ? new Date(service.updated_at) 
                        : null;

                    // Build offers HTML
                    let offersHtml = '';
                    if (offers.length > 0) {
                        offersHtml = `
                            <div class="mt-4">
                                <h6 class="border-bottom pb-2 mb-3"><strong>Available Offers</strong></h6>
                                <div class="offers-list" style="max-height: 200px; overflow-y: auto;">
                        `;
                        
                        offers.forEach(offer => {
                            const offerStartedAt = new Date(offer.created_at);
                            const offerLatestUpdate = offer.updated_at && offer.updated_at !== offer.created_at 
                                ? new Date(offer.updated_at) 
                                : null;
                                
                            offersHtml += `
                                <div class="offer-item mb-2 p-3 border rounded bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-primary">${offer.offer_name}</strong>
                                        <span class="text-success fw-bold">₱${parseFloat(offer.price || 0).toFixed(2)}</span>
                                    </div>
                                    ${offer.description ? `<small class="text-muted d-block">${offer.description}</small>` : ''}
                                    <div class="offer-dates mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-play-circle mr-1"></i>
                                            Started at: ${offerStartedAt.toLocaleDateString()}
                                        </small>
                                        ${offerLatestUpdate ? `
                                            <small class="text-muted ml-3">
                                                <i class="fas fa-sync-alt mr-1"></i>
                                                Latest update: ${offerLatestUpdate.toLocaleDateString()}
                                            </small>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        
                        offersHtml += `
                                </div>
                                <div class="mt-2 text-center">
                                    <small class="text-muted">Total: ${offers.length} offer(s) available</small>
                                </div>
                            </div>
                        `;
                    } else {
                        offersHtml = `
                            <div class="mt-4 text-center py-3">
                                <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                <h6 class="text-muted">No Offers Available</h6>
                                <p class="text-muted mb-0"><small>There are no offers for this service at the moment.</small></p>
                            </div>
                        `;
                    }
                    
                    Swal.fire({
                        title: `<h4 class="mb-0">${service.name}</h4>`,
                        html: `
                            <div class="service-modal-content">
                                <div class="text-center mb-4">
                                    ${imageHtml}
                                </div>
                                
                                <div class="service-details-section mb-4">
                                    <h6 class="border-bottom pb-2 mb-3"><strong>Service Information</strong></h6>
                                    <div class="mb-3">
                                        <strong>Description:</strong>
                                        <p class="mt-1 mb-0 text-justify">${service.description || 'No description available'}</p>
                                    </div>
                                    
                                    <div class="contact-location-info">
                                        ${contactInfo}
                                        ${locationInfo}
                                    </div>
                                </div>
                                
                                ${offersHtml}
                                
                                <div class="service-meta mt-4 pt-3 border-top">
                                    <div class="row text-muted">
                                        <div class="col-6">
                                            <small>
                                                <i class="fas fa-play-circle mr-1"></i>
                                                Started at: ${startedAt.toLocaleDateString()}
                                            </small>
                                        </div>
                                        <div class="col-6 text-right">
                                            <small>
                                                ${latestUpdate ? `
                                                    <i class="fas fa-sync-alt mr-1"></i>
                                                    Latest update: ${latestUpdate.toLocaleDateString()}
                                                ` : `
                                                    <i class="fas fa-clock mr-1"></i>
                                                    ${startedAt.toLocaleTimeString()}
                                                `}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'Close',
                        width: '750px',
                        customClass: {
                            popup: 'service-modal-popup',
                            title: 'service-modal-title',
                            htmlContainer: 'service-modal-body'
                        },
                        didOpen: () => {
                            // Add custom styling to the modal
                            const popup = Swal.getPopup();
                            popup.style.borderRadius = '12px';
                        }
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                Swal.fire('Error!', `Failed to load service details. Status: ${status}`, 'error');
            }
        });
    });

    // View Offers Only - Updated date labels
    $(document).on('click', '.view-offers', function() {
        const id = $(this).data('id');
        const serviceName = $(this).data('name');
        
        $.ajax({
            url: '../connection/citizen_service.php',
            method: 'GET',
            data: { 
                action: 'get_service', 
                id: id,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const service = response.data;
                    const offers = response.offers || [];
                    
                    // Format service dates
                    const serviceStartedAt = new Date(service.create_at);
                    const serviceLatestUpdate = service.updated_at && service.updated_at !== service.create_at 
                        ? new Date(service.updated_at) 
                        : null;
                    
                    // Build contact and location info for offers modal
                    let contactLocationInfo = '';
                    if (service.contact_number || service.location) {
                        contactLocationInfo = `
                            <div class="service-contact-location mb-4 p-3 bg-light rounded">
                                <h6 class="mb-3"><strong>Service Contact & Location</strong></h6>
                                <div class="row">
                                    ${service.contact_number ? `
                                        <div class="col-md-6 mb-2">
                                            <i class="fas fa-phone text-primary mr-2"></i>
                                            <strong>Contact:</strong> ${service.contact_number}
                                        </div>
                                    ` : ''}
                                    ${service.location ? `
                                        <div class="col-md-6 mb-2">
                                            <i class="fas fa-map-marker-alt text-danger mr-2"></i>
                                            <strong>Location:</strong> ${service.location}
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="service-dates mt-3 pt-2 border-top">
                                    <small class="text-muted">
                                        <i class="fas fa-play-circle mr-1"></i>
                                        Service started at: ${serviceStartedAt.toLocaleDateString()}
                                    </small>
                                    ${serviceLatestUpdate ? `
                                        <small class="text-muted ml-3">
                                            <i class="fas fa-sync-alt mr-1"></i>
                                            Latest update: ${serviceLatestUpdate.toLocaleDateString()}
                                        </small>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    }
                    
                    let offersHtml = '';
                    if (offers.length > 0) {
                        offersHtml = `
                            <div class="offers-container">
                                <div class="text-center mb-4">
                                    <h4 class="text-primary">${serviceName}</h4>
                                    <p class="text-muted">Available Service Offers</p>
                                </div>
                                
                                ${contactLocationInfo}
                                
                                <div class="offers-header d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><strong>Service Offers</strong></h5>
                                    <span class="badge badge-primary">${offers.length} offer(s)</span>
                                </div>
                                
                                <div class="offers-list" style="max-height: 400px; overflow-y: auto;">
                        `;
                        
                        offers.forEach((offer, index) => {
                            const offerStartedAt = new Date(offer.created_at);
                            const offerLatestUpdate = offer.updated_at && offer.updated_at !== offer.created_at 
                                ? new Date(offer.updated_at) 
                                : null;
                                
                            offersHtml += `
                                <div class="offer-item mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 text-primary">
                                                <span class="badge badge-secondary mr-2">${index + 1}</span>
                                                ${offer.offer_name}
                                            </h6>
                                            ${offer.description ? `<p class="mb-2 text-muted">${offer.description}</p>` : ''}
                                        </div>
                                        <div class="text-right">
                                            <span class="text-success fw-bold h5">₱${parseFloat(offer.price || 0).toFixed(2)}</span>
                                        </div>
                                    </div>
                                    <div class="offer-dates d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-play-circle mr-1"></i>
                                            Started at: ${offerStartedAt.toLocaleDateString()}
                                        </small>
                                        ${offerLatestUpdate ? 
                                            `<small class="text-muted">
                                                <i class="fas fa-sync-alt mr-1"></i>
                                                Latest update: ${offerLatestUpdate.toLocaleDateString()}
                                            </small>` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        
                        offersHtml += `
                                </div>
                            </div>
                        `;
                    } else {
                        offersHtml = `
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">No Offers Available</h4>
                                </div>
                                ${contactLocationInfo}
                                <p class="text-muted">There are no offers for <strong>${serviceName}</strong> at this time.</p>
                                <small class="text-muted">Please check back later for updates.</small>
                            </div>
                        `;
                    }
                    
                    Swal.fire({
                        title: 'Service Offers',
                        html: offersHtml,
                        showConfirmButton: true,
                        confirmButtonText: 'Close',
                        width: '800px',
                        customClass: {
                            popup: 'offers-modal-popup',
                            htmlContainer: 'offers-modal-body'
                        },
                        didOpen: () => {
                            // Add custom styling to the modal
                            const popup = Swal.getPopup();
                            popup.style.borderRadius = '12px';
                        }
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                Swal.fire('Error!', `Failed to load offers. Status: ${status}`, 'error');
            }
        });
    });
});