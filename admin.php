<?php
session_start();
require_once 'db_connection.php';

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'Access denied. You must be an administrator to view this page.';
    header("Location: index.php");
    exit();
}

// Your existing admin panel logic starts here
// Any previous 'include "junk.php";' or similar direct DB connections should be removed.
// Use $conn for database operations.

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
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
        <h1>Welcome to the Admin Panel, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>This is where you can manage website content and users.</p>

        <!-- Original content of admin.php goes here -->
        <!-- Example: User management, content management links -->
        <section>
            <h2>Manage Users</h2>
            <p>Access user management features via <a href="admin_op.php">Admin Operations</a>.</p>
            <!-- Example: Displaying some basic user stats -->
            <?php
            $user_count = 0;
            $stmt = $conn->prepare("SELECT COUNT(*) FROM customer");
            if ($stmt && $stmt->execute()) {
                $stmt->bind_result($user_count);
                $stmt->fetch();
                $stmt->close();
            }
            ?>
            <p>Total Registered Users: <?php echo $user_count; ?></p>
        </section>
        <!-- End of original content -->

    </main>
</body>
</html>
<?php
$conn->close();
?>