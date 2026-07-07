<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: index.php");
    exit();
}

// Optional: Get and clear any session messages
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
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

    <main>
        <?php if ($message): ?>
            <p class="success-message" style="text-align: center; color: green; font-weight: bold;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <section class="hero">
            <div class="hero-content">
                <h1>Explore the World with Traveler</h1>
                <p>Your journey begins here. Discover breathtaking destinations and create unforgettable memories.</p>
                <a href="destination.php" class="btn">Find Your Adventure</a>
            </div>
        </section>

        <section class="featured-destinations">
            <h2>Featured Destinations</h2>
            <div class="destination-grid">
                <!-- Example destination cards -->
                <div class="destination-card">
                    <img src="https://via.placeholder.com/300x200?text=Paris" alt="Paris">
                    <h3>Paris, France</h3>
                    <p>The City of Love and Lights.</p>
                    <a href="destination.php?id=1" class="btn-small">View Details</a>
                </div>
                <div class="destination-card">
                    <img src="https://via.placeholder.com/300x200?text=Tokyo" alt="Tokyo">
                    <h3>Tokyo, Japan</h3>
                    <p>A vibrant blend of tradition and modernity.</p>
                    <a href="destination.php?id=2" class="btn-small">View Details</a>
                </div>
                <div class="destination-card">
                    <img src="https://via.placeholder.com/300x200?text=Maui" alt="Maui">
                    <h3>Maui, Hawaii</h3>
                    <p>Pristine beaches and lush landscapes.</p>
                    <a href="destination.php?id=3" class="btn-small">View Details</a>
                </div>
            </div>
        </section>

        <section class="testimonials">
            <h2>What Our Travelers Say</h2>
            <div class="testimonial-slider">
                <div class="testimonial-item">
                    <p>"Traveler made my dream vacation a reality! Seamless booking and incredible experiences."</p>
                    <span>- Jane Doe</span>
                </div>
                <div class="testimonial-item">
                    <p>"Highly recommend Traveler for their excellent service and diverse range of trips."</p>
                    <span>- John Smith</span>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
