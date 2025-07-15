<?php
include 'db.php'; // Database connection

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
    $vehicle_number = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['vehicle_number'])));
    $vehicle_number = preg_replace('/\s+/', ' ', $vehicle_number);
    $vehicle_number = preg_replace('/[^A-Z0-9 ]/', '', $vehicle_number);
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $default_rent_per_day = floatval($_POST['default_rent_per_day']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $image = '';
    $image_blob = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image = $upload_dir . basename($_FILES['image']['name']);
        $image_blob = file_get_contents($_FILES['image']['tmp_name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
            $message = "Error uploading vehicle image.";
        }
    } else {
        $image = 'placeholder.jpg';
        $image_blob = null;
    }
    $pattern = '/^[A-Z]{2} [0-9]{2} [A-Z]{2} [0-9]{4}$/';
    if (!preg_match($pattern, $vehicle_number)) {
        $message = "वाहन नंबर का फॉर्मेट गलत है! सही फॉर्मेट: MH 12 DC 1201";
    } else {
        $check_query = "SELECT vehicle_number FROM vehicles WHERE vehicle_number = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $vehicle_number);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $message = "यह वाहन नंबर पहले से मौजूद है। कृपया नया नंबर डालें।";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO vehicles (vehicle_number, vehicle_name, default_rent_per_day, status, image, image_blob) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdsss", $vehicle_number, $vehicle_name, $default_rent_per_day, $status, $image, $image_blob);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Vehicle added successfully!";
            } else {
                $message = "Error adding vehicle: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Handle Edit Vehicle Form
if (isset($_POST['edit_vehicle'])) {
    $vehicle_number = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['vehicle_number'])));
    $vehicle_number = preg_replace('/\s+/', ' ', $vehicle_number);
    $vehicle_number = preg_replace('/[^A-Z0-9 ]/', '', $vehicle_number);
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $default_rent_per_day = floatval($_POST['default_rent_per_day']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $image = mysqli_real_escape_string($conn, $_POST['current_image']);
    $image_blob = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image = $upload_dir . basename($_FILES['image']['name']);
        $image_blob = file_get_contents($_FILES['image']['tmp_name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
            $message = "Error uploading vehicle image.";
        }
        $stmt = mysqli_prepare($conn, "UPDATE vehicles SET vehicle_name = ?, default_rent_per_day = ?, status = ?, image = ?, image_blob = ? WHERE vehicle_number = ?");
        mysqli_stmt_bind_param($stmt, "sdssss", $vehicle_name, $default_rent_per_day, $status, $image, $image_blob, $vehicle_number);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE vehicles SET vehicle_name = ?, default_rent_per_day = ?, status = ?, image = ? WHERE vehicle_number = ?");
        mysqli_stmt_bind_param($stmt, "sdsss", $vehicle_name, $default_rent_per_day, $status, $image, $vehicle_number);
    }
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

// Handle Return Booking (with payment mode)
if (isset($_POST['return_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    $payment_mode = mysqli_real_escape_string($conn, $_POST['payment_mode']);
    $result = mysqli_query($conn, "SELECT r.*, v.vehicle_name, v.image, v.image_blob, v.status, v.default_rent_per_day FROM renting r JOIN vehicles v ON r.vehicle_number = v.vehicle_number WHERE r.booking_id = $booking_id");
    if ($row = mysqli_fetch_assoc($result)) {
        $renting_time = strtotime($row['renting_time']);
        $return_time = time();
        $duration_seconds = $return_time - $renting_time;
        $total_days = ceil($duration_seconds / 86400);
        $total_cost = ($row['rent_per_day'] / 86400) * $duration_seconds;
        $total_cost = round($total_cost, 2);

        $stmt = mysqli_prepare($conn, "INSERT INTO history (user_id, vehicle_number, renting_time, return_time, total_days, rent_per_day, total_cost, payment_mode) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issidds", $row['user_id'], $row['vehicle_number'], $row['renting_time'], $total_days, $row['rent_per_day'], $total_cost, $payment_mode);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE renting SET return_time = NOW() WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $booking_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "DELETE FROM renting WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $booking_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE vehicles SET status = 'Free' WHERE vehicle_number = ?");
        mysqli_stmt_bind_param($stmt, "s", $row['vehicle_number']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $message = "वाहन सफलतापूर्वक लौटाया गया! कुल बिल: ₹$total_cost, Payment Mode: $payment_mode";
    } else {
        $message = "Booking not found!";
    }
}

// Add Booking (manual date/time)
if (isset($_POST['add_booking_admin'])) {
    $user_id = intval($_POST['user_id']);
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $rent_per_day = floatval($_POST['rent_per_day']);
    $renting_time = mysqli_real_escape_string($conn, $_POST['renting_time']);
    $check = mysqli_query($conn, "SELECT * FROM renting WHERE vehicle_number = '$vehicle_number' AND return_time IS NULL");
    if (mysqli_num_rows($check) > 0) {
        $message = "इस वाहन की पहले से एक active booking है!";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO renting (user_id, vehicle_number, renting_time, rent_per_day) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issd", $user_id, $vehicle_number, $renting_time, $rent_per_day);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_query($conn, "UPDATE vehicles SET status='Rented' WHERE vehicle_number='$vehicle_number'");
            $message = "Booking added successfully!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Return Vehicle (manual date/time, correct billing logic)
if (isset($_POST['modal_return_confirm'])) {
    $booking_id = intval($_POST['modal_booking_id']);
    $user_id = intval($_POST['modal_user_id']);
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['modal_vehicle_number']);
    $renting_time = $_POST['modal_renting_time'];
    $rent_per_day = floatval($_POST['modal_rent_per_day']);
    $return_time = mysqli_real_escape_string($conn, $_POST['modal_return_time']);
    $total_cost = isset($_POST['modal_total_cost']) ? floatval($_POST['modal_total_cost']) : 0;
    $renting_time_ts = strtotime($renting_time);
    $return_time_ts = strtotime($return_time);
    if ($return_time_ts <= $renting_time_ts) {
        $message = "Error: Return Time, Booking Time से पहले या बराबर नहीं हो सकता!";
    } else {
        $total_hours = ceil(($return_time_ts - $renting_time_ts) / 3600);
        if ($total_hours <= 24) {
            $auto_cost = $rent_per_day;
        } else {
            $full_days = floor($total_hours / 24);
            $remaining_hours = $total_hours % 24;
            $hourly_rate = $rent_per_day / 24;
            $auto_cost = ($full_days * $rent_per_day) + ($remaining_hours * $hourly_rate);
        }
        $auto_cost = round($auto_cost, 2);
        $final_cost = ($total_cost > 0) ? $total_cost : $auto_cost;
        $total_days = ceil(($return_time_ts - $renting_time_ts) / 86400);
        $payment_mode = 'Cash';

        $stmt = mysqli_prepare($conn, "INSERT INTO history (user_id, vehicle_number, renting_time, return_time, total_days, rent_per_day, total_cost, payment_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssidss", $user_id, $vehicle_number, $renting_time, $return_time, $total_days, $rent_per_day, $final_cost, $payment_mode);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE renting SET return_time = ? WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $return_time, $booking_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE vehicles SET status = 'Free' WHERE vehicle_number = ?");
        mysqli_stmt_bind_param($stmt, "s", $vehicle_number);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $message = "वाहन सफलतापूर्वक लौटाया गया! कुल बिल: ₹" . number_format($final_cost, 2);
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

        let modalUserName = '';
        let modalVehicleName = '';
        function openReturnModal(bookingId, userId, vehicleNumber, rentingTime, rentPerDay, userName, vehicleName) {
            document.getElementById('returnModal').classList.remove('hidden');
            document.getElementById('modal_booking_id').value = bookingId;
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_vehicle_number').value = vehicleNumber;
            document.getElementById('modal_renting_time').value = rentingTime;
            document.getElementById('modal_rent_per_day').value = rentPerDay;
            document.getElementById('modal_user_name').innerText = userName;
            document.getElementById('modal_vehicle_name').innerText = vehicleName + ' (' + vehicleNumber + ')';
            document.getElementById('modal_booking_date').innerText = new Date(rentingTime).toLocaleString();
            document.getElementById('modal_rent_per_day_show').innerText = rentPerDay;
            document.getElementById('modal_return_time').value = '';
            document.getElementById('modal_total_cost').value = '';
            modalUserName = userName;
            modalVehicleName = vehicleName;
        }
        function closeReturnModal() {
            document.getElementById('returnModal').classList.add('hidden');
        }
        function calculateBill() {
            const rentPerDay = parseFloat(document.getElementById('modal_rent_per_day').value);
            const rentingTime = new Date(document.getElementById('modal_renting_time').value).getTime();
            const returnTimeInput = document.getElementById('modal_return_time').value;
            if (!returnTimeInput) return;
            const returnTime = new Date(returnTimeInput).getTime();
            if (returnTime <= rentingTime) {
                document.getElementById('modal_total_cost').value = '';
                return;
            }
            const totalHours = Math.ceil((returnTime - rentingTime) / (1000 * 3600));
            let totalCost = 0;
            if (totalHours <= 24) {
                totalCost = rentPerDay;
            } else {
                const fullDays = Math.floor(totalHours / 24);
                const remainingHours = totalHours % 24;
                const hourlyRate = rentPerDay / 24;
                totalCost = (fullDays * rentPerDay) + (remainingHours * hourlyRate);
            }
            document.getElementById('modal_total_cost').value = totalCost.toFixed(2);
        }
    </script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div id="main-content" class="flex-1 p-6 ml-0 transition-all duration-300">
        <button class="md:hidden btn-primary text-white font-semibold py-2 px-4 rounded-md mb-4" onclick="toggleSidebar()">☰ Menu</button>
        <h2 class="text-3xl font-bold text-center mb-8 text-white">Admin Dashboard - Vehicle Management</h2>

        <!-- Summary Cards -->
        <div class="flex justify-center gap-8 mb-8">
            <a href="vehicles.php" class="circle-card flex-col">
                <h4 class="text-sm font-medium text-gray-300">Rented Vehicles</h4>
                <p class="text-xl font-bold text-indigo-400"><?php echo $rented_vehicles; ?> / <?php echo $total_vehicles; ?></p>
            </a>
            <a href="revenue.php" class="circle-card flex-col">
                <h4 class="text-sm font-medium text-gray-300">Today's Revenue</h4>
                <p class="text-xl font-bold text-indigo-400">₹<?php echo number_format($today_revenue, 2); ?></p>
            </a>
            <a href="user.php" class="circle-card flex-col">
                <h4 class="text-sm font-medium text-gray-300">Total Users</h4>
                <p class="text-xl font-bold text-indigo-400"><?php echo $total_users; ?></p>
            </a>
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
            if (!$vehicle_result) {
                echo '<div class="text-red-400">Error fetching vehicles: ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
            } else {
                while ($vehicle = mysqli_fetch_assoc($vehicle_result)) {
                    // इमेज path सही करें (मानते हैं कि इमेज 'Uploads/' फोल्डर में है)
                    $image_path = !empty($vehicle['image']) ? '' . $vehicle['image'] : 'Uploads/placeholder.jpg';
                    ?>
                    <div class="vehicle-card card bg-gray-800 p-4 rounded-lg shadow-lg" data-name="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" data-number="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>">
                        <img src="<?php echo $image_path; ?>" alt="Vehicle Image" style="width:100%;height:200px;object-fit:cover;">
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
                                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($image_path); ?>">
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
            }
            ?>
        </div>

        <!-- Add Booking Form (Admin) -->
        <div class="card bg-gray-800 p-6 rounded-lg shadow-lg mb-8 max-w-xl mx-auto">
            <h3 class="text-2xl font-semibold mb-4 text-indigo-400">Add Booking (Admin)</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300">User</label>
                    <select name="user_id" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
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
                    <select name="vehicle_number" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                        <option value="">Select Vehicle</option>
                        <?php
                        $vehicle_result = mysqli_query($conn, "SELECT vehicle_number, vehicle_name FROM vehicles WHERE status = 'Free'");
                        while ($vehicle = mysqli_fetch_assoc($vehicle_result)) {
                            echo "<option value='{$vehicle['vehicle_number']}'>" . htmlspecialchars($vehicle['vehicle_name']) . " ({$vehicle['vehicle_number']})</option>";
                        }
                        mysqli_free_result($vehicle_result);
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Rent Per Day (₹)</label>
                    <input type="number" step="0.01" name="rent_per_day" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Booking Date & Time</label>
                    <input type="datetime-local" name="renting_time" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <button type="submit" name="add_booking_admin" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Add Booking</button>
            </form>
        </div>

        <!-- Active Bookings Table -->
        <div class="card bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-4 text-indigo-400">Active Bookings</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="p-4 text-sm font-medium text-gray-300">User</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Vehicle</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Booking Time</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Rent Per Day (₹)</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $query = "SELECT r.*, u.name AS user_name, v.vehicle_name 
                              FROM renting r 
                              JOIN users u ON r.user_id = u.user_id 
                              JOIN vehicles v ON r.vehicle_number = v.vehicle_number 
                              WHERE r.return_time IS NULL";
                    $result = mysqli_query($conn, $query);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                            <tr class="table-row" data-start="<?php echo htmlspecialchars($row['renting_time']); ?>" data-rate="<?php echo htmlspecialchars($row['rent_per_day']); ?>">
                                <td class="p-4 text-gray-200"><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td class="p-4 text-gray-200"><?php echo htmlspecialchars($row['vehicle_name']) . ' (' . htmlspecialchars($row['vehicle_number']) . ')'; ?></td>
                                <td class="p-4 text-gray-200"><?php echo htmlspecialchars($row['renting_time']); ?></td>
                                <td class="p-4 text-gray-200">₹<?php echo number_format($row['rent_per_day'], 2); ?></td>
                                <td class="p-4">
                                    <button onclick="openReturnModal(
                                        '<?php echo $row['booking_id']; ?>',
                                        '<?php echo $row['user_id']; ?>',
                                        '<?php echo htmlspecialchars($row['vehicle_number']); ?>',
                                        '<?php echo htmlspecialchars($row['renting_time']); ?>',
                                        '<?php echo $row['rent_per_day']; ?>',
                                        '<?php echo htmlspecialchars($row['user_name']); ?>',
                                        '<?php echo htmlspecialchars($row['vehicle_name']); ?>'
                                    )" class="btn-primary text-white font-semibold py-1 px-3 rounded-md">Return</button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-200">No active bookings found.</td>
                        </tr>
                        <?php
                    }
                    mysqli_free_result($result);
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Return Modal -->
        <div id="returnModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-60 z-50 hidden">
            <div class="bg-gray-900 p-8 rounded-lg shadow-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4 text-indigo-400">Confirm Vehicle Return</h2>
                <form method="POST">
                    <input type="hidden" name="modal_booking_id" id="modal_booking_id">
                    <input type="hidden" name="modal_user_id" id="modal_user_id">
                    <input type="hidden" name="modal_vehicle_number" id="modal_vehicle_number">
                    <input type="hidden" name="modal_renting_time" id="modal_renting_time">
                    <input type="hidden" name="modal_rent_per_day" id="modal_rent_per_day">
                    <div class="mb-2 text-gray-200">User: <span id="modal_user_name"></span></div>
                    <div class="mb-2 text-gray-200">Vehicle: <span id="modal_vehicle_name"></span></div>
                    <div class="mb-2 text-gray-200">Booking Date: <span id="modal_booking_date"></span></div>
                    <div class="mb-2 text-gray-200">Rent Per Day: ₹<span id="modal_rent_per_day_show"></span></div>
                    <div class="mb-2 text-gray-200">Return Date & Time:</div>
                    <input type="datetime-local" name="modal_return_time" id="modal_return_time" required class="w-full mb-4 p-2 bg-gray-700 border border-gray-600 rounded-md text-white" onchange="calculateBill()">
                    <div class="mb-2 text-gray-200">Total Bill (editable):</div>
                    <input type="number" step="0.01" name="modal_total_cost" id="modal_total_cost" required class="w-full mb-4 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                    <button type="submit" name="modal_return_confirm" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Confirm Return</button>
                    <button type="button" onclick="closeReturnModal()" class="w-full mt-2 btn-secondary text-white font-semibold py-2 rounded-md">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>