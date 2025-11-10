// Global variables
let dataTable;

$(document).ready(function() {
    initializeDataTable();

    // Add new citizen form submission
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');

        // Client-side validation
        if (!$('#add_username').val() || !$('#add_password').val() || !$('#add_firstname').val() || !$('#add_lastname').val()) {
            Swal.fire('Error', 'Please fill all required fields', 'error');
            return;
        }

        // Validate valid_id file
        const validIdFile = $('#add_valid_id')[0].files[0];
        if (!validIdFile) {
            Swal.fire('Error', 'Please upload a valid government ID', 'error');
            return;
        }

        fetch('../connection/create_citizen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message
                });
                $('#addModal').modal('hide');
                this.reset();
                $('#addPreviewImage').hide();
                $('#addValidIdPreview').hide();
                reloadDataTable();
            } else {
                throw new Error(data.message || 'Failed to add citizen');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to add citizen'
            });
        });
    });

    // Edit form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'edit');

        fetch('../connection/create_citizen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message
                });
                $('#editModal').modal('hide');
                reloadDataTable();
            } else {
                throw new Error(data.message || 'Failed to update citizen');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to update citizen'
            });
        });
    });

    // Image preview for add form
    $('#add_image').on('change', function() {
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
    $('#edit_image').on('change', function() {
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

    // Initialize DataTable
    function initializeDataTable() {
    dataTable = $('#citizenTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "../connection/create_citizen.php",
            "type": "POST",
            "data": {
                "action": "fetch"
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
            { "data": "username" },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return `${row.firstname} ${row.middlename || ''} ${row.lastname}`;
                }
            },
            { "data": "gender" },
            { "data": "birthday" },
            { 
                "data": "contact_number",
                "render": function(data, type, row) {
                    return data || 'N/A';
                }
            },
            { 
                "data": "status",
                "render": function(data, type, row) {
                    let badgeClass = 'warning';
                    if (data === 'approved') badgeClass = 'success';
                    if (data === 'rejected') badgeClass = 'danger';
                    
                    const statusText = data ? data.charAt(0).toUpperCase() + data.slice(1) : 'Pending';
                    return `<span class="badge badge-${badgeClass}">${statusText}</span>`;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    let actionButtons = `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-btn" data-id="${row.id}" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                    `;
                    
                    if (row.status === 'pending') {
                        actionButtons += `
                            <button class="btn btn-sm btn-success approve-btn" data-id="${row.id}" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger reject-btn" data-id="${row.id}" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                    }
                    
                    actionButtons += `</div>`;
                    return actionButtons;
                },
                "orderable": false
            }
        ],
        "responsive": true,
        "lengthChange": true,
        "pageLength": 10, // Default to 10 entries
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]], // Entries options
        "autoWidth": false,
        "buttons": [
            {
                extend: 'copy',
                className: 'btn btn-secondary',
                text: '<i class="fas fa-copy"></i> Copy'
            },
            {
                extend: 'excel',
                className: 'btn btn-success',
                text: '<i class="fas fa-file-excel"></i> Excel'
            },
            {
                extend: 'pdf',
                className: 'btn btn-danger',
                text: '<i class="fas fa-file-pdf"></i> PDF'
            },
            {
                extend: 'print',
                className: 'btn btn-info',
                text: '<i class="fas fa-print"></i> Print'
            },
            {
                extend: 'colvis',
                className: 'btn btn-warning',
                text: '<i class="fas fa-eye"></i> Columns'
            }
        ],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>' +
               '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6">>',
        "language": {
            "emptyTable": "No citizen accounts found",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "lengthMenu": "Show _MENU_ entries",
            "loadingRecords": "Loading...",
            "processing": "Processing...",
            "search": "Search:",
            "zeroRecords": "No matching records found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "initComplete": function() {
            // Add event listeners after table is initialized
            addTableEventListeners();
            
            // Add custom styling to length menu
            $('.dataTables_length label').addClass('mb-0');
            $('.dataTables_length select').addClass('custom-select custom-select-sm');
        }
    });

    // Add buttons to the table
    dataTable.buttons().container().appendTo('#citizenTable_wrapper .col-md-6:eq(1)');
}

    // Reload DataTable
    function reloadDataTable() {
        if (dataTable) {
            dataTable.ajax.reload(null, false);
        }
    }

    // Add event listeners to table buttons
    function addTableEventListeners() {
        // View button
        $(document).on('click', '.view-btn', function() {
            const id = $(this).data('id');
            viewCitizen(id);
        });

        // Edit button
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            fetchCitizenById(id);
        });

        // Delete button
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            deleteCitizen(id);
        });

        // Approve button
        $(document).on('click', '.approve-btn', function() {
            const id = $(this).data('id');
            updateCitizenStatus(id, 'approved');
        });

        // Reject button
        $(document).on('click', '.reject-btn', function() {
            const id = $(this).data('id');
            updateCitizenStatus(id, 'rejected');
        });
    }

    // View citizen details using Bootstrap modal
    function viewCitizen(id) {
        const formData = new FormData();
        formData.append('action', 'fetchOne');
        formData.append('id', id);

        fetch('../connection/create_citizen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(citizen => {
            // Populate the view modal with citizen data
            $('#view_username').val(citizen.username);
            $('#view_firstname').val(citizen.firstname);
            $('#view_middlename').val(citizen.middlename || '');
            $('#view_lastname').val(citizen.lastname);
            $('#view_gender').val(citizen.gender);
            $('#view_birthday').val(citizen.birthday);
            $('#view_civil_status').val(citizen.civil_status);
            $('#view_address').val(citizen.address || '');
            $('#view_contact_number').val(citizen.contact_number || 'N/A');
            
            // Set status with appropriate styling
            let statusBadgeClass = 'warning';
            let statusText = 'Pending';
            if (citizen.status === 'approved') {
                statusBadgeClass = 'success';
                statusText = 'Approved';
            } else if (citizen.status === 'rejected') {
                statusBadgeClass = 'danger';
                statusText = 'Rejected';
            }
            $('#view_status').val(statusText).removeClass('bg-warning bg-success bg-danger text-white').addClass(`bg-${statusBadgeClass} text-white`);
            
            // Handle profile image
            const $profileImage = $('#view_profile_image');
            const $noImage = $('#view_no_image');
            if (citizen.image) {
                $profileImage.attr('src', `../connection/create_citizen.php?action=viewProfileImage&id=${citizen.id}`).show();
                $noImage.hide();
            } else {
                $profileImage.hide();
                $noImage.show();
            }
            
            // Handle valid ID
            const $validIdContainer = $('#view_valid_id');
            if (citizen.valid_id) {
                const validIdUrl = `../connection/create_citizen.php?action=viewValidId&id=${citizen.id}`;
                const validIdExt = citizen.valid_id.split('.').pop().toLowerCase();
                
                let validIdHtml = '';
                if (['jpg', 'jpeg', 'png', 'gif'].includes(validIdExt)) {
                    validIdHtml = `
                        <div class="text-center">
                            <img src="${validIdUrl}" class="img-thumbnail" style="max-height: 200px;" alt="Current Valid ID">
                            <br>
                            <a href="${validIdUrl}" download="${citizen.valid_id}" class="btn btn-sm btn-success mt-2">
                                <i class="fas fa-download"></i> Download Valid ID
                            </a>
                        </div>
                    `;
                } else if (validIdExt === 'pdf') {
                    validIdHtml = `
                        <div class="text-center">
                            <a href="${validIdUrl}" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-file-pdf"></i> View PDF ID
                            </a>
                            <br>
                            <a href="${validIdUrl}" download="${citizen.valid_id}" class="btn btn-sm btn-success mt-2">
                                <i class="fas fa-download"></i> Download PDF ID
                            </a>
                        </div>
                    `;
                } else {
                    validIdHtml = `
                        <div class="text-center">
                            <a href="${validIdUrl}" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="fas fa-file"></i> View ID File
                            </a>
                            <br>
                            <a href="${validIdUrl}" download="${citizen.valid_id}" class="btn btn-sm btn-success mt-2">
                                <i class="fas fa-download"></i> Download ID File
                            </a>
                        </div>
                    `;
                }
                $validIdContainer.html(validIdHtml);
            } else {
                $validIdContainer.html('<div class="alert alert-warning">No valid ID uploaded</div>');
            }
            
            // Update modal title with citizen name
            $('#viewModalLabel').html(`<i class="fas fa-eye mr-2"></i> View ${citizen.firstname} ${citizen.lastname}`);
            
            // Show the modal
            $('#viewModal').modal('show');
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to fetch citizen details: ' + error.message, 'error');
        });
    }

    // Fetch citizen by ID for editing
    function fetchCitizenById(id) {
        const formData = new FormData();
        formData.append('action', 'fetchOne');
        formData.append('id', id);

        fetch('../connection/create_citizen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(citizen => {
            if (citizen) {
                openEditModal(citizen);
            } else {
                throw new Error('Citizen data not found');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to fetch citizen details: ' + error.message, 'error');
        });
    }

    // Open edit modal with citizen data
    function openEditModal(citizen) {
        const $modal = $('#editModal');
        
        // Populate form fields
        $modal.find('#edit_id').val(citizen.id);
        $modal.find('#edit_username').val(citizen.username || '');
        $modal.find('#edit_firstname').val(citizen.firstname || '');
        $modal.find('#edit_middlename').val(citizen.middlename || '');
        $modal.find('#edit_lastname').val(citizen.lastname || '');
        $modal.find('#edit_gender').val(citizen.gender || '');
        $modal.find('#edit_birthday').val(citizen.birthday || '');
        $modal.find('#edit_civil_status').val(citizen.civil_status || '');
        $modal.find('#edit_address').val(citizen.address || '');
        $modal.find('#edit_contact_number').val(citizen.contact_number || '');
        
        // Handle image preview using PHP endpoint
        const $imgPreview = $modal.find('#editPreviewImage');
        if (citizen.image) {
            $imgPreview.attr('src', `../connection/create_citizen.php?action=viewProfileImage&id=${citizen.id}`).show();
        } else {
            $imgPreview.hide();
        }
        
        // Handle valid ID display using PHP endpoint
        const $currentValidId = $modal.find('#currentValidId');
        if (citizen.valid_id) {
            const validIdUrl = `../connection/create_citizen.php?action=viewValidId&id=${citizen.id}`;
            const validIdExt = citizen.valid_id.split('.').pop().toLowerCase();
            
            let validIdHtml = '<p class="text-muted">Current Valid ID:</p>';
            if (['jpg', 'jpeg', 'png', 'gif'].includes(validIdExt)) {
                validIdHtml += `
                    <div class="text-center">
                        <img src="${validIdUrl}" class="img-thumbnail" style="max-height: 150px;" alt="Current Valid ID">
                        <br>
                        <a href="${validIdUrl}" download="${citizen.valid_id}" class="btn btn-sm btn-success mt-2">
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
                        <a href="${validIdUrl}" download="${citizen.valid_id}" class="btn btn-sm btn-success mt-2">
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
                        <a href="${validIdUrl}" download="${citizen.valid_id}" class="btn btn-sm btn-success mt-2">
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

    // Update citizen status (Approve/Reject)
    function updateCitizenStatus(id, status) {
        const actionText = status === 'approved' ? 'approve' : 'reject';
        
        Swal.fire({
            title: `Are you sure?`,
            text: `You are about to ${actionText} this citizen account.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: status === 'approved' ? '#28a745' : '#dc3545',
            confirmButtonText: `Yes, ${actionText} it!`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'updateStatus');
                formData.append('id', id);
                formData.append('status', status);

                fetch('../connection/create_citizen.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Success', `Citizen ${status} successfully`, 'success');
                        reloadDataTable();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Failed to update status', 'error');
                });
            }
        });
    }

    // Delete citizen function
    function deleteCitizen(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will permanently delete this citizen account and all their profile data!",
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

                fetch('../connection/create_citizen.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        reloadDataTable();
                    } else {
                        throw new Error(data.message || 'Failed to delete citizen');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to delete citizen'
                    });
                });
            }
        });
    }

    // Reset modals when closed
    $('#addModal').on('hidden.bs.modal', function() {
        $('#addForm')[0].reset();
        $('#addPreviewImage').hide();
        $('#addValidIdPreview').hide();
    });
    
    $('#editModal').on('hidden.bs.modal', function() {
        $('#editForm')[0].reset();
        $('#editPreviewImage').hide();
        $('#currentValidId').empty();
    });
    
    $('#viewModal').on('hidden.bs.modal', function() {
        $('#view_status').removeClass('bg-warning bg-success bg-danger text-white');
    });

    // Handle file input labels
    $('input[type="file"]').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        const label = $(this).siblings('.custom-file-label');
        if (label.length) {
            label.html(fileName);
        }
    });
});