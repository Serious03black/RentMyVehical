<?php
session_start();
$conn = new mysqli("localhost", "root", "", "vehicalmanagment");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST['id_number'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id_number = ?");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_name'] = $row['name'];
        header("Location: user_dashboard.php");
        exit();
    } else {
        $error = "Invalid ID Number!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Login - Vehicle Rental</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #43cea2, #185a9d);
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
    input[type="text"] {
      padding: 10px;
      width: 90%;
      margin: 10px 0;
      border: none;
      border-radius: 5px;
    }
    input[type="submit"] {
      background-color: #fff;
      color: #185a9d;
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
    <h2>User Login</h2>
    <form method="POST">
      <input type="text" name="id_number" placeholder="Enter ID Number" required><br>
      <input type="submit" value="Login">
    </form>
    <?php if ($error): ?>
      <p class="error"><?= $error ?></p>
    <?php endif; ?>
  </div>
</body>
</html>
