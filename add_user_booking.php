<?php
include 'db.php'; 
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

// Handle Add User Form
if (isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $aadhaar_image = '';

    if (isset($_FILES['aadhaar_image']) && $_FILES['aadhaar_image']['error'] == 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $aadhaar_image = $upload_dir . basename($_FILES['aadhaar_image']['name']);
        if (!move_uploaded_file($_FILES['aadhaar_image']['tmp_name'], $aadhaar_image)) {
            $message = "Error uploading Aadhaar image.";
        }
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, mobile_number, id_number, address, aadhaar_image) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $name, $mobile_number, $id_number, $address, $aadhaar_image);
    if (mysqli_stmt_execute($stmt)) {
        $message = "User added successfully!";
    } else {
        $message = "Error adding user: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle Add Booking Form
if (isset($_POST['add_booking'])) {
    $user_id = intval($_POST['user_id']);
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $rent_per_day = floatval($_POST['rent_per_day']);
    $renting_time = mysqli_real_escape_string($conn, $_POST['renting_time']); // यूज़र इनपुट टाइम

    $result = mysqli_query($conn, "SELECT status FROM vehicles WHERE vehicle_number = '$vehicle_number'");
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] === 'Free') {
            $check = mysqli_query($conn, "SELECT booking_id FROM renting WHERE vehicle_number = '$vehicle_number' AND return_time IS NULL");
            if (mysqli_num_rows($check) == 0) {
                $stmt = mysqli_prepare($conn, "INSERT INTO renting (user_id, vehicle_number, renting_time, rent_per_day) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "issd", $user_id, $vehicle_number, $renting_time, $rent_per_day);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_query($conn, "UPDATE vehicles SET status = 'Rented' WHERE vehicle_number = '$vehicle_number'");
                    $message = "Booking added successfully!";
                } else {
                    $message = "Error adding booking: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Error: Vehicle is already booked!";
            }
        } else {
            $message = "Error: Vehicle is not available!";
        }
    } else {
        $message = "Error: Vehicle not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add User & Booking - Vehicle Management</title>
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
        function filterVehicles() {
            const search = document.getElementById('vehicle-search').value.toLowerCase();
            const options = document.querySelectorAll('#vehicle-select option');
            options.forEach(option => {
                const name = option.dataset.name.toLowerCase();
                const number = option.dataset.number.toLowerCase();
                option.style.display = (name.includes(search) || number.includes(search)) ? '' : 'none';
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
    

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-0 md:ml-64">
        <button class="md:hidden btn-primary text-white font-semibold py-2 px-4 rounded-md mb-4" onclick="toggleSidebar()">☰ Menu</button>
        <h2 class="text-3xl font-bold text-center mb-8 text-white">Add User & Booking</h2>

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

        <!-- Add User Form -->
        <div id="add-user-form" class="card bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
            <h3 class="text-2xl font-semibold mb-4 text-indigo-400">Add New User</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300">Name</label>
                    <input type="text" name="name" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Mobile Number</label>
                    <input type="text" name="mobile_number" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">ID Number</label>
                    <input type="text" name="id_number" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Address</label>
                    <textarea name="address" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Aadhaar Image</label>
                    <input type="file" name="aadhaar_image" accept="image/*" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <button type="submit" name="add_user" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Add User</button>
            </form>
        </div>

        <!-- Add Booking Form -->
        <div id="booking-form" class="card bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
            <h3 class="text-2xl font-semibold mb-4 text-indigo-400">Add New Booking</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300">User</label>
                    <select name="user_id" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select User</option>
                        <?php
                        $user_result = mysqli_query($conn, "SELECT user_id, name FROM users");
                        while ($user = mysqli_fetch_assoc($user_result)) {
                            echo "<option value='{$user['user_id']}'>" . htmlspecialchars($user['name']) . "</option>";
                        }
                        mysqli_free_result($user_result);
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Vehicle</label>
                    <select id="vehicle-select" name="vehicle_number" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select Vehicle</option>
                        <?php
                        $vehicle_result = mysqli_query($conn, "SELECT v.vehicle_number, v.vehicle_name 
                                                              FROM vehicles v 
                                                              LEFT JOIN renting r ON v.vehicle_number = r.vehicle_number AND r.return_time IS NULL 
                                                              WHERE v.status = 'Free' AND r.booking_id IS NULL");
                        while ($vehicle = mysqli_fetch_assoc($vehicle_result)) {
                            echo "<option value='{$vehicle['vehicle_number']}' data-name='" . htmlspecialchars($vehicle['vehicle_name']) . "' data-number='" . htmlspecialchars($vehicle['vehicle_number']) . "'>" . htmlspecialchars($vehicle['vehicle_name']) . " ({$vehicle['vehicle_number']})</option>";
                        }
                        mysqli_free_result($vehicle_result);
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Rent Per Day (₹)</label>
                    <input type="number" step="0.01" name="rent_per_day" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Renting Time</label>
                    <input type="datetime-local" name="renting_time" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit" name="add_booking" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Add Booking</button>
            </form>
        </div>
        
        <!-- Return Vehicle Form -->
        <div id="return-vehicle-form" class="card bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
            <h3 class="text-2xl font-semibold mb-4 text-indigo-400">Return Vehicle</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300">User</label>
                    <select name="return_user_id" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select User</option>
                        <?php
                        $user_result = mysqli_query($conn, "SELECT user_id, name FROM users");
                        while ($user = mysqli_fetch_assoc($user_result)) {
                            echo "<option value='{$user['user_id']}'>" . htmlspecialchars($user['name']) . "</option>";
                        }
                        mysqli_free_result($user_result);
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Vehicle</label>
                    <select name="return_vehicle_number" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select Vehicle</option>
                        <?php
                        $vehicle_result = mysqli_query($conn, "SELECT v.vehicle_number, v.vehicle_name FROM vehicles v JOIN renting r ON v.vehicle_number = r.vehicle_number WHERE r.return_time IS NULL");
                        while ($vehicle = mysqli_fetch_assoc($vehicle_result)) {
                            echo "<option value='{$vehicle['vehicle_number']}'>" . htmlspecialchars($vehicle['vehicle_name']) . " ({$vehicle['vehicle_number']})</option>";
                        }
                        mysqli_free_result($vehicle_result);
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Return Time</label>
                    <input type="datetime-local" name="return_time" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit" name="return_vehicle" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Return Vehicle</button>
            </form>
            <?php
            if (isset($_POST['return_vehicle'])) {
                $user_id = intval($_POST['return_user_id']);
                $vehicle_number = mysqli_real_escape_string($conn, $_POST['return_vehicle_number']);
                $return_time = mysqli_real_escape_string($conn, $_POST['return_time']); // यूज़र इनपुट टाइम
                $return_time_ts = strtotime($return_time);
                // Active booking निकालें
                $booking_result = mysqli_query($conn, "SELECT * FROM renting WHERE user_id = $user_id AND vehicle_number = '$vehicle_number' AND return_time IS NULL");
                if ($booking = mysqli_fetch_assoc($booking_result)) {
                    $renting_time = strtotime($booking['renting_time']);
                    $rent_per_day = $booking['rent_per_day'];
                    if ($return_time_ts < $renting_time) {
                        echo '<div class="mt-4 p-2 bg-red-600 rounded">Error: Return Time, Booking Time से पहले नहीं हो सकता!</div>';
                        exit; // प्रोसेसिंग यहीं रुक जाएगी
                    } else {
                        $duration_seconds = $return_time_ts - $renting_time;
                        $total_days = ceil($duration_seconds / 86400);
                        $total_cost = ($rent_per_day / 86400) * $duration_seconds;
                        $total_cost = $total_cost < 0 ? 0 : round($total_cost, 2);
                        // हिस्ट्री में डालें
                        $stmt = mysqli_prepare($conn, "INSERT INTO history (user_id, vehicle_number, renting_time, return_time, total_days, rent_per_day, total_cost, payment_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $payment_mode = 'Cash'; // आप चाहें तो फॉर्म से ले सकते हैं
                        mysqli_stmt_bind_param($stmt, "isssidss", $user_id, $vehicle_number, $booking['renting_time'], $return_time, $total_days, $rent_per_day, $total_cost, $payment_mode);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        // renting में return_time अपडेट करें
                        $stmt = mysqli_prepare($conn, "UPDATE renting SET return_time = ? WHERE booking_id = ?");
                        mysqli_stmt_bind_param($stmt, "si", $return_time, $booking['booking_id']);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        // vehicles को फ्री करें
                        $stmt = mysqli_prepare($conn, "UPDATE vehicles SET status = 'Free' WHERE vehicle_number = ?");
                        mysqli_stmt_bind_param($stmt, "s", $vehicle_number);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        echo '<div class="mt-4 p-2 bg-green-600 rounded">वाहन सफलतापूर्वक लौटाया गया! कुल बिल: ₹' . number_format($total_cost, 2) . '</div>';
                    }
                } else {
                    echo '<div class="mt-4 p-2 bg-red-600 rounded">Error: Active booking नहीं मिली!</div>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>