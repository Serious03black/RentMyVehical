<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Revenue Summary</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-center mb-8 text-indigo-400">Revenue Dashboard</h1>
        <?php
        // कुल कमाई
        $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_cost) AS total FROM history"))['total'] ?? 0;
        // Payment mode summary
        $pmodes = mysqli_query($conn, "SELECT payment_mode, SUM(total_cost) AS total FROM history GROUP BY payment_mode");
        // Vehicle-wise revenue
        $vehicle_rev = mysqli_query($conn, "SELECT vehicle_number, SUM(total_cost) AS total FROM history GROUP BY vehicle_number");
        // Day-wise revenue
        $day_rev = mysqli_query($conn, "SELECT DATE(return_time) AS day, SUM(total_cost) AS total FROM history GROUP BY day ORDER BY day DESC LIMIT 10");
        // Month-wise revenue
        $month_rev = mysqli_query($conn, "SELECT DATE_FORMAT(return_time, '%Y-%m') AS month, SUM(total_cost) AS total FROM history GROUP BY month ORDER BY month DESC LIMIT 12");
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-800 p-6 rounded-lg shadow text-center">
                <div class="text-gray-400">Total Revenue</div>
                <div class="text-2xl font-bold text-green-400">₹<?php echo number_format($total_revenue,2); ?></div>
            </div>
            <div class="bg-gray-800 p-6 rounded-lg shadow text-center">
                <div class="text-gray-400 mb-2">Payment Modes</div>
                <?php while($pm = mysqli_fetch_assoc($pmodes)) {
                    echo '<div class="mb-1">'.htmlspecialchars($pm['payment_mode']).': <span class="text-indigo-300">₹'.number_format($pm['total'],2).'</span></div>';
                } ?>
            </div>
            <div class="bg-gray-800 p-6 rounded-lg shadow text-center">
                <div class="text-gray-400 mb-2">Top Vehicles (Revenue)</div>
                <?php $count=0; while($vr = mysqli_fetch_assoc($vehicle_rev)) { if(++$count>5) break; echo '<div class="mb-1">'.htmlspecialchars($vr['vehicle_number']).': <span class="text-indigo-300">₹'.number_format($vr['total'],2).'</span></div>'; } ?>
            </div>
            <div class="bg-gray-800 p-6 rounded-lg shadow text-center">
                <div class="text-gray-400 mb-2">Recent Days (Revenue)</div>
                <?php while($dr = mysqli_fetch_assoc($day_rev)) { echo '<div class="mb-1">'.htmlspecialchars($dr['day']).': <span class="text-indigo-300">₹'.number_format($dr['total'],2).'</span></div>'; } ?>
            </div>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 text-indigo-300">Month-wise Revenue</h2>
            <table class="w-full text-left">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="p-2">Month</th>
                        <th class="p-2">Total Revenue (₹)</th>
                    </tr>
                </thead>
                <tbody>
                <?php mysqli_data_seek($month_rev, 0); while($mr = mysqli_fetch_assoc($month_rev)) { ?>
                    <tr class="border-b border-gray-700">
                        <td class="p-2"><?php echo htmlspecialchars($mr['month']); ?></td>
                        <td class="p-2">₹<?php echo number_format($mr['total'],2); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 text-indigo-300">Vehicle-wise Revenue</h2>
            <table class="w-full text-left">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="p-2">Vehicle Number</th>
                        <th class="p-2">Total Revenue (₹)</th>
                    </tr>
                </thead>
                <tbody>
                <?php mysqli_data_seek($vehicle_rev, 0); while($vr = mysqli_fetch_assoc($vehicle_rev)) { ?>
                    <tr class="border-b border-gray-700">
                        <td class="p-2"><?php echo htmlspecialchars($vr['vehicle_number']); ?></td>
                        <td class="p-2">₹<?php echo number_format($vr['total'],2); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 text-indigo-300">All Revenue History</h2>
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
                $all = mysqli_query($conn, "SELECT * FROM history ORDER BY return_time DESC");
                while($row = mysqli_fetch_assoc($all)) {
                    echo '<tr class="border-b border-gray-700">';
                    echo '<td class="p-2">'.htmlspecialchars($row['user_id']).'</td>';
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