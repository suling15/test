$(document).ready(function() {
    // Load staff data when page loads
    loadStaff();

    // Add new staff form submission
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');

        // Validate valid_id file
        const validIdFile = $('#add_valid_id')[0].files[0];
        if (!validIdFile) {
            Swal.fire('Error', 'Please upload a valid government ID', 'error');
            return;
        }

        fetch('../connection/create_staff.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            Swal.fire({
                icon: data.status === 'success' ? 'success' : 'error',
                title: data.status === 'success' ? 'Success' : 'Error',
                text: data.message
            });
            if (data.status === 'success') {
                $('#addModal').modal('hide');
                this.reset();
                $('#addPreviewImage').hide();
                $('#addValidIdPreview').hide();
                loadStaff();
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Something went wrong while adding staff.', 'error');
        });
    });

    // Edit form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'edit');

        fetch('../connection/create_staff.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            Swal.fire({
                icon: data.status === 'success' ? 'success' : 'error',
                title: data.status === 'success' ? 'Success' : 'Error',
                text: data.message
            });
            if (data.status === 'success') {
                $('#editModal').modal('hide');
                this.reset();
                loadStaff();
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Something went wrong while updating staff.', 'error');
        });
    });

    // Image preview for add form
    $('#addForm input[name="image"]').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#addPreviewImage').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });

    // Image preview for edit form
    $('#editForm input[name="image"]').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#editPreviewImage').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });

    // Valid ID preview for add form
    $('#add_valid_id').on('change', function() {
        const file = this.files[0];
        const previewDiv = $('#addValidIdPreview');
        previewDiv.empty().hide();
        
        if (file) {
            if (file.type === 'application/pdf') {
                previewDiv.html(`
                    <div class="alert alert-info">
                        <i class="fas fa-file-pdf fa-2x"></i>
                        <p>PDF File: ${file.name}</p>
                    </div>
                `).show();
            } else if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewDiv.html(`
                        <img src="${e.target.result}" class="img-thumbnail" style="max-height: 150px;" alt="ID Preview">
                    `).show();
                }
                reader.readAsDataURL(file);
            }
        }
    });

    // Valid ID preview for edit form
    $('#edit_valid_id').on('change', function() {
        const file = this.files[0];
        const previewDiv = $('#editValidIdPreview');
        previewDiv.empty().hide();
        
        if (file) {
            if (file.type === 'application/pdf') {
                previewDiv.html(`
                    <div class="alert alert-info">
                        <i class="fas fa-file-pdf fa-2x"></i>
                        <p>PDF File: ${file.name}</p>
                    </div>
                `).show();
            } else if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewDiv.html(`
                        <img src="${e.target.result}" class="img-thumbnail" style="max-height: 150px;" alt="ID Preview">
                    `).show();
                }
                reader.readAsDataURL(file);
            }
        }
    });
});

// Load all staff members
function loadStaff() {
    fetch('../connection/create_staff.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=fetch'
    })
    .then(res => res.json())
    .then(data => {
        const tbody = document.querySelector('#staffTable tbody');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No staff members found</td></tr>';
            return;
        }

        data.forEach((staff, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td>${staff.username}</td>
                <td>${staff.firstname} ${staff.middlename || ''} ${staff.lastname}</td>
                <td>${staff.gender}</td>
                <td>${staff.birthday}</td>
                <td>${staff.contact_number}</td>
                <td>
                    <button class="icon-btn text-info view-btn mr-2" title="View" data-id="${staff.id}">
                        <i class="fas fa-eye" title="view"></i>
                    </button>
                    <button class="icon-btn text-warning edit-btn mr-1" title="Edit" data-id="${staff.id}">
                        <i class="fas fa-edit" title="edit"></i>
                    </button>
                    <button class="icon-btn text-danger delete-btn" title="Delete" data-id="${staff.id}">
                        <i class="fas fa-trash" title="delete"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Add event listeners after table is populated
        addTableEventListeners();
    })
    .catch(() => {
        Swal.fire('Error', 'Failed to load staff data.', 'error');
    });
}

// Add event listeners to table buttons
function addTableEventListeners() {
    // View button
    $(document).on('click', '.view-btn', function() {
        const id = $(this).data('id');
        viewStaff(id);
    });

    // Edit button
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        fetchStaffById(id);
    });

    // Delete button
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteStaff(id);
    });
}

// View staff details with valid ID download option
function viewStaff(id) {
    const formData = new FormData();
    formData.append('action', 'fetchOne');
    formData.append('id', id);

    fetch('../connection/create_staff.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(staff => {
        const imageUrl = staff.image ? `../staff_image/${staff.image}` : 'https://via.placeholder.com/150';
        
        // Create valid ID display with download option
        let validIdHtml = 'N/A';
        if (staff.valid_id) {
            const validIdExt = staff.valid_id.split('.').pop().toLowerCase();
            const validIdUrl = `../uploads/staff_validID/${staff.valid_id}`;
            
            if (['jpg', 'jpeg', 'png', 'gif'].includes(validIdExt)) {
                validIdHtml = `
                    <div class="text-center">
                        <img src="${validIdUrl}" class="img-fluid rounded mb-2" style="max-height: 200px;" alt="Valid ID">
                        <br>
                        <a href="${validIdUrl}" download="${staff.valid_id}" class="btn btn-sm btn-success mt-2">
                            <i class="fas fa-download"></i> Download Valid ID
                        </a>
                    </div>
                `;
            } else if (validIdExt === 'pdf') {
                validIdHtml = `
                    <div class="text-center">
                        <a href="${validIdUrl}" target="_blank" class="btn btn-info mb-2">
                            <i class="fas fa-file-pdf"></i> View PDF ID
                        </a>
                        <br>
                        <a href="${validIdUrl}" download="${staff.valid_id}" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download PDF ID
                        </a>
                    </div>
                `;
            } else {
                validIdHtml = `
                    <div class="text-center">
                        <a href="${validIdUrl}" target="_blank" class="btn btn-secondary mb-2">
                            <i class="fas fa-file"></i> View ID File
                        </a>
                        <br>
                        <a href="${validIdUrl}" download="${staff.valid_id}" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download ID File
                        </a>
                    </div>
                `;
            }
        }
        
        Swal.fire({
            title: `${staff.firstname} ${staff.lastname}`,
            html: `
                <div class="text-center mb-3">
                    <img src="${imageUrl}" class="img-fluid rounded mb-3" style="max-height: 200px;" alt="Profile Image">
                </div>
                <p><strong>Username:</strong> ${staff.username}</p>
                <p><strong>Full Name:</strong> ${staff.firstname} ${staff.middlename || ''} ${staff.lastname}</p>
                <p><strong>Gender:</strong> ${staff.gender}</p>
                <p><strong>Birthday:</strong> ${staff.birthday}</p>
                <p><strong>Contact:</strong> ${staff.contact_number || 'N/A'}</p>
                <p><strong>Address:</strong> ${staff.address || 'N/A'}</p>
                <p><strong>Valid ID:</strong></p>
                <div class="text-center">${validIdHtml}</div>
            `,
            width: '700px',
            confirmButtonText: 'Close',
            confirmButtonColor: '#3085d6'
        });
    })
    .catch(() => {
        Swal.fire('Error', 'Failed to fetch staff details.', 'error');
    });
}

// Fetch staff by ID for editing
function fetchStaffById(id) {
    const formData = new FormData();
    formData.append('action', 'fetchOne');
    formData.append('id', id);

    fetch('../connection/create_staff.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(staff => {
        if (staff) {
            openEditModal(staff);
        } else {
            Swal.fire('Error', 'Staff data not found', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to fetch staff details.', 'error');
    });
}

// Open edit modal with staff data and valid ID download option
function openEditModal(staff) {
    const $modal = $('#editModal');
    
    // Populate form fields
    $modal.find('[name="id"]').val(staff.id);
    $modal.find('[name="username"]').val(staff.username || '');
    $modal.find('[name="firstname"]').val(staff.firstname || '');
    $modal.find('[name="middlename"]').val(staff.middlename || '');
    $modal.find('[name="lastname"]').val(staff.lastname || '');
    $modal.find('[name="gender"]').val(staff.gender || '');
    $modal.find('[name="birthday"]').val(staff.birthday || '');
    $modal.find('[name="address"]').val(staff.address || '');
    $modal.find('[name="contact_number"]').val(staff.contact_number || '');
    
    // Handle image preview
    const $imgPreview = $modal.find('#editPreviewImage');
    if (staff.image) {
        $imgPreview.attr('src', `../staff_image/${staff.image}`).show();
    } else {
        $imgPreview.hide();
    }
    
    // Handle valid ID display with download option
    const $currentValidId = $modal.find('#currentValidId');
    if (staff.valid_id) {
        const validIdExt = staff.valid_id.split('.').pop().toLowerCase();
        const validIdUrl = `../uploads/staff_validID/${staff.valid_id}`;
        
        let validIdHtml = '<p class="text-muted">Current Valid ID:</p>';
        if (['jpg', 'jpeg', 'png', 'gif'].includes(validIdExt)) {
            validIdHtml += `
                <div class="text-center">
                    <img src="${validIdUrl}" class="img-thumbnail" style="max-height: 150px;" alt="Current Valid ID">
                    <br>
                    <a href="${validIdUrl}" download="${staff.valid_id}" class="btn btn-sm btn-success mt-2">
                        <i class="fas fa-download"></i> Download Current ID
                    </a>
                </div>
            `;
        } else if (validIdExt === 'pdf') {
            validIdHtml += `
                <div class="text-center">
                    <a href="${validIdUrl}" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-file-pdf"></i> View Current ID
                    </a>
                    <br>
                    <a href="${validIdUrl}" download="${staff.valid_id}" class="btn btn-sm btn-success mt-2">
                        <i class="fas fa-download"></i> Download Current ID
                    </a>
                </div>
            `;
        } else {
            validIdHtml += `
                <div class="text-center">
                    <a href="${validIdUrl}" target="_blank" class="btn btn-sm btn-secondary">
                        <i class="fas fa-file"></i> View Current ID
                    </a>
                    <br>
                    <a href="${validIdUrl}" download="${staff.valid_id}" class="btn btn-sm btn-success mt-2">
                        <i class="fas fa-download"></i> Download Current ID
                    </a>
                </div>
            `;
        }
        
        $currentValidId.html(validIdHtml).show();
    } else {
        $currentValidId.html('<p class="text-muted">No valid ID uploaded</p>').show();
    }
    
    // Show modal
    $modal.modal('show');
}

// Delete staff member
function deleteStaff(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete this staff member!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('../connection/create_staff.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    icon: data.status === 'success' ? 'success' : 'error',
                    title: data.status === 'success' ? 'Deleted!' : 'Error',
                    text: data.message
                });
                if (data.status === 'success') {
                    loadStaff();
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Something went wrong while deleting.', 'error');
            });
        }
    });
}