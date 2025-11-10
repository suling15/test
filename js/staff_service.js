$(document).ready(function() {
    let currentServiceId = null;
    let currentServiceName = null;
    
    // Get variables from data attributes or window object
    const csrfToken = $('body').data('csrf-token') || window.csrfToken || $('meta[name="csrf-token"]').attr('content');
    const staffId = $('body').data('staff-id') || window.staffId;

    // Check if variables are available
    if (!staffId) {
        console.error('Staff ID not found. Available variables:', {
            bodyStaffId: $('body').data('staff-id'),
            windowStaffId: window.staffId,
            staffId: staffId
        });
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'Staff ID not available. Please refresh the page.',
                icon: 'error',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        } else {
            alert('Staff ID not available. Please refresh the page.');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
        return;
    }

    console.log('Staff service initialized for staff ID:', staffId);

    // Initialize custom file input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    // View Service Details
    $('.view-service').on('click', function() {
        const serviceId = $(this).data('id');
        const card = $(this).closest('.card-service');
        const isAssigned = card.hasClass('assigned');
        
        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: { 
                service_id: serviceId,
                request_type: 'details',
                staff_id: staffId,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const service = response.service;
                    currentServiceId = serviceId;
                    
                    // Set modal content
                    $('#modalServiceName').text(service.name);
                    $('#modalServiceDescription').text(service.description || 'No description available');
                    
                    // Set contact and location
                    $('#modalServiceContact').text(service.contact_number || 'Not specified');
                    $('#modalServiceLocation').text(service.location || 'Not specified');
                    
                    // Show assignment info in modal
                    if (response.is_assigned) {
                        $('#modalAssignmentInfo').show();
                        $('#modalAssignedDate').text('Assigned: ' + new Date(response.assigned_at).toLocaleDateString());
                        $('#modalManageBtn').show().data('id', serviceId);
                        $('#addOfferBtn').show();
                    } else {
                        $('#modalAssignmentInfo').hide();
                        $('#modalManageBtn').hide();
                        $('#addOfferBtn').hide();
                    }
                    
                    // Set image
                    if (service.image) {
                        const imageFilename = service.image.includes('/') 
                            ? service.image.split('/').pop() 
                            : service.image;
                        
                        $('#modalServiceImage').attr('src', '../uploads/services_image/' + imageFilename);
                        $('#modalServiceImage').show();
                    } else {
                        $('#modalServiceImage').hide();
                    }
                    
                    // Format dates
                    $('#modalServiceCreated').text(new Date(service.create_at).toLocaleString());
                    if (service.updated_at && service.updated_at !== service.create_at) {
                        $('#modalServiceUpdated').text(new Date(service.updated_at).toLocaleString());
                    } else {
                        $('#modalServiceUpdated').text('Never updated');
                    }
                    
                    // Display offers
                    displayOffers(response.offers, '#serviceOffers', response.is_assigned);
                    
                    // Show modal
                    $('#serviceModal').modal('show');
                } else {
                    Swal.fire('Error', response.message || 'Failed to load service details', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while fetching service details', 'error');
            }
        });
    });
    
    // Add Offer from Service Modal
    $('#addOfferBtn').on('click', function() {
        $('#addOfferModal').modal('show');
        $('#modalOfferServiceId').val(currentServiceId);
        $('#modalOfferName').val('');
        $('#modalOfferPrice').val('');
    });

    // Edit Service functionality
    $('#modalManageBtn').on('click', function() {
        const serviceId = $(this).data('id');
        openEditServiceModal(serviceId);
    });

    function openEditServiceModal(serviceId) {
        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: { 
                service_id: serviceId,
                request_type: 'details',
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const service = response.service;
                    
                    $('#editServiceId').val(service.id);
                    $('#editServiceName').val(service.name);
                    $('#editServiceDescription').val(service.description || '');
                    $('#editServiceContact').val(''); // Clear input field
                    $('#editServiceLocation').val(''); // Clear input field
                    
                    // Display current contact and location
                    const currentContact = service.contact_number || 'None';
                    const currentLocation = service.location || 'None';
                    $('#currentContactDisplay').text(currentContact);
                    $('#currentLocationDisplay').text(currentLocation);
                    
                    // Display current image
                    let imageHtml = '';
                    if (service.image) {
                        const imageFilename = service.image.includes('/') 
                            ? service.image.split('/').pop() 
                            : service.image;
                        imageHtml = `<img src="../uploads/services_image/${imageFilename}" class="img-fluid" style="max-height: 200px;" alt="Current Image">`;
                    } else {
                        imageHtml = '<div class="text-center py-4"><i class="fas fa-image fa-3x text-muted"></i><p class="text-muted mt-2">No image available</p></div>';
                    }
                    $('#currentServiceImage').html(imageHtml);
                    
                    // Update file input label
                    $('#editServiceImage').next('.custom-file-label').text('Choose new image (optional)');
                    
                    $('#editServiceModal').modal('show');
                } else {
                    Swal.fire('Error', response.message || 'Failed to load service data', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while fetching service data', 'error');
            }
        });
    }

    // Save service changes
    $('#saveServiceBtn').on('click', function() {
        const formData = new FormData();
        
        // Manually append all form data
        formData.append('service_id', $('#editServiceId').val());
        formData.append('name', $('#editServiceName').val().trim());
        formData.append('description', $('#editServiceDescription').val().trim());
        formData.append('request_type', 'edit_service');
        formData.append('csrf_token', csrfToken);
        
        // Handle contact number
        const contactInput = $('#editServiceContact').val().trim();
        const currentContact = $('#currentContactDisplay').text().trim();
        if (contactInput === '' && currentContact !== 'None') {
            formData.append('contact_number', currentContact);
        } else {
            formData.append('contact_number', contactInput);
        }
        
        // Handle location
        const locationInput = $('#editServiceLocation').val().trim();
        const currentLocation = $('#currentLocationDisplay').text().trim();
        if (locationInput === '' && currentLocation !== 'None') {
            formData.append('location', currentLocation);
        } else {
            formData.append('location', locationInput);
        }
        
        // Handle file upload
        const imageFile = $('#editServiceImage')[0].files[0];
        if (imageFile) {
            formData.append('image', imageFile);
        }
        
        // Validate required fields
        const serviceName = $('#editServiceName').val().trim();
        if (!serviceName) {
            Swal.fire('Error', 'Service name is required', 'warning');
            return;
        }

        // Show loading
        const saveBtn = $(this);
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Service updated successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#editServiceModal').modal('hide');
                        // Refresh the service modal and page
                        $('.view-service[data-id="' + $('#editServiceId').val() + '"]').click();
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to update service', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while updating the service', 'error');
            },
            complete: function() {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');
            }
        });
    });

    // Function to open edit offer modal
    function openEditOfferModal(offerId, serviceId, currentName, currentPrice) {
        $('#editOfferId').val(offerId);
        $('#editOfferServiceId').val(serviceId);
        $('#editOfferName').val(currentName);
        $('#editOfferPrice').val(currentPrice || '');
        $('#editOfferModal').modal('show');
    }

    // Save offer changes
    $('#saveEditOfferBtn').on('click', function() {
        const offerId = $('#editOfferId').val();
        const serviceId = $('#editOfferServiceId').val();
        const offerName = $('#editOfferName').val().trim();
        const price = $('#editOfferPrice').val();
        
        if (!offerName) {
            Swal.fire('Error', 'Please enter an offer name', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('offer_id', offerId);
        formData.append('service_id', serviceId);
        formData.append('offer_name', offerName);
        formData.append('price', price || 0);
        formData.append('request_type', 'edit_offer');
        formData.append('csrf_token', csrfToken);
        
        // Show loading
        const saveBtn = $(this);
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Offer updated successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#editOfferModal').modal('hide');
                        // Refresh offers
                        $('.view-offers[data-id="' + serviceId + '"]').click();
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to update offer', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while updating the offer', 'error');
            },
            complete: function() {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');
            }
        });
    });

    // Update the displayOffers function to include edit buttons
    function displayOffers(offers, container, isAssigned) {
        let offersHtml = '';
        
        if (offers && offers.length > 0) {
            offers.forEach(offer => {
                offersHtml += `
                    <div class="offer-item" data-offer-id="${offer.id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${offer.offer_name}</h6>
                                <span class="offer-price">₱${parseFloat(offer.price || 0).toFixed(2)}</span>
                            </div>
                            <div>
                                ${isAssigned ? `
                                    <button class="btn btn-sm btn-outline-primary edit-offer-btn" 
                                            data-offer-id="${offer.id}" 
                                            data-service-id="${offer.service_id}"
                                            data-offer-name="${offer.offer_name.replace(/"/g, '&quot;')}" 
                                            data-offer-price="${offer.price}">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-offer ml-1" 
                                            data-offer-id="${offer.id}" 
                                            data-offer-name="${offer.offer_name.replace(/"/g, '&quot;')}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Started: ${new Date(offer.created_at).toLocaleDateString()} 
                            ${offer.updated_at && offer.updated_at !== offer.created_at ? 
                              `| Updated: ${new Date(offer.updated_at).toLocaleDateString()}` : ''}
                        </small>
                    </div>
                `;
            });
        } else {
            offersHtml = '<div class="text-center py-4"><i class="fas fa-tags fa-2x text-muted mb-2"></i><p class="text-muted">No offers available for this service.</p></div>';
        }
        
        $(container).html(offersHtml);
    }
    
    // View Offers Modal
    $('.view-offers').on('click', function() {
        const serviceId = $(this).data('id');
        const serviceName = $(this).data('name');
        const isAssigned = $(this).data('assigned');
        
        currentServiceId = serviceId;
        currentServiceName = serviceName;
        
        $('#offersServiceName').text(serviceName + ' - Offers');
        $('#offerServiceId').val(serviceId);
        $('#modalOfferServiceId').val(serviceId);
        
        // Show/hide add offer button based on assignment
        if (isAssigned) {
            $('#showAddOfferForm').show();
        } else {
            $('#showAddOfferForm').hide();
        }
        $('#addOfferFormContainer').hide();
        
        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: { 
                service_id: serviceId,
                request_type: 'offers',
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayOffers(response.offers, '#offersList', isAssigned);
                    $('#offersModal').modal('show');
                } else {
                    Swal.fire('Error', response.message || 'Failed to load offers', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while fetching offers', 'error');
            }
        });
    });
    
    // Show add offer form in offers modal
    $('#showAddOfferForm').on('click', function() {
        $('#addOfferFormContainer').show();
        $(this).hide();
    });
    
    // Add offer form submission
    $('#addOfferForm').on('submit', function(e) {
        e.preventDefault();
        addNewOffer();
    });
    
    // Save offer from modal
    $('#saveOfferBtn').on('click', function() {
        addNewOffer();
    });
    
    // Manage offers button
    $('.manage-offers').on('click', function() {
        const serviceId = $(this).data('id');
        const serviceName = $(this).data('name');
        
        currentServiceId = serviceId;
        currentServiceName = serviceName;
        
        $('#offersServiceName').text(serviceName + ' - Manage Offers');
        $('#offerServiceId').val(serviceId);
        $('#showAddOfferForm').show().click();
        
        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: { 
                service_id: serviceId,
                request_type: 'offers',
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayOffers(response.offers, '#offersList', true);
                    $('#offersModal').modal('show');
                } else {
                    Swal.fire('Error', response.message || 'Failed to load offers', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while fetching offers', 'error');
            }
        });
    });
    
    // Function to add new offer
    function addNewOffer() {
        const serviceId = currentServiceId;
        const offerName = $('#offerName').val() || $('#modalOfferName').val();
        const price = $('#offerPrice').val() || $('#modalOfferPrice').val();
        
        if (!offerName.trim()) {
            Swal.fire('Error', 'Please enter an offer name', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('service_id', serviceId);
        formData.append('offer_name', offerName.trim());
        formData.append('price', price || 0);
        formData.append('request_type', 'add_offer');
        formData.append('csrf_token', csrfToken);
        
        // Show loading
        const saveBtn = $('#saveOfferBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Offer added successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reset forms
                        $('#addOfferForm')[0].reset();
                        $('#addOfferModalForm')[0].reset();
                        
                        // Hide forms and modals
                        $('#addOfferFormContainer').hide();
                        $('#showAddOfferForm').show();
                        $('#addOfferModal').modal('hide');
                        
                        // Reload offers
                        $('.view-offers[data-id="' + serviceId + '"]').click();
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to add offer', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while adding the offer', 'error');
            },
            complete: function() {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Offer');
            }
        });
    }
    
    // Function to delete offer
    function deleteOffer(offerId, offerName) {
        console.log('Deleting offer:', offerId, offerName);
        
        const formData = new FormData();
        formData.append('offer_id', offerId);
        formData.append('service_id', currentServiceId); // Add this line
        formData.append('request_type', 'delete_offer');
        formData.append('csrf_token', csrfToken);
        
        console.log('Sending delete request with data:', {
            offer_id: offerId,
            service_id: currentServiceId,
            request_type: 'delete_offer'
        });
        
        $.ajax({
            url: '../connection/staff_service.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: `Offer "${offerName}" has been deleted.`,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Remove the offer item from DOM immediately
                        $(`.offer-item[data-offer-id="${offerId}"]`).remove();
                        
                        // Check if no offers left
                        if ($('#offersList .offer-item').length === 0) {
                            $('#offersList').html('<div class="text-center py-4"><i class="fas fa-tags fa-2x text-muted mb-2"></i><p class="text-muted">No offers available for this service.</p></div>');
                        }
                        
                        // Also refresh the service modal if it's open
                        if ($('#serviceModal').is(':visible')) {
                            $('.view-service[data-id="' + currentServiceId + '"]').click();
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to delete offer', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('XHR response:', xhr.responseText);
                Swal.fire('Error', 'An error occurred while deleting the offer: ' + error, 'error');
            }
        });
    }

    // Global event delegation for dynamically created buttons
    $(document).on('click', '.delete-offer', function() {
        const offerId = $(this).data('offer-id');
        const offerName = $(this).data('offer-name');
        
        console.log('Delete button clicked:', offerId, offerName);
        
        if (!offerId) {
            console.error('Offer ID not found in button data');
            Swal.fire('Error', 'Could not identify the offer to delete', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Delete Offer?',
            text: `Are you sure you want to delete "${offerName}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteOffer(offerId, offerName);
            }
        });
    });

    // Global event delegation for edit buttons
    $(document).on('click', '.edit-offer-btn', function() {
        const offerId = $(this).data('offer-id');
        const serviceId = $(this).data('service-id');
        const offerName = $(this).data('offer-name');
        const price = $(this).data('offer-price');
        
        openEditOfferModal(offerId, serviceId, offerName, price);
    });

    // Handle modal hidden events to reset forms
    $('#addOfferModal').on('hidden.bs.modal', function() {
        $('#addOfferModalForm')[0].reset();
    });

    $('#editOfferModal').on('hidden.bs.modal', function() {
        $('#editOfferForm')[0].reset();
    });

    $('#editServiceModal').on('hidden.bs.modal', function() {
        $('#editServiceImage').next('.custom-file-label').text('Choose new image (optional)');
    });
});