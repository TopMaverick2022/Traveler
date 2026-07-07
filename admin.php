<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. You must be logged in as an administrator.";
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Admin specific operations and content start here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <ul>
                <li><a href="admin.php">Home</a></li>
                <li><a href="admin_op.php?action=manage_users">Manage Users</a></li>
                <li><a href="admin_op.php?action=manage_bookings">Manage Bookings</a></li>
                <li><a href="admin_op.php?action=manage_destinations">Manage Destinations</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>This is the administrator panel. Here you can manage various aspects of the Traveler website.</p>
        <!-- Admin dashboard content goes here -->
        <section class="dashboard-overview">
            <h3>Overview</h3>
            <ul>
                <li>Total Users: <!-- Fetch from DB --></li>
                <li>Total Bookings: <!-- Fetch from DB --></li>
                <li>Recent Activities: <!-- Fetch from DB --></li>
            </ul>
        </section>

        <!-- Example: Fetching some data from the database -->
        <section class="latest-users">
            <h3>Latest Registered Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT customer_id, username, email, role, dob FROM customer ORDER BY customer_id DESC LIMIT 5";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['customer_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['dob']) . "</td>"; // Assuming dob can be registration date or similar
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No users found.</td></tr>";
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </section>

    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Traveler Admin. All rights reserved.</p>
    </footer>
</body>
</html>
