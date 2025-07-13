<?php
include '../db.php'; // Database connection

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

        $message = "Vehicle returned successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Vehicles - Vehicle Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
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
                const userId = row.dataset.userId;
                const vehicleRows = row.querySelectorAll(".vehicle-row");
                let totalCost = 0;
                vehicleRows.forEach(vRow => {
                    const time = new Date(vRow.dataset.start);
                    const rate = parseFloat(vRow.dataset.rate);
                    const now = new Date();
                    const seconds = Math.floor((now - time) / 1000);
                    const cost = (rate / 86400) * seconds;
                    totalCost += cost;
                    vRow.querySelector(".vehicle-cost").innerText = `₹${cost.toFixed(2)}`;
                });
                row.querySelector(".total-cost").innerText = `₹${totalCost.toFixed(2)}`;
            });
        }
        setInterval(updateCosts, 1000);

        function filterUsers() {
            const search = document.getElementById('user-search').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const name = row.dataset.name.toLowerCase();
                const mobile = row.dataset.mobile.toLowerCase();
                row.style.display = (name.includes(search) || mobile.includes(search)) ? '' : 'none';
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
                <a href="admin.php#add-vehicle-form" class="block p-2 rounded-md text-gray-300 hover:bg-indigo-500 hover:text-white">Add Vehicle</a>
            </li>
            <li>
                <a href="add_user_booking.php" class="block p-2 rounded-md text-gray-300 hover:bg-indigo-500 hover:text-white">Add User</a>
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
            <li>
                <a href="user_vehicles.php" class="block p-2 rounded-md bg-indigo-600 text-white font-semibold">User Vehicles</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-0 md:ml-64">
        <button class="md:hidden btn-primary text-white font-semibold py-2 px-4 rounded-md mb-4" onclick="toggleSidebar()">☰ Menu</button>
        <h2 class="text-3xl font-bold text-center mb-8 text-white">User Vehicles</h2>

        <!-- Search Bar -->
        <div class="mb-8">
            <input id="user-search" type="text" placeholder="Search by user name or mobile number..." class="w-full p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500" oninput="filterUsers()">
        </div>

        <?php if (isset($message)) { ?>
            <div class="mb-6 p-4 rounded-lg text-center <?php echo strpos($message, 'Error') === false ? 'bg-green-600' : 'bg-red-600'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <!-- Users Table -->
        <div class="card bg-gray-800 p-6 rounded-lg shadow-lg">
            <h3 class="text-2xl font-semibold mb-4 text-indigo-400">Users and Allotted Vehicles</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="p-4 text-sm font-medium text-gray-300">Name</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Mobile Number</th>
                            <th class="p-4 text-sm font-medium text-gray-300">ID Number</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Address</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Aadhaar Image</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Allotted Vehicles</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Total Cost (Live)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $query = "SELECT u.user_id, u.name, u.mobile_number, u.id_number, u.address, u.aadhaar_image 
                              FROM users u 
                              ORDER BY u.name";
                    $result = mysqli_query($conn, $query);
                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    while ($user = mysqli_fetch_assoc($result)) {
                        $aadhaar_image = $user['aadhaar_image'] ?: 'Uploads/placeholder.jpg';
                        echo "<tr class='table-row border-b border-gray-700' data-user-id='{$user['user_id']}' data-name='" . htmlspecialchars($user['name']) . "' data-mobile='" . htmlspecialchars($user['mobile_number']) . "'>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($user['name']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($user['mobile_number']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($user['id_number']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($user['address']) . "</td>";
                        echo "<td class='p-4 text-gray-100'><img src='" . htmlspecialchars($aadhaar_image) . "' alt='Aadhaar' class='h-12 w-12 object-cover rounded'></td>";
                        echo "<td class='p-4 text-gray-100'>";
                        // Fetch allotted vehicles
                        $vehicle_query = "SELECT r.booking_id, r.vehicle_number, r.renting_time, r.rent_per_day, v.vehicle_name 
                                         FROM renting r 
                                         JOIN vehicles v ON r.vehicle_number = v.vehicle_number 
                                         WHERE r.user_id = {$user['user_id']} AND r.return_time IS NULL";
                        $vehicle_result = mysqli_query($conn, $vehicle_query);
                        if (mysqli_num_rows($vehicle_result) > 0) {
                            echo "<ul class='space-y-2'>";
                            while ($vehicle = mysqli_fetch_assoc($vehicle_result)) {
                                $renting_time = date('c', strtotime($vehicle['renting_time']));
                                echo "<li class='vehicle-row' data-start='{$renting_time}' data-rate='{$vehicle['rent_per_day']}'>";
                                echo htmlspecialchars($vehicle['vehicle_name']) . " (" . htmlspecialchars($vehicle['vehicle_number']) . ") - Rented on: " . htmlspecialchars($vehicle['renting_time']);
                                echo " - Cost: <span class='vehicle-cost'>₹0.00</span>";
                                echo " <a class='btn-danger inline-block text-white font-semibold py-1 px-2 rounded-md ml-2' href='?return=1&booking_id={$vehicle['booking_id']}' onclick='return confirm(\"Confirm vehicle return?\")'>Return</a>";
                                echo "</li>";
                            }
                            echo "</ul>";
                            mysqli_free_result($vehicle_result);
                        } else {
                            echo "No vehicles allotted";
                        }
                        echo "</td>";
                        echo "<td class='p-4 text-gray-100 total-cost'>₹0.00</td>";
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