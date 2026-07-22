<?php
session_start();
include 'db_connection.php';
include 'config.php'; // For BASE_URL if needed

$hotels = [];
// [Fix Issue 1 & 2] Use prepared statements for all queries, even without user input, for consistency and security best practices.
// [Fix Issue 2] Add comprehensive error handling for statement preparation and execution.
$stmt = $conn->prepare("SELECT hotel_id, name, location, description, image_url FROM hotels ORDER BY name ASC");
if (!$stmt) {
    // Log the error for debugging; show a generic message to the user for security.
    error_log("Failed to prepare hotel list statement: " . $conn->error);
    die("An internal server error occurred. Please try again later.");
}
$stmt->execute();
if ($stmt->errno) {
    // Log the error for debugging; show a generic message to the user for security.
    error_log("Failed to execute hotel list statement: " . $stmt->error);
    die("An internal server error occurred during data retrieval. Please try again later.");
}
$result = $stmt->get_result(); // Get the result set from the executed statement

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
    $result->free(); // Free the result set
}
$stmt->close(); // Close the prepared statement
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Hotels - Traveler</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hotel.css">
</head>
<body>
    <?php
    // [Fix Issue 3] Replaced 'mainPage.php' with 'header.php'.
    // Including 'mainPage.php' directly is an architectural flaw if it contains full HTML document tags (<!DOCTYPE html>, <html>, <head>, <body>).
    // Header components should be fragments containing only the necessary HTML for the header/navigation section, preventing duplicate document structures.
    include 'header.php'; // Include navigation/header fragment
    ?>

    <main class="container">
        <h1>Explore Our Hotels</h1>
        <p class="lead-text">Discover amazing places to stay around the world.</p>

        <?php if (!empty($hotels)): ?>
            <div class="hotel-list">
                <?php foreach ($hotels as $hotel): ?>
                    <div class="hotel-card">
                        <img src="<?php echo htmlspecialchars($hotel['image_url'] ?? 'https://via.placeholder.com/400x200?text=Hotel+Image'); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                        <div class="hotel-card-content">
                            <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                            <p class="location">Location: <?php echo htmlspecialchars($hotel['location']); ?></p>
                            <p><?php echo htmlspecialchars(substr($hotel['description'], 0, 100)); ?>...</p>
                            <a href="hotel_details.php?hotel_id=<?php echo htmlspecialchars($hotel['hotel_id']); ?>" class="btn-details">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-results">No hotels found at the moment. Please check back later!</p>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; // Assuming you have a footer.php ?>
</body>
</html>