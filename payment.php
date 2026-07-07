<?php
session_start();
require_once 'config.php';
require_once 'db_connection.php'; // Assuming this file connects to your database

// Check if user is logged in (example, adjust based on your auth)
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

// --- Fetch booking details (Example) ---
// In a real application, you would pass a booking ID to this page,
// fetch its details from the database, and calculate the final amount.
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$amount_to_pay = 0;
$booking_details = null;

if ($booking_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $booking_details = $result->fetch_assoc();
        $amount_to_pay = $booking_details['amount']; // Get amount from booking
    } else {
        // Handle case where booking not found or doesn't belong to user
        echo "<script>alert('Booking not found or you do not have permission.'); window.location.href='mainPage.php';</script>";
        exit();
    }
    $stmt->close();
} else {
    echo "<script>alert('No booking ID provided.'); window.location.href='mainPage.php';</script>";
    exit();
}

$customer_id = $_SESSION['user_id']; // Assuming user_id is the customer_id
$currency = DEFAULT_CURRENCY; // Defined in config.php

// Pass Stripe Publishable Key to JavaScript
$stripe_publishable_key = STRIPE_PUBLISHABLE_KEY;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/payment.css">
    <!-- Stripe.js v3 -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <div class="container">
        <h1>Complete Your Payment</h1>
        <?php if (isset($_SESSION['payment_message'])): ?>
            <div class="message">
                <?php echo $_SESSION['payment_message']; unset($_SESSION['payment_message']); ?>
            </div>
        <?php endif; ?>

        <div class="booking-summary">
            <h2>Booking Summary</h2>
            <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking_details['booking_id']); ?></p>
            <p><strong>Destination:</strong> <?php echo htmlspecialchars($booking_details['destination_id']); // Assuming a destination ID for display ?></p>
            <p><strong>Amount Due:</strong> <?php echo htmlspecialchars(number_format($amount_to_pay, 2)) . ' ' . strtoupper($currency); ?></p>
            <!-- Add more booking details as needed -->
        </div>

        <form id="payment-form" action="process_payment.php" method="post">
            <input type="hidden" id="booking-id" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
            <input type="hidden" id="customer-id" name="customer_id" value="<?php echo htmlspecialchars($customer_id); ?>">
            <input type="hidden" id="amount" name="amount" value="<?php echo htmlspecialchars($amount_to_pay); ?>">
            <input type="hidden" id="currency" name="currency" value="<?php echo htmlspecialchars($currency); ?>">
            <input type="hidden" id="payment-method-id" name="payment_method_id">

            <div class="form-group">
                <label for="card-element">
                    Credit or debit card
                </label>
                <div id="card-element">
                    <!-- A Stripe Element will be inserted here. -->
                </div>

                <!-- Used to display form errors. -->
                <div id="card-errors" role="alert"></div>
            </div>

            <button id="submit-button" class="btn">Pay Now</button>
        </form>
    </div>

    <script type="text/javascript">
        // Pass PHP variable to JavaScript
        const stripe_publishable_key = '<?php echo $stripe_publishable_key; ?>';
    </script>
    <script src="js/payment.js"></script>
</body>
</html>
