<?php
session_start();
include('../connection.php');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['confirm_payment']) && isset($_POST['request_id']) && isset($_POST['reference_code'])) {
    $request_id = $_POST['request_id'];
    $reference_code = mysqli_real_escape_string($conn, $_POST['reference_code']);
    $user_id = $_SESSION['user_id'];

    // Verify that this request belongs to the current user
    $verify_query = "SELECT id FROM maintenance WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "ii", $request_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        // Update the maintenance request
        $update_query = "UPDATE maintenance SET 
                        user_confirmation = 'success',
                        reference_code = ?,
                        payment_date = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sii", $reference_code, $request_id, $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Payment confirmed successfully!";
        } else {
            $_SESSION['error_message'] = "Error confirming payment: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "Invalid request!";
    }
} else {
    $_SESSION['error_message'] = "Invalid form submission!";
}

// Redirect back to maintenance page
header("Location: user_maintenance.php");
exit();
?>
