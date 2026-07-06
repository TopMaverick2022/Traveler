<?php
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to access the main page.';
    header("Location: index.php");
    exit();
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

$username = htmlspecialchars($_SESSION['username']);
$user_role = htmlspecialchars($_SESSION['role']);

// Include your database connection if this page requires DB interaction beyond session
// require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Home</title>
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
                <?php if (isset($_SESSION['user_id'])): // If user is logged in ?>
                    <li><span>Welcome, <?php echo $username; ?>!</span></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php if ($user_role === 'admin'): // Show admin panel link for admins ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                <?php else: // If user is not logged in, typically these links would be hidden on a 'mainPage' that requires login ?>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <?php if ($message): ?>
            <p style="color: green; text-align: center;"><?php echo $message; ?></p>
        <?php endif; ?>
        
        <!-- Original content of mainPage.html starts here -->
        <h1>Welcome to Traveler, <?php echo $username; ?>!</h1>
        <p>Your role: <?php echo $user_role; ?></p>
        <p>Discover the world's most breathtaking destinations and plan your next adventure with us.</p>
        
        <!-- Placeholder for main page content from mainPage.html -->
        <section class="hero">
            <h2>Explore the Unseen</h2>
            <p>From serene beaches to majestic mountains, find your perfect escape.</p>
            <a href="destination.php" class="btn">View Destinations</a>
        </section>

        <section class="featured-destinations">
            <h2>Featured Destinations</h2>
            <div class="destination-grid">
                <div class="destination-card">
                    <h3>Paris, France</h3>
                    <p>The City of Lights awaits!</p>
                </div>
                <div class="destination-card">
                    <h3>Kyoto, Japan</h3>
                    <p>Experience ancient traditions and stunning gardens.</p>
                </div>
                <div class="destination-card">
                    <h3>Machu Picchu, Peru</h3>
                    <p>Hike to the lost city of the Incas.</p>
                </div>
            </div>
        </section>

        <!-- End of original content -->

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>