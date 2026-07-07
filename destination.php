<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// Fetch destinations (example)
$destinations = [];
$query = "SELECT destination_id, destination_name, location, description, price, image_url FROM destination";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Destinations</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/destination.css">
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

    <main class="destination-page">
        <h1>Our Destinations</h1>
        <p>Discover amazing places around the world.</p>

        <div class="destination-grid">
            <?php if (count($destinations) > 0): ?>
                <?php foreach ($destinations as $destination): ?>
                    <div class="destination-card">
                        <img src="<?php echo htmlspecialchars($destination['image_url'] ?? 'https://via.placeholder.com/300x200?text=Destination'); ?>" alt="<?php echo htmlspecialchars($destination['destination_name']); ?>">
                        <h3><?php echo htmlspecialchars($destination['destination_name']); ?></h3>
                        <p><?php echo htmlspecialchars($destination['location']); ?></p>
                        <p class="price">From $<?php echo htmlspecialchars(number_format($destination['price'], 2)); ?></p>
                        <p><?php echo htmlspecialchars(substr($destination['description'], 0, 100)); ?>...</p>
                        <a href="#" class="btn-small">View Details</a>
                        <a href="booking.php?destination_id=<?php echo htmlspecialchars($destination['destination_id']); ?>" class="btn-small">Book Now</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No destinations available at the moment.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
