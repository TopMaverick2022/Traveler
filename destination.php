<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// [Fix 4] Helper function to truncate text without cutting words, improving readability.
function truncate_to_word_boundary($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $truncated = mb_substr($text, 0, $length);
    // Find the last space within the truncated part to avoid cutting a word
    if (($last_space = mb_strrpos($truncated, ' ')) !== false) {
        return mb_substr($truncated, 0, $last_space) . $suffix;
    }
    // If no space found (e.g., a single very long word), truncate hard
    return $truncated . $suffix;
}

// Fetch destinations (example)
$destinations = [];
$db_error = false; // [Fix 1] Flag to indicate database error.
$query = "SELECT destination_id, destination_name, location, description, price, image_url FROM destination";

// [Line 20] [Fix 1] [Security] Refactored to use a prepared statement for all database interactions.
// This establishes a secure pattern, making it safer to introduce user-provided data
// in the future without risking SQL injection, even for currently static queries.
$stmt = $conn->prepare($query);

if ($stmt === false) {
    // Error during statement preparation
    error_log("Database statement preparation failed: " . $conn->error);
    $db_error = true;
    $_SESSION['error'] = "Could not retrieve destinations at this time. Please try again later.";
} else {
    // Statement prepared successfully, now execute
    if (!$stmt->execute()) {
        // Error during statement execution
        error_log("Database statement execution failed: " . $stmt->error);
        $db_error = true;
        $_SESSION['error'] = "Could not retrieve destinations at this time. Please try again later.";
    } else {
        // Execution successful, get result set
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $destinations[] = $row;
            }
        } else {
            // Log the database error and set an error flag for user feedback if result retrieval fails.
            error_log("Database result retrieval failed after execution: " . $stmt->error);
            $db_error = true;
            $_SESSION['error'] = "Could not retrieve destinations at this time. Please try again later.";
        }
    }
    $stmt->close(); // Always close the statement
}
?>
<?php
// [Fix 3a] Externalized common HTML header structure into a template file for better modularity and maintainability.
require_once 'templates/header.php';
?>

    <main class="destination-page">
        <h1>Our Destinations</h1>
        <p>Discover amazing places around the world.</p>

        <div class="destination-grid">
            <?php if ($db_error): // [Fix 1 continued] Display specific database error message if query failed. ?>
                <p><?php echo htmlspecialchars($_SESSION['error'] ?? 'An unexpected database error occurred.'); unset($_SESSION['error']); ?></p>
            <?php elseif (count($destinations) > 0): ?>
                <?php foreach ($destinations as $destination): ?>
                    <div class="destination-card">
                        <img src="<?php echo htmlspecialchars($destination['image_url'] ?? 'https://via.placeholder.com/300x200?text=Destination'); ?>" alt="<?php echo htmlspecialchars($destination['destination_name']); ?>">
                        <h3><?php echo htmlspecialchars($destination['destination_name']); ?></h3>
                        <p><?php echo htmlspecialchars($destination['location']); ?></p>
                        <p class="price">From $<?php echo htmlspecialchars(number_format($destination['price'], 2)); ?></p>
                        <p><?php echo htmlspecialchars(truncate_to_word_boundary($destination['description'], 100)); // [Fix 4 continued] Used the new helper function for word-boundary safe truncation. ?></p>
                        <a href="#" class="btn-small">View Details</a>
                        <a href="booking.php?destination_id=<?php echo htmlspecialchars($destination['destination_id']); ?>" class="btn-small">Book Now</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No destinations available at the moment.</p>
            <?php endif; ?>
        </div>
    </main>

<?php
// [Fix 3b] Externalized common HTML footer structure into a template file for better modularity and maintainability.
require_once 'templates/footer.php';
?>
<?php
// [Fix 2] Moved $conn->close() to the very end of the script, ensuring the database connection remains open for any potential includes or further logic throughout the page rendering lifecycle.
$conn->close();
?>