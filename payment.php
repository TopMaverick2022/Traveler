<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to proceed with payment.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$booking_id = $_GET['booking_id'] ?? null;
$total_amount = 0;
$destination_name = 'N/A';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Fetch booking details for display
if ($booking_id) {
    $stmt = $conn->prepare("SELECT b.total_price, d.destination_name FROM booking b JOIN destination d ON b.destination_id = d.destination_id WHERE b.booking_id = ? AND b.customer_id = ?");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $booking = $result->fetch_assoc();
        $total_amount = $booking['total_price'];
        $destination_name = $booking['destination_name'];
    } else {
        $error = "Booking not found or you don't have access to it.";
        $booking_id = null;
    }
    $stmt->close();
} else {
    $error = "No booking ID provided.";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Payment</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/payment.css">
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

    <main class="payment-page">
        <h1>Complete Your Payment</h1>
        <p>Destination: <?php echo htmlspecialchars($destination_name); ?></p>
        <p>Amount Due: <strong>$<?php echo htmlspecialchars(number_format($total_amount, 2)); ?></strong></p>

        <?php if ($message): ?>
            <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($booking_id && $total_amount > 0): ?>
        <div class="payment-form-container">
            <form action="process_payment.php" method="POST">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($total_amount); ?>">

                <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required pattern="[0-9]{13,19}" title="Credit card number (13-19 digits)">
                </div>

                <div class="form-group">
                    <label for="card_name">Name on Card</label>
                    <input type="text" id="card_name" name="card_name" required>
                </div>

                <div class="form-row">
                    <div class="form-group expiry-date">
                        <label for="expiry_month">Expiry Month</label>
                        <input type="text" id="expiry_month" name="expiry_month" placeholder="MM" required pattern="(0[1-9]|1[0-2])" title="Two-digit month (01-12)">
                    </div>
                    <div class="form-group expiry-date">
                        <label for="expiry_year">Expiry Year</label>
                        <input type="text" id="expiry_year" name="expiry_year" placeholder="YY" required pattern="[0-9]{2}" title="Two-digit year">
                    </div>
                    <div class="form-group cvv">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="XXX" required pattern="[0-9]{3,4}" title="3 or 4 digit security code">
                    </div>
                </div>

                <button type="submit" class="pay-button">Pay Now $<?php echo htmlspecialchars(number_format($total_amount, 2)); ?></button>
            </form>
        </div>
        <?php else: ?>
            <p>Unable to process payment without a valid booking. Please go back to <a href="destination.php">Destinations</a> or <a href="mainPage.php">Home</a>.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
    <script src="js/payment.js"></script>
</body>
</html>
