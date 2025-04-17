<?php
session_start();
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

include '../connection.php';

// Pagination settings
$records_per_page = 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "";
if($search != '') {
    $where = "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' OR flatno LIKE '%$search%'";
}

// Count total records for pagination
$total_records_sql = "SELECT COUNT(*) as count FROM user $where";
$total_records_result = mysqli_query($conn, $total_records_sql);
$total_records = mysqli_fetch_assoc($total_records_result)['count'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch users from database in ascending order by ID
$sql = "SELECT * FROM user $where ORDER BY id ASC LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Panel</title>
    <link rel="icon" href="../img/favicon.png" type="png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Common Styles */
        :root {
            --sidebar-width: 250px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
        }

        body {
            overflow-x: hidden;
            background-color: #f8f9fc;
        }

        /* Sidebar Styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            transition: all 0.3s;
            position: fixed;
            z-index: 1000;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul li a {
            padding: 10px 20px;
            font-size: 1.1em;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        #sidebar ul li a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            padding-left: 25px;
        }

        #sidebar ul li.active > a {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }

        /* Content Styles */
        #content {
            width: calc(100% - 250px);
            margin-left: 250px;
            transition: all 0.3s;
            padding: 20px;
        }

        #content.active {
            width: 100%;
            margin-left: 0;
        }

        /* User-specific styles */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .filter-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
                margin-left: 0;
            }
            #content.active {
                margin-left: 250px;
            }
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999;
            opacity: 0;
            transition: all 0.5s ease-in-out;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }

        .table th {
      white-space: nowrap;
      background-color: #f8f9fc;
  }
  
  .table td {
      vertical-align: middle;
  }

  .action-buttons {
      white-space: nowrap;
  }

  .action-buttons .btn {
      margin: 0 2px;
  }

  .badge {
      padding: 8px 12px;
  }

  .edit-mode input {
      width: 100%;
      padding: 5px;
      border: 1px solid #ddd;
      border-radius: 4px;
  }
  .edit-mode td {
      padding: 5px !important;
  }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="admin.php">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="active">
                    <a href="users.php">
                        <i class="fas fa-users me-2"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user me-2"></i>
                        Profile
                    </a>
                </li>
                <li>
                    <a href="complaints.php">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Complaints
                    </a>
                </li>
                <li>
                    <a href="notice.php">
                        <i class="fas fa-bullhorn me-2"></i>
                        Notices
                    </a>
                </li>
                <li>
                    <a href="maintenance.php">
                        <i class="fas fa-tools me-2"></i>
                        Maintenance
                    </a>
                </li>
                <li>
                    <a href="contact.php">
                        <i class="fas fa-envelope me-2"></i>
                        Contact Messages
                    </a>
                </li>
                <li>
                    <a href="download-users.php">
                        <i class="fas fa-download me-2"></i>
                        Download User List
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Users Content -->
            <div class="container-fluid">
                <!-- Filter Section -->
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">Users List</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <form class="d-flex me-2" style="width: 50%;" method="GET" action="users.php">
                                    <input type="text" class="form-control" placeholder="Search by name, email, phone or flat no..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary ms-2" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if(!empty($search)): ?>
                                        <a href="users.php" class="btn btn-secondary ms-2">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </form>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-plus"></i> Add New User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Flat No</th>
                                        <th>Family Members</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if(mysqli_num_rows($result) > 0) {
                                        $counter = ($page - 1) * $records_per_page + 1;
                                        while($row = mysqli_fetch_assoc($result)) {
                                            $status_badge = $row['status'] == 1 ? 
                                                '<span class="badge bg-success">Approved</span>' : 
                                                '<span class="badge bg-warning">Pending</span>';
                                            
                                            echo "<tr data-id='{$row['id']}'>
                                                <td>{$counter}</td>
                                                <td>{$row['name']}</td>
                                                <td>{$row['email']}</td>
                                                <td>{$row['phone']}</td>
                                                <td>{$row['flatno']}</td>
                                                <td>{$row['familymembers']}</td>
                                                <td><span class='status-badge'>{$status_badge}</span></td>
                                                <td class='action-buttons'>
                                                    <button class='btn btn-primary btn-sm' onclick='editUser({$row['id']}, \"{$row['name']}\", \"{$row['email']}\", \"{$row['phone']}\", \"{$row['flatno']}\", \"{$row['familymembers']}\")'>
                                                        <i class='fas fa-edit'></i>
                                                    </button>
                                                    <button class='btn btn-danger btn-sm' onclick='deleteUser({$row['id']})'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                    <button class='btn btn-success btn-sm lock-btn' onclick='toggleStatus({$row['id']}, {$row['status']})'>
                                                        <i class='fas " . ($row['status'] == 1 ? 'fa-lock' : 'fa-unlock') . "'></i>
                                                    </button>
                                                </td>
                                            </tr>";
                                            $counter++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No users found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="card-footer">
                            <nav>
                                <?php if($total_records > 0): ?>
                                    <div class="text-center mb-2">
                                        Showing <?php echo min(($page-1) * $records_per_page + 1, $total_records); ?> 
                                        to <?php echo min($page * $records_per_page, $total_records); ?> 
                                        of <?php echo $total_records; ?> entries
                                        <?php if(!empty($search)): ?>
                                            (filtered from total records)
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if($total_pages > 1): ?>
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Previous</a>
                                        </li>
                                        
                                        <?php
                                        $start_page = max(1, min($page - 2, $total_pages - 4));
                                        $end_page = min($total_pages, max(5, $page + 2));
                                        
                                        if($start_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">1</a>
                                            </li>
                                            <?php if($start_page > 2): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif;
                                        endif;

                                        for($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor;

                                        if($end_page < $total_pages): ?>
                                            <?php if($end_page < $total_pages - 1): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"><?php echo $total_pages; ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" onsubmit="return validateForm()">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" pattern="[0-9]{10}" title="Please enter 10 digits" required>
                        </div>
                        <div class="mb-3">
                            <label for="flatno" class="form-label">Flat No</label>
                            <input type="text" class="form-control" name="flatno" required>
                        </div>
                        <div class="mb-3">
                            <label for="familymembers" class="form-label">Family Members</label>
                            <input type="number" class="form-control" name="familymembers" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" title="Password must contain at least 8 characters, including uppercase, lowercase, numbers and special characters" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">Add User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" id="editPhone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Flat No</label>
                            <input type="text" class="form-control" id="editFlatno" name="flatno" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Family Members</label>
                            <input type="text" class="form-control" id="editFamilyMembers" name="familymembers" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">Update User</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar, #content').toggleClass('active');
                $('.overlay').toggleClass('active');
            });
        });

        function editUser(id, name, email, phone, flatno, familymembers) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editPhone').value = phone;
            document.getElementById('editFlatno').value = flatno;
            document.getElementById('editFamilyMembers').value = familymembers;
            $('#editUserModal').modal('show');
        }

        function updateUser() {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            
            $.ajax({
                url: 'handle_user.php',
                type: 'POST',
                data: {
                    action: 'update',
                    id: formData.get('id'),
                    name: formData.get('name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    flatno: formData.get('flatno'),
                    familymembers: formData.get('familymembers')
                },
                success: function(response) {
                    const result = JSON.parse(response);
                    if(result.status === 'success') {
                        $('#editUserModal').modal('hide');
                        alert('User updated successfully!');
                        window.location.reload();
                    } else {
                        alert('Error updating user');
                    }
                },
                error: function() {
                    alert('Error updating user');
                }
            });
        }

        function deleteUser(id) {
            if(confirm('Are you sure you want to delete this user?')) {
                $.ajax({
                    url: 'handle_user.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: id
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if(result.status === 'success') {
                                alert('User deleted successfully!');
                                location.reload();
                            } else {
                                alert('Error deleting user: ' + result.message);
                            }
                        } catch(e) {
                            alert('Error occurred while deleting user');
                        }
                    },
                    error: function() {
                        alert('Error connecting to server');
                    }
                });
            }
        }

        function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 1 ? 0 : 1;
            const message = newStatus === 1 ? 'Approve this user?' : 'Reject this user?';
            
            if(confirm(message)) {
                $.ajax({
                    url: 'handle_user.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_status',
                        id: id,
                        status: newStatus
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if(result.status === 'success') {
                                const row = document.querySelector(`tr[data-id="${id}"]`);
                                const statusCell = row.querySelector('.status-badge');
                                const lockBtn = row.querySelector('.lock-btn');
                                
                                if(newStatus === 1) {
                                    statusCell.innerHTML = '<span class="badge bg-success">Approved</span>';
                                    lockBtn.innerHTML = '<i class="fas fa-lock"></i>';
                                    alert('User approved and email notification sent!');
                                } else {
                                    statusCell.innerHTML = '<span class="badge bg-warning">Pending</span>';
                                    lockBtn.innerHTML = '<i class="fas fa-unlock"></i>';
                                    alert('User status changed to pending');
                                }
                                lockBtn.setAttribute('onclick', `toggleStatus(${id}, ${newStatus})`);
                                
                                // If there's an error with email but status updated
                                if(result.message) {
                                    console.log('Email Error:', result.message);
                                }
                            } else {
                                alert('Error updating status');
                            }
                        } catch(e) {
                            console.error('Error parsing response:', e);
                            alert('Error processing response');
                        }
                    },
                    error: function() {
                        alert('Error updating status');
                    }
                });
            }
        }

        function validateForm() {
            const form = document.getElementById('addUserForm');
            const name = form.elements['name'].value;
            const email = form.elements['email'].value;
            const phone = form.elements['phone'].value;
            const password = form.elements['password'].value;

            // Name validation
            if(!/^[A-Za-z\s]+$/.test(name)) {
                alert('Name should only contain letters and spaces');
                return false;
            }

            // Phone validation
            if(!/^[0-9]{10}$/.test(phone)) {
                alert('Phone number should be 10 digits');
                return false;
            }

            // Password validation
            if(!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password)) {
                alert('Password must contain at least 8 characters, including uppercase, lowercase, numbers and special characters');
                return false;
            }

            return true;
        }

        function addUser() {
            if(!validateForm()) {
                return;
            }

            const form = document.getElementById('addUserForm');
            const formData = new FormData(form);
            
            $.ajax({
                url: 'handle_user.php',
                type: 'POST',
                data: {
                    action: 'add',
                    name: formData.get('name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    flatno: formData.get('flatno'),
                    familymembers: formData.get('familymembers'),
                    password: formData.get('password')
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if(result.status === 'success') {
                            $('#addUserModal').modal('hide');
                            form.reset();
                            alert('User added successfully!');
                            window.location.reload();
                        } else {
                            alert('Error adding user: ' + (result.message || 'Unknown error'));
                        }
                    } catch(e) {
                        console.error('Error parsing response:', e);
                        alert('Error processing response');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Error adding user');
                }
            });
        }
    </script>
</body>
</html>