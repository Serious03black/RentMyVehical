<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vehicle Rental Management</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #3a7bd5, #00d2ff);
      color: #fff;
      text-align: center;
      padding-top: 100px;
    }
    .container {
      background-color: rgba(0, 0, 0, 0.3);
      padding: 30px;
      border-radius: 10px;
      display: inline-block;
    }
    h1 {
      margin-bottom: 40px;
    }
    a {
      display: inline-block;
      padding: 12px 24px;
      margin: 10px;
      background-color: #fff;
      color: #3a7bd5;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
    a:hover {
      background-color: #3a7bd5;
      color: #fff;
      transition: 0.3s;
    }
  </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex">
    <!-- Sidebar -->
  <div class="container">
    <h1>Welcome to Vehicle Rental Management System</h1>
    <a href="admin_login.php">Admin Login</a>
    <a href="user_login.php">User Login</a>
  </div>
</body>
</html>
