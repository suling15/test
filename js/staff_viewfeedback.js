$(document).ready(function() {
    // Helper functions
    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }
    
    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
    
    function getStarRating(rating) {
      let stars = '';
      for (let i = 1; i <= 5; i++) {
        stars += i <= rating ? 
          '<i class="fas fa-star"></i>' : 
          '<i class="far fa-star"></i>';
      }
      return stars;
    }
    
    function getSentimentInfo(sentiment) {
        const sentimentLower = (sentiment || '').toLowerCase().trim();
        let sentimentIcon = 'fa-meh';
        let sentimentClass = 'sentiment-neutral';
        let sentimentText = 'Neutral';
        
        if (!sentimentLower || sentimentLower === 'null' || sentimentLower === '') {
            sentimentIcon = 'fa-question-circle';
            sentimentClass = 'sentiment-unknown';
            sentimentText = 'Not Analyzed';
        } else if (sentimentLower.includes('positive') || 
                  sentimentLower.includes('happy') ||
                  sentimentLower.includes('good') ||
                  sentimentLower.includes('satisfied') ||
                  sentimentLower.includes('excellent')) {
            sentimentIcon = 'fa-laugh';
            sentimentClass = 'sentiment-positive';
            sentimentText = 'Positive';
        } else if (sentimentLower.includes('negative') || 
                  sentimentLower.includes('angry') ||
                  sentimentLower.includes('bad') ||
                  sentimentLower.includes('poor') ||
                  sentimentLower.includes('disappointed') ||
                  sentimentLower.includes('frustrated')) {
            sentimentIcon = 'fa-angry';
            sentimentClass = 'sentiment-negative';
            sentimentText = 'Negative';
        }
        
        return { sentimentIcon, sentimentClass, sentimentText };
    }

    // Get current month and year for display
    function getCurrentFilterInfo() {
        const monthName = document.getElementById('monthName') ? document.getElementById('monthName').value : 'Current';
        const year = document.getElementById('selectedYear') ? document.getElementById('selectedYear').value : new Date().getFullYear();
        return { monthName, year };
    }

    // Update feedback count display
    function updateFeedbackCount(table) {
        if (!table || !table.page) return;
        
        try {
            const pageInfo = table.page.info();
            const total = pageInfo.recordsTotal;
            const filtered = pageInfo.recordsDisplay;
            const { monthName, year } = getCurrentFilterInfo();
            
            if (total === 0) {
                $('#feedbackCount').html(`No feedback available for your assigned services in ${monthName} ${year}`);
            } else if (filtered === total) {
                $('#feedbackCount').html(`Showing ${total} feedback entries for your assigned services in ${monthName} ${year}`);
            } else {
                $('#feedbackCount').html(`Showing ${filtered} of ${total} feedback entries for your assigned services in ${monthName} ${year}`);
            }
        } catch (error) {
            console.log('Error updating feedback count:', error);
            $('#feedbackCount').html(`Loading feedback data for your assigned services`);
        }
    }

    // Auto-response functionality
    function setupAutoResponseFeatures() {
        // Handle auto-response button clicks
        $('#feedbackTable').on('click', '.generate-auto-response-btn', function() {
            const feedbackId = $(this).data('id');
            const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
            generateAutoResponse(feedbackId, staffId);
        });
        
        $('#feedbackTable').on('click', '.apply-auto-response-btn', function() {
            const feedbackId = $(this).data('id');
            const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
            applyAutoResponse(feedbackId, staffId);
        });
        
        // Handle bulk auto-response
        $('.bulk-auto-respond').on('click', function(e) {
            e.preventDefault();
            const filterType = $(this).data('filter');
            applyBulkAutoResponse(filterType);
        });
        
        // Update respond modal to include auto-response suggestion
        updateRespondModal();
    }

    function generateAutoResponse(feedbackId, staffId) {
        Swal.fire({
            title: 'Generating Auto-Response...',
            text: 'Please wait while we analyze the feedback and generate a response.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '../connection/submit_feedback_response.php',
            method: 'POST',
            data: {
                action: 'generate_auto_response',
                feedback_id: feedbackId,
                staff_id: staffId
            },
            dataType: 'json'
        }).done(function(response) {
            Swal.close();
            if (response.success) {
                showAutoResponsePreview(response.auto_response, feedbackId, staffId);
            } else {
                Swal.fire('Info', response.message, 'info');
            }
        }).fail(function() {
            Swal.fire('Error', 'Failed to generate auto-response', 'error');
        });
    }

    function applyAutoResponse(feedbackId, staffId) {
        Swal.fire({
            title: 'Apply Auto-Response?',
            text: 'This will automatically generate and apply a response based on the feedback content.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Apply Auto-Response',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '../connection/submit_feedback_response.php',
                    method: 'POST',
                    data: {
                        action: 'apply_auto_response',
                        feedback_id: feedbackId,
                        staff_id: staffId
                    },
                    dataType: 'json'
                }).then(response => {
                    if (!response.success) {
                        throw new Error(response.message);
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.responseText || error.message}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Success!', 'Auto-response applied successfully.', 'success');
                table.ajax.reload(null, false);
            }
        });
    }

    function showAutoResponsePreview(autoResponse, feedbackId, staffId) {
        Swal.fire({
            title: 'Auto-Response Generated',
            html: `
                <div class="text-left">
                    <p><strong>Suggested Response:</strong></p>
                    <div class="alert alert-info auto-response-preview">${escapeHtml(autoResponse)}</div>
                    <p class="text-muted small">This response was generated based on the feedback content, sentiment, and rating.</p>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Apply This Response',
            cancelButtonText: 'Use in Reply Form',
            showDenyButton: false,
            customClass: {
                container: 'auto-response-swal'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                applyAutoResponse(feedbackId, staffId);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Pre-fill the respond modal with the auto-response
                $('#responseText').val(autoResponse);
                $('#respondFeedbackModal').modal('show');
            }
        });
    }

    function applyBulkAutoResponse(filterType) {
        const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
        let feedbackIds = [];
        
        table.rows({ search: 'applied' }).every(function() {
            const rowData = this.data();
            let include = false;
            
            switch (filterType) {
                case 'unresponded':
                    include = !rowData.response_text;
                    break;
                case 'negative':
                    const sentiment = (rowData.sentiment || '').toLowerCase();
                    include = !rowData.response_text && (sentiment.includes('negative') || rowData.rating <= 2);
                    break;
                case 'low-rating':
                    include = !rowData.response_text && rowData.rating <= 2;
                    break;
            }
            
            if (include) {
                feedbackIds.push(rowData.id);
            }
        });
        
        if (feedbackIds.length === 0) {
            Swal.fire('Info', 'No matching feedback found for auto-response.', 'info');
            return;
        }
        
        Swal.fire({
            title: `Apply Auto-Response to ${feedbackIds.length} feedback entries?`,
            text: 'This will generate and apply auto-responses based on feedback content.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `Apply to ${feedbackIds.length} entries`,
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const promises = feedbackIds.map(feedbackId => {
                    return $.ajax({
                        url: '../connection/submit_feedback_response.php',
                        method: 'POST',
                        data: {
                            action: 'apply_auto_response',
                            feedback_id: feedbackId,
                            staff_id: staffId
                        },
                        dataType: 'json'
                    });
                });
                
                return Promise.all(promises).then(results => {
                    const successful = results.filter(r => r.success).length;
                    const failed = results.filter(r => !r.success).length;
                    
                    return { successful, failed, total: feedbackIds.length };
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire(
                    'Bulk Auto-Response Complete!',
                    `Successfully applied auto-responses to ${result.value.successful} out of ${result.value.total} feedback entries.`,
                    'success'
                );
                table.ajax.reload(null, false);
            }
        });
    }

    function updateRespondModal() {
        // Add auto-response suggestion button to the respond modal
        const suggestButton = `
            <button type="button" id="suggestAutoResponse" class="btn btn-outline-info btn-sm mb-2">
                <i class="fas fa-robot mr-1"></i>Suggest Auto-Response
            </button>
        `;
        
        // Insert the button before the textarea
        $('#responseText').before(suggestButton);
        
        $('#suggestAutoResponse').on('click', function() {
            const feedbackId = $('#feedbackId').val();
            const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
            
            if (!feedbackId) return;
            
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Generating...');
            
            $.ajax({
                url: '../connection/submit_feedback_response.php',
                method: 'POST',
                data: {
                    action: 'generate_auto_response',
                    feedback_id: feedbackId,
                    staff_id: staffId
                },
                dataType: 'json'
            }).done(function(response) {
                $('#suggestAutoResponse').prop('disabled', false).html('<i class="fas fa-robot mr-1"></i>Suggest Auto-Response');
                if (response.success) {
                    $('#responseText').val(response.auto_response);
                } else {
                    Swal.fire('Info', response.message, 'info');
                }
            }).fail(function() {
                $('#suggestAutoResponse').prop('disabled', false).html('<i class="fas fa-robot mr-1"></i>Suggest Auto-Response');
                Swal.fire('Error', 'Failed to generate auto-response suggestion', 'error');
            });
        });
    }

    // Update the action column to include auto-response buttons
    function getActionColumnRender() {
        return {
            "data": "id",
            "render": function(data, type, row) {
                let buttons = `
                    <button class="btn btn-sm btn-info view-details-btn mr-1" data-id="${data}" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                `;
                
                if (!row.response_text) {
                    buttons += `
                        <div class="btn-group mr-1" role="group">
                            <button class="btn btn-sm btn-success generate-auto-response-btn" data-id="${data}" title="Generate Auto-Response">
                                <i class="fas fa-robot"></i>
                            </button>
                            <button class="btn btn-sm btn-primary apply-auto-response-btn" data-id="${data}" title="Apply Auto-Response">
                                <i class="fas fa-bolt"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-warning respond-btn mr-1" data-id="${data}" title="Manual Response">
                            <i class="fas fa-reply"></i>
                        </button>
                    `;
                } else {
                    buttons += `
                        <button class="btn btn-sm btn-secondary respond-btn mr-1" data-id="${data}" title="Edit Response">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
                
                buttons += `
                    <button class="btn btn-sm btn-outline-secondary print-feedback-btn" data-id="${data}" title="Print Feedback">
                        <i class="fas fa-print"></i>
                    </button>
                `;
                
                return `<div class="btn-group">${buttons}</div>`;
            }
        };
    }

    // Initialize DataTable
    var table = $('#feedbackTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "pageLength": 10,
        "ajax": {
            "url": "../connection/get_feedback_details.php",
            "type": "GET",
            "data": function(d) {
                d.month = document.getElementById('selectedMonth') ? document.getElementById('selectedMonth').value : new Date().getMonth() + 1;
                d.year = document.getElementById('selectedYear') ? document.getElementById('selectedYear').value : new Date().getFullYear();
                d.staff_id = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
            },
            "dataSrc": ""
        },
        "columns": [
            { 
                "data": null,
                "render": function(data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    let html = '<div class="d-flex align-items-center">';
                    
                    if (!row.is_anonymous && row.profile_image) {
                        html += `<img src="../citizen_image/${escapeHtml(row.profile_image)}" class="table-avatar mr-2" alt="Profile">`;
                    } else if (!row.is_anonymous) {
                        html += `<div class="table-avatar-fallback mr-2"><i class="fas fa-user"></i></div>`;
                    } else {
                        html += `<div class="table-avatar-fallback anonymous-avatar mr-2"><i class="fas fa-user-secret"></i></div>`;
                    }
                    
                    html += '<div>';
                    if (row.is_anonymous) {
                        html += '<div class="font-weight-bold">Anonymous</div>';
                    } else {
                        const displayName = row.citizen_fullname && row.citizen_fullname.trim() !== '' ? 
                            escapeHtml(row.citizen_fullname) : 
                            escapeHtml(row.citizen_username);
                        html += `<div class="font-weight-bold">${displayName}</div>`;
                        if (!row.is_anonymous) {
                            html += `<small class="text-muted">${escapeHtml(row.citizen_username)}</small>`;
                        }
                    }
                    html += '</div></div>';
                    
                    return html;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    let html = `<div><strong>${escapeHtml(row.service_name)}</strong>`;
                    if (row.offer_name) {
                        html += `<br><small class="text-muted">${escapeHtml(row.offer_name)}</small>`;
                    }
                    html += '</div>';
                    return html;
                }
            },
            { 
                "data": "feedback_text",
                "render": function(data, type, row) {
                    const truncated = data && data.length > 100 ? data.substring(0, 100) + '...' : data;
                    return `<div class="feedback-text-truncate" title="${escapeHtml(data)}">${escapeHtml(truncated)}</div>`;
                }
            },
            { 
                "data": "rating",
                "render": function(data, type, row) {
                    if (type === 'print') {
                        return `<span class="rating-number">${data}/5</span>`;
                    } else {
                        let stars = '<div class="star-rating">';
                        for (let i = 1; i <= 5; i++) {
                            stars += i <= data ? 
                                '<i class="fas fa-star"></i>' : 
                                '<i class="far fa-star"></i>';
                        }
                        stars += '</div>';
                        stars += `<span class="rating-number">${data}/5</span>`;
                        return stars;
                    }
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    const sentimentInfo = getSentimentInfo(row.sentiment);
                    return `<span class="sentiment-badge-table ${sentimentInfo.sentimentClass}" title="${sentimentInfo.sentimentText}">
                              <i class="fas ${sentimentInfo.sentimentIcon}"></i>
                            </span>`;
                }
            },
            { 
                "data": "create",
                "render": function(data, type, row) {
                    const date = new Date(data);
                    const dateStr = date.toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric', 
                        year: 'numeric' 
                    });
                    const timeStr = date.toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit',
                        hour12: true 
                    });
                    return `<small>${dateStr}</small><br><small class="text-muted">${timeStr}</small>`;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    if (row.response_text) {
                        return `<span class="badge badge-success response-badge" title="Responded on ${formatDate(row.response_date)}">
                                <i class="fas fa-check-circle mr-1"></i>Responded
                              </span>`;
                    } else {
                        return `<span class="badge badge-secondary response-badge">
                                <i class="fas fa-clock mr-1"></i>Pending
                              </span>`;
                    }
                }
            },
            getActionColumnRender()
        ],
        "language": {
            "search": "Search in table:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": emptyTableMessage,
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "emptyTable": emptyTableMessage,
            "loadingRecords": "Loading feedback data...",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            },
            "zeroRecords": zeroRecordsMessage
        },
        "dom": '<"top"lf>rt<"bottom"ip><"clear">',
        "columnDefs": [
            {
                "targets": [0, 8],
                "orderable": false,
                "searchable": false
            },
            {
                "targets": [5, 7],
                "visible": true,
                "className": "no-print"
            }
        ],
        "order": [[6, 'desc']],
        "createdRow": function(row, data, dataIndex) {
            const sentimentInfo = getSentimentInfo(data.sentiment);
            $(row).attr({
                'data-sentiment': sentimentInfo.sentimentClass,
                'data-rating': data.rating,
                'data-service': data.service_name,
                'data-anonymous': data.is_anonymous ? 'anonymous' : 'non-anonymous',
                'data-responded': data.response_text ? 'responded' : 'not-responded'
            });
        },
        "initComplete": function(settings, json) {
            const services = [];
            table.rows().every(function() {
                const service = this.data().service_name;
                if (services.indexOf(service) === -1) {
                    services.push(service);
                }
            });
            
            services.sort();
            const serviceFilter = $('#serviceFilter');
            serviceFilter.empty().append('<option value="">All Services</option>');
            services.forEach(function(service) {
                serviceFilter.append(`<option value="${escapeHtml(service)}">${escapeHtml(service)}</option>`);
            });

            // Initialize auto-response features
            setupAutoResponseFeatures();

            setTimeout(() => {
                updateFeedbackCount(table);
            }, 100);
        }
    });

    // Store the custom filter function reference
    var customFilter = function(settings, data, dataIndex) {
        var sentiment = $('#sentimentFilter').val();
        var service = $('#serviceFilter').val();
        var rating = $('#ratingFilter').val();
        var anonymous = $('#anonymousFilter').val();
        var response = $('#responseFilter').val();
        
        var row = table.row(dataIndex).node();
        if (!row) return true;
        
        var rowSentiment = $(row).data('sentiment');
        var rowService = $(row).data('service');
        var rowRating = $(row).data('rating');
        var rowAnonymous = $(row).data('anonymous');
        var rowResponded = $(row).data('responded');
        
        // Check sentiment filter
        if (sentiment && sentiment !== '') {
            if (sentiment === 'positive' && !rowSentiment.includes('sentiment-positive')) return false;
            if (sentiment === 'negative' && !rowSentiment.includes('sentiment-negative')) return false;
            if (sentiment === 'neutral' && !rowSentiment.includes('sentiment-neutral')) return false;
            if (sentiment === 'unknown' && !rowSentiment.includes('sentiment-unknown')) return false;
        }
        
        // Check service filter
        if (service && service !== '' && rowService !== service) return false;
        
        // Check rating filter
        if (rating && rating !== '' && parseInt(rowRating) !== parseInt(rating)) return false;
        
        // Check anonymous filter
        if (anonymous && anonymous !== '' && rowAnonymous !== anonymous) return false;
        
        // Check response filter
        if (response && response !== '') {
            if (response === 'responded' && rowResponded !== 'responded') return false;
            if (response === 'not-responded' && rowResponded !== 'not-responded') return false;
        }
        
        return true;
    };

    // Apply custom filtering when any filter changes
    $('.dt-custom-filters select').on('change', function() {
        table.draw();
        setTimeout(() => {
            updateFeedbackCount(table);
        }, 100);
    });

    // Register the custom filter
    $.fn.dataTable.ext.search.push(customFilter);

    // Print table functionality
    $('#printTableBtn').on('click', function() {
        table.columns([5, 7]).visible(false);
        
        setTimeout(() => {
            window.print();
            setTimeout(() => {
                table.columns([5, 7]).visible(true);
            }, 500);
        }, 500);
    });

    // Print current view (with filters applied)
    $('#printCurrentViewBtn').on('click', function() {
        table.columns([5, 7]).visible(false);
        
        setTimeout(() => {
            window.print();
            setTimeout(() => {
                table.columns([5, 7]).visible(true);
            }, 500);
        }, 500);
    });

    // Print individual feedback
    $('#feedbackTable').on('click', '.print-feedback-btn', function() {
        const feedbackId = $(this).data('id');
        const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
        
        $.ajax({
            url: '../connection/get_feedback_details.php',
            method: 'GET',
            data: { 
                id: feedbackId,
                staff_id: staffId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    printIndividualFeedback(response.data);
                } else {
                    Swal.fire('Error', 'Failed to load feedback details for printing.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to load feedback details. Please try again.', 'error');
            }
        });
    });

    // Function to print individual feedback
    function printIndividualFeedback(feedback) {
        const isAnonymous = feedback.is_anonymous == 1 || feedback.is_anonymous === true;
        const displayName = isAnonymous ? 'Anonymous Citizen' : 
            (feedback.citizen_fullname && feedback.citizen_fullname.trim() !== '' ? 
                escapeHtml(feedback.citizen_fullname) : 
                escapeHtml(feedback.citizen_username));
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Feedback Details - ${feedback.id}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; color: #000; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .header p { margin: 5px 0; }
                    .section { margin-bottom: 20px; }
                    .section-title { font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    .rating-number { 
                        font-weight: bold; 
                        background: #f8f9fa; 
                        border: 1px solid #dee2e6; 
                        border-radius: 3px; 
                        padding: 4px 8px; 
                        display: inline-block;
                    }
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>CADIZ CITY FEEDBACK REPORT</h1>
                    <p>Individual Feedback Details</p>
                    <p>Generated on: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                </div>
                
                <div class="section">
                    <div class="section-title">CITIZEN INFORMATION</div>
                    <table>
                        <tr><th width="30%">Citizen Name</th><td>${displayName}</td></tr>
                        <tr><th>Service</th><td>${escapeHtml(feedback.service_name)}</td></tr>
                        ${feedback.offer_name ? `<tr><th>Offer</th><td>${escapeHtml(feedback.offer_name)}</td></tr>` : ''}
                        <tr><th>Date Submitted</th><td>${formatDate(feedback.create)}</td></tr>
                        <tr><th>Anonymous</th><td>${isAnonymous ? 'Yes' : 'No'}</td></tr>
                    </table>
                </div>
                
                <div class="section">
                    <div class="section-title">FEEDBACK DETAILS</div>
                    <table>
                        <tr><th width="30%">Rating</th><td><span class="rating-number">${feedback.rating}/5</span></td></tr>
                        <tr><th>Feedback Text</th><td>${escapeHtml(feedback.feedback_text)}</td></tr>
                    </table>
                </div>
                
                <div class="section">
                    <div class="section-title">CITIZEN'S CHARTER QUESTIONS</div>
                    <table>
                        <tr><th width="30%">CC1</th><td>${feedback.CC1 || 'Not answered'}</td></tr>
                        <tr><th>CC2</th><td>${feedback.CC2 || 'Not answered'}</td></tr>
                        <tr><th>CC3</th><td>${feedback.CC3 || 'Not answered'}</td></tr>
                    </table>
                </div>
                
                <div class="section">
                    <div class="section-title">SERVICE QUALITY DIMENSIONS (SQD)</div>
                    ${getSQDDetailsForPrint(feedback)}
                </div>
                
                ${feedback.response_text ? `
                <div class="section">
                    <div class="section-title">STAFF RESPONSE</div>
                    <table>
                        <tr><th width="30%">Response</th><td>${escapeHtml(feedback.response_text)}</td></tr>
                        <tr><th>Response Date</th><td>${formatDate(feedback.response_date)}</td></tr>
                    </table>
                </div>
                ` : ''}
                
                <div class="no-print" style="margin-top: 30px; text-align: center;">
                    <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">Print</button>
                    <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; margin-left: 10px;">Close</button>
                </div>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }

    // Helper function for SQD details in print
    function getSQDDetailsForPrint(feedback) {
        const sqdLabels = [
            'SQD0: I am satisfied with the service that I availed',
            'SQD1: I spent a reasonable amount of time for this transaction',
            'SQD2: The office followed the transaction\'s requirements and steps based on the CC',
            'SQD3: The steps (including payment) I needed to do for this transaction were easy and simple',
            'SQD4: I easily found information about my transaction from the office or its website',
            'SQD5: I paid a reasonable amount of fees for my transaction (including documents, if any)',
            'SQD6: I feel the office was fair to everyone, or "walang palakasan", during my transaction',
            'SQD7: I was treated courteously by the staff, and (if asked for help) the staff was helpful',
            'SQD8: I got what I needed from the government office, or (if denied) denial was sufficiently explained to me'
        ];
        
        let html = '<table>';
        for (let i = 0; i <= 8; i++) {
            const sqdValue = feedback['SQD' + i];
            html += `
                <tr>
                    <th width="70%">${sqdLabels[i]}</th>
                    <td>${sqdValue ? `${sqdValue} - ${getRatingText(sqdValue)}` : 'Not answered'}</td>
                </tr>
            `;
        }
        html += '</table>';
        return html;
    }

    // Handle view details button click
    $('#feedbackTable').on('click', '.view-details-btn', function() {
      const feedbackId = $(this).data('id');
      const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
      
      $('#feedbackDetailsContent').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading details...</p></div>');
      $('#feedbackDetailsModal').modal('show');
      
      $.ajax({
        url: '../connection/get_feedback_details.php',
        method: 'GET',
        data: { 
            id: feedbackId,
            staff_id: staffId
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            displayFeedbackDetails(response.data);
          } else {
            $('#feedbackDetailsContent').html('<div class="alert alert-danger">Error loading feedback details: ' + response.message + '</div>');
          }
        },
        error: function() {
          $('#feedbackDetailsContent').html('<div class="alert alert-danger">Error loading feedback details. Please try again.</div>');
        }
      });
    });

    // Handle respond button click
    $('#feedbackTable').on('click', '.respond-btn', function() {
        const feedbackId = $(this).data('id');
        const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
        
        $('#responseForm')[0].reset();
        $('#feedbackId').val(feedbackId);
        $('#existingResponse').hide();
        
        $('#submitResponseBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Loading...');
        
        $.ajax({
            url: '../connection/get_feedback_details.php',
            method: 'GET',
            data: { 
                id: feedbackId,
                staff_id: staffId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const feedback = response.data;
                    
                    if (feedback.response_text) {
                        $('#respondFeedbackModalLabel').html('<i class="fas fa-edit mr-2"></i>Edit Response');
                        $('#responseText').val(feedback.response_text);
                        $('#existingResponseText').text(feedback.response_text);
                        $('#existingResponseDate').text('Responded on: ' + formatDate(feedback.response_date));
                        $('#existingResponse').show();
                        $('#submitResponseBtn').html('<i class="fas fa-sync-alt mr-2"></i>Update Response');
                    } else {
                        $('#respondFeedbackModalLabel').html('<i class="fas fa-reply mr-2"></i>Respond to Feedback');
                        $('#submitResponseBtn').html('<i class="fas fa-paper-plane mr-2"></i>Submit Response');
                    }
                    
                    $('#respondFeedbackModal').modal('show');
                } else {
                    Swal.fire('Error', 'Failed to load feedback details: ' + response.message, 'error');
                }
                $('#submitResponseBtn').prop('disabled', false);
            },
            error: function() {
                Swal.fire('Error', 'Failed to load feedback details. Please try again.', 'error');
                $('#submitResponseBtn').prop('disabled', false);
            }
        });
    });

    // Handle response form submission
    $('#responseForm').on('submit', function(e) {
        e.preventDefault();
        
        const feedbackId = $('#feedbackId').val();
        const responseText = $('#responseText').val().trim();
        const staffId = document.getElementById('staffId') ? document.getElementById('staffId').value : 0;
        
        if (!responseText) {
            Swal.fire('Error', 'Please enter a response message.', 'warning');
            return;
        }
        
        $('#submitResponseBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...');
        
        $.ajax({
            url: '../connection/submit_feedback_response.php',
            method: 'POST',
            data: {
                action: 'submit_manual',
                feedback_id: feedbackId,
                staff_id: staffId,
                response_text: responseText
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success');
                    $('#respondFeedbackModal').modal('hide');
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
                $('#submitResponseBtn').prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Submit Response');
            },
            error: function() {
                Swal.fire('Error', 'Failed to submit response. Please try again.', 'error');
                $('#submitResponseBtn').prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Submit Response');
            }
        });
    });
    
    function getSQDDetails(feedback) {
      const sqdLabels = [
        'SQD0: I am satisfied with the service that I availed',
        'SQD1: I spent a reasonable amount of time for this transaction',
        'SQD2: The office followed the transaction\'s requirements and steps based on the CC',
        'SQD3: The steps (including payment) I needed to do for this transaction were easy and simple',
        'SQD4: I easily found information about my transaction from the office or its website',
        'SQD5: I paid a reasonable amount of fees for my transaction (including documents, if any)',
        'SQD6: I feel the office was fair to everyone, or "walang palakasan", during my transaction',
        'SQD7: I was treated courteously by the staff, and (if asked for help) the staff was helpful',
        'SQD8: I got what I needed from the government office, or (if denied) denial was sufficiently explained to me'
      ];
      
      let html = '';
      for (let i = 0; i <= 8; i++) {
        const sqdValue = feedback['SQD' + i];
        html += `
          <div class="mb-2">
            <strong>${sqdLabels[i]}:</strong><br>
            ${sqdValue ? 
              `<span class="sqd-rating">${sqdValue}</span> ${getRatingText(sqdValue)}` : 
              'Not answered'
            }
          </div>
        `;
      }
      return html;
    }
    
    function getRatingText(rating) {
      const ratings = {
        1: 'Strongly Disagree',
        2: 'Disagree',
        3: 'Neither Agree nor Disagree',
        4: 'Agree',
        5: 'Strongly Agree'
      };
      return ratings[rating] || 'Unknown';
    }
    
    function displayFeedbackDetails(feedback) {
        const sentimentInfo = getSentimentInfo(feedback.sentiment);
        const isAnonymous = feedback.is_anonymous == 1 || feedback.is_anonymous === true;
        let displayName = 'Anonymous Citizen';
        if (!isAnonymous) {
            if (feedback.citizen_fullname && feedback.citizen_fullname.trim() !== '') {
                displayName = escapeHtml(feedback.citizen_fullname);
            } else {
                displayName = escapeHtml(feedback.citizen_username);
            }
        }
        
        let html = `
            <div class="detail-section">
                <div class="detail-section-title">Sentiment Analysis</div>
                <div class="sentiment-display ${sentimentInfo.sentimentClass}">
                    <i class="fas ${sentimentInfo.sentimentIcon} mr-2"></i>
                    <strong>${sentimentInfo.sentimentText}</strong>
                    ${feedback.sentiment && feedback.sentiment !== 'null' ? ` - ${escapeHtml(feedback.sentiment)}` : ''}
                </div>
            </div>
            
            <div class="detail-section">
                <div class="detail-section-title">Citizen Information</div>
                ${isAnonymous ? 
                    '<p class="text-muted"><i class="fas fa-user-secret mr-2"></i>This feedback was submitted anonymously</p>' :
                    `<p><strong>Full Name:</strong> ${displayName}</p>`
                }
                <p><strong>Service:</strong> ${escapeHtml(feedback.service_name)}</p>
                ${feedback.offer_name ? `<p><strong>Offer:</strong> ${escapeHtml(feedback.offer_name)}</p>` : ''}
                <p><strong>Date:</strong> ${formatDate(feedback.create)}</p>
            </div>
            
            <div class="detail-section">
                <div class="detail-section-title">Feedback</div>
                <p>${escapeHtml(feedback.feedback_text)}</p>
            </div>
            
            <div class="detail-section">
                <div class="detail-section-title">Rating</div>
                <div class="star-rating">
                    ${getStarRating(feedback.rating)}
                </div>
            </div>
            
            <div class="detail-section">
                <div class="detail-section-title">Citizen's Charter (CC) Questions</div>
                <p><strong>CC1:</strong> ${feedback.CC1 || 'Not answered'}</p>
                <p><strong>CC2:</strong> ${feedback.CC2 || 'Not answered'}</p>
                <p><strong>CC3:</strong> ${feedback.CC3 || 'Not answered'}</p>
            </div>
            
            <div class="detail-section">
                <div class="detail-section-title">Service Quality Dimensions (SQD)</div>
                ${getSQDDetails(feedback)}
            </div>
        `;
        
        if (feedback.response_text) {
            html += `
                <div class="detail-section response-section">
                    <div class="detail-section-title">Staff Response</div>
                    <p>${escapeHtml(feedback.response_text)}</p>
                    <p><strong>Responded by:</strong> ${escapeHtml(feedback.responder_name || 'Staff Member')}</p>
                    <p><strong>Response date:</strong> ${formatDate(feedback.response_date)}</p>
                </div>
            `;
        }
        
        $('#feedbackDetailsContent').html(html);
    }
});