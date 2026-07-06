<?php
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to view guides.';
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
    <title>Traveler - Guides</title>
    <link rel="stylesheet" href="css/guide.css">
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
        <h1>Our Expert Guides</h1>
        <p>Meet the people who will make your journey unforgettable.</p>
        
        <!-- Original content of guide.html starts here -->
        <div class="guide-list">
            <div class="guide-card">
                <img src="images/guide1.jpg" alt="Guide Alex">
                <h3>Alex Johnson</h3>
                <p>Specialty: Adventure Travel, Asia</p>
                <p>Alex has led expeditions across the Himalayas and Southeast Asian jungles.</p>
            </div>
            <div class="guide-card">
                <img src="images/guide2.jpg" alt="Guide Maria">
                <h3>Maria Garcia</h3>
                <p>Specialty: Cultural Tours, Europe</p>
                <p>Maria's passion for history and art brings Europe's rich past to life.</p>
            </div>
            <div class="guide-card">
                <img src="images/guide3.jpg" alt="Guide Ben">
                <h3>Ben Carter</h3>
                <p>Specialty: Wildlife Safaris, Africa</p>
                <p>Ben is an expert in African wildlife and has guided countless successful safaris.</p>
            </div>
        </div>
        <!-- End of original content -->

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>