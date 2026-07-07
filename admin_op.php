<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. You must be logged in as an administrator to perform admin operations.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$action = $_GET['action'] ?? '';
$message = '';
$error = '';

// --- Function to sanitize input (basic example) ---
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// --- Handle Admin Operations ---
switch ($action) {
    case 'manage_users':
        // Example: Display users, allow edit/delete
        $users = [];
        $query = "SELECT customer_id, username, email, role, phone FROM customer";
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        } else {
            $error = "Error fetching users: " . $conn->error;
        }
        break;
    case 'edit_user':
        // Example: Edit user details
        $user_id = sanitize_input($_GET['id'] ?? '');
        if (!empty($user_id) && is_numeric($user_id)) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Process update form
                $new_username = sanitize_input($_POST['username']);
                $new_email = sanitize_input($_POST['email']);
                $new_role = sanitize_input($_POST['role']);

                $stmt = $conn->prepare("UPDATE customer SET username = ?, email = ?, role = ? WHERE customer_id = ?");
                $stmt->bind_param("sssi", $new_username, $new_email, $new_role, $user_id);
                if ($stmt->execute()) {
                    $message = "User updated successfully!";
                } else {
                    $error = "Failed to update user: " . $stmt->error;
                }
                $stmt->close();
            } else {
                // Fetch user data for edit form
                $stmt = $conn->prepare("SELECT customer_id, username, email, role, phone FROM customer WHERE customer_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_to_edit = $result->fetch_assoc();
                $stmt->close();
            }
        }
        break;
    case 'delete_user':
        $user_id = sanitize_input($_GET['id'] ?? '');
        if (!empty($user_id) && is_numeric($user_id)) {
            $stmt = $conn->prepare("DELETE FROM customer WHERE customer_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = "User deleted successfully!";
            } else {
                $error = "Failed to delete user: " . $stmt->error;
            }
            $stmt->close();
        }
        header("Location: admin_op.php?action=manage_users");
        exit();
        break;
    case 'manage_bookings':
        // Logic for managing bookings
        $bookings = [];
        $query = "SELECT b.booking_id, c.username, d.destination_name, b.booking_date, b.status FROM booking b JOIN customer c ON b.customer_id = c.customer_id JOIN destination d ON b.destination_id = d.destination_id";
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }
        } else {
            $error = "Error fetching bookings: " . $conn->error;
        }
        break;
    case 'manage_destinations':
        // Logic for managing destinations
        $destinations = [];
        $query = "SELECT destination_id, destination_name, location, price FROM destination";
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $destinations[] = $row;
            }
        } else {
            $error = "Error fetching destinations: " . $conn->error;
        }
        break;
    default:
        $error = "Invalid admin operation.";
        break;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Operations</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .message { color: green; font-weight: bold; margin-bottom: 10px; }
        .error { color: red; font-weight: bold; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a { margin-right: 5px; text-decoration: none; }
    </style>
</head>
<body>
    <header>
        <h1>Admin Operations</h1>
        <nav>
            <ul>
                <li><a href="admin.php">Admin Dashboard</a></li>
                <li><a href="admin_op.php?action=manage_users">Manage Users</a></li>
                <li><a href="admin_op.php?action=manage_bookings">Manage Bookings</a></li>
                <li><a href="admin_op.php?action=manage_destinations">Manage Destinations</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2><?php echo ucwords(str_replace('_', ' ', $action)); ?></h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action == 'manage_users' && isset($users)): ?>
            <h3>User List</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['customer_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td class="actions">
                                    <a href="admin_op.php?action=edit_user&id=<?php echo htmlspecialchars($user['customer_id']); ?>">Edit</a>
                                    <a href="admin_op.php?action=delete_user&id=<?php echo htmlspecialchars($user['customer_id']); ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($action == 'edit_user' && isset($user_to_edit)): ?>
            <h3>Edit User: <?php echo htmlspecialchars($user_to_edit['username']); ?></h3>
            <form action="admin_op.php?action=edit_user&id=<?php echo htmlspecialchars($user_to_edit['customer_id']); ?>" method="POST">
                <label for="username">Username:</label><br>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_to_edit['username']); ?>" required><br><br>

                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" required><br><br>

                <label for="role">Role:</label><br>
                <select id="role" name="role">
                    <option value="user" <?php echo ($user_to_edit['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo ($user_to_edit['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select><br><br>

                <input type="submit" value="Update User">
            </form>
        <?php elseif ($action == 'manage_bookings' && isset($bookings)): ?>
            <h3>Booking List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer Username</th>
                        <th>Destination</th>
                        <th>Booking Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                <td><?php echo htmlspecialchars($booking['destination_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                <td><?php echo htmlspecialchars($booking['status']); ?></td>
                                <td class="actions">
                                    <!-- Add edit/delete/view booking actions here -->
                                    <a href="#">View</a> | <a href="#">Edit</a> | <a href="#">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No bookings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($action == 'manage_destinations' && isset($destinations)): ?>
            <h3>Destination List</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($destinations) > 0): ?>
                        <?php foreach ($destinations as $destination): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($destination['destination_id']); ?></td>
                                <td><?php echo htmlspecialchars($destination['destination_name']); ?></td>
                                <td><?php echo htmlspecialchars($destination['location']); ?></td>
                                <td><?php echo htmlspecialchars($destination['price']); ?></td>
                                <td class="actions">
                                    <!-- Add edit/delete/view destination actions here -->
                                    <a href="#">View</a> | <a href="#">Edit</a> | <a href="#">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No destinations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Traveler Admin Operations. All rights reserved.</p>
    </footer>
</body>
</html>
