<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: index.php");
    exit();
}

// No database connection needed for static info page unless dynamic content is added
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - About Us</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/info.css">
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

    <main class="info-page">
        <h1>About Traveler</h1>
        <p>Welcome to Traveler, your ultimate partner in discovering the world's most breathtaking destinations.</p>

        <section class="our-story">
            <h2>Our Story</h2>
            <p>Founded in [Year], Traveler began with a simple vision: to make the wonders of global travel accessible to everyone. We believe that exploring new cultures, witnessing stunning landscapes, and creating cherished memories are fundamental to a fulfilling life. From our humble beginnings, we've grown into a trusted name in the travel industry, serving thousands of adventurers worldwide.</p>
        </section>

        <section class="our-mission">
            <h2>Our Mission</h2>
            <p>Our mission is to inspire, facilitate, and enhance travel experiences. We strive to provide unparalleled service, curate unique itineraries, and ensure every journey is safe, comfortable, and truly unforgettable. We are committed to sustainable tourism and fostering a deep respect for the environments and communities we visit.</p>
        </section>

        <section class="why-choose-us">
            <h2>Why Choose Us?</h2>
            <ul>
                <li><strong>Expert Guidance:</strong> Our team of experienced travel specialists and local guides ensure you get the most authentic experience.</li>
                <li><strong>Tailored Itineraries:</strong> We offer customizable packages to suit every traveler's unique preferences and budget.</li>
                <li><strong>Seamless Planning:</strong> From flights and accommodation to activities and local transport, we handle all the details.</li>
                <li><strong>Customer Support:</strong> 24/7 support before, during, and after your trip for complete peace of mind.</li>
                <li><strong>Sustainable Travel:</strong> We partner with eco-friendly operators and promote responsible tourism practices.</li>
            </ul>
        </section>

        <section class="contact-info">
            <h2>Contact Us</h2>
            <p>Have questions or ready to plan your next adventure? Get in touch!</p>
            <ul>
                <li>Email: info@traveler.com</li>
                <li>Phone: +1 (123) 456-7890</li>
                <li>Address: 123 Travel Avenue, Wanderlust City, World</li>
            </ul>
        </section>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
