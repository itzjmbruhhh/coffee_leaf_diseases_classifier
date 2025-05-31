<?php
session_start();
include "../components/connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT password, account_type FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($hashedPassword, $accountType);
                $stmt->fetch();

                if (password_verify($password, $hashedPassword)) {
                    $_SESSION["username"] = $username;
                    $_SESSION["account_type"] = $accountType;

                    $stmt->close();
                    $conn->close();

                    if ((int)$accountType === 0) {
                        header("Location: ../admin/");
                    } else {
                        header("Location: ../index.php");
                    }
                    exit();
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Username not found.";
            }
            $stmt->close();
        } else {
            $error = "Database error.";
        }
    } else {
        $error = "All fields are required.";
    }

    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="../components/images/icon.png">
  <title>Leaf It Up to Me || Login</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="navbar">
    <div class="nav-title">Leaf It Up to Me</div>
  </div>

  <div class="container">
    <h2 style="text-align:center;">Login</h2>
    <form method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
      </div>

      <button type="submit">Login</button>
    </form>

    <?php if ($error): ?>
      <p style="color: red; text-align: center;"><?= $error ?></p>
    <?php endif; ?>

    <p style="text-align:center; margin-top: 1rem;">
      Don’t have an account?
      <a href="register.php" class="signup-link">Sign Up</a>
    </p>
  </div>
</body>
</html>
