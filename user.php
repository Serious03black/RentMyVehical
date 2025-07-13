<?php
include 'db.php'; // Database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Users - Vehicle Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    function filterUsers() {
        const search = document.getElementById('user-search').value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const name = row.dataset.name.toLowerCase();
            const mobile = row.dataset.mobile.toLowerCase();
            row.style.display = (name.includes(search) || mobile.includes(search)) ? '' : 'none';
        });
    }
    function showUserModal(userId) {
        fetch('user_details.php?user_id=' + userId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('user-modal-content').innerHTML = html;
                document.getElementById('user-modal').classList.remove('hidden');
            });
    }
    function closeUserModal() {
        document.getElementById('user-modal').classList.add('hidden');
    }
    function editUserModal(userId) {
        fetch('edit_user.php?user_id=' + userId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('user-modal-content').innerHTML = html;
                document.getElementById('user-modal').classList.remove('hidden');
            });
    }
    </script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div id="main-content" class="p-6 transition-all duration-300">
        <h2 class="text-3xl font-bold text-center mb-8 text-white">Add User</h2>
        <!-- Add User Form -->
        <div class="card bg-gray-800 p-6 rounded-lg shadow-lg mb-8 max-w-xl mx-auto">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300">Name</label>
                    <input type="text" name="name" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Mobile Number</label>
                    <input type="text" name="mobile_number" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">ID Number</label>
                    <input type="text" name="id_number" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Address</label>
                    <input type="text" name="address" required class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Aadhaar Image</label>
                    <input type="file" name="aadhaar_image" accept="image/*" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300">Profile Photo</label>
                    <input type="file" name="profile_photo" accept="image/*" class="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                </div>
                <button type="submit" name="add_user" class="w-full btn-primary text-white font-semibold py-2 rounded-md">Add User</button>
            </form>
            <?php
            if (isset($_POST['add_user'])) {
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $mobile = mysqli_real_escape_string($conn, $_POST['mobile_number']);
                $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
                $address = mysqli_real_escape_string($conn, $_POST['address']);
                $aadhaar_image = '';
                $profile_photo = '';
                if (isset($_FILES['aadhaar_image']) && $_FILES['aadhaar_image']['error'] == 0) {
                    $upload_dir = 'admin/Uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $aadhaar_image = $upload_dir . basename($_FILES['aadhaar_image']['name']);
                    move_uploaded_file($_FILES['aadhaar_image']['tmp_name'], $aadhaar_image);
                } else {
                    $aadhaar_image = 'admin/Uploads/placeholder.jpg';
                }
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                    $upload_dir = 'admin/Uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $profile_photo = $upload_dir . basename($_FILES['profile_photo']['name']);
                    move_uploaded_file($_FILES['profile_photo']['tmp_name'], $profile_photo);
                } else {
                    $profile_photo = 'admin/Uploads/placeholder.jpg';
                }
                $stmt = mysqli_prepare($conn, "INSERT INTO users (name, mobile_number, id_number, address, aadhaar_image, profile_photo) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssss", $name, $mobile, $id_number, $address, $aadhaar_image, $profile_photo);
                if (mysqli_stmt_execute($stmt)) {
                    echo '<div class="mt-4 p-2 bg-green-600 rounded">User added successfully!</div>';
                } else {
                    echo '<div class="mt-4 p-2 bg-red-600 rounded">Error: ' . mysqli_error($conn) . '</div>';
                }
                mysqli_stmt_close($stmt);
            }
            ?>
        </div>
        <h2 class="text-2xl font-bold mb-4 text-indigo-400">All Users</h2>
        <!-- Search Bar -->
        <div class="mb-4 max-w-xl mx-auto">
            <input id="user-search" type="text" placeholder="Search by user name or mobile number..." class="w-full p-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:ring-indigo-500 focus:border-indigo-500" oninput="filterUsers()">
        </div>
        <!-- Users as Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        $query = "SELECT * FROM users ORDER BY name";
        $result = mysqli_query($conn, $query);
        while ($user = mysqli_fetch_assoc($result)) {
            $aadhaar_image = $user['aadhaar_image'] ?: 'admin/Uploads/placeholder.jpg';
            $profile_photo = $user['profile_photo'] ?: 'admin/Uploads/placeholder.jpg';
            echo "<div class='bg-gray-800 p-6 rounded-xl shadow-lg user-card cursor-pointer' data-name='" . strtolower($user['name']) . "' data-mobile='" . strtolower($user['mobile_number']) . "' onclick='showUserModal(" . $user['user_id'] . ")'>";
            echo "<img src='" . $profile_photo . "' alt='Profile' class='w-20 h-20 object-cover rounded-full mx-auto mb-4'>";
            echo "<h3 class='text-xl font-semibold text-indigo-400 text-center mb-2'>" . htmlspecialchars($user['name']) . "</h3>";
            echo "<p class='text-gray-300 text-sm text-center mb-1'>📞 " . htmlspecialchars($user['mobile_number']) . "</p>";
            echo "<p class='text-gray-400 text-sm text-center mb-1'>🆔 ID: " . htmlspecialchars($user['id_number']) . "</p>";
            echo "<p class='text-gray-400 text-sm text-center mb-2'>🏠 " . htmlspecialchars($user['address']) . "</p>";
            echo "<img src='" . $aadhaar_image . "' alt='Aadhaar' class='w-16 h-16 object-cover rounded mx-auto mb-2'>";
            // Allotted vehicles
            $vehicle_query = "SELECT v.vehicle_name, v.vehicle_number FROM renting r JOIN vehicles v ON r.vehicle_number = v.vehicle_number WHERE r.user_id = {$user['user_id']} AND r.return_time IS NULL";
            $vehicle_result = mysqli_query($conn, $vehicle_query);
            echo "<div class='mt-2 text-sm text-center'>";
            if (mysqli_num_rows($vehicle_result) > 0) {
                echo "<p class='font-semibold text-green-400 mt-2'>🚗 Allotted Vehicles:</p><ul class='list-disc list-inside'>";
                while ($vehicle = mysqli_fetch_assoc($vehicle_result)) {
                    echo "<li>" . htmlspecialchars($vehicle['vehicle_name']) . " (" . htmlspecialchars($vehicle['vehicle_number']) . ")</li>";
                }
                echo "</ul>";
                mysqli_free_result($vehicle_result);
            } else {
                echo "<p class='text-red-400 mt-2'>❌ No vehicles allotted</p>";
            }
            echo "</div>";
            echo "<div class='mt-4 flex justify-center gap-4'>";
            echo "<button class='bg-blue-600 hover:bg-blue-700 text-white px-4 py-1 rounded' onclick='event.stopPropagation(); editUserModal(" . $user['user_id'] . ")'>Edit</button>";
            echo "</div>";
            echo "</div>";
        }
        mysqli_free_result($result);
        ?>
        </div>
        <!-- User Modal -->
        <div id="user-modal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-gray-900 rounded-lg p-8 max-w-2xl w-full relative">
                <button onclick="closeUserModal()" class="absolute top-2 right-2 text-gray-400 hover:text-white text-2xl">&times;</button>
                <div id="user-modal-content"></div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?> 