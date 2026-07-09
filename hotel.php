<?php
session_start();
include 'db_connection.php';
include 'config.php'; // For BASE_URL if needed

$hotels = [];
$sql = "SELECT hotel_id, name, location, description, image_url FROM hotels ORDER BY name ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}
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
    <?php include 'mainPage.php'; // Include navigation/header ?>

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
