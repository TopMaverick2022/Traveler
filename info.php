<?php
session_start();
require_once 'db_connection.php'; // Include the new centralized database connection

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to view user information.';
    header("Location: index.php");
    exit();
}

// Any existing 'include "junk.php";' or 'include "infop.php";' should be removed.
// All database interactions in this file should now use the $conn variable.

$username_session = htmlspecialchars($_SESSION['username']);
$user_role_session = htmlspecialchars($_SESSION['role']);

$user_data = null;
$message = '';

// Fetch current user's data from database
$sql = "SELECT username, email, role FROM customer WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();
        } else {
            $message = 'User data not found.';
        }
    } else {
        $message = 'Error fetching user data: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $message = 'Database error: Could not prepare statement.';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - User Info</title>
    <link rel="stylesheet" href="css/info.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="mainPage.php">Home</a></li>
                <li><a href="destination.php">Destinations</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="guide.php">Guides</a></li>
                <li><a href="feedback.php">Feedback</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><span>Welcome, <?php echo $username_session; ?>!</span></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php if ($user_role_session === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Your Profile Information</h1>
        <?php if ($message): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <div class="user-profile">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($user_data['role']); ?></p>
                <!-- Add more user-specific information here -->
                <p>This is where you might see your past bookings, preferences, etc.</p>
            </div>
        <?php else: ?>
            <p>Unable to load user profile. Please try again later.</p>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>