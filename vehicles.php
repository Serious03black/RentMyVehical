<?php
include 'db.php';

// --- Add Vehicle Processing ---
if (isset($_POST['add_vehicle'])) {
    $vehicle_number = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['vehicle_number'])));
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $default_rent_per_day = floatval($_POST['default_rent_per_day']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $image_name = 'Uploads/placeholder.jpg'; // Default to full path
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image_name = $upload_dir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_name)) {
            echo '<div class="mt-4 p-2 bg-red-600 rounded">Error uploading image!</div>';
            $image_name = 'Uploads/placeholder.jpg';
        }
    }
    $pattern = '/^[A-Z]{2} [0-9]{2} [A-Z]{2} [0-9]{4}$/';
    if (!preg_match($pattern, $vehicle_number)) {
        echo '<div class="mt-4 p-2 bg-red-600 rounded">वाहन नंबर का फॉर्मेट गलत है! (जैसे: MH 12 DC 1201)</div>';
    } else {
        $check = mysqli_query($conn, "SELECT vehicle_number FROM vehicles WHERE vehicle_number = '$vehicle_number'");
        if (mysqli_num_rows($check) > 0) {
            echo '<div class="mt-4 p-2 bg-red-600 rounded">यह वाहन नंबर पहले से मौजूद है।</div>';
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO vehicles (vehicle_number, vehicle_name, default_rent_per_day, status, image) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdss", $vehicle_number, $vehicle_name, $default_rent_per_day, $status, $image_name);
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="mt-4 p-2 bg-green-600 rounded">वाहन सफलतापूर्वक जोड़ा गया!</div>';
            } else {
                echo '<div class="mt-4 p-2 bg-red-600 rounded">Error: ' . mysqli_error($conn) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- Edit Vehicle Processing ---
if (isset($_POST['edit_vehicle'])) {
    $vehicle_number = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['edit_vehicle_number'])));
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['edit_vehicle_name']);
    $default_rent_per_day = floatval($_POST['edit_default_rent_per_day']);
    $status = mysqli_real_escape_string($conn, $_POST['edit_status']);
    $image_name = mysqli_real_escape_string($conn, $_POST['current_image']);
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image_name = $upload_dir . basename($_FILES['edit_image']['name']);
        if (!move_uploaded_file($_FILES['edit_image']['tmp_name'], $image_name)) {
            echo '<div class="mt-4 p-2 bg-red-600 rounded">Error uploading image!</div>';
            $image_name = 'Uploads/placeholder.jpg';
        }
    }
    $stmt = mysqli_prepare($conn, "UPDATE vehicles SET vehicle_name=?, default_rent_per_day=?, status=?, image=? WHERE vehicle_number=?");
    mysqli_stmt_bind_param($stmt, "sdsss", $vehicle_name, $default_rent_per_day, $status, $image_name, $vehicle_number);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="mt-4 p-2 bg-green-600 rounded">वाहन डिटेल्स अपडेट हो गईं!</div>';
    } else {
        echo '<div class="mt-4 p-2 bg-red-600 rounded">Error: ' . mysqli_error($conn) . '</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- Delete Vehicle Processing ---
if (isset($_POST['delete_vehicle'])) {
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['delete_vehicle_number']);
    $stmt = mysqli_prepare($conn, "DELETE FROM vehicles WHERE vehicle_number = ?");
    mysqli_stmt_bind_param($stmt, "s", $vehicle_number);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="mt-4 p-2 bg-green-600 rounded">वाहन डिलीट हो गया!</div>';
    } else {
        echo '<div class="mt-4 p-2 bg-red-600 rounded">Error: ' . mysqli_error($conn) . '</div>';
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Vehicles - Vehicle Management</title>
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
    </style>
    <script>
        function filterVehicles() {
            const search = document.getElementById('vehicle-search').value.toLowerCase();
            const cards = document.querySelectorAll('.vehicle-card');
            cards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const number = card.dataset.number.toLowerCase();
                card.style.display = (name.includes(search) || number.includes(search)) ? '' : 'none';
            });
        }
        function openEditModal(vehicle) {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('edit_vehicle_number').value = vehicle.vehicle_number;
            document.getElementById('edit_vehicle_name').value = vehicle.vehicle_name;
            document.getElementById('edit_default_rent_per_day').value = vehicle.default_rent_per_day;
            document.getElementById('edit_status').value = vehicle.status;
            document.getElementById('current_image').value = vehicle.image;
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        function confirmDelete(vehicle_number) {
            if (confirm('क्या आप सच में इस वाहन को डिलीट करना चाहते हैं?')) {
                document.getElementById('delete_vehicle_number').value = vehicle_number;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div id="main-content" class="p-6 transition-all duration-300">
        <h2 class="text-3xl font-bold text-center mb-8 text-white">All Vehicles</h2>
        <!-- Add Vehicle Form -->
        <div class="card bg-gray-800 p-6 rounded-lg shadow-lg mb-8 max-w-xl mx-auto">
            <h3 class="text-xl font-semibold mb-4 text-indigo-400">Add Vehicle</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300">Vehicle Number</label>
                    <input type="text" name="vehicle_number" required placeholder="MH 12 DC 1201" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Vehicle Name</label>
                    <input type="text" name="vehicle_name" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Rent Per Day (₹)</label>
                    <input type="number" step="0.01" name="default_rent_per_day" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Status</label>
                    <select name="status" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                        <option value="Free">Free</option>
                        <option value="Rented">Rented</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <button type="submit" name="add_vehicle" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Add Vehicle</button>
            </form>
        </div>
        <h2 class="text-2xl font-bold mb-4 text-indigo-400">Search Vehicles</h2>
        <!-- Search Bar -->
        <div class="mb-4 max-w-xl mx-auto">
            <input id="vehicle-search" type="text" placeholder="Search by vehicle name or number..." class="w-full p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500" oninput="filterVehicles()">
        </div>
        <!-- Vehicles as Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        $query = "SELECT v.*, r.user_id, u.name AS user_name FROM vehicles v LEFT JOIN renting r ON v.vehicle_number = r.vehicle_number AND r.return_time IS NULL LEFT JOIN users u ON r.user_id = u.user_id ORDER BY v.vehicle_name";
        $result = mysqli_query($conn, $query);
        if (!$result) {
            echo '<div class="mt-4 p-2 bg-red-600 rounded">Error fetching vehicles: ' . mysqli_error($conn) . '</div>';
        } else {
            while ($vehicle = mysqli_fetch_assoc($result)) {
                $status = $vehicle['status'];
                $statusColor = $status === 'Rented' ? 'bg-red-600' : ($status === 'Maintenance' ? 'bg-yellow-500' : 'bg-green-600');
                $vehicle_json = htmlspecialchars(json_encode($vehicle), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="vehicle-card bg-gray-800 p-6 rounded-xl shadow-lg" data-name="<?php echo strtolower(htmlspecialchars($vehicle['vehicle_name'])); ?>" data-number="<?php echo strtolower(htmlspecialchars($vehicle['vehicle_number'])); ?>">
                    <img src="get_vehicle_image.php?vehicle_number=<?php echo urlencode($vehicle['vehicle_number']); ?>" 
                         alt="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" 
                         class="w-full h-40 object-cover rounded-md mb-4" 
                         onerror="this.src='Uploads/placeholder.jpg'">
                    <h3 class="text-xl font-semibold text-indigo-400 mb-2"><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></h3>
                    <p class="text-gray-300 text-sm mb-1">No.: <span class="font-semibold"><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></span></p>
                    <p class="text-gray-300 text-sm mb-1">Rent/Day: ₹<?php echo htmlspecialchars($vehicle['default_rent_per_day']); ?></p>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white <?php echo $statusColor; ?>"><?php echo $status; ?></span>
                    <?php if ($status === 'Rented' && $vehicle['user_name']) { ?>
                        <p class="text-sm mt-2 text-green-400">Booked by: <?php echo htmlspecialchars($vehicle['user_name']); ?> (User ID: <?php echo $vehicle['user_id']; ?>)</p>
                    <?php } ?>
                    <div class="flex gap-2 mt-4">
                        <button onclick="openEditModal(<?php echo $vehicle_json; ?>)" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">Edit</button>
                        <button onclick="confirmDelete('<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Delete</button>
                    </div>
                </div>
                <?php
            }
            mysqli_free_result($result);
        }
        ?>
        </div>
        <!-- Edit Modal -->
        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-gray-900 rounded-lg p-8 max-w-xl w-full relative">
                <button onclick="closeEditModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white text-2xl">×</button>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="edit_vehicle_number" id="edit_vehicle_number">
                    <input type="hidden" name="current_image" id="current_image">
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Vehicle Name</label>
                        <input type="text" name="edit_vehicle_name" id="edit_vehicle_name" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Rent Per Day (₹)</label>
                        <input type="number" step="0.01" name="edit_default_rent_per_day" id="edit_default_rent_per_day" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Status</label>
                        <select name="edit_status" id="edit_status" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                            <option value="Free">Free</option>
                            <option value="Rented">Rented</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Image</label>
                        <input type="file" name="edit_image" accept="image/*" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                    </div>
                    <button type="submit" name="edit_vehicle" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Update Vehicle</button>
                </form>
            </div>
        </div>
        <!-- Delete Form (hidden) -->
        <form method="POST" id="deleteForm" style="display:none;">
            <input type="hidden" name="delete_vehicle_number" id="delete_vehicle_number">
            <button type="submit" name="delete_vehicle"></button>
        </form>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>