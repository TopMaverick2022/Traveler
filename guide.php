<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// Fetch guides (example)
$guides = [];
// Assuming a 'guides' table: guide_id, name, specialization, bio, image_url
$query = "SELECT guide_id, name, specialization, bio, image_url FROM guide";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $guides[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Guides</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/guide.css">
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

    <main class="guide-page">
        <h1>Our Expert Guides</h1>
        <p>Meet the professionals who will make your journey unforgettable.</p>

        <div class="guide-grid">
            <?php if (count($guides) > 0): ?>
                <?php foreach ($guides as $guide): ?>
                    <div class="guide-card">
                        <img src="<?php echo htmlspecialchars($guide['image_url'] ?? 'https://via.placeholder.com/150?text=Guide'); ?>" alt="<?php echo htmlspecialchars($guide['name']); ?>">
                        <h3><?php echo htmlspecialchars($guide['name']); ?></h3>
                        <p class="specialization"><?php echo htmlspecialchars($guide['specialization']); ?></p>
                        <p class="bio"><?php echo htmlspecialchars($guide['bio']); ?></p>
                        <a href="#" class="btn-small">Contact Guide</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No guides available at the moment.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
