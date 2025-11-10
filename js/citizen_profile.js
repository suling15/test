
    $(document).ready(function() {
        bsCustomFileInput.init();
        
        // Enhanced AJAX handler for profile form
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            let submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
            
            $.ajax({
                url: '../connection/update_profile.php',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(rawResponse) {
                    submitBtn.prop('disabled', false).text('Save changes');
                    
                    try {
                        // First try to parse as JSON
                        let response = typeof rawResponse === 'string' ? 
                                    JSON.parse(rawResponse) : rawResponse;
                        
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message || 'Profile updated successfully',
                                icon: 'success'
                            }).then(() => location.reload());
                        } else {
                            showError(response.message || 'Operation failed');
                        }
                    } catch (e) {
                        // If JSON parse fails but contains "success", assume it worked
                        if (typeof rawResponse === 'string' && rawResponse.includes('success')) {
                            location.reload();
                        } else {
                            showError('Invalid server response format');
                        }
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).text('Save changes');
                    showError(xhr.responseText || 'Request failed');
                }
            });
        });
         // Preview valid ID when a new file is selected
    $('#valid_id').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            const $previewContainer = $('.current-valid-id-preview');
            
            // Update file label
            $(this).next('.custom-file-label').text(file.name);
            
            // Create preview for image files
            if (file.type.match('image.*')) {
                reader.onload = function(e) {
                    // Remove existing preview if any
                    $previewContainer.find('img').remove();
                    $previewContainer.find('.alert').remove();
                    
                    // Add new image preview
                    $previewContainer.prepend(
                        $('<img>').attr('src', e.target.result)
                            .addClass('img-fluid mb-2')
                            .css({'max-height': '200px', 'border': '1px solid #ddd', 'border-radius': '5px'})
                    );
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                // For PDF files, show a different preview
                $previewContainer.find('img').remove();
                $previewContainer.find('.alert').remove();
                
                $previewContainer.prepend(
                    $('<div>').addClass('alert alert-info')
                        .html('<i class="fas fa-file-pdf mr-2"></i> PDF file selected: ' + file.name)
                );
            }
        }
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
                submitBtn.prop('disabled', false).text('Save changes');
                showError('Password confirmation does not match');
                return;
            }
            
            $.ajax({
                url: '../connection/update_account.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(rawResponse) {
                    submitBtn.prop('disabled', false).text('Save changes');
                    
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
                    submitBtn.prop('disabled', false).text('Save changes');
                    showError(xhr.responseText || 'Request failed');
                }
            });
        });
        
        function showError(message) {
            Swal.fire({
                title: 'Error!',
                text: message || 'An unexpected error occurred',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
