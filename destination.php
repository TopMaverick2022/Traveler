<?php
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to view destinations.';
    header("Location: index.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$user_role = htmlspecialchars($_SESSION['role']);

// Include your database connection if this page requires DB interaction
// require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Destinations</title>
    <link rel="stylesheet" href="css/destination.css">
    <link rel="stylesheet" href="css/style.css"> <!-- For header/footer styling -->
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
                    <li><span>Welcome, <?php echo $username; ?>!</span></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php if ($user_role === 'admin'): ?>
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
        <h1>Our Destinations</h1>
        <p>Explore a variety of breathtaking places around the world.</p>
        
        <!-- Original content of destination.html starts here -->
        <div class="destination-list">
            <div class="destination-item">
                <h2>Japan</h2>
                <p>Experience the blend of ancient traditions and modern wonders.</p>
                <img src="images/japan.jpg" alt="Japan">
            </div>
            <div class="destination-item">
                <h2>Italy</h2>
                <p>Savor exquisite cuisine and marvel at historical art and architecture.</p>
                <img src="images/italy.jpg" alt="Italy">
            </div>
            <div class="destination-item">
                <h2>New Zealand</h2>
                <p>Discover stunning landscapes, from fjords to volcanic plateaus.</p>
                <img src="images/newzealand.jpg" alt="New Zealand">
            </div>
        </div>
        <!-- End of original content -->

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>