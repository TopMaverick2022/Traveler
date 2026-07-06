<?php
session_start();
require_once 'db_connection.php'; // Include the new centralized database connection

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to proceed with payment.';
    header("Location: index.php");
    exit();
}

// Any existing 'include "junk.php";' or 'include "infop.php";' should be removed.
// All database interactions in this file should now use the $conn variable.

$username = htmlspecialchars($_SESSION['username']);
$user_role = htmlspecialchars($_SESSION['role']);

$payment_message = '';
if (isset($_SESSION['payment_message'])) {
    $payment_message = $_SESSION['payment_message'];
    unset($_SESSION['payment_message']);
}

// --- Start of original payment.php logic (adapted) ---
// This page would typically display booking details and a payment form.
// It might fetch details for a specific booking ID from the database.

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$booking_details = null;

if ($booking_id > 0) {
    $sql = "SELECT destination, travel_date, num_travelers FROM bookings WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $booking_details = $result->fetch_assoc();
                // Calculate amount based on booking details, for example:
                $amount = $booking_details['num_travelers'] * 100; // Example: $100 per traveler
                $booking_details['amount'] = $amount; // Add amount to details
            } else {
                $payment_message = 'Booking not found or not authorized.';
            }
        } else {
            $payment_message = 'Error fetching booking details: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $payment_message = 'Database error: Could not prepare statement.';
    }
} else if ($_SERVER["REQUEST_METHOD"] != "POST") { // Only show this if not coming from a form submission trying to process
    $payment_message = 'No booking ID provided. Please go through the booking process.';
}

$conn->close();
// --- End of original payment.php logic ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Payment</title>
    <link rel="stylesheet" href="css/payment.css">
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
        <h1>Complete Your Payment</h1>
        <?php if ($payment_message): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($payment_message); ?></p>
        <?php endif; ?>

        <?php if ($booking_details): ?>
            <div class="booking-summary">
                <h2>Booking Summary</h2>
                <p><strong>Destination:</strong> <?php echo htmlspecialchars($booking_details['destination']); ?></p>
                <p><strong>Travel Date:</strong> <?php echo htmlspecialchars($booking_details['travel_date']); ?></p>
                <p><strong>Number of Travelers:</strong> <?php echo htmlspecialchars($booking_details['num_travelers']); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($booking_details['amount'], 2); ?></p>
            </div>

            <form action="process_payment.php" method="post" class="payment-form">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="amount" value="<?php echo $booking_details['amount']; ?>">

                <h2>Payment Details</h2>
                <label for="card_number">Card Number:</label>
                <input type="text" id="card_number" name="card_number" placeholder="**** **** **** ****" required pattern="[0-9]{13,16}" title="13 to 16 digits">

                <label for="expiry_date">Expiry Date:</label>
                <input type="month" id="expiry_date" name="expiry_date" required>

                <label for="cvv">CVV:</label>
                <input type="text" id="cvv" name="cvv" placeholder="***" required pattern="[0-9]{3,4}" title="3 or 4 digits">

                <button type="submit">Pay Now</button>
            </form>
            <script src="js/payment.js"></script> <!-- Your existing payment JS -->
        <?php else: ?>
            <p>Please go back to the <a href="booking.php">booking page</a> to start a new booking.</p>
        <?php endif; ?>

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>