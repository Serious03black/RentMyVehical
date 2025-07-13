<?php
include '../db.php'; // Database connection

// Fetch summary data
$rented_vehicles_query = "SELECT COUNT(*) AS rented_count FROM renting WHERE return_time IS NULL";
$rented_vehicles_result = mysqli_query($conn, $rented_vehicles_query);
$rented_vehicles = mysqli_fetch_assoc($rented_vehicles_result)['rented_count'];

$total_vehicles_query = "SELECT COUNT(*) AS total_count FROM vehicles";
$total_vehicles_result = mysqli_query($conn, $total_vehicles_query);
$total_vehicles = mysqli_fetch_assoc($total_vehicles_result)['total_count'];

$total_users_query = "SELECT COUNT(*) AS user_count FROM users";
$total_users_result = mysqli_query($conn, $total_users_query);
$total_users = mysqli_fetch_assoc($total_users_result)['user_count'];

$today_revenue_query = "SELECT SUM(total_cost) AS today_revenue FROM history WHERE DATE(return_time) = CURDATE()";
$today_revenue_result = mysqli_query($conn, $today_revenue_query);
$today_revenue = mysqli_fetch_assoc($today_revenue_result)['today_revenue'] ?? 0;

// Handle Add Vehicle Form
if (isset($_POST['add_vehicle'])) {
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $default_rent_per_day = floatval($_POST['default_rent_per_day']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image = $upload_dir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
            $message = "Error uploading vehicle image.";
        }
    } else {
        $image = 'Uploads/placeholder.jpg';
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO vehicles (vehicle_number, vehicle_name, default_rent_per_day, status, image) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssdss", $vehicle_number, $vehicle_name, $default_rent_per_day, $status, $image);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Vehicle added successfully!";
    } else {
        $message = "Error adding vehicle: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle Edit Vehicle Form
if (isset($_POST['edit_vehicle'])) {
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $default_rent_per_day = floatval($_POST['default_rent_per_day']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $image = mysqli_real_escape_string($conn, $_POST['current_image']);

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image = $upload_dir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
            $message = "Error uploading vehicle image.";
        }
    }

    $stmt = mysqli_prepare($conn, "UPDATE vehicles SET vehicle_name = ?, default_rent_per_day = ?, status = ?, image = ? WHERE vehicle_number = ?");
    mysqli_stmt_bind_param($stmt, "sdsss", $vehicle_name, $default_rent_per_day, $status, $image, $vehicle_number);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Vehicle updated successfully!";
    } else {
        $message = "Error updating vehicle: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle Mark Maintenance
if (isset($_GET['mark_maintenance']) && isset($_GET['vehicle_number'])) {
    $vehicle_number = mysqli_real_escape_string($conn, $_GET['vehicle_number']);
    $result = mysqli_query($conn, "SELECT status FROM vehicles WHERE vehicle_number = '$vehicle_number'");
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] !== 'Rented') {
            $stmt = mysqli_prepare($conn, "UPDATE vehicles SET status = 'Maintenance' WHERE vehicle_number = ?");
            mysqli_stmt_bind_param($stmt, "s", $vehicle_number);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Vehicle marked for maintenance!";
            } else {
                $message = "Error marking maintenance: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Error: Cannot mark rented vehicle for maintenance!";
        }
    }
}

// Stop booking and move to history
if (isset($_GET['return']) && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    $result = mysqli_query($conn, "SELECT * FROM renting WHERE booking_id = $booking_id");
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    $booking = mysqli_fetch_assoc($result);
    if ($booking) {
        $renting_time = strtotime($booking['renting_time']);
        $return_time = time();
        $duration_seconds = $return_time - $renting_time;
        $total_days = ceil($duration_seconds / 86400);
        $total_cost = ($booking['rent_per_day'] / 86400) * $duration_seconds;
        $total_cost = round($total_cost, 2);

        $stmt = mysqli_prepare($conn, "INSERT INTO history (user_id, vehicle_number, renting_time, return_time, total_days, rent_per_day, total_cost) VALUES (?, ?, ?, NOW(), ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issidd", $booking['user_id'], $booking['vehicle_number'], $booking['renting_time'], $total_days, $booking['rent_per_day'], $total_cost);
        if (!mysqli_stmt_execute($stmt)) {
            die("Insert into history failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE renting SET return_time = NOW() WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $booking_id);
        if (!mysqli_stmt_execute($stmt)) {
            die("Update renting failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "DELETE FROM renting WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $booking_id);
        if (!mysqli_stmt_execute($stmt)) {
            die("Delete from renting failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE vehicles SET status = 'Free' WHERE vehicle_number = ?");
        mysqli_stmt_bind_param($stmt, "s", $booking['vehicle_number']);
        if (!mysqli_stmt_execute($stmt)) {
            die("Update vehicle status failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        header("Location: admin.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Vehicle Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn-primary {
            background: linear-gradient(to right, #4f46e5, #7c3aed);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-danger {
            background: linear-gradient(to right, #ef4444, #b91c1c);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-secondary {
            background: linear-gradient(to right, #6b7280, #4b5563);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-row:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: scale(1.01);
            transition: transform 0.2s ease-in-out;
        }
        .card {
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .circle-card {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(to bottom, #1f2937, #111827);
        }
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-hidden {
            transform: translateX(-100%);
        }
    </style>
    <script>
        function updateCosts() {
            const rows = document.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const time = new Date(row.dataset.start);
                const now = new Date();
                const seconds = Math.floor((now - time) / 1000);
                const rate = parseFloat(row.dataset.rate);
                const cost = (rate / 86400) * seconds;
                row.querySelector(".cost").innerText = `₹${cost.toFixed(2)}`;
            });
        }
        setInterval(updateCosts, 1000);

        function filterVehicles() {
            const search = document.getElementById('vehicle-search').value.toLowerCase();
            const cards = document.querySelectorAll('.vehicle-card');
            cards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const number = card.dataset.number.toLowerCase();
                if (name.includes(search) || number.includes(search)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-hidden');
        }
    </script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 h-full w-64 bg-gray-800 p-4 shadow-lg z-50 md:transform-none sidebar-hidden md:sidebar">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-indigo-400">Admin Panel</h3>
            <button class="md:hidden text-gray-300 hover:text-white" onclick="toggleSidebar()">✕</button>
        </div>
        <ul class="space-y-2">
            <li>
                <a href="#add-vehicle-form" class="block p-2 rounded-md bg-indigo-600 text-white font-semibold">Add Vehicle</a>
            </li>
            <li>
                <a href="../user.php" class="block p-2 rounded-md text-gray-300 hover:bg-indigo-500 hover:text-white">User</a>
            </li>
            <li>
                <a href="history.php" class="block p-2 rounded-md text-gray-300 hover:bg-indigo-500 hover:text-white">History</a>
            </li>
            <li>
                <a href="revenue.php" class="block p-2 rounded-md text-gray-300 hover:bg-indigo-500 hover:text-white">Revenue</a>
            </li>
            <li>
                <a href="all_users.php" class="block p-2 rounded-md text-gray-300 hover:bg-indigo-500 hover:text-white">All Users</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-0 md:ml-64">
        <button class="md:hidden btn-primary text-white font-semibold py-2 px-4 rounded-md mb-4" onclick="toggleSidebar()">☰ Menu</button>
        <h2 class="text-3xl font-bold text-center mb-8 text-white">Admin Dashboard - Vehicle Management</h2>

        <!-- Summary Cards -->
        <div class="flex justify-center gap-8 mb-8">
            <div class="circle-card flex-col">
                <h4 class="text-sm font-medium text-gray-300">Rented Vehicles</h4>
                <p class="text-xl font-bold text-indigo-400"><?php echo $rented_vehicles; ?> / <?php echo $total_vehicles; ?></p>
            </div>
            <div class="circle-card flex-col">
                <h4 class="text-sm font-medium text-gray-300">Today's Revenue</h4>
                <p class="text-xl font-bold text-indigo-400">₹<?php echo number_format($today_revenue, 2); ?></p>
            </div>
            <div class="circle-card flex-col">
                <h4 class="text-sm font-medium text-gray-300">Total Users</h4>
                <p class="text-xl font-bold text-indigo-400"><?php echo $total_users; ?></p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-8">
            <input id="vehicle-search" type="text" placeholder="Search by vehicle name or number..." class="w-full p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500" oninput="filterVehicles()">
        </div>

        <?php if (isset($message)) { ?>
            <div class="mb-6 p-4 rounded-lg text-center <?php echo strpos($message, 'Error') === false ? 'bg-green-600' : 'bg-red-600'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <!-- Vehicle Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php
            $vehicle_query = "SELECT vehicle_number, vehicle_name, status, default_rent_per_day, image FROM vehicles";
            $vehicle_result = mysqli_query($conn, $vehicle_query);
            while ($vehicle = mysqli_fetch_assoc($vehicle_result)) {
                $image = $vehicle['image'] ?: 'Uploads/placeholder.jpg';
                ?>
                <div class="vehicle-card card bg-gray-800 p-4 rounded-lg shadow-lg" data-name="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" data-number="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" class="w-full h-40 object-cover rounded-md mb-4">
                    <h4 class="text-lg font-semibold text-indigo-400"><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></h4>
                    <p class="text-gray-300"><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></p>
                    <p class="text-gray-300">Status: <span class="<?php echo $vehicle['status'] === 'Rented' ? 'text-red-400' : ($vehicle['status'] === 'Maintenance' ? 'text-yellow-400' : 'text-green-400'); ?>"><?php echo htmlspecialchars($vehicle['status']); ?></span></p>
                    <div class="flex gap-2 mt-4">
                        <button onclick="document.getElementById('edit-vehicle-<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>').classList.remove('hidden')" class="btn-secondary text-white font-semibold py-1 px-3 rounded-md">Edit</button>
                        <?php if ($vehicle['status'] !== 'Rented') { ?>
                            <a href="?mark_maintenance=1&vehicle_number=<?php echo urlencode($vehicle['vehicle_number']); ?>" onclick="return confirm('Mark vehicle for maintenance?')" class="btn-danger text-white font-semibold py-1 px-3 rounded-md">Maintenance</a>
                        <?php } ?>
                        <?php if ($vehicle['status'] === 'Free') { ?>
                            <a href="add_user_booking.php#booking-form" class="btn-primary text-white font-semibold py-1 px-3 rounded-md">Rent</a>
                        <?php } ?>
                    </div>
                    <!-- Edit Vehicle Form (Hidden) -->
                    <div id="edit-vehicle-<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>" class="hidden mt-4">
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="vehicle_number" value="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>">
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($image); ?>">
                            <div>
                                <label class="block text-sm font-medium text-gray-300">Vehicle Name</label>
                                <input type="text" name="vehicle_name" value="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300">Rent Per Day (₹)</label>
                                <input type="number" step="0.01" name="default_rent_per_day" value="<?php echo htmlspecialchars($vehicle['default_rent_per_day']); ?>" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300">Status</label>
                                <select name="status" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                                    <option value="Free" <?php echo $vehicle['status'] === 'Free' ? 'selected' : ''; ?>>Free</option>
                                    <option value="Rented" <?php echo $vehicle['status'] === 'Rented' ? 'selected' : ''; ?>>Rented</option>
                                    <option value="Maintenance" <?php echo $vehicle['status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300">Vehicle Image</label>
                                <input type="file" name="image" accept="image/*" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                            </div>
                            <button type="submit" name="edit_vehicle" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Update Vehicle</button>
                        </form>
                    </div>
                </div>
                <?php
            }
            mysqli_free_result($vehicle_result);
            ?>
        </div>

        <!-- Add Vehicle Form -->
        <div id="add-vehicle-form" class="card bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
            <h3 class="text-2xl font-semibold mb-4 text-indigo-400">Add New Vehicle</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300">Vehicle Number</label>
                    <input type="text" name="vehicle_number" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Vehicle Name</label>
                    <input type="text" name="vehicle_name" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Rent Per Day (₹)</label>
                    <input type="number" step="0.01" name="default_rent_per_day" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Status</label>
                    <select name="status" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Free">Free</option>
                        <option value="Rented">Rented</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Vehicle Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <button type="submit" name="add_vehicle" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Add Vehicle</button>
            </form>
        </div>

        <!-- Active Bookings Table -->
        <div class="card bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-4 text-indigo-400">Active Bookings (With Timer)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="p-4 text-sm font-medium text-gray-300">User</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Vehicle</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Renting Time</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Cost (Live)</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $query = "SELECT r.*, u.name AS user_name, v.vehicle_name, r.rent_per_day 
                              FROM renting r 
                              JOIN users u ON r.user_id = u.user_id 
                              JOIN vehicles v ON r.vehicle_number = v.vehicle_number 
                              WHERE r.return_time IS NULL";
                    $result = mysqli_query($conn, $query);
                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    while ($row = mysqli_fetch_assoc($result)) {
                        $renting_time = date('c', strtotime($row['renting_time']));
                        echo "<tr class='table-row border-b border-gray-700' data-start='{$renting_time}' data-rate='{$row['rent_per_day']}'>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['user_name']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['vehicle_name']) . " (" . htmlspecialchars($row['vehicle_number']) . ")</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['renting_time']) . "</td>";
                        echo "<td class='p-4 text-gray-100 cost'>₹0.00</td>";
                        echo "<td class='p-4'><a class='btn-danger inline-block text-white font-semibold py-2 px-4 rounded-md' href='?return=1&booking_id={$row['booking_id']}' onclick='return confirm(\"Confirm vehicle return?\")'>Return</a></td>";
                        echo "</tr>";
                    }
                    mysqli_free_result($result);
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>