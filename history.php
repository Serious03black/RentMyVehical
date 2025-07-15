<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Booking History</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-center mb-8 text-indigo-400">Booking History</h1>
        <form method="GET" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="user" placeholder="User ID or Name" value="<?php echo isset($_GET['user']) ? htmlspecialchars($_GET['user']) : '' ?>" class="p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
            <input type="text" name="vehicle" placeholder="Vehicle Number" value="<?php echo isset($_GET['vehicle']) ? htmlspecialchars($_GET['vehicle']) : '' ?>" class="p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
            <input type="date" name="date" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>" class="p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
            <select name="payment_mode" class="p-2 bg-gray-700 border border-gray-600 rounded-md text-white">
                <option value="">All Payment Modes</option>
                <option value="Online" <?php if(isset($_GET['payment_mode']) && $_GET['payment_mode']==='Online') echo 'selected'; ?>>Online</option>
                <option value="Cash" <?php if(isset($_GET['payment_mode']) && $_GET['payment_mode']==='Cash') echo 'selected'; ?>>Cash</option>
                <option value="Later" <?php if(isset($_GET['payment_mode']) && $_GET['payment_mode']==='Later') echo 'selected'; ?>>Later</option>
            </select>
            <button type="submit" class="md:col-span-4 btn-primary text-white font-semibold py-2 rounded-md">Search / Filter</button>
        </form>
        <div class="bg-gray-800 p-6 rounded-lg shadow mb-8">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="p-2">User ID</th>
                        <th class="p-2">Vehicle Number</th>
                        <th class="p-2">Booking Time</th>
                        <th class="p-2">Return Time</th>
                        <th class="p-2">Total Days</th>
                        <th class="p-2">Rent/Day</th>
                        <th class="p-2">Total Bill</th>
                        <th class="p-2">Payment Mode</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Filter logic
                $where = [];
                if (!empty($_GET['user'])) {
                    $user = mysqli_real_escape_string($conn, $_GET['user']);
                    $where[] = "(user_id LIKE '%$user%' OR user_id IN (SELECT user_id FROM users WHERE name LIKE '%$user%'))";
                }
                if (!empty($_GET['vehicle'])) {
                    $vehicle = mysqli_real_escape_string($conn, $_GET['vehicle']);
                    $where[] = "vehicle_number LIKE '%$vehicle%'";
                }
                if (!empty($_GET['date'])) {
                    $date = mysqli_real_escape_string($conn, $_GET['date']);
                    $where[] = "DATE(return_time) = '$date'";
                }
                if (!empty($_GET['payment_mode'])) {
                    $pm = mysqli_real_escape_string($conn, $_GET['payment_mode']);
                    $where[] = "payment_mode = '$pm'";
                }
                $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
                $all = mysqli_query($conn, "SELECT h.*, u.name AS user_name FROM history h LEFT JOIN users u ON h.user_id = u.user_id $where_sql ORDER BY h.return_time DESC");
                while($row = mysqli_fetch_assoc($all)) {
                    echo '<tr class="border-b border-gray-700">';
                    echo '<td class="p-2">'.htmlspecialchars($row['user_id']).' - '.htmlspecialchars($row['user_name']).'</td>';
                    echo '<td class="p-2">'.htmlspecialchars($row['vehicle_number']).'</td>';
                    echo '<td class="p-2">'.htmlspecialchars($row['renting_time']).'</td>';
                    echo '<td class="p-2">'.htmlspecialchars($row['return_time']).'</td>';
                    echo '<td class="p-2">'.htmlspecialchars($row['total_days']).'</td>';
                    echo '<td class="p-2">₹'.number_format($row['rent_per_day'],2).'</td>';
                    echo '<td class="p-2">₹'.number_format($row['total_cost'],2).'</td>';
                    echo '<td class="p-2">'.htmlspecialchars($row['payment_mode']).'</td>';
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>