<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Accounts</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .main-header {
            background: linear-gradient(135deg, #9e9fd3ff, #858586ff);
        }

        .main-sidebar {
            background-image: url('sidebar.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            z-index: 0;
        }

        .main-sidebar::before {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }

        .sidebar {
            position: relative;
            z-index: 2;
        }

        .nav-sidebar .nav-link {
            color: #cbd5e0;
        }

        .nav-sidebar .nav-link.active {
            background: linear-gradient(135deg, #c3c4c9ff, #8f8f94ff);
            color: #fff;
        }

        .nav-treeview .nav-link {
            padding-left: 2.5rem;
            font-size: 14px;
        }

        .user-panel .info a {
            color: white;
        }

        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .main-footer {
            background-color: #f3f4f6;
            border-top: 1px solid #ddd;
        }

        .avatar {
            width: 45px;
            height: 45px;
        }

        .small-box {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .small-box:hover {
            transform: translateY(-4px);
        }

        .small-box .icon {
            font-size: 3rem;
            position: absolute;
            top: 10px;
            right: 15px;
            color: rgba(255,255,255,0.3);
        }

        .preview-image {
            max-height: 150px;
            margin-bottom: 10px;
        }
        
        .icon-btn {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .icon-btn:focus {
            outline: none;
        }

        .icon-btn:hover {
            background-color: rgba(0,0,0,0.1);
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-1px);
        }
        
        .service-badge {
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.75rem;
        }
        
        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        /* Mobile-specific styles */
        @media (max-width: 991.98px) {
            .main-sidebar {
                z-index: 1050 !important;
            }
            
            .content-wrapper {
                margin-left: 0 !important;
                padding: 10px;
            }

            /* Hide desktop table on mobile */
            .table-responsive {
                display: none;
            }

            /* Show mobile cards */
            .mobile-cards {
                display: block;
            }

            .staff-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                margin-bottom: 15px;
                overflow: hidden;
                transition: transform 0.2s ease;
            }

            .staff-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }

            .staff-card-header {
                background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                padding: 15px;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .staff-avatar {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: linear-gradient(135deg, #007bff, #0056b3);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 18px;
            }

            .staff-name {
                font-weight: 600;
                font-size: 16px;
                color: #333;
                margin: 0 10px;
                flex-grow: 1;
            }

            .staff-actions {
                display: flex;
                gap: 5px;
            }

            .staff-card-body {
                padding: 15px;
            }

            .staff-info-row {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                padding: 5px 0;
            }

            .staff-info-row:last-child {
                margin-bottom: 0;
            }

            .staff-info-icon {
                width: 20px;
                color: #6c757d;
                margin-right: 10px;
                font-size: 14px;
            }

            .staff-info-label {
                font-weight: 600;
                color: #495057;
                min-width: 80px;
                font-size: 14px;
            }

            .staff-info-value {
                color: #6c757d;
                font-size: 14px;
                flex-grow: 1;
            }

            .services-container {
                margin-top: 5px;
            }

            .btn-edit {
                background-color: #ffc107;
                color: #212529;
            }

            .btn-delete {
                background-color: #dc3545;
                color: white;
            }

            .card-header {
                position: sticky;
                top: 0;
                z-index: 100;
                background: white;
            }
        }

        /* Desktop styles */
        @media (min-width: 992px) {
            .mobile-cards {
                display: none;
            }
            
            .table-responsive {
                display: block;
            }
        }

        /* Common styles for both views */
        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background-color: #138496;
            transform: scale(1.1);
        }

        .btn-edit:hover {
            background-color: #e0a800;
            transform: scale(1.1);
        }

        .btn-delete:hover {
            background-color: #c82333;
            transform: scale(1.1);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://via.placeholder.com/160x160" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block">Admin User</a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="admin_dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link active">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Accounts
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="citizen_account.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Citizen Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="staff_account.php" class="nav-link active">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Staff Accounts</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="services.php" class="nav-link">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Services</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content pt-4">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Staff Accounts</h3>
                                <button class="btn float-right" style="background-color:#007bff; color:white;" data-toggle="modal" data-target="#addModal">
                                  <i class="fas fa-plus"></i> Add Staff
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- Desktop Table View -->
                                <div class="table-responsive">
                                    <table id="staffTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Assigned Services</th>
                                                <th>Gender</th>
                                                <th>Birthday</th>
                                                <th>Contact</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <!-- Mobile Cards View -->
                                <div class="mobile-cards" id="mobileStaffCards">
                                    <!-- Mobile cards will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer text-right">
        <strong>© 2024 CADIZ CITY. All rights reserved.</strong>
    </footer>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addModalLabel">Add New Staff</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="firstname">First Name</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="firstname" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="lastname">Last Name</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="lastname" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    </div>
                                    <select class="form-control" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="birthday">Birthday</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="date" class="form-control" name="birthday" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="contact_number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
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
        
        // Get selected services
        const selectedServices = $('#services').val();
        if (selectedServices) {
            selectedServices.forEach(serviceId => {
                formData.append('services[]', serviceId);
            });
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

// Sample staff data for demonstration
const sampleStaffData = [
    {
        id: 1,
        username: "john_doe",
        firstname: "John",
        middlename: "M",
        lastname: "Doe",
        gender: "Male",
        birthday: "2025-08-01",
        contact_number: "0976345678",
        address: "123 Main St",
        services: [
            {id: 1, name: "Birth Certificate"},
            {id: 2, name: "Marriage Certificate"}
        ]
    },
    {
        id: 2,
        username: "jane_smith", 
        firstname: "Jane",
        middlename: "",
        lastname: "Smith",
        gender: "Female",
        birthday: "1990-05-15",
        contact_number: "0912345678",
        address: "456 Oak Ave",
        services: [
            {id: 3, name: "Business Permit"}
        ]
    },
    {
        id: 3,
        username: "mike_wilson",
        firstname: "Mike",
        middlename: "A",
        lastname: "Wilson",
        gender: "Male",
        birthday: "1985-12-10", 
        contact_number: "0923456789",
        address: "789 Pine Rd",
        services: []
    }
];

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
        
        // Populate mobile cards
        const mobileContainer = document.querySelector('#mobileStaffCards');
        mobileContainer.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No staff members found</td></tr>';
            mobileContainer.innerHTML = '<div class="text-center p-4"><p>No staff members found</p></div>';
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
            
            // Create desktop table row
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td>${staff.username}</td>
                <td>${staff.firstname} ${staff.middlename || ''} ${staff.lastname}</td>
                <td>${servicesHtml}</td>
                <td>${staff.gender}</td>
                <td>${staff.birthday}</td>
                <td>${staff.contact_number}</td>
                <td>
                    <button class="btn-icon btn-view view-btn mr-1" title="View" data-id="${staff.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon btn-edit edit-btn mr-1" title="Edit" data-id="${staff.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete delete-btn" title="Delete" data-id="${staff.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            // Create mobile card
            const fullName = `${staff.firstname} ${staff.middlename ? staff.middlename + ' ' : ''}${staff.lastname}`;
            const initials = staff.firstname.charAt(0) + staff.lastname.charAt(0);
            
            const mobileCard = document.createElement('div');
            mobileCard.className = 'staff-card';
            mobileCard.innerHTML = `
                <div class="staff-card-header">
                    <div class="staff-avatar">${initials}</div>
                    <div class="staff-name">${fullName}</div>
                    <div class="staff-actions">
                        <button class="btn-icon btn-view view-btn" title="View" data-id="${staff.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-edit edit-btn" title="Edit" data-id="${staff.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete delete-btn" title="Delete" data-id="${staff.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="staff-card-body">
                    <div class="staff-info-row">
                        <i class="staff-info-icon fas fa-user"></i>
                        <span class="staff-info-label">Username:</span>
                        <span class="staff-info-value">${staff.username}</span>
                    </div>
                    <div class="staff-info-row">
                        <i class="staff-info-icon fas fa-venus-mars"></i>
                        <span class="staff-info-label">Gender:</span>
                        <span class="staff-info-value">${staff.gender}</span>
                    </div>
                    <div class="staff-info-row">
                        <i class="staff-info-icon fas fa-calendar-alt"></i>
                        <span class="staff-info-label">Birthday:</span>
                        <span class="staff-info-value">${staff.birthday}</span>
                    </div>
                    <div class="staff-info-row">
                        <i class="staff-info-icon fas fa-phone"></i>
                        <span class="staff-info-label">Contact:</span>
                        <span class="staff-info-value">${staff.contact_number}</span>
                    </div>
                    <div class="staff-info-row">
                        <i class="staff-info-icon fas fa-cogs"></i>
                        <span class="staff-info-label">Services:</span>
                        <div class="staff-info-value">
                            <div class="services-container">${servicesHtml}</div>
                        </div>
                    </div>
                </div>
            `;
            mobileContainer.appendChild(mobileCard);
        });

        // Add event listeners after content is populated
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
        
        // Format assigned services
        let servicesHtml = 'None';
        if (staff.services && staff.services.length > 0) {
            servicesHtml = staff.services.map(service => 
                `<span class="badge badge-info service-badge">${service.name}</span>`
            ).join('');
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
                <p><strong>Assigned Services:</strong></p>
                <div>${servicesHtml}</div>
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
    
    // Handle service assignment
    const $servicesSelect = $modal.find('#services');
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
}d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Deleted!', 'Staff member has been deleted.', 'success');
            // Remove from sample data and reload
            const staffIndex = sampleStaffData.findIndex(s => s.id == id);
            if (staffIndex > -1) {
                sampleStaffData.splice(staffIndex, 1);
                loadStaff();
            }
        }
    });
}
</script>

</body>
</html>