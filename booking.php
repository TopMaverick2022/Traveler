<?php
session_start();
require_once 'db_connection.php'; // Include the new centralized database connection

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to make a booking.';
    header("Location: index.php");
    exit();
}

// Any existing 'include "junk.php";' or 'include "infop.php";' should be removed.
// All database interactions in this file should now use the $conn variable.

$username = htmlspecialchars($_SESSION['username']);
$user_role = htmlspecialchars($_SESSION['role']);

// --- Start of original booking.php logic (adapted) ---
$booking_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $travel_date = trim($_POST['travel_date']);
    $num_travelers = intval($_POST['num_travelers']);
    $user_id = $_SESSION['user_id'];

    if (empty($destination) || empty($travel_date) || $num_travelers <= 0) {
        $booking_message = 'Please fill all booking details correctly.';
    } else {
        // Example: Insert booking into a 'bookings' table
        // Assuming 'bookings' table has: id, user_id, destination, travel_date, num_travelers, booking_date
        $sql = "INSERT INTO bookings (user_id, destination, travel_date, num_travelers, booking_date) VALUES (?, ?, ?, ?, NOW())";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isii", $user_id, $destination, $travel_date, $num_travelers);
            if ($stmt->execute()) {
                $booking_message = 'Booking successful! You can proceed to payment.';
                // Optionally redirect to payment page
                // header("Location: payment.php?booking_id=" . $stmt->insert_id);
                // exit();
            } else {
                $booking_message = 'Error during booking: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $booking_message = 'Database error: Could not prepare statement for booking.';
        }
    }
}

// --- End of original booking.php logic ---

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Book Your Trip</title>
    <link rel="stylesheet" href="css/booking.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="mainPage.php">Home</a></li>
                <li><a href="destination.php">Destinations</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="guide.php">Guides</a></li>
                <li><a href="feedback.php">Feedback</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><span>Welcome, <?php echo $username; ?>!</span></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Book Your Dream Trip</h1>
        <?php if ($booking_message): ?>
            <p style="color: <?php echo (strpos($booking_message, 'Error') !== false) ? 'red' : 'green'; ?>; text-align: center;"><?php echo htmlspecialchars($booking_message); ?></p>
        <?php endif; ?>

        <form action="booking.php" method="post" class="booking-form">
            <label for="destination">Destination:</label>
            <input type="text" id="destination" name="destination" placeholder="e.g., Paris, Japan" required>

            <label for="travel_date">Travel Date:</label>
            <input type="date" id="travel_date" name="travel_date" required>

            <label for="num_travelers">Number of Travelers:</label>
            <input type="number" id="num_travelers" name="num_travelers" min="1" value="1" required>

            <button type="submit">Proceed to Payment</button>
        </form>

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>