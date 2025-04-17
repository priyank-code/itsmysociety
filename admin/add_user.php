<?php
session_start();
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

include '../connection.php';

if(isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $flatno = mysqli_real_escape_string($conn, $_POST['flatno']);
    $familymembers = mysqli_real_escape_string($conn, $_POST['familymembers']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if user already exists
    $check = mysqli_query($conn, "SELECT id FROM user WHERE email = '$email' OR phone = '$phone'");
    if(mysqli_num_rows($check) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'User with this email or phone already exists']);
        exit;
    }
    
    $sql = "INSERT INTO user (name, email, phone, flatno, familymembers, password, status) 
            VALUES (?, ?, ?, ?, ?, ?, 0)";
            
    $stmt = mysqli_prepare($conn, $sql);
    if($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $phone, $flatno, $familymembers, $password);
        
        if(mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($conn);
            echo json_encode([
                'status' => 'success',
                'id' => $id,
                'data' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'flatno' => $flatno,
                    'familymembers' => $familymembers,
                    'status' => 0
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error executing statement: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error preparing statement: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
