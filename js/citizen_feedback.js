$(document).ready(function() {
  console.log('Document ready - Feedback page initialized');
  
  const csrf = $('meta[name="csrf-token"]').attr('content');
  let selectedRating = 0;
  let isSubmitting = false;

  // Debug logging
  console.log('CSRF Token:', csrf);
  console.log('Feedback form exists:', $('#feedbackForm').length > 0);
  console.log('Submit button exists:', $('#submitFeedback').length > 0);

  // Set up AJAX error handling
  $.ajaxSetup({
    error: function(xhr, status, error) {
      console.error("AJAX Error:", status, error);
      console.error("Response:", xhr.responseText);
      toast('error', 'Request failed: ' + error);
    }
  });

  // Function to set all SQD ratings to a specific value
  function setAllSQDToValue(value) {
    for (let i = 0; i <= 8; i++) {
      $(`input[name="SQD${i}"][value="${value}"]`).prop('checked', true);
    }
    console.log(`All SQD ratings set to ${value}`);
    
    // Update the active state of quick rating radio
    $(`input[name="sqd_quick_rating"][value="${value}"]`).prop('checked', true);
  }

  function setStars(value) {
    selectedRating = value;
    $('#ratingValue').val(value);
    $('#ratingStars .star').each(function () {
      const v = Number($(this).data('value'));
      $(this).toggleClass('fas', v <= value).toggleClass('far', v > value);
    });
  }

  function toast(icon, title) {
    Swal.fire({ 
      icon, 
      title, 
      timer: 2000, 
      showConfirmButton: false,
      toast: true,
      position: 'top-end'
    });
  }

  function escapeHtml(str) {
    if (str === null || typeof str === 'undefined') return '';
    return String(str)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function nl2br(str) {
    if (str === null || typeof str === 'undefined') return '';
    return String(str).replace(/\n/g,'<br>');
  }

  function renderCards(list) {
    const container = $('#feedbackCards').empty();
    
    if (!list || list.length === 0) {
        container.append(`
            <div class="col-12">
                <div class="no-feedback text-center p-5">
                    <i class="fas fa-comment-slash fa-3x mb-3 text-muted"></i>
                    <h5>No feedback submitted yet</h5>
                    <p class="text-muted">Your feedback will appear here once you submit it.</p>
                </div>
            </div>
        `);
        return;
    }
    
    // Create a row to contain all cards
    const row = $('<div class="row"></div>');
    container.append(row);
    
    list.forEach(rowData => {
        // Format date
        let dateStr = 'N/A';
        if (rowData.create) {
            const date = new Date(rowData.create);
            dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        
        // Truncate feedback text for card view
        const feedbackText = rowData.feedback_text || '';
        const truncatedText = feedbackText.length > 150 ? 
            feedbackText.substring(0, 150) + '...' : feedbackText;

        // Check if feedback has a response
        const hasResponse = rowData.has_response || rowData.response_id;
        
        // Check if feedback is anonymous
        const isAnonymous = rowData.is_anonymous;
        
        // Create a column for each card
        const col = $('<div class="col-md-6 col-lg-4 mb-4"></div>');
        
        // Create the card with unique ID
        const card = $(`
            <div class="card feedback-card h-100" data-id="${rowData.id}">
                ${hasResponse ? '<span class="response-badge"><i class="fas fa-reply me-1"></i> Replied</span>' : ''}
                ${isAnonymous ? '<span class="anonymous-badge"><i class="fas fa-user-secret me-1"></i> Anonymous</span>' : ''}
                <div class="card-header">
                    <span class="service-label">Service:</span>
                    <h6 class="card-title mb-0">${escapeHtml(rowData.service_name || 'No Service Selected')}</h6>
                </div>
                <div class="card-body">
                    <span class="service-offer-label">Service Offer:</span>
                    <h6 class="card-subtitle mb-2">${escapeHtml(rowData.offer_name || 'No Service Offer Selected')}</h6>
                    
                    <span class="feedback-label">Feedback:</span>
                    <p class="card-text feedback-text">
                        ${nl2br(escapeHtml(truncatedText))}
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <span class="rating-label">Rating:</span>
                          <div class="rating">
                            ${[1,2,3,4,5].map(v => `
                              <i class="${v<=rowData.rating?'fas':'far'} fa-star" 
                              style="color: ${v<=rowData.rating?'#ffc107':'#e4e5e9'};"></i>
                            `).join('')}
                            <span class="ms-1">${rowData.rating}/5</span>
                          </div>
                          <small class="text-muted">
                            <i class="far fa-clock me-1"></i> ${dateStr}
                        </small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-sm btn-outline-primary view btn-action" title="View Details">
                            <i class="fas fa-eye me-1"></i> View
                        </button>
                        ${hasResponse ? 
                            `<button class="btn btn-sm btn-outline-info view-response btn-action" title="View Response">
                                <i class="fas fa-reply me-1"></i> View Response
                            </button>` : 
                            `<button class="btn btn-sm btn-outline-danger delete btn-action" title="Delete">
                                <i class="fas fa-trash me-1"></i> Delete
                            </button>`
                        }
                    </div>
                </div>
            </div>
        `);
        
        // Append card to column and column to row
        col.append(card);
        row.append(col);
    });
  }

  function loadServices() {
    return $.ajax({
      url: '../connection/citizen_feedback.php?action=fetch_services',
      method: 'GET',
      dataType: 'json'
    }).then(res => {
      if (!res.success) throw new Error(res.message || 'Failed to load services');
      const sel = $('#serviceSelect').empty().append(`<option value="" disabled selected>Select a service</option>`);
      res.data.forEach(s => sel.append(`<option value="${s.id}">${escapeHtml(s.name)}</option>`));
    }).catch(err => {
      console.error(err);
      toast('error', 'Unable to load services');
    });
  }

  function loadServiceOffers(serviceId) {
    return $.ajax({
      url: '../connection/citizen_feedback.php?action=fetch_service_offers&service_id=' + serviceId,
      method: 'GET',
      dataType: 'json'
    }).then(res => {
      if (!res.success) throw new Error(res.message || 'Failed to load service offers');
      const sel = $('#serviceOfferSelect').empty().append(`<option value="" disabled selected>Select a service offer</option>`);
      res.data.forEach(o => sel.append(`<option value="${o.id}">${escapeHtml(o.offer_name)}</option>`));
    }).catch(err => {
      console.error(err);
      toast('error', 'Unable to load service offers');
    });
  }

  function loadMyFeedback() {
    // Get current filter values from URL parameters or use data attributes
    const urlParams = new URLSearchParams(window.location.search);
    const container = document.getElementById('feedbackCards');
    const defaultMonth = container ? container.dataset.currentMonth : new Date().getMonth() + 1;
    const defaultYear = container ? container.dataset.currentYear : new Date().getFullYear();
    
    const month = urlParams.get('month') || defaultMonth;
    const year = urlParams.get('year') || defaultYear;
    
    return $.ajax({
      url: '../connection/citizen_feedback.php?action=fetch_my_feedback&month=' + month + '&year=' + year,
      method: 'GET',
      dataType: 'json'
    }).then(res => {
      if (!res.success) throw new Error(res.message || 'Failed to load feedback');
      renderCards(res.data);
    }).catch(err => {
      console.error(err);
      toast('error', 'Unable to load feedback');
    });
  }

  function validateForm() {
    const service_id = $('#serviceSelect').val();
    const service_offer_id = $('#serviceOfferSelect').val();
    const feedback_text = $('#feedbackText').val().trim();
    const rating = selectedRating;

    // Check required CC questions
    const CC1 = $('input[name="CC1"]:checked').val();
    const CC2 = $('input[name="CC2"]:checked').val();
    const CC3 = $('input[name="CC3"]:checked').val();

    // Check required SQD questions
    const missingSQD = [];
    for (let i = 0; i <= 8; i++) {
        if (!$(`input[name="SQD${i}"]:checked`).val()) {
            missingSQD.push(`SQD${i}`);
        }
    }

    if (!service_id) {
        toast('warning', 'Please select a service');
        return false;
    }
    if (!service_offer_id) {
        toast('warning', 'Please select a service offer');
        return false;
    }
    if (!feedback_text) {
        toast('warning', 'Please enter your feedback');
        return false;
    }
    if (!rating) {
        toast('warning', 'Please select a rating');
        return false;
    }
    if (!CC1) {
        toast('warning', 'Please answer CC1 question');
        return false;
    }
    if (missingSQD.length > 0) {
        toast('warning', `Please answer all SQD questions. Missing: ${missingSQD.join(', ')}`);
        return false;
    }

    return true;
  }

  function addFeedback() {
    console.log('addFeedback function called');
    
    if (isSubmitting) {
      console.log('Already submitting, skipping');
      return;
    }

    isSubmitting = true;

    if (!validateForm()) {
      isSubmitting = false;
      return;
    }

    // Disable submit button to prevent multiple submissions
    $('#submitFeedback').prop('disabled', true);

    const service_id = $('#serviceSelect').val();
    const service_offer_id = $('#serviceOfferSelect').val();
    const feedback_text = $('#feedbackText').val().trim();
    const rating = selectedRating;
    const is_anonymous = $('#is_anonymous').is(':checked') ? 1 : 0;

    // Get CC values
    const CC1 = $('input[name="CC1"]:checked').val();
    const CC2 = $('input[name="CC2"]:checked').val();
    const CC3 = $('input[name="CC3"]:checked').val();

    // Get SQD values
    const formData = new FormData();
    formData.append('service_id', service_id);
    formData.append('service_offer_id', service_offer_id);
    formData.append('feedback_text', feedback_text);
    formData.append('rating', rating);
    formData.append('is_anonymous', is_anonymous);
    formData.append('CC1', CC1 || '');
    formData.append('CC2', CC2 || '');
    formData.append('CC3', CC3 || '');

    for (let i = 0; i <= 8; i++) {
        const sqdValue = $(`input[name="SQD${i}"]:checked`).val();
        formData.append(`SQD${i}`, sqdValue || '');
    }

    // Show loading indicator
    Swal.fire({
      title: 'Submitting Feedback',
      text: 'Please wait while we process your feedback...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
        url: '../connection/citizen_feedback.php?action=add',
        method: 'POST',
        dataType: 'json',
        headers: { 'X-CSRF-Token': csrf },
        data: formData,
        processData: false,
        contentType: false,
        timeout: 30000 // 30 second timeout for sentiment analysis
    }).done(res => {
        Swal.close();
        $('#submitFeedback').prop('disabled', false); // Re-enable button
        isSubmitting = false;
        if (res.success) {
            toast('success', 'Feedback submitted successfully!');

            // Reset form
            resetForm();
            $('#feedbackModal').modal('hide');
            loadMyFeedback();
        } else {
            toast('error', res.message || 'Failed to submit feedback');
        }
    }).fail((xhr, status, error) => {
        Swal.close();
        $('#submitFeedback').prop('disabled', false); // Re-enable button
        isSubmitting = false;
        console.error('Error:', error, xhr.responseText);

        if (status === 'timeout') {
            toast('error', 'Request timed out. Please try again.');
        } else {
            toast('error', 'Failed to submit feedback: ' + error);
        }
    });
  }

  function resetForm() {
    $('#serviceSelect').val('');
    $('#serviceOfferSelect').empty().append(`<option value="" disabled selected>Select a service offer</option>`);
    $('#feedbackText').val('');
    setStars(0);
    $('#is_anonymous').prop('checked', false);
    $('input[name="CC1"]').prop('checked', false);
    $('input[name="CC2"]').prop('checked', false);
    $('input[name="CC3"]').prop('checked', false);
    
    // Reset SQD ratings to 5 by default
    setAllSQDToValue(5);
  }

  function viewFeedback(rowId) {
    $.getJSON('../connection/citizen_feedback.php?action=get&id=' + encodeURIComponent(rowId))
    .done(res => {
      if (!res.success) return toast('error', res.message || 'Feedback not found');

      const rec = res.data;

      // Question texts for SQD0–SQD8
      const sqdQuestions = [
        "I am satisfied with the service that I availed",
        "I spent a reasonable amount of time for this transaction",
        "The office followed the transaction's requirements and steps based on the CC",
        "The steps (including payment) I needed to do for this transaction were easy and simple",
        "I easily found information about my transaction from the office or its website",
        "I paid a reasonable amount of fees for my transaction (including documents, if any)",
        "I feel the office was fair to everyone, or 'walang palakasan', during my transaction",
        "I was treated courteously by the staff, and (if asked for help) the staff was helpful",
        "I got what I needed from the government office, or (if denied) denial was sufficiently explained to me"
      ];

      // Function to format SQD values for display
      function formatSqdValue(value) {
        if (!value) return 'Not answered';
        const labels = {
          1: '1 - Strongly Disagree',
          2: '2 - Disagree',
          3: '3 - Neither Agree nor Disagree',
          4: '4 - Agree',
          5: '5 - Strongly Agree'
        };
        return labels[value] || value;
      }

      Swal.fire({
        title: 'Feedback Details',
        width: 1000,
        html: `
          <div class="text-left">
            <div class="form-group">
              <label class="font-weight-bold">Submitted:</label>
              <div class="form-control-plaintext">
                ${rec.is_anonymous ? 
                  '<span class="badge badge-secondary"><i class="fas fa-user-secret me-1"></i>Anonymously</span>' : 
                  '<span class="badge badge-info"><i class="fas fa-user me-1"></i>With Name</span>'
                }
              </div>
            </div>
            <div class="form-group">
              <label class="font-weight-bold">Service:</label>
              <div class="form-control-plaintext">${escapeHtml(rec.service_name || 'No Service Selected')}</div>
            </div>
            <div class="form-group">
              <label class="font-weight-bold">Service Offer:</label>
              <div class="form-control-plaintext">${escapeHtml(rec.offer_name || 'No Service Offer Selected')}</div>
            </div>
            <div class="form-group">
              <label class="font-weight-bold">Feedback:</label>
              <div class="form-control-plaintext border p-2" style="background-color: #f8f9fa; border-radius: 0.25rem;">
                ${nl2br(escapeHtml(rec.feedback_text))}
              </div>
            </div>
            <div class="form-group">
              <label class="font-weight-bold">Rating:</label><br>
              <div class="rating-display">
                ${[1,2,3,4,5].map(v => `<i class="${v<=rec.rating?'fas':'far'} fa-star" style="color: ${v<=rec.rating?'gold':'#ccc'}; font-size: 1.5rem;"></i>`).join('')}
                <span class="ml-2">(${rec.rating}/5)</span>
              </div>
            </div>
            <hr>

            <h5 class="font-weight-bold mb-3">Citizen's Charter (CC) Questions</h5>
            
            <div class="form-group">
              <label class="font-weight-bold">CC1: Which of the following best describes your awareness of the Citizen's Charter (CC)?</label>
              <div class="form-control-plaintext">${escapeHtml(rec.CC1 || 'Not answered')}</div>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">CC2: If aware of CC (Answers 1-3 in CC1), would you say that the CC of this office was..?</label>
              <div class="form-control-plaintext">${escapeHtml(rec.CC2 || 'Not answered')}</div>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">CC3: If aware of CC (Answers 1-3 in CC1), how much did the CC help you in your transaction?</label>
              <div class="form-control-plaintext">${escapeHtml(rec.CC3 || 'Not answered')}</div>
            </div>

            <hr>
            
            <h5 class="font-weight-bold mb-3">Service Quality Dimensions (SQD)</h5>
            <p class="text-muted mb-3">1- Strongly Disagree, 2- Disagree, 3- Neither Agree nor Disagree, 4- Agree, 5- Strongly Agree</p>
            
            ${sqdQuestions.map((q, i) => `
              <div class="form-group">
                <label class="font-weight-bold">SQD${i}: ${q}</label>
                <div class="form-control-plaintext">${formatSqdValue(rec['SQD'+i])}</div>
              </div>
            `).join('')}
          </div>
        `,
        showCancelButton: false,
        confirmButtonText: 'Close',
        confirmButtonColor: '#3085d6',
        customClass: {
          popup: 'feedback-view-modal'
        }
      });
    })
    .fail(() => toast('error', 'Unable to fetch feedback details'));
  }

  function viewResponse(feedbackId) {
    $.getJSON('../connection/citizen_feedback.php?action=get_response&feedback_id=' + encodeURIComponent(feedbackId))
    .done(res => {
        if (!res.success) return toast('error', res.message || 'Response not found');

        const response = res.data;
        let dateStr = 'N/A';
        if (response.created_at) {
            const date = new Date(response.created_at);
            dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }

        Swal.fire({
            title: 'Staff Response',
            width: 800,
            html: `
                <div class="text-left">
                    <div class="form-group">
                        <label class="font-weight-bold">Responded by:</label>
                        <div class="form-control-plaintext">${escapeHtml(response.staff_name || 'Staff Member')}</div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Response Date:</label>
                        <div class="form-control-plaintext">${dateStr}</div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Staff Response:</label>
                        <div class="form-control-plaintext border p-3" style="background-color: #f8f9fa; border-radius: 0.25rem; min-height: 150px;">
                            ${nl2br(escapeHtml(response.response_text))}
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: false,
            confirmButtonText: 'Close',
            confirmButtonColor: '#3085d6',
            customClass: {
                popup: 'response-view-modal'
            }
        });
    })
    .fail(() => toast('error', 'Unable to fetch response'));
  }

  function deleteRow(rowId) {
    // First check if this feedback has a response
    $.getJSON('../connection/citizen_feedback.php?action=get&id=' + encodeURIComponent(rowId))
    .done(res => {
        if (!res.success) return toast('error', res.message || 'Feedback not found');
        
        const feedback = res.data;
        
        // Check if feedback has a response
        if (feedback.has_response) {
            Swal.fire({
                title: 'Cannot Delete',
                text: 'This feedback cannot be deleted because it already has a response from staff.',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Proceed with deletion if no response
        Swal.fire({
            title: 'Delete Feedback?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (!result.isConfirmed) return;
            
            $.ajax({
                url: '../connection/citizen_feedback.php?action=delete',
                method: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-Token': csrf },
                data: { id: rowId }
            }).done(r => {
                if (r.success) {
                    toast('success', 'Feedback deleted successfully');
                    loadMyFeedback();
                } else {
                    toast('error', r.message || 'Delete failed');
                }
            }).fail(() => toast('error', 'Request failed'));
        });
    })
    .fail(() => toast('error', 'Unable to check feedback status'));
  }

  // Event Handlers
  $(document).on('click', '#ratingStars .star', function () {
    setStars(Number($(this).data('value')));
  });

  // Quick rating radio for SQD
  $(document).on('change', 'input[name="sqd_quick_rating"]', function() {
    const value = $(this).val();
    setAllSQDToValue(value);
  });

  $('#serviceSelect').on('change', function() {
    const serviceId = $(this).val();
    if (serviceId) {
      loadServiceOffers(serviceId);
    } else {
      $('#serviceOfferSelect').empty().append(`<option value="" disabled selected>Select a service offer</option>`);
    }
  });

  // Form submission handler
  $('#feedbackForm').on('submit', function(e) {
    console.log('Form submission triggered');
    e.preventDefault();
    addFeedback();
  });

  // Alternative button click handler
  $('#submitFeedback').on('click', function(e) {
    console.log('Submit button clicked');
    e.preventDefault();
    addFeedback();
  });

  // Update event handlers for cards
  $('#feedbackCards').on('click', '.view', function(){
    const id = $(this).closest('.feedback-card').data('id');
    viewFeedback(id);
  });

  $('#feedbackCards').on('click', '.view-response', function(){
    const id = $(this).closest('.feedback-card').data('id');
    viewResponse(id);
  });

  $('#feedbackCards').on('click', '.delete', function(){
    const id = $(this).closest('.feedback-card').data('id');
    deleteRow(id);
  });

  // Modal events
  $('#feedbackModal').on('show.bs.modal', function () {
    // Set all SQD ratings to 5 when modal opens
    setAllSQDToValue(5);
  });

  $('#feedbackModal').on('hidden.bs.modal', function () {
    resetForm();
  });

  // Initialize
  setStars(0);
  
  // Set all SQD ratings to 5 on page load
  setTimeout(() => {
    setAllSQDToValue(5);
  }, 100);
  
  Promise.all([loadServices(), loadMyFeedback()]).then(() => {
    console.log('Feedback page initialized successfully');
  }).catch(err => {
    console.error('Initialization error:', err);
    toast('error', 'Failed to initialize page');
  });
});