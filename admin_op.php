<?php
session_start();
require_once 'db_connection.php';

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'Access denied. You must be an administrator to perform this operation.';
    header("Location: index.php");
    exit();
}

// Your existing admin operations logic starts here
// Any previous 'include "junk.php";' or similar direct DB connections should be removed.
// Use $conn for database operations.

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Example: Handle a POST request for an admin operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete_user' && isset($_POST['user_id'])) {
        $user_to_delete_id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM customer WHERE id = ? AND role != 'admin'"); // Prevent deleting other admins directly here
        if ($stmt) {
            $stmt->bind_param("i", $user_to_delete_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = 'User deleted successfully!';
                } else {
                    $_SESSION['message'] = 'Failed to delete user or user is an admin.';
                }
            } else {
                $_SESSION['message'] = 'Error deleting user: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Database error: Could not prepare statement.';
        }
        header("Location: admin.php"); // Redirect back to admin panel
        exit();
    }
    // Add more admin operations here as needed
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Operations</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="mainPage.php">Home</a></li>
                <li><a href="admin.php">Admin Panel</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <?php if ($message): ?>
            <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <h1>Admin Operations</h1>
        <p>Here you can perform specific administrative tasks.</p>

        <!-- Original content of admin_op.php goes here -->
        <!-- Example: Displaying a list of users for management -->
        <section>
            <h2>Manage Customers</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT id, username, email, role FROM customer");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                            echo "<td>";
                            if ($row['role'] !== 'admin') { // Don't allow deleting admins through this simple form
                                echo "<form action='admin_op.php' method='post' style='display:inline;'>";
                                echo "<input type='hidden' name='action' value='delete_user'>";
                                echo "<input type='hidden' name='user_id' value='" . $row['id'] . "'>";
                                echo "<button type='submit' onclick='return confirm(\"Are you sure you want to delete this user?\");'>Delete</button>";
                                echo "</form>";
                            } else {
                                echo "(Admin)";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No customers found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
        <!-- End of original content -->

    </main>
</body>
</html>
<?php
$conn->close();
?>