$(document).ready(function() {
    bsCustomFileInput.init();
    
    // Enhanced AJAX handler for profile form
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        let formData = new FormData(this);
        
        // Add debug information
        console.log('Form data entries:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ', pair[1]);
        }
        
        $.ajax({
            url: '../connection/staff_profile_update.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(rawResponse) {
                submitBtn.prop('disabled', false).text('Save Changes');
                
                console.log('Raw response:', rawResponse);
                
                try {
                    let response = typeof rawResponse === 'string' ? 
                                 JSON.parse(rawResponse) : rawResponse;
                    
                    console.log('Parsed response:', response);
                    
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message || 'Profile updated successfully',
                            icon: 'success'
                        }).then(() => {
                            console.log('Uploaded files:', {
                                imagePath: response.imagePath,
                                validIdPath: response.validIdPath
                            });
                            location.reload();
                        });
                    } else {
                        showError(response.message || 'Operation failed');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.log('Raw response that failed to parse:', rawResponse);
                    
                    if (typeof rawResponse === 'string' && rawResponse.includes('success')) {
                        location.reload();
                    } else {
                        showError('Invalid server response format: ' + e.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).text('Save Changes');
                console.error('AJAX error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showError('Request failed: ' + error);
            }
        });
    });
    
    // Similar enhanced handler for account form
    $('#accountForm').on('submit', function(e) {
        e.preventDefault();
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        // Password validation
        let newPassword = $('#new_password').val();
        let confirmPassword = $('#confirm_password').val();
        
        if (newPassword !== '' && newPassword !== confirmPassword) {
            submitBtn.prop('disabled', false).text('Save Changes');
            showError('Password confirmation does not match');
            return;
        }
        
        $.ajax({
            url: '../connection/staff_account_update.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(rawResponse) {
                submitBtn.prop('disabled', false).text('Save Changes');
                
                try {
                    let response = typeof rawResponse === 'string' ? 
                                 JSON.parse(rawResponse) : rawResponse;
                    
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message || 'Account updated successfully',
                            icon: 'success'
                        }).then(() => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                $('#editAccountModal').modal('hide');
                                location.reload();
                            }
                        });
                    } else {
                        showError(response.message);
                    }
                } catch (e) {
                    if (typeof rawResponse === 'string' && rawResponse.includes('success')) {
                        location.reload();
                    } else {
                        showError('Invalid server response');
                    }
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).text('Save Changes');
                showError(xhr.responseText || 'Request failed');
            }
        });
    });
    
    function showError(message) {
        console.error('Error:', message);
        Swal.fire({
            title: 'Error!',
            text: message || 'An unexpected error occurred',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
});