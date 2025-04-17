<?php
session_start();
include('../connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle reference code submission
if(isset($_POST['submit_reference'])) {
    $request_id = $_POST['request_id'];
    $reference_code = $_POST['reference_code'];
    
    $update_query = "UPDATE maintenance_requests 
                    SET reference_code = ?, user_confirmation = 'success' 
                    WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sii", $reference_code, $request_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $success_message = "Reference code submitted successfully!";
    } else {
        $error_message = "Error submitting reference code: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Get user's payment requests
$request_query = "SELECT * FROM maintenance_requests WHERE user_id = ? ORDER BY request_date DESC";
$stmt = mysqli_prepare($conn, $request_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$request_result = mysqli_stmt_get_result($stmt);

// Set header for PHP
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Panel - User</title>
    <link rel="icon" href="../img/favicon.png" type="png" />
    <link rel="stylesheet" href="../style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Maintenance Payment Requests</h2>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Your Payment Requests</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        <?php if(isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if(mysqli_num_rows($request_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request Date</th>
                                            <th>Amount (₹)</th>
                                            <th>Reference Code</th>
                                            <th>Admin Status</th>
                                            <th>Secretary Status</th>
                                            <th>User Confirmation</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($request = mysqli_fetch_assoc($request_result)): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i', strtotime($request['request_date'])); ?></td>
                                                <td>₹<?php echo number_format($request['amount'], 2); ?></td>
                                                <td>
                                                    <?php if($request['reference_code']): ?>
                                                        <?php echo htmlspecialchars($request['reference_code']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not submitted</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $request['admin_status'] == 'success' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo ucfirst($request['admin_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php 
                                                        if($request['secretary_status'] == 'approved') echo 'bg-success';
                                                        elseif($request['secretary_status'] == 'rejected') echo 'bg-danger';
                                                        else echo 'bg-warning';
                                                    ?>">
                                                        <?php echo ucfirst($request['secretary_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $request['user_confirmation'] == 'success' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo ucfirst($request['user_confirmation']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if(!$request['reference_code']): ?>
                                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#referenceModal<?php echo $request['id']; ?>">
                                                            Submit Reference
                                                        </button>
                                                        
                                                        <!-- Reference Code Modal -->
                                                        <div class="modal fade" id="referenceModal<?php echo $request['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Submit Reference Code</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST" action="">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label for="reference_code" class="form-label">Reference Code</label>
                                                                                <input type="text" class="form-control" id="reference_code" name="reference_code" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                            <button type="submit" name="submit_reference" class="btn btn-primary">Submit</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No payment requests found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
