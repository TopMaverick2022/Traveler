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

// [Fix 1: Security] Using prepared statements for all queries is a critical best practice to prevent SQL injection,
// even when no user input is involved, ensuring consistency and future-proofing.
// [Fix 2: CodeQuality] Added robust error handling for database operations.
if (!$stmt = $conn->prepare($query)) {
    // Log error details for debugging, but show a generic message to the user.
    error_log("Database prepare error in guide.php: " . $conn->error);
    $_SESSION['error'] = "A system error occurred. Please try again later.";
    header("Location: mainPage.php"); // Redirect to a safe page or error page.
    exit();
}

// Execute the prepared statement.
if (!$stmt->execute()) {
    // Log error details.
    error_log("Database execute error in guide.php: " . $stmt->error);
    $_SESSION['error'] = "A system error occurred. Please try again later.";
    header("Location: mainPage.php");
    exit();
}

$result = $stmt->get_result(); // Get the result set from the executed statement.

if ($result->num_rows > 0) { // Check if there are any rows to fetch
    while ($row = $result->fetch_assoc()) {
        $guides[] = $row;
    }
}
$stmt->close(); // Close the prepared statement.
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