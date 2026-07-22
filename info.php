<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // FIX: [Issue 1] Removed setting of $_SESSION['error'] because index.php does not display it,
    // which led to silent failures or a confusing user experience.
    header("Location: index.php");
    exit();
}

// No database connection needed for static info page unless dynamic content is added

// Fix for Issue 1: Extract navigation links into a reusable component.
// This block prepares the navigation links as an array of strings,
// making the navigation dynamic based on login status and reducing redundancy.
// Fix for listed Issue 1: Removed unreachable 'else' branch and redundant 'if' condition as user login is guaranteed by an earlier check.
$username = htmlspecialchars($_SESSION['username']); // Sanitize username once for output
$navLinks = [
    // Fix for listed Issue 2: Converted HTML string concatenation to heredoc syntax for improved readability and maintainability.
    <<<HTML
                    <li><a href="mainPage.php">Home</a></li>
HTML,
    <<<HTML
                    <li><a href="destination.php">Destinations</a></li>
HTML,
    <<<HTML
                    <li><a href="gallery.php">Gallery</a></li>
HTML,
    <<<HTML
                    <li><a href="guide.php">Guides</a></li>
HTML,
    <<<HTML
                    <li><a href="feedback.php">Feedback</a></li>
HTML,
    <<<HTML
                    <li><a href="info.php">About Us</a></li>
HTML,
    // Using variable interpolation within heredoc for the username
    <<<HTML
                    <li><span>Welcome, {$username}</span></li>
HTML,
    <<<HTML
                    <li><a href="logout.php">Logout</a></li>
HTML,
];
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
                <?php
                // Render the dynamically generated navigation links by joining the array elements.
                echo implode("\n", $navLinks);
                ?>
            </ul>
        </nav>
    </header>

    <main class="info-page">
        <h1>About Traveler</h1>
        <p>Welcome to Traveler, your ultimate partner in discovering the world's most breathtaking destinations.</p>

        <section class="our-story">
            <h2>Our Story</h2>
            <!-- Fix for Issue 2: Replace static year with dynamic PHP year. -->
            <p>Founded in <?php echo date('Y'); ?>, Traveler began with a simple vision: to make the wonders of global travel accessible to everyone. We believe that exploring new cultures, witnessing stunning landscapes, and creating cherished memories are fundamental to a fulfilling life. From our humble beginnings, we've grown into a trusted name in the travel industry, serving thousands of adventurers worldwide.</p>
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
                <li><strong>Customer Support:</strong> 24/7 support before, during and after your trip for complete peace of mind.</li>
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
        <!-- FIX: [Issue 2] Dynamically generate copyright year using PHP date('Y') to prevent it from becoming outdated. -->
        <p>&copy; <?php echo date('Y'); ?> Traveler. All rights reserved.</p>
    </footer>
</body>
</html>