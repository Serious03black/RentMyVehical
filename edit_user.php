<?php
include 'db.php';
if (!isset($_GET['user_id'])) { echo 'User not found.'; exit; }
$user_id = intval($_GET['user_id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $aadhaar_image = $_POST['current_aadhaar_image'];
    $profile_photo = $_POST['current_profile_photo'];
    if (isset($_FILES['aadhaar_image']) && $_FILES['aadhaar_image']['error'] == 0) {
        $upload_dir = 'admin/Uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $aadhaar_image = $upload_dir . basename($_FILES['aadhaar_image']['name']);
        move_uploaded_file($_FILES['aadhaar_image']['tmp_name'], $aadhaar_image);
    }
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $upload_dir = 'admin/Uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $profile_photo = $upload_dir . basename($_FILES['profile_photo']['name']);
        move_uploaded_file($_FILES['profile_photo']['tmp_name'], $profile_photo);
    }
    $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, mobile_number=?, id_number=?, address=?, aadhaar_image=?, profile_photo=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "ssssssi", $name, $mobile, $id_number, $address, $aadhaar_image, $profile_photo, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="p-2 bg-green-600 rounded mb-2">User updated successfully!</div>';
    } else {
        echo '<div class="p-2 bg-red-600 rounded mb-2">Error: ' . mysqli_error($conn) . '</div>';
    }
    mysqli_stmt_close($stmt);
}
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id"));
if (!$user) { echo 'User not found.'; exit; }
?>
<h2 class="text-l font-bold mb-2 text-indigo-400">Edit User</h2>
<form method="POST" enctype="multipart/form-data" class="space-y-3 max-w-md mx-auto p-2">
    <div>
        <label class="block text-xs font-medium text-gray-300">Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full mt-1 p-1 bg-gray-700 border border-gray-600 rounded text-sm text-white">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-300">Mobile Number</label>
        <input type="text" name="mobile_number" value="<?php echo htmlspecialchars($user['mobile_number']); ?>" required class="w-full mt-1 p-1 bg-gray-700 border border-gray-600 rounded text-sm text-white">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-300">ID Number</label>
        <input type="text" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>" required class="w-full mt-1 p-1 bg-gray-700 border border-gray-600 rounded text-sm text-white">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-300">Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required class="w-full mt-1 p-1 bg-gray-700 border border-gray-600 rounded text-sm text-white">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-300">Aadhaar Image</label>
        <input type="file" name="aadhaar_image" accept="image/*" class="w-full mt-1 p-1 bg-gray-700 border border-gray-600 rounded text-sm text-white">
        <input type="hidden" name="current_aadhaar_image" value="<?php echo htmlspecialchars($user['aadhaar_image']); ?>">
        <img src="<?php echo htmlspecialchars($user['aadhaar_image']); ?>" alt="Aadhaar" class="h-10 w-10 object-cover rounded mt-2">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-300">Profile Photo</label>
        <input type="file" name="profile_photo" accept="image/*" class="w-full mt-1 p-1 bg-gray-700 border border-gray-600 rounded text-sm text-white">
        <input type="hidden" name="current_profile_photo" value="<?php echo htmlspecialchars($user['profile_photo']); ?>">
        <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile" class="h-10 w-10 object-cover rounded-full mt-2">
    </div>
    <button type="submit" class="w-full btn-primary text-white font-semibold py-1 rounded text-sm">Update User</button>
</form>
<?php mysqli_close($conn); ?> 