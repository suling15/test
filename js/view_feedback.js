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

    // Update feedback count display
    function updateFeedbackCount(table) {
        if (!table || !table.page) return;
        
        try {
            const pageInfo = table.page.info();
            const total = pageInfo.recordsTotal;
            const filtered = pageInfo.recordsDisplay;
            const monthName = months[selectedMonth];
            const year = selectedYear;
            
            if (total === 0) {
                $('#feedbackCount').html(`No feedback available for ${monthName} ${year}`);
            } else if (filtered === total) {
                $('#feedbackCount').html(`Showing ${total} feedback entries for ${monthName} ${year}`);
            } else {
                $('#feedbackCount').html(`Showing ${filtered} of ${total} feedback entries for ${monthName} ${year}`);
            }
        } catch (error) {
            console.log('Error updating feedback count:', error);
            $('#feedbackCount').html(`Loading feedback data for ${months[selectedMonth]} ${selectedYear}`);
        }
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
                d.month = selectedMonth;
                d.year = selectedYear;
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
                    let stars = '<div class="star-rating">';
                    for (let i = 1; i <= 5; i++) {
                        stars += i <= data ? 
                            '<i class="fas fa-star"></i>' : 
                            '<i class="far fa-star"></i>';
                    }
                    stars += '</div>';
                    return stars;
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
                "data": "id",
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-info view-details-btn" data-id="${data}" title="View Details">
                              <i class="fas fa-eye"></i>
                            </button>`;
                }
            }
        ],
        "language": {
            "search": "Search in table:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": `No feedback available for ${months[selectedMonth]} ${selectedYear}`,
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "emptyTable": `No feedback available for ${months[selectedMonth]} ${selectedYear}`,
            "loadingRecords": "Loading feedback data...",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "dom": '<"top"lf>rt<"bottom"ip><"clear">',
        "columnDefs": [
            {
                "targets": [0, 7],
                "orderable": false,
                "searchable": false
            }
        ],
        "order": [[6, 'desc']],
        "createdRow": function(row, data, dataIndex) {
            // Add data attributes for filtering
            const sentimentInfo = getSentimentInfo(data.sentiment);
            $(row).attr({
                'data-sentiment': sentimentInfo.sentimentClass,
                'data-rating': data.rating,
                'data-service': data.service_name,
                'data-anonymous': data.is_anonymous ? 'anonymous' : 'non-anonymous'
            });
        },
        "initComplete": function(settings, json) {
            // Populate service filter after data loads
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

            // Update feedback count after initialization
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
        
        var row = table.row(dataIndex).node();
        if (!row) return true;
        
        var rowSentiment = $(row).data('sentiment');
        var rowService = $(row).data('service');
        var rowRating = $(row).data('rating');
        var rowAnonymous = $(row).data('anonymous');
        
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
        
        return true;
    };

    // Apply custom filtering when any filter changes
    $('.dt-custom-filters select').on('change', function() {
        table.draw();
        // Update count after filter changes
        setTimeout(() => {
            updateFeedbackCount(table);
        }, 100);
    });

    // Register the custom filter
    $.fn.dataTable.ext.search.push(customFilter);

    // Handle view details button click
    $('#feedbackTable').on('click', '.view-details-btn', function() {
      const feedbackId = $(this).data('id');
      
      // Show loading state
      $('#feedbackDetailsContent').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading details...</p></div>');
      $('#feedbackDetailsModal').modal('show');
      
      // Fetch feedback details via AJAX
      $.ajax({
        url: '../connection/get_feedback_details.php',
        method: 'GET',
        data: { id: feedbackId },
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
        // Get sentiment information
        const sentimentInfo = getSentimentInfo(feedback.sentiment);
        
        // Check if feedback is anonymous
        const isAnonymous = feedback.is_anonymous == 1 || feedback.is_anonymous === true;
        
        // Determine display name
        let displayName = 'Anonymous Citizen';
        if (!isAnonymous) {
            if (feedback.citizen_fullname && feedback.citizen_fullname.trim() !== '') {
                displayName = escapeHtml(feedback.citizen_fullname);
            } else {
                displayName = escapeHtml(feedback.citizen_username);
            }
        }
        
        // Create HTML content for the modal
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
        
        // Add response section if exists
        if (feedback.response_text) {
            html += `
                <div class="detail-section">
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