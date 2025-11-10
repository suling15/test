$(document).ready(function() {
    // Get CSRF token from the meta tag
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Initialize custom file input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    // Load services on page load
    loadServices();

    // Search form submission
    $('#searchForm').submit(function(e) {
        e.preventDefault();
        const searchTerm = $('#searchInput').val().trim();
        loadServices(searchTerm);
    });

    // Clear search functionality
    $(document).on('click', '.clear-search, .clear-search-btn', function(e) {
        e.preventDefault();
        $('#searchInput').val('');
        $('.clear-search').hide();
        loadServices();
    });

    // Real-time search
    let searchTimeout;
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val().trim();
        
        // Show/hide clear button
        if (searchTerm.length > 0) {
            $('.clear-search').show();
        } else {
            $('.clear-search').hide();
        }
        
        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (searchTerm.length > 2 || searchTerm.length === 0) {
                loadServices(searchTerm);
            }
        }, 500);
    });

    // Add Service Form Submission
    $('#addServiceForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = $(this).find('[type="submit"]');
        
        // Validate form
        const serviceName = $('#serviceName').val().trim();
        if (!serviceName) {
            Swal.fire('Error!', 'Please enter a service name', 'error');
            return;
        }

        // Validate image if selected
        const imageInput = $('#serviceImage')[0];
        if (imageInput.files.length > 0) {
            const file = imageInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!validTypes.includes(file.type)) {
                Swal.fire('Error!', 'Only JPG, PNG, and GIF images are allowed', 'error');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Error!', 'Image size must be less than 2MB', 'error');
                return;
            }
        }

        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '../connection/services_api.php?action=add_service',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#addServiceModal').modal('hide');
                        $('#addServiceForm')[0].reset();
                        $('#addImagePreview').html('<i class="fas fa-image fa-3x text-muted"></i>');
                        $('.custom-file-label').html('Choose file');
                        loadServices();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to add service';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    errorMsg = xhr.statusText || 'Network error';
                }
                Swal.fire('Error!', errorMsg, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Service');
            }
        });
    });

    // View Service
    $(document).on('click', '.view-service', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: '../connection/services_api.php',
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
                    let imageHtml = '<i class="fas fa-image fa-5x text-muted"></i>';
                    
                    if (service.image) {
                        const imagePath = '../uploads/services_image/' + basename(service.image);
                        imageHtml = `<img src="${imagePath}" class="img-fluid" style="max-height: 200px;">`;
                    }
                    
                    let contactInfo = service.contact_number ? 
                        `<p><strong><i class="fas fa-phone text-primary mr-2"></i>Contact:</strong> ${service.contact_number}</p>` : 
                        `<p><strong><i class="fas fa-phone text-primary mr-2"></i>Contact:</strong> <span class="text-muted">Not specified</span></p>`;
                    
                    let locationInfo = service.location ? 
                        `<p><strong><i class="fas fa-map-marker-alt text-danger mr-2"></i>Location:</strong> ${service.location}</p>` : 
                        `<p><strong><i class="fas fa-map-marker-alt text-danger mr-2"></i>Location:</strong> <span class="text-muted">Not specified</span></p>`;
                    
                    Swal.fire({
                        title: service.name,
                        html: `
                            <div class="text-center mb-3">
                                ${imageHtml}
                            </div>
                            <p><strong>Description:</strong></p>
                            <p>${service.description || 'No description available'}</p>
                            ${contactInfo}
                            ${locationInfo}
                            <p class="text-muted"><small>Created: ${new Date(service.create_at).toLocaleString()}</small></p>
                            ${service.updated_at ? `<p class="text-muted"><small>Last Updated: ${new Date(service.updated_at).toLocaleString()}</small></p>` : ''}
                        `,
                        showConfirmButton: true,
                        width: '600px'
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to load service details', 'error');
            }
        });
    });

    // Manage Offers Button Click
    $(document).on('click', '.manage-offers', function() {
        const serviceId = $(this).data('id');
        const serviceName = $(this).data('name');
        
        $('#serviceNameTitle').text(`Service: ${serviceName}`);
        $('#offerServiceId').val(serviceId);
        
        loadServiceOffers(serviceId);
        $('#manageOffersModal').modal('show');
    });

    // Load Service Offers
    function loadServiceOffers(serviceId) {
        $.ajax({
            url: '../connection/services_api.php',
            method: 'GET',
            data: { 
                action: 'get_service_offers', 
                service_id: serviceId,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const offers = response.data;
                    let offersHtml = '';
                    
                    if (offers.length > 0) {
                        offers.forEach(offer => {
                            offersHtml += `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${offer.offer_name}</strong>
                                        ${offer.price ? `<span class="ml-2">₱${parseFloat(offer.price).toFixed(2)}</span>` : ''}
                                        <br>
                                        <small class="text-muted">Added: ${new Date(offer.created_at).toLocaleString()}</small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-warning edit-offer" data-id="${offer.id}" data-name="${offer.offer_name}" data-price="${offer.price}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-offer" data-id="${offer.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        offersHtml = '<div class="text-center py-3 text-muted">No offers found for this service</div>';
                    }
                    
                    $('#offersList').html(offersHtml);
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to load service offers', 'error');
            }
        });
    }

    // Add Offer Form Submission
    $('#addOfferForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const submitBtn = $(this).find('[type="submit"]');
        
        const offerName = $('#offerName').val().trim();
        if (!offerName) {
            Swal.fire('Error!', 'Please enter an offer name', 'error');
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

        $.ajax({
            url: '../connection/services_api.php?action=add_service_offer',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#offerName').val('');
                        $('#offerPrice').val('');
                        const serviceId = $('#offerServiceId').val();
                        loadServiceOffers(serviceId);
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to add offer';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    errorMsg = xhr.statusText || 'Network error';
                }
                Swal.fire('Error!', errorMsg, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Offer');
            }
        });
    });

    // Edit Offer Button Click
    $(document).on('click', '.edit-offer', function() {
        const offerId = $(this).data('id');
        const offerName = $(this).data('name');
        const offerPrice = $(this).data('price');
        
        $('#editOfferId').val(offerId);
        $('#editOfferName').val(offerName);
        $('#editOfferPrice').val(offerPrice);
        
        $('#editOfferModal').modal('show');
    });

    // Edit Offer Form Submission
    $('#editOfferForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const submitBtn = $(this).find('[type="submit"]');
        
        const offerName = $('#editOfferName').val().trim();
        if (!offerName) {
            Swal.fire('Error!', 'Please enter an offer name', 'error');
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.ajax({
            url: '../connection/services_api.php?action=update_service_offer',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#editOfferModal').modal('hide');
                        const serviceId = $('#offerServiceId').val();
                        loadServiceOffers(serviceId);
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to update offer';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    errorMsg = xhr.statusText || 'Network error';
                }
                Swal.fire('Error!', errorMsg, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Update Offer');
            }
        });
    });

    // Delete Offer
    $(document).on('click', '.delete-offer', function() {
        const offerId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This offer will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../connection/services_api.php',
                    method: 'POST',
                    data: { 
                        action: 'delete_service_offer', 
                        id: offerId,
                        csrf_token: csrfToken
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                const serviceId = $('#offerServiceId').val();
                                loadServiceOffers(serviceId);
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Failed to delete offer';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) {
                            errorMsg = xhr.statusText || 'Network error';
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }
        });
    });

    // Edit Service - Open Modal
    $(document).on('click', '.edit-service', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: '../connection/services_api.php',
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
                    let imageHtml = '<i class="fas fa-image fa-3x text-muted"></i>';
                    
                    if (service.image) {
                        const imagePath = '../uploads/services_image/' + basename(service.image);
                        imageHtml = `<img src="${imagePath}" class="img-thumbnail" style="max-height: 150px;">`;
                    }
                    
                    $('#editServiceId').val(service.id);
                    $('#editServiceName').val(service.name);
                    $('#editServiceDescription').val(service.description);
                    $('#editServiceContact').val('');
                    $('#editServiceLocation').val('');
                    $('#currentImage').val(service.image);
                    $('#imagePreview').html(imageHtml);
                    
                    const currentContact = service.contact_number ? service.contact_number : 'None';
                    $('#currentContactDisplay').text(currentContact);
                    
                    const currentLocation = service.location ? service.location : 'None';
                    $('#currentLocationDisplay').text(currentLocation);
                    
                    $('#editServiceImage').next('.custom-file-label').html('Choose new file (optional)');
                    
                    $('#editServiceModal').modal('show');
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to load service for editing', 'error');
            }
        });
    });

    // Update Service Form Submission
    $('#editServiceForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = $(this).find('[type="submit"]');
        
        const serviceName = $('#editServiceName').val().trim();
        if (!serviceName) {
            Swal.fire('Error!', 'Please enter a service name', 'error');
            return;
        }

        const currentContact = $('#currentContactDisplay').text().trim();
        const currentLocation = $('#currentLocationDisplay').text().trim();
        
        const contactInput = $('#editServiceContact').val().trim();
        if (contactInput === '' && currentContact !== 'None') {
            formData.set('contact_number', currentContact);
        }
        
        const locationInput = $('#editServiceLocation').val().trim();
        if (locationInput === '' && currentLocation !== 'None') {
            formData.set('location', currentLocation);
        }

        const imageInput = $('#editServiceImage')[0];
        if (imageInput.files.length > 0) {
            const file = imageInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!validTypes.includes(file.type)) {
                Swal.fire('Error!', 'Only JPG, PNG, and GIF images are allowed', 'error');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Error!', 'Image size must be less than 2MB', 'error');
                return;
            }
        }

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.ajax({
            url: '../connection/services_api.php?action=update_service',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#editServiceModal').modal('hide');
                        loadServices();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to update service';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    errorMsg = xhr.statusText || 'Network error';
                }
                Swal.fire('Error!', errorMsg, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Service');
            }
        });
    });

    // Delete Service
    $(document).on('click', '.delete-service', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../connection/services_api.php?action=delete_service',
                    method: 'POST',
                    data: { 
                        action: 'delete_service',
                        id: id,
                        csrf_token: csrfToken
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                loadServices();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete Service Error:', error);
                        Swal.fire('Error!', 'Failed to delete service.', 'error');
                    }
                });
            }
        });
    });

    // Image preview for add form
    $('#serviceImage').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#addImagePreview').html(`
                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 150px;">
                    <small class="text-muted d-block">${file.name}</small>
                `);
            }
            reader.readAsDataURL(file);
        } else {
            $('#addImagePreview').html('<i class="fas fa-image fa-3x text-muted"></i>');
        }
    });

    // Image preview for edit form
    $('#editServiceImage').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').html(`
                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 150px;">
                    <small class="text-muted d-block">New image: ${file.name}</small>
                `);
            }
            reader.readAsDataURL(file);
        }
    });

    // Load all services via AJAX
    function loadServices(searchTerm = '') {
        $.ajax({
            url: '../connection/services_api.php',
            method: 'GET',
            data: { 
                action: 'get_all_services',
                search: searchTerm,
                csrf_token: csrfToken
            },
            dataType: 'json',
            beforeSend: function() {
                $('#servicesContainer').html(`
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-muted mb-3"></i>
                        <h4>Loading services...</h4>
                    </div>
                `);
            },
            success: function(response) {
                if (response.success) {
                    displayServices(response.data, searchTerm);
                } else {
                    showNoServicesMessage(searchTerm, response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading services:', error);
                showNoServicesMessage(searchTerm, 'Failed to load services');
            }
        });
    }

    // Display services in the grid
    function displayServices(services, searchTerm = '') {
        const servicesContainer = $('#servicesContainer');
        
        if (services.length === 0) {
            showNoServicesMessage(searchTerm);
            return;
        }

        let servicesHtml = '';
        
        services.forEach(service => {
            let imageHtml = '<div class="text-center py-3"><i class="fas fa-image fa-5x text-muted"></i></div>';
            if (service.image) {
                const imagePath = '../uploads/services_image/' + basename(service.image);
                imageHtml = `<img src="${imagePath}" class="img-fluid mb-3 service-image" alt="${htmlspecialchars(service.name)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <div class="text-center py-3" style="display:none;"><i class="fas fa-image fa-5x text-muted"></i></div>`;
            }

            const description = service.description ? 
                (service.description.length > 100 ? service.description.substring(0, 100) + '...' : service.description) : 
                'No description available';

            servicesHtml += `
                <div class="col-12 col-sm-6 col-lg-4 mb-4">
                    <div class="card card-service h-100">
                        <div class="card-body d-flex flex-column">
                            ${imageHtml}
                            <h5 class="service-title">${htmlspecialchars(service.name)}</h5>
                            <p class="service-description">${htmlspecialchars(description)}</p>
                            
                            ${service.contact_number ? 
                                `<p class="service-contact mb-1"><small><i class="fas fa-phone text-primary mr-1"></i> ${htmlspecialchars(service.contact_number)}</small></p>` : 
                                ''
                            }
                            
                            ${service.location ? 
                                `<p class="service-location mb-2"><small><i class="fas fa-map-marker-alt text-danger mr-1"></i> ${htmlspecialchars(service.location)}</small></p>` : 
                                ''
                            }
                            
                            <div class="actions-grid mt-auto">
                                <div class="row no-gutters">
                                    <div class="col-6 pr-1">
                                        <button class="btn btn-sm btn-primary view-service w-100" data-id="${service.id}">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </div>
                                    <div class="col-6 pl-1">
                                        <button class="btn btn-sm btn-warning edit-service w-100" data-id="${service.id}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </div>
                                <div class="row no-gutters mt-1">
                                    <div class="col-6 pr-1">
                                        <button class="btn btn-sm btn-info manage-offers w-100" data-id="${service.id}" data-name="${htmlspecialchars(service.name)}">
                                            <i class="fas fa-tags"></i> Offers
                                        </button>
                                    </div>
                                    <div class="col-6 pl-1">
                                        <button class="btn btn-sm btn-danger delete-service w-100" data-id="${service.id}">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        servicesContainer.html(servicesHtml);
        
        // Update search results info
        updateSearchResultsInfo(services.length, searchTerm);
    }

    // Show no services message
    function showNoServicesMessage(searchTerm = '', errorMessage = '') {
        const servicesContainer = $('#servicesContainer');
        
        let messageHtml = '';
        if (searchTerm) {
            messageHtml = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>No services found</h4>
                    <p>No services found for "<strong>${htmlspecialchars(searchTerm)}</strong>"</p>
                    ${errorMessage ? `<p class="text-danger">${htmlspecialchars(errorMessage)}</p>` : ''}
                    <button class="btn btn-primary" onclick="loadServices()">Show All Services</button>
                </div>
            `;
        } else {
            messageHtml = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h4>No services found</h4>
                    <p>Click the "Add Service" button to create your first service</p>
                    ${errorMessage ? `<p class="text-danger">${htmlspecialchars(errorMessage)}</p>` : ''}
                </div>
            `;
        }
        
        servicesContainer.html(messageHtml);
        updateSearchResultsInfo(0, searchTerm);
    }

    // Update search results info
    function updateSearchResultsInfo(count, searchTerm = '') {
        const searchResultsInfo = $('#searchResultsInfo');
        const searchResultsText = $('#searchResultsText');
        
        if (searchTerm) {
            searchResultsText.html(`
                <i class="fas fa-search mr-2"></i>
                Found ${count} result(s) for "<strong>${htmlspecialchars(searchTerm)}</strong>"
            `);
            searchResultsInfo.show();
        } else {
            searchResultsInfo.hide();
        }
    }

    // Helper function to escape HTML
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Helper function to get filename from path
    function basename(path) {
        return path.split('/').pop().split('\\').pop();
    }
});