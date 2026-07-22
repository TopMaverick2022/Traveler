<?php
// This file is typically included as a header/navigation bar
// It should not have session_start() if it's included in other files that already call it.
// session_start(); // Only uncomment if this file is meant to be accessed directly and starts a new session

// [Line 10] [CodeQuality] FIX: Ensure config.php is always loaded to provide consistent BASE_URL definition.
// This removes the conditional fallback, preventing potential configuration inconsistencies.
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Guest';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// The conditional definition of BASE_URL was removed as config.php is now guaranteed to be included,
// ensuring BASE_URL is defined consistently at the application entry point.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/destination.css"> <!-- Existing CSS -->
    <link rel="stylesheet" href="css/hotel.css"> <!-- New Hotel CSS -->
    <!-- You might include other CSS files conditionally or based on the page -->
</head>
<body>
    <header>
        <div class="navbar">
            <a href="index.php" class="logo">Traveler</a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="destination.php">Destinations</a></li>
                    <li><a href="hotel.php">Hotels</a></li> <!-- New Hotel Link -->
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="guide.php">Guides</a></li>
                    <li><a href="info.php">About Us</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                    <?php if ($is_logged_in): ?>
                        <li><a href="#">Welcome, <?php echo htmlspecialchars($username); ?></a></li>
                        <?php if ($is_admin): ?>
                            <li><a href="admin.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="signin.php">Sign In</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <!-- The rest of the page content will follow after this inclusion -->