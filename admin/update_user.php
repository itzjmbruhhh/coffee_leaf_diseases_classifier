<?php
include '../components/connection.php';

$user_id = $_POST['user_id'];
$username = $_POST['username'];
$email = $_POST['email'];
$account_type = $_POST['account_type'];
$password = $_POST['password'] ?? '';

// Basic validation here...

if ($password !== '') {
    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, account_type=?, password=? WHERE user_id=?");
    $stmt->bind_param("ssssi", $username, $email, $account_type, $hashed_password, $user_id);
} else {
    // Password not changed, do not update password column
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, account_type=? WHERE user_id=?");
    $stmt->bind_param("sssi", $username, $email, $account_type, $user_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>
