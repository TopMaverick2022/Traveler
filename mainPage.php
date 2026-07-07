<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'You must be logged in to view the main page.';
    header('Location: index.php'); // Redirect to login page
    exit();
}

// Access user information if needed
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Main Page</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/destination.css">
    <link rel="stylesheet" href="css/guide.css">
    <!-- Add any other CSS files specific to the main page -->
</head>
<body>
    <header>
        <nav>
            <div class="logo">Traveler</div>
            <ul class="nav-links">
                <li><a href="mainPage.php">Home</a></li>
                <li><a href="destination.php">Destinations</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="guide.php">Guides</a></li>
                <li><a href="feedback.php">Feedback</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><span class="user-greeting">Welcome, <?php echo $username; ?>!</span></li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section id="hero">
            <h1>Explore the World with Traveler</h1>
            <p>Your ultimate guide to unforgettable journeys.</p>
            <a href="destination.php" class="btn">Discover Destinations</a>
        </section>

        <section id="featured-destinations">
            <h2>Featured Destinations</h2>
            <div class="destination-grid">
                <!-- Example destination cards -->
                <div class="destination-card">
                    <img src="https://via.placeholder.com/300x200?text=Paris" alt="Paris">
                    <h3>Paris, France</h3>
                    <p>The city of love and lights.</p>
                    <a href="info.php?dest=paris" class="btn-small">Learn More</a>
                </div>
                <div class="destination-card">
                    <img src="https://via.placeholder.com/300x200?text=Tokyo" alt="Tokyo">
                    <h3>Tokyo, Japan</h3>
                    <p>A blend of tradition and modernity.</p>
                    <a href="info.php?dest=tokyo" class="btn-small">Learn More</a>
                </div>
                <div class="destination-card">
                    <img src="https://via.placeholder.com/300x200?text=NewYork" alt="New York">
                    <h3>New York, USA</h3>
                    <p>The city that never sleeps.</p>
                    <a href="info.php?dest=newyork" class="btn-small">Learn More</a>
                </div>
            </div>
        </section>

        <section id="our-guides">
            <h2>Our Expert Guides</h2>
            <div class="guide-grid">
                <div class="guide-card">
                    <img src="https://via.placeholder.com/150?text=Guide1" alt="Guide Name">
                    <h3>John Doe</h3>
                    <p>Specializes in European tours.</p>
                    <a href="guide.php?guide=john" class="btn-small">View Profile</a>
                </div>
                <div class="guide-card">
                    <img src="https://via.placeholder.com/150?text=Guide2" alt="Guide Name">
                    <h3>Jane Smith</h3>
                    <p>Expert in Asian adventures.</p>
                    <a href="guide.php?guide=jane" class="btn-small">View Profile</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Traveler. All rights reserved.</p>
    </footer>
</body>
</html>