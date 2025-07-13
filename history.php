<?php
include '../db.php'; // Database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Rental History - Vehicle Management</title>
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
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="container mx-auto p-6 max-w-5xl">
        <nav class="bg-gray-800 p-4 mb-8">
            <ul class="flex gap-4 justify-center">
                <li><a href="admin.php" class="text-indigo-400 hover:text-indigo-300">Dashboard</a></li>
                <li><a href="add_user_booking.php" class="text-indigo-400 hover:text-indigo-300">Add User & Booking</a></li>
                <li><a href="history.php" class="text-indigo-400 hover:text-indigo-300 font-bold">History</a></li>
            </ul>
        </nav>

        <h2 class="text-3xl font-bold text-center mb-8 text-white">Rental History</h2>

        <!-- History Table -->
        <div class="card bg-gray-800 p-6 rounded-lg shadow-lg">
            <h3 class="text-2xl font-semibold mb-4 text-indigo-400">Completed Rentals</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="p-4 text-sm font-medium text-gray-300">User</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Vehicle</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Renting Time</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Return Time</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Total Days</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Rent Per Day</th>
                            <th class="p-4 text-sm font-medium text-gray-300">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $query = "SELECT h.*, u.name AS user_name, v.vehicle_name 
                              FROM history h 
                              JOIN users u ON h.user_id = u.user_id 
                              JOIN vehicles v ON h.vehicle_number = v.vehicle_number 
                              ORDER BY h.return_time DESC";
                    $result = mysqli_query($conn, $query);
                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr class='table-row border-b border-gray-700'>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['user_name']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['vehicle_name']) . " (" . htmlspecialchars($row['vehicle_number']) . ")</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['renting_time']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['return_time']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>" . htmlspecialchars($row['total_days']) . "</td>";
                        echo "<td class='p-4 text-gray-100'>₹" . number_format($row['rent_per_day'], 2) . "</td>";
                        echo "<td class='p-4 text-gray-100'>₹" . number_format($row['total_cost'], 2) . "</td>";
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