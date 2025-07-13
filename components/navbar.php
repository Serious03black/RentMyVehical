<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="w-full bg-gray-800 p-4 shadow-lg flex items-center justify-between">
    <div class="flex items-center space-x-8">
        <?php if ($current_page != 'admin.php') { ?>
            <a href="admin/admin.php" class="text-xl font-bold text-indigo-400">Home</a>
        <?php } ?>
        <a href="../user.php" class="text-gray-300 hover:text-indigo-400">User</a>
        <a href="user_vehicles.php" class="text-gray-300 hover:text-indigo-400">Vehicle</a>
        <a href="revenue.php" class="text-gray-300 hover:text-indigo-400">Revenue</a>
    </div>
</nav>
