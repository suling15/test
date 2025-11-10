$(document).ready(function() {
    // Initialize DataTable with export buttons
    const staffTable = $('#staffTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "dom": '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "buttons": [
            {
                extend: 'copy',
                className: 'btn btn-secondary btn-sm',
                text: '<i class="fas fa-copy"></i> Copy',
                exportOptions: {
                    columns: [0, 2, 3, 4, 5, 6] // Skip username column (index 1)
                }
            },
            {
                extend: 'excel',
                className: 'btn btn-success btn-sm',
                text: '<i class="fas fa-file-excel"></i> Excel',
                exportOptions: {
                    columns: [0, 2, 3, 4, 5, 6] // Skip username column
                }
            },
            {
                extend: 'pdf',
                className: 'btn btn-danger btn-sm',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                exportOptions: {
                    columns: [0, 2, 3, 4, 5, 6] // Skip username column
                },
                customize: function(doc) {
                    doc.content[1].table.widths = 
                        Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    doc.defaultStyle.fontSize = 8;
                    doc.styles.tableHeader.fontSize = 9;
                    // Add government header to PDF
                    doc.content.splice(0, 0, {
                        text: 'REPUBLIC OF THE PHILIPPINES\nCITY OF CADIZ\nSTAFF MASTER LIST',
                        style: 'header',
                        alignment: 'center',
                        margin: [0, 0, 0, 10]
                    });
                    doc.styles.header = {
                        fontSize: 14,
                        bold: true,
                        alignment: 'center'
                    };
                }
            },
            {
                extend: 'print',
                className: 'btn btn-info btn-sm',
                text: '<i class="fas fa-print"></i> Quick Print',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6], // Skip username column (index 1)
                    stripHtml: false
                },
                customize: function(win) {
                    // Remove username column from print view
                    $(win.document).find('th:nth-child(2), td:nth-child(2)').hide();
                    
                    // Re-index the serial numbers after hiding username column
                    $(win.document).find('td:first-child').each(function(index) {
                        $(this).text(index + 1);
                    });
                    
                    // Update table headers after removing username
                    $(win.document).find('th').each(function(index) {
                        if (index === 1) { // This was the username header, now it's full name
                            $(this).text('Full Name');
                        } else if (index > 1) { // Shift other headers left
                            const headers = ['#', 'Full Name', 'Assigned Services', 'Gender', 'Birthday', 'Contact Number'];
                            if (index - 1 < headers.length) {
                                $(this).text(headers[index - 1]);
                            }
                        }
                    });
                    
                    // Add government header
                    $(win.document.body).prepend(`
                        <div class="print-government-header">
                            <div class="republic">REPUBLIC OF THE PHILIPPINES</div>
                            <div class="city">CITY OF CADIZ</div>
                            <div class="document-title">STAFF MASTER LIST</div>
                            <div class="print-date">As of: ${new Date().toLocaleDateString()}</div>
                        </div>
                    `);
                    
                    // Add footer
                    $(win.document.body).append(`
                        <div class="print-footer">
                            <table style="width: 100%; font-size: 9pt;">
                                <tr>
                                    <td>Prepared by: Administration Office</td>
                                    <td style="text-align: center;">Official Document</td>
                                    <td style="text-align: right;">Page 1 of 1</td>
                                </tr>
                            </table>
                        </div>
                    `);
                    
                    // Apply government styling
                    $(win.document.body).find('table')
                        .removeClass('table table-bordered table-striped')
                        .addClass('government-table')
                        .css({
                            'font-size': '10pt',
                            'border': '2px solid #000',
                            'width': '100%'
                        });
                    
                    $(win.document.body).css({
                        'font-family': '"Times New Roman", Times, serif',
                        'font-size': '12pt',
                        'color': '#000',
                        'background': 'white',
                        'margin': '0',
                        'padding': '0'
                    });
                    
                    // Style table headers and cells
                    $(win.document.body).find('th').css({
                        'background': '#d9d9d9',
                        'color': '#000',
                        'font-weight': 'bold',
                        'border': '1px solid #000',
                        'padding': '8px 6px',
                        'text-align': 'center'
                    });
                    
                    $(win.document.body).find('td').css({
                        'border': '1px solid #000',
                        'padding': '6px',
                        'text-align': 'left'
                    });
                    
                    // Style service badges
                    $(win.document.body).find('.service-badge').css({
                        'background': '#d9d9d9',
                        'color': '#000',
                        'border': '1px solid #666',
                        'padding': '1px 4px',
                        'margin': '1px',
                        'font-size': '8pt'
                    });
                    
                    // Auto-print
                    setTimeout(function() {
                        win.print();
                    }, 250);
                }
            }
        ],
        "language": {
            "emptyTable": "No staff members found",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "lengthMenu": "Show _MENU_ entries",
            "search": "Search:",
            "zeroRecords": "No matching records found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            },
            "buttons": {
                "copy": "Copy",
                "copyTitle": "Copy to clipboard",
                "copySuccess": {
                    "_": "Copied %d rows to clipboard",
                    "1": "Copied 1 row to clipboard"
                }
            }
        },
        "columnDefs": [
            {
                "targets": -1, // Last column (Actions)
                "orderable": false,
                "searchable": false
            },
            {
                "targets": 1, // Username column
                "render": function(data, type, row) {
                    if (type === 'print' || type === 'pdf' || type === 'excel' || type === 'csv') {
                        return ''; // Hide username in exports
                    }
                    return data;
                }
            },
            {
                "targets": 3, // Services column
                "render": function(data, type, row) {
                    if (type === 'export') {
                        // For export, return plain text
                        return $(data).text() || 'None';
                    }
                    return data;
                }
            }
        ]
    });

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });
    
    // Load staff data when page loads
    loadStaff();

    // Add new staff form submission
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');

        // Get selected services and add them to formData
        const selectedServices = $('#addservices').val() || [];
        selectedServices.forEach(serviceId => {
            formData.append('services[]', serviceId);
        });

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
                $('#addservices').val(null).trigger('change');
                loadStaff();
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            Swal.fire('Error', 'Something went wrong while adding staff.', 'error');
        });
    });

    // Edit form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'edit');
        
        // Get selected services and add them to formData
        const selectedServices = $('#editservices').val() || [];
        selectedServices.forEach(serviceId => {
            formData.append('services[]', serviceId);
        });

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
                $('#editPreviewImage').hide();
                $('#editValidIdPreview').hide();
                loadStaff();
            }
        })
        .catch((error) => {
            console.error('Error:', error);
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
        const staffTable = $('#staffTable').DataTable();
        
        // Clear the table
        staffTable.clear().draw();
        
        if (data.length === 0) {
            return;
        }

        data.forEach((staff, index) => {
            // Format assigned services as badges
            let servicesHtml = 'None';
            if (staff.services && staff.services.length > 0) {
                servicesHtml = staff.services.map(service => 
                    `<span class="badge badge-info service-badge">${service.name}</span>`
                ).join('');
            }
            
            // Add row to DataTable
            staffTable.row.add([
                index + 1,
                staff.username,
                `${staff.firstname} ${staff.middlename || ''} ${staff.lastname}`,
                servicesHtml,
                staff.gender,
                staff.birthday,
                staff.contact_number,
                `
                    <div class="action-buttons">
                        <button class="btn-icon btn-view view-btn" title="View" data-id="${staff.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-edit edit-btn" title="Edit" data-id="${staff.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-print print-btn" title="Print Details" data-id="${staff.id}">
                            <i class="fas fa-print"></i>
                        </button>
                        <button class="btn-icon btn-delete delete-btn" title="Delete" data-id="${staff.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `
            ]).draw(false);
        });

        // Add event listeners after table is populated
        addTableEventListeners();
    })
    .catch((error) => {
        console.error('Error:', error);
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

    // Print button
    $(document).on('click', '.print-btn', function() {
        const id = $(this).data('id');
        printStaffDetails(id);
    });

    // Delete button
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteStaff(id);
    });
}

// View staff details in modal
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
        if (staff) {
            openViewModal(staff);
        } else {
            Swal.fire('Error', 'Staff data not found', 'error');
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to fetch staff details.', 'error');
    });
}

// Open view modal with staff data
function openViewModal(staff) {
    const $modal = $('#viewModal');
    
    // Set modal title with staff name
    $modal.find('#viewModalLabel').text(`Staff Details - ${staff.firstname} ${staff.lastname}`);
    
    // Populate form fields
    $('#viewUsername').val(staff.username || '');
    $('#viewFirstname').val(staff.firstname || '');
    $('#viewMiddlename').val(staff.middlename || '');
    $('#viewLastname').val(staff.lastname || '');
    $('#viewGender').val(staff.gender || '');
    $('#viewBirthday').val(staff.birthday || '');
    $('#viewAddress').val(staff.address || '');
    $('#viewContact').val(staff.contact_number || '');
    
    // Handle profile image
    const $profileImg = $('#viewProfileImage');
    if (staff.image) {
        $profileImg.attr('src', `../staff_image/${staff.image}`).show();
    } else {
        $profileImg.hide();
    }
    
    // Handle assigned services
    const $servicesContainer = $('#viewServices');
    if (staff.services && staff.services.length > 0) {
        const servicesHtml = staff.services.map(service => 
            `<span class="badge badge-info service-badge">${service.name}</span>`
        ).join('');
        $servicesContainer.html(servicesHtml);
    } else {
        $servicesContainer.html('<p class="text-muted mb-0">No services assigned</p>');
    }
    
    // Handle valid ID display with download option
    const $validIdContainer = $('#viewValidId');
    if (staff.valid_id) {
        const validIdExt = staff.valid_id.split('.').pop().toLowerCase();
        const validIdUrl = `../uploads/staff_validID/${staff.valid_id}`;
        
        let validIdHtml = '';
        if (['jpg', 'jpeg', 'png', 'gif'].includes(validIdExt)) {
            validIdHtml = `
                <div class="text-center">
                    <img src="${validIdUrl}" class="img-thumbnail mb-2" style="max-height: 150px;" alt="Valid ID">
                    <br>
                    <a href="${validIdUrl}" download="${staff.valid_id}" class="btn btn-sm btn-success">
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
        
        $validIdContainer.html(validIdHtml);
    } else {
        $validIdContainer.html('<p class="text-muted">No valid ID uploaded</p>');
    }
    
    // Show modal
    $modal.modal('show');
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
    .catch((error) => {
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
    
    // Handle service assignment
    const $servicesSelect = $modal.find('#editservices');
    const $assignedServices = $modal.find('#assignedServices');
    
    // Clear previous selections
    $servicesSelect.val(null).trigger('change');
    
    if (staff.services && staff.services.length > 0) {
        // Select the services in the dropdown
        const serviceIds = staff.services.map(service => service.id);
        $servicesSelect.val(serviceIds).trigger('change');
        
        // Display assigned services
        let servicesHtml = '<p class="text-muted">Currently assigned services:</p>';
        servicesHtml += staff.services.map(service => 
            `<span class="badge badge-info service-badge">${service.name}</span>`
        ).join('');
        $assignedServices.html(servicesHtml);
    } else {
        $assignedServices.html('<p class="text-muted">No services assigned yet.</p>');
    }
    
    // Show modal
    $modal.modal('show');
}

// Print staff details
function printStaffDetails(id) {
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
            generatePrintDocument(staff);
        } else {
            Swal.fire('Error', 'Staff data not found', 'error');
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to fetch staff details.', 'error');
    });
}

// Generate printable document
function generatePrintDocument(staff) {
    // Format services
    let servicesHtml = 'None';
    if (staff.services && staff.services.length > 0) {
        servicesHtml = staff.services.map(service => 
            `<span class="service-item">${service.name}</span>`
        ).join('');
    }

    // Format birthday
    const birthday = staff.birthday ? new Date(staff.birthday).toLocaleDateString() : 'Not specified';
    
    // Create print window
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Staff Details - ${staff.firstname} ${staff.lastname}</title>
            <style>
                @page {
                    margin: 0.5in;
                    size: letter;
                }
                
                body {
                    font-family: "Times New Roman", Times, serif;
                    font-size: 12pt;
                    line-height: 1.4;
                    color: #000;
                    background: white;
                    margin: 0;
                    padding: 0;
                }
                
                .government-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 15px;
                }
                
                .republic {
                    font-size: 14pt;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .city {
                    font-size: 16pt;
                    font-weight: bold;
                    margin: 5px 0;
                }
                
                .document-title {
                    font-size: 14pt;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin: 10px 0 5px 0;
                }
                
                .print-date {
                    font-size: 10pt;
                    margin-top: 5px;
                }
                
                .staff-details-container {
                    margin: 20px 0;
                }
                
                .detail-section {
                    margin-bottom: 25px;
                    page-break-inside: avoid;
                }
                
                .section-title {
                    background: #d9d9d9;
                    padding: 8px 12px;
                    font-weight: bold;
                    border: 1px solid #000;
                    margin-bottom: 10px;
                }
                
                .detail-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }
                
                .detail-item {
                    margin-bottom: 8px;
                }
                
                .detail-label {
                    font-weight: bold;
                    margin-bottom: 2px;
                }
                
                .detail-value {
                    padding: 5px 0;
                    border-bottom: 1px solid #ddd;
                }
                
                .full-width {
                    grid-column: 1 / -1;
                }
                
                .services-container {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                    margin-top: 5px;
                }
                
                .service-item {
                    background: #f0f0f0;
                    border: 1px solid #666;
                    padding: 3px 8px;
                    font-size: 10pt;
                    border-radius: 3px;
                }
                
                .photo-section {
                    text-align: center;
                    margin: 15px 0;
                }
                
                .staff-photo {
                    max-width: 200px;
                    max-height: 200px;
                    border: 1px solid #000;
                }
                
                .valid-id-section {
                    margin-top: 15px;
                }
                
                .footer {
                    margin-top: 30px;
                    border-top: 1px solid #000;
                    padding-top: 10px;
                    font-size: 10pt;
                }
                
                .signature-area {
                    margin-top: 40px;
                }
                
                .signature-line {
                    border-top: 1px solid #000;
                    width: 200px;
                    margin: 30px 0 5px 0;
                }
                
                .signature-label {
                    font-size: 10pt;
                }
                
                @media print {
                    body {
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="government-header">
                <div class="republic">REPUBLIC OF THE PHILIPPINES</div>
                <div class="city">CITY OF CADIZ</div>
                <div class="document-title">STAFF PERSONNEL RECORD</div>
                <div class="print-date">Printed on: ${new Date().toLocaleDateString()}</div>
            </div>
            
            <div class="staff-details-container">
                <!-- Personal Information Section -->
                <div class="detail-section">
                    <div class="section-title">PERSONAL INFORMATION</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value">${staff.firstname} ${staff.middlename || ''} ${staff.lastname}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Gender</div>
                            <div class="detail-value">${staff.gender || 'Not specified'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Birthday</div>
                            <div class="detail-value">${birthday}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contact Number</div>
                            <div class="detail-value">${staff.contact_number || 'Not specified'}</div>
                        </div>
                        <div class="detail-item full-width">
                            <div class="detail-label">Address</div>
                            <div class="detail-value">${staff.address || 'Not specified'}</div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Profile Photo Section -->
                ${staff.image ? `
                <div class="detail-section">
                    <div class="section-title">PROFILE PHOTO</div>
                    <div class="photo-section">
                        <img src="../staff_image/${staff.image}" alt="Staff Photo" class="staff-photo" 
                             onerror="this.style.display='none'">
                    </div>
                </div>
                ` : ''}
                
                <!-- Assigned Services Section -->
                <div class="detail-section">
                    <div class="section-title">ASSIGNED SERVICES</div>
                    <div class="services-container">
                        ${servicesHtml}
                    </div>
                </div>
                
                <!-- Valid ID Information -->
                <div class="detail-section">
                    <div class="section-title">VALID ID DOCUMENTATION</div>
                    <div class="valid-id-section">
                        ${staff.valid_id ? 
                            `<div class="detail-item">
                                <div class="detail-label">Valid ID File</div>
                                <div class="detail-value">${staff.valid_id} (Uploaded)</div>
                            </div>` 
                            : '<div class="detail-value">No valid ID uploaded</div>'
                        }
                    </div>
                </div>
                
                <!-- Signature Area -->
                <div class="signature-area">
                    <div class="signature-line"></div>
                    <div class="signature-label">Staff Signature</div>
                </div>
            </div>
            
            <div class="footer">
                <table style="width: 100%; font-size: 9pt;">
                    <tr>
                        <td>Document ID: STAFF-${staff.id}</td>
                        <td style="text-align: center;">Official Personnel Record</td>
                        <td style="text-align: right;">Page 1 of 1</td>
                    </tr>
                </table>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()" style="padding: 10px 20px; font-size: 14pt; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Print Document
                </button>
                <button onclick="window.close()" style="padding: 10px 20px; font-size: 14pt; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                    Close Window
                </button>
            </div>
            
            <script>
                // Auto-print option
                window.onload = function() {
                    window.print();
                };
                
                // Handle after print
                window.onafterprint = function() {
                    // Optional: auto-close after printing
                    // window.close();
                };
            </script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
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
            .catch((error) => {
                console.error('Error:', error);
                Swal.fire('Error', 'Something went wrong while deleting.', 'error');
            });
        }
    });
}