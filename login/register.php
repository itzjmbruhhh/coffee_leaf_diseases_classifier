<?php
include "../components/connection.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? '';
    $email = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';
    $confirmPassword = $_POST["confirm_password"] ?? '';

    if ($username && $email && $password && $confirmPassword) {
        if ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Username already exists.";
            } else {
                $stmt->close();
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, account_type) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("sss", $username, $email, $hashedPassword);

                if ($stmt->execute()) {
                    $success = "Registered successfully. Redirecting to login...";
                    echo "<meta http-equiv='refresh' content='2;url=login.php'>";
                } else {
                    $error = "Registration failed.";
                }
            }

            $stmt->close();
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
  <title>Leaf It Up to Me || Register</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="navbar">
    <div class="nav-title">Leaf It Up to Me</div>
  </div>

  <div class="container">
    <h2 style="text-align:center;">Register</h2>
    <form method="POST">
      <div class="form-group">
        <label for="newUsername">Username</label>
        <input type="text" id="newUsername" name="username" required />
      </div>

      <div class="form-group">
        <label for="newEmail">Email</label>
        <input type="email" id="newEmail" name="email" required />
      </div>

      <div class="form-group">
        <label for="newPassword">Password</label>
        <input type="password" id="newPassword" name="password" required />
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirm Password</label>
        <input type="password" id="confirmPassword" name="confirm_password" required />
      </div>

      <button type="submit">Register</button>
    </form>

    <?php if ($error): ?>
      <p style="color: red; text-align: center;"><?= $error ?></p>
    <?php elseif ($success): ?>
      <p style="color: green; text-align: center;"><?= $success ?></p>
    <?php endif; ?>

    <p style="text-align:center; margin-top: 1rem;">
      Already have an account?
      <a href="login.php" class="signup-link">Login</a>
    </p>
  </div>
</body>
</html>
