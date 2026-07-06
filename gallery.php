<?php
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to view the gallery.';
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
    <title>Traveler - Gallery</title>
    <link rel="stylesheet" href="css/gallery.css">
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
        <h1>Our Travel Gallery</h1>
        <p>A collection of stunning moments from our tours.</p>
        
        <!-- Original content of gallery.html starts here -->
        <div class="image-gallery">
            <img src="images/gallery1.jpg" alt="Scenic View 1">
            <img src="images/gallery2.jpg" alt="Scenic View 2">
            <img src="images/gallery3.jpg" alt="Scenic View 3">
            <img src="images/gallery4.jpg" alt="Scenic View 4">
            <img src="images/gallery5.jpg" alt="Scenic View 5">
            <img src="images/gallery6.jpg" alt="Scenic View 6">
        </div>
        <!-- End of original content -->

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>