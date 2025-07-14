<?php
session_start();

$admin_id = "admin";
$admin_password = "admin123";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_id = $_POST['admin_id'];
    $entered_pass = $_POST['password'];

    if ($entered_id === $admin_id && $entered_pass === $admin_password) {
        $_SESSION['admin'] = $admin_id;
        header("Location:admin.php");
        exit();
    } else {
        $error = "Invalid Admin ID or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Vehicle Rental</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #1f4037, #99f2c8);
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .login-box {
      background-color: rgba(0,0,0,0.4);
      padding: 30px;
      border-radius: 10px;
      text-align: center;
    }
    input[type="text"],
    input[type="password"] {
      padding: 10px;
      width: 90%;
      margin: 10px 0;
      border: none;
      border-radius: 5px;
    }
    input[type="submit"] {
      background-color: #fff;
      color: #1f4037;
      font-weight: bold;
      padding: 10px 25px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .error {
      color: red;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Admin Login</h2>
    <form method="POST">
      <input type="text" name="admin_id" placeholder="Enter Admin ID" required><br>
      <input type="password" name="password" placeholder="Enter Password" required><br>
      <input type="submit" value="Login">
    </form>
    <?php if ($error): ?>
      <p class="error"><?= $error ?></p>
    <?php endif; ?>
  </div>
</body>
</html>
