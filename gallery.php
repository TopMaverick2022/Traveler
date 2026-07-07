<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Gallery</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/gallery.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="mainPage.php">Traveler</a>
            </div>
            <ul class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="mainPage.php">Home</a></li>
                    <li><a href="destination.php">Destinations</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="guide.php">Guides</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                    <li><a href="info.php">About Us</a></li>
                    <li><span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="index.php">Sign In</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="gallery-page">
        <h1>Our Travel Gallery</h1>
        <p>A collection of stunning moments from our journeys.</p>

        <div class="gallery-grid">
            <div class="gallery-item">
                <img src="https://via.placeholder.com/400x300?text=MountainView" alt="Mountain View">
                <div class="caption">Majestic Mountains</div>
            </div>
            <div class="gallery-item">
                <img src="https://via.placeholder.com/400x300?text=BeachSunset" alt="Beach Sunset">
                <div class="caption">Serene Beach Sunset</div>
            </div>
            <div class="gallery-item">
                <img src="https://via.placeholder.com/400x300?text=Cityscape" alt="Cityscape">
                <div class="caption">Vibrant City Life</div>
            </div>
            <div class="gallery-item">
                <img src="https://via.placeholder.com/400x300?text=ForestPath" alt="Forest Path">
                <div class="caption">Enchanting Forest Path</div>
            </div>
            <div class="gallery-item">
                <img src="https://via.placeholder.com/400x300?text=DesertOasis" alt="Desert Oasis">
                <div class="caption">Desert Oasis</div>
            </div>
            <div class="gallery-item">
                <img src="https://via.placeholder.com/400x300?text=NorthernLights" alt="Northern Lights">
                <div class="caption">Aurora Borealis</div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
