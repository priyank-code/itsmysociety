<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download User List</title>
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
            min-height: 100vh;
            background: #f8f9fc;
            padding: 20px;
        }

        #content.active {
            width: 100%;
            margin-left: 0;
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
                <li>
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
                <li class="active">
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

            <div class="container-fluid">
                <h2 class="mb-4">User List</h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Family Member</th>
                                <th>Flat/House No</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <?php
                            include '../connection.php';
                            
                            // Check if the table exists
                            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'user'");
                            if(mysqli_num_rows($check_table) == 0) {
                                echo "<tr><td colspan='6'>Table does not exist</td></tr>";
                            } else {
                                // Get table structure
                                $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM user");
                                $columns = array();
                                while($col = mysqli_fetch_assoc($check_columns)) {
                                    $columns[] = $col['Field'];
                                }
                                
                                // Build query based on existing columns
                                $select_columns = array('id', 'name', 'email', 'phone', 'familymembers', 'flatno');
                                $valid_columns = array();
                                foreach($select_columns as $col) {
                                    if(in_array($col, $columns)) {
                                        $valid_columns[] = $col;
                                    }
                                }
                                
                                if(in_array('deleted', $columns)) {
                                    $query = "SELECT " . implode(',', $valid_columns) . " FROM user WHERE deleted = 0 AND status = 1 ORDER BY id ASC";
                                } else {
                                    $query = "SELECT " . implode(',', $valid_columns) . " FROM user WHERE status = 1 ORDER BY id ASC";
                                }
                                
                                $result = mysqli_query($conn, $query);
                                
                                if($result === false) {
                                    echo "<tr><td colspan='6'>Error executing query: " . mysqli_error($conn) . "</td></tr>";
                                } else {
                                    $users = array();
                                    $counter = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $users[] = $row;
                                        
                                        $counter++;
                                        echo "<tr>";
                                        echo "<td>{$counter}</td>";
                                        if(isset($row['name'])) echo "<td>{$row['name']}</td>";
                                        if(isset($row['email'])) echo "<td>{$row['email']}</td>";
                                        if(isset($row['phone'])) echo "<td>{$row['phone']}</td>";
                                        if(isset($row['familymembers'])) echo "<td>{$row['familymembers']}</td>";
                                        if(isset($row['flatno'])) echo "<td>{$row['flatno']}</td>";
                                        echo "</tr>";
                                    }
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php if(isset($users) && !empty($users)): ?>
                <button id="downloadBtn" class="btn btn-primary mt-3">
                    <i class="fas fa-download me-2"></i>Download as Excel
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SheetJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get PHP data
            const users = <?php echo isset($users) ? json_encode($users) : '[]'; ?>;

            // Download as Excel
            document.getElementById('downloadBtn')?.addEventListener('click', function() {
                if(users.length === 0) {
                    alert('No data available to download');
                    return;
                }

                // Create worksheet with custom headers
                const headers = [
                    ['#', 'Name', 'Email', 'Phone', 'Family Member', 'Flat/House No']
                ];
                const data = users.map((user, index) => [
                    index + 1,
                    user.name || '',
                    user.email || '',
                    user.phone || '',
                    user.familymembers || '',
                    user.flatno || ''
                ]);

                const ws = XLSX.utils.aoa_to_sheet([...headers, ...data]);

                // Set column widths
                const colWidths = [10, 20, 30, 15, 15, 15];
                ws['!cols'] = colWidths.map(w => ({ wch: w }));

                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Users');
                
                // Get current date for filename
                const date = new Date().toISOString().split('T')[0];
                XLSX.writeFile(wb, `user_list_${date}.xlsx`);
            });

            // Sidebar toggle
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');

            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    content.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>