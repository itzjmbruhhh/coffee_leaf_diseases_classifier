<?php
session_start();

if (!isset($_SESSION['username']) || (int)$_SESSION['account_type'] === 1) {
    header("Location: ../forbidden");
    exit();
}

include '../components/connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin.php"); // or your admin users page
    exit();
}

$user_id = (int)$_GET['id'];

// Prevent deleting self
if ($user_id === (int)$_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account.";
    header("Location: admin.php");
    exit();
}

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: index");
exit();
